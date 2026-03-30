<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportController;

use App\Http\Controllers\Master\BrandingController;
use App\Http\Controllers\Master\DoctorController;
use App\Http\Controllers\Master\TreatmentController;
use App\Http\Controllers\Master\TreatmentCategoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Auth middleware
Route::middleware(['auth', 'verified'])->group(function () {

    /**
     * DASHBOARD
     */
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    /**
     * PROFILE
     */
    Route::get('/profile', function () {
        return view('profile.edit');
    })->name('profile.edit');

    // INCOME
    Route::get('/income', [IncomeController::class, 'index'])->name('income.index');
    Route::get('/income/create', [IncomeController::class, 'create'])->name('income.create');
    Route::post('/income', [IncomeController::class, 'store'])->name('income.store');
    Route::get('/income/{incomeTransaction}/edit', [IncomeController::class, 'edit'])->name('income.edit');
    Route::patch('/income/{incomeTransaction}', [IncomeController::class, 'update'])->name('income.update');
    Route::delete('/income/{incomeTransaction}', [IncomeController::class, 'destroy'])->name('income.destroy');

    Route::get('/income/{incomeTransaction}/lunasi', [IncomeController::class, 'lunasiForm'])->name('income.lunasi.form');
    Route::post('/income/{incomeTransaction}/lunasi', [IncomeController::class, 'lunasiStore'])->name('income.lunasi.store');

    Route::get('/income/{incomeTransaction}/fee-tamu', [IncomeController::class, 'feeTamuForm'])->name('income.fee_tamu.form');
    Route::post('/income/{incomeTransaction}/fee-tamu', [IncomeController::class, 'feeTamuStore'])->name('income.fee_tamu.store');

    // EXPENSE
    Route::get('/expense', [ExpenseController::class, 'index'])->name('expense.index');
    Route::get('/expense/create', [ExpenseController::class, 'create'])->name('expense.create');
    Route::post('/expense', [ExpenseController::class, 'store'])->name('expense.store');
    Route::get('/expense/{expense}/edit', [ExpenseController::class, 'edit'])->name('expense.edit');
    Route::patch('/expense/{expense}', [ExpenseController::class, 'update'])->name('expense.update');
    Route::delete('/expense/{expense}', [ExpenseController::class, 'destroy'])->name('expense.destroy');

    // INVENTORY
    Route::prefix('inv')->group(function () {
        // Items
        Route::get('/items', [InventoryController::class, 'itemsIndex'])->name('inv.items.index');
        Route::get('/items/create', [InventoryController::class, 'itemsCreate'])->name('inv.items.create');
        Route::post('/items', [InventoryController::class, 'itemsStore'])->name('inv.items.store');
        Route::get('/items/{item}/edit', [InventoryController::class, 'itemsEdit'])->name('inv.items.edit');
        Route::patch('/items/{item}', [InventoryController::class, 'itemsUpdate'])->name('inv.items.update');
        Route::delete('/items/{item}', [InventoryController::class, 'itemsDestroy'])->name('inv.items.destroy');

        // In
        Route::get('/in', [InventoryController::class, 'inIndex'])->name('inv.in.index');
        Route::get('/in/create', [InventoryController::class, 'inCreate'])->name('inv.in.create');
        Route::post('/in', [InventoryController::class, 'inStore'])->name('inv.in.store');

        // Out
        Route::get('/out', [InventoryController::class, 'outIndex'])->name('inv.out.index');
        Route::get('/out/create', [InventoryController::class, 'outCreate'])->name('inv.out.create');
        Route::post('/out', [InventoryController::class, 'outStore'])->name('inv.out.store');

        // Alias route name untuk nav: route('inv.stock')
        Route::get('/stock', [InventoryController::class, 'stockIndex'])->name('inv.stock');
    });

    // STOCK (route name resmi)
    Route::get('/stock', [InventoryController::class, 'stockIndex'])->name('stock.index');

    // REPORTS (nama resmi: reports.*)
    Route::prefix('reports')->group(function () {
        Route::get('/kas-harian', [ReportController::class, 'kasHarian'])->name('reports.kas_harian');
        Route::get('/penerimaan', [ReportController::class, 'rekapPenerimaan'])->name('reports.penerimaan');
        Route::get('/pengeluaran', [ReportController::class, 'rekapPengeluaran'])->name('reports.pengeluaran');
        Route::get('/fee-dokter', [ReportController::class, 'feeDokter'])->name('reports.fee_dokter');
    });

    /**
     * Alias route name untuk nav lama: report.*
     * IMPORTANT: Redirect harus ikut bawa query string start/end!
     */
    Route::prefix('report')->group(function () {

        Route::get('/kas-harian', function (Request $request) {
            return redirect()->route('reports.kas_harian', $request->query());
        })->name('report.kas_harian');

        Route::get('/penerimaan', function (Request $request) {
            return redirect()->route('reports.penerimaan', $request->query());
        })->name('report.rekap_penerimaan');

        Route::get('/pengeluaran', function (Request $request) {
            return redirect()->route('reports.pengeluaran', $request->query());
        })->name('report.rekap_pengeluaran');

        Route::get('/fee-dokter', function (Request $request) {
            return redirect()->route('reports.fee_dokter', $request->query());
        })->name('report.fee_dokter');
    });

    // MASTER
    Route::prefix('master')->group(function () {
        // Branding
        Route::get('/branding', [BrandingController::class, 'edit'])->name('master.branding.edit');
        Route::post('/branding', [BrandingController::class, 'update'])->name('master.branding.update');

        // Doctors
        Route::get('/doctors', [DoctorController::class, 'index'])->name('master.doctors.index');
        Route::get('/doctors/create', [DoctorController::class, 'create'])->name('master.doctors.create');
        Route::post('/doctors', [DoctorController::class, 'store'])->name('master.doctors.store');
        Route::get('/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->name('master.doctors.edit');
        Route::patch('/doctors/{doctor}', [DoctorController::class, 'update'])->name('master.doctors.update');
        Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('master.doctors.destroy');

        // Treatment Categories
        Route::get('/treatment-categories', [TreatmentCategoryController::class, 'index'])->name('master.treatment_categories.index');
        Route::get('/treatment-categories/create', [TreatmentCategoryController::class, 'create'])->name('master.treatment_categories.create');
        Route::post('/treatment-categories', [TreatmentCategoryController::class, 'store'])->name('master.treatment_categories.store');

        // NEW: edit/update/delete category
        Route::get('/treatment-categories/{category}/edit', [TreatmentCategoryController::class, 'edit'])->name('master.treatment_categories.edit');
        Route::patch('/treatment-categories/{category}', [TreatmentCategoryController::class, 'update'])->name('master.treatment_categories.update');
        Route::delete('/treatment-categories/{category}', [TreatmentCategoryController::class, 'destroy'])->name('master.treatment_categories.destroy');

        // Treatments
        Route::get('/treatments', [TreatmentController::class, 'index'])->name('master.treatments.index');
        Route::get('/treatments/create', [TreatmentController::class, 'create'])->name('master.treatments.create');
        Route::post('/treatments', [TreatmentController::class, 'store'])->name('master.treatments.store');
        Route::get('/treatments/{treatment}/edit', [TreatmentController::class, 'edit'])->name('master.treatments.edit');
        Route::patch('/treatments/{treatment}', [TreatmentController::class, 'update'])->name('master.treatments.update');
        Route::delete('/treatments/{treatment}', [TreatmentController::class, 'destroy'])->name('master.treatments.destroy');
    });
});

// Auth routes bawaan Breeze
require __DIR__.'/auth.php';