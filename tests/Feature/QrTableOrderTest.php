<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\DiningTable;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\ReportService;
use App\Support\Qris;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrTableOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    private function table(string $number = '05'): DiningTable
    {
        return DiningTable::create(['number' => $number]);
    }

    private static function staticQris(): string
    {
        $f = fn ($t, $v) => $t.str_pad((string) strlen($v), 2, '0', STR_PAD_LEFT).$v;
        $body = $f('00', '01').$f('01', '11').$f('26', $f('00', 'COM.GO-JEK.WWW').$f('01', '936009143123456789').$f('03', 'UMI'))
            .$f('51', $f('00', 'ID.CO.QRIS.WWW').$f('02', 'ID1020000000001').$f('03', 'UMI')).$f('52', '5812').$f('53', '360')
            .$f('58', 'ID').$f('59', 'HALOHA KITCHEN').$f('60', 'JAKARTA').'6304';
        return $body.Qris::crc($body);
    }

    private function enableQris(): void
    {
        AppSetting::put('qris_payload', self::staticQris());
        AppSetting::put('qris_enabled', '1');
    }

    private function order(DiningTable $table, string $payment = 'cashier', array $extra = [])
    {
        return $this->post(route('qr.store', $table->token), $extra + [
            'checkout_token' => (string) Str::uuid(), 'guest_name' => 'Rina', 'qr_payment' => $payment,
            'items' => [['product_id' => Product::where('sku', 'HL-001')->firstOrFail()->id, 'quantity' => 2, 'notes' => 'Tidak pedas']],
        ]);
    }

    public function test_qris_amount_is_added_with_valid_crc(): void
    {
        $static = self::staticQris();
        $this->assertTrue(Qris::isValid($static));
        $this->assertSame('29B1', Qris::crc('123456789'));
        $dynamic = Qris::withAmount($static, 96037);
        $this->assertTrue(Qris::isValid($dynamic));
        $this->assertStringContainsString('010212', $dynamic);
        $this->assertStringContainsString('540596037', $dynamic);
        $this->assertSame('HALOHA KITCHEN', Qris::merchantName($dynamic));
        $this->assertFalse(Qris::isValid(substr($static, 0, -4).'0000'));
    }

    public function test_scanning_table_qr_fills_table_and_cashier_confirms_before_kitchen(): void
    {
        $table = $this->table();
        $product = Product::where('sku', 'HL-001')->firstOrFail();
        $stock = $product->stock;
        $this->get(route('qr.menu', $table->token))->assertOk()->assertSee('Meja 05')->assertSee($product->name)->assertDontSee('Bayar sekarang pakai QRIS');
        // Nomor meja dari tamu diabaikan; yang dipakai meja milik token QR.
        $this->order($table, 'cashier', ['table_number' => '99'])->assertRedirect();
        $sale = Sale::where('source', 'qr')->firstOrFail();
        $this->assertSame(['incoming', '05', null, $table->id], [$sale->status, $sale->table_number, $sale->user_id, $sale->dining_table_id]);
        $this->assertSame($product->selling_price * 2, $sale->total);
        $this->assertSame($stock, $product->fresh()->stock);
        $this->get(route('qr.order', [$table->token, $sale->checkout_token]))->assertOk()->assertSee('Menunggu konfirmasi kasir');

        $cashier = $this->user('kasir@haloha.test');
        $this->actingAs($cashier)->get(route('orders.index'))->assertOk()->assertSee('Pesanan dari QR meja')->assertSee('Perlu konfirmasi')->assertSee('Tidak pedas');
        $this->get(route('orders.kitchen', $sale))->assertForbidden();
        $this->post(route('orders.accept', $sale), ['as' => 'cashier'])->assertRedirect(route('orders.index'));
        $sale->refresh();
        $this->assertSame(['pending', $cashier->id, 'Kasir 01'], [$sale->status, $sale->user_id, $sale->cashier_name]);
        $this->get(route('orders.kitchen', $sale))->assertOk()->assertSee('Tidak pedas');
        $this->post(route('orders.accept', $sale), ['as' => 'cashier'])->assertSessionHasErrors('order');
        $this->post(route('orders.pay', $sale), ['payments' => [['method' => 'cash', 'amount' => $sale->total, 'paid_amount' => $sale->total]]])->assertRedirect(route('sales.show', $sale));
        $this->assertSame($stock - 2, $product->fresh()->stock);
        auth()->logout();
        $this->get(route('qr.order', [$table->token, $sale->checkout_token]))->assertSee('Pesanan sedang disiapkan')->assertSee('Lunas');
    }

    public function test_qris_order_shows_amount_and_cashier_records_payment_after_funds_arrive(): void
    {
        $this->enableQris();
        $table = $this->table('09');
        $this->get(route('qr.menu', $table->token))->assertSee('Bayar sekarang pakai QRIS');
        $this->order($table, 'qris');
        $sale = Sale::where('source', 'qr')->firstOrFail();
        $this->assertGreaterThanOrEqual(1, $sale->qris_code);
        $this->assertLessThanOrEqual(99, $sale->qris_code);
        $this->get(route('qr.order', [$table->token, $sale->checkout_token]))->assertOk()->assertSee('<svg', false)
            ->assertSee('Rp '.number_format($sale->qrisAmount(), 0, ',', '.'))->assertSee('Saya sudah bayar');

        // Belum diklaim tamu: tidak dihitung menunggu kasir.
        $this->assertSame(0, Sale::awaitingCashier()->count());
        $this->post(route('qr.claim', [$table->token, $sale->checkout_token]))->assertRedirect();
        $this->assertNotNull($sale->fresh()->qris_claimed_at);
        $this->assertSame(1, Sale::awaitingCashier()->count());
        $this->get(route('qr.order', [$table->token, $sale->checkout_token]))->assertSee('Menunggu kasir cek dana masuk');

        $this->actingAs($this->user('kasir@haloha.test'))->get(route('orders.index'))->assertSee('Cek dana masuk');
        $before = app(ReportService::class)->sales(now()->toDateString())['revenue'];
        $this->post(route('orders.accept', $sale), ['as' => 'qris'])->assertRedirect(route('sales.show', $sale));
        $sale->refresh()->load('payments');
        $this->assertSame(['paid', 'qris'], [$sale->status, $sale->payment_method]);
        $this->assertSame([$sale->total, $sale->qrisAmount()], [$sale->payments->first()->amount, $sale->payments->first()->paid_amount]);
        $this->assertSame($before + $sale->total, app(ReportService::class)->sales(now()->toDateString())['revenue']);
        $this->get(route('sales.show', $sale))->assertSee('Cetak dapur')->assertSee('QRIS diterima (kode unik)');
    }

    public function test_unpaid_qris_order_expires_and_rejected_orders_are_cancelled(): void
    {
        $this->enableQris();
        $table = $this->table();
        $this->order($table, 'qris');
        $sale = Sale::where('source', 'qr')->firstOrFail();
        $this->travel(31)->minutes();
        $this->get(route('qr.order', [$table->token, $sale->checkout_token]))->assertSee('Pesanan dibatalkan');
        $this->assertSame('cancelled', $sale->fresh()->status);
        $this->post(route('qr.claim', [$table->token, $sale->checkout_token]));
        $this->assertNull($sale->fresh()->qris_claimed_at);

        $this->order($table);
        $other = Sale::where('source', 'qr')->where('status', 'incoming')->firstOrFail();
        $this->actingAs($this->user('kasir@haloha.test'))->post(route('orders.reject', $other))->assertRedirect(route('orders.index'));
        $this->assertSame('cancelled', $other->fresh()->status);
    }

    public function test_guest_cannot_choose_qris_when_disabled_or_use_inactive_or_old_qr(): void
    {
        $table = $this->table();
        $this->order($table, 'qris')->assertSessionHasErrors('qr_payment');
        $old = $table->token;
        $table->regenerateToken();
        $this->get(route('qr.menu', $old))->assertNotFound();
        $this->get(route('qr.menu', $table->token))->assertOk();
        $table->update(['is_active' => false]);
        $this->get(route('qr.menu', $table->token))->assertNotFound();
        $this->order($table)->assertNotFound();
        $this->assertSame(0, Sale::count() - Sale::where('source', 'pos')->count());
    }

    public function test_guest_order_is_idempotent_and_bound_to_its_table(): void
    {
        $table = $this->table();
        $token = (string) Str::uuid();
        $this->order($table, 'cashier', ['checkout_token' => $token]);
        $this->order($table, 'cashier', ['checkout_token' => $token]);
        $this->assertSame(1, Sale::where('checkout_token', $token)->count());
        $other = $this->table('06');
        $this->order($other, 'cashier', ['checkout_token' => $token])->assertStatus(409);
        $this->get(route('qr.order', [$other->token, $token]))->assertNotFound();
    }

    public function test_admin_manages_tables_and_qris_setting_with_permissions(): void
    {
        $this->actingAs($this->user('kasir@haloha.test'))->get(route('tables.index'))->assertForbidden();
        $this->actingAs($this->user('admin@haloha.test'));
        $this->post(route('tables.store'), ['number' => '07', 'description' => 'Teras'])->assertRedirect();
        $table = DiningTable::where('number', '07')->firstOrFail();
        $this->assertTrue($table->is_active);
        $this->post(route('tables.store'), ['number' => '07'])->assertSessionHasErrors('number');
        $this->get(route('tables.index'))->assertOk()->assertSee('Meja 07')->assertSee($table->orderUrl());
        $this->get(route('tables.print', ['table' => $table->id]))->assertOk()->assertSee('MEJA 07');
        $old = $table->token;
        $this->post(route('tables.token', $table))->assertRedirect();
        $this->assertNotSame($old, $table->fresh()->token);
        $this->put(route('tables.update', $table), ['number' => '7A', 'is_active' => 0])->assertRedirect();
        $this->assertSame(['7A', false], [$table->fresh()->number, $table->fresh()->is_active]);

        $this->put(route('tables.qris'), ['qris_enabled' => 1])->assertSessionHasErrors('qris_payload');
        $this->put(route('tables.qris'), ['qris_payload' => 'bukan-qris', 'qris_enabled' => 1])->assertSessionHasErrors('qris_payload');
        $this->put(route('tables.qris'), ['qris_payload' => self::staticQris(), 'qris_enabled' => 1])->assertSessionHasNoErrors();
        $this->assertSame('1', AppSetting::get('qris_enabled'));
        // Simpan tanpa foto baru mempertahankan QRIS yang terpasang.
        $this->put(route('tables.qris'), ['qris_enabled' => 0])->assertSessionHasNoErrors();
        $this->assertSame(self::staticQris(), AppSetting::get('qris_payload'));
        $this->get(route('tables.index'))->assertSee('HALOHA KITCHEN');

        $this->delete(route('tables.destroy', $table))->assertRedirect();
        $this->assertDatabaseMissing('dining_tables', ['id' => $table->id]);
    }
}
