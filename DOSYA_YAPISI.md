# 📁 Emare POS / Adisyon — Dosya Yapısı

> **Oluşturulma:** Otomatik  
> **Amaç:** Yapay zekalar kod yazmadan önce mevcut dosya yapısını incelemeli

---

## Proje Dosya Ağacı

```
/Users/emre/Desktop/Emare/emarepos
├── DESIGN_GUIDE.md
├── EMARE_ORTAK_CALISMA -> /Users/emre/Desktop/Emare/EMARE_ORTAK_CALISMA
├── TECHNICAL_SPEC.md
├── emareadisyon_hafiza.md
├── emarepos_hafiza.md
├── explore-panel.js
├── explore.js
├── package-lock.json
├── package.json
├── pos-system
│   ├── .editorconfig
│   ├── .env
│   ├── .env.example
│   ├── .gitattributes
│   ├── .gitignore
│   ├── EMARE_AI_COLLECTIVE.md
│   ├── EMARE_ORTAK_HAFIZA.md
│   ├── README.md
│   ├── app
│   │   ├── Http
│   │   │   ├── Controllers
│   │   │   └── Middleware
│   │   ├── Models
│   │   │   ├── AccountTransaction.php
│   │   │   ├── Branch.php
│   │   │   ├── Campaign.php
│   │   │   ├── CampaignUsage.php
│   │   │   ├── CashRegister.php
│   │   │   ├── Category.php
│   │   │   ├── Customer.php
│   │   │   ├── Expense.php
│   │   │   ├── Firm.php
│   │   │   ├── HardwareDevice.php
│   │   │   ├── HardwareDriver.php
│   │   │   ├── Income.php
│   │   │   ├── IncomeExpenseType.php
│   │   │   ├── LoyaltyPoint.php
│   │   │   ├── LoyaltyProgram.php
│   │   │   ├── Module.php
│   │   │   ├── Order.php
│   │   │   ├── OrderItem.php
│   │   │   ├── PaymentType.php
│   │   │   ├── Permission.php
│   │   │   ├── Plan.php
│   │   │   ├── Product.php
│   │   │   ├── RestaurantTable.php
│   │   │   ├── Role.php
│   │   │   ├── Sale.php
│   │   │   ├── SaleItem.php
│   │   │   ├── ServiceCategory.php
│   │   │   ├── Staff.php
│   │   │   ├── StockMovement.php
│   │   │   ├── TableRegion.php
│   │   │   ├── TableSession.php
│   │   │   ├── TaxRate.php
│   │   │   ├── Tenant.php
│   │   │   └── User.php
│   │   ├── Providers
│   │   │   └── AppServiceProvider.php
│   │   ├── Services
│   │   │   ├── CashRegisterService.php
│   │   │   ├── SaleService.php
│   │   │   └── TableService.php
│   │   ├── Traits
│   │   │   └── BelongsToTenant.php
│   │   └── helpers.php
│   ├── artisan
│   ├── bootstrap
│   │   ├── app.php
│   │   ├── cache
│   │   │   ├── .gitignore
│   │   │   ├── packages.php
│   │   │   └── services.php
│   │   └── providers.php
│   ├── composer.json
│   ├── composer.lock
│   ├── config
│   │   ├── app.php
│   │   ├── auth.php
│   │   ├── cache.php
│   │   ├── database.php
│   │   ├── filesystems.php
│   │   ├── logging.php
│   │   ├── mail.php
│   │   ├── queue.php
│   │   ├── services.php
│   │   └── session.php
│   ├── database
│   │   ├── .gitignore
│   │   ├── database.sqlite
│   │   ├── factories
│   │   │   └── UserFactory.php
│   │   ├── migrations
│   │   │   ├── 2026_03_02_000001_create_tenants_table.php
│   │   │   ├── 2026_03_02_000002_create_plans_table.php
│   │   │   ├── 2026_03_02_000003_create_modules_table.php
│   │   │   ├── 2026_03_02_000004_create_plan_modules_table.php
│   │   │   ├── 2026_03_02_000005_create_tenant_modules_table.php
│   │   │   ├── 2026_03_02_000006_create_roles_table.php
│   │   │   ├── 2026_03_02_000007_create_permissions_table.php
│   │   │   ├── 2026_03_02_000008_create_role_permissions_table.php
│   │   │   ├── 2026_03_02_000009_create_branches_table.php
│   │   │   ├── 2026_03_02_000010_create_branch_modules_table.php
│   │   │   ├── 2026_03_02_000011_create_users_table.php
│   │   │   ├── 2026_03_02_000012_create_user_roles_table.php
│   │   │   ├── 2026_03_02_000013_create_categories_table.php
│   │   │   ├── 2026_03_02_000014_create_service_categories_table.php
│   │   │   ├── 2026_03_02_000015_create_tax_rates_table.php
│   │   │   ├── 2026_03_02_000016_create_payment_types_table.php
│   │   │   ├── 2026_03_02_000017_create_products_table.php
│   │   │   ├── 2026_03_02_000018_create_branch_product_table.php
│   │   │   ├── 2026_03_02_000019_create_customers_table.php
│   │   │   ├── 2026_03_02_000020_create_firms_table.php
│   │   │   ├── 2026_03_02_000021_create_staff_table.php
│   │   │   ├── 2026_03_02_000022_create_sales_table.php
│   │   │   ├── 2026_03_02_000023_create_sale_items_table.php
│   │   │   ├── 2026_03_02_000024_create_account_transactions_table.php
│   │   │   ├── 2026_03_02_000025_create_stock_movements_table.php
│   │   │   ├── 2026_03_02_000026_create_income_expense_types_table.php
│   │   │   ├── 2026_03_02_000027_create_incomes_table.php
│   │   │   ├── 2026_03_02_000028_create_expenses_table.php
│   │   │   ├── 2026_03_02_000029_create_hardware_devices_table.php
│   │   │   ├── 2026_03_02_000030_create_hardware_drivers_table.php
│   │   │   ├── 2026_03_02_000031_create_table_regions_table.php
│   │   │   ├── 2026_03_02_000032_create_tables_table.php
│   │   │   ├── 2026_03_02_000033_create_table_sessions_table.php
│   │   │   ├── 2026_03_02_000034_create_orders_table.php
│   │   │   ├── 2026_03_02_000035_create_order_items_table.php
│   │   │   ├── 2026_03_02_000036_create_cash_registers_table.php
│   │   │   ├── 2026_03_02_000037_create_campaigns_table.php
│   │   │   ├── 2026_03_02_000038_create_campaign_usages_table.php
│   │   │   ├── 2026_03_02_000039_create_loyalty_programs_table.php
│   │   │   ├── 2026_03_02_000040_create_loyalty_points_table.php
│   │   │   └── 2026_03_02_000041_create_cache_and_sessions_table.php
│   │   └── seeders
│   │       └── DatabaseSeeder.php
│   ├── package.json
│   ├── phpunit.xml
│   ├── public
│   │   ├── .htaccess
│   │   ├── favicon.ico
│   │   ├── index.php
│   │   └── robots.txt
│   ├── resources
│   │   ├── css
│   │   │   └── app.css
│   │   ├── js
│   │   │   ├── app.js
│   │   │   └── bootstrap.js
│   │   └── views
│   │       ├── pos
│   │       └── welcome.blade.php
│   ├── routes
│   │   ├── console.php
│   │   └── web.php
│   ├── storage
│   │   ├── app
│   │   │   ├── .gitignore
│   │   │   ├── private
│   │   │   └── public
│   │   ├── framework
│   │   │   ├── .gitignore
│   │   │   ├── cache
│   │   │   ├── sessions
│   │   │   ├── testing
│   │   │   └── views
│   │   └── logs
│   │       ├── .gitignore
│   │       └── laravel.log
│   ├── tests
│   │   ├── Feature
│   │   │   └── ExampleTest.php
│   │   ├── TestCase.php
│   │   └── Unit
│   │       └── ExampleTest.php
│   └── vite.config.js
├── screenshot-after-login.png
├── screenshot-dashboard.png
└── screenshot-panel-login.png

38 directories, 142 files

```

---

## 📌 Kullanım Talimatları (AI İçin)

Bu dosya, kod üretmeden önce projenin mevcut yapısını kontrol etmek içindir:

1. **Yeni dosya oluşturmadan önce:** Bu ağaçta benzer bir dosya var mı kontrol et
2. **Yeni klasör oluşturmadan önce:** Mevcut klasör yapısına uygun mu kontrol et
3. **Import/require yapmadan önce:** Dosya yolu doğru mu kontrol et
4. **Kod kopyalamadan önce:** Aynı fonksiyon başka dosyada var mı kontrol et

**Örnek:**
- ❌ "Yeni bir auth.py oluşturalım" → ✅ Kontrol et, zaten `app/auth.py` var mı?
- ❌ "config/ klasörü oluşturalım" → ✅ Kontrol et, zaten `config/` var mı?
- ❌ `from utils import helper` → ✅ Kontrol et, `utils/helper.py` gerçekten var mı?

---

**Not:** Bu dosya otomatik oluşturulmuştur. Proje yapısı değiştikçe güncellenmelidir.

```bash
# Güncelleme komutu
python3 /Users/emre/Desktop/Emare/create_dosya_yapisi.py
```
