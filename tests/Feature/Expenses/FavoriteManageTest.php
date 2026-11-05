<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FavoriteManageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get(route('expenses.create')); // cria o usuário padrão e as categorias
        $this->owner = User::where('email', config('midas.owner.email'))->firstOrFail();
    }

    private function favorite(array $attributes = [], array $tags = ['trabalho', 'almoço']): Favorite
    {
        $favorite = $this->owner->favorites()->create($attributes + [
            'name' => 'Almoço',
            'description' => 'Almoço',
            'amount' => 2550,
            'category_id' => $this->owner->categories()->where('name', 'Alimentação')->value('id'),
        ]);

        $favorite->tags()->attach(collect($tags)->map(fn ($name) => $this->owner->tags()->firstOrCreate(['name' => $name])->id)->all());

        return $favorite;
    }

    private function payload(array $extra = []): array
    {
        return $extra + ['name' => 'Almoço', 'amount' => '25.50', 'description' => 'Almoço', 'tags' => ['almoço', 'trabalho']];
    }

    /**
     * Gasto de origem com categoria e as mesmas tags do favorito, para provar que mexer no favorito não o toca.
     */
    private function sourceExpense(): Expense
    {
        $expense = $this->owner->expenses()->create([
            'amount' => 2550,
            'date' => '2026-11-05',
            'description' => 'Almoço',
            'category_id' => $this->owner->categories()->where('name', 'Alimentação')->value('id'),
        ]);
        $expense->tags()->attach($this->owner->tags()->whereIn('name', ['trabalho', 'almoço'])->pluck('id')->all());

        return $expense;
    }

    private function tagNames(Favorite $favorite): array
    {
        return $favorite->tags()->orderBy('name')->pluck('name')->all();
    }

    public function test_the_edit_screen_opens_with_the_data_of_the_favorite(): void
    {
        $favorite = $this->favorite();

        $this->get(route('favorites.edit', $favorite))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Favorites/Edit')
                ->where('favorite.id', $favorite->id)
                ->where('favorite.name', 'Almoço')
                ->where('favorite.amount', '25.50')
                ->where('favorite.description', 'Almoço')
                ->where('favorite.category_id', $favorite->category_id)
                ->where('favorite.tags', ['almoço', 'trabalho'])
                ->has('categories', 8)
                ->has('tags', 2)
            );
    }

    public function test_a_favorite_can_be_renamed(): void
    {
        $favorite = $this->favorite();

        $this->put(route('favorites.update', $favorite), $this->payload(['name' => 'Almoço no trabalho']))
            ->assertRedirect(route('favorites.index'));

        $this->assertSame('Almoço no trabalho', $favorite->fresh()->name);
        $this->assertSame('Almoço', $favorite->fresh()->description); // só o nome mudou
    }

    public function test_the_name_can_be_cleared(): void
    {
        $favorite = $this->favorite();

        $this->put(route('favorites.update', $favorite), $this->payload(['name' => '']))->assertRedirect(route('favorites.index'));

        $this->assertNull($favorite->fresh()->name);
    }

    public function test_the_data_of_the_favorite_can_be_edited(): void
    {
        $favorite = $this->favorite();
        $leisure = $this->owner->categories()->where('name', 'Lazer')->value('id');

        $this->put(route('favorites.update', $favorite), $this->payload([
            'amount' => '30.00',
            'description' => 'Almoço com bebida',
            'category_id' => $leisure,
            'tags' => ['bebida', 'Trabalho'],
        ]))->assertRedirect(route('favorites.index'));

        $favorite->refresh();
        $this->assertSame(3000, $favorite->amount);
        $this->assertSame('Almoço com bebida', $favorite->description);
        $this->assertSame($leisure, $favorite->category_id);
        $this->assertSame(['bebida', 'trabalho'], $this->tagNames($favorite));
        $this->assertSame(1, $this->owner->tags()->whereRaw('lower(name) = ?', ['trabalho'])->count()); // reaproveitou
    }

    public function test_category_and_tags_can_be_removed(): void
    {
        $favorite = $this->favorite();

        $this->put(route('favorites.update', $favorite), ['amount' => '25.50', 'category_id' => '']);

        $favorite->refresh();
        $this->assertNull($favorite->category_id);
        $this->assertSame([], $this->tagNames($favorite));
    }

    public function test_later_repetitions_use_the_new_data(): void
    {
        $favorite = $this->favorite();

        $this->put(route('favorites.update', $favorite), $this->payload(['amount' => '28.00', 'description' => 'Almoço novo', 'tags' => ['novo']]));

        $this->get(route('favorites.use', $favorite))
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefill.amount', '28.00')
                ->where('prefill.description', 'Almoço novo')
                ->where('prefill.tags', ['novo'])
            );
    }

    public static function invalidPayloads(): array
    {
        return [
            'sem valor' => [['name' => 'x'], 'amount'],
            'valor zero' => [['amount' => '0'], 'amount'],
            'valor negativo' => [['amount' => '-1'], 'amount'],
            'valor com muitas casas' => [['amount' => '1.999'], 'amount'],
            'nome muito longo' => [['amount' => '1', 'name' => str_repeat('a', 256)], 'name'],
            'descrição muito longa' => [['amount' => '1', 'description' => str_repeat('a', 256)], 'description'],
            'tag em branco' => [['amount' => '1', 'tags' => ['']], 'tags.0'],
            'categoria inexistente' => [['amount' => '1', 'category_id' => 99999], 'category_id'],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_invalid_data_is_rejected_without_changing_the_favorite(array $payload, string $field): void
    {
        $favorite = $this->favorite();

        $this->from(route('favorites.edit', $favorite))
            ->put(route('favorites.update', $favorite), $payload)
            ->assertRedirect(route('favorites.edit', $favorite))
            ->assertSessionHasErrors($field);

        $favorite->refresh();
        $this->assertSame(2550, $favorite->amount);
        $this->assertSame('Almoço', $favorite->name);
        $this->assertSame(['almoço', 'trabalho'], $this->tagNames($favorite));
    }

    public function test_a_category_from_another_user_is_rejected(): void
    {
        $favorite = $this->favorite();
        $foreign = \App\Models\Category::create(['user_id' => User::factory()->create()->id, 'name' => 'Alheia']);

        $this->put(route('favorites.update', $favorite), $this->payload(['category_id' => $foreign->id]))
            ->assertSessionHasErrors('category_id');
    }

    public function test_editing_the_favorite_does_not_change_the_expenses_or_the_link_to_the_source(): void
    {
        $favorite = $this->favorite();
        $expense = $this->sourceExpense();
        $favorite->update(['source_expense_id' => $expense->id]);
        $leisure = $this->owner->categories()->where('name', 'Lazer')->value('id');

        $this->put(route('favorites.update', $favorite), $this->payload([
            'amount' => '99.00',
            'description' => 'Jantar',
            'category_id' => $leisure,
            'tags' => ['jantar'],
        ]))->assertRedirect(route('favorites.index'));

        $this->assertSame(9900, $favorite->fresh()->amount); // o favorito mudou...

        $expense->refresh(); // ...e o gasto não
        $this->assertSame(2550, $expense->amount);
        $this->assertSame('Almoço', $expense->description);
        $this->assertSame($this->owner->categories()->where('name', 'Alimentação')->value('id'), $expense->category_id);
        $this->assertSame(['almoço', 'trabalho'], $expense->tags()->orderBy('name')->pluck('name')->all());
        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame($expense->id, $favorite->fresh()->source_expense_id);
    }

    public function test_a_favorite_can_be_removed(): void
    {
        $favorite = $this->favorite();

        $this->delete(route('favorites.destroy', $favorite))->assertRedirect(route('favorites.index'));

        $this->assertModelMissing($favorite);
        $this->get(route('favorites.index'))->assertInertia(fn (Assert $page) => $page->has('favorites', 0));
    }

    public function test_removing_a_favorite_does_not_touch_expenses_categories_or_tags(): void
    {
        $favorite = $this->favorite();
        $expense = $this->sourceExpense();
        $favorite->update(['source_expense_id' => $expense->id]);
        $tags = $this->owner->tags()->count();

        $this->delete(route('favorites.destroy', $favorite))->assertRedirect(route('favorites.index'));

        $this->assertModelMissing($favorite);
        $expense->refresh();
        $this->assertSame(2550, $expense->amount);
        $this->assertSame('Almoço', $expense->description);
        $this->assertNotNull($expense->category_id);
        $this->assertSame(['almoço', 'trabalho'], $expense->tags()->orderBy('name')->pluck('name')->all());
        $this->assertDatabaseCount('expenses', 1);
        $this->assertSame(8, $this->owner->categories()->count());
        $this->assertSame($tags, $this->owner->tags()->count());
    }

    public function test_after_removing_the_favorite_the_source_expense_is_no_longer_favorited(): void
    {
        $expense = $this->owner->expenses()->create(['amount' => 2550, 'date' => '2026-11-05']);
        $favorite = $this->favorite(['source_expense_id' => $expense->id]);

        $this->get(route('expenses.index'))->assertInertia(fn (Assert $page) => $page->where('days.0.expenses.0.favorited', true));

        $this->delete(route('favorites.destroy', $favorite));

        $this->get(route('expenses.index'))->assertInertia(fn (Assert $page) => $page->where('days.0.expenses.0.favorited', false));
        $this->post(route('expenses.favorite', $expense)); // e pode ser favoritado de novo
        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_a_favorite_of_another_user_cannot_be_opened_changed_or_removed(): void
    {
        $other = User::factory()->create()->favorites()->create(['name' => 'alheio', 'amount' => 100]);

        $this->get(route('favorites.edit', $other))->assertNotFound();
        $this->put(route('favorites.update', $other), ['amount' => '1'])->assertNotFound();
        $this->delete(route('favorites.destroy', $other))->assertNotFound();

        $this->assertSame(100, $other->fresh()->amount);
        $this->assertModelExists($other);
    }
}
