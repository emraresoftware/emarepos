# Emare POS — Tam Proje Hafıza Dosyası

> 🔗 **Ortak Hafıza:** [`EMARE_ORTAK_HAFIZA.md`](/Users/emre/Desktop/Emare/EMARE_ORTAK_HAFIZA.md) — Tüm Emare ekosistemi, sunucu bilgileri, standartlar ve proje envanteri için bak.


> **Son Güncelleme:** 9 Mart 2026
> **Proje Durumu:** Aktif geliştirme — tüm sayfalar çalışır (18 sayfa, 71+ route), güvenlik denetimi tamamlandı
> **Bu dosyanın amacı:** Yeni bir AI oturumunda "bu dosyayı oku, kaldığımız yerden devam et" demen yeterlidir — projenin tüm detayları buradadır.
> **Kayıt:** Bu dosyayı taşıyabilirsin, içindeki bilgiler projenin tüm teknik, görsel ve iş mantığı detaylarını kapsar.

---

## BÖLÜM 1 — PROJE TANIMI

**Emare POS** (Emare Adisyon), restoran / kafe / işletmeler için geliştirilmiş web tabanlı bir **POS (Point of Sale) ve Adisyon Yönetim Sistemi**'dir. Emare Finance ürün ailesinin bir parçasıdır.

### Temel Özellikler
- Hızlı satış (barkod / ürün arama ile)
- Masa yönetimi (bölge, açma, sipariş, ödeme, transfer)
- Mutfak ekranı (gerçek zamanlı sipariş takibi)
- Kasa yönetimi (aç / kapat, Z raporu)
- Müşteri yönetimi (cari hesap, bakiye, tahsilat)
- Cari / firma yönetimi (tedarikçi takibi)
- Stok / depo yönetimi (giriş / çıkış hareketleri)
- Raporlama (günlük satış, ödeme yöntemleri, en çok satan, kategori bazlı)
- Kullanıcı yönetimi (rol tabanlı RBAC)
- Şube yönetimi (multi-branch desteği)
- Kategori ve ürün yönetimi
- Gün sonu işlemleri

### İş Modeli
- **Multi-Tenant SaaS**: Birden fazla işletme destekler, her biri kendi verisiyle izole
- **Multi-Branch**: Her tenant'ın birden fazla şubesi olabilir
- **Plan Bazlı Lisanslama**: Başlangıç / Profesyonel / Kurumsal
- **Modüler Yapı**: Core POS zorunlu, diğer modüller aktive edilebilir

---

## BÖLÜM 2 — TEKNİK ALTYAPI

### Teknoloji Stack

| Bileşen | Teknoloji | Versiyon |
|---------|-----------|----------|
| **Backend** | Laravel (PHP) | ^12.0 (PHP ^8.2) |
| **Veritabanı (Geliştirme)** | SQLite | — |
| **Veritabanı (Production)** | MariaDB | 10.x |
| **Web Server (Production)** | Nginx | 1.20 (port 3000) |
| **Sunucu OS (Production)** | AlmaLinux | 9.7 |
| **Frontend CSS** | Tailwind CSS | CDN (inline config) |
| **JS Framework** | Alpine.js | 3.x (CDN) |
| **Grafik** | Chart.js | 4.x (CDN) |
| **İkonlar** | Font Awesome | 6.5.1 (CDN) |
| **Font** | Inter (Google Fonts) | 300–900 |
| **Auth** | Laravel built-in | Session tabanlı |
| **AI Asistan** | Google Gemini | 2.5 Flash |
| **Dil / Timezone** | Türkçe (tr) | Europe/Istanbul |
| **Para Birimi** | TRY (₺) | Format: 1.234,56 ₺ |

### Proje Dizini
```
/Users/emre/Desktop/adisyon sistemi/pos-system/
```

### Sunucuyu Çalıştırma
```bash
cd "/Users/emre/Desktop/adisyon sistemi/pos-system"
php artisan serve --port=8080
# Tarayıcıda: http://127.0.0.1:8080
```

### Veritabanı Sıfırlama
```bash
php artisan migrate:fresh --seed
```

### Giriş Bilgileri
| Alan | Değer |
|------|-------|
| **E-posta** | `admin@emareposs.com` |
| **Şifre** | `123456` |
| **Rol** | Super Admin |

---

## BÖLÜM 3 — DOSYA YAPISI

### 3.1 Controllers (17 adet)
```
app/Http/Controllers/
├── Auth/
│   └── PosLoginController.php          # Giriş / Çıkış
└── Pos/
    ├── DashboardController.php         # Ana sayfa özet
    ├── SaleController.php              # Hızlı satış + satış listesi + AJAX arama
    ├── TableController.php             # Masa yönetimi
    ├── KitchenController.php           # Mutfak ekranı
    ├── CashRegisterController.php      # Kasa aç / kapat
    ├── CashReportController.php        # Kasa raporu
    ├── ProductController.php           # Ürün CRUD
    ├── CategoryController.php          # Kategori CRUD
    ├── CustomerController.php          # Müşteri CRUD + tahsilat
    ├── FirmController.php              # Cari CRUD + ödeme
    ├── OrderController.php             # Sipariş listeleme
    ├── ReportController.php            # Raporlar (grafik + tablo)
    ├── StockController.php             # Stok hareketleri
    ├── BranchController.php            # Şube yönetimi
    ├── UserController.php              # Kullanıcı yönetimi
    ├── DayOperationController.php      # Gün sonu işlemleri
    └── SettingController.php           # Ayarlar
```

### 3.2 Models (34 adet)
```
app/Models/
├── Tenant.php              # İşletme (multi-tenant)
├── Plan.php                # Abonelik planı
├── Module.php              # Sistem modülleri
├── Branch.php              # Şube
├── User.php                # Kullanıcı
├── Role.php                # Rol
├── Permission.php          # İzin
├── Category.php            # Ürün kategorisi
├── ServiceCategory.php     # Hizmet kategorisi
├── Product.php             # Ürün
├── Customer.php            # Müşteri
├── Firm.php                # Cari / Tedarikçi
├── Staff.php               # Personel
├── Sale.php                # Satış
├── SaleItem.php            # Satış kalemi
├── Order.php               # Sipariş
├── OrderItem.php           # Sipariş kalemi
├── CashRegister.php        # Kasa
├── RestaurantTable.php     # Masa
├── TableRegion.php         # Masa bölgesi
├── TableSession.php        # Masa oturumu
├── StockMovement.php       # Stok hareketi
├── AccountTransaction.php  # Hesap hareketi
├── TaxRate.php             # Vergi oranı
├── PaymentType.php         # Ödeme türü
├── Income.php              # Gelir
├── Expense.php             # Gider
├── IncomeExpenseType.php   # Gelir / Gider tipi
├── Campaign.php            # Kampanya
├── CampaignUsage.php       # Kampanya kullanımı
├── LoyaltyProgram.php      # Sadakat programı
├── LoyaltyPoint.php        # Sadakat puanı
├── HardwareDevice.php      # Donanım cihazı
└── HardwareDriver.php      # Donanım sürücüsü
```

