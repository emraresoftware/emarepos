<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Firm;
use App\Models\Product;
use App\Models\PurchaseInvoice;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoiceAndStockCountFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_invoice_store_increases_branch_stock_and_updates_firm_balance(): void
    {
        [$tenant, $branch, $user] = $this->hazirKullaniciOrtami();
        $firm = $this->firmaOlustur($tenant->id);
        $product = $this->urunOlustur($tenant->id, [
            'name' => 'Süt',
            'purchase_price' => 12,
            'sale_price' => 25,
        ]);
        $product->setStockForBranch($branch->id, 1);

        $response = $this->actingAs($user)
            ->postJson(route('pos.purchase-invoices.store'), [
                'firm_id' => $firm->id,
                'invoice_no' => 'ALIŞ-001',
                'invoice_date' => '2026-03-10',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 4,
                    'unit_price' => 15,
                    'vat_rate' => 10,
                    'discount' => 0,
                ]],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $invoice = PurchaseInvoice::with('items')->firstOrFail();
        $product->refresh();
        $firm->refresh();

        $this->assertSame('received', $invoice->status);
        $this->assertSame(5.0, $product->stockForBranch($branch->id));
        $this->assertSame(5.0, (float) $product->stock_quantity);
        $this->assertSame('15.00', $product->purchase_price);
        $this->assertSame('-66.00', $firm->balance);
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'purchase',
            'quantity' => 4,
        ]);
    }

    public function test_purchase_invoice_cancel_restores_stock_and_firm_balance(): void
    {
        [$tenant, $branch, $user] = $this->hazirKullaniciOrtami();
        $firm = $this->firmaOlustur($tenant->id);
        $product = $this->urunOlustur($tenant->id, [
            'name' => 'Yoğurt',
            'purchase_price' => 20,
            'sale_price' => 35,
        ]);

        $storeResponse = $this->actingAs($user)
            ->postJson(route('pos.purchase-invoices.store'), [
                'firm_id' => $firm->id,
                'invoice_no' => 'ALIŞ-002',
                'invoice_date' => '2026-03-10',
                'items' => [[
                    'product_id' => $product->id,
                    'quantity' => 3,
                    'unit_price' => 18,
                    'vat_rate' => 0,
                    'discount' => 0,
                ]],
            ]);

        $storeResponse->assertOk();
        $invoice = PurchaseInvoice::firstOrFail();

        $cancelResponse = $this->actingAs($user)
            ->putJson(route('pos.purchase-invoices.update', $invoice), [
                'invoice_date' => '2026-03-10',
                'status' => 'cancelled',
            ]);

        $cancelResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $invoice->refresh();
        $product->refresh();
        $firm->refresh();

        $this->assertSame('cancelled', $invoice->status);
        $this->assertSame(0.0, $product->stockForBranch($branch->id));
        $this->assertSame(0.0, (float) $product->stock_quantity);
        $this->assertSame('0.00', $firm->balance);
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'purchase_return',
            'quantity' => -3,
        ]);
    }

    public function test_stock_count_apply_sets_branch_stock_and_creates_adjustment(): void
    {
        [$tenant, $branch, $user] = $this->hazirKullaniciOrtami();
        $product = $this->urunOlustur($tenant->id, [
            'name' => 'Maden Suyu',
            'purchase_price' => 6,
            'sale_price' => 18,
        ]);
        $product->setStockForBranch($branch->id, 9);

        $storeResponse = $this->actingAs($user)
            ->postJson(route('pos.stock-count.store'), [
                'title' => 'Mart Sayımı',
                'items' => [[
                    'product_id' => $product->id,
                    'counted_quantity' => 12,
                    'note' => 'Fazla ürün bulundu',
                ]],
            ]);

        $storeResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $count = StockCount::firstOrFail();

        $applyResponse = $this->actingAs($user)
            ->postJson(route('pos.stock-count.apply', $count));

        $applyResponse
            ->assertOk()
            ->assertJsonPath('success', true);

        $count->refresh();
        $product->refresh();

        $this->assertSame('applied', $count->status);
        $this->assertSame(12.0, $product->stockForBranch($branch->id));
        $this->assertSame(12.0, (float) $product->stock_quantity);
        $this->assertDatabaseHas('stock_movements', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'type' => 'adjustment',
            'quantity' => 3,
        ]);
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

    private function firmaOlustur(int $tenantId): Firm
    {
        return Firm::create([
            'tenant_id' => $tenantId,
            'name' => 'Test Tedarikçi',
            'balance' => 0,
            'is_active' => true,
        ]);
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
