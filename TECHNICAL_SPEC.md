# Emare Finance — Teknik Şartname (POS Entegrasyon Dökümanı)

> **Tarih:** 2 Mart 2026  
> **Amaç:** Bu döküman, Emare Finance backend/yönetim paneli ile uyumlu çalışacak bir POS yazılımı geliştirmek isteyen yapay zeka veya geliştirici için hazırlanmıştır. Veritabanı şeması, API endpoint'leri, yetkilendirme, modül sistemi ve iş mantığı detaylı olarak açıklanmıştır.

---

## 1. PROJE GENEL BAKIŞ

### 1.1 Teknoloji Stack
| Bileşen | Teknoloji |
|---------|-----------|
| Backend Framework | Laravel 12 (PHP 8.4) |
| Veritabanı | MariaDB 10.x |
| Web Server | Nginx 1.20 (port 3000) |
| Sunucu OS | AlmaLinux 9.7 |
| Frontend (Panel) | Tailwind CSS + Alpine.js + Chart.js |
| AI Asistan | Google Gemini 2.5 Flash |
| Dil/Timezone | Türkçe (tr), Europe/Istanbul |
| Para Birimi | TRY (₺), format: 1.234,56 ₺ |

### 1.2 Mimari
- **Multi-tenant SaaS**: Her firma (Tenant) kendi şubeleri, kullanıcıları, ürünleri ve satışlarına sahip
- **Modüler yapı**: Core POS zorunlu, diğer modüller (Hardware, E-Fatura, SMS, Marketing vs.) aktive edilebilir
- **RBAC**: Role-Based Access Control — 5 rol, 41 izin
- **Veri izolasyonu**: `BelongsToTenant` trait ile otomatik tenant filtreleme

---

## 2. VERİTABANI ŞEMASI

### 2.1 Temel Tablolar

#### `tenants` — Firmalar (Multi-Tenant)
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
slug            VARCHAR(255) UNIQUE
status          ENUM('active','suspended','cancelled') DEFAULT 'active'
plan_id         BIGINT UNSIGNED FK → plans.id
trial_ends_at   DATETIME
billing_email   VARCHAR(255)
meta            JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `branches` — Şubeler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
external_id     VARCHAR(255) INDEX (dış sistemden gelen ID)
name            VARCHAR(255) NOT NULL
code            VARCHAR(255)
address         VARCHAR(255)
phone           VARCHAR(255)
city            VARCHAR(255)
district        VARCHAR(255)
is_active       TINYINT(1) DEFAULT 1
settings        JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP (soft delete)
```

#### `users` — Kullanıcılar
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
branch_id       BIGINT UNSIGNED FK → branches.id
role_id         BIGINT UNSIGNED FK → roles.id (birincil rol)
is_super_admin  TINYINT(1) DEFAULT 0
name            VARCHAR(255) NOT NULL
email           VARCHAR(255) UNIQUE NOT NULL
email_verified_at TIMESTAMP
password        VARCHAR(255) NOT NULL (bcrypt hash)
remember_token  VARCHAR(100)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.2 Ürün & Kategori Tabloları

#### `categories` — Ürün Kategorileri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
name            VARCHAR(255) NOT NULL
parent_id       BIGINT UNSIGNED FK → categories.id (self-referential, hiyerarşik)
sort_order      INT DEFAULT 0
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `products` — Ürünler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
barcode         VARCHAR(255) INDEX
name            VARCHAR(255) NOT NULL
description     TEXT
category_id     BIGINT UNSIGNED FK → categories.id
service_category_id BIGINT UNSIGNED FK → service_categories.id
variant_type    VARCHAR(255) (renk, beden vs.)
parent_id       BIGINT UNSIGNED FK → products.id (varyant parent)
unit            VARCHAR(255) DEFAULT 'Adet'
purchase_price  DECIMAL(12,2) DEFAULT 0.00
sale_price      DECIMAL(12,2) DEFAULT 0.00
vat_rate        INT DEFAULT 20 (KDV oranı %)
additional_taxes JSON (ÖTV, ÖİV vs. ek vergiler)
stock_quantity  DECIMAL(12,2) DEFAULT 0.00
critical_stock  DECIMAL(12,2) DEFAULT 0.00 (düşük stok eşiği)
image_url       VARCHAR(255)
is_active       TINYINT(1) DEFAULT 1
is_service      TINYINT(1) DEFAULT 0 (hizmet mi ürün mü)
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP (soft delete)
```

#### `branch_product` — Şube-Ürün Pivot (Şubeye Özel Stok & Fiyat)
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
branch_id       BIGINT UNSIGNED FK → branches.id
product_id      BIGINT UNSIGNED FK → products.id
stock_quantity  DECIMAL(12,2) DEFAULT 0.00
sale_price      DECIMAL(12,2) DEFAULT 0.00
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `service_categories` — Hizmet Kategorileri (Tekrarlayan Fatura İçin)
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
description     TEXT
parent_id       BIGINT UNSIGNED FK → service_categories.id (self-referential)
color           VARCHAR(255)
icon            VARCHAR(255)
sort_order      INT DEFAULT 0
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.3 Satış Tabloları

#### `sales` — Satışlar
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
receipt_no      VARCHAR(255) INDEX (fiş numarası)
branch_id       BIGINT UNSIGNED FK → branches.id
customer_id     BIGINT UNSIGNED FK → customers.id (opsiyonel)
user_id         BIGINT UNSIGNED FK → users.id (kasada login olan)
payment_method  VARCHAR(255) DEFAULT 'cash' (cash/card/credit/mixed)
total_items     INT DEFAULT 0
subtotal        DECIMAL(14,2) DEFAULT 0.00 (vergisiz ara toplam)
vat_total       DECIMAL(14,2) DEFAULT 0.00 (KDV toplamı)
additional_tax_total DECIMAL(14,2) DEFAULT 0.00 (ek vergi toplamı)
discount_total  DECIMAL(14,2) DEFAULT 0.00 (indirim toplamı)
grand_total     DECIMAL(14,2) DEFAULT 0.00 (genel toplam)
discount        DECIMAL(14,2) DEFAULT 0.00
cash_amount     DECIMAL(14,2) DEFAULT 0.00
card_amount     DECIMAL(14,2) DEFAULT 0.00
status          VARCHAR(255) DEFAULT 'completed' (completed/cancelled/refunded)
notes           TEXT
staff_name      VARCHAR(255) (satışı yapan personel)
application     VARCHAR(255) (web/pos/mobile — hangi uygulama)
note            TEXT
sold_at         TIMESTAMP (satış zamanı)
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP (soft delete)
```

#### `sale_items` — Satış Kalemleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
sale_id         BIGINT UNSIGNED FK → sales.id
product_id      BIGINT UNSIGNED FK → products.id
product_name    VARCHAR(255) NOT NULL (satış anındaki isim)
barcode         VARCHAR(255)
quantity        DECIMAL(12,2) DEFAULT 1.00
unit_price      DECIMAL(12,2) DEFAULT 0.00 (birim satış fiyatı)
discount        DECIMAL(12,2) DEFAULT 0.00 (kalem indirimi)
vat_rate        INT DEFAULT 20 (KDV oranı %)
vat_amount      DECIMAL(12,2) DEFAULT 0.00 (KDV tutarı)
additional_taxes JSON (ek vergiler)
additional_tax_amount DECIMAL(12,2) DEFAULT 0.00
total           DECIMAL(14,2) DEFAULT 0.00 (kalem toplamı)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.4 Müşteri & Cari Tabloları

#### `customers` — Müşteriler / Cariler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
name            VARCHAR(255) NOT NULL
type            VARCHAR(255) DEFAULT 'individual' (individual/corporate)
tax_number      VARCHAR(255)
tax_office      VARCHAR(255)
phone           VARCHAR(255)
email           VARCHAR(255)
address         VARCHAR(255)
city            VARCHAR(255)
district        VARCHAR(255)
balance         DECIMAL(14,2) DEFAULT 0.00 (+ alacak, - borç)
notes           TEXT
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP (soft delete)
```

