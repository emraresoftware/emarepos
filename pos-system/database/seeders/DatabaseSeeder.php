<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Module;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Branch;
use App\Models\User;
use App\Models\TaxRate;
use App\Models\PaymentType;
use App\Models\Category;
use App\Models\Product;
use App\Models\TableRegion;
use App\Models\RestaurantTable;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CashRegister;
use App\Models\Firm;
use App\Models\StockMovement;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. Plans ────────────────────────────────────────────
        $starterPlan = Plan::create([
            'code' => 'starter',
            'name' => 'Başlangıç',
            'description' => 'Küçük işletmeler için temel POS paketi',
            'price_monthly' => 499,
            'price_yearly' => 4990,
            'is_active' => true,
            'limits' => ['max_branches' => 1, 'max_users' => 3, 'max_products' => 500],
            'sort_order' => 1,
        ]);

        $professionalPlan = Plan::create([
            'code' => 'professional',
            'name' => 'Profesyonel',
            'description' => 'Orta ölçekli işletmeler için gelişmiş paket',
            'price_monthly' => 999,
            'price_yearly' => 9990,
            'is_active' => true,
            'limits' => ['max_branches' => 5, 'max_users' => 15, 'max_products' => 5000],
            'sort_order' => 2,
        ]);

        $enterprisePlan = Plan::create([
            'code' => 'enterprise',
            'name' => 'Kurumsal',
            'description' => 'Büyük işletmeler ve zincir restoranlar için',
            'price_monthly' => 1999,
            'price_yearly' => 19990,
            'is_active' => true,
            'limits' => ['max_branches' => 999, 'max_users' => 999, 'max_products' => 999999],
            'sort_order' => 3,
        ]);

        // ─── 2. Modules ─────────────────────────────────────────
        $modulesData = [
            ['code' => 'core_pos',          'name' => 'Temel POS',              'description' => 'Satış, ürün, müşteri, stok ve temel raporlar', 'is_core' => true,  'scope' => 'tenant', 'sort_order' => 1],
            ['code' => 'hardware',          'name' => 'Donanım Sürücüleri',     'description' => 'Yazıcı, barkod okuyucu, terazi entegrasyonu',   'is_core' => false, 'scope' => 'branch', 'sort_order' => 2],
            ['code' => 'einvoice',          'name' => 'E-Fatura / E-Arşiv',     'description' => 'Elektronik fatura ve arşiv yönetimi',           'is_core' => false, 'scope' => 'tenant', 'sort_order' => 3],
            ['code' => 'income_expense',    'name' => 'Gelir-Gider',            'description' => 'Gelir ve gider takibi',                         'is_core' => false, 'scope' => 'tenant', 'sort_order' => 4],
            ['code' => 'staff',             'name' => 'Personel Yönetimi',      'description' => 'Personel takibi ve vardiya yönetimi',            'is_core' => false, 'scope' => 'both',   'sort_order' => 5],
            ['code' => 'advanced_reports',  'name' => 'Gelişmiş Raporlar',      'description' => 'Detaylı analiz ve raporlama',                   'is_core' => false, 'scope' => 'tenant', 'sort_order' => 6],
            ['code' => 'api_access',        'name' => 'API Erişimi',            'description' => 'Üçüncü parti entegrasyonlar için API',           'is_core' => false, 'scope' => 'tenant', 'sort_order' => 7],
            ['code' => 'mobile_premium',    'name' => 'Mobil Premium',          'description' => 'Gelişmiş mobil uygulama özellikleri',            'is_core' => false, 'scope' => 'tenant', 'sort_order' => 8],
            ['code' => 'marketing',         'name' => 'Pazarlama',              'description' => 'Kampanya ve sadakat programı yönetimi',          'is_core' => false, 'scope' => 'tenant', 'sort_order' => 9],
            ['code' => 'sms',               'name' => 'SMS Yönetimi',           'description' => 'Toplu ve bireysel SMS gönderimi',                'is_core' => false, 'scope' => 'tenant', 'sort_order' => 10],
        ];

        $modules = [];
        foreach ($modulesData as $m) {
            $modules[$m['code']] = Module::create($m);
        }

        // ─── 3. Plan ↔ Module Assignments ────────────────────────
        // Starter: core_pos only
        $starterPlan->modules()->attach($modules['core_pos']->id, ['included' => true]);

        // Professional: core_pos + hardware + income_expense + staff + advanced_reports
        $professionalModules = ['core_pos', 'hardware', 'income_expense', 'staff', 'advanced_reports'];
        foreach ($professionalModules as $code) {
            $professionalPlan->modules()->attach($modules[$code]->id, ['included' => true]);
        }

        // Enterprise: ALL modules
        foreach ($modules as $module) {
            $enterprisePlan->modules()->attach($module->id, ['included' => true]);
        }

        // ─── 4. Roles ───────────────────────────────────────────
        $rolesData = [
            ['code' => 'admin',      'name' => 'Yönetici',          'description' => 'Tüm izinlere sahip',    'scope' => 'tenant', 'is_system' => true],
            ['code' => 'manager',    'name' => 'Şube Müdürü',       'description' => 'Şube bazlı yönetim',    'scope' => 'branch', 'is_system' => true],
            ['code' => 'cashier',    'name' => 'Kasiyer',           'description' => 'Satış odaklı',           'scope' => 'branch', 'is_system' => true],
            ['code' => 'accounting', 'name' => 'Muhasebe',          'description' => 'Finans odaklı',          'scope' => 'tenant', 'is_system' => true],
            ['code' => 'warehouse',  'name' => 'Depo Sorumlusu',    'description' => 'Stok odaklı',            'scope' => 'branch', 'is_system' => true],
        ];

        $roles = [];
        foreach ($rolesData as $r) {
            $roles[$r['code']] = Role::create($r);
        }

        // ─── 5. Permissions (41) ────────────────────────────────
        $permissionsData = [
            // Satış (5)
            ['code' => 'sales.view',     'name' => 'Satışları Görüntüle',  'module_code' => 'core_pos', 'group' => 'Satış'],
            ['code' => 'sales.create',   'name' => 'Satış Oluştur',        'module_code' => 'core_pos', 'group' => 'Satış'],
            ['code' => 'sales.cancel',   'name' => 'Satış İptal Et',       'module_code' => 'core_pos', 'group' => 'Satış'],
            ['code' => 'sales.refund',   'name' => 'İade İşlemi',          'module_code' => 'core_pos', 'group' => 'Satış'],
            ['code' => 'sales.discount', 'name' => 'İndirim Uygula',       'module_code' => 'core_pos', 'group' => 'Satış'],
            // Ürün (4)
            ['code' => 'products.view',   'name' => 'Ürünleri Görüntüle', 'module_code' => 'core_pos', 'group' => 'Ürün'],
            ['code' => 'products.create', 'name' => 'Ürün Ekle',          'module_code' => 'core_pos', 'group' => 'Ürün'],
            ['code' => 'products.edit',   'name' => 'Ürün Düzenle',       'module_code' => 'core_pos', 'group' => 'Ürün'],
            ['code' => 'products.delete', 'name' => 'Ürün Sil',           'module_code' => 'core_pos', 'group' => 'Ürün'],
            // Müşteri (4)
            ['code' => 'customers.view',   'name' => 'Müşterileri Görüntüle', 'module_code' => 'core_pos', 'group' => 'Müşteri'],
            ['code' => 'customers.create', 'name' => 'Müşteri Ekle',          'module_code' => 'core_pos', 'group' => 'Müşteri'],
            ['code' => 'customers.edit',   'name' => 'Müşteri Düzenle',       'module_code' => 'core_pos', 'group' => 'Müşteri'],
            ['code' => 'customers.delete', 'name' => 'Müşteri Sil',           'module_code' => 'core_pos', 'group' => 'Müşteri'],
            // Stok (4)
            ['code' => 'stock.view',     'name' => 'Stok Görüntüle',  'module_code' => 'core_pos', 'group' => 'Stok'],
            ['code' => 'stock.adjust',   'name' => 'Stok Düzenleme',  'module_code' => 'core_pos', 'group' => 'Stok'],
            ['code' => 'stock.count',    'name' => 'Sayım Yap',       'module_code' => 'core_pos', 'group' => 'Stok'],
            ['code' => 'stock.transfer', 'name' => 'Stok Transfer',   'module_code' => 'core_pos', 'group' => 'Stok'],
            // Rapor (3)
            ['code' => 'reports.basic',    'name' => 'Temel Raporlar',      'module_code' => 'core_pos',         'group' => 'Rapor'],
            ['code' => 'reports.advanced', 'name' => 'Gelişmiş Raporlar',   'module_code' => 'advanced_reports', 'group' => 'Rapor'],
            ['code' => 'reports.export',   'name' => 'Rapor Dışa Aktar',    'module_code' => 'core_pos',         'group' => 'Rapor'],
            // Gelir-Gider (4)
            ['code' => 'income.view',   'name' => 'Gelir Görüntüle',  'module_code' => 'income_expense', 'group' => 'Gelir-Gider'],
            ['code' => 'income.create', 'name' => 'Gelir Ekle',       'module_code' => 'income_expense', 'group' => 'Gelir-Gider'],
            ['code' => 'expense.view',  'name' => 'Gider Görüntüle',  'module_code' => 'income_expense', 'group' => 'Gelir-Gider'],
            ['code' => 'expense.create','name' => 'Gider Ekle',       'module_code' => 'income_expense', 'group' => 'Gelir-Gider'],
            // E-Fatura (3)
            ['code' => 'einvoice.view',   'name' => 'E-Fatura Görüntüle', 'module_code' => 'einvoice', 'group' => 'E-Fatura'],
            ['code' => 'einvoice.create', 'name' => 'E-Fatura Oluştur',   'module_code' => 'einvoice', 'group' => 'E-Fatura'],
            ['code' => 'einvoice.cancel', 'name' => 'E-Fatura İptal',     'module_code' => 'einvoice', 'group' => 'E-Fatura'],
            // Personel (3)
            ['code' => 'staff.view',   'name' => 'Personel Görüntüle', 'module_code' => 'staff', 'group' => 'Personel'],
            ['code' => 'staff.create', 'name' => 'Personel Ekle',      'module_code' => 'staff', 'group' => 'Personel'],
            ['code' => 'staff.edit',   'name' => 'Personel Düzenle',   'module_code' => 'staff', 'group' => 'Personel'],
            // Donanım (2)
            ['code' => 'hardware.view',   'name' => 'Donanım Görüntüle', 'module_code' => 'hardware', 'group' => 'Donanım'],
            ['code' => 'hardware.manage', 'name' => 'Donanım Yönet',     'module_code' => 'hardware', 'group' => 'Donanım'],
            // Şube (3)
            ['code' => 'branches.view',   'name' => 'Şubeleri Görüntüle', 'module_code' => 'core_pos', 'group' => 'Şube'],
            ['code' => 'branches.create', 'name' => 'Şube Ekle',          'module_code' => 'core_pos', 'group' => 'Şube'],
            ['code' => 'branches.edit',   'name' => 'Şube Düzenle',       'module_code' => 'core_pos', 'group' => 'Şube'],
            // Yönetim (6)
            ['code' => 'settings.manage', 'name' => 'Ayar Yönetimi',            'module_code' => 'core_pos', 'group' => 'Yönetim'],
            ['code' => 'modules.manage',  'name' => 'Modül Yönetimi',           'module_code' => 'core_pos', 'group' => 'Yönetim'],
            ['code' => 'roles.manage',    'name' => 'Rol Yönetimi',             'module_code' => 'core_pos', 'group' => 'Yönetim'],
            ['code' => 'users.view',      'name' => 'Kullanıcıları Görüntüle',  'module_code' => 'core_pos', 'group' => 'Yönetim'],
            ['code' => 'users.create',    'name' => 'Kullanıcı Ekle',           'module_code' => 'core_pos', 'group' => 'Yönetim'],
            ['code' => 'users.edit',      'name' => 'Kullanıcı Düzenle',        'module_code' => 'core_pos', 'group' => 'Yönetim'],
        ];

        $permissions = [];
        foreach ($permissionsData as $p) {
            $permissions[$p['code']] = Permission::create($p);
        }

        // ─── 6. Role ↔ Permission Assignments ───────────────────

        // Admin: ALL permissions
        $roles['admin']->permissions()->attach(
            collect($permissions)->pluck('id')->toArray()
        );

        // Manager: all except modules.manage, roles.manage, settings.manage
        $managerExcluded = ['modules.manage', 'roles.manage', 'settings.manage'];
        $roles['manager']->permissions()->attach(
            collect($permissions)
                ->filter(fn($p, $code) => !in_array($code, $managerExcluded))
                ->pluck('id')
                ->toArray()
        );

        // Cashier: sales.*, products.view, customers.view, customers.create, reports.basic
        $cashierCodes = [
            'sales.view', 'sales.create', 'sales.cancel', 'sales.refund', 'sales.discount',
            'products.view',
            'customers.view', 'customers.create',
            'reports.basic',
        ];
        $roles['cashier']->permissions()->attach(
            collect($permissions)
                ->filter(fn($p, $code) => in_array($code, $cashierCodes))
                ->pluck('id')
                ->toArray()
        );

        // Accounting: sales.view, reports.*, income.*, expense.*, einvoice.*
        $accountingCodes = [
            'sales.view',
            'reports.basic', 'reports.advanced', 'reports.export',
            'income.view', 'income.create',
            'expense.view', 'expense.create',
            'einvoice.view', 'einvoice.create', 'einvoice.cancel',
        ];
        $roles['accounting']->permissions()->attach(
            collect($permissions)
                ->filter(fn($p, $code) => in_array($code, $accountingCodes))
                ->pluck('id')
                ->toArray()
        );

        // Warehouse: products.*, stock.*, reports.basic
        $warehouseCodes = [
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'stock.view', 'stock.adjust', 'stock.count', 'stock.transfer',
            'reports.basic',
        ];
        $roles['warehouse']->permissions()->attach(
            collect($permissions)
                ->filter(fn($p, $code) => in_array($code, $warehouseCodes))
                ->pluck('id')
                ->toArray()
        );

        // ─── 7. Default Tenant ──────────────────────────────────
        $tenant = Tenant::create([
            'name' => 'Demo İşletme',
            'slug' => 'demo-isletme',
            'status' => 'active',
            'plan_id' => $enterprisePlan->id,
            'trial_ends_at' => Carbon::now()->addDays(14),
            'billing_email' => 'admin@emareposs.com',
            'meta' => ['company' => 'Demo A.Ş.', 'tax_office' => 'Kadıköy', 'tax_number' => '1234567890'],
        ]);

        // ─── 8. Tenant ↔ Module Assignments (all active) ────────
        foreach ($modules as $module) {
            $tenant->modules()->attach($module->id, [
                'is_active' => true,
                'activated_at' => Carbon::now(),
                'expires_at' => null,
                'config' => null,
            ]);
        }

        // ─── 9. Default Branch ──────────────────────────────────
        $branch = Branch::create([
            'tenant_id' => $tenant->id,
            'name' => 'Merkez Şube',
            'code' => 'MERKEZ',
            'address' => 'İstanbul, Kadıköy',
            'phone' => '0216 555 00 00',
            'city' => 'İstanbul',
            'district' => 'Kadıköy',
            'is_active' => true,
            'settings' => ['currency' => 'TRY', 'timezone' => 'Europe/Istanbul'],
        ]);

        // ─── 10. Default Tax Rates ──────────────────────────────
        TaxRate::create([
            'tenant_id' => $tenant->id,
            'name' => 'KDV %1',
            'code' => 'kdv_1',
            'rate' => 1.0000,
            'type' => 'percentage',
            'description' => 'Temel gıda maddeleri',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        TaxRate::create([
            'tenant_id' => $tenant->id,
            'name' => 'KDV %10',
            'code' => 'kdv_10',
            'rate' => 10.0000,
            'type' => 'percentage',
            'description' => 'Gıda ve restoran hizmetleri',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        TaxRate::create([
            'tenant_id' => $tenant->id,
            'name' => 'KDV %20',
            'code' => 'kdv_20',
            'rate' => 20.0000,
            'type' => 'percentage',
            'description' => 'Genel KDV oranı',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // ─── 11. Default Payment Types ──────────────────────────
        PaymentType::create(['tenant_id' => $tenant->id, 'name' => 'Nakit',          'code' => 'cash',     'is_active' => true, 'sort_order' => 1]);
        PaymentType::create(['tenant_id' => $tenant->id, 'name' => 'Kredi Kartı',    'code' => 'card',     'is_active' => true, 'sort_order' => 2]);
        PaymentType::create(['tenant_id' => $tenant->id, 'name' => 'Veresiye',       'code' => 'credit',   'is_active' => true, 'sort_order' => 3]);
        PaymentType::create(['tenant_id' => $tenant->id, 'name' => 'Havale / EFT',   'code' => 'transfer', 'is_active' => true, 'sort_order' => 4]);

        // ─── 12. Admin User ─────────────────────────────────────
        $adminUser = User::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'role_id' => $roles['admin']->id,
            'is_super_admin' => true,
            'name' => 'Sistem Yöneticisi',
            'email' => 'admin@emareposs.com',
            'password' => Hash::make('123456'),
        ]);

        // ─── 13. Sample Categories ──────────────────────────────
        $catYiyecek = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Yiyecek',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $catIcecek = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'İçecek',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $catTatli = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Tatlı',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // ─── 14. Sample Products ────────────────────────────────
        $productsData = [
            // Yiyecek
            ['barcode' => '8690001000001', 'name' => 'Hamburger',     'category_id' => $catYiyecek->id, 'sale_price' => 150.00, 'purchase_price' => 75.00,  'vat_rate' => 10, 'stock_quantity' => 100],
            ['barcode' => '8690001000002', 'name' => 'Pizza',         'category_id' => $catYiyecek->id, 'sale_price' => 200.00, 'purchase_price' => 100.00, 'vat_rate' => 10, 'stock_quantity' => 100],
            ['barcode' => '8690001000003', 'name' => 'Adana Kebap',   'category_id' => $catYiyecek->id, 'sale_price' => 350.00, 'purchase_price' => 175.00, 'vat_rate' => 10, 'stock_quantity' => 100],
            // İçecek
            ['barcode' => '8690001000004', 'name' => 'Ayran',         'category_id' => $catIcecek->id, 'sale_price' => 30.00,  'purchase_price' => 10.00,  'vat_rate' => 10, 'stock_quantity' => 200],
            ['barcode' => '8690001000005', 'name' => 'Kola',          'category_id' => $catIcecek->id, 'sale_price' => 40.00,  'purchase_price' => 15.00,  'vat_rate' => 10, 'stock_quantity' => 200],
            ['barcode' => '8690001000006', 'name' => 'Çay',           'category_id' => $catIcecek->id, 'sale_price' => 25.00,  'purchase_price' => 5.00,   'vat_rate' => 10, 'stock_quantity' => 500],
            ['barcode' => '8690001000007', 'name' => 'Türk Kahvesi',  'category_id' => $catIcecek->id, 'sale_price' => 50.00,  'purchase_price' => 15.00,  'vat_rate' => 10, 'stock_quantity' => 300],
            ['barcode' => '8690001000008', 'name' => 'Su',            'category_id' => $catIcecek->id, 'sale_price' => 15.00,  'purchase_price' => 3.00,   'vat_rate' => 10, 'stock_quantity' => 500],
            // Tatlı
            ['barcode' => '8690001000009', 'name' => 'Künefe',        'category_id' => $catTatli->id, 'sale_price' => 120.00, 'purchase_price' => 50.00,  'vat_rate' => 10, 'stock_quantity' => 50],
            ['barcode' => '8690001000010', 'name' => 'Baklava',       'category_id' => $catTatli->id, 'sale_price' => 100.00, 'purchase_price' => 40.00,  'vat_rate' => 10, 'stock_quantity' => 50],
        ];

        foreach ($productsData as $pd) {
            Product::create(array_merge($pd, [
                'tenant_id' => $tenant->id,
                'unit' => 'adet',
                'is_active' => true,
                'is_service' => false,
                'critical_stock' => 10,
            ]));
        }

        // ─── 15. Sample Table Region & Tables ───────────────────
        $region = TableRegion::create([
            'tenant_id' => $tenant->id,
            'branch_id' => $branch->id,
            'name' => 'İç Mekan',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            RestaurantTable::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'table_region_id' => $region->id,
                'table_no' => (string)$i,
                'name' => "Masa {$i}",
                'capacity' => 4,
                'status' => 'empty',
                'sort_order' => $i,
                'is_active' => true,
            ]);
        }

        // ─── 16. Sample Customers ───────────────────────────────
        $customers = [];
        $customerNames = [
            ['name' => 'Ahmet Yılmaz',  'phone' => '0532 111 22 33', 'email' => 'ahmet@test.com',  'type' => 'individual'],
            ['name' => 'Mehmet Kaya',    'phone' => '0533 222 33 44', 'email' => 'mehmet@test.com',  'type' => 'individual'],
            ['name' => 'Ayşe Demir',     'phone' => '0534 333 44 55', 'email' => 'ayse@test.com',    'type' => 'individual'],
            ['name' => 'Fatma Çelik',    'phone' => '0535 444 55 66', 'email' => 'fatma@test.com',   'type' => 'individual'],
            ['name' => 'ABC Şirketi',    'phone' => '0212 666 77 88', 'email' => 'info@abc.com',     'type' => 'corporate', 'tax_number' => '1234567890', 'tax_office' => 'Kadıköy'],
        ];
        foreach ($customerNames as $c) {
            $customers[] = Customer::create(array_merge($c, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'balance' => 0,
            ]));
        }

        // ─── 17. Sample Firms ───────────────────────────────────
        $firmNames = [
            ['name' => 'Metro Toptan',      'phone' => '0212 100 00 01', 'city' => 'İstanbul'],
            ['name' => 'Baktat Gıda',       'phone' => '0312 200 00 02', 'city' => 'Ankara'],
            ['name' => 'İçecek Dünyası',    'phone' => '0232 300 00 03', 'city' => 'İzmir'],
        ];
        foreach ($firmNames as $f) {
            Firm::create(array_merge($f, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
                'balance' => 0,
            ]));
        }

        // ─── 18. Sample Sales & Orders (last 30 days) ──────────
        $products = Product::where('tenant_id', $tenant->id)->get();
        $paymentMethods = ['cash', 'card', 'cash', 'cash', 'card']; // weighted towards cash
        $receiptCounter = 1000;
        $now = Carbon::now();

        // Create a closed cash register for past days
        for ($dayOffset = 30; $dayOffset >= 0; $dayOffset--) {
            $date = $now->copy()->subDays($dayOffset);
            $dayStart = $date->copy()->setTime(9, 0, 0);
            $dayEnd = $date->copy()->setTime(23, 0, 0);

            // Create cash register for this day
            $register = CashRegister::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'user_id' => $adminUser->id,
                'opening_amount' => 500.00,
                'closing_amount' => 0,
                'expected_amount' => 0,
                'difference' => 0,
                'total_sales' => 0,
                'total_cash' => 0,
                'total_card' => 0,
                'total_refunds' => 0,
                'total_transactions' => 0,
                'status' => $dayOffset === 0 ? 'open' : 'closed',
                'opened_at' => $dayStart,
                'closed_at' => $dayOffset === 0 ? null : $dayEnd,
            ]);

            // Generate 8-20 sales per day
            $salesPerDay = rand(8, 20);
            $dayTotalSales = 0;
            $dayCashTotal = 0;
            $dayCardTotal = 0;

            for ($s = 0; $s < $salesPerDay; $s++) {
                $receiptCounter++;
                $saleTime = $dayStart->copy()->addMinutes(rand(0, 840)); // random time in business hours
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
                $customerId = rand(0, 5) > 3 ? $customers[array_rand($customers)]->id : null;

                // Pick 1-5 random products for this sale
                $itemCount = rand(1, 5);
                $selectedProducts = $products->random(min($itemCount, $products->count()));
                $subtotal = 0;
                $vatTotal = 0;
                $saleItems = [];

                foreach ($selectedProducts as $product) {
                    $qty = rand(1, 3);
                    $unitPrice = $product->sale_price;
                    $discount = rand(0, 10) > 8 ? round($unitPrice * 0.1, 2) : 0;
                    $itemTotal = ($unitPrice * $qty) - $discount;
                    $vatAmount = round($itemTotal * ($product->vat_rate / (100 + $product->vat_rate)), 2);

                    $saleItems[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'barcode' => $product->barcode,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'discount' => $discount,
                        'vat_rate' => $product->vat_rate,
                        'vat_amount' => $vatAmount,
                        'total' => $itemTotal,
                    ];

                    $subtotal += $unitPrice * $qty;
                    $vatTotal += $vatAmount;
                }

                $discountTotal = collect($saleItems)->sum('discount');
                $grandTotal = $subtotal - $discountTotal;
                $cashAmount = $paymentMethod === 'cash' ? $grandTotal : 0;
                $cardAmount = $paymentMethod === 'card' ? $grandTotal : 0;

                $sale = Sale::create([
                    'tenant_id' => $tenant->id,
                    'branch_id' => $branch->id,
                    'receipt_no' => 'FIS-' . $receiptCounter,
                    'customer_id' => $customerId,
                    'user_id' => $adminUser->id,
                    'payment_method' => $paymentMethod,
                    'total_items' => count($saleItems),
                    'subtotal' => $subtotal,
                    'vat_total' => $vatTotal,
                    'additional_tax_total' => 0,
                    'discount_total' => $discountTotal,
                    'grand_total' => $grandTotal,
                    'discount' => $discountTotal,
                    'cash_amount' => $cashAmount,
                    'card_amount' => $cardAmount,
                    'status' => 'completed',
                    'staff_name' => $adminUser->name,
                    'sold_at' => $saleTime,
                ]);

                foreach ($saleItems as $item) {
                    SaleItem::create(array_merge($item, [
                        'sale_id' => $sale->id,
                    ]));
                }

                $dayTotalSales += $grandTotal;
                $dayCashTotal += $cashAmount;
                $dayCardTotal += $cardAmount;

                // Also create a matching order for some sales
                if (rand(0, 1)) {
                    $order = Order::create([
                        'tenant_id' => $tenant->id,
                        'branch_id' => $branch->id,
                        'sale_id' => $sale->id,
                        'order_number' => 'SIP-' . $receiptCounter,
                        'user_id' => $adminUser->id,
                        'customer_id' => $customerId,
                        'status' => 'completed',
                        'order_type' => ['dine_in', 'takeaway', 'delivery'][rand(0, 2)],
                        'total_items' => count($saleItems),
                        'subtotal' => $subtotal,
                        'vat_total' => $vatTotal,
                        'discount_total' => $discountTotal,
                        'grand_total' => $grandTotal,
                        'ordered_at' => $saleTime,
                    ]);

                    foreach ($saleItems as $item) {
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $item['product_id'],
                            'product_name' => $item['product_name'],
                            'quantity' => $item['quantity'],
                            'unit_price' => $item['unit_price'],
                            'discount' => $item['discount'],
                            'vat_rate' => $item['vat_rate'],
                            'vat_amount' => $item['vat_amount'],
                            'total' => $item['total'],
                            'status' => 'served',
                        ]);
                    }
                }
            }

            // Update cash register totals
            $register->update([
                'total_sales' => $dayTotalSales,
                'total_cash' => $dayCashTotal,
                'total_card' => $dayCardTotal,
                'total_transactions' => $salesPerDay,
                'expected_amount' => 500 + $dayCashTotal,
                'closing_amount' => $dayOffset === 0 ? 0 : (500 + $dayCashTotal + rand(-20, 20)),
                'difference' => $dayOffset === 0 ? 0 : rand(-20, 20),
            ]);
        }

        // ─── 19. A few pending orders for kitchen ───────────────
        for ($i = 1; $i <= 3; $i++) {
            $order = Order::create([
                'tenant_id' => $tenant->id,
                'branch_id' => $branch->id,
                'order_number' => 'SIP-MUTFAK-' . $i,
                'user_id' => $adminUser->id,
                'status' => $i <= 2 ? 'pending' : 'preparing',
                'order_type' => 'dine_in',
                'total_items' => 2,
                'subtotal' => 0,
                'vat_total' => 0,
                'discount_total' => 0,
                'grand_total' => 0,
                'ordered_at' => Carbon::now()->subMinutes(rand(5, 30)),
            ]);

            $orderTotal = 0;
            $selectedProducts = $products->random(2);
            foreach ($selectedProducts as $product) {
                $qty = rand(1, 2);
                $itemTotal = $product->sale_price * $qty;
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                    'unit_price' => $product->sale_price,
                    'discount' => 0,
                    'vat_rate' => $product->vat_rate,
                    'vat_amount' => round($itemTotal * ($product->vat_rate / (100 + $product->vat_rate)), 2),
                    'total' => $itemTotal,
                    'status' => $i <= 2 ? 'pending' : 'preparing',
                ]);
                $orderTotal += $itemTotal;
            }
            $order->update(['grand_total' => $orderTotal, 'subtotal' => $orderTotal]);
        }

        // ─── 20. Stock Movements ────────────────────────────────
        foreach ($products->take(5) as $product) {
            StockMovement::create([
                'tenant_id' => $tenant->id,
                'type' => 'purchase',
                'barcode' => $product->barcode,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'transaction_code' => 'STK-' . rand(1000, 9999),
                'note' => 'İlk stok girişi',
                'firm_customer' => 'Metro Toptan',
                'payment_type' => 'cash',
                'quantity' => $product->stock_quantity,
                'remaining' => $product->stock_quantity,
                'unit_price' => $product->purchase_price,
                'total' => $product->purchase_price * $product->stock_quantity,
                'movement_date' => Carbon::now()->subDays(30),
            ]);
        }
    }
}
