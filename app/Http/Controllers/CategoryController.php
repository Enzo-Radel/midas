<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => $request->user()->categories()->orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $request->user()->categories()->create(['name' => $request->validated('name')]);

        return redirect()->route('categories.index');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $category->update(['name' => $request->validated('name')]);

        return redirect()->route('categories.index');
    }
}
