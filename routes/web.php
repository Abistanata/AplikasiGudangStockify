<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\StaffTaskController;
use App\Http\Controllers\StaffReportController;
use App\Exports\IncomingReportExport;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\StaffDashboardController;
use App\Http\Controllers\ManagerDashboardController;

/*
|--------------------------------------------------------------------------
| WEB ROUTES
|--------------------------------------------------------------------------
*/

// ===================================
// HALAMAN UTAMA & AUTENTIKASI
// ===================================

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return redirect(match ($user->role) {
            'Admin'          => route('admin.dashboard'),
            'Manajer Gudang' => route('manajergudang.dashboard'),
            'Staff Gudang'   => route('staff.dashboard'),
            default          => '/login',
        });
    }
    return view('layouts.welcome');
})->name('welcome');

// GUEST ROUTES
Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => view('auth.login'))->name('login');
    Route::get('/register', fn() => view('auth.register'))->name('register');
});

// Auth actions (available for all)
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/login/simple', [AuthController::class, 'simpleLogin'])->name('login.simple');
Route::post('/register', [AuthController::class, 'register'])->name('register.process');

// Auth actions (require authentication)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
});

// ===================================
// ADMIN ROUTES
// ===================================
Route::middleware(['auth', 'role:Admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Resource Routes
    Route::resource('products', ProductController::class)->except(['destroy']);
    Route::resource('categories', CategoryController::class);
    Route::resource('suppliers', SupplierController::class)->names([
        'index'   => 'suppliers.index',
        'create'  => 'suppliers.create',
        'store'   => 'suppliers.store',
        'show'    => 'suppliers.show',
        'edit'    => 'suppliers.edit',
        'update'  => 'suppliers.update',
        'destroy' => 'suppliers.destroy',
    ]);

    // Users Management (alternative routes)
    Route::get('/users', [AdminDashboardController::class, 'userList'])->name('users.index');
    Route::get('/users/create', [AdminDashboardController::class, 'userCreate'])->name('users.create');
    Route::post('/users', [AdminDashboardController::class, 'userStore'])->name('users.store');
    Route::get('/users/{user}', [AdminDashboardController::class, 'userShow'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminDashboardController::class, 'userEdit'])->name('users.edit');
    Route::put('/users/{user}', [AdminDashboardController::class, 'userUpdate'])->name('users.update');
    Route::get('/users/{user}/delete', [AdminDashboardController::class, 'confirmDeleteUser'])->name('users.delete');
    Route::delete('/users/{user}', [AdminDashboardController::class, 'userDestroy'])->name('users.destroy');

    // Products Management (alternative routes)
    Route::get('/products', [AdminDashboardController::class, 'productList'])->name('products.index');
    Route::get('/products/create', [AdminDashboardController::class, 'productCreate'])->name('products.create');
    Route::post('/products', [AdminDashboardController::class, 'productStore'])->name('products.store');
    Route::get('/products/{product}', [AdminDashboardController::class, 'productShow'])->name('products.show');
    Route::get('/products/{product}/edit', [AdminDashboardController::class, 'productEdit'])->name('products.edit');
    Route::put('/products/{product}', [AdminDashboardController::class, 'productUpdate'])->name('products.update');
    Route::get('/products/{product}/delete', [AdminDashboardController::class, 'confirmDeleteProduct'])->name('products.confirm-delete');
    Route::delete('/products/{product}', [AdminDashboardController::class, 'destroy'])->name('products.destroy');
    Route::delete('/products/{product}/force', [AdminDashboardController::class, 'forceDestroy'])->name('products.force-destroy');
    Route::post('/products/generate-sku', [AdminDashboardController::class, 'generateSku'])->name('products.generate-sku');
    Route::post('/admin/products/generate-sku', [AdminDashboardController::class, 'generateSkuApi'])->name('admin.products.generate-sku');
    Route::get('export', [AdminDashboardController::class, 'export'])->name('products.export');
    Route::get('export-template', [AdminDashboardController::class, 'exportTemplate'])->name('products.export-template');
    Route::post('import', [AdminDashboardController::class, 'import'])->name('products.import');

    // Categories Management (alternative routes)
    Route::get('/categories', [AdminDashboardController::class, 'categoryList'])->name('categories.index');
    Route::get('/categories/create', [AdminDashboardController::class, 'categoryCreate'])->name('categories.create');
    Route::post('/categories', [AdminDashboardController::class, 'categoryStore'])->name('categories.store');
    Route::get('/categories/{category}', [AdminDashboardController::class, 'categoryShow'])->name('categories.show');
    Route::get('/categories/{category}/edit', [AdminDashboardController::class, 'categoryEdit'])->name('categories.edit');
    Route::put('/categories/{category}', [AdminDashboardController::class, 'categoryUpdate'])->name('categories.update');
    Route::get('/categories/{category}/delete', [AdminDashboardController::class, 'confirmDeleteCategory'])->name('categories.delete');
    Route::delete('/categories/{category}', [AdminDashboardController::class, 'categoryDestroy'])->name('categories.destroy');

    // Suppliers Management (alternative routes)
    Route::get('/suppliers', [AdminDashboardController::class, 'supplierList'])->name('suppliers.index');
    Route::get('/suppliers/create', [AdminDashboardController::class, 'supplierCreate'])->name('suppliers.create');
    Route::post('/suppliers', [AdminDashboardController::class, 'supplierStore'])->name('suppliers.store');
    Route::get('/suppliers/{supplier}', [AdminDashboardController::class, 'supplierShow'])->name('suppliers.show');
    Route::get('/suppliers/{supplier}/edit', [AdminDashboardController::class, 'supplierEdit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [AdminDashboardController::class, 'supplierUpdate'])->name('suppliers.update');
    Route::get('/suppliers/{supplier}/delete', [AdminDashboardController::class, 'confirmDeleteSupplier'])->name('suppliers.delete');

    // System Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
        Route::get('/transactions', [ReportController::class, 'transactions'])->name('transactions');
        Route::get('/users', [ReportController::class, 'users'])->name('users');
        Route::get('/system', [ReportController::class, 'system'])->name('system');
        // ##### PERBAIKAN: Tambahkan route export untuk admin di sini jika diperlukan #####
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/profile', [AdminDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [AdminDashboardController::class, 'updateProfile'])->name('profile.update');
});

// ===================================
// MANAJER GUDANG ROUTES
// ===================================
Route::middleware(['auth', 'role:Manajer Gudang'])->prefix('manajergudang')->name('manajergudang.')->group(function () {
    Route::get('/dashboard', [ManagerDashboardController::class, 'index'])->name('dashboard');
    Route::resource('products', ProductController::class)->except(['destroy']);
    Route::get('/products', [ManagerDashboardController::class, 'productList'])->name('products.index');
    Route::get('/products/{product}', [ManagerDashboardController::class, 'productShow'])->name('products.show');
    Route::get('/stock', [ManagerDashboardController::class, 'stockIndex'])->name('stock.index');
    Route::get('/stock/in', [ManagerDashboardController::class, 'stockIn'])->name('stock.in');
    Route::post('/stock/in', [ManagerDashboardController::class, 'stockInStore'])->name('stock.in.store');
    Route::get('/stock/out', [ManagerDashboardController::class, 'stockOut'])->name('stock.out');
    Route::post('/stock/out', [ManagerDashboardController::class, 'stockOutStore'])->name('stock.out.store');
    Route::get('/stock/opname', [ManagerDashboardController::class, 'stockOpname'])->name('stock.opname');
    Route::post('/stock/opname', [ManagerDashboardController::class, 'stockOpnameStore'])->name('stock.opname.store');
    Route::get('/stock/history', [ManagerDashboardController::class, 'stockHistory'])->name('stock.history');
    Route::get('/transactions', [ManagerDashboardController::class, 'transactionList'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [ManagerDashboardController::class, 'transactionShow'])->name('transactions.show');
    Route::get('/transactions/create/in', [ManagerDashboardController::class, 'transactionCreateIn'])->name('transactions.create.in');
    Route::get('/transactions/create/out', [ManagerDashboardController::class, 'transactionCreateOut'])->name('transactions.create.out');
    Route::post('/transactions', [ManagerDashboardController::class, 'transactionStore'])->name('transactions.store');
    Route::put('/transactions/{transaction}/approve', [ManagerDashboardController::class, 'transactionApprove'])->name('transactions.approve');
    Route::put('/transactions/{transaction}/reject', [ManagerDashboardController::class, 'transactionReject'])->name('transactions.reject');
    Route::post('/{transaction}/complete', [StaffTaskController::class, 'processIncomingConfirmation'])->name('complete');
    Route::get('/suppliers', [ManagerDashboardController::class, 'supplierList'])->name('suppliers.index');
    Route::get('/suppliers/{supplier}', [ManagerDashboardController::class, 'supplierShow'])->name('suppliers.show');
    Route::get('/reports', [ManagerDashboardController::class, 'reportIndex'])->name('reports.index');
    Route::get('/reports/stock', [ManagerDashboardController::class, 'reportStock'])->name('reports.stock');
    Route::get('/reports/transactions', [ManagerDashboardController::class, 'reportTransactions'])->name('reports.transactions');
    Route::get('/reports/inventory', [ManagerDashboardController::class, 'reportInventory'])->name('reports.inventory');
    // ##### PERBAIKAN: Mengganti method dari POST ke GET dan membuat namanya unik #####
    Route::get('/reports/export', [ManagerDashboardController::class, 'reportExport'])->name('reports.export');
    Route::get('/staff', [ManagerDashboardController::class, 'staffList'])->name('staff.index');
    Route::get('/staff/{user}', [ManagerDashboardController::class, 'staffShow'])->name('staff.show');
    Route::get('/staff/{user}/tasks', [ManagerDashboardController::class, 'staffTasks'])->name('staff.tasks');
    Route::post('/staff/{user}/assign-task', [ManagerDashboardController::class, 'staffAssignTask'])->name('staff.assign-task');
    Route::get('/profile', [ManagerDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [ManagerDashboardController::class, 'updateProfile'])->name('profile.update');
});

// ===================================
// STAFF GUDANG ROUTES
// ===================================
Route::middleware(['auth', 'role:Staff Gudang'])->prefix('staff')->name('staff.')->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');

    // Stock Management Routes
    Route::prefix('stock')->name('stock.')->group(function () {
        // Incoming Stock Routes
        Route::prefix('incoming')->name('incoming.')->group(function () {
            Route::get('/', [StaffTaskController::class, 'listIncoming'])->name('list');
            Route::get('/{transaction}/confirm', [StaffTaskController::class, 'showIncomingConfirmationForm'])->name('confirm');
            Route::post('/{transaction}/complete', [StaffTaskController::class, 'processIncomingConfirmation'])->name('complete');
        });

        // Outgoing Stock Routes
        Route::prefix('outgoing')->name('outgoing.')->group(function () {
            Route::get('/', [StaffTaskController::class, 'listOutgoing'])->name('list');
            Route::get('/{transaction}/prepare', [StaffTaskController::class, 'showOutgoingPreparationForm'])->name('prepare');
            Route::post('/{transaction}/dispatch', [StaffTaskController::class, 'processOutgoingDispatch'])->name('dispatch');
        });
    });

    // Task Management Routes
    Route::prefix('tasks')->name('tasks.')->group(function () {
        Route::get('/', [StaffTaskController::class, 'index'])->name('index');

        // Incoming Tasks
        Route::prefix('incoming')->name('incoming.')->group(function () {
        Route::get('/', [StaffTaskController::class, 'listIncoming'])->name('list');
        Route::get('/{transaction}/confirm', [StaffTaskController::class, 'showIncomingConfirmationForm'])->name('confirm');
        Route::post('/{transaction}/complete', [StaffTaskController::class, 'processIncomingConfirmation'])->name('complete'); // Diubah dari approve ke complete
        Route::post('/{transaction}/reject', [StaffTaskController::class, 'rejectIncomingTask'])->name('reject');
    });

        // Outgoing Tasks
        Route::prefix('outgoing')->name('outgoing.')->group(function () {
            Route::get('/', [StaffTaskController::class, 'listOutgoing'])->name('list');
            Route::get('/{transaction}/prepare', [StaffTaskController::class, 'showOutgoingPreparationForm'])->name('prepare');
            Route::post('/{transaction}/approve', [StaffTaskController::class, 'approveOutgoingTask'])->name('approve');
            Route::post('/{transaction}/reject', [StaffTaskController::class, 'rejectOutgoingTask'])->name('reject');
        });
    });

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/incoming', [StaffReportController::class, 'showIncomingReport'])->name('incoming');
        Route::get('/outgoing', [StaffReportController::class, 'showOutgoingReport'])->name('outgoing');
        Route::get('/export', [ReportController::class, 'export'])->name('export');
    });

    // Profile
    Route::get('/profile', [StaffDashboardController::class, 'profile'])->name('profile');
    Route::put('/profile', [StaffDashboardController::class, 'updateProfile'])->name('profile.update');
});


// ===================================
// FALLBACK
// ===================================
Route::fallback(function () {
    if (auth()->check()) {
        return redirect('/');
    }
    return redirect('/login');
});
