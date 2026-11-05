<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\Favorite;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TagManageTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->get(route('expenses.create')); // cria o usuário padrão e as categorias
        $this->owner = User::where('email', config('midas.owner.email'))->firstOrFail();
    }

    private function tag(string $name): Tag
    {
        return $this->owner->tags()->firstOrCreate(['name' => $name]);
    }

    private function expenseWith(array $tags, string $description = 'gasto'): Expense
    {
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'description' => $description]);
        $expense->tags()->attach(collect($tags)->map(fn ($name) => $this->tag($name)->id)->all());

        return $expense;
    }

    private function favoriteWith(array $tags): Favorite
    {
        $favorite = $this->owner->favorites()->create(['name' => 'fav', 'amount' => 900]);
        $favorite->tags()->attach(collect($tags)->map(fn ($name) => $this->tag($name)->id)->all());

        return $favorite;
    }

    public function test_the_tags_screen_comes_empty_when_there_are_no_tags(): void
    {
        $this->get(route('tags.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Tags/Index')->has('tags', 0));
    }

    public function test_the_tags_screen_lists_the_tags_in_alphabetical_order_with_their_usage(): void
    {
        $this->expenseWith(['zebra', 'Água']);
        $this->expenseWith(['zebra']);
        $this->favoriteWith(['zebra']);
        $this->tag('banana');

        $this->get(route('tags.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('tags', fn ($tags) => collect($tags)->pluck('name')->all() === ['Água', 'banana', 'zebra'])
                ->where('tags.2.expenses_count', 2)
                ->where('tags.2.favorites_count', 1)
                ->where('tags.0.expenses_count', 1)
                ->where('tags.1.expenses_count', 0)
                ->where('tags.1.favorites_count', 0)
            );
    }

    public function test_only_the_tags_of_the_user_are_listed(): void
    {
        $this->tag('minha');
        User::factory()->create()->tags()->create(['name' => 'alheia']);

        $this->get(route('tags.index'))
            ->assertInertia(fn (Assert $page) => $page->has('tags', 1)->where('tags.0.name', 'minha'));
    }

    public function test_a_tag_can_be_renamed(): void
    {
        $tag = $this->tag('trabalho');

        $this->put(route('tags.update', $tag), ['name' => 'escritório'])->assertRedirect(route('tags.index'));

        $this->assertSame('escritório', $tag->fresh()->name);
    }

    public function test_the_new_name_appears_in_expenses_and_favorites(): void
    {
        $tag = $this->tag('trabalho');
        $expense = $this->expenseWith(['trabalho']);
        $this->favoriteWith(['trabalho']);

        $this->put(route('tags.update', $tag), ['name' => 'escritório']);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page->where('days.0.expenses.0.tags', ['escritório']));
        $this->get(route('favorites.index'))
            ->assertInertia(fn (Assert $page) => $page->where('favorites.0.tags', ['escritório']));
        $this->get(route('expenses.edit', $expense))
            ->assertInertia(fn (Assert $page) => $page->where('expense.tags', ['escritório'])->where('tags', ['escritório']));
    }

    public function test_renaming_keeps_the_expenses_and_favorites_that_use_the_tag(): void
    {
        $tag = $this->tag('trabalho');
        $this->expenseWith(['trabalho']);
        $this->favoriteWith(['trabalho']);

        $this->put(route('tags.update', $tag), ['name' => 'escritório']);

        $this->assertSame(1, $tag->fresh()->expenses()->count());
        $this->assertSame(1, $tag->fresh()->favorites()->count());
    }

    public function test_a_tag_can_keep_its_name_or_change_only_the_case(): void
    {
        $tag = $this->tag('trabalho');

        $this->put(route('tags.update', $tag), ['name' => 'trabalho'])->assertSessionHasNoErrors();
        $this->put(route('tags.update', $tag), ['name' => 'Trabalho'])->assertSessionHasNoErrors();

        $this->assertSame('Trabalho', $tag->fresh()->name);
    }

    public function test_renaming_to_the_name_of_another_tag_is_rejected_ignoring_case_and_spaces(): void
    {
        $tag = $this->tag('trabalho');
        $this->tag('Ação');

        $this->put(route('tags.update', $tag), ['name' => 'TRABALHO '])->assertSessionHasNoErrors(); // o próprio nome
        $this->put(route('tags.update', $tag), ['name' => 'AÇÃO'])
            ->assertSessionHasErrors(['name' => 'Já existe uma tag com esse nome.']);

        $this->assertSame('TRABALHO', $tag->fresh()->name);
    }

    public static function invalidNames(): array
    {
        return [
            'em branco' => [''],
            'só espaços' => ['   '],
            'longo demais' => [str_repeat('a', 51)],
            'não é texto' => [['x']],
        ];
    }

    #[DataProvider('invalidNames')]
    public function test_an_invalid_name_is_rejected(string|array $name): void
    {
        $tag = $this->tag('trabalho');

        $this->put(route('tags.update', $tag), ['name' => $name])->assertSessionHasErrors('name');

        $this->assertSame('trabalho', $tag->fresh()->name);
    }

    public function test_a_name_of_exactly_fifty_characters_is_accepted(): void
    {
        $tag = $this->tag('trabalho');

        $this->put(route('tags.update', $tag), ['name' => str_repeat('a', 50)])->assertSessionHasNoErrors();

        $this->assertSame(str_repeat('a', 50), $tag->fresh()->name);
    }

    public function test_another_user_may_have_a_tag_with_the_same_name(): void
    {
        $tag = $this->tag('trabalho');
        User::factory()->create()->tags()->create(['name' => 'escritório']);

        $this->put(route('tags.update', $tag), ['name' => 'escritório'])->assertSessionHasNoErrors();
    }

    public function test_deleting_a_tag_removes_it_from_the_expenses_and_favorites_that_had_it(): void
    {
        $work = $this->tag('trabalho');
        $expense = $this->expenseWith(['trabalho', 'almoço']);
        $favorite = $this->favoriteWith(['trabalho', 'almoço']);

        $this->delete(route('tags.destroy', $work))->assertRedirect(route('tags.index'));

        $this->assertModelMissing($work);
        $this->assertSame(['almoço'], $expense->tags()->pluck('name')->all());
        $this->assertSame(['almoço'], $favorite->tags()->pluck('name')->all());
        $this->assertDatabaseMissing('expense_tag', ['tag_id' => $work->id]);
        $this->assertDatabaseMissing('favorite_tag', ['tag_id' => $work->id]);
    }

    public function test_deleting_a_tag_does_not_delete_or_change_expenses_and_favorites(): void
    {
        $work = $this->tag('trabalho');
        $expense = $this->expenseWith(['trabalho'], 'almoço no trabalho');
        $favorite = $this->favoriteWith(['trabalho']);

        $this->delete(route('tags.destroy', $work));

        $this->assertModelExists($expense);
        $this->assertModelExists($favorite);
        $this->assertSame(500, $expense->fresh()->amount);
        $this->assertSame('almoço no trabalho', $expense->fresh()->description);
        $this->assertSame(900, $favorite->fresh()->amount);
        $this->assertSame('fav', $favorite->fresh()->name);
    }

    public function test_deleting_a_tag_does_not_touch_the_other_tags(): void
    {
        $work = $this->tag('trabalho');
        $lunch = $this->tag('almoço');
        $other = User::factory()->create()->tags()->create(['name' => 'trabalho']);

        $this->delete(route('tags.destroy', $work));

        $this->assertModelExists($lunch);
        $this->assertModelExists($other);
    }

    public function test_a_deleted_tag_disappears_from_the_lists_and_suggestions(): void
    {
        $work = $this->tag('trabalho');
        $expense = $this->expenseWith(['trabalho']);

        $this->delete(route('tags.destroy', $work));

        $this->get(route('expenses.index'))->assertInertia(fn (Assert $page) => $page->where('days.0.expenses.0.tags', []));
        $this->get(route('expenses.edit', $expense))->assertInertia(fn (Assert $page) => $page->where('tags', [])->where('expense.tags', []));
        $this->get(route('tags.index'))->assertInertia(fn (Assert $page) => $page->has('tags', 0));
    }

    public function test_the_name_of_a_deleted_tag_can_be_used_again(): void
    {
        $work = $this->tag('trabalho');
        $this->delete(route('tags.destroy', $work));

        $this->post(route('expenses.store'), ['amount' => '10', 'date' => '2026-11-05', 'tags' => ['trabalho']])->assertSessionHasNoErrors();

        $this->assertSame(1, $this->owner->tags()->count());
        $this->assertNotSame($work->id, $this->owner->tags()->first()->id);
    }

    public function test_an_unused_tag_can_be_deleted(): void
    {
        $tag = $this->tag('solta');

        $this->delete(route('tags.destroy', $tag))->assertRedirect(route('tags.index'));

        $this->assertModelMissing($tag);
    }

    public function test_a_tag_of_another_user_cannot_be_renamed_or_deleted(): void
    {
        $other = User::factory()->create()->tags()->create(['name' => 'alheia']);

        $this->put(route('tags.update', $other), ['name' => 'minha'])->assertNotFound();
        $this->delete(route('tags.destroy', $other))->assertNotFound();

        $this->assertSame('alheia', $other->fresh()->name);
    }
}
