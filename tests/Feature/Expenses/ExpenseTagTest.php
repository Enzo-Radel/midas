<?php

namespace Tests\Feature\Expenses;

use App\Models\Expense;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ExpenseTagTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $this->get(route('expenses.create'));

        return User::where('email', config('midas.owner.email'))->firstOrFail();
    }

    private function store(array $tags, array $extra = [])
    {
        return $this->post(route('expenses.store'), ['amount' => '10', 'date' => '2026-11-05', 'tags' => $tags] + $extra);
    }

    private function tagNames(Expense $expense): array
    {
        return $expense->tags()->orderBy('name')->pluck('name')->all();
    }

    public function test_there_are_no_tags_the_first_time(): void
    {
        $this->get(route('expenses.create'))
            ->assertInertia(fn (Assert $page) => $page->component('Expenses/Create')->has('tags', 0));
    }

    public function test_an_expense_can_have_several_tags_or_none(): void
    {
        $this->owner();

        $this->store(['trabalho', 'almoço'])->assertSessionHasNoErrors();
        $this->post(route('expenses.store'), ['amount' => '5', 'date' => '2026-11-05'])->assertSessionHasNoErrors();

        $this->assertSame(['almoço', 'trabalho'], $this->tagNames(Expense::where('amount', 1000)->sole()));
        $this->assertSame([], $this->tagNames(Expense::where('amount', 500)->sole()));
    }

    public function test_a_new_tag_name_creates_the_tag_and_assigns_it(): void
    {
        $owner = $this->owner();

        $this->store(['trabalho']);

        $this->assertSame(['trabalho'], $owner->tags()->pluck('name')->all());
        $this->assertSame(['trabalho'], $this->tagNames(Expense::sole()));
    }

    public function test_an_existing_tag_is_reused_ignoring_case_and_spaces(): void
    {
        $owner = $this->owner();
        $tag = $owner->tags()->create(['name' => 'Trabalho']);

        $this->store(['  trabalho ', 'TRABALHO']);

        $this->assertDatabaseCount('tags', 1);
        $this->assertSame([$tag->id], Expense::sole()->tags()->pluck('tags.id')->all());
    }

    public function test_the_existing_tags_are_offered_on_the_screens(): void
    {
        $owner = $this->owner();
        $owner->tags()->create(['name' => 'trabalho']);
        $owner->tags()->create(['name' => 'almoço']);
        Tag::create(['user_id' => User::factory()->create()->id, 'name' => 'alheia']);
        $expense = $owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05']);

        $this->get(route('expenses.create'))
            ->assertInertia(fn (Assert $page) => $page->where('tags', ['almoço', 'trabalho']));
        $this->get(route('expenses.edit', $expense))
            ->assertInertia(fn (Assert $page) => $page->where('tags', ['almoço', 'trabalho']));
    }

    public function test_the_tags_appear_in_the_list(): void
    {
        $this->owner();
        $this->store(['trabalho', 'almoço']);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page->where('days.0.expenses.0.tags', ['almoço', 'trabalho']));
    }

    public function test_the_edit_screen_has_the_current_tags(): void
    {
        $this->owner();
        $this->store(['trabalho', 'almoço']);

        $this->get(route('expenses.edit', Expense::sole()))
            ->assertInertia(fn (Assert $page) => $page->where('expense.tags', ['almoço', 'trabalho']));
    }

    public function test_tags_can_be_added_and_removed_when_editing(): void
    {
        $this->owner();
        $this->store(['trabalho', 'almoço']);
        $expense = Expense::sole();

        $this->put(route('expenses.update', $expense), ['amount' => '10', 'date' => '2026-11-05', 'tags' => ['almoço', 'mercado']]);
        $this->assertSame(['almoço', 'mercado'], $this->tagNames($expense));

        $this->put(route('expenses.update', $expense), ['amount' => '10', 'date' => '2026-11-05']);
        $this->assertSame([], $this->tagNames($expense));
    }

    public function test_removing_a_tag_from_an_expense_keeps_the_tag_for_other_expenses(): void
    {
        $owner = $this->owner();
        $this->store(['trabalho']);
        $this->store(['trabalho']);
        $first = Expense::orderBy('id')->first();

        $this->put(route('expenses.update', $first), ['amount' => '10', 'date' => '2026-11-05']);

        $this->assertSame(['trabalho'], $this->tagNames(Expense::orderBy('id')->get()->last()));
        $this->assertSame(1, $owner->tags()->count());
    }

    public function test_tags_of_different_users_are_independent(): void
    {
        $this->owner();
        $other = User::factory()->create();
        $other->tags()->create(['name' => 'trabalho']);

        $this->store(['trabalho']);

        $this->assertDatabaseCount('tags', 2);
        $this->assertSame(1, Tag::where('user_id', '!=', $other->id)->count());
    }

    public function test_invalid_tags_are_rejected(): void
    {
        $this->owner();

        $this->store([''])->assertSessionHasErrors('tags.0');
        $this->store([str_repeat('a', 51)])->assertSessionHasErrors('tags.0');
        $this->store([['x']])->assertSessionHasErrors('tags.0');
        $this->store(array_map(fn ($n) => "t$n", range(1, 21)))->assertSessionHasErrors('tags');
        $this->post(route('expenses.store'), ['amount' => '10', 'date' => '2026-11-05', 'tags' => 'abc'])->assertSessionHasErrors('tags');

        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('tags', 0);
    }

    public function test_a_tag_named_zero_is_kept(): void
    {
        $this->owner();

        $this->store(['0'])->assertSessionHasNoErrors();

        $this->assertSame(['0'], $this->tagNames(Expense::sole()));
    }

    public function test_tags_with_accents_are_matched_ignoring_case(): void
    {
        $owner = $this->owner();
        $owner->tags()->create(['name' => 'Ação']);

        $this->store(['ação', 'AÇÃO']);

        $this->assertDatabaseCount('tags', 1);
        $this->assertSame(['Ação'], $this->tagNames(Expense::sole()));
    }

    public function test_the_expense_and_its_tags_are_saved_together(): void
    {
        $this->owner();
        // Se gravar as tags falhar, o gasto também não pode ficar salvo.
        Tag::creating(fn () => throw new \RuntimeException('falha ao criar a tag'));

        $this->withoutExceptionHandling();

        try {
            $this->store(['nova']);
            $this->fail('A exceção deveria ter sido lançada.');
        } catch (\RuntimeException) {
            // esperado
        } finally {
            Tag::flushEventListeners(); // o listener é estático e não pode vazar para outros testes
        }

        $this->assertDatabaseCount('expenses', 0);
    }
}