### 3.3 Views (21 Blade dosyası)
```
resources/views/pos/
├── layouts/
│   └── app.blade.php               # Ana layout (sidebar + header + toast + posAjax)
├── auth/
│   └── login.blade.php             # Giriş sayfası
├── dashboard.blade.php             # Ana sayfa özeti (/)
├── sales/
│   ├── index.blade.php             # Hızlı satış POS ekranı (/pos)
│   └── list.blade.php              # Satış geçmişi (/pos/sales-list)
├── tables/
│   ├── index.blade.php             # Masa listesi (/tables)
│   └── detail.blade.php            # Masa detay (/tables/{id}/detail)
├── kitchen/
│   └── index.blade.php             # Mutfak ekranı (/kitchen)
├── cash-register/
│   └── index.blade.php             # Kasa yönetimi (/cash-register)
├── cash-report/
│   └── index.blade.php             # Kasa raporu (/cash-report)
├── products/
│   └── index.blade.php             # Ürün yönetimi (/products)
├── categories/
│   └── index.blade.php             # Kategori yönetimi (/categories)
├── customers/
│   └── index.blade.php             # Müşteri yönetimi (/customers)
├── firms/
│   └── index.blade.php             # Cari yönetimi (/firms)
├── orders/
│   └── index.blade.php             # Sipariş listesi (/orders)
├── reports/
│   └── index.blade.php             # Raporlar (/reports)
├── stock/
│   └── index.blade.php             # Stok / Depo (/stock)
├── branches/
│   └── index.blade.php             # Şube yönetimi (/branches)
├── users/
│   └── index.blade.php             # Kullanıcı yönetimi (/users)
├── day-operations/
│   └── index.blade.php             # Gün işlemleri (/day-operations)
└── settings/
    └── index.blade.php             # Ayarlar (/settings)
```

### 3.4 Services (3 adet)
```
app/Services/
├── SaleService.php           # 323 satır — Satış oluşturma, iade, stok güncelleme
├── TableService.php          # 141 satır — Masa aç/kapat, sipariş, ödeme, transfer
└── CashRegisterService.php   # 86 satır  — Kasa aç/kapat, rapor
```

### 3.5 Middleware (3 adet)
```
app/Http/Middleware/
├── ResolveTenant.php     # Auth sonrası tenant_id ve branch_id session'a yazar
├── CheckModule.php       # Modül erişim kontrolü
└── CheckPermission.php   # İzin kontrolü
```

### 3.6 Diğer Dosyalar
```
app/helpers.php              # formatCurrency() → number_format + " ₺"
bootstrap/app.php            # redirectGuestsTo('/login') middleware
database/database.sqlite     # SQLite geliştirme veritabanı
database/seeders/DatabaseSeeder.php  # Demo data seeder
routes/web.php               # Tüm route'lar (tek dosya, 67 route)
```

---

## BÖLÜM 4 — VERİTABANI ŞEMASI

### 4.1 Mevcut Migration Tabloları (41 tablo)

| # | Tablo | Açıklama |
|---|-------|----------|
| 1 | `tenants` | İşletme / Kiracı |
| 2 | `plans` | Abonelik planları (Başlangıç / Pro / Kurumsal) |
| 3 | `modules` | Sistem modülleri (10 adet) |
| 4 | `plan_modules` | Plan–modül ilişkisi |
| 5 | `tenant_modules` | Tenant–modül ilişkisi |
| 6 | `roles` | Roller (admin / manager / cashier / waiter / kitchen) |
| 7 | `permissions` | İzinler (41 adet) |
| 8 | `role_permissions` | Rol–izin ilişkisi |
| 9 | `branches` | Şubeler |
| 10 | `branch_modules` | Şube–modül ilişkisi |
| 11 | `users` | Kullanıcılar |
| 12 | `user_roles` | Kullanıcı–rol ilişkisi |
| 13 | `categories` | Ürün kategorileri |
| 14 | `service_categories` | Hizmet kategorileri |
| 15 | `tax_rates` | Vergi oranları (KDV %1 / %10 / %20) |
| 16 | `payment_types` | Ödeme türleri (Nakit / Kart / Havale / Veresiye) |
| 17 | `products` | Ürünler |
| 18 | `branch_product` | Şube–ürün pivot (şubeye özel fiyat / stok) |
| 19 | `customers` | Müşteriler |
| 20 | `firms` | Cariler / Tedarikçiler |
| 21 | `staff` | Personel |
| 22 | `sales` | Satışlar |
| 23 | `sale_items` | Satış kalemleri |
| 24 | `account_transactions` | Hesap hareketleri (tahsilat vb.) |
| 25 | `stock_movements` | Stok hareketleri |
| 26 | `income_expense_types` | Gelir / gider tipleri |
| 27 | `incomes` | Gelirler |
| 28 | `expenses` | Giderler |
| 29 | `hardware_devices` | Donanım cihazları (yazıcı, okuyucu) |
| 30 | `hardware_drivers` | Donanım sürücüleri |
| 31 | `table_regions` | Masa bölgeleri |
| 32 | `restaurant_tables` | Restoran masaları |
| 33 | `table_sessions` | Masa oturumları |
| 34 | `orders` | Siparişler |
| 35 | `order_items` | Sipariş kalemleri |
| 36 | `cash_registers` | Kasa kayıtları |
| 37 | `campaigns` | Kampanyalar |
| 38 | `campaign_usages` | Kampanya kullanımları |
| 39 | `loyalty_programs` | Sadakat programları |
| 40 | `loyalty_points` | Sadakat puanları |
| 41 | `cache` + `sessions` | Laravel cache ve session tabloları |

### 4.2 Kritik Tablo Detayları

#### `sales` (Satışlar)
```sql
id, tenant_id, external_id, receipt_no, branch_id, customer_id, user_id,
payment_method ENUM(cash/card/mixed/credit),
total_items, subtotal, vat_total, additional_tax_total,
discount_total, grand_total, discount,
cash_amount, card_amount,
status ENUM(completed/refunded/cancelled),
notes, staff_name, application,
sold_at, created_at, updated_at, deleted_at
```

#### `sale_items` (Satış Kalemleri)
```sql
id, sale_id, product_id, product_name, barcode,
quantity, unit_price, discount,
vat_rate, vat_amount,
additional_taxes JSON, additional_tax_amount,
total, created_at, updated_at
```

#### `orders` (Siparişler)
```sql
id, tenant_id, branch_id, table_session_id, sale_id,
order_number, user_id, customer_id,
status ENUM(pending/preparing/ready/served/completed/cancelled),
order_type ENUM(dine_in/takeaway/delivery),
total_items, subtotal, vat_total, discount_total, grand_total,
notes, kitchen_notes, ordered_at
```

#### `order_items` (Sipariş Kalemleri)
```sql
id, order_id, product_id, product_name,
quantity, unit_price, discount,
vat_rate, vat_amount, total,
status ENUM(pending/preparing/ready/served/cancelled),
notes
```

#### `products` (Ürünler)
```sql
id, tenant_id, external_id, barcode, name, description,
category_id, service_category_id,
variant_type, parent_id,
unit, purchase_price, sale_price,
vat_rate INT, additional_taxes JSON,
stock_quantity, critical_stock,
image_url, is_active, is_service,
created_at, updated_at, deleted_at
```

#### `customers` (Müşteriler)
```sql
id, tenant_id, external_id, name,
type ENUM(individual/corporate),
tax_number, tax_office,
phone, email, address, city, district,
balance DECIMAL(14,2),  -- + alacak, - borç
notes, is_active,
created_at, updated_at, deleted_at
```

#### `cash_registers` (Kasa)
```sql
id, tenant_id, branch_id, user_id,
opening_amount, closing_amount,
expected_amount, difference,
total_sales, total_cash, total_card,
total_refunds, total_transactions,
status ENUM(open/closed),
opened_at, closed_at
```

#### `restaurant_tables` (Masalar)
```sql
id, tenant_id, branch_id, table_region_id,
table_no, name, capacity,
status ENUM(empty/occupied/reserved/cleaning),
sort_order, is_active
```

#### `branches` (Şubeler)
```sql
id, tenant_id, external_id, name, code,
address, phone, city, district,
is_active, settings JSON,
created_at, updated_at, deleted_at
```

