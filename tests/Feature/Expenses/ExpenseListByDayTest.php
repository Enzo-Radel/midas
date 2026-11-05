<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseListByDayTest extends TestCase
{
    use RefreshDatabase;

    private function spend(string $date, int $cents, ?string $description = null): void
    {
        $user = User::firstOrCreate(
            ['email' => config('midas.owner.email')],
            ['name' => 'Owner', 'password' => 'secret'],
        );

        Expense::create(['user_id' => $user->id, 'date' => $date, 'amount' => $cents, 'description' => $description]);
    }

    public function test_expenses_are_grouped_by_day_from_most_recent_to_oldest(): void
    {
        $this->spend('2026-11-03', 1000, 'a');
        $this->spend('2026-11-05', 2000, 'b');
        $this->spend('2026-11-03', 500, 'c');
        $this->spend('2026-11-04', 700, 'd');

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Index')
                ->has('days', 3)
                ->where('days.0.date', '2026-11-05')
                ->where('days.1.date', '2026-11-04')
                ->where('days.2.date', '2026-11-03')
                ->has('days.2.expenses', 2)
            );
    }

    public function test_each_day_shows_the_exact_sum_of_its_expenses(): void
    {
        $this->spend('2026-11-05', 1999);
        $this->spend('2026-11-05', 1);
        $this->spend('2026-11-05', 2550);
        $this->spend('2026-11-04', 333);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('days.0.total', 4550)
                ->where('days.1.total', 333)
            );
    }

    public function test_within_a_day_the_last_registered_expense_comes_first(): void
    {
        $this->spend('2026-11-05', 100, 'primeiro');
        $this->spend('2026-11-05', 200, 'segundo');

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('days.0.expenses.0.description', 'segundo')
                ->where('days.0.expenses.1.description', 'primeiro')
            );
    }

    public function test_future_days_appear_above_today(): void
    {
        $today = now()->toDateString();
        $future = now()->addDays(10)->toDateString();
        $this->spend($today, 100);
        $this->spend($future, 200);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('days.0.date', $future)
                ->where('days.1.date', $today)
            );
    }

    public function test_the_day_total_ignores_expenses_from_other_users(): void
    {
        $this->spend('2026-11-05', 100);
        Expense::create(['user_id' => User::factory()->create()->id, 'date' => '2026-11-05', 'amount' => 9999]);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page->has('days', 1)->where('days.0.total', 100));
    }

    public function test_without_expenses_the_list_comes_empty(): void
    {
        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page->has('days', 0));
    }
}
