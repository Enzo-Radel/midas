<?php

namespace Tests\Feature\Expenses;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $this->get(route('expenses.create')); // a primeira visita cria o usuário padrão e as categorias

        return User::where('email', config('midas.owner.email'))->firstOrFail();
    }

    private function category(User $user, string $name): Category
    {
        return $user->categories()->where('name', $name)->firstOrFail();
    }

    public function test_the_default_categories_exist_the_first_time(): void
    {
        $this->get(route('expenses.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Create')
                ->has('categories', 8)
                ->where('categories', fn ($categories) => collect($categories)->pluck('name')->all() === [
                    'Alimentação', 'Transporte', 'Moradia', 'Saúde', 'Lazer', 'Educação', 'Compras', 'Outros',
                ])
            );
    }

    public function test_the_default_categories_are_not_recreated_on_later_visits(): void
    {
        $this->get(route('expenses.create'));
        $this->get(route('expenses.create'));
        $this->get(route('expenses.index'));

        $this->assertDatabaseCount('categories', 8);
    }

    public function test_an_expense_can_be_saved_with_one_category(): void
    {
        $category = $this->category($this->owner(), 'Alimentação');

        $this->post(route('expenses.store'), ['amount' => '25', 'date' => '2026-11-05', 'category_id' => $category->id]);

        $this->assertTrue(Expense::sole()->category->is($category));
    }

    public function test_the_category_is_optional(): void
    {
        $this->owner();

        $this->post(route('expenses.store'), ['amount' => '25', 'date' => '2026-11-05'])->assertSessionHasNoErrors();

        $this->assertNull(Expense::sole()->category_id);
    }

    public function test_a_category_that_does_not_exist_or_belongs_to_another_user_is_rejected(): void
    {
        $this->owner();
        $foreign = Category::create(['user_id' => User::factory()->create()->id, 'name' => 'Alheia']);

        $this->post(route('expenses.store'), ['amount' => '25', 'date' => '2026-11-05', 'category_id' => $foreign->id])
            ->assertSessionHasErrors('category_id');
        $this->post(route('expenses.store'), ['amount' => '25', 'date' => '2026-11-05', 'category_id' => 99999])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_the_category_appears_in_the_list(): void
    {
        $owner = $this->owner();
        $category = $this->category($owner, 'Transporte');
        $owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $category->id, 'description' => 'ônibus']);
        $owner->expenses()->create(['amount' => 300, 'date' => '2026-11-05', 'description' => 'sem categoria']);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('days.0.expenses.0.category', null)
                ->where('days.0.expenses.1.category', 'Transporte')
            );
    }

    public function test_the_edit_screen_offers_the_categories_and_the_current_one(): void
    {
        $owner = $this->owner();
        $category = $this->category($owner, 'Lazer');
        $expense = $owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $category->id]);

        $this->get(route('expenses.edit', $expense))
            ->assertInertia(fn (Assert $page) => $page
                ->has('categories', 8)
                ->where('expense.category_id', $category->id)
            );
    }

    public function test_the_category_can_be_changed_and_removed_when_editing(): void
    {
        $owner = $this->owner();
        $food = $this->category($owner, 'Alimentação');
        $leisure = $this->category($owner, 'Lazer');
        $expense = $owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $food->id]);

        $this->put(route('expenses.update', $expense), ['amount' => '5', 'date' => '2026-11-05', 'category_id' => $leisure->id]);
        $this->assertSame($leisure->id, $expense->fresh()->category_id);

        $this->put(route('expenses.update', $expense), ['amount' => '5', 'date' => '2026-11-05', 'category_id' => '']);
        $this->assertNull($expense->fresh()->category_id);
    }

    public function test_the_screens_offer_only_the_categories_of_the_user(): void
    {
        $owner = $this->owner();
        Category::create(['user_id' => User::factory()->create()->id, 'name' => 'Alheia']);
        $expense = $owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05']);

        $names = fn ($categories) => ! collect($categories)->pluck('name')->contains('Alheia');

        $this->get(route('expenses.create'))->assertInertia(fn (Assert $page) => $page->has('categories', 8)->where('categories', $names));
        $this->get(route('expenses.edit', $expense))->assertInertia(fn (Assert $page) => $page->has('categories', 8)->where('categories', $names));
    }

    public function test_editing_with_a_category_from_another_user_is_rejected(): void
    {
        $owner = $this->owner();
        $expense = $owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05']);
        $foreign = Category::create(['user_id' => User::factory()->create()->id, 'name' => 'Alheia']);

        $this->put(route('expenses.update', $expense), ['amount' => '5', 'date' => '2026-11-05', 'category_id' => $foreign->id])
            ->assertSessionHasErrors('category_id');

        $this->assertNull($expense->fresh()->category_id);
    }

    public function test_a_category_id_that_is_not_a_number_is_rejected_instead_of_failing(): void
    {
        $this->owner();

        $this->post(route('expenses.store'), ['amount' => '5', 'date' => '2026-11-05', 'category_id' => [1]])
            ->assertSessionHasErrors('category_id');
        $this->post(route('expenses.store'), ['amount' => '5', 'date' => '2026-11-05', 'category_id' => 'abc'])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('expenses', 0);
    }
}