#### `account_transactions` (Cari Hesap Hareketleri)
```sql
id, external_id, customer_id,
type ENUM(sale/payment/refund/adjustment),
amount, balance_after,
description, reference,
transaction_date, created_at, updated_at
```

#### `stock_movements` (Stok Hareketleri)
```sql
id, type ENUM(purchase/sale/return/transfer/adjustment/waste/count),
barcode, product_id, product_name,
transaction_code, note, firm_customer, payment_type,
quantity, remaining, unit_price, total,
movement_date, created_at, updated_at
```

### 4.3 İleride Eklenecek Tablolar (TECHNICAL_SPEC'te Tanımlı)

Bu tablolar TECHNICAL_SPEC.md'de detaylı SQL şemasıyla tanımlı ama henüz migration'a eklenmemiştir:

| Tablo | Amaç |
|-------|------|
| `stock_counts` / `stock_count_items` | Stok sayım modülü |
| `purchase_invoices` / `purchase_invoice_items` | Alış fatura modülü |
| `e_invoices` / `e_invoice_items` / `e_invoice_settings` | E-Fatura modülü |
| `recurring_invoices` | Tekrarlayan fatura |
| `quotes` / `quote_items` | Teklif modülü |
| `customer_segments` / `customer_segment_members` | Müşteri segmentasyonu |
| `sms_settings` / `sms_templates` / `sms_scenarios` / `sms_logs` / `sms_blacklist` | SMS modülü |
| `signage_devices` / `signage_playlists` / `signage_contents` | Dijital tabela |
| `staff_motions` | Personel hareketleri |
| `tasks` | Görev yönetimi |

---

## BÖLÜM 5 — ROUTE HARİTASI (67 Route)

### Auth
| Method | URI | Açıklama |
|--------|-----|----------|
| GET | `/login` | Giriş sayfası |
| POST | `/login` | Giriş işlemi |
| POST | `/logout` | Çıkış |

### Dashboard
| Method | URI | Route Name |
|--------|-----|-----------|
| GET | `/` | `pos.dashboard` |

### Hızlı Satış (POS)
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/pos` | `pos.sales` | POS satış ekranı |
| POST | `/pos/sale` | `pos.sales.store` | Satış kaydet |
| GET | `/pos/products/search` | `pos.products.search` | Ürün arama (AJAX) |
| GET | `/pos/customers/search` | `pos.customers.search` | Müşteri arama (AJAX) |
| GET | `/pos/recent-sales` | `pos.sales.recent` | Son satışlar (AJAX) |
| GET | `/pos/sale/{sale}` | `pos.sales.show` | Satış detayı (AJAX) |
| POST | `/pos/sale/{sale}/refund` | `pos.sales.refund` | İade (AJAX) |
| GET | `/pos/sales-list` | `pos.sales.list` | Satış geçmişi sayfası |

### Masalar
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/tables` | `pos.tables` | Masa listesi |
| GET | `/tables/{table}/detail` | `pos.tables.detail` | Masa detay |
| POST | `/tables/{table}/open` | `pos.tables.open` | Masa aç |
| POST | `/tables/{table}/order` | `pos.tables.order` | Sipariş ekle |
| POST | `/tables/{table}/pay` | `pos.tables.pay` | Ödeme al |
| POST | `/tables/{table}/transfer` | `pos.tables.transfer` | Masa transferi |

### Mutfak
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/kitchen` | `pos.kitchen` | Mutfak ekranı |
| GET | `/kitchen/orders` | `pos.kitchen.orders` | Siparişler (AJAX) |
| POST | `/kitchen/order/{order}/status` | `pos.kitchen.order.status` | Sipariş durumu |
| POST | `/kitchen/item/{item}/status` | `pos.kitchen.item.status` | Kalem durumu |

### Kasa
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/cash-register` | `pos.cash-register` | Kasa sayfası |
| POST | `/cash-register/open` | `pos.cash-register.open` | Kasa aç |
| POST | `/cash-register/close` | `pos.cash-register.close` | Kasa kapat |
| GET | `/cash-register/{register}/report` | `pos.cash-register.report` | Kasa raporu |
| GET | `/cash-report` | `pos.cash-report` | Kasa rapor listesi |
| GET | `/cash-report/{register}` | `pos.cash-report.show` | Kasa rapor detayı |

### Ürünler
| Method | URI | Route Name |
|--------|-----|-----------|
| GET | `/products` | `pos.products` |
| POST | `/products` | `pos.products.store` |
| PUT | `/products/{product}` | `pos.products.update` |
| DELETE | `/products/{product}` | `pos.products.destroy` |

### Kategoriler
| Method | URI | Route Name |
|--------|-----|-----------|
| GET | `/categories` | `pos.categories` |
| POST | `/categories` | `pos.categories.store` |
| PUT | `/categories/{category}` | `pos.categories.update` |
| DELETE | `/categories/{category}` | `pos.categories.destroy` |

### Müşteriler
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/customers` | `pos.customers` | Müşteri listesi |
| POST | `/customers` | `pos.customers.store` | Müşteri oluştur |
| GET | `/customers/{customer}` | `pos.customers.show` | Müşteri detayı (JSON) |
| PUT | `/customers/{customer}` | `pos.customers.update` | Müşteri güncelle |
| POST | `/customers/{customer}/payment` | `pos.customers.payment` | Tahsilat ekle |

### Cariler (Firmalar)
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/firms` | `pos.firms` | Cari listesi |
| POST | `/firms` | `pos.firms.store` | Cari oluştur |
| PUT | `/firms/{firm}` | `pos.firms.update` | Cari güncelle |
| POST | `/firms/{firm}/payment` | `pos.firms.payment` | Cari ödeme |

### Siparişler
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/orders` | `pos.orders` | Sipariş listesi |
| GET | `/orders/{order}` | `pos.orders.show` | Sipariş detayı (JSON) |
| PUT | `/orders/{order}/status` | `pos.orders.status` | Durum güncelle |

### Raporlar
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/reports` | `pos.reports` | Rapor sayfası |
| GET | `/reports/daily` | `pos.reports.daily` | Günlük rapor (AJAX) |
| GET | `/reports/top-products` | `pos.reports.top-products` | En çok satanlar (AJAX) |

### Stok
| Method | URI | Route Name |
|--------|-----|-----------|
| GET | `/stock` | `pos.stock` |
| POST | `/stock` | `pos.stock.store` |

### Yönetim
| Method | URI | Route Name |
|--------|-----|-----------|
| GET | `/branches` | `pos.branches` |
| POST | `/branches` | `pos.branches.store` |
| PUT | `/branches/{branch}` | `pos.branches.update` |
| GET | `/users` | `pos.users` |
| POST | `/users` | `pos.users.store` |
| PUT | `/users/{user}` | `pos.users.update` |
| DELETE | `/users/{user}` | `pos.users.destroy` |
| GET | `/day-operations` | `pos.day-operations` |
| GET | `/settings` | `pos.settings` |
| PUT | `/settings/branch` | `pos.settings.branch` |
| PUT | `/settings/general` | `pos.settings.general` |

---

## BÖLÜM 6 — FRONTEND MİMARİSİ

### Layout Yapısı (`layouts/app.blade.php`)
- **Sidebar** (sol): 18 menü öğesi, toggle edilebilir (dar / geniş), Alpine.js `sidebarOpen` state
- **Main content**: `@yield('content')` ile sayfa içeriği
- **Toast sistemi**: Alpine.js ile `showToast(message, type)` global fonksiyon
- **AJAX Helper**: `posAjax(url, options)` — fetch wrapper, CSRF token otomatik eklenir, JSON parse eder
- **formatCurrency**: JS tarafında `formatCurrency(amount)` → "1.234,56 ₺"

