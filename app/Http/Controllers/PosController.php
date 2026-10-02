<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PosController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->where('is_active', true)->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();
        $checkoutToken = (string) Str::uuid();
        $productData = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'category_id' => $p->category_id, 'price' => $p->selling_price, 'stock' => $p->stock])->values();

        return view('pos.index', compact('products', 'categories', 'checkoutToken', 'productData'));
    }

    public function store(Request $r, SaleService $service)
    {
        $data = $r->validate(['checkout_token' => 'required|uuid', 'items' => 'required|array|min:1|max:100', 'items.*.product_id' => 'required|integer|exists:products,id', 'items.*.quantity' => 'required|integer|min:1|max:10000', 'discount' => 'nullable|integer|min:0|max:1000000000000', 'payment_method' => 'required|in:cash,qris,transfer', 'paid_amount' => 'nullable|integer|min:0|max:1000000000000', 'confirmed' => 'accepted']);
        $sale = $service->checkout($r->user(), $data);

        return redirect()->route('sales.show', $sale)->with('success', 'Transaksi berhasil disimpan.');
    }

    public function show(Request $r, Sale $sale)
    {
        abort_unless($r->user()->hasPermission('sales.view') || ($r->user()->hasPermission('pos.view') && $sale->user_id === $r->user()->id), 403);
        $sale->load('items', 'user');

        return view('sales.show', compact('sale'));
    }
}
