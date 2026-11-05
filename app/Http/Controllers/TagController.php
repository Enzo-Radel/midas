<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TagController extends Controller
{
    public function index(Request $request): Response
    {
        $tags = $request->user()->tags()
            ->withCount(['expenses', 'favorites'])
            ->get()
            // Ordem alfabética sem diferenciar maiúsculas nem acentos ("Água" fica junto do "A").
            ->sortBy(fn (Tag $tag) => Str::lower(Str::ascii($tag->name)))
            ->values()
            ->map(fn (Tag $tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'expenses_count' => $tag->expenses_count,
                'favorites_count' => $tag->favorites_count,
            ]);

        return Inertia::render('Tags/Index', ['tags' => $tags]);
    }

    public function update(TagRequest $request, Tag $tag): RedirectResponse
    {
        $this->authorizeOwner($request, $tag);

        $tag->update(['name' => $request->validated('name')]);

        return redirect()->route('tags.index');
    }

    /**
     * Exclui a tag. Ela só é retirada dos gastos e favoritos que a tinham (os vínculos somem junto
     * com ela); os gastos e favoritos em si não são afetados.
     */
    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        $this->authorizeOwner($request, $tag);

        $tag->delete();

        return redirect()->route('tags.index');
    }

    private function authorizeOwner(Request $request, Tag $tag): void
    {
        abort_unless($tag->user_id === $request->user()->id, 404);
    }
}