### Sidebar Menü Sırası (18 öğe)
```
1.  Özet (Dashboard)     /
2.  Şubeler              /branches
3.  Siparişler           /orders
4.  Hızlı Satış          /pos
5.  Masalar              /tables
6.  Mutfak               /kitchen
7.  Gün İşlemleri        /day-operations
8.  Kasa                 /cash-register
9.  Kasa Raporu          /cash-report
10. Satışlar             /pos/sales-list
11. Müşteriler           /customers
12. Cariler              /firms
13. Kategoriler          /categories
14. Ürünler              /products
15. Kullanıcılar         /users
16. Raporlar             /reports
17. Depo                 /stock
18. Ayarlar              /settings
```

### AJAX Pattern
Tüm CRUD işlemleri (ürün, kategori, müşteri, cari, kullanıcı, şube) Alpine.js + `posAjax()` ile yapılır:
```javascript
const response = await posAjax(url, {
    method: 'POST', // veya PUT, DELETE
    body: JSON.stringify(this.form),
});
showToast('Başarılı', 'success');
window.location.reload();
```

### Form Pattern
- **Standard POST (redirect)**: Kasa aç/kapat, Ayarlar sayfası
- **Alpine.js modal + AJAX (JSON)**: Diğer tüm CRUD işlemleri

---

## BÖLÜM 7 — SEEDER (Demo Data)

`php artisan migrate:fresh --seed` ile oluşturulan veriler:

### Altyapı Verileri
| Veri | Adet | Detay |
|------|------|-------|
| Plans | 3 | Başlangıç, Profesyonel, Kurumsal |
| Modules | 10 | core, tables, kitchen, stock, reports, crm, campaigns, loyalty, hardware, multi_branch |
| Roles | 5 | admin, manager, cashier, waiter, kitchen |
| Permissions | 41 | Tüm modüller için CRUD izinleri |
| Tenant | 1 | "Emare Demo" |
| Branch | 1 | "Merkez Şube" |
| Tax Rates | 3 | KDV %1, %10, %20 |
| Payment Types | 4 | Nakit, Kredi Kartı, Havale/EFT, Veresiye |
| Admin User | 1 | admin@emareposs.com / 123456 |

### İçerik Verileri
| Veri | Adet | Detay |
|------|------|-------|
| Categories | 3 | Yiyecek, İçecek, Tatlı |
| Products | 10 | Adana Kebap, Lahmacun, vs. (fiyat: 25–350 ₺) |
| Table Region | 1 | "Salon" |
| Restaurant Tables | 5 | Masa 1–5 (4 kişilik, boş) |
| Customers | 5 | Ahmet Yılmaz, Mehmet Kaya vb. + 1 kurumsal |
| Firms | 3 | Metro Toptan, Baktat Gıda, İçecek Dünyası |

### İşlem Verileri (Son 30 Gün)
| Veri | Adet | Detay |
|------|------|-------|
| Cash Registers | 31 | Her gün 1 kasa (son gün açık, diğerleri kapalı) |
| Sales | ~439 | Günde 8–20 satış, nakit / kart karışık |
| Sale Items | ~1318 | Her satışta 1–5 ürün |
| Orders | ~228 | Satışların ~%50'si sipariş olarak da oluşturulmuş |
| Order Items | ~684 | |
| Kitchen Orders | 3 | 2 pending, 1 preparing (mutfak ekranı için) |
| Stock Movements | 5 | İlk 5 ürün için giriş hareketi |

---

## BÖLÜM 8 — MİMARİ NOTLAR

### Multi-Tenant Akış
1. Kullanıcı login olur → `PosLoginController` session'a `tenant_id` ve `branch_id` yazar
2. Her istek `ResolveTenant` middleware'den geçer → session güncellenir
3. Controller'lar `session('branch_id')` ve `session('tenant_id')` kullanır
4. Tüm sorgularda `where('branch_id', $branchId)` filtresi uygulanır

### Satış Akışı
1. POS ekranında ürünler seçilir (barkod / arama)
2. Müşteri seçilebilir (opsiyonel)
3. Ödeme yöntemi seçilir (nakit / kart / karışık / veresiye)
4. `SaleService::createSale()` çağrılır:
   - Sale kaydı oluşturulur
   - SaleItem'lar oluşturulur
   - Stok düşülür
   - Müşteri bakiyesi güncellenir (veresiye ise)
   - Fiş no otomatik üretilir

### Masa Akışı
1. Masa listesinden masa seçilir
2. Masa açılır → `TableSession` oluşturulur → masa durumu `occupied`
3. Sipariş eklenir → `Order` + `OrderItem` oluşturulur
4. Mutfak ekranında sipariş görünür
5. Ödeme alınır → Satış oluşturulur, masa kapanır → durum `empty`
6. Masa transfer edilebilir (başka masaya taşıma)

### Kasa Akışı
1. Gün başında kasa açılır → açılış tutarı girilir
2. Gün boyunca satışlar kasaya kaydedilir
3. Gün sonunda kasa kapatılır → sayım tutarı girilir
4. Beklenen tutar vs sayılan tutar farkı hesaplanır
5. Z Raporu oluşturulur

---

## BÖLÜM 9 — DÜZELTILMIŞ BUGLAR

Önceki oturumlarda tespit edilen ve düzeltilen buglar:

| # | Dosya | Hata | Düzeltme |
|---|-------|------|----------|
| 1 | `CustomerController` | `withSum('sales', 'total')` | → `'grand_total'` |
| 2 | `KitchenController` | `completed` enum order_items'da yok | → `served` |
| 3 | `SaleController list()` | `$summaryStats` eksik + lazy loading | → `items` eager loading eklendi |
| 4 | `sales/list.blade.php` | `discount_amount` / `vat_amount` yanlış alan | → `discount_total` / `vat_total` |
| 5 | `sales/list.blade.php` | İade URL `/pos/sales/` | → `/pos/sale/` |
| 6 | `products/index.blade.php` | `$product->image` | → `$product->image_url` |
| 7 | `customers/index.blade.php` | `sales_sum_total` | → `sales_sum_grand_total` |
| 8 | `kitchen/index.blade.php` | AJAX URL `pos/kitchen/order` | → `/kitchen/order` |
| 9 | `CashRegisterController` | JSON response | → `redirect()` (form POST uyumlu) |
| 10 | `SettingController` | JSON response | → `redirect()` |
| 11 | `settings/index.blade.php` | `$branch->email/$branch->tax_number` | → `$branch->city/$branch->district` |
| 12 | `DayOperationController` | `branch_id` filtresi eksik | → eklendi + `with('user')` |
| 13 | `ReportController` | `HOUR(sold_at)` MySQL syntax | → `strftime('%H', sold_at)` SQLite uyumlu |
| 14 | `CashReportController show()` | `branch_id` filtresi eksik | → eklendi |
| 15 | `ReportController` | Tarih default `Carbon::today()` | → `Carbon::now()->startOfMonth()` |
| 16 | `SaleController list()` | Tarih filtre `date_from/date_to` | → `start_date/end_date` (view ile uyumlu) |
| 17 | `bootstrap/app.php` | "Route [login] not defined" hatası | → `redirectGuestsTo('/login')` eklendi |

### Mevcut Durum
- **Tüm 18 sayfa HTTP 200 döndürüyor** ✅
- **67 route tamamı çalışıyor** ✅
- **Laravel log'da 0 hata** ✅
- **Demo data ile tüm sayfalar dolu görünüyor** ✅

---

## BÖLÜM 10 — TASARIM SİSTEMİ (Design System)

