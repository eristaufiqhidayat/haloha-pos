<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return view('categories.index', ['categories' => Category::withCount('products')->orderBy('name')->get()]);
    }

    public function store(Request $r)
    {
        Category::create($r->validate(['name' => 'required|string|max:100|unique:categories,name']));

        return back()->with('success', 'Kategori dibuat.');
    }

    public function update(Request $r, Category $category)
    {
        $category->update($r->validate(['name' => ['required', 'string', 'max:100', Rule::unique('categories')->ignore($category->id)]]));

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Kategori masih digunakan produk.']);
        }$category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }
}