#### `account_transactions` — Cari Hesap Hareketleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
customer_id     BIGINT UNSIGNED FK → customers.id
type            VARCHAR(255) NOT NULL (sale/payment/refund/adjustment)
amount          DECIMAL(14,2) DEFAULT 0.00
balance_after   DECIMAL(14,2) DEFAULT 0.00
description     VARCHAR(255)
reference       VARCHAR(255) (ilişkili fiş/fatura no)
transaction_date TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.5 Stok Tabloları

#### `stock_movements` — Stok Hareketleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
type            VARCHAR(255) NOT NULL (purchase/sale/return/transfer/adjustment/waste/count)
barcode         VARCHAR(255)
product_id      BIGINT UNSIGNED FK → products.id
product_name    VARCHAR(255)
transaction_code VARCHAR(255)
note            TEXT
firm_customer   VARCHAR(255)
payment_type    VARCHAR(255)
quantity        DECIMAL(12,2) DEFAULT 0.00
remaining       DECIMAL(12,2) DEFAULT 0.00
unit_price      DECIMAL(12,2) DEFAULT 0.00
total           DECIMAL(14,2) DEFAULT 0.00
movement_date   TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `stock_counts` — Stok Sayımları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
branch_id       BIGINT UNSIGNED FK → branches.id
status          VARCHAR(255) DEFAULT 'draft' (draft/in_progress/completed)
total_items     INT DEFAULT 0
notes           TEXT
counted_at      TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `stock_count_items` — Sayım Kalemleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
stock_count_id  BIGINT UNSIGNED FK → stock_counts.id
product_id      BIGINT UNSIGNED FK → products.id
barcode         VARCHAR(255)
product_name    VARCHAR(255) NOT NULL
system_quantity DECIMAL(12,2) DEFAULT 0.00
counted_quantity DECIMAL(12,2) DEFAULT 0.00
difference      DECIMAL(12,2) DEFAULT 0.00
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.6 Satın Alma Tabloları

#### `purchase_invoices` — Alış Faturaları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
invoice_type    VARCHAR(255) DEFAULT 'purchase' (purchase/return)
invoice_no      VARCHAR(255) INDEX
firm_id         BIGINT UNSIGNED FK → firms.id
branch_id       BIGINT UNSIGNED FK → branches.id
waybill_no      VARCHAR(255)
document_no     VARCHAR(255)
payment_type    VARCHAR(255) DEFAULT 'cash'
total_items     INT DEFAULT 0
total_amount    DECIMAL(14,2) DEFAULT 0.00
invoice_date    DATE
shipment_date   DATE
notes           TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP (soft delete)
```

#### `purchase_invoice_items` — Alış Faturası Kalemleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
purchase_invoice_id BIGINT UNSIGNED FK → purchase_invoices.id
product_id      BIGINT UNSIGNED FK → products.id
product_name    VARCHAR(255) NOT NULL
barcode         VARCHAR(255)
quantity        DECIMAL(12,2) DEFAULT 1.00
unit_price      DECIMAL(12,2) DEFAULT 0.00
total           DECIMAL(14,2) DEFAULT 0.00
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.7 Tedarikçi Tablosu

#### `firms` — Tedarikçiler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
name            VARCHAR(255) NOT NULL
tax_number      VARCHAR(255)
tax_office      VARCHAR(255)
phone           VARCHAR(255)
email           VARCHAR(255)
address         VARCHAR(255)
city            VARCHAR(255)
balance         DECIMAL(14,2) DEFAULT 0.00
notes           TEXT
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP (soft delete)
```

### 2.8 Gelir-Gider Tabloları

#### `income_expense_types` — Gelir/Gider Türleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
direction       VARCHAR(255) NOT NULL (income/expense)
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `incomes` — Gelirler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
income_expense_type_id BIGINT UNSIGNED FK → income_expense_types.id
type_name       VARCHAR(255)
note            TEXT
amount          DECIMAL(14,2) DEFAULT 0.00
payment_type    VARCHAR(255) DEFAULT 'cash'
date            DATE
time            TIME
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `expenses` — Giderler
```sql
-- Aynı yapı incomes ile birebir aynı
```

### 2.9 E-Fatura Tabloları

