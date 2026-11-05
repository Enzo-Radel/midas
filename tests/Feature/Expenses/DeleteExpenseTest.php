<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeleteExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function expense(string $date, int $cents): Expense
    {
        $user = User::firstOrCreate(
            ['email' => config('midas.owner.email')],
            ['name' => 'Owner', 'password' => 'secret'],
        );

        return Expense::create(['user_id' => $user->id, 'date' => $date, 'amount' => $cents, 'description' => 'gasto']);
    }

    public function test_deleting_removes_the_expense_and_goes_back_to_the_list(): void
    {
        $expense = $this->expense('2026-11-05', 1000);

        $this->delete(route('expenses.destroy', $expense))->assertRedirect(route('expenses.index'));

        $this->assertModelMissing($expense);
    }

    public function test_the_day_total_is_updated_after_deleting(): void
    {
        $removed = $this->expense('2026-11-05', 1000);
        $this->expense('2026-11-05', 250);

        $this->delete(route('expenses.destroy', $removed));

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('days', 1)
                ->where('days.0.total', 250)
                ->has('days.0.expenses', 1)
            );
    }

    public function test_a_day_left_without_expenses_stops_appearing(): void
    {
        $removed = $this->expense('2026-11-05', 1000);
        $this->expense('2026-11-04', 250);

        $this->delete(route('expenses.destroy', $removed));

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('days', 1)
                ->where('days.0.date', '2026-11-04')
            );
    }

    public function test_only_the_chosen_expense_is_deleted(): void
    {
        $removed = $this->expense('2026-11-05', 1000);
        $kept = $this->expense('2026-11-05', 250);

        $this->delete(route('expenses.destroy', $removed));

        $this->assertModelExists($kept);
        $this->assertDatabaseCount('expenses', 1);
    }

    public function test_an_expense_from_another_user_cannot_be_deleted(): void
    {
        $this->expense('2026-11-05', 1000);
        $other = Expense::create([
            'user_id' => User::factory()->create()->id,
            'date' => '2026-11-05',
            'amount' => 999,
        ]);

        $this->delete(route('expenses.destroy', $other))->assertNotFound();

        $this->assertModelExists($other);
    }
}
