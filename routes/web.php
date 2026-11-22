<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MerchantController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\OpeningBalanceController;
use App\Http\Controllers\Schedule3Controller;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\GstReportsController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\TdsController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\Gstr2bController;
use App\Http\Controllers\PaymentReversalController;
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
Route::match(['get','post'], '/logout', function (\Illuminate\Http\Request $request) {
    // Signing out needs a form post (with the page's security token), so another website cannot log you out by a hidden link.
    if($request->isMethod('post') && session()->has('id')){
        session()->flush();
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
   Route::get('/merchant/entity', [MerchantController::class,'entity'])->name('students.list')->middleware('admin');
   Route::post('/merchant/add-entity', [MerchantController::class,'addEntity'])->middleware('admin');
   Route::get('/merchant/customers', [MerchantController::class,'customers'])->name('customers.list');
   Route::post('/merchant/add-customer', [MerchantController::class,'addCustomer']);
   Route::get('/merchant/account-head', [MerchantController::class,'accountHead'])->name('head.list')->middleware('admin');
   Route::post('/merchant/add-head', [MerchantController::class,'addHead'])->middleware('admin');
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
   Route::post('/merchant/add-bank', [MerchantController::class,'addBank'])->middleware('admin');
   Route::post('/merchant/add-rent', [MerchantController::class,'addRent']);
   Route::get('/merchant/manage-product', [MerchantController::class,'manageProduct']);
   Route::post('/merchant/add-product', [MerchantController::class,'addProduct']);
   Route::get('/merchant/manage-invoice', [BillingController::class,'index']);
   Route::get('/merchant/cheque-register', [MatchController::class,'register']);
   Route::get('/merchant/bank-reconciliation', [MatchController::class,'reconciliation']);
   Route::get('/merchant/receipts-to-match', [MatchController::class,'index']);
   Route::get('/merchant/match/candidates/{id}', [MatchController::class,'candidatesJson'])->whereNumber('id');
   Route::post('/merchant/match/confirm', [MatchController::class,'confirm']);
   Route::post('/merchant/billing/{id}/void', [PaymentReversalController::class,'void'])->whereNumber('id');
   Route::post('/merchant/billing/{id}/reverse', [PaymentReversalController::class,'reverse'])->whereNumber('id');
   Route::post('/merchant/billing/{id}/note', [MatchController::class,'note'])->whereNumber('id');
   Route::get('/merchant/departments', [DepartmentController::class,'index'])->middleware('admin');
   Route::post('/merchant/departments', [DepartmentController::class,'store'])->middleware('admin');
   Route::post('/merchant/departments/{id}/toggle', [DepartmentController::class,'toggle'])->whereNumber('id')->middleware('admin');
   Route::get('/merchant/journal/new', [JournalController::class,'create']);
   Route::get('/merchant/journal/{id}/edit', [JournalController::class,'edit'])->whereNumber('id');
   Route::post('/merchant/journal/save', [JournalController::class,'save']);
   Route::post('/merchant/journal/{id}/save', [JournalController::class,'save'])->whereNumber('id');
   Route::get('/merchant/opening-balance', [OpeningBalanceController::class,'form'])->middleware('admin');
   Route::get('/merchant/opening-balance/template', [OpeningBalanceController::class,'template'])->middleware('admin');
   Route::post('/merchant/opening-balance/preview', [OpeningBalanceController::class,'preview'])->middleware('admin');
   Route::post('/merchant/opening-balance/confirm', [OpeningBalanceController::class,'confirm'])->middleware('admin');
   Route::get('/merchant/gstr2b', [Gstr2bController::class,'index']);
   Route::post('/merchant/gstr2b/upload', [Gstr2bController::class,'upload']);
   Route::post('/merchant/gstr2b/draft-all', [Gstr2bController::class,'draftAll']);
   Route::post('/merchant/gstr2b/{id}/draft', [Gstr2bController::class,'draft'])->whereNumber('id');
   Route::post('/merchant/billing/{id}/accounts', [BillingController::class,'accounts'])->whereNumber('id');
   Route::get('/merchant/change-password', [AccountController::class,'passwordForm']);
   Route::post('/merchant/change-password', [AccountController::class,'passwordSave']);
   Route::get('/merchant/users', [AccountController::class,'users'])->middleware('admin');
   Route::post('/merchant/users', [AccountController::class,'userAdd'])->middleware('admin');
   Route::post('/merchant/users/{id}/toggle', [AccountController::class,'userToggle'])->whereNumber('id')->middleware('admin');
   Route::post('/merchant/users/{id}/role', [AccountController::class,'userRole'])->whereNumber('id')->middleware('admin');
   Route::get('/merchant/reopen-year', [AccountController::class,'reopen'])->middleware('admin');
   Route::post('/merchant/reopen-year', [AccountController::class,'reopenSave'])->middleware('admin');
   Route::post('/merchant/reopen-year/close-again', [AccountController::class,'closeAgain'])->middleware('admin');
   Route::get('/merchant/tds', [TdsController::class,'index']);
   Route::post('/merchant/tds/challan', [TdsController::class,'save']);
   Route::get('/merchant/tds/{part}.csv', [TdsController::class,'csv']);
   Route::get('/merchant/budgets', [BudgetController::class,'index']);
   Route::post('/merchant/budgets/save', [BudgetController::class,'save']);
   Route::get('/merchant/budgets/template', [BudgetController::class,'template']);
   Route::post('/merchant/budgets/upload', [BudgetController::class,'upload']);
   Route::get('/merchant/inventorise', [\App\Http\Controllers\InventoriseScreenController::class,'index']);
   Route::post('/merchant/inventorise/confirm', [\App\Http\Controllers\InventoriseScreenController::class,'confirm']);
   Route::post('/merchant/inventorise/create-party', [\App\Http\Controllers\InventoriseScreenController::class,'createParty']);
   Route::get('/merchant/gst-reports', [GstReportsController::class,'index']);
   Route::get('/merchant/gst-reports/{table}.csv', [GstReportsController::class,'csv']);
   Route::get('/merchant/data-analytics', [AnalyticsController::class,'index']);
   Route::get('/merchant/financial-statements', [Schedule3Controller::class,'index']);
   Route::get('/merchant/financial-statements/export', [Schedule3Controller::class,'export']);
   Route::get('/merchant/posting-accounts', [SettingsController::class,'index'])->middleware('admin');
   Route::post('/merchant/posting-accounts', [SettingsController::class,'save'])->middleware('admin');
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
   Route::post('/merchant/close-book', [MerchantController::class, 'closeBook'])->middleware('admin');
   Route::get('/merchant/manage-book', [MerchantController::class, 'manageBook'])->middleware('admin');
});
