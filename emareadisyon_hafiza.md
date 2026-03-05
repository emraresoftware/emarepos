# Emare Adisyon — Proje Hafıza Dosyası

> 🔗 **Ortak Hafıza:** [`EMARE_ORTAK_HAFIZA.md`](/Users/emre/Desktop/Emare/EMARE_ORTAK_HAFIZA.md) — Tüm Emare ekosistemi, sunucu bilgileri, standartlar ve proje envanteri için bak.


> **Son Güncelleme:** 3 Mart 2026
> **Proje Durumu:** Aktif geliştirme — tüm sayfalar çalışır durumda, tasarım yenileme süreci devam ediyor
> **Geliştirici Notu:** Bu dosya, projenin tüm teknik detaylarını içerir. Yeni bir oturumda "bu dosyayı oku ve kaldığımız yerden devam et" demen yeterlidir.

---

## 1. PROJE TANIMI

**Emare Adisyon** (Emare POS), restoran/kafe/işletmeler için geliştirilmiş web tabanlı bir **POS (Point of Sale) ve Adisyon Yönetim Sistemi**'dir.

### Temel Özellikler
- Hızlı satış (barkod/ürün arama ile)
- Masa yönetimi (bölge, açma, sipariş, ödeme, transfer)
- Mutfak ekranı (gerçek zamanlı sipariş takibi)
- Kasa yönetimi (aç/kapat, Z raporu)
- Müşteri yönetimi (cari hesap, bakiye, tahsilat)
- Cari/firma yönetimi (tedarikçi takibi)
- Stok/depo yönetimi (giriş/çıkış hareketleri)
- Raporlama (günlük satış, ödeme yöntemleri, en çok satan ürünler, kategori bazlı)
- Kullanıcı yönetimi (rol tabanlı)
- Şube yönetimi (multi-branch desteği)
- Kategori ve ürün yönetimi
- Gün sonu işlemleri

