<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $r)
    {
        $q = trim((string) $r->query('q', ''));
        $products = Product::with('category')->when($q, fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('sku', 'like', '%'.$q.'%')))->orderBy('name')->paginate(20)->withQueryString();

        return view('products.index', compact('products', 'q'));
    }

    public function create()
    {
        return $this->form(new Product(['is_active' => true, 'minimum_stock' => 8]));
    }

    public function edit(Product $product)
    {
        return $this->form($product);
    }

    private function form(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('products.form', compact('product', 'categories'));
    }

    private function data(Request $r, ?Product $p = null)
    {
        return $r->validate(['name' => 'required|string|max:150', 'sku' => ['required', 'string', 'max:60', Rule::unique('products')->ignore($p?->id)], 'category_id' => 'required|exists:categories,id', 'cost_price' => 'required|integer|min:0|max:1000000000', 'selling_price' => 'required|integer|min:1|max:1000000000', 'minimum_stock' => 'required|integer|min:0|max:1000000', 'is_active' => 'required|boolean']);
    }

    public function store(Request $r)
    {
        Product::create($this->data($r));

        return redirect()->route('products.index')->with('success', 'Produk dibuat. Catat persediaan melalui menu Stok masuk.');
    }

    public function update(Request $r, Product $product)
    {
        $product->update($this->data($r, $product));

        return redirect()->route('products.index')->with('success', 'Produk diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);

        return back()->with('success','Produk diarsipkan. Riwayat transaksi tetap tersedia.');
    }
}
