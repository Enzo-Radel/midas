<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegisterExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::where('email', config('midas.owner.email'))->firstOrFail();
    }

    public function test_the_new_expense_screen_is_available(): void
    {
        $this->get(route('expenses.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Expenses/Create'));
    }

    public function test_an_expense_can_be_saved_with_only_amount_and_date(): void
    {
        $this->post(route('expenses.store'), ['amount' => '25.50', 'date' => '2026-11-05'])
            ->assertRedirect(route('expenses.index'));

        $expense = Expense::sole();
        $this->assertSame(2550, $expense->amount);
        $this->assertSame('2026-11-05', $expense->date->toDateString());
        $this->assertNull($expense->description);
    }

    public function test_the_expense_belongs_to_the_default_user(): void
    {
        $this->post(route('expenses.store'), ['amount' => '10', 'date' => '2026-11-05']);

        $this->assertTrue(Expense::sole()->user->is($this->owner()));
    }

    public function test_the_description_is_saved_when_informed(): void
    {
        $this->post(route('expenses.store'), [
            'amount' => '25',
            'date' => '2026-11-05',
            'description' => 'Almoço no restaurante',
        ]);

        $this->assertSame('Almoço no restaurante', Expense::sole()->description);
    }

    public function test_past_and_future_dates_are_accepted(): void
    {
        $this->post(route('expenses.store'), ['amount' => '5', 'date' => '2026-10-01'])->assertSessionHasNoErrors();
        $this->post(route('expenses.store'), ['amount' => '5', 'date' => '2027-01-15'])->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing(
            ['2026-10-01', '2027-01-15'],
            Expense::all()->map(fn (Expense $e) => $e->date->toDateString())->all(),
        );
    }

    public static function invalidPayloads(): array
    {
        return [
            'sem valor' => [['date' => '2026-11-05'], 'amount'],
            'valor zero' => [['amount' => '0', 'date' => '2026-11-05'], 'amount'],
            'valor negativo' => [['amount' => '-3', 'date' => '2026-11-05'], 'amount'],
            'valor não numérico' => [['amount' => 'abc', 'date' => '2026-11-05'], 'amount'],
            'valor com mais de duas casas' => [['amount' => '1.999', 'date' => '2026-11-05'], 'amount'],
            'sem data' => [['amount' => '10'], 'date'],
            'data inválida' => [['amount' => '10', 'date' => '2026-02-30'], 'date'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_expenses_are_rejected_with_the_reason(array $payload, string $field): void
    {
        $this->from(route('expenses.create'))
            ->post(route('expenses.store'), $payload)
            ->assertRedirect(route('expenses.create'))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_the_list_shows_expenses_from_most_recent_to_oldest(): void
    {
        $this->post(route('expenses.store'), ['amount' => '1', 'date' => '2026-11-01', 'description' => 'antigo']);
        $this->post(route('expenses.store'), ['amount' => '2', 'date' => '2026-11-05', 'description' => 'recente']);
        $this->post(route('expenses.store'), ['amount' => '3', 'date' => '2026-11-05', 'description' => 'recente e criado depois']);
        $this->post(route('expenses.store'), ['amount' => '4', 'date' => '2026-11-03', 'description' => 'meio']);

        $this->get(route('expenses.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Index')
                ->has('days', 3)
                ->where('days.0.expenses.0.description', 'recente e criado depois')
                ->where('days.0.expenses.1.description', 'recente')
                ->where('days.1.expenses.0.description', 'meio')
                ->where('days.2.expenses.0.description', 'antigo')
                ->where('days.2.expenses.0.amount', 100)
                ->where('days.2.date', '2026-11-01')
            );
    }

    public function test_the_list_does_not_show_expenses_from_other_users(): void
    {
        $this->post(route('expenses.store'), ['amount' => '2', 'date' => '2026-11-05']);
        Expense::create(['amount' => 999, 'date' => '2026-11-05', 'user_id' => User::factory()->create()->id]);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page->has('days', 1)->has('days.0.expenses', 1)->where('days.0.expenses.0.amount', 200));
    }
}
