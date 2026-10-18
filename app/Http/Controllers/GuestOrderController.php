<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use App\Support\QrImage;
use App\Support\Qris;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Halaman publik untuk tamu yang scan QR di meja; tanpa login. */
class GuestOrderController extends Controller
{
    public function menu(string $token)
    {
        $table = $this->table($token);
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $categories = Category::whereIn('id', $products->pluck('category_id'))->orderBy('name')->get();
        $productData = $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'category_id' => $p->category_id, 'price' => $p->selling_price, 'stock' => $p->stock])->values();
        $recent = Sale::where('dining_table_id', $table->id)->whereIn('checkout_token', session('qr_orders', []))
            ->whereIn('status', ['incoming', 'pending'])->latest()->get();
        return view('guest.menu', ['table' => $table, 'categories' => $categories, 'productData' => $productData, 'recent' => $recent,
            'checkoutToken' => (string) Str::uuid(), 'qrisEnabled' => self::qrisPayload() !== null]);
    }

    public function store(Request $r, string $token, SaleService $service)
    {
        $table = $this->table($token);
        $data = $r->validate([
            'checkout_token' => 'required|uuid', 'items' => 'required|array|min:1|max:50',
            'items.*.product_id' => 'required|integer|exists:products,id', 'items.*.quantity' => 'required|integer|min:1|max:100',
            'items.*.notes' => 'nullable|string|max:300', 'guest_name' => 'nullable|string|max:100',
            'qr_payment' => 'required|in:cashier'.(self::qrisPayload() ? ',qris' : ''),
        ]);
        $sale = $service->placeGuestOrder($table, $data);
        session()->put('qr_orders', array_slice(array_unique([...session('qr_orders', []), $sale->checkout_token]), -20));
        return redirect()->route('qr.order', [$table->token, $sale->checkout_token]);
    }

    public function show(string $token, string $order)
    {
        $table = $this->table($token, false);
        Sale::expireGuestOrders();
        $sale = $this->sale($table, $order);
        $payload = $sale->status === 'incoming' && $sale->qr_payment === 'qris' && ! $sale->qris_claimed_at ? self::qrisPayload() : null;
        return view('guest.order', ['table' => $table, 'sale' => $sale->load('items'),
            'qrSvg' => $payload ? QrImage::svg(Qris::withAmount($payload, $sale->qrisAmount()), 260) : null,
            'merchant' => $payload ? Qris::merchantName($payload) : null]);
    }

    public function claim(string $token, string $order, SaleService $service)
    {
        $table = $this->table($token, false);
        Sale::expireGuestOrders();
        $service->claimGuestPayment($this->sale($table, $order));
        return redirect()->route('qr.order', [$table->token, $order]);
    }

    /** Payload QRIS resto bila pembayaran QRIS dari HP tamu diaktifkan. */
    public static function qrisPayload(): ?string
    {
        $payload = AppSetting::get('qris_payload');
        return AppSetting::get('qris_enabled') === '1' && $payload && Qris::isValid($payload) ? $payload : null;
    }

    private function table(string $token, bool $activeOnly = true): DiningTable
    {
        $table = DiningTable::where('token', $token)->first();
        abort_if(! $table || ($activeOnly && ! $table->is_active), 404, 'QR meja tidak berlaku. Silakan pesan ke kasir.');
        return $table;
    }

    private function sale(DiningTable $table, string $order): Sale
    {
        abort_unless(Str::isUuid($order), 404);
        return Sale::where('checkout_token', $order)->where('dining_table_id', $table->id)->where('source', 'qr')->firstOrFail();
    }
}