#### `e_invoices` — E-Faturalar
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) UNIQUE
invoice_no      VARCHAR(255)
uuid            VARCHAR(255) UNIQUE
direction       ENUM('outgoing','incoming') DEFAULT 'outgoing'
type            ENUM('invoice','return','withholding','exception','special') DEFAULT 'invoice'
scenario        ENUM('basic','commercial','export') DEFAULT 'basic'
status          VARCHAR(255) DEFAULT 'draft'
customer_id     BIGINT UNSIGNED FK → customers.id
receiver_name   VARCHAR(255)
receiver_tax_number VARCHAR(255)
receiver_tax_office VARCHAR(255)
receiver_address VARCHAR(255)
branch_id       BIGINT UNSIGNED FK → branches.id
sale_id         BIGINT UNSIGNED FK → sales.id
currency        VARCHAR(255) DEFAULT 'TRY'
exchange_rate   DECIMAL(12,4) DEFAULT 1.0000
subtotal        DECIMAL(12,2) DEFAULT 0.00
vat_total       DECIMAL(12,2) DEFAULT 0.00
additional_tax_total DECIMAL(14,2) DEFAULT 0.00
discount_total  DECIMAL(12,2) DEFAULT 0.00
grand_total     DECIMAL(12,2) DEFAULT 0.00
withholding_total DECIMAL(12,2) DEFAULT 0.00
vat_rate        INT DEFAULT 20
notes           TEXT
payment_method  VARCHAR(255)
invoice_date    DATE
sent_at         DATETIME
received_at     DATETIME
meta            JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP
```

#### `e_invoice_items` — E-Fatura Kalemleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
e_invoice_id    BIGINT UNSIGNED FK → e_invoices.id
product_id      BIGINT UNSIGNED FK → products.id
product_name    VARCHAR(255) NOT NULL
product_code    VARCHAR(255)
unit            VARCHAR(255) DEFAULT 'Adet'
quantity        DECIMAL(12,3) DEFAULT 1.000
unit_price      DECIMAL(12,2) DEFAULT 0.00
discount        DECIMAL(12,2) DEFAULT 0.00
vat_rate        INT DEFAULT 20
vat_amount      DECIMAL(12,2) DEFAULT 0.00
additional_taxes JSON
additional_tax_amount DECIMAL(12,2) DEFAULT 0.00
total           DECIMAL(12,2) DEFAULT 0.00
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `e_invoice_settings` — E-Fatura Ayarları (singleton)
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
company_name    VARCHAR(255)
tax_number      VARCHAR(255)
tax_office      VARCHAR(255)
address, city, district, phone, email, web VARCHAR(255)
integrator      VARCHAR(255)
api_key         VARCHAR(255) (encrypted)
api_secret      VARCHAR(255) (encrypted)
sender_alias    VARCHAR(255)
receiver_alias  VARCHAR(255)
auto_send       TINYINT(1) DEFAULT 0
is_active       TINYINT(1) DEFAULT 0
default_scenario VARCHAR(255) DEFAULT 'basic'
default_currency VARCHAR(255) DEFAULT 'TRY'
default_vat_rate INT DEFAULT 20
invoice_prefix  VARCHAR(255)
invoice_counter INT DEFAULT 1
meta            JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.10 Tekrarlayan Fatura Tabloları

#### `recurring_invoices` — Tekrarlayan Faturalar
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
title           VARCHAR(255) NOT NULL
description     TEXT
customer_id     BIGINT UNSIGNED FK → customers.id
branch_id       BIGINT UNSIGNED FK → branches.id
service_category_id BIGINT UNSIGNED FK → service_categories.id
frequency       ENUM('weekly','monthly','bimonthly','quarterly','semiannual','annual') DEFAULT 'monthly'
frequency_day   INT DEFAULT 1
currency        VARCHAR(255) DEFAULT 'TRY'
subtotal        DECIMAL(14,2) DEFAULT 0.00
tax_total       DECIMAL(14,2) DEFAULT 0.00
discount_total  DECIMAL(14,2) DEFAULT 0.00
grand_total     DECIMAL(14,2) DEFAULT 0.00
payment_method  VARCHAR(255)
status          ENUM('active','paused','cancelled','completed') DEFAULT 'active'
start_date      DATE NOT NULL
end_date        DATE
next_invoice_date DATE
last_invoice_date DATE
invoices_generated INT DEFAULT 0
max_invoices    INT
auto_send       TINYINT(1) DEFAULT 0
notes           TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP
```

### 2.11 Personel Tabloları

#### `staff` — Personeller
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
external_id     VARCHAR(255) INDEX
name            VARCHAR(255) NOT NULL
role            VARCHAR(255)
branch_id       BIGINT UNSIGNED FK → branches.id
phone           VARCHAR(255)
email           VARCHAR(255)
total_sales     DECIMAL(14,2) DEFAULT 0.00
total_transactions INT DEFAULT 0
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `staff_motions` — Personel Hareketleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
staff_id        BIGINT UNSIGNED FK → staff.id
staff_name      VARCHAR(255)
action          VARCHAR(255) NOT NULL (clock_in/clock_out/break/leave vs.)
description     TEXT
application     VARCHAR(255)
detail          TEXT
action_date     TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.12 Görev Tablosu

#### `tasks` — Görevler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
title           VARCHAR(255) NOT NULL
description     TEXT
status          VARCHAR(255) DEFAULT 'pending' (pending/in_progress/completed)
priority        VARCHAR(255) DEFAULT 'normal' (low/normal/high/urgent)
assigned_to     BIGINT UNSIGNED FK → staff.id
due_date        DATE
completed_at    TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.13 Pazarlama Tabloları

#### `campaigns` — Kampanyalar
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
branch_id       BIGINT UNSIGNED FK → branches.id
name            VARCHAR(255) NOT NULL
description     TEXT
type            ENUM('discount','bogo','bundle','loyalty_bonus','free_shipping','gift','seasonal','flash_sale') DEFAULT 'discount'
status          ENUM('draft','scheduled','active','paused','ended','cancelled') DEFAULT 'draft'
discount_type   ENUM('percentage','fixed_amount','buy_x_get_y')
discount_value  DECIMAL(10,2)
min_purchase_amount DECIMAL(12,2)
max_discount_amount DECIMAL(12,2)
usage_limit     INT
usage_count     INT DEFAULT 0
per_customer_limit INT
coupon_code     VARCHAR(255) UNIQUE
target_products JSON (ürün ID listesi)
target_categories JSON (kategori ID listesi)
target_segments JSON (segment ID listesi)
starts_at       TIMESTAMP
ends_at         TIMESTAMP
created_by      BIGINT UNSIGNED FK → users.id
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP
```

#### `campaign_usages` — Kampanya Kullanımları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
campaign_id     BIGINT UNSIGNED FK → campaigns.id
customer_id     BIGINT UNSIGNED FK → customers.id
sale_id         BIGINT UNSIGNED FK → sales.id
discount_applied DECIMAL(12,2) DEFAULT 0.00
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `customer_segments` — Müşteri Segmentleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
name            VARCHAR(255) NOT NULL
description     TEXT
color           VARCHAR(7) DEFAULT '#6366f1'
icon            VARCHAR(255) DEFAULT 'fa-users'
type            ENUM('manual','auto') DEFAULT 'manual'
conditions      JSON (otomatik segmentasyon kuralları)
customer_count  INT DEFAULT 0
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `customer_segment_members` — Segment Üyeleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
segment_id      BIGINT UNSIGNED FK → customer_segments.id
customer_id     BIGINT UNSIGNED FK → customers.id
added_at        TIMESTAMP
```

