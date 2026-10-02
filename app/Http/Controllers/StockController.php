<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        return view('stock.index', ['products' => Product::where('is_active', true)->orderBy('name')->get(), 'movements' => StockMovement::with(['product', 'user'])->orderByDesc('occurred_at')->orderByDesc('id')->paginate(25)]);
    }

    public function store(Request $r, StockService $service)
    {
        $data = $r->validate(['product_id' => 'required|exists:products,id', 'quantity' => 'required|integer|min:1|max:1000000', 'note' => 'nullable|string|max:500']);
        $service->receive(Product::findOrFail($data['product_id']), $data['quantity'], $r->user(), $data['note'] ?? '');

        return back()->with('success', 'Stok masuk dicatat.');
    }
}
