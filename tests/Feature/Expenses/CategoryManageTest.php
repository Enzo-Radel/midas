<?php

namespace Tests\Feature\Expenses;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CategoryManageTest extends TestCase
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

    public function test_the_categories_screen_lists_the_categories(): void
    {
        $this->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Categories/Index')
                ->has('categories', 8)
                ->where('categories', fn ($categories) => collect($categories)->pluck('name')->all() === $this->names())
            );
    }

    public function test_a_category_can_be_created(): void
    {
        $this->post(route('categories.store'), ['name' => 'Pets'])->assertRedirect(route('categories.index'));

        $this->assertSame('Pets', $this->owner->categories()->orderByDesc('id')->value('name'));
        $this->assertCount(9, $this->names());
    }

    public function test_a_new_category_is_offered_when_registering_and_editing_expenses(): void
    {
        $this->post(route('categories.store'), ['name' => 'Pets']);
        $expense = $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05']);

        $this->get(route('expenses.create'))
            ->assertInertia(fn (Assert $page) => $page->has('categories', 9)->where('categories.8.name', 'Pets'));
        $this->get(route('expenses.edit', $expense))
            ->assertInertia(fn (Assert $page) => $page->has('categories', 9));

        $pets = $this->category('Pets');
        $this->post(route('expenses.store'), ['amount' => '10', 'date' => '2026-11-05', 'category_id' => $pets->id])->assertSessionHasNoErrors();
        $this->assertSame(1, $pets->expenses()->count());
    }

    public function test_a_name_of_exactly_fifty_characters_is_accepted(): void
    {
        $leisure = $this->category('Lazer');
        $name = str_repeat('a', 50);

        $this->post(route('categories.store'), ['name' => $name])->assertSessionHasNoErrors();
        $this->put(route('categories.update', $leisure), ['name' => str_repeat('b', 50)])->assertSessionHasNoErrors();

        $this->assertContains($name, $this->names());
        $this->assertSame(str_repeat('b', 50), $leisure->fresh()->name);
    }

    public function test_the_name_is_trimmed(): void
    {
        $this->post(route('categories.store'), ['name' => '  Pets  ']);

        $this->assertContains('Pets', $this->names());
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
        $this->from(route('categories.index'))
            ->post(route('categories.store'), ['name' => $name])
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasErrors('name');

        $this->assertCount(8, $this->names());
    }

    public function test_a_name_equal_to_another_is_rejected_ignoring_case_and_surrounding_spaces(): void
    {
        $this->post(route('categories.store'), ['name' => 'alimentação'])->assertSessionHasErrors('name');
        $this->post(route('categories.store'), ['name' => 'ALIMENTAÇÃO '])->assertSessionHasErrors('name');
        $this->post(route('categories.store'), ['name' => 'Educação'])->assertSessionHasErrors('name');

        $this->post(route('categories.store'), ['name' => 'Ação']);
        $this->post(route('categories.store'), ['name' => 'AÇÃO'])->assertSessionHasErrors('name');

        $this->assertCount(9, $this->names());
    }

    public function test_the_duplicate_name_comes_with_the_reason(): void
    {
        $this->post(route('categories.store'), ['name' => 'Lazer'])
            ->assertSessionHasErrors(['name' => 'Já existe uma categoria com esse nome.']);
    }

    public function test_a_category_can_be_renamed(): void
    {
        $leisure = $this->category('Lazer');

        $this->put(route('categories.update', $leisure), ['name' => 'Diversão'])->assertRedirect(route('categories.index'));

        $this->assertSame('Diversão', $leisure->fresh()->name);
        $this->assertCount(8, $this->names());
    }

    public function test_the_new_name_appears_in_expenses_and_favorites(): void
    {
        $leisure = $this->category('Lazer');
        $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);
        $this->owner->favorites()->create(['name' => 'Cinema', 'amount' => 3000, 'category_id' => $leisure->id]);

        $this->put(route('categories.update', $leisure), ['name' => 'Diversão']);

        $this->get(route('expenses.index'))
            ->assertInertia(fn (Assert $page) => $page->where('days.0.expenses.0.category', 'Diversão'));
        $this->get(route('favorites.index'))
            ->assertInertia(fn (Assert $page) => $page->where('favorites.0.category', 'Diversão'));
    }

    public function test_renaming_does_not_change_which_expenses_use_the_category(): void
    {
        $leisure = $this->category('Lazer');
        $this->owner->expenses()->create(['amount' => 500, 'date' => '2026-11-05', 'category_id' => $leisure->id]);

        $this->put(route('categories.update', $leisure), ['name' => 'Diversão']);

        $this->assertSame(1, $leisure->fresh()->expenses()->count());
    }

    public function test_a_category_can_keep_its_name_or_change_only_the_case(): void
    {
        $leisure = $this->category('Lazer');

        $this->put(route('categories.update', $leisure), ['name' => 'Lazer'])->assertSessionHasNoErrors();
        $this->put(route('categories.update', $leisure), ['name' => 'LAZER'])->assertSessionHasNoErrors();

        $this->assertSame('LAZER', $leisure->fresh()->name);
    }

    public function test_renaming_to_the_name_of_another_category_is_rejected(): void
    {
        $leisure = $this->category('Lazer');

        $this->put(route('categories.update', $leisure), ['name' => 'saúde'])->assertSessionHasErrors('name');

        $this->assertSame('Lazer', $leisure->fresh()->name);
    }

    public function test_an_invalid_new_name_is_rejected(): void
    {
        $leisure = $this->category('Lazer');

        $this->put(route('categories.update', $leisure), ['name' => ''])->assertSessionHasErrors('name');
        $this->put(route('categories.update', $leisure), ['name' => str_repeat('a', 51)])->assertSessionHasErrors('name');

        $this->assertSame('Lazer', $leisure->fresh()->name);
    }

    public function test_another_user_may_have_a_category_with_the_same_name(): void
    {
        $other = User::factory()->create();
        $other->categories()->create(['name' => 'Pets']);

        $this->post(route('categories.store'), ['name' => 'Pets'])->assertSessionHasNoErrors();

        $this->assertContains('Pets', $this->names());
    }

    public function test_the_categories_of_another_user_are_not_listed_or_changeable(): void
    {
        $other = User::factory()->create()->categories()->create(['name' => 'Alheia']);

        $this->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page->has('categories', 8)->where('categories', fn ($c) => ! collect($c)->pluck('name')->contains('Alheia')));
        $this->put(route('categories.update', $other), ['name' => 'Minha'])->assertNotFound();

        $this->assertSame('Alheia', $other->fresh()->name);
    }
}
