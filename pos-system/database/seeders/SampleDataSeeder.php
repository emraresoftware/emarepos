<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\TableRegion;
use App\Models\User;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'adem@adem.com')->first();
        if (!$user) {
            $this->command->error('adem@adem.com kullanıcısı bulunamadı!');
            return;
        }

        $tenantId = $user->tenant_id;
        $branchId = $user->branch_id;
        $this->command->info("Tenant: {$tenantId}, Branch: {$branchId}");

        // ── 1. KATEGORİLER ──────────────────────────────────────────────
        $this->command->info('Kategoriler oluşturuluyor...');
        $categories = [];
        $catData = [
            ['name' => 'Ana Yemekler',     'sort_order' => 1],
            ['name' => 'Çorbalar',         'sort_order' => 2],
            ['name' => 'Salatalar',        'sort_order' => 3],
            ['name' => 'Pizzalar',         'sort_order' => 4],
            ['name' => 'Burgerler',        'sort_order' => 5],
            ['name' => 'Atıştırmalıklar',  'sort_order' => 6],
            ['name' => 'Tatlılar',         'sort_order' => 7],
            ['name' => 'Sıcak İçecekler',  'sort_order' => 8],
            ['name' => 'Soğuk İçecekler',  'sort_order' => 9],
            ['name' => 'Alkollü İçecekler','sort_order' => 10],
        ];
        foreach ($catData as $cat) {
            $categories[$cat['name']] = Category::firstOrCreate(
                ['tenant_id' => $tenantId, 'name' => $cat['name']],
                ['sort_order' => $cat['sort_order'], 'is_active' => true]
            );
        }
        $this->command->info(count($categories) . ' kategori hazır.');

        // ── 2. ÜRÜNLER ──────────────────────────────────────────────────
        $this->command->info('Ürünler oluşturuluyor...');
        $products = [
            // Ana Yemekler
            ['cat' => 'Ana Yemekler', 'name' => 'Izgara Köfte',           'barcode' => 'AY001', 'purchase_price' => 45,  'sale_price' => 95,  'vat_rate' => 10, 'stock' => 100],
            ['cat' => 'Ana Yemekler', 'name' => 'Tavuk Şiş',              'barcode' => 'AY002', 'purchase_price' => 40,  'sale_price' => 85,  'vat_rate' => 10, 'stock' => 100],
            ['cat' => 'Ana Yemekler', 'name' => 'Karışık Izgara',         'barcode' => 'AY003', 'purchase_price' => 80,  'sale_price' => 165, 'vat_rate' => 10, 'stock' => 50],
            ['cat' => 'Ana Yemekler', 'name' => 'Adana Kebap',            'barcode' => 'AY004', 'purchase_price' => 55,  'sale_price' => 110, 'vat_rate' => 10, 'stock' => 60],
            ['cat' => 'Ana Yemekler', 'name' => 'Pide (Kıymalı)',         'barcode' => 'AY005', 'purchase_price' => 30,  'sale_price' => 65,  'vat_rate' => 10, 'stock' => 80],
            ['cat' => 'Ana Yemekler', 'name' => 'Lahmacun',               'barcode' => 'AY006', 'purchase_price' => 15,  'sale_price' => 35,  'vat_rate' => 10, 'stock' => 120],
            // Çorbalar
            ['cat' => 'Çorbalar',     'name' => 'Mercimek Çorbası',       'barcode' => 'CO001', 'purchase_price' => 8,   'sale_price' => 25,  'vat_rate' => 10, 'stock' => 200],
            ['cat' => 'Çorbalar',     'name' => 'Ezogelin Çorbası',       'barcode' => 'CO002', 'purchase_price' => 8,   'sale_price' => 25,  'vat_rate' => 10, 'stock' => 200],
            ['cat' => 'Çorbalar',     'name' => 'Domates Çorbası',        'barcode' => 'CO003', 'purchase_price' => 7,   'sale_price' => 22,  'vat_rate' => 10, 'stock' => 150],
            ['cat' => 'Çorbalar',     'name' => 'İşkembe Çorbası',        'barcode' => 'CO004', 'purchase_price' => 12,  'sale_price' => 35,  'vat_rate' => 10, 'stock' => 100],
            // Salatalar
            ['cat' => 'Salatalar',    'name' => 'Çoban Salatası',         'barcode' => 'SA001', 'purchase_price' => 10,  'sale_price' => 35,  'vat_rate' => 10, 'stock' => 100],
            ['cat' => 'Salatalar',    'name' => 'Mevsim Salatası',        'barcode' => 'SA002', 'purchase_price' => 12,  'sale_price' => 40,  'vat_rate' => 10, 'stock' => 100],
            ['cat' => 'Salatalar',    'name' => 'Sezar Salatası',         'barcode' => 'SA003', 'purchase_price' => 18,  'sale_price' => 55,  'vat_rate' => 10, 'stock' => 80],
            // Pizzalar
            ['cat' => 'Pizzalar',     'name' => 'Margarita Pizza',        'barcode' => 'PZ001', 'purchase_price' => 35,  'sale_price' => 80,  'vat_rate' => 10, 'stock' => 50],
            ['cat' => 'Pizzalar',     'name' => 'Karışık Pizza',          'barcode' => 'PZ002', 'purchase_price' => 45,  'sale_price' => 105, 'vat_rate' => 10, 'stock' => 50],
            ['cat' => 'Pizzalar',     'name' => 'Sucuklu Pizza',          'barcode' => 'PZ003', 'purchase_price' => 40,  'sale_price' => 90,  'vat_rate' => 10, 'stock' => 50],
            ['cat' => 'Pizzalar',     'name' => 'Vejetaryen Pizza',       'barcode' => 'PZ004', 'purchase_price' => 35,  'sale_price' => 80,  'vat_rate' => 10, 'stock' => 50],
            // Burgerler
            ['cat' => 'Burgerler',    'name' => 'Classic Burger',         'barcode' => 'BU001', 'purchase_price' => 38,  'sale_price' => 85,  'vat_rate' => 10, 'stock' => 60],
            ['cat' => 'Burgerler',    'name' => 'Cheese Burger',          'barcode' => 'BU002', 'purchase_price' => 42,  'sale_price' => 95,  'vat_rate' => 10, 'stock' => 60],
            ['cat' => 'Burgerler',    'name' => 'Double Burger',          'barcode' => 'BU003', 'purchase_price' => 58,  'sale_price' => 125, 'vat_rate' => 10, 'stock' => 40],
            ['cat' => 'Burgerler',    'name' => 'Tavuk Burger',           'barcode' => 'BU004', 'purchase_price' => 35,  'sale_price' => 75,  'vat_rate' => 10, 'stock' => 60],
            // Atıştırmalıklar
            ['cat' => 'Atıştırmalıklar','name' => 'Patates Kızartması',  'barcode' => 'AT001', 'purchase_price' => 12,  'sale_price' => 35,  'vat_rate' => 10, 'stock' => 150],
            ['cat' => 'Atıştırmalıklar','name' => 'Mozzarella Stick',    'barcode' => 'AT002', 'purchase_price' => 18,  'sale_price' => 50,  'vat_rate' => 10, 'stock' => 100],
            ['cat' => 'Atıştırmalıklar','name' => 'Soğan Halkası',       'barcode' => 'AT003', 'purchase_price' => 10,  'sale_price' => 30,  'vat_rate' => 10, 'stock' => 100],
            ['cat' => 'Atıştırmalıklar','name' => 'Nachos',              'barcode' => 'AT004', 'purchase_price' => 15,  'sale_price' => 45,  'vat_rate' => 10, 'stock' => 80],
            // Tatlılar
            ['cat' => 'Tatlılar',     'name' => 'Künefe',                 'barcode' => 'TA001', 'purchase_price' => 20,  'sale_price' => 55,  'vat_rate' => 10, 'stock' => 40],
            ['cat' => 'Tatlılar',     'name' => 'Sütlaç',                 'barcode' => 'TA002', 'purchase_price' => 12,  'sale_price' => 35,  'vat_rate' => 10, 'stock' => 60],
            ['cat' => 'Tatlılar',     'name' => 'Tiramisu',               'barcode' => 'TA003', 'purchase_price' => 18,  'sale_price' => 50,  'vat_rate' => 10, 'stock' => 40],
            ['cat' => 'Tatlılar',     'name' => 'Baklava (Porsiyon)',     'barcode' => 'TA004', 'purchase_price' => 15,  'sale_price' => 45,  'vat_rate' => 10, 'stock' => 50],
            // Sıcak İçecekler
            ['cat' => 'Sıcak İçecekler','name' => 'Türk Kahvesi',        'barcode' => 'SI001', 'purchase_price' => 5,   'sale_price' => 25,  'vat_rate' => 10, 'stock' => 999, 'is_service' => true],
            ['cat' => 'Sıcak İçecekler','name' => 'Espresso',            'barcode' => 'SI002', 'purchase_price' => 6,   'sale_price' => 25,  'vat_rate' => 10, 'stock' => 999, 'is_service' => true],
            ['cat' => 'Sıcak İçecekler','name' => 'Cappuccino',          'barcode' => 'SI003', 'purchase_price' => 8,   'sale_price' => 35,  'vat_rate' => 10, 'stock' => 999, 'is_service' => true],
            ['cat' => 'Sıcak İçecekler','name' => 'Latte',               'barcode' => 'SI004', 'purchase_price' => 9,   'sale_price' => 40,  'vat_rate' => 10, 'stock' => 999, 'is_service' => true],
            ['cat' => 'Sıcak İçecekler','name' => 'Çay',                 'barcode' => 'SI005', 'purchase_price' => 2,   'sale_price' => 10,  'vat_rate' => 10, 'stock' => 999, 'is_service' => true],
            ['cat' => 'Sıcak İçecekler','name' => 'Bitki Çayı',          'barcode' => 'SI006', 'purchase_price' => 4,   'sale_price' => 20,  'vat_rate' => 10, 'stock' => 999, 'is_service' => true],
            // Soğuk İçecekler
            ['cat' => 'Soğuk İçecekler','name' => 'Kola (330ml)',        'barcode' => 'SO001', 'purchase_price' => 8,   'sale_price' => 25,  'vat_rate' => 10, 'stock' => 200],
            ['cat' => 'Soğuk İçecekler','name' => 'Ayran',               'barcode' => 'SO002', 'purchase_price' => 4,   'sale_price' => 15,  'vat_rate' => 10, 'stock' => 300],
            ['cat' => 'Soğuk İçecekler','name' => 'Maden Suyu',          'barcode' => 'SO003', 'purchase_price' => 5,   'sale_price' => 15,  'vat_rate' => 10, 'stock' => 200],
            ['cat' => 'Soğuk İçecekler','name' => 'Taze Sıkılmış OJ',   'barcode' => 'SO004', 'purchase_price' => 12,  'sale_price' => 35,  'vat_rate' => 10, 'stock' => 100, 'is_service' => true],
            ['cat' => 'Soğuk İçecekler','name' => 'Limonata',            'barcode' => 'SO005', 'purchase_price' => 10,  'sale_price' => 30,  'vat_rate' => 10, 'stock' => 100, 'is_service' => true],
            ['cat' => 'Soğuk İçecekler','name' => 'Şişe Su (0.5L)',      'barcode' => 'SO006', 'purchase_price' => 2,   'sale_price' => 8,   'vat_rate' => 10, 'stock' => 500],
            // Alkollü İçecekler
            ['cat' => 'Alkollü İçecekler','name' => 'Bira (330ml)',      'barcode' => 'AL001', 'purchase_price' => 18,  'sale_price' => 50,  'vat_rate' => 20, 'stock' => 150],
            ['cat' => 'Alkollü İçecekler','name' => 'Beyaz Şarap (Kadeh)','barcode' => 'AL002','purchase_price' => 25,  'sale_price' => 75,  'vat_rate' => 20, 'stock' => 100],
            ['cat' => 'Alkollü İçecekler','name' => 'Kırmızı Şarap (Kadeh)','barcode' => 'AL003','purchase_price' => 28,'sale_price' => 80,  'vat_rate' => 20, 'stock' => 100],
            ['cat' => 'Alkollü İçecekler','name' => 'Rakı (Tek)',        'barcode' => 'AL004', 'purchase_price' => 30,  'sale_price' => 90,  'vat_rate' => 20, 'stock' => 80],
        ];

        foreach ($products as $p) {
            $catObj = $categories[$p['cat']] ?? null;
            if (!$catObj) continue;
            Product::firstOrCreate(
                ['tenant_id' => $tenantId, 'barcode' => $p['barcode']],
                [
                    'name'           => $p['name'],
                    'category_id'    => $catObj->id,
                    'purchase_price' => $p['purchase_price'],
                    'sale_price'     => $p['sale_price'],
                    'vat_rate'       => $p['vat_rate'],
                    'stock_quantity' => $p['stock'],
                    'unit'           => 'adet',
                    'is_active'      => true,
                    'is_service'     => $p['is_service'] ?? false,
                ]
            );
        }
        $this->command->info(count($products) . ' ürün hazır.');

        // ── 3. MASA BÖLGELERİ & MASALAR ────────────────────────────────
        $this->command->info('Masa bölgeleri ve masalar oluşturuluyor...');

        $salon = TableRegion::firstOrCreate(
            ['tenant_id' => $tenantId, 'branch_id' => $branchId, 'name' => 'Salon'],
            ['sort_order' => 1, 'is_active' => true, 'bg_color' => '#eff6ff', 'icon' => 'fa-utensils']
        );
        $bahce = TableRegion::firstOrCreate(
            ['tenant_id' => $tenantId, 'branch_id' => $branchId, 'name' => 'Bahçe'],
            ['sort_order' => 2, 'is_active' => true, 'bg_color' => '#f0fdf4', 'icon' => 'fa-tree']
        );
        $teras = TableRegion::firstOrCreate(
            ['tenant_id' => $tenantId, 'branch_id' => $branchId, 'name' => 'Teras'],
            ['sort_order' => 3, 'is_active' => true, 'bg_color' => '#fefce8', 'icon' => 'fa-sun']
        );

        // Salon masaları — 10 masa, 4 sıra 2'li grid
        $salonLayout = [
            ['no'=>'S1','name'=>'Salon 1','cap'=>4,'x'=>5,'y'=>5],
            ['no'=>'S2','name'=>'Salon 2','cap'=>4,'x'=>25,'y'=>5],
            ['no'=>'S3','name'=>'Salon 3','cap'=>2,'x'=>45,'y'=>5],
            ['no'=>'S4','name'=>'Salon 4','cap'=>6,'x'=>65,'y'=>5],
            ['no'=>'S5','name'=>'Salon 5','cap'=>4,'x'=>5,'y'=>30],
            ['no'=>'S6','name'=>'Salon 6','cap'=>4,'x'=>25,'y'=>30],
            ['no'=>'S7','name'=>'Salon 7','cap'=>8,'x'=>45,'y'=>30],
            ['no'=>'S8','name'=>'Salon 8','cap'=>4,'x'=>65,'y'=>30],
            ['no'=>'S9','name'=>'Salon 9','cap'=>2,'x'=>5,'y'=>55],
            ['no'=>'S10','name'=>'Salon 10','cap'=>4,'x'=>25,'y'=>55],
        ];
        foreach ($salonLayout as $i => $t) {
            RestaurantTable::firstOrCreate(
                ['tenant_id' => $tenantId, 'branch_id' => $branchId, 'table_no' => $t['no']],
                [
                    'table_region_id' => $salon->id,
                    'name'       => $t['name'],
                    'capacity'   => $t['cap'],
                    'status'     => 'empty',
                    'sort_order' => $i + 1,
                    'is_active'  => true,
                    'pos_x'      => $t['x'],
                    'pos_y'      => $t['y'],
                    'shape'      => 'square',
                ]
            );
        }

        // Bahçe masaları — 6 masa
        $bahceLayout = [
            ['no'=>'B1','name'=>'Bahçe 1','cap'=>4,'x'=>5,'y'=>5],
            ['no'=>'B2','name'=>'Bahçe 2','cap'=>4,'x'=>25,'y'=>5],
            ['no'=>'B3','name'=>'Bahçe 3','cap'=>6,'x'=>45,'y'=>5],
            ['no'=>'B4','name'=>'Bahçe 4','cap'=>4,'x'=>5,'y'=>30],
            ['no'=>'B5','name'=>'Bahçe 5','cap'=>4,'x'=>25,'y'=>30],
            ['no'=>'B6','name'=>'Bahçe 6','cap'=>8,'x'=>45,'y'=>30],
        ];
        foreach ($bahceLayout as $i => $t) {
            RestaurantTable::firstOrCreate(
                ['tenant_id' => $tenantId, 'branch_id' => $branchId, 'table_no' => $t['no']],
                [
                    'table_region_id' => $bahce->id,
                    'name'       => $t['name'],
                    'capacity'   => $t['cap'],
                    'status'     => 'empty',
                    'sort_order' => $i + 11,
                    'is_active'  => true,
                    'pos_x'      => $t['x'],
                    'pos_y'      => $t['y'],
                    'shape'      => 'circle',
                ]
            );
        }

        // Teras masaları — 4 VIP masa
        $terasLayout = [
            ['no'=>'T1','name'=>'Teras VIP 1','cap'=>6,'x'=>10,'y'=>10,'shape'=>'square'],
            ['no'=>'T2','name'=>'Teras VIP 2','cap'=>6,'x'=>40,'y'=>10,'shape'=>'square'],
            ['no'=>'T3','name'=>'Teras Bar 1','cap'=>2,'x'=>10,'y'=>40,'shape'=>'circle'],
            ['no'=>'T4','name'=>'Teras Bar 2','cap'=>2,'x'=>40,'y'=>40,'shape'=>'circle'],
        ];
        foreach ($terasLayout as $i => $t) {
            RestaurantTable::firstOrCreate(
                ['tenant_id' => $tenantId, 'branch_id' => $branchId, 'table_no' => $t['no']],
                [
                    'table_region_id' => $teras->id,
                    'name'       => $t['name'],
                    'capacity'   => $t['cap'],
                    'status'     => 'empty',
                    'sort_order' => $i + 17,
                    'is_active'  => true,
                    'pos_x'      => $t['x'],
                    'pos_y'      => $t['y'],
                    'shape'      => $t['shape'],
                ]
            );
        }
        $this->command->info('3 bölge, 20 masa hazır.');

        // ── 4. MÜŞTERİLER ───────────────────────────────────────────────
        $this->command->info('Müşteriler oluşturuluyor...');
        $customers = [
            ['name' => 'Mehmet Yılmaz',   'phone' => '0532 111 22 33', 'email' => 'mehmet@example.com', 'type' => 'individual', 'balance' => 0,     'notes' => 'Düzenli müşteri'],
            ['name' => 'Ayşe Kaya',        'phone' => '0533 222 33 44', 'email' => 'ayse@example.com',   'type' => 'individual', 'balance' => 150,   'notes' => 'VIP müşteri'],
            ['name' => 'Ali Demir',         'phone' => '0534 333 44 55', 'email' => 'ali@example.com',    'type' => 'individual', 'balance' => -200,  'notes' => 'Veresiye bakiye var'],
            ['name' => 'Fatma Şahin',       'phone' => '0535 444 55 66', 'email' => 'fatma@example.com',  'type' => 'individual', 'balance' => 0,     'notes' => ''],
            ['name' => 'ABC Şirketi',       'phone' => '0212 555 66 77', 'email' => 'info@abc.com',       'type' => 'company',    'balance' => 500,   'notes' => 'Kurumsal hesap'],
            ['name' => 'Hasan Öztürk',     'phone' => '0536 666 77 88', 'email' => '',                   'type' => 'individual', 'balance' => 0,     'notes' => ''],
            ['name' => 'Zeynep Çelik',     'phone' => '0537 777 88 99', 'email' => 'zeynep@example.com', 'type' => 'individual', 'balance' => 75,    'notes' => 'Sadakat programı üyesi'],
        ];
        foreach ($customers as $c) {
            Customer::firstOrCreate(
                ['tenant_id' => $tenantId, 'phone' => $c['phone']],
                [
                    'name'      => $c['name'],
                    'email'     => $c['email'] ?: null,
                    'type'      => $c['type'],
                    'balance'   => $c['balance'],
                    'notes'     => $c['notes'],
                    'is_active' => true,
                ]
            );
        }
        $this->command->info(count($customers) . ' müşteri hazır.');

        $this->command->info('');
        $this->command->info('✅ Tüm örnek veriler başarıyla oluşturuldu!');
        $this->command->info('   - ' . count($catData) . ' kategori');
        $this->command->info('   - ' . count($products) . ' ürün');
        $this->command->info('   - 3 masa bölgesi (Salon, Bahçe, Teras) / 20 masa');
        $this->command->info('   - ' . count($customers) . ' müşteri');
    }
}
