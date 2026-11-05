<?php

namespace Tests\Feature\Expenses;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryDeleteTest extends TestCase
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

    private function names(): array
    {
        return $this->owner->categories()->orderBy('id')->pluck('name')->all();
    }

    public function test_a_category_without_expenses_can_be_deleted(): void
    {
        $leisure = $this->category('Lazer');

        $this->delete(route('categories.destroy', $leisure))->assertRedirect(route('categories.index'));

        $this->assertModelMissing($leisure);
        $this->assertNotContains('Lazer', $this->names());
        $this->assertCount(7, $this->names());
    }

    public function test_a_deleted_category_is_no_longer_offered_for_choosing(): void
    {
        $this->delete(route('categories.destroy', $this->category('Lazer')));

        $this->get(route('expenses.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('categories', 7)
                ->where('categories', fn ($categories) => ! collect($categories)->pluck('name')->contains('Lazer'))
            );
    }

    public function test_deleting_does_not_touch_the_other_categories_or_expenses(): void
    {
        $food = $this->category('Alimentação');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $food->id]);

        $this->delete(route('categories.destroy', $this->category('Lazer')));

        $this->assertSame($food->id, $expense->fresh()->category_id);
        $this->assertModelExists($food);
    }

    public function test_the_name_of_a_deleted_category_can_be_used_again(): void
    {
        $this->delete(route('categories.destroy', $this->category('Lazer')));

        $this->post(route('categories.store'), ['name' => 'Lazer'])->assertSessionHasNoErrors();

        $this->assertContains('Lazer', $this->names());
    }

    public function test_the_screen_says_how_many_expenses_and_favorites_use_each_category(): void
    {
        $food = $this->category('Alimentação');
        $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $food->id]);
        $this->owner->expenses()->create(['amount' => 700, 'date' => '2026-11-05', 'category_id' => $food->id]);
        $this->owner->favorites()->create(['amount' => 900, 'category_id' => $food->id]);

        $this->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('categories.0.name', 'Alimentação')
                ->where('categories.0.expenses_count', 2)
                ->where('categories.0.favorites_count', 1)
                ->where('categories.1.expenses_count', 0)
                ->where('categories.1.favorites_count', 0)
            );
    }

    public function test_a_category_used_by_an_expense_cannot_be_deleted(): void
    {
        $food = $this->category('Alimentação');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $food->id]);

        $this->from(route('categories.index'))
            ->delete(route('categories.destroy', $food))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasErrors('category');

        $this->assertModelExists($food);
        $this->assertSame($food->id, $expense->fresh()->category_id);
    }

    public function test_a_category_used_only_by_a_favorite_cannot_be_deleted(): void
    {
        $leisure = $this->category('Lazer');
        $favorite = $this->owner->favorites()->create(['name' => 'Cinema', 'amount' => 3000, 'category_id' => $leisure->id]);

        $this->delete(route('categories.destroy', $leisure))->assertSessionHasErrors('category');

        $this->assertModelExists($leisure);
        $this->assertSame($leisure->id, $favorite->fresh()->category_id);
    }

    public function test_a_category_that_lost_its_last_expense_can_be_deleted(): void
    {
        $leisure = $this->category('Lazer');
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);

        $this->delete(route('categories.destroy', $leisure))->assertSessionHasErrors('category');
        $this->assertModelExists($leisure);

        $this->delete(route('expenses.destroy', $expense));
        $this->delete(route('categories.destroy', $leisure))->assertRedirect(route('categories.index'));

        $this->assertModelMissing($leisure);
    }

    public function test_a_category_of_another_user_cannot_be_deleted(): void
    {
        $other = User::factory()->create()->categories()->create(['name' => 'Alheia']);

        $this->delete(route('categories.destroy', $other))->assertNotFound();

        $this->assertModelExists($other);
    }
}
