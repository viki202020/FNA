<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\RuleConflictController;

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
        session()->flush();
        return redirect('login');
    }    
    return redirect('login');
});
Route::post('/login', [AuthController::class,'password']);
Route::get('/login/code', [AuthController::class,'codeForm']);
Route::post('/login/code', [AuthController::class,'codeCheck']);
Route::get('/login/setup', [AuthController::class,'setupForm']);
Route::post('/login/setup', [AuthController::class,'setupCheck']);
Route::get('/login/recovery', [AuthController::class,'recovery']);
Route::post('/login/finish', [AuthController::class,'finish']);
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
   Route::get('/merchant/vouchers', [VoucherController::class,'index'])->name('vouchers.list');
   Route::get('/merchant/voucher/{voucherNo}', [VoucherController::class,'show'])->name('vouchers.show');
   Route::get('/merchant/rule-conflicts', [RuleConflictController::class,'conflicts'])->name('rules.conflicts');
   Route::post('/merchant/rule-conflicts', [RuleConflictController::class,'resolve']);
   Route::get('/merchant/rule-audit', [RuleConflictController::class,'audit'])->name('rules.audit');
   Route::get('/merchant/ledgers', [MerchantController::class,'ledgers'])->name('ledgers.list');
   Route::post('/merchant/ledger-by-head', [MerchantController::class,'ledgerByHead']);
   Route::get('/merchant/manage-account', [MerchantController::class,'manageAccount']);
   Route::post('/merchant/add-employee', [MerchantController::class,'addEmployee']);
   Route::post('/merchant/add-supplier', [MerchantController::class,'addSupplier']);
   Route::post('/merchant/add-bank', [MerchantController::class,'addBank']);
   Route::post('/merchant/add-rent', [MerchantController::class,'addRent']);
   Route::get('/merchant/manage-product', [MerchantController::class,'manageProduct']);
   Route::post('/merchant/add-product', [MerchantController::class,'addProduct']);
   Route::get('/merchant/manage-invoice', [BillingController::class,'index']);
   Route::get('/merchant/cheque-register', [MatchController::class,'register']);
   Route::get('/merchant/bank-reconciliation', [MatchController::class,'reconciliation']);
   Route::get('/merchant/receipts-to-match', [MatchController::class,'index']);
   Route::get('/merchant/match/candidates/{id}', [MatchController::class,'candidatesJson'])->whereNumber('id');
   Route::post('/merchant/match/confirm', [MatchController::class,'confirm']);
   Route::post('/merchant/billing/{id}/note', [MatchController::class,'note'])->whereNumber('id');
   Route::get('/merchant/manage-bill', [BillingController::class,'index']);
   Route::get('/merchant/invoices/new', [BillingController::class,'create']);
   Route::get('/merchant/bills/new', [BillingController::class,'create']);
   Route::post('/merchant/billing/save', [BillingController::class,'store']);
   Route::get('/merchant/invoices/{id}', [BillingController::class,'show'])->whereNumber('id');
   Route::get('/merchant/bills/{id}', [BillingController::class,'show'])->whereNumber('id');
   Route::post('/merchant/billing/{id}/post', [BillingController::class,'post'])->whereNumber('id');
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
