<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductSubDefinition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductBranchStockFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_store_creates_branch_stock_for_active_branch(): void
    {
        [$tenant, $branch, $user] = $this->hazirKullaniciOrtami();

        $response = $this->actingAs($user)
            ->postJson(route('pos.products.store'), [
                'name' => 'Deneme Ürün',
                'sale_price' => 125,
                'purchase_price' => 80,
                'vat_rate' => 10,
                'stock_quantity' => 7,
                'critical_stock' => 2,
                'unit' => 'Adet',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $product = Product::firstOrFail();
        $product->refresh();

        $this->assertSame(7.0, $product->stockForBranch($branch->id));
        $this->assertSame(7.0, (float) $product->stock_quantity);
        $this->assertDatabaseHas('branch_product', [
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'stock_quantity' => 7.0,
        ]);
    }

    public function test_product_store_defaults_critical_stock_to_zero_when_missing(): void
    {
        [$tenant, $branch, $user] = $this->hazirKullaniciOrtami();

        $response = $this->actingAs($user)
            ->postJson(route('pos.products.store'), [
                'name' => 'Kritik Stoksuz Ürün',
                'sale_price' => 99,
                'purchase_price' => 45,
                'vat_rate' => 10,
                'stock_quantity' => 2,
                'unit' => 'Adet',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $product = Product::where('name', 'Kritik Stoksuz Ürün')->firstOrFail();

        $this->assertSame('0.00', $product->critical_stock);
        $this->assertSame(2.0, $product->stockForBranch($branch->id));
    }

    public function test_product_update_changes_only_current_branch_stock_and_keeps_total_synced(): void
    {
        [$tenant, $branchA, $user] = $this->hazirKullaniciOrtami();
        $branchB = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Şube B',
            'is_active' => true,
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Çok Şubeli Ürün',
            'sale_price' => 200,
            'purchase_price' => 110,
            'vat_rate' => 10,
            'stock_quantity' => 0,
            'critical_stock' => 1,
            'unit' => 'Adet',
            'show_on_pos' => true,
            'is_active' => true,
        ]);

        $product->branches()->attach($branchA->id, ['stock_quantity' => 5, 'sale_price' => 200]);
        $product->branches()->attach($branchB->id, ['stock_quantity' => 11, 'sale_price' => 200]);
        $product->syncStockQuantityFromBranches();

        $response = $this->actingAs($user)
            ->putJson(route('pos.products.update', $product), [
                'name' => 'Çok Şubeli Ürün Güncel',
                'sale_price' => 210,
                'purchase_price' => 115,
                'vat_rate' => 10,
                'stock_quantity' => 9,
                'critical_stock' => 1,
                'unit' => 'Adet',
                'show_on_pos' => true,
                'is_service' => false,
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $product->refresh();

        $this->assertSame(9.0, $product->stockForBranch($branchA->id));
        $this->assertSame(11.0, $product->stockForBranch($branchB->id));
        $this->assertSame(20.0, (float) $product->stock_quantity);
        $this->assertDatabaseHas('branch_product', [
            'branch_id' => $branchA->id,
            'product_id' => $product->id,
            'stock_quantity' => 9.0,
        ]);
        $this->assertDatabaseHas('branch_product', [
            'branch_id' => $branchB->id,
            'product_id' => $product->id,
            'stock_quantity' => 11.0,
        ]);
    }

    public function test_sub_definition_response_returns_branch_specific_sub_product_stock(): void
    {
        [$tenant, $branchA, $user] = $this->hazirKullaniciOrtami();
        $branchB = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Şube B',
            'is_active' => true,
        ]);

        $parentProduct = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Koli Ürün',
            'sale_price' => 500,
            'purchase_price' => 300,
            'vat_rate' => 10,
            'stock_quantity' => 0,
            'critical_stock' => 0,
            'unit' => 'Koli',
            'show_on_pos' => true,
            'is_active' => true,
        ]);

        $subProduct = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Adet Ürün',
            'sale_price' => 50,
            'purchase_price' => 30,
            'vat_rate' => 10,
            'stock_quantity' => 0,
            'critical_stock' => 0,
            'unit' => 'Adet',
            'show_on_pos' => true,
            'is_active' => true,
        ]);

        $subProduct->branches()->attach($branchA->id, ['stock_quantity' => 4, 'sale_price' => 50]);
        $subProduct->branches()->attach($branchB->id, ['stock_quantity' => 10, 'sale_price' => 50]);
        $subProduct->syncStockQuantityFromBranches();

        ProductSubDefinition::create([
            'tenant_id' => $tenant->id,
            'parent_product_id' => $parentProduct->id,
            'sub_product_id' => $subProduct->id,
            'multiplier' => 12,
            'apply_to_branches' => true,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('pos.products.sub-definitions', $parentProduct));

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('definitions.0.sub_product.stock_quantity', '4.00');
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
}
