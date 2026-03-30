<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// INVENTORY
use App\Http\Controllers\Inventory\InventoryItemController;
use App\Http\Controllers\Inventory\InventoryMovementController;

// WAREHOUSE
use App\Http\Controllers\Warehouse\WarehouseItemController;
use App\Http\Controllers\Warehouse\WarehouseMovementController;

// INCOME
use App\Http\Controllers\Income\IncomeTransactionController;
use App\Http\Controllers\OtherIncomeController;

// EXPENSE
use App\Http\Controllers\ExpenseController;

// MASTER
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\Master\TreatmentController;
use App\Http\Controllers\Master\TreatmentCategoryController;
use App\Http\Controllers\Master\DoctorController;

// PROFILE
use App\Http\Controllers\ProfileController;

// DASHBOARD
use App\Http\Controllers\DashboardController;

// REPORT
use App\Http\Controllers\ReportController;
use App\Http\Controllers\OwnerFinanceController;
use App\Http\Controllers\OwnerPrivateTransactionController;

// MODEL
use App\Models\Setting;

// Breeze auth routes
require __DIR__ . '/auth.php';

Route::get('/', function () {
    return redirect()->route('dashboard');
});

/*
|--------------------------------------------------------------------------
| PUBLIC INVOICE VERIFICATION
|--------------------------------------------------------------------------
*/
Route::get('/invoice/verify/{income}/{code}', [IncomeTransactionController::class, 'invoiceVerify'])
    ->name('income.invoice.verify');

// Dashboard
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});

