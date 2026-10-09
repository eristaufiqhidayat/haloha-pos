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
    public function index(Request $r, ?Sale $order = null)
    {
        if ($order) {
            $this->ownOrder($r, $order);
            abort_unless($order->status === 'pending', 409);
            $order->load('items');
        }
        $catalogUser = $order?->user ?? $r->user();
        $products = Product::with('category')->where('is_active', true)->orderBy('name')->get()
            ->filter(fn ($p) => $catalogUser->role->is_system || $catalogUser->product_ids === null || in_array($p->id, $catalogUser->product_ids, true));
        $categories = Category::orderBy('name')->get();
        $checkoutToken = $order?->checkout_token ?? (string) Str::uuid();
        $productData = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'category_id' => $p->category_id, 'price' => $p->selling_price, 'stock' => $p->stock])->values();
        return view('pos.index', compact('products', 'categories', 'checkoutToken', 'productData', 'order'));
    }

    private function itemData(Request $r, bool $pending = true): array
    {
        return $r->validate([
            'checkout_token' => 'required|uuid', 'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|exists:products,id', 'items.*.quantity' => 'required|integer|min:1|max:10000', 'items.*.notes' => 'nullable|string|max:600',
            'guest_name' => 'nullable|string|max:100', 'takeaway' => 'nullable|boolean',
            'table_number' => ($pending ? 'required_unless:takeaway,1|' : 'nullable|').'string|max:30',
            'discount_enabled' => 'nullable|boolean', 'discount' => 'nullable|integer|min:0|max:1000000000000',
            'tax_enabled' => 'nullable|boolean', 'tax_rate' => 'nullable|integer|min:0|max:100',
            'payment_method' => ($pending ? 'nullable' : 'required').'|in:cash,qris,transfer,debit',
            'paid_amount' => 'nullable|integer|min:0|max:1000000000000', 'confirmed' => $pending ? 'nullable' : 'accepted',
        ]);
    }

    public function store(Request $r, SaleService $service)
    {
        $pending = $r->input('action') === 'pending';
        $data = $this->itemData($r, $pending);
        $sale = $pending ? $service->savePending($r->user(), $data) : $service->checkout($r->user(), $data);
        return redirect()->route($pending ? 'orders.index' : 'sales.show', $pending ? [] : [$sale])->with('success', $pending ? 'Pesanan tersimpan. Cetak dapur lalu lanjutkan pembayaran.' : 'Transaksi berhasil disimpan.');
    }

    public function orders(Request $r)
    {
        $orders = Sale::with('items', 'user')->where('status', 'pending')
            ->when(! $r->user()->role->is_system, fn ($q) => $q->where('user_id', $r->user()->id))
            ->orderBy('created_at')->get();
        return view('pos.orders', compact('orders'));
    }

    public function update(Request $r, Sale $order, SaleService $service)
    {
        $this->ownOrder($r, $order);
        $service->savePending($r->user(), $this->itemData($r), $order);
        return redirect()->route('orders.index')->with('success', 'Pesanan diperbarui.');
    }

    public function payment(Request $r, Sale $order)
    {
        $this->ownOrder($r, $order);
        if ($order->status === 'paid') {
            return redirect()->route('sales.show', $order);
        }
        $order->load('items');
        return view('pos.payment', compact('order'));
    }

    public function pay(Request $r, Sale $order, SaleService $service)
    {
        $this->ownOrder($r, $order);
        $data = $r->validate([
            'payments' => 'required|array|min:1|max:4', 'payments.*.method' => 'required|distinct|in:cash,qris,transfer,debit',
            'payments.*.amount' => 'required|integer|min:0|max:1000000000000',
            'payments.*.paid_amount' => 'nullable|integer|min:0|max:1000000000000',
            'payments.*.confirmed' => 'nullable|boolean',
        ]);
        $sale = $service->payPending($r->user(), $order, $data);
        return redirect()->route('sales.show', $sale)->with('success', 'Pembayaran tercatat.');
    }

    public function kitchen(Request $r, Sale $order)
    {
        $this->ownOrder($r, $order);
        $order->load('items', 'user');
        return view('sales.kitchen', ['sale' => $order]);
    }

    private function ownOrder(Request $r, Sale $order): void
    {
        abort_unless($r->user()->role->is_system || $order->user_id === $r->user()->id, 403);
    }

    public function show(Request $r, Sale $sale)
    {
        abort_unless($r->user()->hasPermission('sales.view') || ($r->user()->hasPermission('pos.view') && $sale->user_id === $r->user()->id), 403);
        $sale->load('items', 'user', 'payments');
        if ($sale->status !== 'paid') {
            return redirect()->route('orders.index');
        }
        return view('sales.show', compact('sale'));
    }
}