> Kaynak dosya: `DESIGN_GUIDE.md`  
> Tüm sayfalarda uygulanmalıdır.

### 10.1 Renk Paleti (Brand Colors)

| Token | HEX | Kullanım |
|-------|-----|----------|
| `brand-50` | `#eef2ff` | Çok açık arka plan, hover bg |
| `brand-100` | `#e0e7ff` | Açık arka plan |
| `brand-200` | `#c7d2fe` | Border (secondary buton) |
| `brand-300` | `#a5b4fc` | İkon accent |
| `brand-400` | `#818cf8` | Hover accent |
| `brand-500` | `#6366f1` | **Ana marka rengi (Primary)** |
| `brand-600` | `#4f46e5` | Hover / active primary |
| `brand-700` | `#4338ca` | Koyu primary, secondary buton text |
| `brand-800` | `#3730a3` | Koyu bg accent |
| `brand-900` | `#312e81` | Çok koyu bg |
| `brand-950` | `#1e1b4b` | En koyu bg |
| `purple-600` | `#9333ea` | Gradient bitiş |

### 10.2 Ek Durum Renkleri
| Renk | HEX | Kullanım |
|------|-----|----------|
| `green-500` | `#22c55e` | Başarı / aktif |
| `amber-500` | `#f59e0b` | Uyarı |
| `red-500` | `#ef4444` | Hata |

### 10.3 Gradyanlar

```css
/* Primary buton / logo */
background: linear-gradient(to right, #6366f1, #9333ea);

/* Gradient metin (başlıklarda) */
background: linear-gradient(135deg, #4f46e5, #7c3aed, #6d28d9);
-webkit-background-clip: text;
-webkit-text-fill-color: transparent;

/* Hero / CTA koyu arka plan */
background: linear-gradient(-45deg, #0f0a2e, #1e1b4b, #1e1b4b, #312e81);
background-size: 400% 400%;
animation: gradient 8s ease infinite;
```

### 10.4 Tipografi
- **Font**: Inter (Google Fonts) — wght@300;400;500;600;700;800;900
- **Body default**: `font-sans antialiased text-gray-800`

| Kullanım | Tailwind Class | Weight |
|----------|----------------|--------|
| Hero H1 | `text-5xl lg:text-7xl font-extrabold` | 800 |
| Section H2 | `text-4xl lg:text-5xl font-bold` | 700 |
| Card H3 | `text-xl font-bold` | 700 |
| Buton Text | `text-sm font-semibold` | 600 |
| Body | `text-lg text-gray-600` | 400 |

### 10.5 Buton Stilleri

```html
<!-- Primary (Filled) -->
<button class="px-8 py-4 rounded-2xl text-lg font-semibold text-white
               bg-gradient-to-r from-brand-500 to-purple-600
               shadow-xl shadow-brand-500/30
               hover:shadow-brand-500/50 hover:scale-105
               transition-all duration-300">

<!-- Secondary (Outlined) -->
<button class="px-5 py-2.5 rounded-xl text-sm font-semibold
               text-brand-700 border-2 border-brand-200
               hover:border-brand-500 hover:bg-brand-50
               transition-all duration-300">

<!-- Ghost (Koyu BG üzerinde) -->
<button class="px-8 py-4 rounded-2xl text-lg font-semibold text-white
               bg-white/20 border-2 border-white/50
               hover:bg-white/30
               transition-all duration-300">
```

### 10.6 Kart Stilleri

```html
<!-- Feature Card -->
<div class="bg-white rounded-3xl p-8 border border-gray-100
            shadow-lg shadow-gray-100/50
            hover:shadow-xl hover:shadow-brand-100/50 hover:border-brand-100
            transition-all duration-500">

<!-- Module Card -->
<div class="bg-white rounded-2xl p-6 border border-gray-100
            shadow-lg shadow-gray-100/50
            hover:shadow-xl hover:border-brand-200
            transition-all duration-500">

<!-- Pricing Card (Popüler) -->
<div class="bg-gradient-to-br from-brand-500 to-purple-600
            rounded-3xl p-8 shadow-2xl text-white
            ring-4 ring-brand-200 scale-105">

<!-- Glass Card (Koyu BG) -->
<!-- background: rgba(255,255,255,0.1); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.2) -->
```

### 10.7 Border Radius Tablosu
| Eleman | Tailwind | Pixel |
|--------|----------|-------|
| Küçük buton | `rounded-xl` | 12px |
| Büyük buton | `rounded-2xl` | 16px |
| Feature card | `rounded-3xl` | 24px |
| Module card | `rounded-2xl` | 16px |
| İkon kutusu | `rounded-2xl` | 16px |
| Logo kutusu | `rounded-xl` | 12px |
| Input / Form | `rounded-xl` | 12px |
| Badge | `rounded-full` | 9999px |

### 10.8 İkon Kutu Stili
```html
<div class="w-14 h-14 rounded-2xl
            bg-gradient-to-br from-brand-500/10 to-purple-500/10
            flex items-center justify-center text-brand-600 text-2xl
            transition-all duration-300
            group-hover:from-brand-500 group-hover:to-purple-600
            group-hover:text-white group-hover:scale-110 group-hover:rotate-[-5deg]">
    <i class="fas fa-chart-line"></i>
</div>
```

### 10.9 Logo
```html
<div class="w-10 h-10 rounded-xl bg-gradient-to-br from-brand-500 to-purple-600
            flex items-center justify-center shadow-lg shadow-brand-500/30">
    <span class="text-white font-bold text-lg">EF</span>
</div>
<span class="text-xl font-bold text-gray-900">
    Emare <span class="gradient-text">Finance</span>
</span>
```

### 10.10 Form Elemanları

```html
<!-- Text Input -->
<input class="w-full px-4 py-3 rounded-xl border border-gray-200
              focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20
              outline-none transition-all duration-300
              text-gray-800 placeholder-gray-400">

<!-- Form Submit Button -->
<button class="w-full px-8 py-4 rounded-xl
               bg-gradient-to-r from-brand-500 to-purple-600
               text-white font-semibold shadow-lg shadow-brand-500/30
               hover:shadow-brand-500/50 hover:scale-[1.02]
               transition-all duration-300">
```

### 10.11 Animasyonlar

```css
/* Float (dekoratif blob) */
@keyframes float {
    0%, 100% { transform: translateY(0px); }
    50%       { transform: translateY(-20px); }
}
/* animate-float: 6s | animate-float-delayed: 6s 2s | animate-float-slow: 8s 1s */

/* Gradient BG */
@keyframes gradient {
    0%, 100% { background-position: 0% 50%; }
    50%       { background-position: 100% 50%; }
}

/* Fade Up */
@keyframes fadeUp {
    0%   { opacity: 0; transform: translateY(30px); }
    100% { opacity: 1; transform: translateY(0); }
}

/* Slide Right */
@keyframes slideRight {
    0%   { opacity: 0; transform: translateX(-30px); }
    100% { opacity: 1; transform: translateX(0); }
}

/* Pulse Soft */
@keyframes pulseSoft {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.7; }
}
```

### 10.12 Scrollbar Stili

```css
::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-track { background: #1e1b4b; }
::-webkit-scrollbar-thumb { background: #4f46e5; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #6366f1; }
```

### 10.13 Tailwind Config (CDN inline)

