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
use App\Http\Controllers\Admin\AdminController;

// Auth routes
Route::get('/login', [PosLoginController::class, 'showLogin'])->name('pos.login');
Route::post('/login', [PosLoginController::class, 'login']);
Route::post('/logout', [PosLoginController::class, 'logout'])->name('pos.logout');

// POS Routes (authenticated)
Route::middleware(['auth', \App\Http\Middleware\ResolveTenant::class])->group(function () {
    
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
    
    // Müşteriler (Customers)
    Route::get('/customers', [CustomerController::class, 'index'])->name('pos.customers');
    Route::post('/customers', [CustomerController::class, 'store'])->name('pos.customers.store');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('pos.customers.show');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('pos.customers.update');
    Route::post('/customers/{customer}/payment', [CustomerController::class, 'addPayment'])->name('pos.customers.payment');
    
    // Raporlar (Reports)
    Route::get('/reports', [ReportController::class, 'index'])->name('pos.reports');
    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('pos.reports.daily');
    Route::get('/reports/top-products', [ReportController::class, 'topProducts'])->name('pos.reports.top-products');
    
    // Siparişler (Orders)
    Route::get('/orders', [OrderController::class, 'index'])->name('pos.orders');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('pos.orders.show');
    Route::put('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('pos.orders.status');
    
    // Kategoriler (Categories)
    Route::get('/categories', [CategoryController::class, 'index'])->name('pos.categories');
    Route::post('/categories', [CategoryController::class, 'store'])->name('pos.categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('pos.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('pos.categories.destroy');
    
    // Şubeler (Branches)
    Route::get('/branches', [BranchController::class, 'index'])->name('pos.branches');
    Route::post('/branches', [BranchController::class, 'store'])->name('pos.branches.store');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('pos.branches.update');
    
    // Kullanıcılar (Users)
    Route::get('/users', [UserController::class, 'index'])->name('pos.users');
    Route::post('/users', [UserController::class, 'store'])->name('pos.users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('pos.users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('pos.users.destroy');
    
    // Depo / Stok (Stock)
    Route::get('/stock', [StockController::class, 'index'])->name('pos.stock');
    Route::post('/stock', [StockController::class, 'store'])->name('pos.stock.store');
    
    // Cariler (Firms)
    Route::get('/firms', [FirmController::class, 'index'])->name('pos.firms');
    Route::post('/firms', [FirmController::class, 'store'])->name('pos.firms.store');
    Route::put('/firms/{firm}', [FirmController::class, 'update'])->name('pos.firms.update');
    Route::post('/firms/{firm}/payment', [FirmController::class, 'addPayment'])->name('pos.firms.payment');
    
    // Gün İşlemleri (Day Operations)
    Route::get('/day-operations', [DayOperationController::class, 'index'])->name('pos.day-operations');
    
    // Kasa Raporu (Cash Report)
    Route::get('/cash-report', [CashReportController::class, 'index'])->name('pos.cash-report');
    Route::get('/cash-report/{register}', [CashReportController::class, 'show'])->name('pos.cash-report.show');
    
    // Ayarlar (Settings)
    Route::get('/settings', [SettingController::class, 'index'])->name('pos.settings');
    Route::put('/settings/branch', [SettingController::class, 'updateBranch'])->name('pos.settings.branch');
    Route::put('/settings/general', [SettingController::class, 'updateGeneral'])->name('pos.settings.general');

    // Gelir / Gider (Income & Expense)
    Route::get('/income-expense', [IncomeExpenseController::class, 'index'])->name('pos.income-expense');
    Route::post('/income-expense/income', [IncomeExpenseController::class, 'storeIncome'])->name('pos.income-expense.income.store');
    Route::delete('/income-expense/income/{income}', [IncomeExpenseController::class, 'destroyIncome'])->name('pos.income-expense.income.destroy');
    Route::post('/income-expense/expense', [IncomeExpenseController::class, 'storeExpense'])->name('pos.income-expense.expense.store');
    Route::delete('/income-expense/expense/{expense}', [IncomeExpenseController::class, 'destroyExpense'])->name('pos.income-expense.expense.destroy');
    Route::post('/income-expense/type', [IncomeExpenseController::class, 'storeType'])->name('pos.income-expense.type.store');
    Route::delete('/income-expense/type/{type}', [IncomeExpenseController::class, 'destroyType'])->name('pos.income-expense.type.destroy');

    // Personel (Staff)
    Route::get('/staff', [StaffController::class, 'index'])->name('pos.staff');
    Route::post('/staff', [StaffController::class, 'store'])->name('pos.staff.store');
    Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('pos.staff.update');
    Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('pos.staff.destroy');

    // Donanım Yönetimi
    Route::get('/hardware', [HardwareController::class, 'index'])->name('pos.hardware');
    Route::post('/hardware', [HardwareController::class, 'store'])->name('pos.hardware.store');
    Route::put('/hardware/{device}', [HardwareController::class, 'update'])->name('pos.hardware.update');
    Route::delete('/hardware/{device}', [HardwareController::class, 'destroy'])->name('pos.hardware.destroy');
    Route::post('/hardware/{device}/test', [HardwareController::class, 'test'])->name('pos.hardware.test');
    Route::get('/hardware/drivers', [HardwareController::class, 'drivers'])->name('pos.hardware.drivers');

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
