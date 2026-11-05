<?php

namespace Tests\Feature\Expenses;

use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FavoriteListTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get(route('expenses.create')); // cria o usuário padrão e as categorias
        $this->owner = User::where('email', config('midas.owner.email'))->firstOrFail();
    }

    private function favorite(array $attributes = [], array $tags = []): Favorite
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

    public function test_the_list_shows_the_favorites_with_their_data(): void
    {
        $this->favorite(tags: ['trabalho', 'almoço']);

        $this->get(route('favorites.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Favorites/Index')
                ->has('favorites', 1)
                ->where('favorites.0.name', 'Almoço')
                ->where('favorites.0.description', 'Almoço')
                ->where('favorites.0.amount', 2550)
                ->where('favorites.0.category', 'Alimentação')
                ->where('favorites.0.tags', ['almoço', 'trabalho'])
            );
    }

    public function test_the_list_comes_empty_when_there_are_no_favorites(): void
    {
        $this->get(route('favorites.index'))
            ->assertInertia(fn (Assert $page) => $page->component('Favorites/Index')->has('favorites', 0));
    }

    public function test_the_list_is_ordered_by_name_and_favorites_without_name_come_last(): void
    {
        $this->favorite(['name' => 'zebra']);
        $this->favorite(['name' => null, 'description' => null, 'amount' => 700]);
        $this->favorite(['name' => 'Água']);
        $this->favorite(['name' => 'banana']);

        $this->get(route('favorites.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('favorites', fn ($favorites) => collect($favorites)->pluck('name')->all() === ['Água', 'banana', 'zebra', null])
            );
    }

    public function test_a_favorite_without_description_still_comes_with_value_and_category(): void
    {
        $this->favorite(['name' => null, 'description' => null, 'amount' => 1200]);

        $this->get(route('favorites.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('favorites.0.name', null)
                ->where('favorites.0.amount', 1200)
                ->where('favorites.0.category', 'Alimentação')
            );
    }

    public function test_only_the_favorites_of_the_user_are_listed(): void
    {
        $this->favorite();
        $other = User::factory()->create();
        $other->favorites()->create(['name' => 'alheio', 'amount' => 1]);

        $this->get(route('favorites.index'))
            ->assertInertia(fn (Assert $page) => $page->has('favorites', 1)->where('favorites.0.name', 'Almoço'));
    }

    public function test_a_favorite_whose_source_expense_was_deleted_is_still_listed(): void
    {
        $expense = $this->owner->expenses()->create(['amount' => 2550, 'date' => '2026-11-05']);
        $this->favorite(['source_expense_id' => $expense->id]);

        $this->delete(route('expenses.destroy', $expense));

        $this->get(route('favorites.index'))->assertInertia(fn (Assert $page) => $page->has('favorites', 1));
    }

    public function test_using_a_favorite_opens_the_form_filled_with_its_data(): void
    {
        $favorite = $this->favorite(tags: ['trabalho', 'almoço']);

        $this->get(route('favorites.use', $favorite))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Expenses/Create')
                ->where('source', 'favorite')
                ->where('prefill.amount', '25.50')
                ->where('prefill.description', 'Almoço')
                ->where('prefill.category_id', $favorite->category_id)
                ->where('prefill.tags', ['almoço', 'trabalho'])
                ->missing('prefill.date')
                ->has('categories', 8)
                ->has('tags', 2)
            );
    }

    public function test_using_a_favorite_saves_nothing(): void
    {
        $favorite = $this->favorite(tags: ['trabalho']);
        $before = $favorite->fresh()->getAttributes();

        $this->get(route('favorites.use', $favorite))->assertOk();

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('favorites', 1);
        $this->assertSame($before, $favorite->fresh()->getAttributes()); // o favorito não foi tocado
        $this->assertSame(['trabalho'], $favorite->tags()->pluck('name')->all());
    }

    public function test_saving_an_expense_after_changing_the_fields_does_not_change_the_favorite(): void
    {
        $favorite = $this->favorite(tags: ['trabalho', 'almoço']);

        $this->get(route('favorites.use', $favorite));
        $this->post(route('expenses.store'), [
            'amount' => '32.00',
            'date' => '2026-11-05',
            'description' => 'Almoço com bebida',
            'tags' => ['bebida'],
        ])->assertRedirect(route('expenses.index'));

        $this->assertDatabaseCount('expenses', 1);

        $favorite->refresh();
        $this->assertSame(2550, $favorite->amount);
        $this->assertSame('Almoço', $favorite->description);
        $this->assertSame('Almoço', $favorite->name);
        $this->assertSame(['almoço', 'trabalho'], $favorite->tags()->orderBy('name')->pluck('name')->all());
    }

    public function test_a_favorite_without_optional_data_can_be_used(): void
    {
        $favorite = $this->owner->favorites()->create(['amount' => 500]);

        $this->get(route('favorites.use', $favorite))
            ->assertInertia(fn (Assert $page) => $page
                ->where('prefill.amount', '5.00')
                ->where('prefill.description', null)
                ->where('prefill.category_id', null)
                ->where('prefill.tags', [])
            );
    }

    public function test_a_favorite_of_another_user_cannot_be_used(): void
    {
        $other = User::factory()->create()->favorites()->create(['name' => 'alheio', 'amount' => 1]);

        $this->get(route('favorites.use', $other))->assertNotFound();
    }
}
