<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FavoriteExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get(route('expenses.create')); // cria o usuário padrão e as categorias
        $this->owner = User::where('email', config('midas.owner.email'))->firstOrFail();
    }

    private function lunch(array $attributes = []): Expense
    {
        $expense = $this->owner->expenses()->create($attributes + [
            'amount' => 2550,
            'date' => '2026-11-05',
            'description' => 'Almoço',
            'category_id' => $this->owner->categories()->where('name', 'Alimentação')->value('id'),
        ]);
        $expense->tags()->attach([
            $this->owner->tags()->firstOrCreate(['name' => 'trabalho'])->id,
            $this->owner->tags()->firstOrCreate(['name' => 'almoço'])->id,
        ]);

        return $expense;
    }

    private function payload(array $extra = []): array
    {
        // Os valores passados em $extra têm prioridade sobre os padrões.
        return $extra + ['amount' => '25.50', 'date' => '2026-11-05', 'description' => 'Almoço', 'tags' => ['almoço', 'trabalho']];
    }

    public function test_saving_a_new_expense_with_favorite_creates_the_favorite_with_a_copy_of_the_data(): void
    {
        $categoryId = $this->owner->categories()->where('name', 'Alimentação')->value('id');

        $this->post(route('expenses.store'), $this->payload(['category_id' => $categoryId, 'favorite' => true]))
            ->assertRedirect(route('expenses.index'));

        $favorite = Favorite::sole();
        $this->assertSame(2550, $favorite->amount);
        $this->assertSame('Almoço', $favorite->description);
        $this->assertSame('Almoço', $favorite->name);
        $this->assertSame($categoryId, $favorite->category_id);
        $this->assertSame(['almoço', 'trabalho'], $favorite->tags()->orderBy('name')->pluck('name')->all());
        $this->assertSame(Expense::sole()->id, $favorite->source_expense_id);
    }

    public function test_saving_without_favorite_creates_no_favorite(): void
    {
        $this->post(route('expenses.store'), $this->payload());
        $this->post(route('expenses.store'), $this->payload(['favorite' => false]));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_an_expense_already_saved_can_be_favorited_from_the_list(): void
    {
        $expense = $this->lunch();

        $this->post(route('expenses.favorite', $expense))->assertRedirect();

        $favorite = Favorite::sole();
        $this->assertSame(2550, $favorite->amount);
        $this->assertSame($expense->id, $favorite->source_expense_id);
    }

    public function test_favoriting_the_same_expense_twice_does_not_duplicate_the_favorite(): void
    {
        $expense = $this->lunch();

        $this->post(route('expenses.favorite', $expense));
        $this->post(route('expenses.favorite', $expense));
        $this->put(route('expenses.update', $expense), $this->payload(['favorite' => true]));

        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_the_list_shows_which_expenses_are_favorited(): void
    {
        $favorited = $this->lunch(['description' => 'favorito']);
        $this->lunch(['description' => 'comum']);
        $this->post(route('expenses.favorite', $favorited));

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('days.0.expenses', fn ($expenses) => collect($expenses)->pluck('favorited', 'description')->all() == ['favorito' => true, 'comum' => false])
            );
    }

    public function test_the_edit_screen_says_whether_the_expense_is_favorited(): void
    {
        $expense = $this->lunch();

        $this->get(route('expenses.edit', $expense))->assertInertia(fn (Assert $page) => $page->where('expense.favorited', false));

        $this->post(route('expenses.favorite', $expense));

        $this->get(route('expenses.edit', $expense))->assertInertia(fn (Assert $page) => $page->where('expense.favorited', true));
    }

    public function test_an_expense_can_be_favorited_when_editing(): void
    {
        $expense = $this->lunch();

        $this->put(route('expenses.update', $expense), $this->payload(['amount' => '30.00', 'favorite' => true]));

        $favorite = Favorite::sole();
        $this->assertSame(3000, $favorite->amount); // copia o gasto já com a edição
        $this->assertSame($expense->id, $favorite->source_expense_id);
    }

    public function test_the_favorite_can_be_removed_from_the_expense(): void
    {
        $expense = $this->lunch();
        $this->post(route('expenses.favorite', $expense));

        $this->delete(route('expenses.unfavorite', $expense))->assertRedirect();

        $this->assertDatabaseCount('favorites', 0);
        $this->assertModelExists($expense);
    }

    public function test_unchecking_favorite_when_editing_removes_the_favorite(): void
    {
        $expense = $this->lunch();
        $this->post(route('expenses.favorite', $expense));

        $this->put(route('expenses.update', $expense), $this->payload(['favorite' => false]));

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_editing_without_the_favorite_field_leaves_the_favorite_as_it_is(): void
    {
        $expense = $this->lunch();
        $this->post(route('expenses.favorite', $expense));

        $this->put(route('expenses.update', $expense), $this->payload(['amount' => '31.00']))
            ->assertRedirect(route('expenses.index'));

        $this->assertSame(3100, $expense->fresh()->amount);
        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_editing_the_source_expense_does_not_change_the_favorite(): void
    {
        $expense = $this->lunch();
        $this->post(route('expenses.favorite', $expense));

        $this->put(route('expenses.update', $expense), $this->payload(['amount' => '99.00', 'description' => 'Jantar', 'tags' => ['jantar']]))
            ->assertRedirect(route('expenses.index'));

        $expense->refresh();
        $this->assertSame(9900, $expense->amount);
        $this->assertSame('Jantar', $expense->description);
        $this->assertSame(['jantar'], $expense->tags()->pluck('name')->all());

        $favorite = Favorite::sole();
        $this->assertSame(2550, $favorite->amount);
        $this->assertSame('Almoço', $favorite->description);
        $this->assertSame(['almoço', 'trabalho'], $favorite->tags()->orderBy('name')->pluck('name')->all());
    }

    public function test_deleting_the_source_expense_keeps_the_favorite(): void
    {
        $expense = $this->lunch();
        $this->post(route('expenses.favorite', $expense));

        $this->delete(route('expenses.destroy', $expense));

        $favorite = Favorite::sole();
        $this->assertSame(2550, $favorite->amount);
        $this->assertNull($favorite->source_expense_id);
    }

    public function test_an_expense_created_from_a_repetition_can_also_be_favorited(): void
    {
        $original = $this->lunch();
        $this->post(route('expenses.favorite', $original));

        // Repetir abre o cadastro preenchido; salvar com "favoritar" marcado favorita o gasto novo.
        $this->get(route('expenses.repeat', $original))->assertOk();
        $this->post(route('expenses.store'), $this->payload(['amount' => '32.00', 'favorite' => true]))
            ->assertRedirect(route('expenses.index'));

        $this->assertDatabaseCount('favorites', 2);
        $new = Expense::where('amount', 3200)->sole();
        $this->assertSame($new->id, Favorite::where('amount', 3200)->sole()->source_expense_id);
        $this->assertSame($original->id, Favorite::where('amount', 2550)->sole()->source_expense_id);
    }

    public function test_the_favorite_without_description_keeps_the_value_and_the_category(): void
    {
        $categoryId = $this->owner->categories()->where('name', 'Transporte')->value('id');

        $this->post(route('expenses.store'), ['amount' => '12.00', 'date' => '2026-11-05', 'category_id' => $categoryId, 'favorite' => true]);

        $favorite = Favorite::sole();
        $this->assertNull($favorite->name);
        $this->assertNull($favorite->description);
        $this->assertSame(1200, $favorite->amount);
        $this->assertSame($categoryId, $favorite->category_id);
    }

    public function test_the_favorite_tags_are_independent_of_the_expense_tags(): void
    {
        $expense = $this->lunch();
        $this->post(route('expenses.favorite', $expense));

        $this->put(route('expenses.update', $expense), $this->payload(['tags' => []]))
            ->assertRedirect(route('expenses.index'));

        $this->assertSame(0, $expense->tags()->count());
        $this->assertSame(2, Favorite::sole()->tags()->count());
    }

    public function test_an_invalid_expense_does_not_create_the_favorite(): void
    {
        $this->post(route('expenses.store'), ['amount' => '0', 'date' => '2026-11-05', 'favorite' => true])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_the_expense_and_the_favorite_of_another_user_cannot_be_touched(): void
    {
        $other = Expense::create(['user_id' => User::factory()->create()->id, 'date' => '2026-11-05', 'amount' => 999]);

        $this->post(route('expenses.favorite', $other))->assertNotFound();
        $this->delete(route('expenses.unfavorite', $other))->assertNotFound();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_the_favorite_field_must_be_a_boolean(): void
    {
        $this->post(route('expenses.store'), $this->payload(['favorite' => 'talvez']))->assertSessionHasErrors('favorite');
    }

    public function test_the_database_does_not_accept_two_favorites_for_the_same_expense(): void
    {
        $expense = $this->lunch();
        $data = ['user_id' => $this->owner->id, 'source_expense_id' => $expense->id, 'amount' => 100];

        Favorite::create($data);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);
        Favorite::create($data);
    }

    public function test_favorites_of_deleted_expenses_do_not_conflict_with_each_other(): void
    {
        $first = $this->lunch();
        $second = $this->lunch();
        $this->post(route('expenses.favorite', $first));
        $this->post(route('expenses.favorite', $second));

        $this->delete(route('expenses.destroy', $first));
        $this->delete(route('expenses.destroy', $second));

        $this->assertSame(2, Favorite::whereNull('source_expense_id')->count());
    }
}