#### `loyalty_programs` — Sadakat Programları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
name            VARCHAR(255) NOT NULL
description     TEXT
points_per_currency DECIMAL(10,2) DEFAULT 1.00 (1 TL = X puan)
currency_per_point DECIMAL(10,4) DEFAULT 0.0100 (1 puan = X TL)
min_redeem_points INT DEFAULT 100
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `loyalty_points` — Sadakat Puanları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
customer_id     BIGINT UNSIGNED FK → customers.id
loyalty_program_id BIGINT UNSIGNED FK → loyalty_programs.id
points          INT NOT NULL
type            ENUM('earn','redeem','expire','bonus','adjustment') DEFAULT 'earn'
description     VARCHAR(255)
sale_id         BIGINT UNSIGNED FK → sales.id
campaign_id     BIGINT UNSIGNED FK → campaigns.id
balance_after   INT DEFAULT 0
expires_at      TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `quotes` — Teklifler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
branch_id       BIGINT UNSIGNED FK → branches.id
quote_number    VARCHAR(255) UNIQUE NOT NULL
customer_id     BIGINT UNSIGNED FK → customers.id
customer_name, customer_email, customer_phone, customer_company VARCHAR(255)
customer_tax_number, customer_address VARCHAR(255)
title           VARCHAR(255) NOT NULL
description     TEXT
status          ENUM('draft','sent','viewed','accepted','rejected','expired','converted') DEFAULT 'draft'
subtotal        DECIMAL(12,2)
tax_total       DECIMAL(12,2)
discount_total  DECIMAL(12,2)
grand_total     DECIMAL(12,2)
currency        VARCHAR(3) DEFAULT 'TRY'
issue_date      DATE
valid_until     DATE
notes           TEXT
terms           TEXT
created_by      BIGINT UNSIGNED FK → users.id
sent_at, viewed_at, accepted_at, rejected_at TIMESTAMP
rejection_reason TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP
```

#### `quote_items` — Teklif Kalemleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
quote_id        BIGINT UNSIGNED FK → quotes.id
product_id      BIGINT UNSIGNED FK → products.id
name            VARCHAR(255) NOT NULL
description     TEXT
quantity        DECIMAL(10,2) DEFAULT 1.00
unit            VARCHAR(255) DEFAULT 'Adet'
unit_price      DECIMAL(12,2)
tax_rate        DECIMAL(5,2) DEFAULT 0.00
tax_amount      DECIMAL(12,2) DEFAULT 0.00
discount_rate   DECIMAL(5,2) DEFAULT 0.00
discount_amount DECIMAL(12,2) DEFAULT 0.00
total           DECIMAL(12,2)
sort_order      INT DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.14 SMS Tabloları

#### `sms_settings` — SMS Ayarları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
provider        VARCHAR(255) DEFAULT 'netgsm' (netgsm/iletimerkezi/twilio/mutlucell/custom)
api_key         VARCHAR(255) (encrypted)
api_secret      VARCHAR(255) (encrypted)
sender_id       VARCHAR(255)
username        VARCHAR(255)
password        VARCHAR(255) (encrypted)
api_url         VARCHAR(255)
balance         DECIMAL(12,2) DEFAULT 0.00
is_active       TINYINT(1) DEFAULT 0
extra_config    JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `sms_templates` — SMS Şablonları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
name            VARCHAR(255) NOT NULL
code            VARCHAR(255) INDEX
content         TEXT NOT NULL (değişkenler: {musteri_adi}, {telefon}, {tutar} vs.)
category        VARCHAR(255) DEFAULT 'general'
is_active       TINYINT(1) DEFAULT 1
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `sms_scenarios` — Otomatik SMS Senaryoları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
name            VARCHAR(255) NOT NULL
trigger_event   VARCHAR(255) NOT NULL (sale_completed/payment_received/birthday vs.)
template_id     BIGINT UNSIGNED FK → sms_templates.id
target_type     VARCHAR(255) DEFAULT 'all'
customer_type_filter VARCHAR(255)
segment_id      BIGINT UNSIGNED FK → customer_segments.id
conditions      JSON
schedule_type   VARCHAR(255) DEFAULT 'immediate' (immediate/delayed/scheduled)
delay_minutes   INT
cron_expression VARCHAR(255)
send_time       TIME
is_active       TINYINT(1) DEFAULT 1
priority        INT DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `sms_logs` — SMS Logları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
scenario_id     BIGINT UNSIGNED FK → sms_scenarios.id
template_id     BIGINT UNSIGNED FK → sms_templates.id
customer_id     BIGINT UNSIGNED FK → customers.id
phone           VARCHAR(20) NOT NULL
content         TEXT NOT NULL
status          VARCHAR(255) DEFAULT 'pending' (pending/sent/delivered/failed)
provider_message_id VARCHAR(255)
error_message   VARCHAR(255)
cost            DECIMAL(8,4) DEFAULT 0.0000
trigger_event   VARCHAR(255)
meta            JSON
sent_at         TIMESTAMP
delivered_at    TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `sms_blacklist` — Kara Liste
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
phone           VARCHAR(20) INDEX
reason          VARCHAR(255)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.15 Dijital Ekran (Signage) Tabloları

#### `signage_devices` — Dijital Ekran Cihazları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
location        VARCHAR(255)
resolution      VARCHAR(255) DEFAULT '1920x1080'
orientation     ENUM('landscape','portrait') DEFAULT 'landscape'
template        VARCHAR(255) DEFAULT 'menu-board'
device_type, model, os, ip_address, mac_address VARCHAR(255)
brightness      TINYINT UNSIGNED DEFAULT 80
volume          TINYINT UNSIGNED DEFAULT 0
auto_power      TINYINT(1) DEFAULT 0
power_on, power_off VARCHAR(255)
api_token       VARCHAR(64) UNIQUE
status          ENUM('online','offline','maintenance') DEFAULT 'offline'
last_ping_at    TIMESTAMP
meta            JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `signage_playlists` — Oynatma Listeleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
loop            TINYINT(1) DEFAULT 1
schedule_text   VARCHAR(255)
status          ENUM('active','inactive') DEFAULT 'active'
meta            JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `signage_contents` — İçerikler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
type            ENUM('image','video','template','widget','url') DEFAULT 'image'
file_path       VARCHAR(255)
file_url        VARCHAR(255)
url             VARCHAR(255)
resolution, file_size VARCHAR(255)
duration        INT UNSIGNED DEFAULT 10
tags            JSON
meta            JSON
status          ENUM('active','draft','scheduled','archived') DEFAULT 'draft'
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### Pivot & İlişki Tabloları
```sql
-- signage_playlist_items (playlist ↔ content, sort_order, duration_override)
-- signage_device_playlist (device ↔ playlist, priority)
-- signage_schedules (playlist_id, time_start, time_end, days JSON, priority, is_active)
```

### 2.16 Donanım Tabloları

#### `hardware_devices` — Donanım Cihazları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
type            VARCHAR(255) INDEX (printer/barcode_scanner/cash_drawer/scale/card_reader/display)
connection      VARCHAR(255) NOT NULL (usb/network/serial/bluetooth)
protocol        VARCHAR(255) (esc_pos/zpl/star vs.)
model, manufacturer VARCHAR(255)
vendor_id, product_id VARCHAR(255) (USB vendor/product ID)
ip_address      VARCHAR(255)
port            INT
serial_port     VARCHAR(255)
baud_rate       INT DEFAULT 9600
mac_address     VARCHAR(255)
settings        JSON
is_default      TINYINT(1) DEFAULT 0
is_active       TINYINT(1) DEFAULT 1
last_seen_at    TIMESTAMP
status          VARCHAR(255) DEFAULT 'disconnected' (connected/disconnected/error)
branch_id       BIGINT UNSIGNED FK → branches.id
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `hardware_drivers` — Donanım Sürücüleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
device_type     VARCHAR(255) INDEX
manufacturer    VARCHAR(255) INDEX
model           VARCHAR(255)
vendor_id       VARCHAR(255) INDEX (USB)
product_id      VARCHAR(255)
protocol        VARCHAR(255)
connections     JSON
features        JSON
specs           JSON
notes           TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.17 Vergi Tablosu

#### `tax_rates` — Vergi Oranları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
code            VARCHAR(255) INDEX (kdv/otv/oiv)
rate            DECIMAL(8,4) DEFAULT 0.0000
type            ENUM('percentage','fixed') DEFAULT 'percentage'
description     VARCHAR(255)
is_default      TINYINT(1) DEFAULT 0
is_active       TINYINT(1) DEFAULT 1
sort_order      INT DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.18 Ödeme Tipleri

#### `payment_types` — Ödeme Türleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
name            VARCHAR(255) NOT NULL
code            VARCHAR(255)
is_active       TINYINT(1) DEFAULT 1
sort_order      INT DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.19 RBAC Tabloları

#### `roles` — Roller
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
code            VARCHAR(255) UNIQUE NOT NULL
name            VARCHAR(255) NOT NULL
description     TEXT
scope           ENUM('tenant','branch') DEFAULT 'branch'
is_system       TINYINT(1) DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `permissions` — İzinler
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
code            VARCHAR(255) UNIQUE NOT NULL
name            VARCHAR(255) NOT NULL
module_code     VARCHAR(255) INDEX (hangi modüle ait)
group           VARCHAR(255) (UI gruplandırma)
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `role_permissions` — Rol-İzin Pivot
```sql
role_id         BIGINT UNSIGNED FK → roles.id  (composite PK)
permission_id   BIGINT UNSIGNED FK → permissions.id  (composite PK)
```

#### `user_roles` — Kullanıcı-Rol Pivot (ek roller)
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
user_id         BIGINT UNSIGNED FK → users.id
role_id         BIGINT UNSIGNED FK → roles.id
tenant_id       BIGINT UNSIGNED FK → tenants.id
branch_id       BIGINT UNSIGNED FK → branches.id
created_at      DATETIME
```

