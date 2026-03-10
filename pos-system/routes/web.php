<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PosLoginController;
use App\Http\Controllers\Pos\DashboardController;
use App\Http\Controllers\Pos\SaleController;
use App\Http\Controllers\Pos\TableController;
use App\Http\Controllers\Pos\KitchenController;
use App\Http\Controllers\Pos\CashRegisterController;
use App\Http\Controllers\Pos\ProductController;
use App\Http\Controllers\Pos\CustomerController;
use App\Http\Controllers\Pos\ReportController;
use App\Http\Controllers\Pos\OrderController;
use App\Http\Controllers\Pos\CategoryController;
use App\Http\Controllers\Pos\BranchController;
use App\Http\Controllers\Pos\UserController;
use App\Http\Controllers\Pos\StockController;
use App\Http\Controllers\Pos\FirmController;
use App\Http\Controllers\Pos\DayOperationController;
use App\Http\Controllers\Pos\CashReportController;
use App\Http\Controllers\Pos\SettingController;
use App\Http\Controllers\Pos\IncomeExpenseController;
use App\Http\Controllers\Pos\StaffController;
use App\Http\Controllers\Pos\FeedbackController;
use App\Http\Controllers\Pos\HardwareController;
use App\Http\Controllers\Pos\PaymentTypeController;
use App\Http\Controllers\Pos\StockCountController;
use App\Http\Controllers\Pos\StockTransferController;
use App\Http\Controllers\Pos\PurchaseInvoiceController;
use App\Http\Controllers\Admin\AdminController;

// Auth routes
Route::get('/login', [PosLoginController::class, 'showLogin'])->name('pos.login');
Route::post('/login', [PosLoginController::class, 'login']);
Route::post('/logout', [PosLoginController::class, 'logout'])->name('pos.logout');