### İş Modeli
- **Multi-Tenant** mimari (birden fazla işletme destekler)
- **Multi-Branch** (her tenant'ın birden fazla şubesi olabilir)
- Plan bazlı lisanslama (Başlangıç/Profesyonel/Kurumsal)

---

## 2. TEKNİK ALTYAPI

| Bileşen | Teknoloji | Versiyon |
|---------|-----------|----------|
| **Backend** | Laravel (PHP) | ^12.0 (PHP ^8.2) |
| **Veritabanı** | SQLite (geliştirme) | — |
| **Frontend CSS** | Tailwind CSS | CDN (tailwind.config inline) |
| **JS Framework** | Alpine.js | 3.x (CDN) |
| **Grafik** | Chart.js | 4.x (CDN) |
| **İkonlar** | Font Awesome | 6.5.1 (CDN) |
| **Font** | Inter (Google Fonts) | 300-900 |
| **Auth** | Laravel built-in | Session tabanlı |
| **Sunucu** | PHP built-in dev server | `php artisan serve --port=8080` |

### Proje Dizini
```
/Users/emre/Desktop/adisyon sistemi/pos-system/
```

### Çalıştırma
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

## 3. DOSYA YAPISI

### 3.1 Controllers (17 adet)
```
app/Http/Controllers/
├── Auth/
│   └── PosLoginController.php          # Giriş/Çıkış
└── Pos/
    ├── DashboardController.php         # Ana sayfa özet
    ├── SaleController.php              # Hızlı satış + satış listesi + AJAX arama
    ├── TableController.php             # Masa yönetimi
    ├── KitchenController.php           # Mutfak ekranı
    ├── CashRegisterController.php      # Kasa aç/kapat
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
├── Firm.php                # Cari/Tedarikçi
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
├── IncomeExpenseType.php   # Gelir/Gider tipi
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
├── dashboard.blade.php             # Ana sayfa özet (/ )
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
│   └── index.blade.php             # Stok/Depo (/stock)
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

### 3.6 Diğer
```
app/helpers.php              # formatCurrency() → number_format + " ₺"
bootstrap/app.php            # redirectGuestsTo('/login') middleware
database/database.sqlite     # SQLite dev veritabanı
database/seeders/DatabaseSeeder.php  # Demo data seeder (kapsamlı)
```

---

## 4. VERİTABANI ŞEMASI

### 41 Migration — Tablo Listesi

| # | Tablo | Açıklama |
|---|-------|----------|
| 1 | `tenants` | İşletme/Kiracı |
| 2 | `plans` | Abonelik planları (Başlangıç/Pro/Kurumsal) |
| 3 | `modules` | Sistem modülleri (10 adet) |
| 4 | `plan_modules` | Plan-modül ilişkisi |
| 5 | `tenant_modules` | Tenant-modül ilişkisi |
| 6 | `roles` | Roller (admin/manager/cashier/waiter/kitchen) |
| 7 | `permissions` | İzinler (41 adet) |
| 8 | `role_permissions` | Rol-izin ilişkisi |
| 9 | `branches` | Şubeler |
| 10 | `branch_modules` | Şube-modül ilişkisi |
| 11 | `users` | Kullanıcılar |
| 12 | `user_roles` | Kullanıcı-rol ilişkisi |
| 13 | `categories` | Ürün kategorileri |
| 14 | `service_categories` | Hizmet kategorileri |
| 15 | `tax_rates` | Vergi oranları (KDV %1/%10/%20) |
| 16 | `payment_types` | Ödeme türleri (Nakit/Kart/Havale/Veresiye) |
| 17 | `products` | Ürünler |
| 18 | `branch_product` | Şube-ürün ilişkisi (şubeye özel fiyat/stok) |
| 19 | `customers` | Müşteriler |
| 20 | `firms` | Cariler/Tedarikçiler |
| 21 | `staff` | Personel |
| 22 | `sales` | Satışlar |
| 23 | `sale_items` | Satış kalemleri |
| 24 | `account_transactions` | Hesap hareketleri (tahsilat vb.) |
| 25 | `stock_movements` | Stok hareketleri |
| 26 | `income_expense_types` | Gelir/gider tipleri |
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

### Kritik Tablo Kolonları

#### `sales` (Satış)
```
id, tenant_id, external_id, receipt_no, branch_id, customer_id, user_id,
payment_method (cash/card/mixed/credit), total_items, subtotal,
vat_total, additional_tax_total, discount_total, grand_total, discount,
cash_amount, card_amount, status (completed/refunded), notes,
staff_name, application, sold_at, created_at, updated_at, deleted_at
```

#### `sale_items` (Satış Kalemi)
```
id, sale_id, product_id, product_name, barcode, quantity, unit_price,
discount, vat_rate, vat_amount, additional_taxes, additional_tax_amount,
total, created_at, updated_at
```

#### `orders` (Sipariş)
```
id, tenant_id, branch_id, table_session_id, sale_id, order_number,
user_id, customer_id, status (pending/preparing/ready/served/completed/cancelled),
order_type (dine_in/takeaway/delivery), total_items, subtotal, vat_total,
discount_total, grand_total, notes, kitchen_notes, ordered_at
```

#### `order_items` (Sipariş Kalemi)
```
id, order_id, product_id, product_name, quantity, unit_price, discount,
vat_rate, vat_amount, total, status (pending/preparing/ready/served/cancelled), notes
```

#### `products` (Ürün)
```
id, tenant_id, barcode, name, description, category_id, unit,
purchase_price, sale_price, vat_rate, stock_quantity, critical_stock,
image_url, is_active, is_service
```

#### `customers` (Müşteri)
```
id, tenant_id, name, type (individual/corporate), tax_number, tax_office,
phone, email, address, city, district, balance, notes, is_active
```

#### `cash_registers` (Kasa)
```
id, tenant_id, branch_id, user_id, opening_amount, closing_amount,
expected_amount, difference, total_sales, total_cash, total_card,
total_refunds, total_transactions, status (open/closed), opened_at, closed_at
```

#### `restaurant_tables` (Masa)
```
id, tenant_id, branch_id, table_region_id, table_no, name, capacity,
status (empty/occupied/reserved/cleaning), sort_order, is_active
```

---

## 5. ROUTE HARİTASI (67 Route)

### Auth
| Method | URI | Controller | Açıklama |
|--------|-----|-----------|----------|
| GET | `/login` | PosLoginController@showLogin | Giriş sayfası |
| POST | `/login` | PosLoginController@login | Giriş işlemi |
| POST | `/logout` | PosLoginController@logout | Çıkış |

### Dashboard
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/` | pos.dashboard | Ana sayfa özet |

### Hızlı Satış (POS)
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/pos` | pos.sales | POS satış ekranı |
| POST | `/pos/sale` | pos.sales.store | Satış kaydet |
| GET | `/pos/products/search` | pos.products.search | Ürün arama (AJAX) |
| GET | `/pos/customers/search` | pos.customers.search | Müşteri arama (AJAX) |
| GET | `/pos/recent-sales` | pos.sales.recent | Son satışlar (AJAX) |
| GET | `/pos/sale/{sale}` | pos.sales.show | Satış detayı (AJAX) |
| POST | `/pos/sale/{sale}/refund` | pos.sales.refund | İade (AJAX) |
| GET | `/pos/sales-list` | pos.sales.list | Satış geçmişi sayfası |

### Masalar
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/tables` | pos.tables | Masa listesi |
| GET | `/tables/{table}/detail` | pos.tables.detail | Masa detay |
| POST | `/tables/{table}/open` | pos.tables.open | Masa aç |
| POST | `/tables/{table}/order` | pos.tables.order | Sipariş ekle |
| POST | `/tables/{table}/pay` | pos.tables.pay | Ödeme al |
| POST | `/tables/{table}/transfer` | pos.tables.transfer | Masa transferi |

### Mutfak
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/kitchen` | pos.kitchen | Mutfak ekranı |
| GET | `/kitchen/orders` | pos.kitchen.orders | Siparişler (AJAX) |
| POST | `/kitchen/order/{order}/status` | pos.kitchen.order.status | Sipariş durumu |
| POST | `/kitchen/item/{item}/status` | pos.kitchen.item.status | Kalem durumu |

### Kasa
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/cash-register` | pos.cash-register | Kasa sayfası |
| POST | `/cash-register/open` | pos.cash-register.open | Kasa aç |
| POST | `/cash-register/close` | pos.cash-register.close | Kasa kapat |
| GET | `/cash-register/{register}/report` | pos.cash-register.report | Kasa raporu |
| GET | `/cash-report` | pos.cash-report | Kasa rapor listesi |
| GET | `/cash-report/{register}` | pos.cash-report.show | Kasa rapor detayı |

### Ürünler
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/products` | pos.products | Ürün listesi |
| POST | `/products` | pos.products.store | Ürün oluştur |
| PUT | `/products/{product}` | pos.products.update | Ürün güncelle |
| DELETE | `/products/{product}` | pos.products.destroy | Ürün sil |

### Kategoriler
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/categories` | pos.categories | Kategori listesi |
| POST | `/categories` | pos.categories.store | Kategori oluştur |
| PUT | `/categories/{category}` | pos.categories.update | Kategori güncelle |
| DELETE | `/categories/{category}` | pos.categories.destroy | Kategori sil |

### Müşteriler
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/customers` | pos.customers | Müşteri listesi |
| POST | `/customers` | pos.customers.store | Müşteri oluştur |
| GET | `/customers/{customer}` | pos.customers.show | Müşteri detayı (AJAX/JSON) |
| PUT | `/customers/{customer}` | pos.customers.update | Müşteri güncelle |
| POST | `/customers/{customer}/payment` | pos.customers.payment | Tahsilat ekle |

### Cariler (Firmalar)
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/firms` | pos.firms | Cari listesi |
| POST | `/firms` | pos.firms.store | Cari oluştur |
| PUT | `/firms/{firm}` | pos.firms.update | Cari güncelle |
| POST | `/firms/{firm}/payment` | pos.firms.payment | Cari ödeme |

### Siparişler
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/orders` | pos.orders | Sipariş listesi |
| GET | `/orders/{order}` | pos.orders.show | Sipariş detayı (JSON) |
| PUT | `/orders/{order}/status` | pos.orders.status | Durum güncelle |

### Raporlar
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/reports` | pos.reports | Rapor sayfası |
| GET | `/reports/daily` | pos.reports.daily | Günlük rapor (AJAX) |
| GET | `/reports/top-products` | pos.reports.top-products | En çok satanlar (AJAX) |

### Stok
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/stock` | pos.stock | Stok listesi |
| POST | `/stock` | pos.stock.store | Stok hareketi ekle |

### Yönetim
| Method | URI | Route Name | Açıklama |
|--------|-----|-----------|----------|
| GET | `/branches` | pos.branches | Şube listesi |
| POST | `/branches` | pos.branches.store | Şube oluştur |
| PUT | `/branches/{branch}` | pos.branches.update | Şube güncelle |
| GET | `/users` | pos.users | Kullanıcı listesi |
| POST | `/users` | pos.users.store | Kullanıcı oluştur |
| PUT | `/users/{user}` | pos.users.update | Kullanıcı güncelle |
| DELETE | `/users/{user}` | pos.users.destroy | Kullanıcı sil |
| GET | `/day-operations` | pos.day-operations | Gün işlemleri |
| GET | `/settings` | pos.settings | Ayarlar |
| PUT | `/settings/branch` | pos.settings.branch | Şube ayarlarını güncelle |
| PUT | `/settings/general` | pos.settings.general | Genel ayarları güncelle |

---

## 6. FRONTEND MİMARİSİ

### Layout Yapısı (`layouts/app.blade.php`)
- **Sidebar** (sol): 18 menü öğesi, toggle edilebilir (dar/geniş), Alpine.js ile `sidebarOpen` state
- **Main content**: `@yield('content')` ile sayfa içeriği
- **Toast sistemi**: Alpine.js ile `showToast(message, type)` global fonksiyon
- **AJAX Helper**: `posAjax(url, options)` — fetch wrapper, CSRF token otomatik eklenir, JSON parse eder
- **formatCurrency**: JS tarafında `formatCurrency(amount)` → "1.234,56 ₺" formatı

### Sidebar Menü Sırası
1. Özet (Dashboard)
2. Şubeler
3. Siparişler
4. Hızlı Satış
5. Masalar
6. Mutfak
7. Gün İşlemleri
8. Kasa
9. Kasa Raporu
10. Satışlar
11. Müşteriler
12. Cariler
13. Kategoriler
14. Ürünler
15. Kullanıcılar
16. Raporlar
17. Depo
18. Ayarlar

### Tasarım Sistemi
- **DESIGN_GUIDE.md** dosyası mevcut — Emare Finance tasarım rehberi
- **Ana marka rengi**: `brand-500` = `#6366f1` (Indigo)
- **Renk skalası**: `brand-50` → `brand-950` (10 ton)
- **Tailwind config**: Inline CDN kullanımı, `brand` renk skalası extend edilmiş
- **Font**: Inter (Google Fonts)
- **İkonlar**: Font Awesome 6.5.1

### AJAX Pattern
Tüm CRUD işlemleri (ürün, kategori, müşteri, cari, kullanıcı, şube) Alpine.js + `posAjax()` ile yapılır:
```javascript
const response = await posAjax(url, {
    method: 'POST', // veya PUT, DELETE
    body: JSON.stringify(this.form),
});
showToast('Başarılı mesaj', 'success');
window.location.reload();
```

### Form Pattern
- Kasa aç/kapat ve Ayarlar: Standard HTML form POST (redirect ile dönüş)
- Diğer tümü: Alpine.js modal + AJAX (JSON response)

---

## 7. SEEDER (Demo Data)

Veritabanı `php artisan migrate:fresh --seed` ile sıfırlanır. Seeder şunları oluşturur:

### Altyapı
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

### İçerik
| Veri | Adet | Detay |
|------|------|-------|
| Categories | 3 | Yiyecek, İçecek, Tatlı |
| Products | 10 | Adana Kebap, Lahmacun, vs. (fiyat: 25-350₺) |
| Table Region | 1 | "Salon" |
| Restaurant Tables | 5 | Masa 1-5 (4 kişilik, boş) |
| Customers | 5 | Ahmet Yılmaz, Mehmet Kaya, vb. + 1 kurumsal |
| Firms | 3 | Metro Toptan, Baktat Gıda, İçecek Dünyası |

### İşlem Verileri (Son 30 gün)
| Veri | Adet | Detay |
|------|------|-------|
| Cash Registers | 31 | Her gün 1 kasa (son gün açık, diğerleri kapalı) |
| Sales | ~439 | Günde 8-20 satış, nakit/kart karışık |
| Sale Items | ~1318 | Her satışta 1-5 ürün |
| Orders | ~228 | Satışların ~%50'si sipariş olarak da oluşturulmuş |
| Order Items | ~684 | |
| Kitchen Orders | 3 | 2 pending, 1 preparing (mutfak ekranı için) |
| Stock Movements | 5 | İlk 5 ürün için giriş hareketi |

---

## 8. BİLİNEN DURUMLAR VE DÜZELTMELER

### Düzeltilmiş Buglar (Önceki Oturumlarda)

1. **CustomerController**: `withSum('sales', 'total')` → `'grand_total'` olarak düzeltildi
2. **KitchenController**: `completed` enum → `served` olarak düzeltildi (order_items'da `completed` yok)
3. **SaleController list()**: `$summaryStats` ve `items` eager loading eklendi
4. **sales/list.blade.php**: `discount_amount` → `discount_total`, `vat_amount` → `vat_total`, `$item->discount_amount` → `$item->discount`
5. **sales/list.blade.php**: İade URL `/pos/sales/` → `/pos/sale/` düzeltildi
6. **products/index.blade.php**: `$product->image` → `$product->image_url` düzeltildi
7. **customers/index.blade.php**: `sales_sum_total` → `sales_sum_grand_total` düzeltildi
8. **kitchen/index.blade.php**: AJAX URL'ler `pos/kitchen/order` → `/kitchen/order` düzeltildi
9. **CashRegisterController**: open/close JSON yerine `redirect()` döndürüyor (form POST uyumlu)
10. **SettingController**: updateBranch/updateGeneral JSON yerine `redirect()` döndürüyor
11. **settings/index.blade.php**: `$branch->email/$branch->tax_number` → `$branch->city/$branch->district` düzeltildi
12. **DayOperationController**: `branch_id` filtresi + `with('user')` eager load eklendi
13. **ReportController**: `HOUR(sold_at)` MySQL → `strftime('%H', sold_at)` SQLite uyumlu hale getirildi
14. **CashReportController show()**: `branch_id` filtresi eklendi
15. **ReportController**: Tarih default'u `Carbon::today()` → `Carbon::now()->startOfMonth()` olarak düzeltildi
16. **SaleController list()**: Tarih filtre `date_from/date_to` → `start_date/end_date` olarak view ile uyumlu hale getirildi
17. **bootstrap/app.php**: `redirectGuestsTo('/login')` eklendi ("Route [login] not defined" hatası düzeltildi)

### Aktif Durum
- **Tüm 18 sayfa HTTP 200 döndürüyor** ✅
- **67 route tamamı çalışıyor** ✅
- **Laravel log'da 0 hata** ✅
- **Demo data ile tüm sayfalar dolu görünüyor** ✅

### Tasarım Yenileme
- **DESIGN_GUIDE.md** yeni tasarım rehberi hazırlandı (Indigo temalı, açık tonlar)
- Layout (app.blade.php) güncellendi — yeni renk sistemi, Inter font, brand renkleri
- **Devam eden:** Tüm view dosyalarının DESIGN_GUIDE.md'ye uygun şekilde yeniden tasarlanması

---

## 9. MİMARİ NOTLAR

### Multi-Tenant Akış
1. Kullanıcı login olur → `PosLoginController` session'a `tenant_id` ve `branch_id` yazar
2. Her istek `ResolveTenant` middleware'den geçer → session güncellenir
3. Controller'lar `session('branch_id')` ve `session('tenant_id')` kullanır
4. Tüm sorgularda `where('branch_id', $branchId)` filtresi uygulanır

### Satış Akışı
1. POS ekranında ürünler seçilir (barkod/arama)
2. Müşteri seçilebilir (opsiyonel)
3. Ödeme yöntemi seçilir (nakit/kart/karışık/veresiye)
4. `SaleService::createSale()` çağrılır:
   - Sale kaydı oluşturulur
   - SaleItem'lar oluşturulur
   - Stok düşülür
   - Müşteri bakiyesi güncellenir (veresiye ise)
   - Fiş no otomatik üretilir

### Masa Akışı
1. Masa listesinden masa seçilir
2. Masa açılır → `TableSession` oluşturulur → masa durumu `occupied` olur
3. Sipariş eklenir → `Order` + `OrderItem` oluşturulur
4. Mutfak ekranında sipariş görünür
5. Ödeme alınır → Satış oluşturulur, masa kapanır → durum `empty` olur
6. Masa transfer edilebilir (başka masaya taşıma)

### Kasa Akışı
1. Gün başında kasa açılır → açılış tutarı girilir
2. Gün boyunca satışlar kasaya kaydedilir
3. Gün sonunda kasa kapatılır → sayım tutarı girilir
4. Beklenen tutar vs sayılan tutar farkı hesaplanır
5. Z Raporu oluşturulur

---

## 10. KOMUT REFERANSlARI

```bash
# Sunucu başlat
cd "/Users/emre/Desktop/adisyon sistemi/pos-system"
php artisan serve --port=8080

# Veritabanı sıfırla + demo data
php artisan migrate:fresh --seed

# Cache temizle
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# View cache oluştur
php artisan view:cache

# Route listesi
php artisan route:list

# Tinker (DB sorgulama)
php artisan tinker

# Log izle
tail -f storage/logs/laravel.log
```

---

## 11. YAPILACAKLAR LİSTESİ

### Tasarım (Devam Eden)
- [ ] Tüm Blade view dosyalarının DESIGN_GUIDE.md'ye uygun şekilde yeniden tasarlanması
- [ ] Login sayfası yeni tasarım
- [ ] Dashboard yeni tasarım
- [ ] POS satış ekranı yeni tasarım
- [ ] Diğer tüm sayfalar

### Gelecek Özellikler (Planlanan)
- [ ] Kampanya modülü (campaign tablolar hazır, UI yok)
- [ ] Sadakat programı (loyalty tablolar hazır, UI yok)
- [ ] Gelir/Gider takibi (income/expense tablolar hazır, UI yok)
- [ ] Donanım entegrasyonu (hardware tablolar hazır, UI yok)
- [ ] Fiş yazdırma
- [ ] Barkod okuyucu entegrasyonu
- [ ] Staff (personel) yönetimi sayfası
- [ ] Multi-branch tam entegrasyon (şubeler arası transfer)
- [ ] Export (Excel/PDF rapor çıktısı)
- [ ] API endpoint'leri (mobil uygulama için)

---

## 12. DOSYA KONUMLARI ÖZET

| Ne | Nerede |
|----|--------|
| Proje root | `/Users/emre/Desktop/adisyon sistemi/pos-system/` |
| Bu hafıza dosyası | `/Users/emre/Desktop/adisyon sistemi/emareadisyon_hafiza.md` |
| Tasarım rehberi | `/Users/emre/Desktop/adisyon sistemi/DESIGN_GUIDE.md` |
| Controllers | `app/Http/Controllers/Pos/` (17 dosya) |
| Models | `app/Models/` (34 dosya) |
| Views | `resources/views/pos/` (21 blade dosyası) |
| Routes | `routes/web.php` (tek dosya, 67 route) |
| Services | `app/Services/` (3 dosya) |
| Migrations | `database/migrations/` (41 dosya) |
| Seeder | `database/seeders/DatabaseSeeder.php` |
| DB dosyası | `database/database.sqlite` |
| Layout | `resources/views/pos/layouts/app.blade.php` |
| Helpers | `app/helpers.php` |
| Middleware | `app/Http/Middleware/` (3 dosya) |
| Laravel log | `storage/logs/laravel.log` |