### 2.20 Modül & Plan Tabloları

#### `modules` — Sistem Modülleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
code            VARCHAR(255) UNIQUE NOT NULL
name            VARCHAR(255) NOT NULL
description     TEXT
is_core         TINYINT(1) DEFAULT 0
scope           ENUM('tenant','branch','both') DEFAULT 'both'
dependencies    JSON (bağımlı modül kodları)
sort_order      INT DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `plans` — Abonelik Planları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
code            VARCHAR(255) UNIQUE NOT NULL
name            VARCHAR(255) NOT NULL
description     TEXT
price_monthly   DECIMAL(10,2) DEFAULT 0.00
price_yearly    DECIMAL(10,2) DEFAULT 0.00
is_active       TINYINT(1) DEFAULT 1
limits          JSON (max_branches, max_users, max_products vs.)
sort_order      INT DEFAULT 0
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `plan_modules` — Plan-Modül Pivot
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
plan_id         BIGINT UNSIGNED FK → plans.id
module_id       BIGINT UNSIGNED FK → modules.id
included        TINYINT(1) DEFAULT 1
config          JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `tenant_modules` — Aktif Modüller
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
module_id       BIGINT UNSIGNED FK → modules.id
is_active       TINYINT(1) DEFAULT 1
activated_at    DATETIME
expires_at      DATETIME
config          JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

#### `branch_modules` — Şube Modülleri
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
branch_id       BIGINT UNSIGNED FK → branches.id
module_id       BIGINT UNSIGNED FK → modules.id
is_active       TINYINT(1) DEFAULT 1
activated_at    DATETIME
config          JSON
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.21 Entegrasyon Talepleri

#### `integration_requests` — Entegrasyon Başvuruları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
user_id         BIGINT UNSIGNED FK → users.id
integration_type VARCHAR(255) NOT NULL
integration_name VARCHAR(255) NOT NULL
message         TEXT
status          ENUM('pending','approved','rejected') DEFAULT 'pending'
admin_note      TEXT
reviewed_by     BIGINT UNSIGNED FK → users.id
reviewed_at     TIMESTAMP
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

### 2.22 Pazarlama Mesajları

#### `marketing_messages` — Toplu Mesajlar
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
tenant_id       BIGINT UNSIGNED FK → tenants.id
title           VARCHAR(255) NOT NULL
content         TEXT NOT NULL
channel         ENUM('email','sms','whatsapp','push') DEFAULT 'email'
status          ENUM('draft','scheduled','sending','sent','failed','cancelled') DEFAULT 'draft'
segment_id      BIGINT UNSIGNED FK → customer_segments.id
campaign_id     BIGINT UNSIGNED FK → campaigns.id
recipient_filters JSON
total_recipients INT DEFAULT 0
sent_count, delivered_count, opened_count, clicked_count, bounced_count INT DEFAULT 0
scheduled_at    TIMESTAMP
sent_at         TIMESTAMP
created_by      BIGINT UNSIGNED FK → users.id
created_at      TIMESTAMP
updated_at      TIMESTAMP
deleted_at      TIMESTAMP
```

#### `marketing_message_logs` — Mesaj Logları
```sql
id              BIGINT UNSIGNED PK AUTO_INCREMENT
message_id      BIGINT UNSIGNED FK → marketing_messages.id
customer_id     BIGINT UNSIGNED FK → customers.id
recipient       VARCHAR(255) NOT NULL
status          ENUM('pending','sent','delivered','opened','clicked','bounced','failed') DEFAULT 'pending'
sent_at, delivered_at, opened_at, clicked_at TIMESTAMP
error_message   TEXT
created_at      TIMESTAMP
updated_at      TIMESTAMP
```

---

## 3. API ENDPOINT'LERİ

### 3.1 Mevcut REST API (Token-based, auth:sanctum)

**Base URL:** `http://77.92.152.3:3000/api/v1`

#### Dashboard API
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/dashboard` | İstatistikler: toplam ürün, müşteri, şube, personel, bugün/hafta/ay gelir, düşük stok, son 7 gün chart, son 5 satış |

#### Ürün API
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/products` | Sayfalı ürün listesi. Filtre: search, category_id, low_stock |
| GET | `/products/{id}` | Tekil ürün + 30 gün satış istatistikleri |
| GET | `/products/categories` | Aktif kategoriler + ürün sayıları |
| GET | `/products/low-stock` | Kritik stok altı ürünler |

