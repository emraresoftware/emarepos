<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleAndStockTransferFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_store_decrements_branch_stock_and_saves_purchase_cost_snapshot(): void
    {
        [$tenant, $branch, $user] = $this->hazirKullaniciOrtami();
        $product = $this->urunOlustur($tenant->id, [
            'name' => 'Kahve',
            'purchase_price' => 40,
            'sale_price' => 100,
        ]);
        $product->setStockForBranch($branch->id, 10);

        $response = $this->actingAs($user)
            ->postJson(route('pos.sales.store'), [
                'payment_method' => 'cash',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 100,
                ]],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $sale = Sale::with('items')->firstOrFail();
        $saleItem = $sale->items->first();
        $product->refresh();

        $this->assertSame(7.0, $product->stockForBranch($branch->id));
        $this->assertSame(7.0, (float) $product->stock_quantity);
        $this->assertSame('40.00', $saleItem->purchase_cost);
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'sale',
            'quantity' => -3,
        ]);
    }

    public function test_sale_store_rejects_when_branch_stock_is_insufficient(): void
    {
        [$tenant, $branch, $user] = $this->hazirKullaniciOrtami();
        $product = $this->urunOlustur($tenant->id, [
            'name' => 'Çay',
            'purchase_price' => 15,
            'sale_price' => 50,
        ]);
        $product->setStockForBranch($branch->id, 2);

        $response = $this->actingAs($user)
            ->postJson(route('pos.sales.store'), [
                'payment_method' => 'cash',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 5,
                    'unit_price' => 50,
                ]],
            ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $product->refresh();

        $this->assertSame(2.0, $product->stockForBranch($branch->id));
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_refund_restores_branch_stock_and_marks_sale_refunded(): void
    {
        [$tenant, $branch, $user] = $this->hazirKullaniciOrtami();
        $product = $this->urunOlustur($tenant->id, [
            'name' => 'Pasta',
            'purchase_price' => 55,
            'sale_price' => 140,
        ]);
        $product->setStockForBranch($branch->id, 8);

        $saleResponse = $this->actingAs($user)
            ->postJson(route('pos.sales.store'), [
                'payment_method' => 'cash',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'unit_price' => 140,
                ]],
            ]);

        $saleResponse->assertOk();
        $sale = Sale::firstOrFail();

        $refundResponse = $this->actingAs($user)
            ->postJson(route('pos.sales.refund', $sale), [
                'reason' => 'Test iadesi',
            ]);

        $refundResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $sale->refresh();
        $product->refresh();

        $this->assertSame('refunded', $sale->status);
        $this->assertStringContainsString('Test iadesi', (string) $sale->notes);
        $this->assertSame(8.0, $product->stockForBranch($branch->id));
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'return',
            'quantity' => 2,
        ]);
    }

    public function test_stock_transfer_approve_moves_stock_between_branches_and_preserves_total(): void
    {
        [$tenant, $branchA, $user] = $this->hazirKullaniciOrtami();
        $branchB = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Şube B',
            'is_active' => true,
        ]);

        $product = $this->urunOlustur($tenant->id, [
            'name' => 'Su',
            'purchase_price' => 8,
            'sale_price' => 20,
        ]);
        $product->setStockForBranch($branchA->id, 9);
        $product->setStockForBranch($branchB->id, 2);

        $createResponse = $this->actingAs($user)
            ->postJson(route('pos.stock-transfers.store'), [
                'to_branch_id' => $branchB->id,
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 4,
                ]],
            ]);

        $createResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $transfer = StockTransfer::firstOrFail();

        $approveResponse = $this->actingAs($user)
            ->postJson(route('pos.stock-transfers.approve', $transfer));

        $approveResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $product->refresh();
        $transfer->refresh();

        $this->assertSame('completed', $transfer->status);
        $this->assertSame(5.0, $product->stockForBranch($branchA->id));
        $this->assertSame(6.0, $product->stockForBranch($branchB->id));
        $this->assertSame(11.0, (float) $product->stock_quantity);
        $this->assertSame(2, StockMovement::where('transaction_code', $transfer->code)->count());
    }

    private function hazirKullaniciOrtami(): array
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant-' . uniqid(),
            'status' => 'active',
        ]);

        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Merkez Şube',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
        ]);

        return [$tenant, $branch, $user];
    }

    private function urunOlustur(int $tenantId, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'tenant_id' => $tenantId,
            'name' => 'Test Ürün',
            'sale_price' => 100,
            'purchase_price' => 50,
            'vat_rate' => 10,
            'stock_quantity' => 0,
            'critical_stock' => 0,
            'unit' => 'Adet',
            'show_on_pos' => true,
            'is_active' => true,
            'is_service' => false,
        ], $attributes));
    }
}
