<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'kasir@haloha.test')->firstOrFail());
    }

    private function order(): Sale
    {
        $product = Product::where('sku', 'HL-001')->firstOrFail();
        $this->post(route('pos.store'), [
            'action' => 'pending', 'checkout_token' => (string) Str::uuid(), 'guest_name' => 'Rina', 'table_number' => '05',
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'takeaway' => 0]],
        ])->assertRedirect(route('orders.index'));

        return Sale::latest('id')->firstOrFail();
    }

    public function test_invoice_numbers_are_sequential_per_day_and_reset_next_day(): void
    {
        Carbon::setTestNow('2030-01-15 08:00:00');
        $this->assertSame('INV-2030-01-15-0001', $this->order()->invoice_number);
        $this->assertSame('INV-2030-01-15-0002', $this->order()->invoice_number);

        Carbon::setTestNow('2030-01-16 07:30:00');
        $this->assertSame('INV-2030-01-16-0001', $this->order()->invoice_number);

        Carbon::setTestNow();
    }

    public function test_old_invoice_numbers_are_left_untouched(): void
    {
        Carbon::setTestNow('2030-01-15 08:00:00');
        $old = $this->order();
        $old->update(['invoice_number' => 'INV-20261011093000-ABCD1234']);
        $this->assertSame('INV-2030-01-15-0002', $this->order()->invoice_number);
        $this->assertSame('INV-20261011093000-ABCD1234', $old->fresh()->invoice_number);

        Carbon::setTestNow();
    }
}