#### Satış API
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/sales` | Sayfalı satış listesi. Filtre: search, start_date, end_date, payment_method |
| GET | `/sales/{id}` | Tekil satış + müşteri + kalemler |
| GET | `/sales/summary` | Tarih aralığı özeti: toplam, ortalama, nakit/kart dağılımı, saatlik dağılım |

#### Müşteri API
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/customers` | Sayfalı müşteri listesi. Filtre: search, has_debt |
| GET | `/customers/{id}` | Tekil müşteri + satış istatistikleri |
| GET | `/customers/{id}/sales` | Müşterinin satışları |

#### Rapor API
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/reports/daily` | Günlük rapor: satış, gelir, indirim, nakit/kart/veresiye |
| GET | `/reports/top-products` | En çok satan ürünler (son N gün) |
| GET | `/reports/revenue-chart` | Gelir chart verisi (son N gün) |
| GET | `/reports/payment-methods` | Ödeme yöntemi dağılımı |

#### Stok API
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/stock/overview` | Stok özeti: toplam ürün, düşük stok, biten stok, toplam değer |
| GET | `/stock/movements` | Stok hareketleri |
| GET | `/stock/alerts` | Stok uyarıları |

#### Donanım API
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/hardware/devices` | Kayıtlı donanım cihazları |
| GET | `/hardware/drivers` | Desteklenen sürücüler |
| GET | `/hardware/drivers/manufacturers` | Üretici listesi |
| GET | `/hardware/drivers/models` | Model listesi |
| GET | `/hardware/drivers/stats` | Sürücü istatistikleri |
| GET | `/hardware/drivers/{id}` | Sürücü detayı |
| POST | `/hardware/print-network` | Ağ yazıcıya yazdır |

#### Vergi API
| Method | Endpoint | Açıklama |
|--------|----------|----------|
| GET | `/tax-rates` | Vergi oranları listesi |
| GET | `/tax-rates/grouped` | Koda göre gruplanmış (kdv, otv, oiv) |

---

## 4. YETKİLENDİRME SİSTEMİ

### 4.1 Roller
| Kod | Ad | Açıklama |
|-----|-----|----------|
| `admin` | Yönetici | Tüm izinlere sahip |
| `manager` | Şube Müdürü | Şube bazlı yönetim |
| `cashier` | Kasiyer | Satış odaklı |
| `accounting` | Muhasebe | Finans odaklı |
| `warehouse` | Depo Sorumlusu | Stok odaklı |

### 4.2 İzinler (41 adet)

| Grup | İzin Kodu | Ad |
|------|-----------|-----|
| **Satış** | `sales.view` | Satışları Görüntüle |
| | `sales.create` | Satış Oluştur |
| | `sales.cancel` | Satış İptal Et |
| | `sales.refund` | İade İşlemi |
| | `sales.discount` | İndirim Uygula |
| **Ürün** | `products.view` | Ürünleri Görüntüle |
| | `products.create` | Ürün Ekle |
| | `products.edit` | Ürün Düzenle |
| | `products.delete` | Ürün Sil |
| **Müşteri** | `customers.view` | Müşterileri Görüntüle |
| | `customers.create` | Müşteri Ekle |
| | `customers.edit` | Müşteri Düzenle |
| | `customers.delete` | Müşteri Sil |
| **Stok** | `stock.view` | Stok Görüntüle |
| | `stock.adjust` | Stok Düzenleme |
| | `stock.count` | Sayım Yap |
| | `stock.transfer` | Stok Transfer |
| **Rapor** | `reports.basic` | Temel Raporlar |
| | `reports.advanced` | Gelişmiş Raporlar |
| | `reports.export` | Rapor Dışa Aktar |
| **Gelir-Gider** | `income.view` | Gelir Görüntüle |
| | `income.create` | Gelir Ekle |
| | `expense.view` | Gider Görüntüle |
| | `expense.create` | Gider Ekle |
| **E-Fatura** | `einvoice.view` | E-Fatura Görüntüle |
| | `einvoice.create` | E-Fatura Oluştur |
| | `einvoice.cancel` | E-Fatura İptal |
| **Personel** | `staff.view` | Personel Görüntüle |
| | `staff.create` | Personel Ekle |
| | `staff.edit` | Personel Düzenle |
| **Donanım** | `hardware.view` | Donanım Görüntüle |
| | `hardware.manage` | Donanım Yönet |
| **Şube** | `branches.view` | Şubeleri Görüntüle |
| | `branches.create` | Şube Ekle |
| | `branches.edit` | Şube Düzenle |
| **Yönetim** | `settings.manage` | Ayar Yönetimi |
| | `modules.manage` | Modül Yönetimi |
| | `roles.manage` | Rol Yönetimi |
| | `users.view` | Kullanıcıları Görüntüle |
| | `users.create` | Kullanıcı Ekle |
| | `users.edit` | Kullanıcı Düzenle |

### 4.3 Yetki Kontrol Akışı
```
1. Super Admin → TÜM İzinlere Sahip (is_super_admin = true)
2. Birincil Rol kontrolü (users.role_id → roles → role_permissions)
3. Ek Roller kontrolü (user_roles pivot tablosu)
```

### 4.4 Middleware
| Alias | Sınıf | Kullanım |
|-------|-------|----------|
| `permission` | `CheckPermission` | `Route::middleware('permission:sales.create')` |
| `module` | `CheckModule` | `Route::middleware('module:hardware')` |
| `super_admin` | `SuperAdmin` | `Route::middleware('super_admin')` |
| `tenant` | `ResolveTenant` | Otomatik — tüm web/api isteklerinde |

---

## 5. MODÜL SİSTEMİ

### 5.1 Modüller (10 adet)
| Kod | Ad | Core | Scope | Açıklama |
|-----|-----|------|-------|----------|
| `core_pos` | Temel POS | ✅ | tenant | **Zorunlu** — Ürün, satış, müşteri, stok, raporlama |
| `hardware` | Donanım Sürücüleri | ❌ | branch | Yazıcı, barkod okuyucu, kasa, terazi |
| `einvoice` | E-Fatura / E-Arşiv | ❌ | tenant | GİB entegrasyonu |
| `income_expense` | Gelir-Gider | ❌ | tenant | Gelir/gider takibi |
| `staff` | Personel Yönetimi | ❌ | both | Giriş-çıkış, hareket kaydı |
| `advanced_reports` | Gelişmiş Raporlar | ❌ | tenant | Korelasyon, tarihsel analiz |
| `api_access` | API Erişimi | ❌ | tenant | Dış sistem REST API |
| `mobile_premium` | Mobil Premium | ❌ | tenant | Mobil uygulama özellikleri |
| `marketing` | Pazarlama | ❌ | tenant | Kampanya, segment, sadakat, teklif |
| `sms` | SMS Yönetimi | ❌ | tenant | Toplu SMS, şablon, senaryo |

### 5.2 Modül Aktivasyon Zinciri
```
Plan → Plan Modülleri (plan_modules)
  ↓
