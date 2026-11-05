<?php

namespace Tests\Feature\Expenses;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryActivationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get(route('expenses.create')); // cria o usuário padrão e as categorias
        $this->owner = User::where('email', config('midas.owner.email'))->firstOrFail();
    }

    private function category(string $name): Category
    {
        return $this->owner->categories()->where('name', $name)->firstOrFail();
    }

    private function inactive(string $name): Category
    {
        $category = $this->category($name);
        $category->update(['active' => false]);

        return $category;
    }

    public function test_every_category_starts_active(): void
    {
        $this->assertSame(0, $this->owner->categories()->where('active', false)->count());

        $this->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page->where('categories.0.active', true));
    }

    public function test_a_category_in_use_can_be_deactivated(): void
    {
        $food = $this->category('Alimentação');
        $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $food->id]);

        $this->post(route('categories.deactivate', $food))->assertRedirect(route('categories.index'));

        $this->assertFalse($food->fresh()->active);
        $this->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page->where('categories.0.active', false)->where('categories.0.expenses_count', 1));
    }

    public function test_deactivating_twice_is_harmless(): void
    {
        $food = $this->category('Alimentação');

        $this->post(route('categories.deactivate', $food));
        $this->post(route('categories.deactivate', $food))->assertRedirect(route('categories.index'));

        $this->assertFalse($food->fresh()->active);
    }

    public function test_a_deactivated_category_is_not_offered_for_choosing(): void
    {
        $this->inactive('Lazer');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05']);
        $notLeisure = fn ($categories) => ! collect($categories)->pluck('name')->contains('Lazer');

        $this->get(route('expenses.create'))
            ->assertInertia(fn (Assert $page) => $page->has('categories', 7)->where('categories', $notLeisure));
        $this->get(route('expenses.edit', $expense))
            ->assertInertia(fn (Assert $page) => $page->has('categories', 7)->where('categories', $notLeisure));
    }

    public function test_a_new_expense_cannot_use_a_deactivated_category(): void
    {
        $leisure = $this->inactive('Lazer');

        $this->post(route('expenses.store'), ['amount' => '10', 'date' => '2026-11-05', 'category_id' => $leisure->id])
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('expenses', 0);
    }

    public function test_old_expenses_and_favorites_keep_showing_the_deactivated_category(): void
    {
        $leisure = $this->category('Lazer');
        $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);
        $this->owner->favorites()->create(['name' => 'Cinema', 'amount' => 3000, 'category_id' => $leisure->id]);

        $this->post(route('categories.deactivate', $leisure));

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page->where('days.0.expenses.0.category', 'Lazer'));
        $this->get(route('favorites.index'))
            ->assertInertia(fn (Assert $page) => $page->where('favorites.0.category', 'Lazer'));
    }

    public function test_an_old_expense_keeps_its_deactivated_category_when_editing(): void
    {
        $leisure = $this->category('Lazer');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);
        $this->post(route('categories.deactivate', $leisure));

        $this->get(route('expenses.edit', $expense))
            ->assertInertia(fn (Assert $page) => $page
                ->where('expense.category_id', $leisure->id)
                ->has('categories', 8)
                ->where('categories', fn ($categories) => collect($categories)->firstWhere('id', $leisure->id)['active'] === false)
            );

        $this->put(route('expenses.update', $expense), ['amount' => '12', 'date' => '2026-11-05', 'category_id' => $leisure->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($leisure->id, $expense->fresh()->category_id);
        $this->assertSame(1200, $expense->fresh()->amount);
    }

    public function test_the_deactivated_category_can_be_replaced_but_not_chosen_again(): void
    {
        $leisure = $this->category('Lazer');
        $food = $this->category('Alimentação');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);
        $this->post(route('categories.deactivate', $leisure));

        $this->put(route('expenses.update', $expense), ['amount' => '5', 'date' => '2026-11-05', 'category_id' => $food->id])
            ->assertSessionHasNoErrors();
        $this->assertSame($food->id, $expense->fresh()->category_id);

        $this->put(route('expenses.update', $expense), ['amount' => '5', 'date' => '2026-11-05', 'category_id' => $leisure->id])
            ->assertSessionHasErrors('category_id');
        $this->assertSame($food->id, $expense->fresh()->category_id);
    }

    public function test_another_expense_cannot_borrow_the_deactivated_category_through_an_edit(): void
    {
        $leisure = $this->category('Lazer');
        $other = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05']);
        $this->post(route('categories.deactivate', $leisure));

        $this->put(route('expenses.update', $other), ['amount' => '5', 'date' => '2026-11-05', 'category_id' => $leisure->id])
            ->assertSessionHasErrors('category_id');
    }

    public function test_a_favorite_keeps_its_deactivated_category_when_editing(): void
    {
        $leisure = $this->category('Lazer');
        $food = $this->category('Alimentação');
        $favorite = $this->owner->favorites()->create(['name' => 'Cinema', 'amount' => 3000, 'category_id' => $leisure->id]);
        $this->post(route('categories.deactivate', $leisure));

        $this->get(route('favorites.edit', $favorite))
            ->assertInertia(fn (Assert $page) => $page
                ->where('favorite.category_id', $leisure->id)
                ->has('categories', 8)
            );

        $this->put(route('favorites.update', $favorite), ['amount' => '31', 'category_id' => $leisure->id])->assertSessionHasNoErrors();
        $this->assertSame($leisure->id, $favorite->fresh()->category_id);

        $this->put(route('favorites.update', $favorite), ['amount' => '31', 'category_id' => $food->id])->assertSessionHasNoErrors();
        $this->put(route('favorites.update', $favorite), ['amount' => '31', 'category_id' => $leisure->id])->assertSessionHasErrors('category_id');
    }

    public function test_repeating_an_expense_with_a_deactivated_category_opens_without_it_and_warns(): void
    {
        $leisure = $this->category('Lazer');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);
        $this->post(route('categories.deactivate', $leisure));

        $this->get(route('expenses.repeat', $expense))
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefill.category_id', null)
                ->where('prefill.inactive_category', 'Lazer')
                ->has('categories', 7)
            );

        $this->assertSame($leisure->id, $expense->fresh()->category_id); // o original não muda
    }

    public function test_using_a_favorite_with_a_deactivated_category_opens_without_it_and_warns(): void
    {
        $leisure = $this->category('Lazer');
        $favorite = $this->owner->favorites()->create(['name' => 'Cinema', 'amount' => 3000, 'category_id' => $leisure->id]);
        $this->post(route('categories.deactivate', $leisure));

        $this->get(route('favorites.use', $favorite))
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefill.category_id', null)
                ->where('prefill.inactive_category', 'Lazer')
            );
        $this->assertSame($leisure->id, $favorite->fresh()->category_id);
    }

    public function test_an_active_category_gives_no_warning_when_repeating(): void
    {
        $leisure = $this->category('Lazer');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);

        $this->get(route('expenses.repeat', $expense))
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefill.category_id', $leisure->id)
                ->where('prefill.inactive_category', null)
            );
    }

    public function test_a_category_can_be_reactivated_and_is_offered_again(): void
    {
        $leisure = $this->inactive('Lazer');

        $this->post(route('categories.reactivate', $leisure))->assertRedirect(route('categories.index'));

        $this->assertTrue($leisure->fresh()->active);
        $this->get(route('expenses.create'))->assertInertia(fn (Assert $page) => $page->has('categories', 8));
        $this->post(route('expenses.store'), ['amount' => '10', 'date' => '2026-11-05', 'category_id' => $leisure->id])
            ->assertSessionHasNoErrors();
    }

    public function test_a_deactivated_category_can_be_renamed_and_stays_deactivated(): void
    {
        $leisure = $this->inactive('Lazer');

        $this->put(route('categories.update', $leisure), ['name' => 'Diversão'])->assertSessionHasNoErrors();

        $this->assertSame('Diversão', $leisure->fresh()->name);
        $this->assertFalse($leisure->fresh()->active);
    }

    public function test_deactivating_does_not_change_the_expenses_that_use_the_category(): void
    {
        $leisure = $this->category('Lazer');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);
        $favorite = $this->owner->favorites()->create(['amount' => 3000, 'category_id' => $leisure->id]);

        $this->post(route('categories.deactivate', $leisure));
        $this->post(route('categories.reactivate', $leisure));

        $this->assertSame($leisure->id, $expense->fresh()->category_id);
        $this->assertSame($leisure->id, $favorite->fresh()->category_id);
    }

    public function test_a_deactivated_category_that_is_still_in_use_cannot_be_deleted(): void
    {
        $leisure = $this->category('Lazer');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);
        $this->post(route('categories.deactivate', $leisure));

        $this->delete(route('categories.destroy', $leisure))->assertSessionHasErrors('category');

        $this->assertModelExists($leisure);
        $this->assertSame($leisure->id, $expense->fresh()->category_id);
    }

    public function test_the_categories_of_another_user_cannot_be_deactivated_or_reactivated(): void
    {
        $other = User::factory()->create()->categories()->create(['name' => 'Alheia']);

        $this->post(route('categories.deactivate', $other))->assertNotFound();
        $this->post(route('categories.reactivate', $other))->assertNotFound();

        $this->assertTrue($other->fresh()->active);
    }
}