// Semua modul aplikasi minimal auth
Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFILE SAYA
    |--------------------------------------------------------------------------
    */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | OWNER PASSWORD
    |--------------------------------------------------------------------------
    */
    Route::get('/owner/change-password', [ProfileController::class, 'changePasswordForm'])->name('owner.password.edit');
    Route::put('/owner/change-password', [ProfileController::class, 'changePasswordUpdate'])->name('owner.password.update');

    /*
    |--------------------------------------------------------------------------
    | PEMASUKAN PASIEN
    |--------------------------------------------------------------------------
    */
    Route::resource('income', IncomeTransactionController::class);

    Route::post('income/{incomeTransaction}/pay', [IncomeTransactionController::class, 'pay'])->name('income.pay');
    Route::delete('income/{incomeTransaction}/payments/{payment}', [IncomeTransactionController::class, 'destroyPayment'])->name('income.payments.destroy');

    Route::post('income/{incomeTransaction}/items', [IncomeTransactionController::class, 'storeItem'])
        ->name('income.items.store');

    Route::patch('income/{incomeTransaction}/items/{item}', [IncomeTransactionController::class, 'updateItem'])
        ->name('income.items.update');

    Route::delete('income/{incomeTransaction}/items/{item}', [IncomeTransactionController::class, 'destroyItem'])
        ->name('income.items.destroy');

    // INVOICE
    Route::get('income/{income}/invoice', [IncomeTransactionController::class, 'invoice'])
        ->name('income.invoice');

    Route::get('income/{income}/invoice/pdf', [IncomeTransactionController::class, 'invoicePdf'])
        ->name('income.invoice.pdf');

    /*
    |--------------------------------------------------------------------------
    | PEMASUKAN LAIN-LAIN
    |--------------------------------------------------------------------------
    */
    Route::prefix('other-income')->name('other_income.')->group(function () {
        Route::get('/', [OtherIncomeController::class, 'index'])->name('index');
        Route::get('/create', [OtherIncomeController::class, 'create'])->name('create');
        Route::post('/', [OtherIncomeController::class, 'store'])->name('store');
        Route::get('/{otherIncome}/edit', [OtherIncomeController::class, 'edit'])->name('edit');
        Route::put('/{otherIncome}', [OtherIncomeController::class, 'update'])->name('update');
        Route::delete('/{otherIncome}', [OtherIncomeController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | OWNER FINANCE CONTROL (OWNER ONLY)
    |--------------------------------------------------------------------------
    */
    Route::prefix('owner-finance')->name('owner_finance.')->group(function () {
        Route::get('/', [OwnerFinanceController::class, 'index'])->name('index');
        Route::get('/create', [OwnerFinanceController::class, 'create'])->name('create');
        Route::post('/', [OwnerFinanceController::class, 'store'])->name('store');
        Route::get('/{ownerFinanceCase}/edit', [OwnerFinanceController::class, 'edit'])->name('edit');
        Route::put('/{ownerFinanceCase}', [OwnerFinanceController::class, 'update'])->name('update');

        Route::put('/{ownerFinanceCase}/installments/{installment}', [OwnerFinanceController::class, 'updateInstallment'])
            ->name('installments.update');

        Route::delete('/{ownerFinanceCase}/installments/{installment}', [OwnerFinanceController::class, 'destroyInstallment'])
            ->name('installments.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | OWNER PRIVATE TRANSACTIONS (OWNER ONLY)
    |--------------------------------------------------------------------------
    */
    Route::prefix('owner-private')->name('owner_private.')->group(function () {
        Route::get('/', [OwnerPrivateTransactionController::class, 'index'])->name('index');
        Route::get('/create', [OwnerPrivateTransactionController::class, 'create'])->name('create');
        Route::post('/', [OwnerPrivateTransactionController::class, 'store'])->name('store');
        Route::get('/{ownerPrivateTransaction}/edit', [OwnerPrivateTransactionController::class, 'edit'])->name('edit');
        Route::put('/{ownerPrivateTransaction}', [OwnerPrivateTransactionController::class, 'update'])->name('update');
        Route::delete('/{ownerPrivateTransaction}', [OwnerPrivateTransactionController::class, 'destroy'])->name('destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | PENGELUARAN
    |--------------------------------------------------------------------------
    */
    Route::resource('expenses', ExpenseController::class);

    /*
    |--------------------------------------------------------------------------
    | INVENTORY
    |--------------------------------------------------------------------------
    */
    Route::prefix('inventory')->group(function () {

        Route::get('/', [InventoryItemController::class, 'panel'])->name('inventory.panel');

        Route::resource('items', InventoryItemController::class)->names('inventory.items');

        Route::get('stok', [InventoryMovementController::class, 'stok'])->name('inventory.stok');
        Route::get('stok/export/pdf', [InventoryMovementController::class, 'exportStokPdf'])->name('inventory.stok.export.pdf');
        Route::get('stok/export/excel', [InventoryMovementController::class, 'exportStokExcel'])->name('inventory.stok.export.excel');

        Route::get('movements/{type}', [InventoryMovementController::class, 'index'])
            ->whereIn('type', ['in', 'out', 'adjust'])
            ->name('inventory.movements.index');

        Route::get('movements/{type}/export/pdf', [InventoryMovementController::class, 'exportPdf'])
            ->whereIn('type', ['in', 'out'])
            ->name('inventory.movements.export.pdf');

        Route::get('movements/{type}/export/excel', [InventoryMovementController::class, 'exportExcel'])
            ->whereIn('type', ['in', 'out'])
            ->name('inventory.movements.export.excel');

        Route::get('movements/{type}/create', [InventoryMovementController::class, 'create'])
            ->whereIn('type', ['in', 'out', 'adjust'])
            ->name('inventory.movements.create');

        Route::post('movements/{type}', [InventoryMovementController::class, 'store'])
            ->whereIn('type', ['in', 'out', 'adjust'])
            ->name('inventory.movements.store');

        Route::get('movements/{type}/{id}/edit', [InventoryMovementController::class, 'edit'])
            ->whereIn('type', ['in', 'out', 'adjust'])
            ->whereNumber('id')
            ->name('inventory.movements.edit');

        Route::put('movements/{type}/{id}', [InventoryMovementController::class, 'update'])
            ->whereIn('type', ['in', 'out', 'adjust'])
            ->whereNumber('id')
            ->name('inventory.movements.update');

        Route::delete('movements/{type}/{id}', [InventoryMovementController::class, 'destroy'])
            ->whereIn('type', ['in', 'out', 'adjust'])
            ->whereNumber('id')
            ->name('inventory.movements.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | ALIAS INVENTORY
    |--------------------------------------------------------------------------
    */
    Route::get('inventory/in', [InventoryMovementController::class, 'index'])
        ->defaults('type', 'in')
        ->name('inv.in.index');

    Route::get('inventory/in/create', [InventoryMovementController::class, 'create'])
        ->defaults('type', 'in')
        ->name('inv.in.create');

    Route::post('inventory/in', [InventoryMovementController::class, 'store'])
        ->defaults('type', 'in')
        ->name('inv.in.store');

    Route::get('inventory/in/{id}/edit', [InventoryMovementController::class, 'edit'])
        ->defaults('type', 'in')
        ->whereNumber('id')
        ->name('inv.in.edit');

    Route::put('inventory/in/{id}', [InventoryMovementController::class, 'update'])
        ->defaults('type', 'in')
        ->whereNumber('id')
        ->name('inv.in.update');

    Route::delete('inventory/in/{id}', [InventoryMovementController::class, 'destroy'])
        ->defaults('type', 'in')
        ->name('inv.in.destroy');

    Route::get('inventory/out', [InventoryMovementController::class, 'index'])
        ->defaults('type', 'out')
        ->name('inv.out.index');

    Route::get('inventory/out/create', [InventoryMovementController::class, 'create'])
        ->defaults('type', 'out')
        ->name('inv.out.create');

    Route::post('inventory/out', [InventoryMovementController::class, 'store'])
        ->defaults('type', 'out')
        ->name('inv.out.store');

    Route::get('inventory/out/{id}/edit', [InventoryMovementController::class, 'edit'])
        ->defaults('type', 'out')
        ->whereNumber('id')
        ->name('inv.out.edit');

    Route::put('inventory/out/{id}', [InventoryMovementController::class, 'update'])
        ->defaults('type', 'out')
        ->whereNumber('id')
        ->name('inv.out.update');

    Route::delete('inventory/out/{id}', [InventoryMovementController::class, 'destroy'])
        ->defaults('type', 'out')
        ->whereNumber('id')
        ->name('inv.out.destroy');

    /*
    |--------------------------------------------------------------------------
    | WAREHOUSE / GUDANG
    |--------------------------------------------------------------------------
    */
    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseItemController::class, 'panel'])->name('panel');

        Route::resource('items', WarehouseItemController::class)
            ->parameters(['items' => 'item'])
            ->names('items');

        Route::get('stok', [WarehouseMovementController::class, 'stok'])->name('stok');

        Route::get('movements/{type}', [WarehouseMovementController::class, 'index'])
            ->whereIn('type', ['in', 'out'])
            ->name('movements.index');

        Route::get('movements/{type}/create', [WarehouseMovementController::class, 'create'])
            ->whereIn('type', ['in', 'out'])
            ->name('movements.create');

        Route::post('movements/{type}', [WarehouseMovementController::class, 'store'])
            ->whereIn('type', ['in', 'out'])
            ->name('movements.store');

        Route::get('movements/{type}/{id}/edit', [WarehouseMovementController::class, 'edit'])
            ->whereIn('type', ['in', 'out'])
            ->whereNumber('id')
            ->name('movements.edit');

        Route::put('movements/{type}/{id}', [WarehouseMovementController::class, 'update'])
            ->whereIn('type', ['in', 'out'])
            ->whereNumber('id')
            ->name('movements.update');

        Route::delete('movements/{type}/{id}', [WarehouseMovementController::class, 'destroy'])
            ->whereIn('type', ['in', 'out'])
            ->name('movements.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | MASTER DATA
    |--------------------------------------------------------------------------
    */
    Route::prefix('master')->name('master.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::patch('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::patch('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::resource('doctors', DoctorController::class)->except(['show']);

        Route::resource('treatments', TreatmentController::class)->except(['show']);

        Route::resource('treatment-categories', TreatmentCategoryController::class)
            ->parameters(['treatment-categories' => 'category'])
            ->names('treatment_categories')
            ->except(['show']);

        Route::post('branding', function (Request $request) {
            $user = auth()->user();

            if (!$user || strtolower((string) $user->role) !== 'owner') {
                abort(403, 'Hanya OWNER yang boleh mengubah branding sistem.');
            }

            $request->validate([
                'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
                'login_background' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            ], [
                'logo.image' => 'File logo harus berupa gambar.',
                'logo.mimes' => 'Logo harus berformat jpg, jpeg, png, webp, atau svg.',
                'logo.max' => 'Ukuran logo maksimal 2MB.',
                'login_background.image' => 'Background login harus berupa gambar.',
                'login_background.mimes' => 'Background login harus berformat jpg, jpeg, png, atau webp.',
                'login_background.max' => 'Ukuran background login maksimal 4MB.',
            ]);

            $setting = Setting::query()->first();
            if (!$setting) {
                $setting = new Setting();
            }

            if ($request->hasFile('logo')) {
                if (!empty($setting->logo_path) && Storage::disk('public')->exists($setting->logo_path)) {
                    Storage::disk('public')->delete($setting->logo_path);
                }

                $setting->logo_path = $request->file('logo')->store('branding/logo', 'public');
            }

            if ($request->hasFile('login_background')) {
                if (!empty($setting->login_background_path) && Storage::disk('public')->exists($setting->login_background_path)) {
                    Storage::disk('public')->delete($setting->login_background_path);
                }

                $setting->login_background_path = $request->file('login_background')->store('branding/login-background', 'public');
            }

            $setting->save();

            return redirect()
                ->route('master.users.index')
                ->with('success', 'Branding sistem berhasil diperbarui.');
        })->name('branding.update');
    });

    /*
    |--------------------------------------------------------------------------
    | REPORTS
    |--------------------------------------------------------------------------
    */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('laba-rugi', [ReportController::class, 'labaRugi'])->name('laba-rugi');
        Route::get('kas-harian', [ReportController::class, 'kasHarian'])->name('daily_cash.index');
        Route::get('fee-dokter', [ReportController::class, 'feeDokter'])->name('fee_dokter.index');

        // EXPORT PDF
        Route::get('kas-harian/export/pdf', [ReportController::class, 'exportKasHarianPdf'])
            ->name('daily_cash.export.pdf');
        Route::get('laba-rugi/export/pdf', [ReportController::class, 'exportLabaRugiPdf'])
            ->name('laba-rugi.export.pdf');
        Route::get('fee-dokter/export/pdf', [ReportController::class, 'exportFeeDokterPdf'])
            ->name('fee_dokter.export.pdf');

        // EXPORT EXCEL
        Route::get('kas-harian/export/excel', [ReportController::class, 'exportKasHarianExcel'])
            ->name('daily_cash.export.excel');
        Route::get('laba-rugi/export/excel', [ReportController::class, 'exportLabaRugiExcel'])
            ->name('laba-rugi.export.excel');
        Route::get('fee-dokter/export/excel', [ReportController::class, 'exportFeeDokterExcel'])
            ->name('fee_dokter.export.excel');
    });
});