Tenant → Tenant Modülleri (tenant_modules) — plan'daki + ekstra aktive edilenler
  ↓
Branch → Branch Modülleri (branch_modules) — scope 'branch' veya 'both' olanlar
```

---

## 6. İŞ MANTIKLARI (POS İçin Kritik)

### 6.1 Satış İşlemi Akışı
```
1. Kasada ürünler barkod/arama ile sepete eklenir
2. Her kalem: product_name, barcode, quantity, unit_price, discount, vat_rate/amount saklanır
3. Ödeme: cash/card/credit/mixed — mixed durumda cash_amount + card_amount ayrı tutulur
4. sales tablosuna kayıt:
   - subtotal = Σ(unit_price × quantity - discount)
   - vat_total = Σ(vat_amount)
   - discount_total = Σ(kalem indirimleri) + genel indirim
   - grand_total = subtotal + vat_total - discount_total
5. Eğer customer_id varsa ve payment_method = 'credit':
   - customer.balance güncellenir (borç artar)
   - account_transactions'a yeni kayıt (type: 'sale')
6. Stok düşümü: products.stock_quantity güncellenir
7. sold_at: satış tarihi/saati
8. application: 'web' / 'pos' / 'mobile' (hangi client)
```

### 6.2 İade İşlemi
```
- sales.status → 'refunded' veya 'cancelled'
- Stok geri eklenir
- Müşteri bakiyesi güncellenir (varsa)
- İzin: sales.refund veya sales.cancel
```

### 6.3 Cari Hesap
```
- Veresiye satış: customer.balance -= grand_total (bakiye azalır, borç artar)
- Tahsilat: customer.balance += amount (bakiye artar, borç azalır)
- Her hareket account_transactions'a kaydedilir
- balance > 0: Alacaklı (müşterinin firmaya borcu var)
- balance < 0: Borçlu (firmanın müşteriye borcu var)
```

### 6.4 Stok Yönetimi
```
- Satış: stok düşer
- Alış faturası: stok artar
- Sayım: fark varsa düzeltme
- Transfer: şubeler arası (branch_product pivot)
- Uyarı seviyesi: products.critical_stock
```

### 6.5 Vergi Hesaplama
```
- KDV Oranları: %1, %10, %20 (products.vat_rate)
- KDV dahildir (Türk mevzuatına uygun)
- Ek vergiler (ÖTV, ÖİV): products.additional_taxes JSON
- tax_rates tablosunda özel vergi oranları tanımlanabilir
```

### 6.6 Kampanya Uygulama
```
1. Kampanya tipi: percentage/fixed_amount/buy_x_get_y
2. Koşullar: min_purchase_amount, target_products, target_categories, target_segments
3. Limitler: usage_limit, per_customer_limit
4. Kupon kodu: coupon_code (opsiyonel)
5. Kampanya uygulandığında campaign_usages'a kayıt
```

### 6.7 Sadakat Puanı
```
1. Satış tamamlandığında: points = grand_total × points_per_currency
2. Harcama: min_redeem_points kontrol → TL karşılığı = points × currency_per_point
3. loyalty_points tablosunda earn/redeem/expire/bonus/adjustment kayıtları
4. balance_after ile anlık bakiye takibi
```

---

## 7. POS YAZILIMI İÇİN ENTEGRASYON REHBERİ

### 7.1 POS'un Emare Finance ile Bağlantı Yöntemleri

#### Yöntem 1: Doğrudan API (Önerilen)
- Mevcut REST API endpoint'lerini kullan
- `auth:sanctum` token-based auth
- `/api/v1/*` altında tüm CRUD

#### Yöntem 2: Ortak Veritabanı
- Aynı MariaDB veritabanına bağlan
- `sales`, `sale_items`, `products`, `customers` tablolarını kullan
- **DİKKAT:** `tenant_id` ve `branch_id` doğru set edilmeli

#### Yöntem 3: Yeni API Endpoint'leri
- POS için özel API endpoint'leri oluşturulabilir (`/api/pos/*`)
- Satış kayıt, stok sorgulama, müşteri arama

### 7.2 POS'un YAZMASI Gereken Tablolar

| Tablo | İşlem | Açıklama |
|-------|-------|----------|
| `sales` | INSERT | Yeni satış kaydı |
| `sale_items` | INSERT | Satış kalem detayları |
| `products` | UPDATE | stock_quantity düşürme |
| `branch_product` | UPDATE | Şubeye özel stok |
| `stock_movements` | INSERT | Stok hareketi kaydı |
| `account_transactions` | INSERT | Veresiye satışta cari hareket |
| `customers` | UPDATE | Bakiye güncelleme |
| `campaign_usages` | INSERT | Kampanya kullanım kaydı |
| `loyalty_points` | INSERT | Puan kazanım/harcama |

### 7.3 POS'un OKUMASASI Gereken Tablolar

| Tablo | Amaç |
|-------|------|
| `products` + `categories` | Ürün listesi ve kategoriler |
| `branch_product` | Şubeye özel fiyat ve stok |
| `customers` | Müşteri arama, bakiye sorgulama |
| `campaigns` | Aktif kampanyalar |
| `loyalty_programs` | Sadakat programı kuralları |
| `tax_rates` | Vergi oranları |
| `payment_types` | Ödeme yöntemleri |
| `hardware_devices` + `hardware_drivers` | Yazıcı, barkod okuyucu config |

### 7.4 Satış Kayıt Formatı (POS → DB)
```json
{
  "receipt_no": "POS-2026-000001",
  "branch_id": 1,
  "customer_id": null,
  "user_id": 1,
  "payment_method": "cash",
  "total_items": 3,
  "subtotal": 150.00,
  "vat_total": 30.00,
  "additional_tax_total": 0.00,
  "discount_total": 10.00,
  "grand_total": 170.00,
  "discount": 10.00,
  "cash_amount": 170.00,
  "card_amount": 0.00,
  "status": "completed",
  "staff_name": "Ahmet Yılmaz",
  "application": "pos",
  "sold_at": "2026-03-02 14:30:00",
  "items": [
    {
      "product_id": 1,
      "product_name": "Ürün A",
      "barcode": "8690000000001",
      "quantity": 2.00,
      "unit_price": 50.00,
      "discount": 5.00,
      "vat_rate": 20,
      "vat_amount": 19.00,
      "additional_taxes": null,
      "additional_tax_amount": 0.00,
      "total": 95.00
    }
  ]
}
```

### 7.5 Donanım Entegrasyonu
```
POS yazılımı şu donanımları desteklemeli:
- Fiş Yazıcı (ESC/POS, Star): hardware_devices.type = 'printer'
- Barkod Okuyucu: hardware_devices.type = 'barcode_scanner'  
- Para Çekmecesi: hardware_devices.type = 'cash_drawer'
- Müşteri Ekranı: hardware_devices.type = 'display'
- Terazi: hardware_devices.type = 'scale'
- Kart Okuyucu: hardware_devices.type = 'card_reader'

Bağlantı yöntemleri: usb, network (IP:port), serial, bluetooth
Donanım ayarları hardware_devices.settings JSON'da saklanır
Varsayılan cihaz: is_default = true
```

### 7.6 Kimlik Doğrulama
```
- POS'ta kasiyerler email + şifre ile login olmalı
- User.branch_id → aktif şube
- User.role_id → izinler (sales.create, sales.discount, sales.refund vs.)
- Kasadaki işlem izinleri:
  - Satış: sales.create
  - İndirim: sales.discount  
  - İade: sales.refund
  - İptal: sales.cancel
```

---

## 8. İLİŞKİ DİYAGRAMI

```
                    ┌──────────┐
                    │  plans   │
                    └────┬─────┘
                         │ hasMany
                    ┌────▼─────┐      belongsToMany
                    │ tenants  │◄──────── modules
                    └────┬─────┘      (tenant_modules)
                         │ hasMany
              ┌──────────┼──────────┐
              │          │          │
         ┌────▼───┐ ┌───▼────┐ ┌───▼────┐
         │branches│ │ users  │ │sms_*   │
         └────┬───┘ └───┬────┘ └────────┘
              │         │
    ┌─────────┼─────┐   │ hasMany      ┌──────────┐
    │         │     │   ├──────────────►│  sales   │
    │    ┌────▼──┐  │   │              └────┬─────┘
    │    │ staff │  │   │                   │ hasMany
    │    └───────┘  │   │              ┌────▼──────┐
    │               │   │              │sale_items │
    │    ┌──────────▼─┐ │              └───────────┘
    │    │hardware_   │ │
    │    │devices     │ │
    │    └────────────┘ │
    │                   │
    │ belongsToMany     │
    │ (branch_product)  │
    │         │         │
    │    ┌────▼─────┐   │
    └───►│products  │◄──┘
         └────┬─────┘
              │ belongsTo
         ┌────▼──────┐
         │categories │
         └───────────┘

    ┌──────────┐     ┌─────────────────┐
    │customers │────►│account_         │
    └────┬─────┘     │transactions     │
         │           └─────────────────┘
         │ belongsToMany
    ┌────▼───────────┐
    │customer_       │
    │segments        │
    └────┬───────────┘
         │
    ┌────▼───────────┐
    │campaigns       │
    └────────────────┘
```

---

## 9. MEVCUT VERİ DURUMU

| Tablo | Kayıt Sayısı |
|-------|-------------|
| products | 2 |
| customers | 1 |
| sales | 0 |
| categories | 2 |
| branches | 4 |
| staff | 0 |
| firms | 0 |
| users | 5 |
| roles | 5 |
| permissions | 41 |
| modules | 10 |
| tenants | 4 |
| plans | 3 |

---

## 10. ÖNEMLİ NOTLAR

### 10.1 Para Formatı
- Türk Lirası (TRY / ₺)
- Ondalık: virgül (,)
- Binlik: nokta (.)
- Örnek: ₺1.234,56
- Veritabanında: DECIMAL(14,2) veya DECIMAL(12,2) — nokta ile

### 10.2 Tarih/Saat
- Timezone: Europe/Istanbul (UTC+3)
- DB'de: TIMESTAMP (UTC saklanır)
- PHP/Display: Carbon ile `Europe/Istanbul`
- Locale: Türkçe (`tr`)

### 10.3 Soft Delete
- `sales`, `products`, `customers`, `firms`, `purchase_invoices`, `branches`, `e_invoices`, `campaigns`, `marketing_messages`, `quotes`, `recurring_invoices`
- `deleted_at` NULL ise aktif, TIMESTAMP ise silinmiş

### 10.4 External ID Sistemi
- Birçok tabloda `external_id` alanı var
- Dış sistemlerden (BenimPOS vs.) veri senkronizasyonu için kullanılır
- POS yazılımı kendi external_id'lerini atayabilir

### 10.5 Multi-Tenant Veri İzolasyonu
- `BelongsToTenant` trait kullanan modeller otomatik olarak `tenant_id` filtresi uygular
- POS yazılımı doğru `tenant_id` ile çalışmalı
- Farklı tenant'ların verileri birbirine karışmamalı

### 10.6 Uygulama (application) Alanı
- `sales.application` alanı satışın hangi platformdan yapıldığını gösterir
- Değerler: `web`, `pos`, `mobile`
- POS yazılımı satışlarda `application: 'pos'` kullanmalı

---

## 11. GELİŞTİRME ÖNERİLERİ

### 11.1 POS API Endpoint Önerileri
POS için şu ek API endpoint'leri oluşturulabilir:

```
POST   /api/pos/sales              → Yeni satış kaydet
POST   /api/pos/sales/{id}/refund  → İade işlemi
GET    /api/pos/products/search    → Barkod/isim ile hızlı arama
GET    /api/pos/customers/search   → Telefon/isim ile müşteri arama
POST   /api/pos/cash-register/open → Kasa açılış
POST   /api/pos/cash-register/close → Kasa kapanış (Z raporu)
GET    /api/pos/campaigns/active   → Aktif kampanyalar
POST   /api/pos/loyalty/earn       → Puan kazanım
POST   /api/pos/loyalty/redeem     → Puan harcama
POST   /api/pos/print/receipt      → Fiş yazdır
GET    /api/pos/sync/products      → Ürün senkronizasyonu  
GET    /api/pos/sync/customers     → Müşteri senkronizasyonu
```

### 11.2 Offline Çalışma
- POS internet kesildiğinde çalışabilmeli
- Satışlar lokalde saklanıp internet gelince senkronize edilmeli
- SQLite veya IndexedDB ile offline cache

### 11.3 Fiş Formatı
```
================================
        EMARE FİNANS
     [Şube Adı]
     [Şube Adresi]
     [Şube Telefon]
================================
Fiş No: POS-2026-000001
Tarih:  02.03.2026 14:30
Kasiyer: Ahmet Yılmaz
--------------------------------
Ürün A x2     ₺100,00
  İndirim       -₺5,00
Ürün B x1      ₺50,00
--------------------------------
Ara Toplam:     ₺145,00
KDV (%20):       ₺29,00
İndirim:        -₺10,00
================================
TOPLAM:         ₺170,00
================================
Nakit:          ₺170,00
================================
    Teşekkür ederiz!
  [Firma Adı]
================================
```

---

> **Bu döküman, Emare Finance yazılımının POS entegrasyonu için ihtiyaç duyulan tüm teknik detayları içermektedir. POS yazılımı geliştiren yapay zeka bu dökümanı okuyarak mevcut sisteme tam uyumlu bir çözüm üretebilir.**
