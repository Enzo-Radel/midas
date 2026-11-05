<?php

namespace Tests\Feature\Expenses;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryReplaceTest extends TestCase
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

    /**
     * Categoria "Lazer" usada por dois gastos e um favorito.
     *
     * @return array{0: Category, 1: list<Expense>, 2: Favorite}
     */
    private function leisureInUse(): array
    {
        $leisure = $this->category('Lazer');
        $expenses = [
            $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'description' => 'cinema', 'category_id' => $leisure->id]),
            $this->owner->expenses()->create(['amount' => 700, 'date' => '2026-11-04', 'description' => 'parque', 'category_id' => $leisure->id]),
        ];
        $favorite = $this->owner->favorites()->create(['name' => 'Cinema', 'amount' => 3000, 'category_id' => $leisure->id]);

        return [$leisure, $expenses, $favorite];
    }

    public function test_deleting_with_a_replacement_moves_expenses_and_favorites_and_removes_the_category(): void
    {
        [$leisure, $expenses, $favorite] = $this->leisureInUse();
        $food = $this->category('Alimentação');

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $food->id])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($leisure);
        foreach ($expenses as $expense) {
            $this->assertSame($food->id, $expense->fresh()->category_id);
        }
        $this->assertSame($food->id, $favorite->fresh()->category_id);
        $this->assertSame(2, $food->expenses()->count());
        $this->assertSame(1, $food->favorites()->count());
    }

    public function test_deleting_without_category_leaves_expenses_and_favorites_without_one(): void
    {
        [$leisure, $expenses, $favorite] = $this->leisureInUse();

        $this->delete(route('categories.destroy', $leisure), ['without_category' => true])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($leisure);
        foreach ($expenses as $expense) {
            $this->assertModelExists($expense);
            $this->assertNull($expense->fresh()->category_id);
        }
        $this->assertNull($favorite->fresh()->category_id);
    }

    public function test_the_other_data_of_expenses_and_favorites_is_untouched(): void
    {
        [$leisure, $expenses, $favorite] = $this->leisureInUse();
        $expenses[0]->tags()->attach($this->owner->tags()->create(['name' => 'trabalho'])->id);

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $this->category('Alimentação')->id]);

        $expense = $expenses[0]->fresh();
        $this->assertSame(500, $expense->amount);
        $this->assertSame('cinema', $expense->description);
        $this->assertSame('2026-11-05', $expense->date->toDateString());
        $this->assertSame(['trabalho'], $expense->tags()->pluck('name')->all());
        $this->assertSame('Cinema', $favorite->fresh()->name);
        $this->assertSame(3000, $favorite->fresh()->amount);
        $this->assertDatabaseCount('expenses', 2);
    }

    public function test_a_category_in_use_without_a_destination_is_not_deleted(): void
    {
        [$leisure, $expenses, $favorite] = $this->leisureInUse();

        $this->delete(route('categories.destroy', $leisure))->assertSessionHasErrors('category');
        $this->delete(route('categories.destroy', $leisure), ['without_category' => false])->assertSessionHasErrors('category');

        $this->assertModelExists($leisure);
        $this->assertSame($leisure->id, $expenses[0]->fresh()->category_id);
        $this->assertSame($leisure->id, $favorite->fresh()->category_id);
    }

    public function test_a_deactivated_category_in_use_can_also_be_deleted_with_a_replacement(): void
    {
        [$leisure, $expenses] = $this->leisureInUse();
        $this->post(route('categories.deactivate', $leisure));
        $food = $this->category('Alimentação');

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $food->id])->assertSessionHasNoErrors();

        $this->assertModelMissing($leisure);
        $this->assertSame($food->id, $expenses[0]->fresh()->category_id);
    }

    public function test_a_deactivated_category_in_use_can_also_be_deleted_without_category(): void
    {
        [$leisure, $expenses, $favorite] = $this->leisureInUse();
        $this->post(route('categories.deactivate', $leisure));

        $this->delete(route('categories.destroy', $leisure), ['without_category' => true])->assertSessionHasNoErrors();

        $this->assertModelMissing($leisure);
        $this->assertNull($expenses[0]->fresh()->category_id);
        $this->assertNull($favorite->fresh()->category_id);
    }

    public function test_when_both_a_replacement_and_without_category_are_sent_the_replacement_wins(): void
    {
        [$leisure, $expenses] = $this->leisureInUse();
        $food = $this->category('Alimentação');

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $food->id, 'without_category' => true])
            ->assertSessionHasNoErrors();

        $this->assertSame($food->id, $expenses[0]->fresh()->category_id);
    }

    public function test_the_replacement_must_be_active(): void
    {
        [$leisure, $expenses] = $this->leisureInUse();
        $food = $this->category('Alimentação');
        $food->update(['active' => false]);

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $food->id])->assertSessionHasErrors('replacement_id');

        $this->assertModelExists($leisure);
        $this->assertSame($leisure->id, $expenses[0]->fresh()->category_id);
    }

    public function test_the_replacement_cannot_be_the_deleted_category_itself(): void
    {
        [$leisure] = $this->leisureInUse();

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $leisure->id])->assertSessionHasErrors('replacement_id');

        $this->assertModelExists($leisure);
    }

    public function test_the_replacement_must_be_a_category_of_the_user(): void
    {
        [$leisure, $expenses] = $this->leisureInUse();
        $foreign = Category::create(['user_id' => User::factory()->create()->id, 'name' => 'Alheia']);

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $foreign->id])->assertSessionHasErrors('replacement_id');
        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => 99999])->assertSessionHasErrors('replacement_id');
        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => 'abc'])->assertSessionHasErrors('replacement_id');

        $this->assertModelExists($leisure);
        $this->assertSame($leisure->id, $expenses[0]->fresh()->category_id);
    }

    public function test_expenses_of_other_categories_and_other_users_are_not_touched(): void
    {
        [$leisure] = $this->leisureInUse();
        $health = $this->category('Saúde');
        $mine = $this->owner->expenses()->create(['amount' => 100, 'date' => '2026-11-05', 'category_id' => $health->id]);
        $otherUser = User::factory()->create();
        $theirCategory = $otherUser->categories()->create(['name' => 'Lazer']);
        $theirs = Expense::create(['user_id' => $otherUser->id, 'amount' => 100, 'date' => '2026-11-05', 'category_id' => $theirCategory->id]);

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $this->category('Alimentação')->id]);

        $this->assertSame($health->id, $mine->fresh()->category_id);
        $this->assertSame($theirCategory->id, $theirs->fresh()->category_id);
        $this->assertModelExists($theirCategory);
    }

    public function test_a_category_without_use_is_simply_deleted_even_if_a_destination_is_sent(): void
    {
        $leisure = $this->category('Lazer');

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $this->category('Alimentação')->id])
            ->assertRedirect(route('categories.index'));

        $this->assertModelMissing($leisure);
    }

    public function test_after_the_swap_the_replacement_shows_the_new_usage_on_the_screen(): void
    {
        [$leisure] = $this->leisureInUse();
        $food = $this->category('Alimentação');

        $this->delete(route('categories.destroy', $leisure), ['replacement_id' => $food->id]);

        $this->get(route('categories.index'))->assertInertia(fn ($page) => $page
            ->where('categories.0.name', 'Alimentação')
            ->where('categories.0.expenses_count', 2)
            ->where('categories.0.favorites_count', 1)
            ->has('categories', 7)
        );
    }

    public function test_a_category_of_another_user_cannot_be_deleted_with_a_replacement(): void
    {
        $other = User::factory()->create();
        $theirs = $other->categories()->create(['name' => 'Alheia']);

        $this->delete(route('categories.destroy', $theirs), ['without_category' => true])->assertNotFound();

        $this->assertModelExists($theirs);
    }
}