```javascript
tailwind.config = {
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                brand: {
                    50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe',
                    300: '#a5b4fc', 400: '#818cf8', 500: '#6366f1',
                    600: '#4f46e5', 700: '#4338ca', 800: '#3730a3',
                    900: '#312e81', 950: '#1e1b4b',
                }
            },
            animation: {
                'float':         'float 6s ease-in-out infinite',
                'float-delayed': 'float 6s ease-in-out 2s infinite',
                'float-slow':    'float 8s ease-in-out 1s infinite',
                'gradient':      'gradient 8s ease infinite',
                'fade-up':       'fadeUp 0.6s ease-out forwards',
                'slide-right':   'slideRight 0.6s ease-out forwards',
                'pulse-soft':    'pulseSoft 3s ease-in-out infinite',
            },
            // keyframes bloğu da animate ile paralel tanımlı
        }
    }
}
```

### 10.14 Bağımlılıklar (CDN)

| Kütüphane | Versiyon | Kaynak |
|-----------|----------|--------|
| Tailwind CSS | latest | `cdn.tailwindcss.com` |
| Alpine.js | 3.x | `cdn.jsdelivr.net/npm/alpinejs@3.x.x` |
| Alpine Collapse | 3.x | `cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x` |
| Alpine Intersect | 3.x | `cdn.jsdelivr.net/npm/@alpinejs/intersect@3.x.x` |
| Font Awesome | 6.5.1 | `cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css` |
| Inter Font | — | Google Fonts |
| Chart.js | 4.x | CDN |

---

## BÖLÜM 11 — KOMUT REFERANSLARI

```bash
# Sunucu başlat
cd "/Users/emre/Desktop/adisyon sistemi/pos-system"
php artisan serve --port=8080

# Veritabanı sıfırla + demo data yükle
php artisan migrate:fresh --seed

# Cache temizle
php artisan cache:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear

# View cache oluştur
php artisan view:cache

# Route listesi gör
php artisan route:list

# Tinker (DB sorgulama)
php artisan tinker

# Log izle (gerçek zamanlı)
tail -f storage/logs/laravel.log

# Migration durumu gör
php artisan migrate:status
```

---

## BÖLÜM 12 — YAPILACAKLAR LİSTESİ

### Tasarım Yenileme (Devam Eden)
- [ ] **Login sayfası** yeni tasarıma taşı (DESIGN_GUIDE.md)
- [ ] **Dashboard** yeni tasarıma taşı
- [ ] **POS satış ekranı** yeni tasarıma taşı
- [ ] **Masalar** sayfası yeni tasarıma taşı
- [ ] **Mutfak ekranı** yeni tasarıma taşı
- [ ] Diğer tüm Blade view dosyaları (toplamda 21 view)
- [ ] `layouts/app.blade.php` sidebar brand renk güncellemesi

### Gelecek Özellikler (Tablolar Hazır, UI Yok)
- [ ] Kampanya modülü (`campaigns` tablosu hazır)
- [ ] Sadakat programı (`loyalty_*` tabloları hazır)
- [ ] Gelir / Gider takibi (`incomes` + `expenses` tabloları hazır)
- [ ] Donanım entegrasyonu (`hardware_*` tabloları hazır)

