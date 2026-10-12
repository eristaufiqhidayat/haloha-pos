<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TakeawayPerItemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::where('email', 'kasir@haloha.test')->firstOrFail());
    }

    private function product(string $sku): Product
    {
        return Product::where('sku', $sku)->firstOrFail();
    }

    private function store(array $items, array $extra = [])
    {
        return $this->post(route('pos.store'), $extra + [
            'action' => 'pending', 'checkout_token' => (string) Str::uuid(), 'guest_name' => 'Rina', 'table_number' => '05', 'items' => $items,
        ]);
    }

    public function test_mixed_order_keeps_table_and_marks_items_on_tickets_and_receipt(): void
    {
        $food = $this->product('HL-001');
        $drink = $this->product('HL-002');
        $this->store([
            ['product_id' => $food->id, 'quantity' => 2, 'takeaway' => 0],
            ['product_id' => $food->id, 'quantity' => 1, 'takeaway' => 1, 'notes' => 'Sambal dipisah'],
            ['product_id' => $drink->id, 'quantity' => 1, 'takeaway' => 1],
        ])->assertRedirect(route('orders.index'));
        $sale = Sale::with('items')->latest('id')->firstOrFail();
        $this->assertFalse($sale->takeaway);
        $this->assertSame('05', $sale->table_number);
        $this->assertCount(3, $sale->items);
        $this->assertSame(2, $sale->takeawayQuantity());
        $this->assertSame('Meja 05 · ada take away', $sale->placeLabel());

        $this->get(route('orders.index'))->assertSee('2 porsi take away');
        $this->get(route('orders.kitchen', $sale))->assertOk()->assertSee('ADA TAKE AWAY')
            ->assertSeeInOrder(['MAKAN DI SINI', '2 × '.$food->name, 'TAKE AWAY · BUNGKUS', '1 × '.$food->name, 'Sambal dipisah']);
        $this->get(route('orders.edit', $sale))->assertOk()->assertSee('\u0022takeaway\u0022:true', false);

        $this->post(route('orders.pay', $sale), ['payments' => [['method' => 'cash', 'amount' => $sale->total, 'paid_amount' => $sale->total]]]);
        $this->get(route('sales.show', $sale))->assertOk()->assertSee('[TA]')->assertSee('[TA] = take away');
    }

    public function test_table_is_required_unless_every_item_is_takeaway(): void
    {
        $id = $this->product('HL-001')->id;
        $this->store([['product_id' => $id, 'quantity' => 1, 'takeaway' => 0], ['product_id' => $id, 'quantity' => 1, 'takeaway' => 1]], ['table_number' => ''])
            ->assertSessionHasErrors('table_number');
        $this->store([['product_id' => $id, 'quantity' => 2, 'takeaway' => 1]], ['table_number' => ''])->assertSessionHasNoErrors();
        $sale = Sale::latest('id')->firstOrFail();
        $this->assertTrue($sale->takeaway);
        $this->assertNull($sale->table_number);
        $this->get(route('orders.kitchen', $sale))->assertSee('TAKE AWAY')->assertDontSee('MAKAN DI SINI')->assertDontSee('ADA TAKE AWAY');
    }

    public function test_order_level_takeaway_from_old_clients_still_applies_to_every_item(): void
    {
        $this->store([['product_id' => $this->product('HL-001')->id, 'quantity' => 1]], ['takeaway' => 1, 'table_number' => ''])->assertSessionHasNoErrors();
        $sale = Sale::with('items')->latest('id')->firstOrFail();
        $this->assertTrue($sale->takeaway);
        $this->assertTrue($sale->items->first()->takeaway);
    }

    public function test_split_bill_keeps_takeaway_per_portion(): void
    {
        $id = $this->product('HL-001')->id;
        $this->store([['product_id' => $id, 'quantity' => 2, 'takeaway' => 0], ['product_id' => $id, 'quantity' => 1, 'takeaway' => 1]]);
        $sale = Sale::with('items')->latest('id')->firstOrFail();
        [$dine, $ta] = [$sale->items->firstWhere('takeaway', false), $sale->items->firstWhere('takeaway', true)];
        $this->assertNotSame($dine->splitKey(), $ta->splitKey());
        $this->post(route('orders.split.store', $sale), ['mode' => 'item', 'count' => 2, 'alloc' => [
            $dine->splitKey() => [1 => 2], $ta->splitKey() => [2 => 1],
        ]])->assertRedirect(route('orders.bills', $sale));
        $this->assertTrue($sale->bills()->where('number', 2)->firstOrFail()->items()->firstOrFail()->takeaway);
        $this->get(route('orders.bills', $sale))->assertSee('[TA]');
        $this->delete(route('orders.split.destroy', $sale));
        $this->assertSame([false, true], $sale->items()->orderBy('id')->pluck('takeaway')->all());
    }
}
