<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Merchant;
use App\Models\Entity;
use App\Models\Customer;
use App\Models\Head;
use App\Models\AccountType;
use App\Models\Rule;
use App\Models\Statement;
use App\Models\Category;
use App\Models\Unit;
use App\Models\Product;
use App\Models\AccountTypeParent;
use App\Models\EntityFinancialYear;
use Session;
use Yajra\DataTables\DataTables;
use Excel;
use App\Imports\UsersImport;
use App\Imports\ExpencessCsvImport;
use DB;
use Illuminate\Support\Str;
use App\Support\VoucherNumber;
use App\Support\FinancialYears;
use App\Support\TaxId;
use App\Support\RuleMatcher;
class MerchantController extends Controller{
   public function index(){
      $merchant = Session::get('merchant_id');
      $year = FinancialYears::current();
      $date = "COALESCE(STR_TO_DATE(s.st_date,'%Y-%m-%d'),STR_TO_DATE(s.st_date,'%d-%m-%Y'))";
      // the month the Sales / Purchases tiles describe: this month if it is inside the year, else the year's last month
      $entity = FinancialYears::entity();
      $start = $entity && $entity->financial_year_start ? \Carbon\Carbon::parse($entity->financial_year_start) : null;
      $end = $entity && $entity->financial_year_end ? \Carbon\Carbon::parse($entity->financial_year_end) : null;
      $month = \Carbon\Carbon::now();
      if($start && $end && ($month->lt($start) || $month->gt($end))){ $month = $end->copy(); }
      $effective = DB::table('statement as s')->where('s.merchant_id',$merchant)->where('s.status',1)->where('s.fyear',$year);
      $sum = function($typeIds, $creditMinusDebit = false) use ($effective, $merchant, $date, $month){
         $q = DB::table('statement as s')->join('head as h','h.id','=','s.head_id')
               ->where('s.merchant_id',$merchant)->where('s.status',1)->where('s.opening',0)
               ->whereIn('h.account_type_id',$typeIds)
               ->whereRaw("$date >= ?",[$month->copy()->startOfMonth()->format('Y-m-d')])
               ->whereRaw("$date <= ?",[$month->copy()->endOfMonth()->format('Y-m-d')]);
         $row = $q->selectRaw('COALESCE(SUM(s.debit+0),0) as d, COALESCE(SUM(s.credit+0),0) as c')->first();
         return $creditMinusDebit ? ($row->c - $row->d) : ($row->d - $row->c);
      };
      $bankRow = DB::table('statement as s')->join('head as h','h.id','=','s.head_id')
               ->where('s.merchant_id',$merchant)->where('s.status',1)->where('s.fyear',$year)->where('h.account_type_id',3)
               ->selectRaw('COALESCE(SUM(s.debit+0),0) as d, COALESCE(SUM(s.credit+0),0) as c')->first();
      $bankCount = DB::table('head')->where('account_type_id',3)->where('status',1)->count();
      $totals = (clone $effective)->selectRaw('COALESCE(SUM(s.debit+0),0) as d, COALESCE(SUM(s.credit+0),0) as c')->first();
      $uncategorised = DB::table('statement')->where('merchant_id',$merchant)->where('status',0)->where('opening',0)
               ->where(function($q) use ($year){ $q->whereNull('fyear')->orWhere('fyear',$year); })->count();
      $drafts = DB::table('vouchers')->where('merchant_id',$merchant)->where('status','draft')->where('fyear',$year)->count();
      $recent = DB::table('vouchers as v')
               ->leftJoinSub(DB::table('statement')->whereNotNull('voucher_id')->select('voucher_id',DB::raw('ROUND(SUM(debit+0),2) as amount'),DB::raw('MIN(remark) as remark'))->groupBy('voucher_id'),'a','a.voucher_id','=','v.id')
               ->where('v.merchant_id',$merchant)->where('v.fyear',$year)
               ->orderByDesc('v.id')->limit(5)
               ->get(['v.voucher_no','v.voucher_date','v.status','v.narration','a.amount','a.remark']);
      return view('dashboard.dashboard',[
         'year'=>$year, 'monthName'=>$month->format('M'),
         'bank'=>round($bankRow->d - $bankRow->c,2), 'bankCount'=>$bankCount,
         'sales'=>round($sum([9],true),2), 'purchases'=>round($sum([5,6]),2),
         'uncategorised'=>$uncategorised, 'drafts'=>$drafts, 'recent'=>$recent,
         'balanced'=>abs($totals->d - $totals->c) < 0.005,
         'tds'=>\App\Support\TdsDue::next(), 'receivables'=>\App\Support\Outstanding::summary('sales'), 'payables'=>\App\Support\Outstanding::summary('purchase'),
      ]);
   }
   public function entity(Request $request){
      if($request->ajax()) {
         $data = Entity::where('merchant_id',Session::get('merchant_id'))->latest()->get();
         return Datatables::of($data)
         ->addIndexColumn()
         ->addColumn('action', function($row){
               $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm edit_entity" data-id="'.$row->id.'" data-gst_number="'.$row->gst_number.'" data-frequency="'.$row->frequency.'" data-address="'.$row->address.'" data-city="'.$row->city.'" data-state="'.$row->state.'" data-pincode="'.$row->pincode.'" data-mobile="'.$row->mobile.'" data-email="'.$row->email.'" data-name="'.$row->name.'" data-financial_year="'.$row->financial_year.'" data-start_year="'.$row->financial_year_start.'" data-end_year="'.$row->financial_year_end.'">Edit</a>';
               return $actionBtn;
            })
         ->rawColumns(['action'])
         ->make(true);
      }
      $entity = Entity::where('merchant_id',Session::get('merchant_id'))->latest()->get();
      if(date('m') <= 3) {
         $financial_year = (date('Y')-1) . '-' . date('Y');
         $financial_year_start = (date('Y')-1)."-04-01";
         $financial_year_end = date('Y')."-03-31";
      }else{
         $financial_year = date('Y') . '-' . (date('Y') + 1);
         $financial_year_start = date('Y')."-04-01";
         $financial_year_end = (date('Y') + 1)."-03-31";
      }
      return view('entity.entity',['company'=>$entity->first(),'entity_count'=>count($entity),"financial_year_start"=>$financial_year_start,"financial_year_end"=>$financial_year_end]);
   }
   public function login(Request $req){
      $user_name = $req->input('username');
      $password = $req->input('password');        
      $validated = $req->validate([
         'username' => 'required',
         'password' => 'required',
      ]);      
      $check_login = \App\Support\PasswordCheck::user($user_name, $password);
      if($check_login){            
         $req->session()->put('id', $check_login->id);
         $req->session()->put('merchant_id', $check_login->id);
         $req->session()->put('name', $check_login->name);     
         $req->session()->put('mobile', $check_login->mobile);
         $req->session()->put('email', $check_login->email);
         return redirect('merchant');                       
      }else{
         return redirect()->back()->withErrors(['Invalid username/password']);
      }
   }
   public function addEntity(Request $req){
      $city = $req->input('city');
      $frequency = $req->input('frequency');
      $address = $req->input('address');
      $state = $req->input('state');
      $pincode = $req->input('pincode');
      $mobile = $req->input('mobile');
      $email = $req->input('email'); 
      $gst = TaxId::clean($req->input('gst'));
      $name = $req->input('name');    
      $validated = $req->validate([
         'address' => 'required',        
         'state' => 'required',
         'city' => 'required',
         'pincode' => 'required',
         'mobile' => 'required',
         'email' => 'required',
         'name' => 'required',
         'gst' => [function($attribute,$value,$fail){ $message = TaxId::error($value); if($message){ $fail($message); } }],
      ]);
      if(!empty($req->input('entity_id'))){
         $entity = Entity::find($req->input('entity_id'));
         $entity->name = $name;
         $entity->gst_number = $gst;
         $entity->frequency = $frequency;
         $entity->address = $address; 
         $entity->city = $city;
         $entity->state = $state;       
         $entity->pincode = $pincode;
         $entity->mobile = $mobile;
         $entity->email = $email;
         $entity->updated_at = date('d-m-Y H:i:s');
         $entity->updated_by = Session::get('id');
         $entity->save();               
         $req->session()->flash('success_msg','Entity Update Successfully');
      }else{
         $sy = explode("-",$req->input('start_year'));
         $ey = explode("-",$req->input('end_year'));
         $financial_year = $sy[0]."-".$ey[0];
         $entity = new Entity;
         $entity->gst_number = $gst;
         $entity->name = $name;
         $entity->frequency = $frequency;
         $entity->address = $address; 
         $entity->city = $city;
         $entity->state = $state;       
         $entity->pincode = $pincode;
         $entity->mobile = $mobile;
         $entity->email = $email;
         $entity->financial_year = $financial_year;
         $entity->financial_year_start = $req->input('start_year');
         $entity->financial_year_end = $req->input('end_year');
         $entity->merchant_id = Session::get('merchant_id');
         $entity->created_at = date('d-m-Y H:i:s');
         $entity->created_by = Session::get('id');
         $entity->save();
         $req->session()->flash('success_msg','Entity Add Successfully');
      }
      return redirect('merchant/entity');      
   }
   public function customers(Request $request){
      if($request->ajax()) {
         $data = Customer::where("merchant",Session::get('merchant_id'))->latest()->get();
         return Datatables::of($data)
         ->addIndexColumn()
         ->addColumn('action', function($row){
               $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm edit_customer" data-id="'.$row->id.'" data-title="'.$row->title.'" data-first_name="'.$row->first_name.'" data-last_name="'.$row->last_name.'" data-email="'.$row->email.'" data-phone="'.$row->phone.'" data-mobile="'.$row->mobile.'" data-display_name="'.$row->display_name.'" data-gst_type="'.$row->gst_type.'" data-gst="'.$row->gst.'" data-billing_street="'.$row->billing_street.'" data-billing_city="'.$row->billing_city.'" data-billing_state="'.$row->billing_state.'" data-billing_pincode="'.$row->billing_pincode.'" data-billing_country="'.$row->billing_country.'" data-shipping_street="'.$row->shipping_street.'" data-shipping_city="'.$row->shipping_city.'" data-shipping_state="'.$row->shipping_state.'" data-shipping_pincode="'.$row->shipping_pincode.'" data-shipping_country="'.$row->shipping_country.'" data-category="'.$row->category_id.'" data-pan="'.$row->pan.'" data-tds_status="'.$row->tds_status.'" data-tds_rate="'.$row->tds_rate.'" data-tds_section="'.$row->tds_section.'" data-type="'.$row->type.'">Edit</a>';
               return $actionBtn;
            })
         ->rawColumns(['action'])
         ->make(true);
      }
      $category = Category::where('status',1)->get(); 
      return view('customer.customer',["categorys"=>$category]);
   }
   public function addCustomer(Request $req){

      $title = $req->input('title');
      $first_name = $req->input('first_name');
      $last_name = $req->input('last_name');
      $email = $req->input('email');
      $phone = $req->input('phone');
      $mobile = $req->input('mobile');
      $display_name = $req->input('display_name'); 
      $gst_type = $req->input('gst_type');
      $gst = TaxId::clean($req->input('gst'));
      $billing_street = $req->input('billing_street1');
      $billing_city = $req->input('billing_city1');
      $billing_state = $req->input('billing_state1');
      $billing_pincode = $req->input('billing_pincode1');
      $billing_country = $req->input('billing_country1');
      $shipping_street = $req->input('shipping_street1');
      $shipping_city = $req->input('shipping_city1');
      $shipping_state = $req->input('shipping_state1');
      $shipping_pincode = $req->input('shipping_pincode1');
      $shipping_country = $req->input('shipping_country1');

      $opening_balance = $req->input('customer_opening_balance');
      $opening_balance_type = $req->input('customer_open_type');
      $opening_balance_date = $req->input('customer_opening_balance_date');

      $category = $req->input('category');
      $validated = $req->validate([
         'first_name' => 'required',        
         'email' => 'required',
         'mobile' => 'required',
         'display_name' => 'required',
         'gst_type' => 'required',
         'gst' => ['required', function($attribute,$value,$fail){ $message = TaxId::error($value); if($message){ $fail($message); } }]
      ]);
      
      if(!empty($req->input('customer_id'))){
         $customer = Customer::find($req->input('customer_id'));
         $customer->title = $title;
         $customer->first_name = $first_name;
         $customer->last_name = $last_name; 
         $customer->email = $email;
         $customer->phone = $phone; 
         $customer->opening_balance = $opening_balance; 
         $customer->opening_balance_type = $opening_balance_type;       
         $customer->opening_balance_date = $opening_balance_date; 
         $customer->mobile = $mobile;
         $customer->display_name = $display_name;
         $customer->gst_type = $gst_type;
         $customer->gst = $gst;
         $customer->billing_street = $billing_street;
         $customer->billing_city = $billing_city;
         $customer->billing_state = $billing_state;
         $customer->billing_pincode = $billing_pincode;
         $customer->billing_country = $billing_country;
         $customer->shipping_street = $shipping_street;
         $customer->shipping_city = $shipping_city;
         $customer->shipping_state = $shipping_state;
         $customer->shipping_pincode = $shipping_pincode;
         $customer->shipping_country = $shipping_country;
 $customer->category_id = $category;
         $this->fillPartyTax($customer, $req);
         $customer->updated_at = date('d-m-Y H:i:s');
         $customer->updated_by = Session::get('id');
         $customer->save();               
         $req->session()->flash('success_msg','Customer Update Successfully');
      }else{
         $party = new Customer;
         $party->type = (int) $req->input('party_type', 1) === 4 ? 4 : 1;
         $party->title = $title; $party->first_name = $first_name; $party->last_name = $last_name; $party->email = $email; $party->phone = $phone; $party->mobile = $mobile;
         $party->display_name = $display_name; $party->gst_type = $gst_type; $party->gst = $gst;
         $party->billing_street = $billing_street; $party->billing_city = $billing_city; $party->billing_state = $billing_state; $party->billing_pincode = $billing_pincode; $party->billing_country = $billing_country;
         $party->shipping_street = $shipping_street; $party->shipping_city = $shipping_city; $party->shipping_state = $shipping_state; $party->shipping_pincode = $shipping_pincode; $party->shipping_country = $shipping_country;
         $party->opening_balance = $opening_balance; $party->opening_balance_type = $opening_balance_type; $party->opening_balance_date = $opening_balance_date;
         $party->category_id = $category;
         $this->fillPartyTax($party, $req);
         $party->merchant = Session::get('merchant_id'); $party->status = 1; $party->created_at = date('d-m-Y H:i:s'); $party->created_by = Session::get('id');
         $party->save();
         if($req->ajax() || $req->expectsJson()){ return response(['success' => ($party->type === 4 ? 'Supplier' : 'Customer').' saved.']); }
         $req->session()->flash('success_msg', ($party->type === 4 ? 'Supplier' : 'Customer').' saved.');
         return redirect('merchant/customers');
      }
      return redirect('merchant/customers');
   }

