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
class MerchantController extends Controller{
   public function index(){
      return view('dashboard.dashboard');
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
      return view('entity.entity',['entity_count'=>count($entity),"financial_year_start"=>$financial_year_start,"financial_year_end"=>$financial_year_end]);
   }
   public function login(Request $req){
      $user_name = $req->input('username');
      $password = $req->input('password');        
      $validated = $req->validate([
         'username' => 'required',
         'password' => 'required',
      ]);      
      $check_login = Merchant::select('*')
                     ->where([
                           ["status", "=", 1],
                           ["email", "=", $user_name],
                           ["password", "=", md5($password)]
                           ])
                     ->first();
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
      $gst = $req->input('gst');
      $name = $req->input('name');    
      $validated = $req->validate([
         'address' => 'required',        
         'state' => 'required',
         'city' => 'required',
         'pincode' => 'required',
         'mobile' => 'required',
         'email' => 'required',
         'name' => 'required'
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
               $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm edit_customer" data-id="'.$row->id.'" data-title="'.$row->title.'" data-first_name="'.$row->first_name.'" data-last_name="'.$row->last_name.'" data-email="'.$row->email.'" data-phone="'.$row->phone.'" data-mobile="'.$row->mobile.'" data-display_name="'.$row->display_name.'" data-gst_type="'.$row->gst_type.'" data-gst="'.$row->gst.'" data-billing_street="'.$row->billing_street.'" data-billing_city="'.$row->billing_city.'" data-billing_state="'.$row->billing_state.'" data-billing_pincode="'.$row->billing_pincode.'" data-billing_country="'.$row->billing_country.'" data-shipping_street="'.$row->shipping_street.'" data-shipping_city="'.$row->shipping_city.'" data-shipping_state="'.$row->shipping_state.'" data-shipping_pincode="'.$row->shipping_pincode.'" data-shipping_country="'.$row->shipping_country.'" data-category="'.$row->category_id.'">Edit</a>';
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
      $gst = $req->input('gst');
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
         'gst' => 'required'
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
         $customer->updated_at = date('d-m-Y H:i:s');
         $customer->updated_by = Session::get('id');
         $customer->save();               
         $req->session()->flash('success_msg','Customer Update Successfully');
      }else{
         $head = new Head;      
         $head->name = $first_name;
         $head->email = $email;
         $head->phone = $phone;       
         $head->mobile = $mobile;
         $head->display_name = $display_name;
         $head->gst_type = $gst_type;
         $head->gst = $gst;
         $head->billing_street = $billing_street;
         $head->billing_city = $billing_city;
         $head->billing_state = $billing_state;
         $head->billing_pincode = $billing_pincode;
         $head->billing_country = $billing_country;
         $head->shipping_street = $shipping_street;
         $head->shipping_city = $shipping_city;
         $head->shipping_state = $shipping_state;
         $head->shipping_pincode = $shipping_pincode;
         $head->shipping_country = $shipping_country;
         $head->tds_status = $req->input('supp_tds_status');
         $head->tds_rate = $req->input('supp_tds_rate');
         $head->merchant = Session::get('merchant_id');
         $head->created_at = date('d-m-Y H:i:s');
         $head->created_by = Session::get('id');
         $head->category = $req->input('supplier_category');
         $head->account_type_id = 18;     
         if($head->save()){
            return response(['success' => 'Supplier created successfully.']);
         }    
         
      }
            
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
               $actionBtn = '<a href="javascript:void(0)" class="edit btn btn-success btn-sm edit_head" data-id="'.$row->id.'" data-account_type="'.$row->account_type_id.'"  data-name="'.$row->name.'" data-description="'.$row->description.'" data-applicable_gst="'.$row->applicable_gst.'" data-tax_code="'.$row->tax_code.'" data-status="'.$row->status.'" data-balance="'.$row->balance.'" data-balance_date="'.$row->balance_date.'" data-balance_type="'.$row->balance_type.'" data-mandatory_category="'.$row->mandatory_category.'" data-mandatory_customer="'.$row->mandatory_customer.'" data-mandatory_supplier="'.$row->mandatory_supplier.'" data-parent_id="'.$row->parent_id.'">Edit</a>';
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
      Excel::import(new UsersImport($data), $req->file('csv_file')->store('temp'));
      return redirect('merchant/uncategorized-data');
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
                  ->where("statement.status",1)
                  ->where('head_id',$hid)
                  ->where("statement.opening",0)
                  ->where("statement.fyear",$selected_year)
                  ->where("statement.voucher_no",null)
                  ->orderBy("statement.opening","desc")
                  ->orderBy(DB::raw("STR_TO_DATE(statement.st_date, '%d-%m-%Y')"),"asc")
                  ->orderBy("statement.id","asc")
                  ->get(['statement.*', 'head.name']);
         foreach ($data as $key => $row){
            $statement = DB::select("select sum(debit) as debitAmount,sum(credit) as creditAmount from statement where fyear='".$selected_year."' and head_id='".$hid."' and merchant_id='".Session::get('merchant_id')."' and status=1 and opening=0 and STR_TO_DATE(st_date, '%d-%m-%Y') < STR_TO_DATE('".$row['st_date']."', '%d-%m-%Y')");
            $pending_amount1 = $statement['0']->debitAmount - $statement['0']->creditAmount;
            if($i==1){

               if(!empty($row["credit"]) && $row["credit"]!="-"){
                  if($bank){
                     $pending_amount = (float)$bank->debit + (float)$row["credit"];
                  }else{
                     $pending_amount = $row["credit"];
                  }
                  
               }else if(!empty($row["debit"]) && $row["debit"]!="-"){
                  if($bank){
                     $pending_amount = (float)$bank->debit - (float)$row["debit"];
                  }else{
                     $pending_amount = $row["debit"];
                  }
                  
               }
               
            }else{
               if(!empty($row["credit"]) && $row["credit"]!="-"){
                  $pending_amount = $penA + (float)$row["credit"];
                  // if(!empty($dr)){
                  //    $pending_amount = $penA + $dr;
                  // }else if(!empty($cr)){
                  //    $pending_amount = $penA - $cr;
                  // }                     
                  $dr = $row["credit"];
                  $cr = "";
               }else if(!empty($row["debit"]) && $row["debit"]!="-"){
                  $pending_amount = $penA - (float)$row["debit"];
                  // if(!empty($dr)){
                  //    $pending_amount = $penA + $dr;
                  // }else if(!empty($cr)){
                  //    $pending_amount = $penA - $cr;
                  // }                     
                  $cr = $row["debit"];
                  $dr = "";
               }
            }
            if($pending_amount<0){ 
               //$balance = str_replace("-","",$pending_amount).' Cr';
               $balance = $pending_amount;
               $data[$key]->balance = number_format($balance,2);
            }else{ 
               // $balance = $pending_amount.' Dr'; 
               $balance = $pending_amount; 
               $data[$key]->balance = number_format($balance,2);
            }
            $penA = $pending_amount;
            $i++;
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

      return view('account.statement',["bank"=>$bank_open,"bankname"=>$bankname]);
   }
   public function uncategorizedData(Request $request){
      if($request->ajax()){      
         $data = Statement::leftjoin('head', 'statement.head_id', '=', 'head.id')
                  ->where("merchant_id",Session::get('merchant_id'))
                  ->where("statement.status",0)
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
        
      $validated = $req->validate([
         'select_rule' => 'required'
      ]);
      $select_rule = $req->input('select_rule');    
      $row = Statement::select('statement.*')
               ->where("merchant_id",Session::get('merchant_id'))
               ->whereIn("statement.id",$req->input('apply_row_id'))
               ->where("statement.status",0)
               ->get();
     
      foreach ($row as $key => $value) {
         $debit_amount = "";$credit_amount = "";$status = 0;$head_id = "";
         if(!empty($value->debit) && $value->debit!='-'){
            $debit_amount = trim($value->debit);
         }else if(!empty($value->credit) && $value->credit!='-'){
            $credit_amount = trim($value->credit);
         }
         $rule = Rule::where('id',$select_rule)->first();
         if($rule->col_type=="Amount"){
            $cond1 = $rule->rule_condition;
            if($rule->rule_condition=="Exact"){
               $cond1 = "==";
            }
            if($rule->rule_for=="Debit"){
               switch($cond1){
                  case ">=":
                     if($debit_amount>=$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     } 
                     break;
                  case "<=":
                     if($debit_amount<=$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     }
                     break;
                  case "<":
                     if($debit_amount<$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     } 
                     break;
                  case ">":
                     if($debit_amount>$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     }
                     break;
                  case "==":
                     if($debit_amount==$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     } 
                     break;
               }           
            }else if($rule->rule_for=="Credit"){
               switch($cond1){
                  case ">=":
                     if($credit_amount>=$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     } 
                     break;
                  case "<=":
                     if($credit_amount<=$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     }
                     break;
                  case "<":
                     if($credit_amount<$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     } 
                     break;
                  case ">":
                     if($credit_amount>$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     }
                     break;
                  case "==":
                     if($credit_amount==$rule->content){
                        if($rule->rule_condition1!="Any"){
                           if($value->remark==$rule->content1){
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }else{
                           $status = 1;
                           $head_id = $rule->head_id;
                        }
                     } 
                     break;
               }
            }
         }else if($rule->col_type=="Description"){
            $cond1 = $rule->rule_condition;
            if($rule->rule_condition=="Exact"){
               if($rule->rule_for=="Debit"){
                  if($value->remark==$rule->content){
                     if($rule->rule_condition1!="Any"){
                        $cond2 = $rule->rule_condition1;
                        if($rule->rule_condition1 == "Exact"){
                           $cond2 = "==";
                        }
                        switch($cond2){
                           case ">=":
                              if($debit_amount>=$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                           case "<=":
                              if($debit_amount<=$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }
                              break;
                           case "<":
                              if($debit_amount<$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                           case ">":
                              if($debit_amount>$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }
                              break;
                           case "==":
                              if($debit_amount==$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                        }
                     }else{
                        $status = 1;
                        $head_id = $rule->head_id;
                     }
                  }
               }else if($rule->rule_for=="Credit"){
                 if($value->remark==$rule->content){
                     if($rule->rule_condition1!="Any"){
                        $cond2 = $rule->rule_condition1;
                        if($rule->rule_condition1 == "Exact"){
                           $cond2 = "==";
                        }
                        switch($cond2){
                           case ">=":
                              if($credit_amount>=$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                           case "<=":
                              if($credit_amount<=$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }
                              break;
                           case "<":
                              if($credit_amount<$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                           case ">":
                              if($credit_amount>$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }
                              break;
                           case "==":
                              if($credit_amount==$rule->content){
                                 if($rule->rule_condition1!="Any"){
                                    if($value->remark==$rule->content1){
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }else{
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              } 
                              break;
                        }
                     }else{
                        $status = 1;
                        $head_id = $rule->head_id;
                     }
                  }
               }
            }else{
               if($rule->rule_for=="Debit"){
                  if($rule->rule_condition1!="Any"){
                     $cond2 = $rule->rule_condition1;
                     if($rule->rule_condition1 == "Exact"){
                        $cond2 = "==";
                     }
                     switch($cond2){
                        case ">=":
                           if($debit_amount>=$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case "<=":
                           if($debit_amount<=$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "<":
                           if($debit_amount<$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case ">":
                           if($debit_amount>$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "==":
                           if($debit_amount==$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                     }
                  }else{
                     $check_status = 0;
                     if($rule->rule_condition=="Any"){
                        $content = strtoupper($rule->content);
                        $search = strtoupper($value->remark); 
                        if(Str::contains($search, $content)==true){
                           $check_status = 1;
                        }
                     }else if($rule->rule_condition=="Exact"){
                        if($value->remark==$rule->content){
                           $check_status = 1;
                        }
                     }
                     if($check_status==1){
                        if($rule->rule_condition1=="Any"){
                           $status = 1;
                           $head_id = $rule->head_id;
                        }else{
                           $cond2 = $rule->rule_condition1;
                           if($rule->rule_condition1 == "Exact"){
                              $cond2 = "==";
                           }
                           switch($cond2){
                              case ">=":
                                 if($debit_amount>=$rule->content1){   
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 } 
                                 break;
                              case "<=":
                                 if($debit_amount<=$rule->content1){ 
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                                 break;
                              case "<":
                                 if($debit_amount<$rule->content1){  
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 } 
                                 break;
                              case ">":
                                 if($debit_amount>$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                                 break;
                              case "==":
                                 if($debit_amount==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 } 
                                 break;
                           }
                        }
                     }
                  }
               }else if($rule->rule_for=="Credit"){

                  if($rule->rule_condition1!="Any"){
                     $cond2 = $rule->rule_condition1;
                     if($rule->rule_condition1 == "Exact"){
                        $cond2 = "==";
                     }
                     switch($cond2){
                        case ">=":
                           if($credit_amount>=$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case "<=":
                           if($credit_amount<=$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "<":
                           if($credit_amount<$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case ">":
                           if($credit_amount>$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "==":
                           if($credit_amount==$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                     }
                  }else{

                     $check_status = 0;
                     if($rule->rule_condition=="Any"){
                        $content = strtoupper($rule->content);
                        $search = strtoupper($value->remark);                        
                        if(Str::contains($search, $content)==true){
                           $check_status = 1;
                        }
                     }else if($rule->rule_condition=="Exact"){
                        if($value->remark==$rule->content){
                           $check_status = 1;
                        }
                     }
                     
                     if($check_status==1){
                        if($rule->rule_condition1=="Any"){
                           $status = 1;
                           $head_id = $rule->head_id;
                        }else{
                           $cond2 = $rule->rule_condition1;
                           if($rule->rule_condition1 == "Exact"){
                              $cond2 = "==";
                           }
                           switch($cond2){
                              case ">=":
                                 if($credit_amount>=$rule->content1){   
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 } 
                                 break;
                              case "<=":
                                 if($credit_amount<=$rule->content1){ 
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                                 break;
                              case "<":
                                 if($credit_amount<$rule->content1){  
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 } 
                                 break;
                              case ">":
                                 if($credit_amount>$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                                 break;
                              case "==":
                                 if($credit_amount==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 } 
                                 break;
                           }
                        }
                     }                     
                  }
               }            
            }         
         }
         $statement = Statement::find($value->id);
         $statement->head_id = $head_id;
         $statement->status = $status; 
         $statement->save();         
      }
      echo 1;
   }
   public function applyBulkRule(Request $req){
      // $select_rule = $req->input('select_rule');      
      // $validated = $req->validate([
      //    'select_rule' => 'required'
      // ]);
      $row = Statement::select('statement.*')
               ->where("merchant_id",Session::get('merchant_id'))
               ->get();
      foreach ($row as $key => $value) {
         $debit_amount = "";$credit_amount = "";$status = 0;$head_id = "";
         if(!empty($value->debit) && $value->debit!='-'){
            $debit_amount = trim($value->debit);
         }else if(!empty($value->credit) && $value->credit!='-'){
            $credit_amount = trim($value->credit);
         }
         //Rule Array
         $rules = Rule::select('*')->where('status',1)->get();
         if(count($rules)>0){
            foreach ($rules as $k => $rule) {
               if($rule->col_type=="Amount"){
                  $cond1 = $rule->rule_condition;
                  if($rule->rule_condition=="Exact"){
                     $cond1 = "==";
                  }
                  if($rule->rule_for=="Debit"){
                     switch($cond1){
                        case ">=":
                           if($debit_amount>=$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case "<=":
                           if($debit_amount<=$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "<":
                           if($debit_amount<$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case ">":
                           if($debit_amount>$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "==":
                           if($debit_amount==$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                     }           
                  }else if($rule->rule_for=="Credit"){
                     switch($cond1){
                        case ">=":
                           if($credit_amount>=$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case "<=":
                           if($credit_amount<=$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "<":
                           if($credit_amount<$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                        case ">":
                           if($credit_amount>$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           }
                           break;
                        case "==":
                           if($credit_amount==$rule->content){
                              if($rule->rule_condition1!="Any"){
                                 if($value->remark==$rule->content1){
                                    $status = 1;
                                    $head_id = $rule->head_id;
                                 }
                              }else{
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }
                           } 
                           break;
                     }
                  }
               }else if($rule->col_type=="Description"){
                  $cond1 = $rule->rule_condition;
                  if($rule->rule_condition=="Exact"){
                     if($rule->rule_for=="Debit"){
                        if($value->remark==$rule->content){
                           if($rule->rule_condition1!="Any"){
                              $cond2 = $rule->rule_condition1;
                              if($rule->rule_condition1 == "Exact"){
                                 $cond2 = "==";
                              }
                              switch($cond2){
                                 case ">=":
                                    if($debit_amount>=$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    } 
                                    break;
                                 case "<=":
                                    if($debit_amount<=$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }
                                    break;
                                 case "<":
                                    if($debit_amount<$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    } 
                                    break;
                                 case ">":
                                    if($debit_amount>$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }
                                    break;
                                 case "==":
                                    if($debit_amount==$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    } 
                                    break;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }
                     }else if($rule->rule_for=="Credit"){
                       if($value->remark==$rule->content){
                           if($rule->rule_condition1!="Any"){
                              $cond2 = $rule->rule_condition1;
                              if($rule->rule_condition1 == "Exact"){
                                 $cond2 = "==";
                              }
                              switch($cond2){
                                 case ">=":
                                    if($credit_amount>=$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    } 
                                    break;
                                 case "<=":
                                    if($credit_amount<=$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }
                                    break;
                                 case "<":
                                    if($credit_amount<$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    } 
                                    break;
                                 case ">":
                                    if($credit_amount>$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }
                                    break;
                                 case "==":
                                    if($credit_amount==$rule->content){
                                       if($rule->rule_condition1!="Any"){
                                          if($value->remark==$rule->content1){
                                             $status = 1;
                                             $head_id = $rule->head_id;
                                          }
                                       }else{
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    } 
                                    break;
                              }
                           }else{
                              $status = 1;
                              $head_id = $rule->head_id;
                           }
                        }
                     }
                  }else{
                     if($rule->rule_for=="Debit"){
                        if($rule->rule_condition1!="Any"){
                           $cond2 = $rule->rule_condition1;
                           if($rule->rule_condition1 == "Exact"){
                              $cond2 = "==";
                           }
                           switch($cond2){
                              case ">=":
                                 if($debit_amount>=$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                              case "<=":
                                 if($debit_amount<=$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }
                                 break;
                              case "<":
                                 if($debit_amount<$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                              case ">":
                                 if($debit_amount>$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }
                                 break;
                              case "==":
                                 if($debit_amount==$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                           }
                        }else{
                           $check_status = 0;
                           if($rule->rule_condition=="Any"){
                              $content = strtoupper($rule->content);
                              $search = strtoupper($value->remark);                        
                              if(Str::contains($search, $content)==true){
                                 $check_status = 1;
                              }
                           }else if($rule->rule_condition=="Exact"){
                              if($value->remark==$rule->content){
                                 $check_status = 1;
                              }
                           }
                           
                           if($check_status==1){
                              if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }else{
                                 $cond2 = $rule->rule_condition1;
                                 if($rule->rule_condition1 == "Exact"){
                                    $cond2 = "==";
                                 }
                                 switch($cond2){
                                    case ">=":
                                       if($debit_amount>=$rule->content1){   
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       } 
                                       break;
                                    case "<=":
                                       if($debit_amount<=$rule->content1){ 
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                       break;
                                    case "<":
                                       if($debit_amount<$rule->content1){  
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       } 
                                       break;
                                    case ">":
                                       if($credit_amount>$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                       break;
                                    case "==":
                                       if($debit_amount==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       } 
                                       break;
                                 }
                              }
                           } 
                        }
                     }else if($rule->rule_for=="Credit"){
                        if($rule->rule_condition1!="Any"){
                           $cond2 = $rule->rule_condition1;
                           if($rule->rule_condition1 == "Exact"){
                              $cond2 = "==";
                           }
                           switch($cond2){
                              case ">=":
                                 if($credit_amount>=$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                              case "<=":
                                 if($credit_amount<=$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }
                                 break;
                              case "<":
                                 if($credit_amount<$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                              case ">":
                                 if($credit_amount>$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 }
                                 break;
                              case "==":
                                 if($credit_amount==$rule->content){
                                    if($rule->rule_condition1!="Any"){
                                       if($value->remark==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                    }else{
                                       $status = 1;
                                       $head_id = $rule->head_id;
                                    }
                                 } 
                                 break;
                           }
                        }else{
                           $check_status = 0;
                           if($rule->rule_condition=="Any"){
                              $content = strtoupper($rule->content);
                              $search = strtoupper($value->remark);                        
                              if(Str::contains($search, $content)==true){
                                 $check_status = 1;
                              }
                           }else if($rule->rule_condition=="Exact"){
                              if($value->remark==$rule->content){
                                 $check_status = 1;
                              }
                           }
                           
                           if($check_status==1){
                              if($rule->rule_condition1=="Any"){
                                 $status = 1;
                                 $head_id = $rule->head_id;
                              }else{
                                 $cond2 = $rule->rule_condition1;
                                 if($rule->rule_condition1 == "Exact"){
                                    $cond2 = "==";
                                 }
                                 switch($cond2){
                                    case ">=":
                                       if($credit_amount>=$rule->content1){   
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       } 
                                       break;
                                    case "<=":
                                       if($credit_amount<=$rule->content1){ 
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                       break;
                                    case "<":
                                       if($credit_amount<$rule->content1){  
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       } 
                                       break;
                                    case ">":
                                       if($credit_amount>$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       }
                                       break;
                                    case "==":
                                       if($credit_amount==$rule->content1){
                                          $status = 1;
                                          $head_id = $rule->head_id;
                                       } 
                                       break;
                                 }
                              }
                           } 
                        }
                     }            
                  }         
               }
               if(!empty($head_id)){
                  $statement = Statement::find($value->id);
                  $statement->head_id = $head_id;
                  $statement->status = $status; 
                  $statement->save(); 
                  continue;
               }
            }
         }
         
         //End Rule Array
                 
      }
      echo 1;
   }
   public function assignHeadManually(Request $req){
      $input = $req->all();
      if(isset($input['voucher_row'])){
         foreach ($input['voucher_row'] as $key => $value){
            if($input['voucher_head'][$key]!=""){
               $statement = Statement::find($value);
               $statement->head_id = $input['voucher_head'][$key];
               $statement->status = 1;     
               $statement->updated_at = date('d-m-Y H:i:s');
               $statement->updated_by = Session::get('id');
               $statement->save(); 
            }
         }
         echo 1;
         exit();
      }
      $select_head = $input['select_head'];
      $manually_id = $input['manually_id'];
      $statement = Statement::find($manually_id);
      $statement->head_id = $select_head;
      $statement->status = 1;     
      $statement->updated_at = date('d-m-Y H:i:s');
      $statement->updated_by = Session::get('id');
      if($statement->save()){
         $credit_amount = $statement->credit;
         $debit_amount = $statement->debit;
         $addstatement = new Statement;
         $addstatement->head_id = $statement->bank_id;
         $addstatement->debit = $credit_amount;
         $addstatement->credit = $debit_amount;
         $addstatement->bank_id = $statement->bank_id;
         $addstatement->remark = $statement->remark;
         $addstatement->st_date = date('Y-m-d',strtotime($statement->st_date));
         $addstatement->status = 1;
         $addstatement->merchant_id = Session::get('id');
         $addstatement->created_at = date('d-m-Y H:i:s');
         $addstatement->created_by = Session::get('id');
         $addstatement->save();
         echo 1;
      }
      
   }
   public function ledgers(Request $request){
      if($request->ajax()){
         $data = Statement::leftjoin('head', 'statement.head_id', '=', 'head.id')
                           ->select(DB::raw("sum(debit-credit) as debitAmount,count(*) as cnt,head_id,head.name,(CASE WHEN sum(debit-credit)>'0' THEN 'Dr'  ELSE 'Cr' END) as amount_type"))
                           ->where("merchant_id",Session::get('merchant_id')) 
                           ->where("statement.status",1)
                           ->groupBy('statement.head_id')
                           ->orderBy('head.name')
                           ->get();    
         return Datatables::of($data)
         ->addIndexColumn()
         ->addColumn('hname', function($row){
               $actionBtn = '<a href="javascript:void(0)" class="btn btn-success btn-sm view_all" data-id="'.$row->head_id.'">'.$row->name.'</a>';
               return $actionBtn;
            }) 
         ->rawColumns(['hname'])    
         ->make(true);
      }
      
      return view('account.ledger');
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
      return view('account.account',['categorys'=>$category]);
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
      $gst = $req->input('gst');
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
      $head = new Head;      
      $head->name = $first_name;
      $head->email = $email;
      $head->phone = $phone;       
      $head->mobile = $mobile;
      $head->display_name = $display_name;
      $head->gst_type = $gst_type;
      $head->gst = $gst;
      $head->billing_street = $billing_street;
      $head->billing_city = $billing_city;
      $head->billing_state = $billing_state;
      $head->billing_pincode = $billing_pincode;
      $head->billing_country = $billing_country;
      $head->shipping_street = $shipping_street;
      $head->shipping_city = $shipping_city;
      $head->shipping_state = $shipping_state;
      $head->shipping_pincode = $shipping_pincode;
      $head->shipping_country = $shipping_country;
      $head->tds_status = $req->input('supp_tds_status');
      $head->tds_rate = $req->input('supp_tds_rate');
      $head->balance = $req->input('supplier_opening_balance');
      $head->balance_type = $req->input('supplier_open_type');
      $head->balance_date = $req->input('supplier_opening_balance_date');
      $head->merchant = Session::get('merchant_id');
      $head->created_at = date('d-m-Y H:i:s');
      $head->created_by = Session::get('id');
      $head->category = $req->input('supplier_category');
      $head->account_type_id = 18;
      $head->save();
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
      $head->tds_status = $input['tds_status'];
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
      return view('account.product',["units"=>$unit,"heads"=>$head]);
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
      Excel::import(new ExpencessCsvImport($data), $req->file('csv_file')->store('temp'));
      return back();
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
      }
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
         $account_type_parent[$key]->account_total = number_format($account_total,2);
         $account_type_parent[$key]->child = $account_type;
      }
      $entity_financial_year = EntityFinancialYear::where('merchant_id',Session::get('merchant_id'))
                                                   ->where('status',1)
                                                   ->get();
      
      return view('account.balance_sheet',["account_type_parents"=>$account_type_parent,"financial_year"=>$financial_year_end,"entity"=>$entity,"entity_financial_year"=>$entity_financial_year,"selected_year"=>$selected_year]);
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
        
         $aa = json_decode('{"st_date":"31-03-2023","voucher_no":"","remark":"'.$rmk.'","debit":"'.$dt.'","credit":"'.$ct.'","balance":"'.$balance.'"}');
         array_push($data,$aa);
      }      
      return view('account.ledger_by_head',["ledgers"=>$data,"open"=>$open,"name"=>$head->name]);
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
      return view('account.profit_loss',["account_type_parents"=>$account_type_parent,"pl"=>$pl,"entity"=>$entity,"selected_year"=>$selected_year,"entity_financial_year"=>$entity_financial_year]);
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
      return view('account.trial_balance',["heads"=>$head,"debit_total"=>number_format($debit_total,2),"credit_total"=>number_format($credit_total,2),"close_total"=>(float) $close_total,"open_total"=>number_format($open_total,2),"entity_name"=>$entityname,"address"=>$address,"entity_financial_year"=>$entity_financial_year,"selected_year"=>$selected_year,"entity"=>$entity]);
   }
   public function assignBulkHeadManually(Request $request){
      $input = $request->all();
      $id = explode(",",$input['row_id']);
      $update = Statement::whereIn('id', $id)->update(['head_id' => $input['select_bulk_head'],'status'=>1]);
      if($update){
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
      $input = $request->all();
      $entity = Entity::where('merchant_id',Session::get('merchant_id'))->first();
      $year = explode("-",$entity->financial_year_end);
      $nyear = $year[0];
      $nstart = $nyear."-04-01";     
      $nend = $nyear+1;
      $nend.="-03-31";
      $neyear = $nyear + 1;
      $head = Head::where('status',1)->get();
      $selected_year = $entity->financial_year;  
      foreach ($head as $key => $value){
         if($value->balance_type=="Credit"){
            $head[$key]->open_bal = -$value->balance;
         }else{
            $head[$key]->open_bal = $value->balance;
         }   
         $debit_amount = "";
         $credit_amount = "";                 
         if($value->id==24){
            $account_type_parent = AccountTypeParent::where('financial_statement_id',1)->get();
            $head_arr = [];
            foreach ($account_type_parent as $key1 => $value1) {
               $account_type1 = AccountType::where('parent_id',$value1->id)->get();
               foreach ($account_type1 as $key2 => $value2) {
                  $head1 = Head::where('account_type_id',$value2->id)->where('status',1)->get();
                  foreach ($head1 as $key3 => $value3){
                     array_push($head_arr,$value3->id);
                  }
               }
            }
            $openstatement = Statement::select('debit','credit')
                                 ->where("fyear",$selected_year)
                                 ->where("opening",1)
                                 ->where('head_id',24)
                                 ->where('status',1)
                                 ->first();
            $statement = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
                  ->where("fyear",$selected_year)
                  ->where("opening",0)
                  ->where('status',1)
                  ->get();
                  $debitAmount = $statement[0]->debitAmount;
                  $creditAmount = $statement[0]->creditAmount;
                  if($openstatement){
                     $debitAmount = $debitAmount - $openstatement->debit;
                  }  
            $balance = $debitAmount - $creditAmount;    
            if($balance>0){
               $credit_amount = round($balance,2);
            }else{
               $debit_amount = abs(round($balance,2));
            }           
         }else{
            $statement = Statement::select(DB::raw("sum(debit) as debitAmount,sum(credit) as creditAmount"))
            ->where("fyear",$selected_year)
                  ->where('status',1)
                  ->where('head_id',$value->id)->get();               
            $debitAmount = $statement['0']->debitAmount;
            $creditAmount = $statement['0']->creditAmount;
            $balance = $debitAmount - $creditAmount;   
            if($balance>0){
               $debit_amount = round($balance,2);
            }else{
               $credit_amount = abs(round($balance,2));
            }         
         }
         
         
         
         $addOpening = new Statement();
         $addOpening->debit = $debit_amount;
         $addOpening->credit = $credit_amount;
         $addOpening->remark = "Opening Balance";
         $addOpening->head_id = $value->id;
         $addOpening->st_date = $nstart;
         $addOpening->fyear = $nyear."-".$neyear;
         $addOpening->opening = 1;
         $addOpening->status = 1;
         $addOpening->merchant_id = Session::get('merchant_id');
         $addOpening->created_at = date('d-m-Y H:i:s');
         $addOpening->created_by = Session::get('id');
         $addOpening->save();
      }
      
      $entityYear = new EntityFinancialYear;
      $entityYear->financial_year = $entity->financial_year;
      $entityYear->start_year = $entity->financial_year_start;
      $entityYear->end_year = $entity->financial_year_end;
      $entityYear->merchant_id = Session::get('merchant_id');
      if($entityYear->save()){
         
         Entity::where('merchant_id',Session::get('merchant_id'))
               ->update([
                 'financial_year' => $nyear."-".$neyear,
                 'financial_year_start' => $nstart, 
                 'financial_year_end' => $nend
               ]);
         
      }
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
      return view('account.manage_close_book',["entity"=>$entity,"button_status"=>$button_status]);
   }

}
