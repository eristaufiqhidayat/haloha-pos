<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleBill;
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
            if ($order->hasPaidBill()) {
                return redirect()->route('orders.bills', $order)->withErrors(['order' => 'Sebagian tagihan sudah lunas. Pesanan tidak dapat diubah.']);
            }
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
            'items.*.takeaway' => 'nullable|boolean', 'guest_name' => 'nullable|string|max:100', 'takeaway' => 'nullable|boolean',
            'table_number' => ($pending && ! $this->allTakeaway($r) ? 'required|' : 'nullable|').'string|max:30',
            'discount_enabled' => 'nullable|boolean', 'discount' => 'nullable|integer|min:0|max:1000000000000',
            'tax_enabled' => 'nullable|boolean', 'tax_rate' => 'nullable|integer|min:0|max:100',
            'payment_method' => ($pending ? 'nullable' : 'required').'|in:cash,qris,transfer,debit',
            'paid_amount' => 'nullable|integer|min:0|max:1000000000000', 'confirmed' => $pending ? 'nullable' : 'accepted',
        ]);
    }

    /** Nomor meja hanya boleh kosong bila semua menu take away. */
    private function allTakeaway(Request $r): bool
    {
        $items = $r->input('items');
        if (! is_array($items) || $items === []) {
            return $r->boolean('takeaway');
        }
        foreach ($items as $row) {
            $value = is_array($row) && array_key_exists('takeaway', $row) ? $row['takeaway'] : $r->input('takeaway');
            if (! filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                return false;
            }
        }
        return true;
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
        $orders = Sale::with('items', 'user', 'bills')->where('status', 'pending')
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
        if ($order->isSplit()) {
            return redirect()->route('orders.bills', $order);
        }
        $order->load('items');
        return view('pos.payment', ['order' => $order, 'bill' => null, 'payTotal' => $order->total, 'action' => route('orders.pay', $order)]);
    }

    public function pay(Request $r, Sale $order, SaleService $service)
    {
        $this->ownOrder($r, $order);
        $sale = $service->payPending($r->user(), $order, $this->paymentData($r));
        return redirect()->route('sales.show', $sale)->with('success', 'Pembayaran tercatat.');
    }

    private function paymentData(Request $r): array
    {
        return $r->validate([
            'payments' => 'required|array|min:1|max:4', 'payments.*.method' => 'required|distinct|in:cash,qris,transfer,debit',
            'payments.*.amount' => 'required|integer|min:0|max:1000000000000',
            'payments.*.paid_amount' => 'nullable|integer|min:0|max:1000000000000',
            'payments.*.confirmed' => 'nullable|boolean',
        ]);
    }

    public function splitForm(Request $r, Sale $order)
    {
        $this->ownOrder($r, $order);
        $order->load('items', 'bills.items');
        if ($order->status !== 'pending' || $order->bills->contains->isPaid()) {
            return redirect()->route($order->status === 'paid' ? 'sales.show' : 'orders.bills', $order);
        }
        // Editor memakai baris menu gabungan; pembagian lama (jika ada) dimuat sebagai nilai awal.
        $items = $order->items->groupBy->splitKey()->map(fn ($rows) => [
            'key' => $rows->first()->splitKey(), 'name' => $rows->first()->product_name, 'notes' => $rows->first()->notes, 'takeaway' => $rows->first()->takeaway, 'price' => $rows->first()->unit_price,
            'qty' => (int) $rows->sum('quantity'), 'alloc' => $rows->filter->sale_bill_id->groupBy(fn ($i) => $order->bills->firstWhere('id', $i->sale_bill_id)->number)->map->sum('quantity'),
        ])->values();
        return view('pos.split', ['order' => $order, 'items' => $items, 'max' => SaleBill::MAX]);
    }

    public function storeSplit(Request $r, Sale $order, SaleService $service)
    {
        $this->ownOrder($r, $order);
        $data = $r->validate([
            'mode' => 'required|in:item,equal', 'count' => 'required|integer|min:2|max:'.SaleBill::MAX,
            'labels' => 'nullable|array|max:'.SaleBill::MAX, 'labels.*' => 'nullable|string|max:40',
            'alloc' => 'required_if:mode,item|array', 'alloc.*' => 'array', 'alloc.*.*' => 'integer|min:0|max:10000',
        ]);
        $service->split($r->user(), $order, $data);
        return redirect()->route('orders.bills', $order)->with('success', 'Pesanan dibagi menjadi '.$data['count'].' tagihan.');
    }

    public function destroySplit(Request $r, Sale $order, SaleService $service)
    {
        $this->ownOrder($r, $order);
        $service->unsplit($r->user(), $order);
        return redirect()->route('orders.index')->with('success', 'Pembagian tagihan dibatalkan.');
    }

    public function bills(Request $r, Sale $order)
    {
        $this->ownOrder($r, $order);
        $order->load('bills.items', 'bills.payments', 'items');
        if (! $order->isSplit()) {
            return redirect()->route($order->status === 'paid' ? 'sales.show' : 'orders.index', $order->status === 'paid' ? [$order] : []);
        }
        return view('pos.bills', compact('order'));
    }

    public function billPayment(Request $r, Sale $order, int $number)
    {
        $this->ownOrder($r, $order);
        $bill = $order->bills()->where('number', $number)->with('items')->firstOrFail();
        if ($bill->isPaid()) {
            return redirect()->route('sales.bill', [$order, $number]);
        }
        $order->load('items');
        return view('pos.payment', ['order' => $order, 'bill' => $bill, 'payTotal' => $bill->total, 'action' => route('orders.bills.pay', [$order, $number])]);
    }

    public function payBill(Request $r, Sale $order, int $number, SaleService $service)
    {
        $this->ownOrder($r, $order);
        $bill = $service->payBill($r->user(), $order, $number, $this->paymentData($r));
        return redirect()->route('sales.bill', [$order, $bill->number])->with('success', $bill->title().' lunas.');
    }

    public function billReceipt(Request $r, Sale $sale, int $number)
    {
        abort_unless($r->user()->hasPermission('sales.view') || ($r->user()->hasPermission('pos.view') && $sale->user_id === $r->user()->id), 403);
        $sale->load('user', 'bills');
        $bill = $sale->bills->firstWhere('number', $number) ?? abort(404);
        abort_unless($bill->isPaid(), 404);
        $bill->load('items', 'payments');
        return view('sales.bill', compact('sale', 'bill'));
    }

    public function kitchen(Request $r, Sale $order, string $station = 'dapur')
    {
        $this->ownOrder($r, $order);
        $station = Sale::kitchenStation($station) ?? abort(404);
        $order->load('items', 'user');
        return view('sales.kitchen', ['sale' => $order, 'station' => $station, 'items' => $order->kitchenItems($station)]);
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