   /** PAN and the TDS details of a party, from the form (the old supplier fields are still understood). */
   private function fillPartyTax($party, Request $req){
      $party->pan = strtoupper(trim((string)$req->input('pan'))) ?: $party->pan;
      $applicable = $req->input('tds_applicable', $req->input('supp_tds_status'));
      if($applicable === 'Yes' || $applicable === '1'){ $party->tds_status = 1; }
      elseif($applicable === 'No' || $applicable === '0'){ $party->tds_status = 0; }
      $rate = $req->input('tds_rate', $req->input('supp_tds_rate'));
      if($rate !== null && $rate !== ''){ $party->tds_rate = $rate; }
      if($req->input('tds_section') !== null && $req->input('tds_section') !== ''){ $party->tds_section = $req->input('tds_section'); }
   }
   public function accountHead(Request $request){
      if($request->ajax()){
         $data = Head::join('account_type', 'head.account_type_id', '=', 'account_type.id')
               ->where("merchant",Session::get('merchant_id'))
               ->latest()
               ->get(['head.*', 'account_type.name as account_name','account_type.parent_id']);
         //$data = Head::where("merchant",Session::get('id'))->latest()->get();
         return Datatables::of($data)
         ->addIndexColumn()
         ->addColumn('action', function($row){
               $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm edit_head" data-id="'.$row->id.'" data-account_type="'.$row->account_type_id.'"  data-name="'.$row->name.'" data-description="'.$row->description.'" data-applicable_gst="'.$row->applicable_gst.'" data-tax_code="'.$row->tax_code.'" data-status="'.$row->status.'" data-balance="'.$row->balance.'" data-balance_date="'.$row->balance_date.'" data-balance_type="'.$row->balance_type.'" data-mandatory_category="'.$row->mandatory_category.'" data-mandatory_customer="'.$row->mandatory_customer.'" data-mandatory_supplier="'.$row->mandatory_supplier.'" data-department_required="'.$row->department_required.'" data-parent_id="'.$row->parent_id.'">Edit</a>';
               return $actionBtn;
            })
         ->rawColumns(['action'])
         ->make(true);
      }
      $account_type = AccountType::get();
      $financial_year_start = "";
      $entity = Entity::select('financial_year_start')
                        ->where('merchant_id',Session::get('merchant_id'))
                        ->first();
      if($entity){
         $financial_year_start = $entity->financial_year_start;
      }
      return view('account.head',["account_type"=>$account_type,"entity"=>$financial_year_start]);
   }
   public function addHead(Request $req){
      $account_type = $req->input('account_type');
      $name = $req->input('name');
      $description = $req->input('description');
      $tax_code = $req->input('tax_code');
      $balance = $req->input('balance'); 
      $date = $req->input('date');
      $balance_type = $req->input('open_type');
      $applicable_gst = $req->input('applicable_gst');
      $mandatory_category = 0;$mandatory_customer = 0;$mandatory_supplier = 0;
      if($req->input('mandatory_category')!=""){
         $mandatory_category = 1;
      }
      if($req->input('mandatory_customer')!=""){
         $mandatory_customer = 1;
      }
      if($req->input('mandatory_supplier')!=""){
         $mandatory_supplier = 1;
      }
      $department_required = $req->input('department_required')!="" ? 1 : 0;
      $validated = $req->validate([
         'name' => 'required',
         'applicable_gst' => 'required',           
         'account_type' => 'required',  
      ]);
      if(!empty($req->input('head_id'))){
         $head = Head::find($req->input('head_id'));
         $head->account_type_id = $account_type;
         $head->name = $name; 
         $head->description = $description;
         $head->applicable_gst = $applicable_gst;       
         $head->tax_code = $tax_code;
         $head->balance = $balance;
         $head->balance_date = $date;
         $head->mandatory_category = $mandatory_category;
         $head->mandatory_customer = $mandatory_customer;
         $head->mandatory_supplier = $mandatory_supplier;
         $head->department_required = $department_required;
         $head->balance_type = $balance_type;
         $head->updated_at = date('d-m-Y H:i:s');
         $head->updated_by = Session::get('id');
         $head->save();               
         $req->session()->flash('success_msg','Head Update Successfully');
      }else{
         $head = new Head;
         $head->account_type_id = $account_type;
         $head->name = $name; 
         $head->description = $description;
         $head->applicable_gst = $applicable_gst;       
         $head->tax_code = $tax_code;
         $head->balance = $balance;
         $head->mandatory_category = $mandatory_category;
         $head->mandatory_customer = $mandatory_customer;
         $head->mandatory_supplier = $mandatory_supplier;
         $head->department_required = $department_required;
         $head->balance_type = $balance_type;
         $head->balance_date = $date;
         $head->merchant = Session::get('merchant_id');
         $head->created_at = date('d-m-Y H:i:s');
         $head->created_by = Session::get('id');
         if($head->save()){
            $entity = Entity::select('financial_year')->where('merchant_id',Session::get('merchant_id'))->first();
            $statement = new Statement();
            if($balance_type=="Credit"){
               $statement->credit = $balance;
            }else{
               $statement->debit = $balance;
            }
            $statement->remark = "Opening Balance";
            $statement->head_id = $head->id;
            $statement->st_date = date('Y-m-d',strtotime($date));
            $statement->fyear = $entity->financial_year;
            $statement->opening = 1;
            $statement->status = 1;
            $statement->merchant_id = Session::get('merchant_id');
            $statement->created_at = date('d-m-Y H:i:s');
            $statement->created_by = Session::get('id');
            $statement->save();
         }
         $req->session()->flash('success_msg','Head Add Successfully');
      }
      return redirect('merchant/account-head');      
   }
   public function uploadCSV(Request $request){
      // if($request->ajax()){
      //    $data = Head::where("merchant",Session::get('id'))->latest()->get();
      //    return Datatables::of($data)
      //    ->addIndexColumn()
      //    ->addColumn('action', function($row){
      //          $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm edit_head" data-id="'.$row->id.'" data-account_type="'.$row->account_type.'" data-account_detail="'.$row->account_detail.'" data-name="'.$row->name.'" data-description="'.$row->description.'" data-parent_account="'.$row->parent_account.'" data-tax_code="'.$row->tax_code.'" data-status="'.$row->status.'" data-balance="'.$row->balance.'" data-balance_date="'.$row->balance_date.'">Edit</a>';
      //          return $actionBtn;
      //       })
      //    ->rawColumns(['action'])
      //    ->make(true);
      // }    
      $bank = Head::select('id','name')
                     ->where('account_type_id',3)
                     ->where('merchant',Session::get('merchant_id'))
                     ->where('status',1)
                     ->get();  
      return view('account.upload_csv',['banks'=>$bank]);
   }
   public function manageRules(Request $request){
      if($request->ajax()){
         $data = Rule::join('head', 'rules.head_id', '=', 'head.id')
               ->where("merchant_id",Session::get('merchant_id'))
               ->latest()
               ->get(['rules.*', 'head.name']);
         return Datatables::of($data)
         ->addIndexColumn()
         ->addColumn('action', function($row){
               $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm edit_rule" data-id="'.$row->id.'" data-col_type="'.$row->col_type.'" data-rule_condition="'.$row->rule_condition.'" data-content="'.$row->content.'" data-rule_action_type="'.$row->rule_action_type.'" data-head_id="'.$row->head_id.'" data-col_type1="'.$row->col_type1.'" data-rule_condition1="'.$row->rule_condition1.'" data-content1="'.$row->content1.'" data-status="'.$row->status.'" data-rule_name="'.$row->rule_name.'" data-rule_for="'.$row->rule_for.'" data-category="'.$row->category.'" data-customer="'.$row->customer.'" data-supplier="'.$row->supplier.'" data-rule_in="'.$row->rule_in.'">Edit</a>';
               return $actionBtn;
            })
         ->rawColumns(['action'])
         ->make(true);
      }
      
      $account_type = AccountType::get();  
      $category = Category::where('status',1)->get(); 
      $customer = Customer::where('status',1)->where('type',1)->get();
      $supplier = Customer::where('status',1)->where('type',4)->get();
      $bank = Head::select('id','name')
                     ->where('account_type_id',3)
                     ->where('merchant',Session::get('merchant_id'))
                     ->where('status',1)
                     ->get();
      return view('account.rules',["account_type"=>$account_type,"categorys"=>$category,"customers"=>$customer,"suppliers"=>$supplier,"banks"=>$bank]);
   }
   public function addRule(Request $req){
      $col_type = $req->input('col_type');
      $rule_condition = $req->input('rule_condition');
      $rule_action_type = $req->input('rule_action_type');
      $head_id = $req->input('head');
      $col_type1 = $req->input('col_type2');
      $content = $req->input('content');
      $content1 = $req->input('content2');
      $rule_condition1 = $req->input('rule_condition2');
      $rule_name = $req->input('rule_name');
      $rule_for = $req->input('rule_for');
      $rule_in = $req->input('rule_in');
      $category = $req->input('category');
      $customer = $req->input('customer');
      $supplier = $req->input('supplier');
      $validated = $req->validate([
         'col_type' => 'required',
         'rule_condition' => 'required',
         'content' => 'required',         
         'col_type2' => 'required',
         'rule_condition2' => 'required',
         'rule_action_type' => 'required',
         'rule_name'=> 'required',
         'rule_for'=>'required'
      ]);

      if(!empty($req->input('head_id'))){

         foreach ($col_type as $key => $value){
            
            $rule = Rule::find($req->input('head_id'));            
            $rule->update([
               'col_type' => $value,
               'rule_condition' => $rule_condition[$key],
               'rule_action_type' => $rule_action_type[$key],
               'head_id' => $head_id[$key],
               'col_type1' => $col_type1[$key],
               'rule_condition1' => $rule_condition1[$key],
               'content' => $content[$key],
               'content1' => $content1[$key],
               'rule_name' => $rule_name[$key],
               'category' => $category[$key],
               'customer' => $customer[$key],
               'supplier' => $supplier[$key],
               'rule_for'=> $rule_for[$key],
               'rule_in'=> $rule_in[$key],
               'status' => $req->input('rule_status', 1) == 0 ? 0 : 1,
               'updated_at' => date('d-m-Y H:i:s'),
               'updated_by' => Session::get('id')
            ]);
         }           
         $req->session()->flash('success_msg','Rule Update Successfully');
      }else{
         foreach ($col_type as $key => $value){
            $answers[] = [
               'col_type' => $value,
               'rule_condition' => $rule_condition[$key],
               'rule_action_type' => $rule_action_type[$key],
               'head_id' => $head_id[$key],
               'col_type1' => $col_type1[$key],
               'rule_condition1' => $rule_condition1[$key],
               'content' => $content[$key],
               'content1' => $content1[$key],
               'rule_name' => $rule_name[$key],
               'rule_for'=> $rule_for[$key],
               'rule_in'=> $rule_in[$key],
               'status' => $req->input('rule_status', 1) == 0 ? 0 : 1,
               'category' => $category[$key],
               'customer' => $customer[$key],
               'supplier' => $supplier[$key],
               'merchant_id' => Session::get('merchant_id'),
               'created_at' => date('d-m-Y H:i:s'),
               'created_by' => Session::get('id')
            ];
         }
         Rule::insert($answers);         
         $req->session()->flash('success_msg','Rule Add Successfully');
      }
      return redirect('merchant/manage-rules');      
   }
   public function getHead(Request $req){
      $type = $req->input('type');
      $data = [];
      $name = "";
      if($type=="Credit"){
         //Income/Liabilities
         $account = AccountType::select('id','name')->whereIN('parent_id', [3, 4])->get();
         $account_type = AccountType::whereIN('parent_id', [3, 4])->pluck('id');         
         $head = Head::select('id','name','mandatory_category','mandatory_customer','mandatory_supplier')
               ->whereIN('account_type_id',$account_type)
               ->where(function($query) use ($name){
                  $query->where('merchant',Session::get('merchant_id'));
                  $query->orWhereNull('merchant');
               })
               ->where(function($query) use ($name){
                  $query->where('merchant',Session::get('merchant_id'));
                  $query->orWhere('hybrid_status',1);
               })
               ->orderBy('name')->get();         
      }else if($type=="Debit"){
         //Asset/Expense
         $account = AccountType::select('id','name')->whereIN('parent_id', [1, 2])->get(); 
         $account_type = AccountType::whereIN('parent_id', [1, 2])->pluck('id');         
         $head = Head::select('id','name','mandatory_category','mandatory_customer','mandatory_supplier')
               ->whereIN('account_type_id',$account_type)
               ->where(function($query) use ($name){
                  $query->where('merchant',Session::get('merchant_id'));
                  $query->orWhereNull('merchant');
               })
               ->where(function($query) use ($name){
                  $query->where('merchant',Session::get('merchant_id'));
                  $query->orWhere('hybrid_status',1);
               })
               ->orderBy('name')
               ->get();         
      }else if($type=="All"){
         //Asset/Expense
         $account = AccountType::select('id','name')->whereIN('parent_id', [1, 2, 3,4,])->get(); 
         $account_type = AccountType::whereIN('parent_id', [1, 2,3,4])->pluck('id');         
         $head = Head::select('id','name','mandatory_category','mandatory_customer','mandatory_supplier')
                  ->whereIN('account_type_id',$account_type)
                  ->where(function($query) use ($name){
                     $query->where('merchant',Session::get('merchant_id'));
                     $query->orWhereNull('merchant');
                  })
                  ->where(function($query) use ($name){
                        $query->where('merchant',Session::get('merchant_id'));
                        $query->orWhere('hybrid_status',1);
                     })
                  ->orderBy('name')
                  ->get();         
      }
      $statement = [];
      if($req->input('voucher_no') && $req->input('voucher_no')!=""){
         $statement = Statement::where('voucher_no',$req->input('voucher_no'))->where('merchant_id',Session::get('merchant_id'))->get();
         foreach ($statement as $key => $value) {

            if($value->debit!='' && $value->debit!='-'){                         
               $account_type1 = AccountType::whereIN('parent_id', [1, 2])->pluck('id');
               $shead = Head::select('id','name','mandatory_category','mandatory_customer','mandatory_supplier')
                     ->whereIN('account_type_id',$account_type1)
                     ->where(function($query) use ($name){
                        $query->where('merchant',Session::get('merchant_id'));
                        $query->orWhereNull('merchant');
                     })
                     ->where(function($query) use ($name){
                        $query->where('merchant',Session::get('merchant_id'));
                        $query->orWhere('hybrid_status',1);
                     })
                     ->orderBy('name')
                     ->get(); 
               $statement[$key]->head = $shead;
            }else{
               $account_type1 = AccountType::whereIN('parent_id', [3, 4])->pluck('id');
               $shead = Head::select('id','name','mandatory_category','mandatory_customer','mandatory_supplier')
                     ->whereIN('account_type_id',$account_type1)
                     ->where(function($query) use ($name){
                        $query->where('merchant',Session::get('merchant_id'));
                        $query->orWhereNull('merchant');
                     })
                     ->where(function($query) use ($name){
                        $query->where('merchant',Session::get('merchant_id'));
                        $query->orWhere('hybrid_status',1);
                     })
                     
                     ->orderBy('name')->get();
               $statement[$key]->head = $shead;
            }
            
         }
      }
      array_push($data,array("head"=>$head,"account_type"=>$account,"statements"=>$statement));
      return $data;
   }
   public function getRule(Request $req){
      $rule = Rule::join('head', 'rules.head_id', '=', 'head.id')
               ->where("merchant_id",Session::get('merchant_id'))
               ->get(['rules.*', 'head.name']);
      return $rule;
   }
   public function fileImport(Request $req){
      $header_row = $req->input('header_row');
      $last_row = $req->input('last_row');
      $amount_column = $req->input('amount_column');
      $amount_contain = $req->input('amount_contain');
      $date_col = $req->input('date_col');
      $description_col = $req->input('description_col');
      $amount_col = $req->input('amount_col');
      $debit_amount_col = $req->input('debit_amount_col');
      $credit_amount_col = $req->input('credit_amount_col');
      $bank = $req->input('bank');
      //$select_rule = $req->input('select_rule');
      $data = array(
         "header_row"=>$header_row,
         "last_row"=>$last_row,
         "amount_column"=>$amount_column,
         "date_col"=>$date_col,
         "description_col"=>$description_col,
         "amount_col"=>$amount_col,
         "debit_amount_col"=>$debit_amount_col,
         "credit_amount_col"=>$credit_amount_col,
         "bank"=>$bank,
         //"select_rule"=>$select_rule,
         "amount_contain"=>$amount_contain
      );
      $import = new UsersImport($data);
      Excel::import($import, $req->file('csv_file')->store('temp'));
      $returns = \App\Support\ReturnDetector::scan();   // payments the bank has taken back are recognised and reversed
      return redirect('merchant/uncategorized-data')->with('bank_upload_summary', [
         'returns'  => $returns,
         'year'     => FinancialYears::current(),
         'saved'    => $import->saved,
         'rejected' => $import->rejected,
         'auto'     => $import->auto_categorised,
         'conflicts'=> $import->conflicts,
      ]);
      //$req->session()->flash('success_msg','CSV Uploaded Successfully');
   }      
   public function fileExport(){
      return Excel::download(new UsersExport, 'users-collection.xlsx');
   }   
   public function manageStatement(Request $request,$year=null,$id=null){
      $entity = Entity::where('status',1)->where('merchant_id',Session::get('merchant_id'))->orderBy('id','desc')->first();
   $selected_year = $entity->financial_year;

   if($year!="" && $year!=null){
      $selected_year = $year;
   }
   $bankname = Head::select('id','name')
                  ->where('account_type_id',3)
                  ->where('merchant',Session::get('merchant_id'))
                  ->where('status',1)
                  ->get();
   if(!empty($id)){
      $hid = $id;
   }else{
      $hid = $bankname[0]->id;
   }
   $bank = Statement::select('debit','credit')
                        ->where('head_id',$hid)
                        ->where("statement.opening",1)
                        ->where('merchant_id',Session::get('merchant_id'))
                        ->where("statement.fyear",$selected_year)
                        ->first();
                        if($bank){
                           $bank_open = $bank->debit ." Dr";
                        }else{
                           $bank_open = "0 Dr";
                        }  
                        
                        
      if($request->ajax()){
         $i = 1;
         $dr = "";
         $cr = "";
         $penA = 0;
         $pending_amount = 0;

         if($bank){
            //$pending_amount = $bank->balance;
         }    

         $data = Statement::leftjoin('head', 'statement.head_id', '=', 'head.id')
                  ->where("merchant_id",Session::get('merchant_id'))
                  ->whereIn("statement.status",[0,1])
                  ->where('head_id',$hid)
                  ->where("statement.opening",0)
                  ->where("statement.fyear",$selected_year)
                  ->where(function($q){
                     $q->whereNull("statement.voucher_no")->orWhere("statement.voucher_no","like","SBV-%");
                  })
                  ->orderBy("statement.opening","desc")
                  ->orderBy(DB::raw("COALESCE(STR_TO_DATE(statement.st_date, '%Y-%m-%d'), STR_TO_DATE(statement.st_date, '%d-%m-%Y'))"),"asc")
                  ->orderBy("statement.id","asc")
                  ->get(['statement.*', 'head.name']);
         // Running balance of a bank account: opening + debit - credit.
         // (The bank-side rows hold money received as Debit and money paid out
         // as Credit, the same as the Trial Balance, so the two always agree.)
         $toNumber = function($value){
            $value = str_replace(',', '', (string)$value);
            return is_numeric($value) ? (float)$value : 0;
         };
         $running = $bank ? ($toNumber($bank->debit) - $toNumber($bank->credit)) : 0;
         foreach ($data as $key => $row){
            // Only categorised entries are in the accounts, so only they move the balance.
            if((int)$row->status === 1){ $running += $toNumber($row["debit"]) - $toNumber($row["credit"]); }
            $data[$key]->balance = number_format($running,2);
         }
         // The other side of each bank line: which account it went to, and whether a rule or a person chose it.
         $vnos = $data->pluck('voucher_no')->filter()->unique()->values()->all();
         $others = [];
         if($vnos){
            $heads = Head::pluck('name','id');
            $ruleNames = Rule::pluck('rule_name','id');
            $users = \DB::table('merchant')->pluck('name','id');
            foreach(\DB::table('statement')->where('merchant_id',Session::get('merchant_id'))->whereIn('voucher_no',$vnos)->whereRaw('(head_id IS NULL OR head_id = \'\' OR head_id <> bank_id)')->get() as $o){
               $others[$o->voucher_no] = $o;
            }
            foreach($data as $k=>$row){
               $o = $others[$row->voucher_no] ?? null;
               if(!$o || (int)$o->status !== 1){ $data[$k]->account = 'Not categorised'; $data[$k]->how = ''; continue; }
               $data[$k]->account = $heads[$o->head_id] ?? '';
               $data[$k]->how = $o->cat_source==='return' ? 'Returned payment' : ($o->cat_source==='rule' ? 'By rule'.(isset($ruleNames[$o->cat_rule_id]) ? ': '.$ruleNames[$o->cat_rule_id] : '')
                              : ($o->cat_source==='person' ? 'By '.($users[$o->cat_by] ?? 'a user') : ''));
            }
         }
         return Datatables::of($data)
         ->addIndexColumn()
         ->addColumn('action', function($row){
               // $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm edit_rule" data-id="'.$row->id.'">Edit</a>';
               // return $actionBtn;
            })
         ->rawColumns(['action'])
         ->make(true);
      }  

      $entity_financial_year = EntityFinancialYear::where('merchant_id',Session::get('merchant_id'))
                                 ->where('status',1)
                                 ->orderBy('id')
                                 ->get();
      $show_closed = (bool)request('closed');
      $year_options = FinancialYears::options($entity_financial_year->pluck('financial_year')->all(), $selected_year, $show_closed);
      $year_is_closed = !FinancialYears::isOpen($selected_year);
      $year_is_reopened = FinancialYears::isReopened($selected_year);
      return view('account.statement',["year_options"=>$year_options,"year_is_closed"=>$year_is_closed,"year_is_reopened"=>$year_is_reopened,"bank"=>$bank_open,"bankname"=>$bankname,"selected_year"=>$selected_year,"selected_bank"=>$hid,"entity_financial_year"=>$entity_financial_year,"entity"=>$entity]);
   }
   public function uncategorizedData(Request $request){
      if($request->ajax()){      
         $open_years = FinancialYears::openYears();
         $data = Statement::leftjoin('head', 'statement.head_id', '=', 'head.id')
                  ->where("merchant_id",Session::get('merchant_id'))
                  ->where("statement.status",0)
                  ->where(function($q) use ($open_years){
                     $q->whereNull("statement.fyear")->orWhereIn("statement.fyear",$open_years);
                  })
                  ->orderBy(DB::raw("STR_TO_DATE(statement.st_date, '%d-%m-%Y')"),"desc")
                  ->orderBy("statement.id","desc")
                  ->get(['statement.*', 'head.name']);
         return Datatables::of($data)
         ->addIndexColumn()
         ->addColumn('action', function($row){
               $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm row_apply_rule" data-id="659" style="background-color: #dfa350;">Apply Rule</a> <a href="javascript:void(0)" class="edit btn btn-success btn-sm row_manually" data-id="'.$row->id.'" data-debit="'.$row->debit.'" data-credit="'.$row->credit.'" data-voucher_no="'.$row->voucher_no.'" style="background-color: #0be4598c;" >Assign Manually</a>';
               return $actionBtn;
            })
         ->addColumn('bulk_check', function($row){
               $checkBtn = '<input type="checkbox" class="check" value="'.$row->id.'" data-debit="'.$row->debit.'" data-credit="'.$row->credit.'" data-remark="'.$row->remark.'" data-st_date="'.$row->st_date.'">';
               return $checkBtn;
            })
         ->rawColumns(['action','bulk_check'])
         ->make(true);
      }
      $rule = Rule::join('head', 'rules.head_id', '=', 'head.id')
                     ->where("merchant_id",Session::get('merchant_id'))
                     ->get(['rules.*', 'head.name']);
      $bank = Head::where('account_type_id',3)->where('status',1)->get();
      return view('account.uncategorized_data',["rules"=>$rule,"banks"=>$bank]);
   }
   public function applyRule(Request $req){
      // The user picked ONE rule for the selected lines: it is applied only where it fits.
      $req->validate(['select_rule' => 'required']);
      $rule = Rule::where('id',$req->input('select_rule'))->first();
      $closed_line = Statement::whereIn('id',(array)$req->input('apply_row_id'))->get()->first(function($l){ return !FinancialYears::isOpen($l->fyear); });
      if($closed_line){
         return response()->json(['success'=>false,'message'=>FinancialYears::message($closed_line->fyear)]);
      }
      $rows = Statement::select('statement.*')
               ->where("merchant_id",Session::get('merchant_id'))
               ->whereIn("statement.id",(array)$req->input('apply_row_id'))
               ->where("statement.status",0)
               ->get();
      $done = 0; $not_fit = 0;
      foreach ($rows as $value) {
         if($rule && RuleMatcher::fits($rule, $value->remark, $value->debit, $value->credit)){
            \App\Support\LineCategoriser::set($value->id, $rule->head_id, 'rule', $rule->id);
            $this->auditRuleApplied($value, 'resolved', $rule->id, $rule->head_id, [$rule->id]);
            $done++;
         }else{
            $not_fit++;
         }
      }
      session()->flash('rule_apply_msg', $done.' line(s) categorised by the rule'.($not_fit ? '; '.$not_fit.' did not fit it and were left uncategorised.' : '.'));
      echo 1;
   }
   public function applyBulkRule(Request $req){
      // Every uncategorised line is tested against ALL active rules.
      //  - fits no rule                          -> left alone
      //  - fits rules that agree on one account  -> categorised
      //  - fits rules that point to different accounts -> held on the Rule conflicts screen
      // Never touches lines that already have an account, bank-side entries or opening balances.
      $rows = Statement::select('statement.*')
               ->where("merchant_id",Session::get('merchant_id'))
               ->where("statement.status",0)
               ->where("statement.opening",0)
               ->where(function($q){
                  $q->whereNull("statement.fyear")->orWhereIn("statement.fyear",FinancialYears::openYears());
               })
               ->get();
      $rules = Rule::select('*')->where('status',1)->orderBy('id')->get();
      $done = 0; $clash = 0; $untraceable = 0;
      foreach ($rows as $value) {
         $matched = [];
         foreach ($rules as $rule) {
            if(RuleMatcher::fits($rule, $value->remark, $value->debit, $value->credit)){
               $matched[] = ['rule'=>$rule->id,'head'=>$rule->head_id];
            }
         }
         if(count($matched)==0){ continue; }
         $heads = array_values(array_unique(array_column($matched,'head')));
         $ids = array_values(array_unique(array_column($matched,'rule')));
         if(count($heads)==1){
            \App\Support\LineCategoriser::set($value->id, $heads[0], 'rule', $matched[0]['rule']);
            $this->auditRuleApplied($value, 'auto', $matched[0]['rule'], $heads[0], $ids, $matched);
            $done++;
         }else if(empty($value->voucher_no)){
            $untraceable++; // an old line without a voucher number cannot be held on the conflicts screen
         }else{
            $this->auditRuleApplied($value, 'conflict', null, null, $ids, $matched);
            $clash++;
         }
      }
      $msg = $done.' line(s) categorised by rules.';
      if($clash){ $msg .= ' '.$clash.' line(s) fit more than one rule and need your choice (Rule conflicts).'; }
      if($untraceable){ $msg .= ' '.$untraceable.' older line(s) fit several rules but have no voucher number, so they were left for you to categorise by hand.'; }
      session()->flash('rule_apply_msg', $msg);
      echo 1;
   }
   /** One audit row per rule decision made from the uncategorised screen. */
   private function auditRuleApplied($line, $outcome, $appliedRule, $headId, array $ruleIds, array $matched = []){
      if(empty($line->voucher_no)){ return; }
      // do not stack a second 'conflict' row for a line that is already waiting
      if($outcome=='conflict' && DB::table('rule_audit')->where('voucher_no',$line->voucher_no)->where('outcome','conflict')->exists()){ return; }
      sort($ruleIds);
      $amount = (RuleMatcher::clean($line->debit)!=='') ? RuleMatcher::clean($line->debit) : RuleMatcher::clean($line->credit);
      DB::table('rule_audit')->insert([
         'merchant_id'=>Session::get('merchant_id'), 'batch'=>'apply-'.date('YmdHis').'-'.Session::get('id'),
         'voucher_no'=>$line->voucher_no, 'fyear'=>$line->fyear, 'line_date'=>$line->st_date,
         'description'=>substr((string)$line->remark,0,500), 'amount'=>(float)$amount,
         'direction'=>(RuleMatcher::clean($line->debit)!=='') ? 'Debit' : 'Credit',
         'matched'=>json_encode($matched ?: array_map(function($r){ return ['rule'=>$r,'head'=>null]; }, $ruleIds)),
         'matched_key'=>implode(',',$ruleIds), 'outcome'=>$outcome,
         'applied_rule_id'=>$outcome=='auto' ? $appliedRule : null,
         'chosen_rule_id'=>$outcome=='resolved' ? $appliedRule : null,
         'head_id'=>$headId,
         'created_by'=>Session::get('id'),
         'decided_by'=>$outcome=='resolved' ? Session::get('id') : null,
         'decided_at'=>$outcome=='resolved' ? now() : null,
         'created_at'=>now(), 'updated_at'=>now(),
      ]);
   }
   public function assignHeadManually(Request $req){
      $input = $req->all();
      if(isset($input['voucher_row'])){
         $closed_line = Statement::whereIn('id',$input['voucher_row'])->get()->first(function($l){ return !FinancialYears::isOpen($l->fyear); });
         if($closed_line){
            return response()->json(['success'=>false,'message'=>FinancialYears::message($closed_line->fyear)]);
         }
         foreach ($input['voucher_row'] as $key => $value){
            if($input['voucher_head'][$key]!=""){
               \App\Support\LineCategoriser::set($value, $input['voucher_head'][$key], 'person');
            }
         }
         echo 1;
         exit();
      }
      $select_head = $input['select_head'];
      $manually_id = $input['manually_id'];
      $statement = Statement::find($manually_id);
      if(!FinancialYears::isOpen($statement->fyear)){
         return response()->json(['success'=>false,'message'=>FinancialYears::message($statement->fyear)]);
      }
      // The bank's own entry already exists (it is created when the line is uploaded);
      // choosing the account completes the other side and brings both entries into the accounts.
      if(\App\Support\LineCategoriser::set($statement->id, $select_head, 'person')){
         echo 1;
      }
      
   }
   public function ledgers(Request $request){
      $selected_year = $request->input('year') ?: FinancialYears::current();
      if($request->ajax()){
         $data = Statement::leftjoin('head', 'statement.head_id', '=', 'head.id')
                           ->select(DB::raw("ROUND(sum(debit+0),2) as debitTotal,ROUND(sum(credit+0),2) as creditTotal,ROUND(sum(debit+0)-sum(credit+0),2) as net,count(*) as cnt,head_id,head.name"))
                           ->where("merchant_id",Session::get('merchant_id'))
                           ->effective()
                           ->where("statement.fyear",$selected_year)
                           ->groupBy('statement.head_id')
                           ->orderBy('head.name')
                           ->get();
         return Datatables::of($data)
         ->addIndexColumn()
         ->addColumn('hname', function($row) use ($selected_year){
               return '<a href="javascript:void(0)" class="ledger-link view_all" data-id="'.$row->head_id.'">'.e($row->name).'</a>';
            })
         ->addColumn('debit_fmt', function($row){ return $row->debitTotal > 0 ? number_format($row->debitTotal,2) : ''; })
         ->addColumn('credit_fmt', function($row){ return $row->creditTotal > 0 ? number_format($row->creditTotal,2) : ''; })
         ->addColumn('balance_fmt', function($row){ return number_format(abs($row->net),2); })
         ->addColumn('amount_type', function($row){ return $row->net > 0 ? 'Dr' : ($row->net < 0 ? 'Cr' : ''); })
         ->rawColumns(['hname'])
         ->make(true);
      }
      $efy = EntityFinancialYear::where('merchant_id',Session::get('merchant_id'))->where('status',1)->pluck('financial_year')->all();
      $year_options = FinancialYears::options($efy, $selected_year, true);
      return view('account.ledger',["year_options"=>$year_options,"selected_year"=>$selected_year]);
   }
   public function ledgerByHead(Request $request){
      $id = $request->input('id');     
      $data = Statement::leftjoin('head', 'statement.head_id', '=', 'head.id')
                     ->where("merchant_id",Session::get('merchant_id')) 
                     ->where("statement.status",1)
                     ->where("statement.head_id",$id)                     
                     ->orderBy(DB::raw("STR_TO_DATE(statement.st_date, '%d-%m-%Y')"),"asc")
                     ->get(['statement.*', 'head.name']);    
      return $data;         
   }
   public function manageAccount(Request $request){
      $category = Category::where('status',1)->get(); 
      $merchant = Session::get('merchant_id');
      $heads = Head::where('merchant',$merchant)->whereIn('account_type_id',[3,8,18])->orderBy('name')->get();
      $isRent = function($h){ return trim((string)$h->property_address) !== ''; };
      $lists = [
         'employee' => $heads->where('account_type_id',8)->reject($isRent)->values(),
         'rent'     => $heads->where('account_type_id',8)->filter($isRent)->values(),
         'supplier' => $heads->where('account_type_id',18)->values(),
         'bank'     => $heads->where('account_type_id',3)->values(),
         'debtor'   => Customer::where('merchant',$merchant)->orderBy('first_name')->get(),
      ];
      return view('account.account',['categorys'=>$category,'lists'=>$lists]);
   }
   public function addEmployee(Request $request){
      $input =  $request->all();
      $head = new Head;
      $account_type = 8;
      $head->account_type_id = $account_type;
      $head->name = $input['employee_name']; 
      $head->fname = $input['fname'];
      $head->dob = $input['dob'];
      $head->pan = $input['pan'];
      $head->aadhar = $input['aadhar'];
      $head->account_no = $input['employee_account_no'];
      $head->branch_name = $input['employee_bank_name'];
      $head->ifsc_code = $input['employee_ifsc_code'];
      $head->pf_account = $input['pf_account'];
      $head->category = $input['employee_category'];
      $head->merchant = Session::get('merchant_id');
      $head->created_at = date('d-m-Y H:i:s');
      $head->created_by = Session::get('id');
      if($head->save()){
         return response(['status' => '1','success' => 'Employee created successfully.']);
      }else{
         return response(['status' => '0','success' => 'Employee created successfully.']);
      }
   }
   public function addSupplier(Request $req){
      $title = $req->input('title');
      $first_name = $req->input('first_name');
      $last_name = $req->input('last_name');
      $email = $req->input('email');
      $phone = $req->input('phone');
      $mobile = $req->input('mobile');
      $display_name = $req->input('display_name'); 
      $gst_type = $req->input('gst_type');
      $tax_id_error = TaxId::error($req->input('gst'));
      if($tax_id_error){
         return response()->json(['success' => $tax_id_error, 'errors' => ['gst' => [$tax_id_error]]], 422);
      }
      $gst = TaxId::clean($req->input('gst'));
      $billing_street = $req->input('billing_street');
      $billing_city = $req->input('billing_city');
      $billing_state = $req->input('billing_state');
      $billing_pincode = $req->input('billing_pincode');
      $billing_country = $req->input('billing_country');
      $shipping_street = $req->input('shipping_street');
      $shipping_city = $req->input('shipping_city');
      $shipping_state = $req->input('shipping_state');
      $shipping_pincode = $req->input('shipping_pincode');
      $shipping_country = $req->input('shipping_country');
      $party = new Customer;
      $party->type = 4;
      $party->title = $title; $party->first_name = $first_name; $party->last_name = $last_name; $party->email = $email; $party->phone = $phone; $party->mobile = $mobile;
      $party->display_name = $display_name; $party->gst_type = $gst_type; $party->gst = $gst;
      $party->billing_street = $billing_street; $party->billing_city = $billing_city; $party->billing_state = $billing_state; $party->billing_pincode = $billing_pincode; $party->billing_country = $billing_country;
      $party->shipping_street = $shipping_street; $party->shipping_city = $shipping_city; $party->shipping_state = $shipping_state; $party->shipping_pincode = $shipping_pincode; $party->shipping_country = $shipping_country;
      $party->opening_balance = $req->input('supplier_opening_balance'); $party->opening_balance_type = $req->input('supplier_open_type'); $party->opening_balance_date = $req->input('supplier_opening_balance_date');
      $party->category_id = $req->input('supplier_category');
      $this->fillPartyTax($party, $req);
      $party->merchant = Session::get('merchant_id'); $party->status = 1; $party->created_at = date('d-m-Y H:i:s'); $party->created_by = Session::get('id');
      $party->save();
      return response(['success' => 'Supplier created successfully.']);
   }
   public function addBank(Request $request){
      $input =  $request->all();
      $head = new Head;
      $head->account_type_id = 3;
      $head->name = $input['bank_name'];     
      $head->balance = $input['bank_opening_balance'];
      $head->balance_type = $input['bank_open_type'];
      $head->balance_date = $input['bank_opening_balance_date'];
      $head->account_no = $input['bank_account_no'];
      $head->ifsc_code = $input['bank_ifsc_code'];
      $head->branch_name = $input['branch_name'];
      $head->branch_address = $input['branch_address'];
      $head->pincode = $input['pincode'];
      $head->category = $input['bank_category'];
      $head->merchant = Session::get('merchant_id');
      $head->created_at = date('d-m-Y H:i:s');
      $head->created_by = Session::get('id');
      if($head->save()){
         $entity = Entity::select('financial_year')->where('merchant_id',Session::get('merchant_id'))->first();
         $statement = new Statement();
         if($input['bank_open_type']=="Credit"){
            $statement->credit = $input['bank_opening_balance'];
         }else{
            $statement->debit = $input['bank_opening_balance'];
         }
         $statement->remark = "Opening Balance";
         $statement->head_id = $head->id;
         $statement->st_date = date('Y-m-d',strtotime($input['bank_opening_balance_date']));
         $statement->fyear = $entity->financial_year;
         $statement->opening = 1;
         $statement->status = 1;
         $statement->merchant_id = Session::get('merchant_id');
         $statement->created_at = date('d-m-Y H:i:s');
         $statement->created_by = Session::get('id');
         $statement->save();
         return response(['status' => '1','success' => 'Bank created successfully.']);
      }else{
         return response(['status' => '0','success' => 'Bank created successfully.']);
      }
   }
   public function addRent(Request $request){
      $input =  $request->all();
      $head = New Head;
      $head->name = $input['landlord_name'];
      $head->pan = $input['landlord_pan'];
      $head->property_address = $input['property_address'];
      $head->tds_status = in_array($input['tds_status'] ?? null, ['Yes', '1', 1], true) ? 1 : 0;
      $head->tds_rate = $input['tds_rate'];
      $head->category = $input['rent_category'];
      $head->account_type_id = 8;
      $head->merchant = Session::get('merchant_id');
      $head->created_at = date('d-m-Y H:i:s');
      $head->created_by = Session::get('id');
      if($head->save()){
         return response(['status' => '1','success' => 'Rent created successfully.']);
      }else{
         return response(['status' => '0','success' => 'Rent created successfully.']);
      }
      
   }
   public function manageProduct(Request $request){
      $unit = Unit::where('status',1)->orderBy('name')->get();
      $head = Head::where('status',1)->where('status',1)->orderBy('name')->get();
      $products = DB::table('products as p')->leftJoin('units as u','u.id','=','p.unit')->leftJoin('head as h','h.id','=','p.head')
                     ->where('p.merchant_id',Session::get('merchant_id'))->orderByDesc('p.status')->orderBy('p.name')
                     ->get(['p.*','u.name as unit_name','h.name as head_name']);
      return view('account.product',["units"=>$unit,"heads"=>$head,"products"=>$products]);
   }
   public function addProduct(Request $request){
      $input =  $request->all();      
      $validated = $request->validate([
         'type' => 'required',
         'name' => 'required',
         'unit' => 'required',         
         'price' => 'required',
      ]);
      if(!empty($input['product_id'])){         
         $product = Product::find($input['product_id']); 
         $product->type = $input['type'];
         $product->name = $input['name'];
         $product->unit = $input['unit'];
         $product->hsn_code = $input['hsn_code'];
         $product->tax_preference = $input['tax_preference'];
         $product->price = $input['price'];
         $product->head = $input['head'];
         $product->description = $input['description'];
         if(isset($input['tax_rate'])){
            $product->tax_rate = $input['tax_rate'];
         }else{
            $product->tax_rate = 0;
         }
         $product->status = $input['status'];
         $product->updated_by = Session::get('id');
         $product->updated_at = date('d-m-Y H:i:s');
         if($product->save()){
            $response = [
                  'success' => true,
                  'data'    => '',
                  'message' => "Product Updated Successfully.",
               ];
            return response()->json($response, 200);
         }else{
            $response = [
                  'success' => false,
                  'data'    => '',
                  'message' => "Something Went Wrong.",
               ];
            return response()->json($response, 200);
         }
      }else{  
         $product = new Product();          
         $product->type = $input['type'];
         $product->name = $input['name'];
         $product->unit = $input['unit'];
         $product->hsn_code = $input['hsn_code'];
         $product->tax_preference = $input['tax_preference'];
         $product->price = $input['price'];
         $product->head = $input['head'];
         $product->description = $input['description'];
         if(isset($input['tax_rate'])){
            $product->tax_rate = $input['tax_rate'];
         }else{
            $product->tax_rate = 0;
         }
         
         $product->status = $input['status'];
         $product->merchant_id = Session::get('merchant_id');
         $product->created_by = Session::get('id');
         $product->created_at = date('d-m-Y H:i:s');
         if($product->save()){
            $response = [
                  'success' => true,
                  'data'    => '',
                  'message' => "Product Add Successfully.",
               ];
            return response()->json($response, 200);
         }else{
            $response = [
                  'success' => false,
                  'data'    => '',
                  'message' => "Something Went Wrong",
               ];
            return response()->json($response, 200);
         }
      }      
   }
   public function manageInvoice(Request $request){
      $customer = Customer::select('id','first_name','last_name','gst')
                           ->where('merchant',Session::get('merchant_id'))
                           ->where('type',1)
                           ->where('status',1)
                           ->orderBy('first_name')
                           ->get();
      $product = Product::select('id','name','hsn_code','tax_preference','price','tax_rate','description')
                           ->where('merchant_id',Session::get('merchant_id'))
                           ->where('status',1)
                           ->orderBy('name')
                           ->get();
      return view('account.invoice',['customers'=>$customer,"products"=>$product]);
   }
   public function uploadExpencessCSV(Request $request){
      return view('account.upload_expencess_csv');
   }
   public function importExpencessCsv(Request $req){
      $header_row = $req->input('header_row');
      $last_row = $req->input('last_row');
      $amount_column = $req->input('amount_column');
      $amount_contain = $req->input('amount_contain');
      $date_col = $req->input('date_col');
      $description_col = $req->input('description_col');
      $amount_col = $req->input('amount_col');
      $debit_amount_col = $req->input('debit_amount_col');
      $credit_amount_col = $req->input('credit_amount_col');
      $serial_col = $req->input('serial_col');
      $data = array(
         "header_row"=>$header_row,
         "last_row"=>$last_row,
         "amount_column"=>$amount_column,
         "date_col"=>$date_col,
         "description_col"=>$description_col,
         "amount_col"=>$amount_col,
         "debit_amount_col"=>$debit_amount_col,
         "credit_amount_col"=>$credit_amount_col,
         "serial_col"=>$serial_col,
         "amount_contain"=>$amount_contain
      );
      $import = new ExpencessCsvImport($data);
      Excel::import($import, $req->file('csv_file')->store('temp'));
      // After every upload: say how many vouchers were created and whether each one balances.
      $unbalanced = [];
      $line_count = 0;
      foreach ($import->summary as $voucher_no => $voucher){
         $line_count += $voucher['lines'];
         $difference = round($voucher['debit'] - $voucher['credit'], 2);
         if(abs($difference) > 0.004){
            $unbalanced[$voucher_no] = ['ref'=>$voucher['ref'],'debit'=>$voucher['debit'],'credit'=>$voucher['credit'],'difference'=>$difference];
         }
      }
      return back()->with('upload_summary', [
         'year'       => FinancialYears::current(),
         'rejected'   => $import->rejected,
         'vouchers'   => count($import->summary),
         'lines'      => $line_count,
         'first'      => count($import->summary) ? array_key_first($import->summary) : null,
         'last'       => count($import->summary) ? array_key_last($import->summary) : null,
         'unbalanced' => $unbalanced,
      ]);
      //$req->session()->flash('success_msg','CSV Uploaded Successfully');
   }
   public function balanceSheet(Request $request,$year = null){
      $name = "";
      $entity = Entity::where('status',1)->where('merchant_id',Session::get('merchant_id'))->orderBy('id','desc')->first();
      $selected_year = "";
      $financial_year_end = "";
      if($entity){
         $selected_year = $entity->financial_year;
         $financial_year_end = date('d-m-Y',strtotime($entity->financial_year_end));
      }      
      if($year!="" && $year!=null){
         $selected_year = $year;
         $chosen_year = EntityFinancialYear::where('merchant_id',Session::get('merchant_id'))->where('financial_year',$year)->first();
         if($chosen_year){
            $financial_year_end = date('d-m-Y',strtotime($chosen_year->end_year));
         }
      }
      $bs_raw = [];
      $account_type_parent = AccountTypeParent::where('financial_statement_id',1)
      ->orderBy('sequence')
      ->get();
      foreach ($account_type_parent as $key => $value) {
         $account_total = 0;
         $account_type = AccountType::where('parent_id',$value->id)->orderBy('sequence')->get();
         $name = "";
         foreach ($account_type as $k => $v){
            $head = Head::where('account_type_id',$v->id)
                           ->where('status',1)
                           ->where(function($query) use ($name){
                              $query->where('merchant',Session::get('merchant_id'));
                              $query->orWhereNull('merchant');
                           })
                           ->orderBy('sequence')
                           ->get();
            $head_total = 0;
            foreach ($head as $k1 => $v1){               
               $statement = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
                     ->effective()
                     ->where("fyear",$selected_year)
                     ->where('head_id',$v1->id)
                     ->where('merchant_id',Session::get('merchant_id'))
                     ->get();
               $debitAmount = $statement[0]->debitAmount;
               $creditAmount = $statement[0]->creditAmount;                     
               if($value->id==1){
                  $head[$k1]->amount = number_format($debitAmount - $creditAmount,2);
                  $head_total = $head_total + $debitAmount - $creditAmount;
               }else{
                  $head[$k1]->amount = round($creditAmount - $debitAmount,2);
                  $head_total = $head_total + $creditAmount - $debitAmount;
               }
               if($v1->id==78){
                  $account_total2 = 0;
                  $account_type_parent21 = AccountTypeParent::where('id',3)->get();
                  foreach ($account_type_parent21 as $key21 => $value21) {
                     $account_type22 = AccountType::where('parent_id',$value21->id)->orderBy('sequence')->get();
                     foreach ($account_type22 as $k22 => $v22) {
                        $head23 = Head::where('account_type_id',$v22->id)
                        ->where('status',1)
                        ->where(function($query) use ($name){
                           $query->where('merchant',Session::get('merchant_id'));
                           $query->orWhereNull('merchant');
                        })
                        ->orderBy('sequence')
                        ->get();
                        $head_total2 = 0;
                        foreach($head23 as $k23 => $v23){
                           $statement24 = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
                                 ->effective()
                              ->where('head_id',$v23->id)
                              ->where('merchant_id',Session::get('merchant_id'))
                              ->where("fyear",$selected_year)
                              ->get();
                              $head_total2 = $head_total2 + $statement24[0]->debitAmount - $statement24[0]->creditAmount;
                        }
                        $account_total2 = $account_total2 + $head_total2;
                     }
                  }
                  // //Lia..
                  $account_total1 = 0;
                  $account_type_parent31 = AccountTypeParent::where('id',2)->get();
                  foreach ($account_type_parent31 as $key31 => $value31){
                     $account_type32 = AccountType::where('parent_id',$value31->id)->orderBy('sequence')->get();
                     foreach ($account_type32 as $k32 => $v32) {
                        $head33 = Head::where('account_type_id',$v32->id)
                                       ->where('status',1)
                                       ->where(function($query) use ($name){
                                          $query->where('merchant',Session::get('merchant_id'));
                                          $query->orWhereNull('merchant');
                                       })
                                       ->get();
                        $head_total123 = 0;
                        foreach($head33 as $k33 => $v33){
                           $statement34 = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
                                 ->effective()
                              ->where('head_id',$v33->id)
                              ->where('merchant_id',Session::get('merchant_id'))
                              ->where("fyear",$selected_year)
                              ->get();
                              $head_total123 = $head_total123 + $statement34[0]->creditAmount - $statement34[0]->debitAmount;
                        }
                        $account_total1 = $account_total1 + $head_total123;
                     }
                  }
                  
                  $head[$k1]->amount = $head[$k1]->amount + abs($account_total2) - abs($account_total1);
                  $head_total = $head_total + abs($account_total2) - abs($account_total1);
               }         
            }
            $account_type[$k]->head_total = number_format($head_total,2);
            $account_type[$k]->head = $head;
            $account_total = $account_total + $head_total;
         }
         $bs_raw[$value->id] = round($account_total,2);
         $account_type_parent[$key]->account_total = number_format($account_total,2);
         $account_type_parent[$key]->child = $account_type;
      }
      $entity_financial_year = EntityFinancialYear::where('merchant_id',Session::get('merchant_id'))
                                                   ->where('status',1)
                                                   ->get();
      
      $show_closed = (bool)request('closed');
      $year_options = FinancialYears::options($entity_financial_year->pluck('financial_year')->all(), $selected_year, $show_closed);
      $year_is_closed = !FinancialYears::isOpen($selected_year);
      $year_is_reopened = FinancialYears::isReopened($selected_year);
      // Assets (group 1) must equal Equity and Liabilities (group 4)
      $bs_difference = round(($bs_raw[1] ?? 0) - ($bs_raw[4] ?? 0), 2);
      return view('account.balance_sheet',["bs_difference"=>$bs_difference,"year_options"=>$year_options,"year_is_closed"=>$year_is_closed,"year_is_reopened"=>$year_is_reopened,"account_type_parents"=>$account_type_parent,"financial_year"=>$financial_year_end,"entity"=>$entity,"entity_financial_year"=>$entity_financial_year,"selected_year"=>$selected_year]);
   }
   public function ledgerByHeadId(Request $request,$id,$year = null){
      $entity = Entity::where('status',1)->where('merchant_id',Session::get('merchant_id'))->orderBy('id','desc')->first();
      $selected_year = $entity->financial_year;
      if($year!="" && $year!=null){
         $selected_year = $year;
      }
      $head = Head::join('account_type',"head.account_type_id","=","account_type.id")
                     ->where('head.id',$id)
                     ->first(['balance','balance_type','parent_id','head.name']);
      $i = 1;
      $dr = "";
      $cr = "";
      $penA = 0;
      $open = "0";
      $pending_amount = 0;
      if($head){
         $pending_amount = $head->balance;
         $open = $head->balance." ".$head->balance_type;
      }
      $data =[];
      $data = Statement::leftjoin('head', 'statement.head_id', '=', 'head.id')
                  ->where("merchant_id",Session::get('merchant_id'))
                  ->where("statement.status",1)
                  ->where("statement.head_id",$id)
                  ->where("fyear",$selected_year)
                  ->orderBy("statement.opening","desc")
                  ->orderBy(DB::raw("STR_TO_DATE(statement.st_date, '%Y-%m-%d')"),"asc")
                  ->orderBy("statement.id","asc")
                  ->get(['statement.*', 'head.name']);
      foreach ($data as $key => $row){
         $data[$key]->st_date = date('d-m-Y',strtotime($row['st_date']));
         $statement = DB::select("select sum(debit) as debitAmount,sum(credit) as creditAmount from statement where merchant_id='".Session::get('merchant_id')."' and status=1 and STR_TO_DATE(st_date, '%d-%m-%Y') < STR_TO_DATE('".$row['st_date']."', '%d-%m-%Y') and id!='".$row['id']."' and head_id='".$row['head_id']."' and fyear='".$selected_year."'");
        $pending_amount1 = $statement['0']->debitAmount - $statement['0']->creditAmount;
         if($i==1){

            // if($head && $head->balance_type!=""){
            //    if($head->balance_type=="Credit"){
            //       if(!empty($row["credit"]) && $row["credit"]!="-"){
            //          $pending_amount = $pending_amount + (float)$row["credit"];
            //       }else if(!empty($row["debit"]) && $row["debit"]!="-"){
            //          $pending_amount = $pending_amount - (float)$row["debit"];
            //       }
            //    }else{
            //       if(!empty($row["credit"]) && $row["credit"]!="-"){
            //          $pending_amount = $pending_amount - (float)$row["credit"];
            //       }else if(!empty($row["debit"]) && $row["debit"]!="-"){
            //          $pending_amount = $pending_amount + (float)$row["debit"];
            //       }
            //    }
            // }else{
               if($head->parent_id=="1" || $head->parent_id=="2"){
                  if(!empty($row["credit"]) && $row["credit"]!="-"){
                     $pending_amount = $pending_amount1 - (float)$row["credit"];
                  }else if(!empty($row["debit"]) && $row["debit"]!="-"){

                     $pending_amount = $pending_amount1 + (float)$row["debit"];
                  }
               }else if($head->parent_id=="4" || $head->parent_id=="3"){
                  if(!empty($row["credit"]) && $row["credit"]!="-"){
                     $pending_amount = $pending_amount1 + (float)$row["credit"];
                  }else if(!empty($row["debit"]) && $row["debit"]!="-"){
                     $pending_amount = $pending_amount1 - (float)$row["debit"];
                  }
               }
               
            //}               
         }else{
            if($head->parent_id=="1" || $head->parent_id=="2"){
               if(!empty($row["credit"]) && $row["credit"]!="-"){
                  $pending_amount = $penA - (float)$row["credit"];
               }else if(!empty($row["debit"]) && $row["debit"]!="-"){
                  $pending_amount = $penA + (float)$row["debit"];
               }  
            }else if($head->parent_id=="4" || $head->parent_id=="3"){
               if(!empty($row["credit"]) && $row["credit"]!="-"){
                  $pending_amount = $penA + (float)$row["credit"];
               }else if(!empty($row["debit"]) && $row["debit"]!="-"){
                  $pending_amount = $penA - (float)$row["debit"];
               }
            }
         }

         if($pending_amount<0){ 
            if($head->parent_id=="1" || $head->parent_id=="2"){
               $balance = str_replace("-","",$pending_amount).' Cr';
            }else{
               $balance = str_replace("-","",$pending_amount).' Dr';
            }
            
            $data[$key]->balance = $balance;
         }else{ 
            if($head->parent_id=="1" || $head->parent_id=="2"){
               $balance = $pending_amount.' Dr'; 
            }else{
               $balance = $pending_amount.' Cr'; 
            }
            
            $data[$key]->balance = $balance;
         }
         $penA = $pending_amount;
         $i++;
      }
      if($id==78){
         $account_total2 = 0;
         $account_type_parent21 = AccountTypeParent::where('id',3)->get();
         foreach ($account_type_parent21 as $key21 => $value21) {
            $account_type22 = AccountType::where('parent_id',$value21->id)->orderBy('sequence')->get();
            foreach ($account_type22 as $k22 => $v22) {
               $head23 = Head::where('account_type_id',$v22->id)->where('status',1)->orderBy('sequence')->get();
               $head_total2 = 0;
               foreach($head23 as $k23 => $v23){
                  $statement24 = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
                        ->effective()
                     ->where("fyear",$selected_year)
                     ->where('head_id',$v23->id)->get();
                     $head_total2 = $head_total2 + $statement24[0]->debitAmount - $statement24[0]->creditAmount;
               }
               $account_total2 = $account_total2 + $head_total2;
            }
         }
         // //Lia..
         $account_total1 = 0;
         $account_type_parent31 = AccountTypeParent::where('id',2)->get();
         foreach ($account_type_parent31 as $key31 => $value31){
            $account_type32 = AccountType::where('parent_id',$value31->id)->orderBy('sequence')->get();
            foreach ($account_type32 as $k32 => $v32) {
               $head33 = Head::where('account_type_id',$v32->id)->where('status',1)->get();
               $head_total123 = 0;
               foreach($head33 as $k33 => $v33){
                  $statement34 = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
                        ->effective()
                  ->where("fyear",$selected_year)
                     ->where('head_id',$v33->id)->get();
                  $head_total123 = $head_total123 + $statement34[0]->creditAmount - $statement34[0]->debitAmount;
               }
               $account_total1 = $account_total1 + $head_total123;
            }
         }
         $bb = abs($account_total2) - abs($account_total1);
         if($bb<0){
            $balance = $pending_amount + $bb;
            $rmk = "Loss for the year";
            $dt = abs($bb);
            $ct = "";
         }else{
            $balance = $pending_amount - $bb;
            $rmk = "Profit for the year";
            $dt = "";
            $ct = abs($bb);
         }
         $data =[];
        
         $fy_end_md = $entity->financial_year_end ? \Carbon\Carbon::parse($entity->financial_year_end) : \Carbon\Carbon::create(2000,3,31);
         $fy_end_year = preg_match('/(\d{4})\D*$/', (string)$selected_year, $mm) ? (int)$mm[1] : (int)$fy_end_md->format('Y');
         $fy_end_date = \Carbon\Carbon::create($fy_end_year, (int)$fy_end_md->format('n'), 1)->endOfMonth();
         if((int)$fy_end_md->format('j') < (int)$fy_end_date->format('j')){ $fy_end_date->day((int)$fy_end_md->format('j')); }
         $aa = json_decode('{"st_date":"'.$fy_end_date->format('d-m-Y').'","voucher_no":"","remark":"'.$rmk.'","debit":"'.$dt.'","credit":"'.$ct.'","balance":"'.$balance.'"}');
         array_push($data,$aa);
      }      
      $efy = EntityFinancialYear::where('merchant_id',Session::get('merchant_id'))->where('status',1)->pluck('financial_year')->all();
      $year_options = FinancialYears::options($efy, $selected_year, true);
      $year_is_closed = !FinancialYears::isOpen($selected_year);
      $year_is_reopened = FinancialYears::isReopened($selected_year);
      return view('account.ledger_by_head',["ledgers"=>$data,"open"=>$open,"name"=>$head->name,"head_id"=>$id,"year_options"=>$year_options,"selected_year"=>$selected_year,"year_is_closed"=>$year_is_closed,"year_is_reopened"=>$year_is_reopened]);
   }
   public function profitLoss(Request $request,$year = null){
      $entity = Entity::where('status',1)->where('merchant_id',Session::get('merchant_id'))->orderBy('id','desc')->first();
      $selected_year = $entity->financial_year;
      if($year!="" && $year!=null){
         $selected_year = $year;
      }
      $account_type_parent = AccountTypeParent::where('financial_statement_id',2)->orderBy('sequence')->get();
      foreach ($account_type_parent as $key => $value) {
         $account_total = 0;
         $account_type = AccountType::where('parent_id',$value->id)->orderBy('sequence')->get();
         foreach ($account_type as $k => $v) {
            $head = Head::where('account_type_id',$v->id)
                        ->where('status',1)
                        ->where(function($query){
                           $query->where('merchant',Session::get('merchant_id'));
                           $query->orWhereNull('merchant');
                        })
                        ->orderBy('sequence')
                        ->get();
            $head_total = 0;
            foreach($head as $k1 => $v1){
               $statement = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
                     ->effective()
                  ->where("opening",0)
                  ->where('merchant_id',Session::get('merchant_id'))
                  ->where("fyear",$selected_year)
                  ->where('head_id',$v1->id)->get();
                  $bal = $statement[0]->debitAmount - $statement[0]->creditAmount;                  
                  if($statement[0]->debitAmount - $statement[0]->creditAmount<0){
                     $head[$k1]->amount = str_replace("-","",number_format($statement[0]->debitAmount - $statement[0]->creditAmount,2))." CR";
                  }else{
                     $head[$k1]->amount = number_format($statement[0]->debitAmount - $statement[0]->creditAmount,2)." Dr";
                  }                  
                  $head_total = $head_total + $statement[0]->debitAmount - $statement[0]->creditAmount;
                  if($statement[0]->debitAmount==0 && $statement[0]->creditAmount==0){
                     unset($head[$k1]);
                  }
            }
            if($head_total<0){
               $account_type[$k]->head_total = str_replace("-","",number_format($head_total,2))." CR";
            }else{
               $account_type[$k]->head_total = number_format($head_total,2)." Dr";
            }
            $account_type[$k]->head = $head;
            $account_total = $account_total + $head_total;
         }
         if($account_total<0){
            $account_type_parent[$key]->account_total = str_replace("-","",number_format($account_total,2))." CR";
         }else{
            $account_type_parent[$key]->account_total = number_format($account_total,2)." Dr";
         }
         $account_type_parent[$key]->child = $account_type;
         if($value->id==3){
            $income = $account_total;
         }
         if($value->id==2){
            $expenses = $account_total;
         }
      }
      
      $pl = abs($income) - abs($expenses);
      
      $entity_financial_year = EntityFinancialYear::where('merchant_id',Session::get('merchant_id'))
                                                   ->where('status',1)
                                                   ->get();
      $show_closed = (bool)request('closed');
      $year_options = FinancialYears::options($entity_financial_year->pluck('financial_year')->all(), $selected_year, $show_closed);
      $year_is_closed = !FinancialYears::isOpen($selected_year);
      $year_is_reopened = FinancialYears::isReopened($selected_year);
      return view('account.profit_loss',["year_options"=>$year_options,"year_is_closed"=>$year_is_closed,"year_is_reopened"=>$year_is_reopened,"account_type_parents"=>$account_type_parent,"pl"=>$pl,"entity"=>$entity,"selected_year"=>$selected_year,"entity_financial_year"=>$entity_financial_year]);
   }
   public function trialBalance(Request $request,$year = null){
      $entity = Entity::where('status',1)->where('merchant_id',Session::get('merchant_id'))->orderBy('id','desc')->first();
      $selected_year = "";
      if($entity){
         $selected_year = $entity->financial_year;
      }
      
      if($year!="" && $year!=null){
         $selected_year = $year;
      }
      $from = $request->input('from');
      $head = Head::where('status',1)
                  ->where(function($query){
                     $query->where('merchant',Session::get('merchant_id'));
                     $query->orWhereNull('merchant');
                  })
                  ->orderBy('category')
                  ->orderBy('name')
                  ->get();
      $head_sum = Head::where('status',1)
                        ->where(function($query){
                           $query->where('merchant',Session::get('merchant_id'));
                           $query->orWhereNull('merchant');
                        })
                        ->sum('balance');
      $debit_total = 0;$credit_total = 0;$open_total = $head_sum;$close_total=0;
      foreach ($head as $key => $value){
         $open_statement = Statement::select('debit','credit')
         ->where('opening',1)
         ->where('fyear',$selected_year)
         ->where('status',1)
         ->where('merchant_id',Session::get('merchant_id'))
         ->where('head_id',$value->id)
         ->first();
         if($open_statement){
            if(!empty($open_statement->debit)){
               $head[$key]->open_bal = $open_statement->debit;
            }else{
               $head[$key]->open_bal = $open_statement->credit;
            }
         }else{
            $head[$key]->open_bal = 0;
         }                  
         
         if($from!=""){
            $form_date = date('d-m-Y',strtotime($from));
            $statement = DB::select("select sum(debit) as debitAmount,sum(credit) as creditAmount from statement where status=1 and opening=0 and fyear='".$selected_year."' and merchant_id='".Session::get('merchant_id')."' and head_id='".$value->id."' and STR_TO_DATE(st_date, '%d-%m-%Y') >= STR_TO_DATE('".$form_date."', '%d-%m-%Y')");
         }else{
            $statement = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
            ->where('opening',0)
               ->where('fyear',$selected_year)
               ->where('status',1)
               ->where('merchant_id',Session::get('merchant_id'))
               ->where('head_id',$value->id)->get(); 
         }
         $debitAmount = $statement['0']->debitAmount;
         $creditAmount = $statement['0']->creditAmount;               
         $head[$key]->debit = number_format($debitAmount,2);
         $head[$key]->credit = number_format($creditAmount,2);
         if(!empty($head[$key]->open_bal)){
            $balance = $head[$key]->open_bal + $debitAmount - $creditAmount;
         }else{
            $balance = $debitAmount - $creditAmount;
         }
         $close_total = $close_total + $balance;                 
         $head[$key]->balance = number_format($balance,2);      
         if($debitAmount!="" && $debitAmount!='-'){
            $debit_total = $debit_total + $debitAmount;
         }
         if($creditAmount!="" && $creditAmount!='-'){
            $credit_total = $credit_total + $creditAmount;
         }
         
      }
      $entity_financial_year = EntityFinancialYear::where('merchant_id',Session::get('merchant_id'))
         ->where('status',1)
         ->get();
      $entityname = "";$address = "";
      if($entity){
         $entityname = $entity->name;
         $address = $entity->address.",".$entity->city.",".$entity->state.",".$entity->pincode;
      }
      $show_closed = (bool)request('closed');
      $year_options = FinancialYears::options($entity_financial_year->pluck('financial_year')->all(), $selected_year, $show_closed);
      $year_is_closed = !FinancialYears::isOpen($selected_year);
      $year_is_reopened = FinancialYears::isReopened($selected_year);
      return view('account.trial_balance',["tb_difference"=>round($debit_total - $credit_total, 2),"year_options"=>$year_options,"year_is_closed"=>$year_is_closed,"year_is_reopened"=>$year_is_reopened,"heads"=>$head,"debit_total"=>number_format($debit_total,2),"credit_total"=>number_format($credit_total,2),"close_total"=>(float) $close_total,"open_total"=>number_format($open_total,2),"entity_name"=>$entityname,"address"=>$address,"entity_financial_year"=>$entity_financial_year,"selected_year"=>$selected_year,"entity"=>$entity]);
   }
   public function assignBulkHeadManually(Request $request){
      // Every line is handled on its own: the ones that can be assigned are assigned, and the others
      // are listed with the reason so only those need another try.
      $input = $request->all();
      $ids = array_filter(explode(",",$input['row_id'] ?? ''));
      if(empty($input['select_bulk_head']) || empty($ids)){
         return response()->json(['success'=>false,'data'=>'','message'=>'Choose lines and an account.']);
      }
      $done = 0; $failed = [];
      foreach($ids as $lineId){
         $line = Statement::where('id',$lineId)->where('merchant_id',Session::get('merchant_id'))->first();
         if(!$line){ $failed[] = ['id'=>$lineId,'st_date'=>'','remark'=>'(line not found)','reason'=>'The line no longer exists.']; continue; }
         $why = null;
         if((int)$line->status !== 0){ $why = 'Already categorised.'; }
         elseif(!FinancialYears::isOpen($line->fyear)){ $why = FinancialYears::message($line->fyear); }
         if($why){ $failed[] = ['id'=>$line->id,'st_date'=>$line->st_date,'remark'=>substr((string)$line->remark,0,40),'reason'=>$why]; continue; }
         try{
            \App\Support\LineCategoriser::set($line->id, $input['select_bulk_head'], 'person');
            $done++;
         }catch(\Throwable $e){
            $failed[] = ['id'=>$line->id,'st_date'=>$line->st_date,'remark'=>substr((string)$line->remark,0,40),'reason'=>'Could not be saved. Try again.'];
         }
      }
      return response()->json(['success'=>true,'data'=>'','done'=>$done,'failed'=>$failed,'message'=>$done.' assigned']);
   }
   public function voucherEntry(Request $request){
      $input = $request->all();
      $voucher_no = $input['voucher_no'];
      $statement = Statement::leftjoin("head","statement.head_id","=","head.id")
                              ->where('voucher_no',$voucher_no)
                              ->where('merchant_id',Session::get('merchant_id'))
                              ->get(['statement.*','head.name as head']);      
      $response = [
         'success' => true,
         'data'    => $statement,
         'message' => "Product Add Successfully.",
      ];
      return response()->json($response, 200);
      
   }
   public function closeBook(Request $request){
      $merchant_id = Session::get('merchant_id');
      $entity = Entity::where('merchant_id',$merchant_id)->first();
      // The next year starts the day after this one ends and lasts one year. This follows the
      // company's own dates, so it works for April-March (India), July-June (Australia),
      // or any other financial year. The label is built like the entity screen builds it.
      $next_start = \Carbon\Carbon::parse($entity->financial_year_end)->addDay();
      $next_end = $next_start->copy()->addYear()->subDay();
      $nstart = $next_start->format('Y-m-d');
      $nend = $next_end->format('Y-m-d');
      $nyear = (int)$next_start->format('Y');
      $new_year = $next_start->format('Y')."-".$next_end->format('Y');
      $closing_year = $entity->financial_year;

      // Do not create the opening balances twice.
      $already = Statement::where('merchant_id',$merchant_id)
                     ->where('fyear',$new_year)
                     ->where('opening',1)
                     ->exists();
      if($already){
         return response()->json([
            'success' => false,
            'data'    => "",
            'message' => "Opening balances for ".$new_year." already exist, so the book was not closed again.",
         ], 200);
      }

      // A year cannot be closed while anything in it is still uncategorised.
      $blockers = FinancialYears::closeBlockers($closing_year);
      if(count($blockers)){
         return response()->json([
            'success' => false,
            'data'    => "",
            'message' => "The book for ".$closing_year." cannot be closed yet:\n- ".implode("\n- ", array_column($blockers,'message')),
         ], 200);
      }

      // Balance Sheet heads carry their closing balance forward; Profit & Loss
      // heads start again from zero and their result goes to Retained Earnings.
      $bs_parents = AccountTypeParent::where('financial_statement_id',1)->pluck('id');
      $pl_parents = AccountTypeParent::where('financial_statement_id',2)->pluck('id');
      $bs_types = AccountType::whereIn('parent_id',$bs_parents)->pluck('id')->toArray();
      $pl_types = AccountType::whereIn('parent_id',$pl_parents)->pluck('id')->toArray();

      // Closing balance (debit - credit) of every head for the year being closed.
      // The opening row of the year is included. Blank or "-" amounts count as 0.
      $sums = DB::table('statement')
         ->where('merchant_id',$merchant_id)
         ->where('fyear',$closing_year)
         ->where('status',1)
         ->groupBy('head_id')
         ->select('head_id', DB::raw("SUM(CAST(NULLIF(NULLIF(REPLACE(debit,',',''),'-'),'') AS DECIMAL(15,2))) AS dr"), DB::raw("SUM(CAST(NULLIF(NULLIF(REPLACE(credit,',',''),'-'),'') AS DECIMAL(15,2))) AS cr"))
         ->get()
         ->keyBy('head_id');

      $heads = Head::where('status',1)
                  ->where(function($query) use ($merchant_id){
                     $query->where('merchant',$merchant_id);
                     $query->orWhereNull('merchant');
                  })->get();

      $net = [];          // head id => closing balance, debit positive
      $profit_loss = 0;   // loss positive, profit negative
      foreach ($heads as $head){
         $row = $sums->get((string)$head->id);
         $balance = $row ? ((float)$row->dr - (float)$row->cr) : 0;
         if(in_array($head->account_type_id,$bs_types)){
            $net[$head->id] = $balance;
         }else if(in_array($head->account_type_id,$pl_types)){
            $profit_loss += $balance;
         }
      }
      $retained = $heads->first(function($head) use ($bs_types){
         return $head->name == "Retained Earnings" && in_array($head->account_type_id,$bs_types);
      });
      if($retained){
         $net[$retained->id] = ($net[$retained->id] ?? 0) + $profit_loss;
      }

      DB::transaction(function() use ($net,$new_year,$nstart,$merchant_id,$entity,$nend,$nyear){
         foreach ($net as $head_id => $balance){
            $balance = round($balance,2);
            if(abs($balance) < 0.005){
               continue;
            }
            $addOpening = new Statement();
            $addOpening->debit = $balance > 0 ? $balance : "";
            $addOpening->credit = $balance < 0 ? abs($balance) : "";
            $addOpening->remark = "Opening Balance";
            $addOpening->head_id = $head_id;
            $addOpening->st_date = $nstart;
            $addOpening->fyear = $new_year;
            $addOpening->opening = 1;
            $addOpening->status = 1;
            $addOpening->merchant_id = $merchant_id;
            $addOpening->created_at = date('Y-m-d H:i:s');
            $addOpening->created_by = Session::get('id');
            $addOpening->save();
         }
         // Keep a record of the year that was closed (once).
         $recorded = EntityFinancialYear::where('merchant_id',$merchant_id)
                        ->where('financial_year',$entity->financial_year)
                        ->exists();
         if(!$recorded){
            $entityYear = new EntityFinancialYear;
            $entityYear->financial_year = $entity->financial_year;
            $entityYear->start_year = $entity->financial_year_start;
            $entityYear->end_year = $entity->financial_year_end;
            $entityYear->merchant_id = $merchant_id;
            $entityYear->save();
         }
         Entity::where('merchant_id',$merchant_id)
               ->update([
                 'financial_year' => $new_year,
                 'financial_year_start' => $nstart,
                 'financial_year_end' => $nend
               ]);
      });
      $response = [
         'success' => true,
         'data'    => "",
         'message' => "Book Closed Successfully.",
      ];
      return response()->json($response, 200);
   }
   public function manageBook(Request $request){  
      $entity = Entity::where('merchant_id',Session::get('merchant_id'))->first(); 
      $button_status = "0";
      if(strtotime($entity->financial_year_end)<strtotime(date('Y-m-d'))){
         $button_status = "1";
      }   
      return view('account.manage_close_book',["entity"=>$entity,"button_status"=>$button_status,"blockers"=>FinancialYears::closeBlockers($entity->financial_year),"warnings"=>FinancialYears::closeWarnings($entity->financial_year)]);
   }

}
