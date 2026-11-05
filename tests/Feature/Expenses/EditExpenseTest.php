<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EditExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function expense(array $attributes = []): Expense
    {
        $user = User::firstOrCreate(
            ['email' => config('midas.owner.email')],
            ['name' => 'Owner', 'password' => 'secret'],
        );

        return Expense::create($attributes + [
            'user_id' => $user->id,
            'date' => '2026-11-05',
            'amount' => 2550,
            'description' => 'Almoço',
        ]);
    }

    public function test_the_edit_screen_opens_with_the_current_data(): void
    {
        $expense = $this->expense();

        $this->get(route('expenses.edit', $expense))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Edit')
                ->where('expense.id', $expense->id)
                ->where('expense.amount', '25.50')
                ->where('expense.date', '2026-11-05')
                ->where('expense.description', 'Almoço')
            );
    }

    public function test_changes_are_saved_and_shown_in_the_list(): void
    {
        $expense = $this->expense();

        $this->put(route('expenses.update', $expense), [
            'amount' => '30.00',
            'date' => '2026-11-05',
            'description' => 'Almoço com bebida',
        ])->assertRedirect(route('expenses.index'));

        $expense->refresh();
        $this->assertSame(3000, $expense->amount);
        $this->assertSame('Almoço com bebida', $expense->description);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('days.0.expenses.0.description', 'Almoço com bebida')
                ->where('days.0.expenses.0.amount', 3000)
            );
    }

    public function test_changing_the_date_moves_the_expense_and_updates_the_day_totals(): void
    {
        $moved = $this->expense(['date' => '2026-11-05', 'amount' => 1000]);
        $this->expense(['date' => '2026-11-05', 'amount' => 500]);

        $this->put(route('expenses.update', $moved), ['amount' => '10', 'date' => '2026-11-03']);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('days', 2)
                ->where('days.0.date', '2026-11-05')
                ->where('days.0.total', 500)
                ->where('days.1.date', '2026-11-03')
                ->where('days.1.total', 1000)
            );
    }

    public function test_the_description_can_be_cleared(): void
    {
        $expense = $this->expense();

        $this->put(route('expenses.update', $expense), ['amount' => '25.50', 'date' => '2026-11-05', 'description' => '']);

        $this->assertNull($expense->fresh()->description);
    }

    public static function invalidPayloads(): array
    {
        return [
            'sem valor' => [['date' => '2026-11-05'], 'amount'],
            'valor zero' => [['amount' => '0', 'date' => '2026-11-05'], 'amount'],
            'valor negativo' => [['amount' => '-3', 'date' => '2026-11-05'], 'amount'],
            'sem data' => [['amount' => '10'], 'date'],
            'data inválida' => [['amount' => '10', 'date' => '2026-02-30'], 'date'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_the_same_validation_rules_as_registration_apply(array $payload, string $field): void
    {
        $expense = $this->expense();

        $this->from(route('expenses.edit', $expense))
            ->put(route('expenses.update', $expense), $payload)
            ->assertRedirect(route('expenses.edit', $expense))
            ->assertSessionHasErrors($field);

        $expense->refresh();
        $this->assertSame(2550, $expense->amount);
        $this->assertSame('2026-11-05', $expense->date->toDateString());
        $this->assertSame('Almoço', $expense->description);
    }

    public function test_an_expense_from_another_user_cannot_be_opened_or_changed(): void
    {
        $this->expense();
        $other = Expense::create([
            'user_id' => User::factory()->create()->id,
            'date' => '2026-11-05',
            'amount' => 999,
            'description' => 'alheio',
        ]);

        $this->get(route('expenses.edit', $other))->assertNotFound();
        $this->put(route('expenses.update', $other), ['amount' => '1', 'date' => '2026-11-05'])->assertNotFound();
        $this->assertSame(999, $other->fresh()->amount);
    }
}