// POS Routes (authenticated)
Route::middleware(['auth', \App\Http\Middleware\ResolveTenant::class, 'throttle:120,1'])->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('pos.dashboard');
    
    // Hızlı Satış (Quick Sale / POS Screen)
    Route::get('/pos', [SaleController::class, 'index'])->name('pos.sales');
    Route::post('/pos/sale', [SaleController::class, 'store'])->name('pos.sales.store');
    Route::get('/pos/products/search', [SaleController::class, 'searchProducts'])->name('pos.products.search');
    Route::get('/pos/customers/search', [SaleController::class, 'searchCustomers'])->name('pos.customers.search');
    Route::get('/pos/recent-sales', [SaleController::class, 'recentSales'])->name('pos.sales.recent');
    Route::get('/pos/sale/{sale}', [SaleController::class, 'show'])->name('pos.sales.show');
    Route::post('/pos/sale/{sale}/refund', [SaleController::class, 'refund'])->name('pos.sales.refund');
    Route::post('/pos/refund-by-receipt', [SaleController::class, 'refundByReceipt'])->name('pos.sales.refund.search');
    Route::get('/pos/sales-list', [SaleController::class, 'list'])->name('pos.sales.list');
    
    // Masalar (Tables)
    Route::get('/tables', [TableController::class, 'index'])->name('pos.tables');
    Route::post('/tables/{table}/open', [TableController::class, 'open'])->name('pos.tables.open');
    Route::get('/tables/{table}/detail', [TableController::class, 'detail'])->name('pos.tables.detail');
    Route::post('/tables/{table}/order', [TableController::class, 'addOrder'])->name('pos.tables.order');
    Route::post('/tables/{table}/pay', [TableController::class, 'pay'])->name('pos.tables.pay');
    Route::post('/tables/{table}/pay-partial', [TableController::class, 'payPartial'])->name('pos.tables.pay.partial');
    Route::post('/tables/{table}/transfer', [TableController::class, 'transfer'])->name('pos.tables.transfer');
    // Masa Tasarımcı — CRUD
    Route::post('/tables/layout', [TableController::class, 'updateLayout'])->name('pos.tables.layout');
    Route::post('/tables/store', [TableController::class, 'storeTable'])->name('pos.tables.store');
    Route::put('/tables/{table}/update', [TableController::class, 'updateTable'])->name('pos.tables.update');
    Route::delete('/tables/{table}/destroy', [TableController::class, 'destroyTable'])->name('pos.tables.destroy');
    // Mekan (Region) CRUD
    Route::post('/regions', [TableController::class, 'storeRegion'])->name('pos.regions.store');
    Route::put('/regions/{region}', [TableController::class, 'updateRegion'])->name('pos.regions.update');
    Route::delete('/regions/{region}', [TableController::class, 'destroyRegion'])->name('pos.regions.destroy');
    
    // Mutfak (Kitchen)
    Route::get('/kitchen', [KitchenController::class, 'index'])->name('pos.kitchen');
    Route::get('/kitchen/orders', [KitchenController::class, 'getOrders'])->name('pos.kitchen.orders');
    Route::post('/kitchen/order/{order}/status', [KitchenController::class, 'updateOrderStatus'])->name('pos.kitchen.order.status');
    Route::post('/kitchen/item/{item}/status', [KitchenController::class, 'updateItemStatus'])->name('pos.kitchen.item.status');
    
    // Kasa (Cash Register)
    Route::get('/cash-register', [CashRegisterController::class, 'index'])->name('pos.cash-register');
    Route::post('/cash-register/open', [CashRegisterController::class, 'open'])->name('pos.cash-register.open');
    Route::post('/cash-register/close', [CashRegisterController::class, 'close'])->name('pos.cash-register.close');
    Route::get('/cash-register/{register}/report', [CashRegisterController::class, 'report'])->name('pos.cash-register.report');
    Route::get('/cash-register/sales-detail', [CashRegisterController::class, 'salesDetail'])->name('pos.cash-register.sales-detail');
    Route::get('/cash-register/sale-items/{sale}', [CashRegisterController::class, 'saleItems'])->name('pos.cash-register.sale-items');
    
    // Ürünler (Products)
    Route::get('/products', [ProductController::class, 'index'])->name('pos.products');
    Route::post('/products', [ProductController::class, 'store'])->name('pos.products.store');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('pos.products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('pos.products.destroy');
    Route::get('/products/{product}/history', [ProductController::class, 'history'])->name('pos.products.history');
    Route::get('/products/{product}/prices', [ProductController::class, 'getPrices'])->name('pos.products.prices');
    Route::post('/products/{product}/prices', [ProductController::class, 'storePrice'])->name('pos.products.prices.store');
    Route::put('/products/{product}/prices/{price}', [ProductController::class, 'updatePrice'])->name('pos.products.prices.update');
    Route::delete('/products/{product}/prices/{price}', [ProductController::class, 'destroyPrice'])->name('pos.products.prices.destroy');
    Route::get('/products/{product}/branches', [ProductController::class, 'getBranches'])->name('pos.products.branches');
    Route::post('/products/{product}/branches', [ProductController::class, 'syncBranches'])->name('pos.products.branches.sync');
    
    // Ürün Varyantları
    Route::get('/product-variants', [ProductController::class, 'variantTypes'])->name('pos.products.variant-types');
    Route::post('/product-variants', [ProductController::class, 'createVariantType'])->name('pos.products.variant-types.store');
    Route::delete('/product-variants/{variantType}', [ProductController::class, 'deleteVariantType'])->name('pos.products.variant-types.destroy');
    Route::post('/product-variants/{variantType}/values', [ProductController::class, 'createVariantValue'])->name('pos.products.variant-values.store');
    Route::delete('/product-variant-values/{variantValue}', [ProductController::class, 'deleteVariantValue'])->name('pos.products.variant-values.destroy');
    Route::get('/products/{product}/variants', [ProductController::class, 'getProductVariants'])->name('pos.products.variants');
    Route::post('/products/{product}/variants', [ProductController::class, 'syncProductVariants'])->name('pos.products.variants.sync');

    // Alt Ürün Tanımları
    Route::get('/products/{product}/sub-definitions', [ProductController::class, 'getSubDefinitions'])->name('pos.products.sub-definitions');
    Route::post('/products/{product}/sub-definitions', [ProductController::class, 'createSubDefinition'])->name('pos.products.sub-definitions.store');
    Route::delete('/products/{product}/sub-definitions/{subDefinition}', [ProductController::class, 'deleteSubDefinition'])->name('pos.products.sub-definitions.destroy');

    // Toplu İşlemler
    Route::post('/products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('pos.products.bulk-delete');
    Route::post('/products/bulk-assign-category', [ProductController::class, 'bulkAssignCategory'])->name('pos.products.bulk-assign');
    Route::post('/products/bulk-price-update', [ProductController::class, 'bulkPriceUpdate'])->name('pos.products.bulk-price');
    Route::post('/products/bulk-assign-branches', [ProductController::class, 'bulkAssignBranches'])->name('pos.products.bulk-branches');

    // Görsel Yükleme
    Route::post('/products/{product}/image', [ProductController::class, 'uploadImage'])->name('pos.products.image');

    // Excel İçe/Dışa Aktarma
    Route::get('/products-export', [ProductController::class, 'exportExcel'])->name('pos.products.export');
    Route::post('/products-import', [ProductController::class, 'importExcel'])->name('pos.products.import');

    // Ürün Özet Dökümü & Barkod Etiketi
    Route::get('/products-summary', [ProductController::class, 'summary'])->name('pos.products.summary');
    Route::post('/products-labels', [ProductController::class, 'generateLabels'])->name('pos.products.labels');
    Route::post('/products-sort', [ProductController::class, 'updateSortOrder'])->name('pos.products.sort');

    // Filtre Şablonları
    Route::post('/products/filter-templates', [ProductController::class, 'saveFilterTemplate'])->name('pos.products.filter-templates.store');
    Route::delete('/products/filter-templates/{template}', [ProductController::class, 'deleteFilterTemplate'])->name('pos.products.filter-templates.destroy');
    
    // Müşteriler (Customers)
    Route::get('/customers', [CustomerController::class, 'index'])->name('pos.customers');
    Route::post('/customers', [CustomerController::class, 'store'])->name('pos.customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('pos.customers.show');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('pos.customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('pos.customers.destroy');
    Route::post('/customers/{customer}/payment', [CustomerController::class, 'addPayment'])->name('pos.customers.payment');
    Route::post('/customer-groups', [CustomerController::class, 'storeGroup'])->name('pos.customer-groups.store');
    Route::put('/customer-groups/{group}', [CustomerController::class, 'updateGroup'])->name('pos.customer-groups.update');
    Route::delete('/customer-groups/{group}', [CustomerController::class, 'destroyGroup'])->name('pos.customer-groups.destroy');
    
    // Raporlar (Reports)
    Route::get('/reports', [ReportController::class, 'index'])->name('pos.reports');
    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('pos.reports.daily');
    Route::get('/reports/top-products', [ReportController::class, 'topProducts'])->name('pos.reports.top-products');
    Route::get('/reports/profit-loss', [ReportController::class, 'profitLoss'])->name('pos.reports.profit-loss');
    Route::get('/reports/staff', [ReportController::class, 'staffReport'])->name('pos.reports.staff');
    Route::get('/reports/categories', [ReportController::class, 'categoryReport'])->name('pos.reports.categories');
    Route::get('/reports/comparison', [ReportController::class, 'periodComparison'])->name('pos.reports.comparison');
    Route::get('/reports/suspicious', [ReportController::class, 'suspiciousTransactions'])->name('pos.reports.suspicious');
    
    // Siparişler (Orders)
    Route::get('/orders', [OrderController::class, 'index'])->name('pos.orders');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('pos.orders.show');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('pos.orders.status');
    
    // Kategoriler (Categories)
    Route::get('/categories', [CategoryController::class, 'index'])->name('pos.categories');
    Route::get('/categories/tree', [CategoryController::class, 'tree'])->name('pos.categories.tree');
    Route::post('/categories', [CategoryController::class, 'store'])->name('pos.categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('pos.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('pos.categories.destroy');
    
    // Şubeler (Branches)
    Route::get('/branches', [BranchController::class, 'index'])->name('pos.branches');
    Route::post('/branches', [BranchController::class, 'store'])->name('pos.branches.store');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('pos.branches.update');
    Route::get('/branches/{branch}/modules', [BranchController::class, 'modules'])->name('pos.branches.modules');
    Route::post('/branches/{branch}/modules', [BranchController::class, 'updateModules'])->name('pos.branches.modules.update');
    Route::get('/branches/{branch}/devices', [BranchController::class, 'devices'])->name('pos.branches.devices');
    Route::post('/branches/{branch}/device-settings', [BranchController::class, 'updateDeviceSettings'])->name('pos.branches.device-settings');
    Route::get('/branches/{branch}/stats', [BranchController::class, 'stats'])->name('pos.branches.stats');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('pos.branches.destroy');
    
    // Kullanıcılar (Users)
    Route::get('/users', [UserController::class, 'index'])->name('pos.users');
    Route::post('/users', [UserController::class, 'store'])->name('pos.users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('pos.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('pos.users.destroy');
    
    // Depo / Stok (Stock)
    Route::get('/stock', [StockController::class, 'index'])->name('pos.stock');
    Route::post('/stock', [StockController::class, 'store'])->name('pos.stock.store');

    // Stok Sayımı (Stock Count / Inventory)
    Route::get('/stock-count', [StockCountController::class, 'index'])->name('pos.stock-count');
    Route::post('/stock-count', [StockCountController::class, 'store'])->name('pos.stock-count.store');
    Route::get('/stock-count/{stockCount}', [StockCountController::class, 'show'])->name('pos.stock-count.show');
    Route::post('/stock-count/{stockCount}/apply', [StockCountController::class, 'apply'])->name('pos.stock-count.apply');
    Route::delete('/stock-count/{stockCount}', [StockCountController::class, 'destroy'])->name('pos.stock-count.destroy');

    // Şubeler Arası Transfer
    Route::get('/stock-transfers', [StockTransferController::class, 'index'])->name('pos.stock-transfers');
    Route::post('/stock-transfers', [StockTransferController::class, 'store'])->name('pos.stock-transfers.store');
    Route::get('/stock-transfers/{transfer}', [StockTransferController::class, 'show'])->name('pos.stock-transfers.show');
    Route::post('/stock-transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->name('pos.stock-transfers.approve');
    Route::post('/stock-transfers/{transfer}/reject', [StockTransferController::class, 'reject'])->name('pos.stock-transfers.reject');

    // Alış Faturaları (Purchase Invoices)
    Route::get('/purchase-invoices', [PurchaseInvoiceController::class, 'index'])->name('pos.purchase-invoices');
    Route::post('/purchase-invoices', [PurchaseInvoiceController::class, 'store'])->name('pos.purchase-invoices.store');
    Route::get('/purchase-invoices/{invoice}', [PurchaseInvoiceController::class, 'show'])->name('pos.purchase-invoices.show');
    Route::put('/purchase-invoices/{invoice}', [PurchaseInvoiceController::class, 'update'])->name('pos.purchase-invoices.update');
    Route::delete('/purchase-invoices/{invoice}', [PurchaseInvoiceController::class, 'destroy'])->name('pos.purchase-invoices.destroy');
    
    // Cariler (Firms)
    Route::get('/firms', [FirmController::class, 'index'])->name('pos.firms');
    Route::post('/firms', [FirmController::class, 'store'])->name('pos.firms.store');
    Route::get('/firms/{firm}', [FirmController::class, 'show'])->name('pos.firms.show');
    Route::put('/firms/{firm}', [FirmController::class, 'update'])->name('pos.firms.update');
    Route::delete('/firms/{firm}', [FirmController::class, 'destroy'])->name('pos.firms.destroy');
    Route::post('/firms/{firm}/payment', [FirmController::class, 'addPayment'])->name('pos.firms.payment');
    Route::post('/firm-groups', [FirmController::class, 'storeGroup'])->name('pos.firm-groups.store');
    Route::put('/firm-groups/{group}', [FirmController::class, 'updateGroup'])->name('pos.firm-groups.update');
    Route::delete('/firm-groups/{group}', [FirmController::class, 'destroyGroup'])->name('pos.firm-groups.destroy');
    
    // Gün İşlemleri (Day Operations)
    Route::get('/day-operations', [DayOperationController::class, 'index'])->name('pos.day-operations');
    
    // Kasa Raporu (Cash Report)
    Route::get('/cash-report', [CashReportController::class, 'index'])->name('pos.cash-report');
    Route::get('/cash-report/{register}', [CashReportController::class, 'show'])->name('pos.cash-report.show');
    
    // Ayarlar (Settings)
    Route::get('/settings', [SettingController::class, 'index'])->name('pos.settings');
    Route::put('/settings/branch', [SettingController::class, 'updateBranch'])->name('pos.settings.branch');
    Route::put('/settings/general', [SettingController::class, 'updateGeneral'])->name('pos.settings.general');

    // Ödeme Türleri (Payment Types)
    Route::get('/payment-types', [PaymentTypeController::class, 'index'])->name('pos.payment-types.index');
    Route::post('/payment-types', [PaymentTypeController::class, 'store'])->name('pos.payment-types.store');
    Route::put('/payment-types/{paymentType}', [PaymentTypeController::class, 'update'])->name('pos.payment-types.update');
    Route::delete('/payment-types/{paymentType}', [PaymentTypeController::class, 'destroy'])->name('pos.payment-types.destroy');

    // Gelir / Gider (Income & Expense)
    Route::get('/income-expense', [IncomeExpenseController::class, 'index'])->name('pos.income-expense');
    Route::post('/income-expense/income', [IncomeExpenseController::class, 'storeIncome'])->name('pos.income-expense.income.store');
    Route::put('/income-expense/income/{income}', [IncomeExpenseController::class, 'updateIncome'])->name('pos.income-expense.income.update');
    Route::delete('/income-expense/income/{income}', [IncomeExpenseController::class, 'destroyIncome'])->name('pos.income-expense.income.destroy');
    Route::post('/income-expense/expense', [IncomeExpenseController::class, 'storeExpense'])->name('pos.income-expense.expense.store');
    Route::put('/income-expense/expense/{expense}', [IncomeExpenseController::class, 'updateExpense'])->name('pos.income-expense.expense.update');
    Route::delete('/income-expense/expense/{expense}', [IncomeExpenseController::class, 'destroyExpense'])->name('pos.income-expense.expense.destroy');
    Route::post('/income-expense/type', [IncomeExpenseController::class, 'storeType'])->name('pos.income-expense.type.store');
    Route::delete('/income-expense/type/{type}', [IncomeExpenseController::class, 'destroyType'])->name('pos.income-expense.type.destroy');

    // Personel (Staff)
    Route::get('/staff', [StaffController::class, 'index'])->name('pos.staff');
    Route::post('/staff', [StaffController::class, 'store'])->name('pos.staff.store');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('pos.staff.update');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('pos.staff.destroy');
    Route::get('/staff/{staff}/performance', [StaffController::class, 'performance'])->name('pos.staff.performance');

    // Donanım Yönetimi
    Route::get('/hardware', [HardwareController::class, 'index'])->name('pos.hardware');
    Route::post('/hardware', [HardwareController::class, 'store'])->name('pos.hardware.store');
    Route::put('/hardware/{device}', [HardwareController::class, 'update'])->name('pos.hardware.update');
    Route::delete('/hardware/{device}', [HardwareController::class, 'destroy'])->name('pos.hardware.destroy');
    Route::post('/hardware/{device}/test', [HardwareController::class, 'test'])->name('pos.hardware.test');
    Route::get('/hardware/drivers', [HardwareController::class, 'drivers'])->name('pos.hardware.drivers');
    // Yazdırma endpoint'leri
    Route::post('/hardware/{device}/print', [HardwareController::class, 'print'])->name('pos.hardware.print');
    Route::post('/hardware/print-receipt', [HardwareController::class, 'printReceipt'])->name('pos.hardware.print-receipt');
    Route::post('/hardware/print-kitchen', [HardwareController::class, 'printKitchen'])->name('pos.hardware.print-kitchen');
    Route::post('/hardware/open-drawer', [HardwareController::class, 'openDrawer'])->name('pos.hardware.open-drawer');

    // Geri Bildirim — API (widget)
    Route::post('/api/feedback', [FeedbackController::class, 'store'])->name('pos.feedback.store');
    Route::get('/api/feedback/my', [FeedbackController::class, 'my'])->name('pos.feedback.my');

    // Geri Bildirim — Admin yönetim sayfası
    Route::get('/feedback', [FeedbackController::class, 'index'])->name('pos.feedback');
    Route::patch('/feedback/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('pos.feedback.status');
    Route::post('/feedback/{feedback}/reply', [FeedbackController::class, 'reply'])->name('pos.feedback.reply');
    Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('pos.feedback.destroy');
});

// ─── Süper Admin Paneli ──────────────────────────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\SuperAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/',           [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/tenants',    [AdminController::class, 'tenants'])->name('tenants');
        Route::post('/tenants',   [AdminController::class, 'tenantStore'])->name('tenants.store');
        Route::patch('/tenants/{tenant}/status',  [AdminController::class, 'tenantStatus'])->name('tenants.status');
        Route::delete('/tenants/{tenant}',        [AdminController::class, 'tenantDestroy'])->name('tenants.destroy');
        Route::get('/feedbacks',  [AdminController::class, 'feedbacks'])->name('feedbacks');
        Route::get('/users',      [AdminController::class, 'users'])->name('users');
    });