### Gelecek Özellikler (Planlanan)
- [ ] Fiş yazdırma (ESC/POS)
- [ ] Barkod okuyucu entegrasyonu
- [ ] Staff (personel) yönetimi sayfası
- [ ] Multi-branch tam entegrasyon (şubeler arası ürün/stok transfer)
- [ ] Export (Excel / PDF rapor çıktısı)
- [ ] API endpoint'leri (mobil uygulama için)
- [ ] E-Fatura modülü (tablolar TECHNICAL_SPEC'te tanımlı)
- [ ] SMS modülü (tablolar TECHNICAL_SPEC'te tanımlı)
- [ ] Stok sayım modülü
- [ ] Alış fatura modülü
- [ ] Dijital tabela (signage) modülü
- [ ] Teklif modülü
- [ ] Tekrarlayan fatura modülü
- [ ] Müşteri segmentasyonu

---

## BÖLÜM 13 — DOSYA KONUMLARI ÖZET

| Ne | Nerede |
|----|--------|
| **Proje root** | `/Users/emre/Desktop/adisyon sistemi/pos-system/` |
| **Bu hafıza dosyası** | `/Users/emre/Desktop/adisyon sistemi/emarepos_hafiza.md` |
| **Tasarım rehberi** | `/Users/emre/Desktop/adisyon sistemi/DESIGN_GUIDE.md` |
| **Teknik şartname** | `/Users/emre/Desktop/adisyon sistemi/TECHNICAL_SPEC.md` |
| **Controllers** | `pos-system/app/Http/Controllers/Pos/` (17 dosya) |
| **Models** | `pos-system/app/Models/` (34 dosya) |
| **Views** | `pos-system/resources/views/pos/` (21 blade dosyası) |
| **Routes** | `pos-system/routes/web.php` (tek dosya, 67 route) |
| **Services** | `pos-system/app/Services/` (3 dosya) |
| **Migrations** | `pos-system/database/migrations/` (41 dosya) |
| **Seeder** | `pos-system/database/seeders/DatabaseSeeder.php` |
| **DB dosyası** | `pos-system/database/database.sqlite` |
| **Layout** | `pos-system/resources/views/pos/layouts/app.blade.php` |
| **Helpers** | `pos-system/app/helpers.php` |
| **Middleware** | `pos-system/app/Http/Middleware/` (3 dosya) |
| **Laravel log** | `pos-system/storage/logs/laravel.log` |

---

## BÖLÜM 14 — PROJE GEÇMİŞİ (Oturum Özeti)

### Geliştirme Sırası
1. **Backend altyapısı**: 34 model, 41 migration, 17 controller, 3 service, 3 middleware kuruldu
2. **Tüm route'lar**: 67 route `routes/web.php`'e eklendi
3. **Seeder**: Kapsamlı demo data seeder (`DatabaseSeeder.php`) yazıldı
4. **View'lar**: 21 Blade view dosyası oluşturuldu
5. **Bug düzeltmeleri**: 17 kritik hata tespit edilip düzeltildi
6. **Tasarım sistemi**: `DESIGN_GUIDE.md` hazırlandı, `app.blade.php` güncellendi
7. **Teknik şartname**: `TECHNICAL_SPEC.md` hazırlandı (production yapısı ve genişletilmiş şema)
8. **Durum (3 Mart 2026)**: Tüm 18 sayfa çalışıyor, tasarım yenileme devam ediyor
9. **7 Mart 2026 — Yeni özellikler**:
   - Ürün şube yönetimi (branch_product pivot CRUD, fiyat karşılaştırma)
   - Kategori hiyerarşisi (3 seviye: Grup → Marka → Ürün)
   - Cari gruplama (firm_groups tablosu, CRUD, filtre)
   - Çoklu fiyat (ProductPrice modeli, CRUD UI)
   - İskonto modal, Son Fişler, İade Al, Barkod fiyat seçimi

---

## BÖLÜM — RAKİP ANALİZİ: BENİMPOS.COM vs EMARE POS (7 Mart 2026)

### benimpos.com Genel Bilgi
- **Fiyatlandırma**: Lite (ücretsiz/sınırlı), Yıllık ₺5.349,90, Ömür Boyu ₺11.749,90
- **Platform**: Web + Masaüstü (offline) + Mobil (Android/iOS)
- **Hedef**: Küçük-orta perakende işletmeler

### Özellik Karşılaştırma

| Özellik | BenimPOS | Emare POS | Durum |
|---------|----------|-----------|-------|
| Barkodlu satış | ✅ | ✅ | Eşit |
| Stok yönetimi | ✅ | ✅ | Eşit |
| Cari hesap takibi | ✅ | ✅ | Eşit |
| Kategori/Ürün grubu | ✅ | ✅ (3 seviye!) | Emare avantajlı |
| Raporlama | ✅ (7 farklı yöntem) | ⚠️ (3 temel rapor) | **Eksik** |
| Şube yönetimi | ✅ | ✅ | Eşit |
| Şubeler arası transfer | ✅ | ❌ | **Eksik** |
| Personel takibi (detaylı) | ✅ (satış, ödeme, ürün okutma) | ⚠️ (basit personel listesi) | **Eksik** |
| Alış faturası ekleme | ✅ | ❌ | **Eksik** |
| Stok sayımı | ✅ | ❌ | **Eksik** |
| Hazır barkodlu ürün veritabanı | ✅ (3M+ ürün) | ❌ | **Eksik** |
| Mobil uygulama | ✅ (Android + iOS) | ❌ | **Eksik** |
| Masaüstü uygulama (offline) | ✅ | ❌ (sadece web) | **Eksik** |
| E-fatura entegrasyonu | ✅ | ❌ | **Eksik** |
| Yazarkasa POS entegrasyonu | ✅ | ❌ | **Eksik** |
| Şüpheli işlem raporlama | ✅ | ❌ | **Eksik** |
| Masa yönetimi | ❌ (perakende odaklı) | ✅ | **Emare avantajlı** |
| Mutfak ekranı | ❌ | ✅ | **Emare avantajlı** |
| Multi-tenant SaaS | ❌ (tek işletme) | ✅ | **Emare avantajlı** |
| Kampanya/Sadakat | ❌ | ✅ (modüler) | **Emare avantajlı** |
| Çoklu fiyat (ürün bazlı) | ❌ | ✅ | **Emare avantajlı** |
| İskonto sistemi | ⚠️ | ✅ (grid + manuel) | **Emare avantajlı** |
| Gelir/Gider takibi | ❌ | ✅ | **Emare avantajlı** |
| Geri bildirim sistemi | ❌ | ✅ | **Emare avantajlı** |
| Donanım yönetimi | ❌ | ✅ | **Emare avantajlı** |

### Kritik Eksikler (Öncelik Sırasıyla)

1. **📊 Gelişmiş Raporlama** — BenimPOS 7 farklı yöntem sunuyor. Bizde: günlük satış, kasa raporu, en çok satan var ama kar/zarar analizi, personel bazlı satış raporu, kategori raporu, dönemsel karşılaştırma, grafik rapor eksik.

2. **📦 Stok Sayımı** — Fiziksel envanter sayımı yapıp farkları otomatik düzeltme. Depo yönetimi için kritik.

3. **🔄 Şubeler Arası Ürün Transferi** — Bir şubeden diğerine stok transferi. Çoklu şube kullanan işletmeler için zorunlu.

4. **🧾 Alış Faturası Yönetimi** — Tedarikçiden mal alımını fatura olarak kaydetme, düzeltme, silme. Cari hesap entegrasyonu ile birlikte çalışmalı.

5. **👨‍💼 Personel Detaylı Takip** — Personel bazında: toplam satış, ödeme aldığı tutarlar, okutulan ürün sayısı, performans raporları.

6. **📱 Mobil Uygulama** — Android/iOS native veya PWA. İleride düşünülecek.

7. **🖥️ Masaüstü Uygulama** — Electron ile offline çalışan masaüstü versiyonu. İleride düşünülecek.

8. **🧾 E-fatura / Yazarkasa** — Türkiye yasal gereksinimleri. 3. parti entegrasyon gerekli.

9. **🔍 Şüpheli İşlem Raporlama** — İptal, iade, düşük fiyat gibi şüpheli işlemleri raporlama.

10. **📊 Hazır Ürün Veritabanı** — Barkod ile 3M+ ürün bilgisine erişim (3. parti API gerekli).

### Nerede Öndeyiz?
- **Restoran/Kafe**: Masa yönetimi + mutfak ekranı = büyük avantaj
- **SaaS Model**: Multi-tenant + plan bazlı = daha geniş pazar
- **Modüler Yapı**: İşletme tipine göre modül aç/kapat
- **Modern UI**: Tailwind + Alpine.js = hızlı, responsive
- **Kampanya/Sadakat**: Müşteri bağlılık programları
- **Gelir/Gider Takibi**: İşletme finansal yönetimi
- **Çoklu Fiyat + İskonto**: Esnek fiyatlandırma

---

*Bu dosyayı farklı bir yere taşısa bile içeriği bütün projeyi anlatır. Yeni AI oturumunda sadece "emarepos_hafiza.md dosyasını oku ve devam et" demek yeterlidir.*

---

## BÖLÜM — SON GELİŞMELER (9 Mart 2026)

### Kapsamlı Güvenlik Denetimi & Bug Fix (commit 8bb1e5f)

**Migration Eklendi:**
- `2026_03_09_000001_add_branch_id_to_incomes_expenses_stock_movements` — incomes, expenses, stock_movements tablolarına `branch_id` FK eklendi

**Kritik Bug Düzeltmeleri:**
- **BUG-01:** Income/Expense/StockMovement modellerine `branch_id` fillable eklendi, controller'larda branch_id filtreleme
- **BUG-02:** SaleController payment_method validation: `in:` → `regex:` (other_xxx custom ödeme tipleri desteği)
- **BUG-03:** CashReportController tüm sorgulara `branch_id` filtresi eklendi
- **BUG-04:** Sale show/refund — branch_id yetkilendirme (403) kontrolü

**Orta Seviye Bug Düzeltmeleri:**
- **BUG-05:** StockController index/store branch_id filtresi
- **BUG-07:** StaffController branch_id filtresi
- **BUG-08:** SaleController summaryStats tarih/ödeme filtrelerini doğru kullanıyor
- **BUG-09:** Feedback modeline `BelongsToTenant` trait eklendi
- **BUG-11:** PurchaseInvoiceController N+1 sorgu optimizasyonu (Product::whereIn)

**Eksik CRUD Endpoint'leri:**
- Firma silme: `DELETE /firms/{firm}` (soft-delete, bakiye kontrolü)
- Müşteri silme: `DELETE /customers/{customer}` (soft-delete, bakiye kontrolü)
- Gelir güncelleme: `PUT /income-expense/income/{income}`
- Gider güncelleme: `PUT /income-expense/expense/{expense}`

**StockMovement branch_id Tamamlama:**
Tüm StockMovement::create çağrılarına (9 konum) branch_id eklendi:
- SaleService (satış, iade, iptal)
- StockController (manuel hareket)
- StockCountController (sayım düzeltme)
- StockTransferController (gönderen/alan şube)
- PurchaseInvoiceController (alış faturası)

**AUDIT_REPORT.md:** Detaylı denetim raporu repo'ya eklendi (28 bulgu: 4 kritik, 8 orta, 8 iyileştirme, 8 eksik özellik)

### Kalan İyileştirmeler (Düşük Öncelik)
~~BUG-10: posAjax response standardizasyonu~~ ✅
~~BUG-12: ReportController query clone optimizasyonu~~ ✅
~~IMP-01~08: Çeşitli N+1, DB::transaction, withQueryString iyileştirmeleri~~ ✅
~~MISS-03: Şube silme endpoint'i~~ ✅
~~MISS-05~08: StockCount izolasyon, toplu fiyat güncelleme, activity log, rate limiting~~ ✅

### Tüm İyileştirmeler Tamamlandı (commit 3d4710e)

**Bug Düzeltmeleri:**
- **BUG-10:** posAjax — 422 validation error detaylarını parse ediyor (her iki layout'ta)
- **BUG-12:** ReportController periodComparison — 8 sorgu → 1 tek selectRaw sorgusu

**Performans İyileştirmeleri:**
- **IMP-01:** SaleController searchProducts — N+1 fix (eager-load branches)
- **IMP-02:** withQueryString() — 11 controller'a eklendi (sayfalama filtreleri artık korunuyor)
- **IMP-03:** StockController store — DB::transaction ile atomik stok güncellemesi
- **IMP-05:** suspiciousTransactions — array_merge → Collection::concat optimizasyonu
- **IMP-07:** ApiResponse trait oluşturuldu — base Controller'a eklendi, tüm controller'lar kullanabiliyor

**Yeni Özellikler:**
- **MISS-03:** Şube silme: `DELETE /branches/{branch}` (soft-delete, satış/personel kontrolü)
- **MISS-05:** StockCount show/apply/destroy — branch_id izolasyonu (403 check)
- **MISS-07:** Activity Log sistemi:
  - `activity_logs` tablosu (migration)
  - `ActivityLog` modeli (`::log()` statik metod)
  - 7 controller'a entegre (Sale, Product, Firm, Customer, Stock, IncomeExpense)
- **MISS-08:** API Rate Limiting: `throttle:120,1` tüm POS route'larına eklendi
- **MISS-06:** Toplu fiyat güncelleme zaten mevcuttu (bulkPriceUpdate)

### 📊 AUDIT_REPORT.md — TAMAMI ÇÖZÜLDÜ ✅
28/28 bulgunun tamamı düzeltildi veya mevcut olduğu tespit edildi.

### Derin Fonksiyonel Analiz & 13 Bug Düzeltmesi (commit 531ca96)

Tüm controller fonksiyonları migration şemalarıyla karşılaştırılarak analiz edildi. 13 yeni hata tespit ve düzeltildi:

**Schema Migration (2026_03_09_000003_fix_critical_schema_bugs):**
- `account_transactions.customer_id` → nullable (firma ödeme SQL hatası)
- `purchase_invoices.invoice_no` → nullable
- `purchase_invoices.status` → string(20) (enum 'received' eksikti)
- `order_items.status` → string(20) (enum 'paid' eksikti)

**Controller Düzeltmeleri:**
- **BUG-3:** PurchaseInvoiceController — `Firm::get(['id','name','type'])` → type sütunu yok, kaldırıldı
- **BUG-6:** PurchaseInvoiceController — firma bakiye yönü düzeltildi (increment↔decrement swap)
- **BUG-7:** PurchaseInvoiceController — update status validation genişletildi (draft,received,approved,returned,cancelled)
- **BUG-8:** IncomeExpenseController — ActivityLog `$income->description` → `$income->note`
- **BUG-9:** CustomerController — store'a district+notes, update'e city+district eklendi
- **BUG-10:** DayOperationController — total_sales/cash/card/avg_basket/hourly sorgularına `where('status','completed')` eklendi
- **BUG-11:** AdminController — `Sale::whereDate('created_at')` → `sold_at` düzeltildi
- **BUG-12:** AdminController — `withoutGlobalScope('tenant')` eklendi (süper admin tüm tenant verisini görsün)
- **BUG-13:** ReportController suspiciousTransactions — 5 sorgunun tamamında `created_at` → `sold_at`

### Derin Analiz Round 2 — 13 Bug Daha (commit 757dabd)

Tüm controller fonksiyonları tekrar tarandı. 13 yeni bug tespit ve düzeltildi:

**Schema Migration (2026_03_09_000004):**
- `tenants.status` enum → string(20) ('trial' değerine izin vermek için)

**Kritik:**
- **BUG-1:** tenants.status enum'da 'trial' yoktu → yeni tenant oluşturulamıyordu, string'e çevrildi

**Yüksek:**
- **BUG-2+3:** AdminController users+feedbacks → `withoutGlobalScope('tenant')` eklendi (süper admin tüm verileri görsün)
- **BUG-4:** StaffController performance — 6 sorguda `created_at` → `sold_at` (personel istatistikleri yanlış geliyordu)
- **BUG-5:** ReportController profitLoss — Income/Expense `created_at` → `date` (kâr-zarar raporu yanlış tarih kullanıyordu)

**Orta:**
- **BUG-6:** DayOperationController — cash/card mixed ödeme sorunu → `sum(cash_amount)` / `sum(card_amount)` kullanıldı
- **BUG-7:** ReportController categoryStats — INNER JOIN → LEFT JOIN + COALESCE (kategorisiz ürünler kayboluyordu)
- **BUG-8:** FeedbackController — `session('user_name')` → `auth()->user()?->name` (her zaman NULL geliyordu)
- **BUG-9:** CashRegisterController — credit_total → `sum(credit_amount)` (mixed ödemeler kaçıyordu)
- **BUG-10:** ReportController daily — cash/card/credit → sum(amount sütunları) (mixed ödemeler)
- **BUG-11+12:** StockCount/StockTransfer kod üretimi — `DB::transaction` + `lockForUpdate` (race condition)
- **BUG-13:** StockTransferController approve — gönderen şube pivot stoğu da düşürülüyor

### Derin Analiz Round 3 — 12 Bug Daha (commit a3344d6)

Tüm controller fonksiyonları üçüncü kez tarandı. 12 yeni bug tespit ve düzeltildi:

**Yüksek (4 — Güvenlik/IDOR):**
- **BUG-1:** StockTransferController approve — `$product->decrement('stock_quantity')` global stok kaybına yol açıyordu. Kaldırıldı, sadece şube pivot güncelleniyor.
- **BUG-2:** ProductController deleteVariantValue — tenant scope yoktu (IDOR). Eklendi: `$variantValue->type->tenant_id` kontrolü.
- **BUG-3:** KitchenController updateItemStatus/updateOrderStatus — tenant scope yoktu (IDOR). Eklendi: `$item->order->tenant_id` / `$order->tenant_id` kontrolü.
- **BUG-4:** ProductController updatePrice/destroyPrice — ürün-fiyat sahiplik kontrolü yoktu (IDOR). Eklendi: `$price->product_id !== $product->id` kontrolü. deleteVariantType'a da tenant kontrolü eklendi.

**Orta (6 — Mantık Hataları):**
- **BUG-5:** SaleController searchProducts — `show_on_pos` filtresi eksikti. POS'ta gösterilmeyen ürünler aranabiliyordu.
- **BUG-6:** CashReportController — credit toplamı `payment_method='credit'` ile hesaplanıyordu, mixed ödemeler kaçıyordu → `sum('credit_amount')` kullanıldı.
- **BUG-7:** SaleController list summaryStats — cash/card `payment_method` filtresiydi, mixed ödemeler kaçıyordu → `sum('cash_amount')` / `sum('card_amount')` kullanıldı.
- **BUG-8:** DashboardController lowStockCount — stock=0 ve critical=0 olan ürünleri de sayıyordu (0<=0=true) → `where('critical_stock', '>', 0)` eklendi.
- **BUG-9:** PurchaseInvoiceController update — 'received' durumundan 'cancelled'/'returned'a geçişte stok ve firma bakiyesi geri alınmıyordu → rollback mantığı eklendi.
- **BUG-10:** CashRegisterService closeRegister — expectedAmount nakit gelir/giderleri dahil etmiyordu → Income/Expense hesaplaması eklendi.

**Düşük (2 — Validation):**
- **BUG-11:** Tüm controller'lardaki `exists:tablo,id` kuralları tenant scope bypass'ıydı → `Rule::exists(...)->where('tenant_id', session('tenant_id'))` ile değiştirildi (7 controller, 10+ kural).
- **BUG-12:** FeedbackController category/priority `nullable` idi ama enum sütunları NOT NULL → `sometimes` ile değiştirildi.

**Etkilenen Dosyalar (12):**
StockTransferController, ProductController, KitchenController, SaleController, CashReportController, DashboardController, PurchaseInvoiceController, CashRegisterService, FeedbackController, StockCountController, StockController, IncomeExpenseController