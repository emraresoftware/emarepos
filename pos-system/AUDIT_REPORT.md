# 🔍 POS System Kapsamlı Kod Denetim Raporu

**Tarih:** 2025  
**Kapsam:** `pos-system/` — 25 Controller, 48+ Model, 4 Service, 28+ Blade View, Routes

---

## 🔴 KRİTİK BUGLAR (Hemen Düzeltilmeli)

### BUG-01: Income/Expense modellerinde `branch_id` alanı yok — Kâr/Zarar raporu yanlış sonuç veriyor

**Dosya:** [app/Http/Controllers/Pos/ReportController.php](app/Http/Controllers/Pos/ReportController.php#L197-L204)  
**İlgili Modeller:** [app/Models/Income.php](app/Models/Income.php), [app/Models/Expense.php](app/Models/Expense.php)

**Sorun:** `profitLoss()` metodu Income ve Expense tablolarını `where('branch_id', $branchId)` ile filtreliyor. Ancak Income ve Expense modellerinde `branch_id` field'ı **hiç yok** (fillable'da bulunmuyor, migration'da da yok). Bu query sessizce **0** döndürür — gider ve diğer gelirler rapordan tamamen düşer.

```php
// ReportController.php L197 — ÇALIŞMİYOR
$expenses = Expense::where('branch_id', $branchId)->sum('amount');

// ReportController.php L203 — ÇALIŞMİYOR
$otherIncome = Income::where('branch_id', $branchId)->sum('amount');
```

**Çözüm:**
1. Migration ekle: `branch_id` nullable sütunu Income ve Expense tablolarına
2. Income ve Expense modellerinin `$fillable` listesine `branch_id` ekle
3. `IncomeExpenseController::storeIncome` ve `storeExpense` metodlarına `$data['branch_id'] = session('branch_id')` ekle
4. Mevcut kayıtları mümkünse doğru branch ile güncelle

---

### BUG-02: `payment_method` validasyon uyuşmazlığı — Özel ödeme türleri 422 hata alıyor

**Dosya:** [app/Http/Controllers/Pos/SaleController.php](app/Http/Controllers/Pos/SaleController.php#L126)  
**Blade:** [resources/views/pos/sales/index.blade.php](resources/views/pos/sales/index.blade.php#L1192-L1214)

**Sorun:** Satış ekranında "Diğer Ödeme Türleri" butonları `other_xxx` formatında ödeme yapıyor (örn. `other_havale`). JS bu değeri `actualMethod = method` olarak doğrudan payload'a koyuyor. Ancak controller'da validation:

```php
// SaleController.php L126
'payment_method' => 'required|string|in:cash,card,credit,mixed,transfer',
```

`other_havale`, `other_yemek_karti`, vb. değerleri **geçersiz** — 422 Validation Error döner.

**Çözüm:**
```php
// Seçenek A: Validation genişlet
'payment_method' => 'required|string',

// Seçenek B: regex ile
'payment_method' => ['required', 'string', 'regex:/^(cash|card|credit|mixed|transfer|other_.+)$/'],
```
Ayrıca JS tarafında `actualMethod` mantığı düzeltilmeli — `other_xxx` geldiğinde method transfer olarak gönderip notes'a açıklama eklenebilir.

---

### BUG-03: CashReportController ana sorgusu `branch_id` filtresi yok

**Dosya:** [app/Http/Controllers/Pos/CashReportController.php](app/Http/Controllers/Pos/CashReportController.php#L14-L29)

**Sorun:** `index()` metodu CashRegister kayıtlarını `branch_id` filtresiz çekiyor. Tüm şubelerin kasa kayıtları karışık listeleniyor.

```php
// L14 — branch_id FİLTRESİ YOK
$query = CashRegister::with('user')
    ->orderBy('opened_at', 'desc');
```

İstatistikler de benzer şekilde filtre yok:
```php
// L34-38 — GLOBAL toplamlar, şube bazlı değil
'total_registers' => CashRegister::count(),
'total_sales_all' => CashRegister::where('status', 'closed')->sum('total_sales'),
```

**Çözüm:**
```php
$branchId = session('branch_id');
$query = CashRegister::with('user')
    ->where('branch_id', $branchId)  // ← EKLE
    ->orderBy('opened_at', 'desc');

// Stats'a da branch filtresi:
'total_registers' => CashRegister::where('branch_id', $branchId)->count(),
```

---

### BUG-04: `SaleController::show` ve `refund` — Yetkilendirme kontrolü yok

**Dosya:** [app/Http/Controllers/Pos/SaleController.php](app/Http/Controllers/Pos/SaleController.php#L177-L200)

**Sorun:** `show()` ve `refund()` metodları route model binding ile Sale alıyor ama **hiçbir branch_id veya tenant_id kontrolü yapmıyor**. Herhangi bir oturum açmış kullanıcı, başka bir şubenin veya tenant'ın satışını görüntüleyebilir/iade edebilir (ID biliyorsa).

```php
// L177 — Kontrol YOK
public function show(Sale $sale) {
    $sale->load(['items', 'customer', 'user']);
    return response()->json($sale);
}

// L184 — Kontrol YOK
public function refund(Request $request, Sale $sale) {
    $result = $this->saleService->refundSale($sale->id, ...);
}
```

**Çözüm:**
```php
public function show(Sale $sale) {
    if ($sale->branch_id !== (int) session('branch_id')) {
        abort(403);
    }
    // ...
}
```
Not: Sale modeli BelongsToTenant kullandığı için tenant seviyesi güvenli, ancak **branch seviyesi korunmuyor**.

---

## 🟡 ORTA SEVİYE BUGLAR

### BUG-05: StockController — `branch_id` filtreleme tamamen eksik

**Dosya:** [app/Http/Controllers/Pos/StockController.php](app/Http/Controllers/Pos/StockController.php#L13-L63)

**Sorun:** `index()` metodunda `StockMovement` sorgusu branch_id filtresi **hiç yok**. `store()` metodunda yeni hareket kaydına branch_id **eklenmiyor**. StockMovement modelinde de branch_id field'ı yok.

- Tüm şubelerin stok hareketleri karışık.
- Kritik stok uyarısı da branch-agnostik.
- Stok değeri istatistikleri de branch-agnostik.

**Çözüm:** 
1. StockMovement tablosuna `branch_id` sütunu ekle (migration)
2. StockMovement modeline `branch_id` fillable ekle
3. `store()` içinde `$data['branch_id'] = session('branch_id');`
4. `index()` sorgusuna `->where('branch_id', session('branch_id'))` ekle

---

### BUG-06: IncomeExpenseController — `branch_id` filtresi tamamen eksik

**Dosya:** [app/Http/Controllers/Pos/IncomeExpenseController.php](app/Http/Controllers/Pos/IncomeExpenseController.php#L12-L67)

**Sorun:** Gelir ve gider listeleme, istatistik hesaplama — hiçbirinde branch_id filtresi yok. Tüm şubelerin gelir/giderleri karışık gösteriliyor.

Ayrıca `storeIncome()` ve `storeExpense()` branch_id kaydetmiyor (modellerde de yok — BUG-01 ile bağlantılı).

**Çözüm:** BUG-01 çözümü sonrası, tüm sorgulara branch_id filtresi ekle.

---

### BUG-07: StaffController::index — `branch_id` filtresi yok

**Dosya:** [app/Http/Controllers/Pos/StaffController.php](app/Http/Controllers/Pos/StaffController.php#L15-L32)

**Sorun:** `index()` metodu (`Staff::orderBy('name')`) branch_id filtresi olmadan tüm personeli çekiyor. Buna karşın performans raporu (`performance()`) doğru şekilde branch_id filtreliyor (L87+).

```php
// L15 — branch_id YOK
$query = Staff::orderBy('name');

// L34-35 — İstatistikler de filtre yok
'total'  => Staff::count(),
'active' => Staff::where('is_active', true)->count(),
```

**Çözüm:**
```php
$branchId = session('branch_id');
$query = Staff::where('branch_id', $branchId)->orderBy('name');

$stats = [
    'total'  => Staff::where('branch_id', $branchId)->count(),
    'active' => Staff::where('branch_id', $branchId)->where('is_active', true)->count(),
];
```

---

### BUG-08: SaleController::list — summaryStats tarih filtresi yok (performans)

**Dosya:** [app/Http/Controllers/Pos/SaleController.php](app/Http/Controllers/Pos/SaleController.php#L249-L254)

**Sorun:** Satış listesindeki `summaryStats` her zaman **tüm zamanların** toplamını çekiyor, sayfa filtreleri (start_date, end_date) uygulanmıyor. Büyük veri setlerinde hem yavaş çalışır, hem de filtrelenen döneme uymayan toplam gösterir.

```php
// L249-254 — TÜM tarihlerin toplamı
'total' => Sale::where('branch_id', $branchId)->where('status', 'completed')->sum('grand_total'),
'cash'  => Sale::where('branch_id', $branchId)->...->sum('grand_total'),
```

**Çözüm:** Query filtrelerini (tarih, status, ödeme türü) summaryStats'a da uygula ya da `$query` clone'layarak hesapla.

---

### BUG-09: Feedback modeli `BelongsToTenant` trait'i eksik

**Dosya:** [app/Models/Feedback.php](app/Models/Feedback.php)

**Sorun:** Feedback modeli tenant_id field'ına sahip ama `BelongsToTenant` trait'ini kullanmıyor. `FeedbackController` manuel olarak `where('tenant_id', ...)` filtreliyor fakat bu global scope garantisi vermiyor. İleride başka bir yerde `Feedback::all()` veya `Feedback::find()` kullanılırsa tenant izolasyonu bozulur.

**Çözüm:** Feedback modeline `use BelongsToTenant;` ekle.

---

### BUG-10: posAjax çağrı ikilemselliği — Response hata yönetimi kırılgan

**Dosya:** [resources/views/pos/sales/index.blade.php](resources/views/pos/sales/index.blade.php#L1225-L1229)

**Sorun:** posAjax fonksiyonu iki farklı signature'la çağrılıyor:
- Çoğu yerde: `posAjax(url, dataObj, 'POST')`
- sales/index: `posAjax(url, {method: 'POST', body: JSON.stringify(payload)})`

Bu pattern karışıklığa neden olabilir. İkinci kullanımda response `data.success` kontrolü yapılıyor ama HTTP 422 veya 500 geldiğinde catch bloğu genel error gösteriyor, validation error detayları kayboluyor.

**Çözüm:** posAjax kullanımını standardize et. Tüm controller'lar `{success, message, errors}` döndürüyorsa, posAjax wrapper'ını validation error'ları parse edecek şekilde güncelle.

---

### BUG-11: PurchaseInvoiceController::store — N+1 Product::find döngüde

**Dosya:** [app/Http/Controllers/Pos/PurchaseInvoiceController.php](app/Http/Controllers/Pos/PurchaseInvoiceController.php) (store metodu)

**Sorun:** `store()` metodu fatura kalemlerini işlerken her item için ayrı `Product::find($item['product_id'])` çalıştırıyor — N+1 query problemi.

**Çözüm:** Önce tüm product_id'leri topla, tek sorguda çek:
```php
$productIds = collect($request->items)->pluck('product_id');
$products = Product::whereIn('id', $productIds)->get()->keyBy('id');
// Döngüde: $products[$item['product_id']]
```

---

### BUG-12: ReportController::periodComparison — (clone $q)->count() 5+ kez tekrarlanıyor

**Dosya:** [app/Http/Controllers/Pos/ReportController.php](app/Http/Controllers/Pos/ReportController.php#L416-L427)

**Sorun:** `$getStats` closure'unda `(clone $q)->count()` 3-4 kez çalıştırılıyor. Her clone ayrı bir SQL query. Toplam her period için 8+ query var.

```php
'avg_basket' => (clone $q)->count() > 0 
    ? round((clone $q)->sum('grand_total') / (clone $q)->count(), 2) : 0
//  ^^^^^^^^^^^^^^^^^^^^^^^^  ^^^^^^^^^^^^^^^^^^^^^^^^ = 3 ayrı sorgu
```

**Çözüm:** Tek seferde çek:
```php
$result = (clone $q)->selectRaw('COUNT(*) as cnt, SUM(grand_total) as total, SUM(discount_total) as disc, SUM(total_items) as items')->first();
```

---

## 🟢 İYİLEŞTİRME ÖNERİLERİ

### IMP-01: SaleController::searchProducts — branch bazlı stok N+1

**Dosya:** [app/Http/Controllers/Pos/SaleController.php](app/Http/Controllers/Pos/SaleController.php#L72-L91)

**Sorun:** Her product için `$product->branches()->where('branch_id', $branchId)->first()` ayrı bir SQL çalıştırıyor. 50 ürün = 50 ek sorgu.

**Çözüm:**
```php
->with(['category', 'prices', 'branches' => fn($q) => $q->where('branch_id', $branchId)])
```
Sonra döngüde: `$product->branches->first()` (eager-loaded).

---

### IMP-02: Tüm sayfalama sorgularında `withQueryString()` eksik

**Dosya:** Birçok controller (`StockController::index`, `CashReportController::index`)

**Sorun:** Filtre uygulandığında, sayfalama linklerinde filtre parametreleri kaybolur.

**Çözüm:** Tüm `paginate()` çağrılarına `->withQueryString()` ekle.

---

### IMP-03: StockController::store — Transaction kullanmıyor

**Dosya:** [app/Http/Controllers/Pos/StockController.php](app/Http/Controllers/Pos/StockController.php#L66-L100)

**Sorun:** Stok güncelleme ve hareket kaydı ayrı adımlarda yapılıyor, `DB::transaction` ile sarılmamış. Araya hata girerse stok güncellenir ama hareket kaydedilmez (veya tersi).

**Çözüm:** `DB::transaction(function() { ... })` ile sar.

---

### IMP-04: CustomerController::searchCustomers — tenant_id gereksiz elle filtreleme

**Dosya:** [app/Http/Controllers/Pos/SaleController.php](app/Http/Controllers/Pos/SaleController.php#L95-L111)

**Sorun:** `searchCustomers()` içinde `->where('tenant_id', $tenantId)` manuel yazılmış ama Customer modeli `BelongsToTenant` trait kullanıyor (global scope). Manuel filtre gereksiz — kafa karıştırıcı ama zararlı değil.

**Çözüm:** Manuel `where('tenant_id')` kaldırılabilir, BelongsToTenant scope yeterlidir. Ancak ekstra koruma olarak bırakılışa da zararı yok.

---

### IMP-05: suspiciousTransactions — İç içe loop + array_merge optimizasyonu

**Dosya:** [app/Http/Controllers/Pos/ReportController.php](app/Http/Controllers/Pos/ReportController.php#L450-L538)

**Sorun:** 5 farklı sorgu sonucu array_merge ile birleştiriliyor. Her biri ayrı collection. Büyük veri setlerinde bellek tüketimi yüksek.

**Çözüm:** Collection ile `concat()` veya `merge()` kullan, final'de `sortBy` uygula.

---

### IMP-06: Tüm delete route'larında soft-delete kontrolü eksik

**Birçok controller:** Firm, Customer gibi modellerde `destroy()` metodu doğrudan `$model->delete()` çağırıyor. Bu model soft-delete kullanmıyorsa geri dönüşümsüz.

**Çözüm:** Kritik modellere `SoftDeletes` trait ekle veya silmeden önce bağlı kayıt kontrolü yap.

---

### IMP-07: Consistent Error Response Formatı

**Tüm controller'lar**

**Sorun:** Bazı controller'lar `['success' => true, 'message' => ...]`, bazıları `['success' => false, ...]` + HTTP kodu, bazıları sadece JSON döndürüyor. Standart bir format yok.

**Çözüm:** API Response trait veya helper fonksiyon oluştur:
```php
protected function success($data = [], $message = 'Başarılı', $code = 200)
protected function error($message = 'Hata', $code = 422, $errors = [])
```

---

### IMP-08: SQL Injection koruması — Raw expression'larda dikkat

**Dosya:** [app/Http/Controllers/Pos/ReportController.php](app/Http/Controllers/Pos/ReportController.php#L477)

**Sorun:** `suspiciousTransactions()` içinde `whereRaw('discount_total > 0 AND (discount_total / (grand_total + discount_total)) > 0.3')` kullanılıyor. Bu spesifik kullanım güvenli (hardcoded value) ama pattern olarak riskli.

**Not:** Gerçek bir SQL injection riski yok şu an ama `selectRaw` ve `whereRaw` kullanımlarını minimize edin.

---

## ⚪ EKSİK ÖZELLİKLER

### MISS-01: Firma silme özelliği yok

**Route:** [routes/web.php](routes/web.php) — `DELETE /pos/firms/{firm}` mevcut değil  
**Controller:** [app/Http/Controllers/Pos/FirmController.php](app/Http/Controllers/Pos/FirmController.php)

**Sorun:** Firma oluşturma ve güncelleme var ama silme metodu ve route'u yok. UI'da silme butonu da yok.

**Çözüm:**
```php
// web.php
Route::delete('/pos/firms/{firm}', [FirmController::class, 'destroy']);

// FirmController.php
public function destroy(Firm $firm) {
    // Bağlı ürün/fatura kontrolü
    $firm->delete();
    return response()->json(['success' => true]);
}
```

---

### MISS-02: Müşteri silme özelliği yok

**Route:** [routes/web.php](routes/web.php) — `DELETE /pos/customers/{customer}` mevcut değil  
**Controller:** [app/Http/Controllers/Pos/CustomerController.php](app/Http/Controllers/Pos/CustomerController.php)

**Sorun:** Müşteri CRUD'da delete yok. Veresiye bakiyesi olan müsterilerin silinmesi engellenmeli.

**Çözüm:** Route + controller metodu ekle, bakiye kontrolü ile.

---

### MISS-03: Şube silme özelliği yok

**Route:** [routes/web.php](routes/web.php)  
**Controller:** [app/Http/Controllers/Pos/BranchController.php](app/Http/Controllers/Pos/BranchController.php)

**Sorun:** Sadece `index`, `store`, `update` var. Şube silme yok (aktif satışlar, personel vb. bağımlılıklar nedeniyle soft-delete önerilir).

---

### MISS-04: Gelir/Gider düzenleme (update) özelliği yok

**Controller:** [app/Http/Controllers/Pos/IncomeExpenseController.php](app/Http/Controllers/Pos/IncomeExpenseController.php)

**Sorun:** `storeIncome`, `storeExpense`, `destroyIncome`, `destroyExpense` var ama **update** metodu yok. Kullanıcı hatalı giriş yaptığında silip tekrar eklemek zorunda.

**Çözüm:** `updateIncome(Request $request, Income $income)` ve `updateExpense(...)` ekle.

---

### MISS-05: Stok sayımında (StockCount) `branch_id` izolasyonu

**Controller:** [app/Http/Controllers/Pos/StockCountController.php](app/Http/Controllers/Pos/StockCountController.php)

**Sorun:** Stok sayımı branch seviyesinde izole değilse, farklı şubelerin stokları karışabilir.

---

### MISS-06: Toplu ürün fiyat güncelleme

**Controller:** [app/Http/Controllers/Pos/ProductController.php](app/Http/Controllers/Pos/ProductController.php)

**Sorun:** Tek tek ürün fiyatı güncellenebiliyor ama toplu fiyat artışı/azalışı (kategoriye göre, %-'ye göre) özelliği yok.

---

### MISS-07: Kullanıcı aktivite log'u

**Sorun:** Hangi kullanıcının ne zaman ne yaptığı (silme, iade, iskonto) kayıt altına alınmıyor. Suspicious transactions raporu mevcut ama proaktif log sistemi yok.

**Çözüm:** Laravel'in observer veya event sistemi ile `ActivityLog` modeli oluşturulabilir.

---

### MISS-08: API Rate Limiting

**Sorun:** AJAX endpoint'lerinde herhangi bir rate limiting yok. Bir kullanıcı kısa sürede binlerce istek gönderebilir.

**Çözüm:** Laravel throttle middleware ekle (zaten mevcut, sadece route'lara uygulanması gerekiyor):
```php
Route::middleware(['auth', 'throttle:60,1'])->group(...)
```

---

## 📊 ÖZET TABLO

| Kategori | Sayı | Önem |
|----------|------|------|
| 🔴 Kritik Bug | 4 | Veri kaybı / güvenlik riski |
| 🟡 Orta Seviye | 8 | Yanlış veri gösterimi / performans |
| 🟢 İyileştirme | 8 | Kod kalitesi / performans |
| ⚪ Eksik Özellik | 8 | Kullanılabilirlik |
| **TOPLAM** | **28** | |

---

## 🎯 ÖNCELİK SIRASI (Tavsiye)

1. **BUG-01** — Income/Expense `branch_id` eksikliği (kâr/zarar raporu tamamen yanlış)
2. **BUG-02** — `payment_method` validation (özel ödeme türleri çalışmıyor)
3. **BUG-03** — CashReport branch filtresi (güvenlik + veri doğruluğu)
4. **BUG-04** — Sale show/refund yetkilendirme (güvenlik)
5. **BUG-05** — Stock branch filtresi
6. **BUG-06** — IncomeExpense branch filtresi
7. **BUG-07** — Staff branch filtresi
8. **BUG-08** — summaryStats tarih filtresi
9. Eksik CRUD operasyonları (MISS-01/02/03/04)
10. N+1 ve performans iyileştirmeleri (IMP-01, BUG-11/12)
