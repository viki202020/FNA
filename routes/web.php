<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MerchantController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/login', function () { 
    if(session('id')){
        return redirect('merchant');
    }
    return view('login.login');
});
Route::get('/', function () { 
    if(session('id')){
        return redirect('merchant');
    }
    return view('login.login');
});
Route::get('/logout', function () {    
    if(session()->has('id')){
        session()->pull('id',null);
        return redirect('login');
    }    
    return redirect('login');
});
Route::post('/login', [MerchantController::class,'login']);
Route::group(['middleware' => ['checklogin']], function () {
   Route::get('/merchant', [MerchantController::class,'index']);
   Route::get('/merchant/entity', [MerchantController::class,'entity'])->name('students.list');
   Route::post('/merchant/add-entity', [MerchantController::class,'addEntity']);
   Route::get('/merchant/customers', [MerchantController::class,'customers'])->name('customers.list');
   Route::post('/merchant/add-customer', [MerchantController::class,'addCustomer']);
   Route::get('/merchant/account-head', [MerchantController::class,'accountHead'])->name('head.list');
   Route::post('/merchant/add-head', [MerchantController::class,'addHead']);
   Route::get('/merchant/upload-csv', [MerchantController::class,'uploadCSV']);
   Route::get('/merchant/manage-rules', [MerchantController::class,'manageRules'])->name('rule.list');
   Route::post('/merchant/add-rule', [MerchantController::class,'addRule']);
   Route::post('/merchant/get-head', [MerchantController::class,'getHead']);
   Route::post('/merchant/get-rule', [MerchantController::class,'getRule']);
   Route::post('/merchant/file-import', [MerchantController::class, 'fileImport']);
   Route::get('/merchant/statement/{year?}/{id?}', [MerchantController::class,'manageStatement'])->name('statement.list');
   Route::get('/merchant/uncategorized-data', [MerchantController::class,'uncategorizedData'])->name('uncategorized_data.list');
   Route::post('/merchant/apply-rule', [MerchantController::class,'applyRule']);
   Route::post('/merchant/apply-bulk-rule', [MerchantController::class,'applyBulkRule']);
   Route::post('/merchant/assign-head-manually', [MerchantController::class,'assignHeadManually']);
   Route::get('/merchant/ledgers', [MerchantController::class,'ledgers'])->name('ledgers.list');
   Route::post('/merchant/ledger-by-head', [MerchantController::class,'ledgerByHead']);
   Route::get('/merchant/manage-account', [MerchantController::class,'manageAccount']);
   Route::post('/merchant/add-employee', [MerchantController::class,'addEmployee']);
   Route::post('/merchant/add-supplier', [MerchantController::class,'addSupplier']);
   Route::post('/merchant/add-bank', [MerchantController::class,'addBank']);
   Route::post('/merchant/add-rent', [MerchantController::class,'addRent']);
   Route::get('/merchant/manage-product', [MerchantController::class,'manageProduct']);
   Route::post('/merchant/add-product', [MerchantController::class,'addProduct']);
   Route::get('/merchant/manage-invoice', [MerchantController::class,'manageInvoice']);
   Route::get('/merchant/upload-expencess-csv', [MerchantController::class,'uploadExpencessCSV']);
   Route::post('/merchant/import-expencess-csv', [MerchantController::class, 'importExpencessCsv']);
   Route::get('/merchant/balance-sheet/{year?}', [MerchantController::class,'balanceSheet']);
   Route::get('/merchant/ledger-by-head/{id}/{year?}', [MerchantController::class,'ledgerByHeadId']);
   Route::get('/merchant/profit-loss/{year?}', [MerchantController::class,'profitLoss']);
   Route::get('/merchant/trial-balance/{year?}', [MerchantController::class,'trialBalance']);
   Route::post('/merchant/assign-bulk-head-manually', [MerchantController::class, 'assignBulkHeadManually']);
   Route::post('/merchant/voucher-entry', [MerchantController::class, 'voucherEntry']);
   Route::post('/merchant/close-book', [MerchantController::class, 'closeBook']);
   Route::get('/merchant/manage-book', [MerchantController::class, 'manageBook']);
});
