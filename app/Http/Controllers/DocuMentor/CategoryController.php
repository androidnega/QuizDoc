<?php

namespace App\Http\Controllers\DocuMentor;

use App\Http\Controllers\Controller;
use App\Models\DocuMentor\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Section 7: CRUD Categories. Coordinator manages: Create, Update, Delete Categories.
 */
class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('projects')->orderBy('name')->get();
        return view('docu-mentor.coordinators.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('docu-mentor.coordinators.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        Category::create($request->only('name'));
        return redirect()->route('dashboard.coordinators.categories.index')
            ->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('docu-mentor.coordinators.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
        ]);

        $category->update($request->only('name'));
        return redirect()->route('dashboard.coordinators.categories.index')
            ->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->projects()->exists()) {
            return back()->with('error', 'Cannot delete category with projects.');
        }
        $category->delete();

        // When table is empty, reset auto-increment so next ID starts at 1
        if (Category::count() === 0) {
            $driver = DB::getDriverName();
            $table = (new Category)->getTable();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            }
            if ($driver === 'sqlite') {
                DB::statement("DELETE FROM sqlite_sequence WHERE name = ?", [$table]);
            }
        }

        return redirect()->route('dashboard.coordinators.categories.index')
            ->with('success', 'Category deleted.');
    }
}
