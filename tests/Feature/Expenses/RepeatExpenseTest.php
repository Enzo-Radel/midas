<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RepeatExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function lunch(): Expense
    {
        $this->get(route('expenses.create')); // cria o usuário padrão e as categorias
        $owner = User::where('email', config('midas.owner.email'))->firstOrFail();

        $expense = $owner->expenses()->create([
            'amount' => 2550,
            'date' => '2026-10-20',
            'description' => 'Almoço',
            'category_id' => $owner->categories()->where('name', 'Alimentação')->value('id'),
        ]);
        $expense->tags()->attach([
            $owner->tags()->create(['name' => 'trabalho'])->id,
            $owner->tags()->create(['name' => 'almoço'])->id,
        ]);

        return $expense;
    }

    public function test_repeating_opens_the_form_filled_with_the_data_of_the_expense(): void
    {
        $expense = $this->lunch();

        $this->get(route('expenses.repeat', $expense))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Create')
                ->where('prefill.amount', '25.50')
                ->where('prefill.description', 'Almoço')
                ->where('prefill.category_id', $expense->category_id)
                ->where('prefill.tags', ['almoço', 'trabalho'])
                ->has('categories', 8)
                ->has('tags', 2)
            );
    }

    public function test_the_original_date_is_not_carried_over(): void
    {
        $expense = $this->lunch();

        $this->get(route('expenses.repeat', $expense))
            ->assertInertia(fn (Assert $page) => $page->missing('prefill.date'));
    }

    public function test_opening_the_repeat_screen_saves_nothing(): void
    {
        $expense = $this->lunch();

        $this->get(route('expenses.repeat', $expense));

        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame('2026-10-20', $expense->fresh()->date->toDateString());
    }

    public function test_saving_creates_a_new_expense_and_keeps_the_original(): void
    {
        $expense = $this->lunch();

        $this->post(route('expenses.store'), [
            'amount' => '32.00',
            'date' => '2026-11-05',
            'description' => 'Almoço',
            'category_id' => $expense->category_id,
            'tags' => ['almoço', 'trabalho'],
        ])->assertRedirect(route('expenses.index'));

        $this->assertDatabaseCount('expenses', 2);

        $original = $expense->fresh();
        $this->assertSame(2550, $original->amount);
        $this->assertSame('2026-10-20', $original->date->toDateString());
        $this->assertSame(['almoço', 'trabalho'], $original->tags()->orderBy('name')->pluck('name')->all());

        $this->assertDatabaseCount('tags', 2); // as tags foram reaproveitadas, não duplicadas
    }

    public function test_an_expense_from_another_user_cannot_be_repeated(): void
    {
        $this->lunch();
        $other = Expense::create(['user_id' => User::factory()->create()->id, 'date' => '2026-11-05', 'amount' => 999]);

        $this->get(route('expenses.repeat', $other))->assertNotFound();
    }

    public function test_the_expense_without_optional_data_can_be_repeated(): void
    {
        $this->get(route('expenses.create'));
        $owner = User::where('email', config('midas.owner.email'))->firstOrFail();
        $expense = $owner->expenses()->create(['amount' => 500, 'date' => '2026-11-01']);

        $this->get(route('expenses.repeat', $expense))
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefill.amount', '5.00')
                ->where('prefill.description', null)
                ->where('prefill.category_id', null)
                ->where('prefill.tags', [])
            );
    }
}
