@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row">
    <div class="col-12">
        <h1 class="mb-2">Accounts</h1>
        <!-- Tab -->
        <style>
.fna-acc-tabs{border:0;gap:6px;flex-wrap:wrap}
.fna-acc-tabs .nav-link{border:1px solid var(--fna-field)!important;border-radius:999px!important;color:var(--fna-green)!important;font-weight:700;font-size:13px;padding:5px 14px;background:#fff}
.fna-acc-tabs .nav-link.active{background:var(--fna-green)!important;border-color:var(--fna-green)!important;color:#fff!important}
.fna-acc-tabs .cnt{font-size:11px;background:#E2EFE7;color:var(--fna-green);border-radius:999px;padding:0 7px;margin-left:4px}
.fna-acc-tabs .active .cnt{background:#fff}
</style>
<nav>
            <div class="nav nav-tabs fna-acc-tabs mb-3" id="nav-tab" role="tablist">
                <a class="nav-item nav-link active" id="nav-employee-tab" data-bs-toggle="tab" href="#nav-employee" role="tab" aria-controls="nav-employee" aria-selected="true">Employee <span class="cnt">{{ count($lists["employee"]) }}</span></a>
                <a class="nav-item nav-link" id="nav-customer-tab" data-bs-toggle="tab" href="#nav-customer" role="tab" aria-controls="nav-customer" aria-selected="false">Supplier <span class="cnt">{{ count($lists["supplier"]) }}</span></a>
                <a class="nav-item nav-link" id="nav-bank-tab" data-bs-toggle="tab" href="#nav-bank" role="tab" aria-controls="nav-bank" aria-selected="false">Bank <span class="cnt">{{ count($lists["bank"]) }}</span></a>
                <a class="nav-item nav-link" id="nav-rent-tab" data-bs-toggle="tab" href="#nav-rent" role="tab" aria-controls="nav-rent" aria-selected="false">Rent <span class="cnt">{{ count($lists["rent"]) }}</span></a>
                <a class="nav-item nav-link" id="nav-debtor-tab" data-bs-toggle="tab" href="#nav-debtor" role="tab" aria-controls="nav-debtor" aria-selected="false">Customers (Debtors) <span class="cnt">{{ count($lists["debtor"]) }}</span></a>
            </div>
        </nav>
        <div class="tab-content" id="nav-tabContent">
            <div class="tab-pane fade show active" id="nav-employee" role="tabpanel" aria-labelledby="nav-employee-tab">
<div class="fna-tablebox mt-2"><table class="table"><thead><tr><th>Name</th><th>PAN</th><th>Bank A/C</th><th>Category</th></tr></thead><tbody>@forelse($lists["employee"] as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->pan }}</td><td>{{ $r->account_no }}</td><td>{{ $r->category }}</td></tr>@empty<tr><td colspan="4" class="text-muted">Nothing added yet.</td></tr>@endforelse</tbody></table></div>
<div class="fw-bold mt-3" style="font-size:14px">Add new</div>
               <div class="card card-body border-0 shadow mb-4 mt-4">
                  <form action="add-head" method="post" id="employee_form_data" name="employee_form_data" action="javascript:void(0)">
                     @csrf
                     <div class="row">
                        <div class="col-md-3 mb-3">
                           <div>
                              <label for="employee_name">Name</label>
                              <input class="form-control" type="text" placeholder="Name" name="employee_name" id="employee_name" required>
                           </div>
                        </div>            
                        <div class="col-md-3 mb-3">
                           <div>
                              <label for="fname">Father's Name</label>
                              <input class="form-control" type="text" placeholder="Father's Name" name="fname" id="fname">
                           </div>
                           <ul style="color: red;">
                              @error('fname'){{$message}}@enderror                        
                           </ul>
                        </div>
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="dob">DOB</label>
                              <input class="form-control" type="date"  name="dob" id="dob">
                           </div>
                           <ul style="color: red;">
                              @error('dob'){{$message}}@enderror                        
                           </ul>
                        </div>
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="pan">PAN</label>
                              <input class="form-control" type="text" placeholder="PAN" name="pan" id="pan">
                           </div>
                           <ul style="color: red;">
                              @error('pan'){{$message}}@enderror                        
                           </ul>
                        </div>
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="dob">AADHAR</label>
                              <input class="form-control" type="text" placeholder="AADHAR" name="aadhar" id="aadhar">
                           </div>
                           <ul style="color: red;">
                              @error('aadhar'){{$message}}@enderror                        
                           </ul>
                        </div>
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="employee_account_no">Bank A/C No.</label>
                              <input class="form-control" type="text" placeholder="Bank A/C No."  name="employee_account_no" id="employee_account_no">
                           </div>
                           <ul style="color: red;">
                              @error('account_no'){{$message}}@enderror                        
                           </ul>
                        </div>
                        <div class="col-md-3 mb-3">
                           <div>
                              <label for="employee_bank_name">Bank Name</label>
                              <input class="form-control" type="text" placeholder="Bank Name"  name="employee_bank_name" id="employee_bank_name">
                           </div>
                           <ul style="color: red;">
                              @error('bank_name'){{$message}}@enderror                        
                           </ul>
                        </div>
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="employee_ifsc_code">IFSC CODE</label>
                              <input class="form-control" type="text" placeholder="IFSC CODE" name="employee_ifsc_code" id="employee_ifsc_code">
                           </div>
                           <ul style="color: red;">
                              @error('ifsc_code'){{$message}}@enderror                        
                           </ul>
                        </div>
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="pf_account">PF A/C</label>
                              <input class="form-control" type="text" placeholder="PF A/C"  name="pf_account" id="pf_account">
                           </div>
                           <ul style="color: red;">
                              @error('pf_account'){{$message}}@enderror                        
                           </ul>
                        </div>
                         <div class="col-md-3 mb-3">
                           <div>
                              <label for="employee_category">Category</label>
                              <select class="form-control" name="employee_category" id="employee_category">
                                 <option value="">Select Category</option>
                                 @foreach($categorys as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                 @endforeach
                              </select>
                           </div>
                           <ul style="color: red;">
                              @error('applicable_gst'){{$message}}@enderror                        
                           </ul>
                        </div>
                     </div>
                     <div class="mt-3">
                        <button class="btn btn-gray-800 mt-2 animate-up-2 save_employee" type="submit">Submit</button>
                        @if(session('success_msg'))
                           <span style="color:green">{{session('success_msg')}}</span>
                        @endif
                     </div>
                  </form>
               </div>
            </div>
            <div class="tab-pane fade" id="nav-customer" role="tabpanel" aria-labelledby="nav-customer-tab">
<div class="fna-tablebox mt-2"><table class="table"><thead><tr><th>Name</th><th>PAN</th><th>Category</th></tr></thead><tbody>@forelse($lists["supplier"] as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->pan }}</td><td>{{ $r->category }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Nothing added yet.</td></tr>@endforelse</tbody></table></div>
<div class="fw-bold mt-3" style="font-size:14px">Add new</div>
               <div class="card card-body border-0 shadow mb-4 mt-4">
               <form action="add-customer" method="post" id="supplier_form_data">
                  @csrf
                  <div class="row">
                     <!-- <div class="col-md-1 mb-3">
                        <div>
                           <label for="title">Title</label>
                           <input class="form-control" type="text" placeholder="Title"  name="title" id="title" required>
                        </div>
                     </div>  -->           
                     <div class="col-md-3 mb-3">
                        <div>
                           <label for="first_name">Name</label>
                           <input class="form-control" type="text" placeholder="Name" name="first_name" id="first_name">
                        </div>
                        <ul style="color: red;">
                           @error('first_name'){{$message}}@enderror                        
                        </ul>
                     </div>            
                     <!-- <div class="col-md-3 mb-3">
                        <div>
                           <label for="last_name">Last Name</label>
                           <input class="form-control" type="text" placeholder="Last Name" name="last_name" id="last_name">
                        </div>
                        <ul style="color: red;">
                           @error('last_name'){{$message}}@enderror                        
                        </ul>
                     </div>  --> 
                     <div class="col-md-3 mb-3">
                        <div>
                           <label for="email">Email</label>
                           <input class="form-control" type="email" placeholder="Email" name="email" id="email">
                        </div>
                        <ul style="color: red;">
                           @error('email'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-2 mb-3">
                        <div class="form-group">
                           <label for="phone">Phone</label>
                           <input class="form-control" type="text" placeholder="Phone" required name="phone" id="phone">
                        </div>
                        <ul style="color: red;">
                           @error('phone'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">
                           <label for="mobile">Mobile</label>
                           <input class="form-control" id="mobile" type="text" placeholder="Mobile" required name="mobile">
                        </div>
                        <ul style="color: red;">
                           @error('mobile'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">
                           <label for="email">Display Name As</label>
                           <input class="form-control" id="display_name" type="text" placeholder="Display Name" required name="display_name" >
                        </div>
                        <ul style="color: red;">
                           @error('display_name'){{$message}}@enderror                        
                        </ul>
                     </div>               
                     <div class="col-md-3 mb-3">
                        <div class="form-group">
                           <label for="email">GST Registration Type</label>
                           <select class="form-control" id="gst_type"  name="gst_type" >
                              <option value="">GST Registration Type </option>
                              <option value="Consumer">Consumer</option>
                           </select>
                        </div>
                        <ul style="color: red;">
                           @error('state'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">
                           <label for="gst">GSTIN</label>
                           <input class="form-control" id="gst" type="text" placeholder="GSTIN" required name="gst">
                        </div>
                        <ul style="color: red;">
                           @error('state'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="pan">Opening Balance</label>
                           <input class="form-control" type="text" placeholder="Opening Balance" name="supplier_opening_balance" id="supplier_opening_balance">
                        </div>                        
                     </div>
                     <div class="col-md-2 mb-3">
                        <div class="form-group">
                           <label for="phone">Type</label>
                           <select class="form-control" id="supplier_open_type" name="supplier_open_type">
                              <option value="">Select Type</option>
                              <option value="Credit">Credit</option>
                              <option value="Debit">Debit</option>
                           </select>
                        </div>
                     </div>
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="pan">As of</label>
                           <input class="form-control" type="date" name="supplier_opening_balance_date" id="supplier_opening_balance_date">
                        </div>                        
                     </div>
                     <div class="col-md-6 mb-3"><h5>Billing Address</h5></div>
                     <div class="col-md-6 mb-3"><h5>Shipping Address <input type="checkbox" id="shipp_check"> Same as billing address</h5> </div>
                     <div class="col-md-6 mb-3">
                        <div class="form-group">                     
                           <textarea class="form-control" id="billing_street" placeholder="Street" required name="billing_street"></textarea> 
                        </div>
                        <ul style="color: red;">
                           @error('billing_street'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-6 mb-3">
                        <div class="form-group">                     
                           <textarea class="form-control" id="shipping_street" placeholder="Street" required name="shipping_street" ></textarea> 
                        </div>
                        <ul style="color: red;">
                           @error('shipping_street'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">                     
                           <input type="text"  class="form-control" id="billing_city" type="text" placeholder="City/Town" required name="billing_city">
                        </div>
                        <ul style="color: red;">
                           @error('billing_city'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">                     
                           <input type="text"  class="form-control" id="billing_state" type="text" placeholder="State" required name="billing_state">
                        </div>
                        <ul style="color: red;">
                           @error('billing_state'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">                     
                           <input type="text"  class="form-control" id="shipping_city" type="text" placeholder="City/Town" required name="shipping_city">
                        </div>
                        <ul style="color: red;">
                           @error('shipping_city'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">                     
                           <input type="text"  class="form-control" id="shipping_state" type="text" placeholder="State" required name="shipping_state">
                        </div>
                        <ul style="color: red;">
                           @error('shipping_state'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">                     
                           <input type="text"  class="form-control" id="billing_pincode" type="text" placeholder="Pincode" required name="billing_pincode">
                        </div>
                        <ul style="color: red;">
                           @error('billing_pincode'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">                     
                           <input type="text"  class="form-control" id="billing_country" type="text" placeholder="Country" required name="billing_country">
                        </div>
                        <ul style="color: red;">
                           @error('billing_country'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">                     
                           <input type="text"  class="form-control" id="shipping_pincode" type="text" placeholder="Pincode" required name="shipping_pincode">
                        </div>
                        <ul style="color: red;">
                           @error('shipping_pincode'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-3 mb-3">
                        <div class="form-group">                     
                           <input type="text"  class="form-control" id="shipping_country" type="text" placeholder="Country" required name="shipping_country">
                        </div>
                        <ul style="color: red;">
                           @error('shipping_country'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="branch_name">TDS</label><br>
                           <input type="radio" name="supp_tds_status" class="supp_tds_status" value="Yes"> Yes
                           <input type="radio" name="supp_tds_status" class="supp_tds_status" value="No"> No
                        </div>                           
                     </div>
                     <div class="col-md-2 mb-3 supp_tds_rate_div" style="display:none">
                        <div>
                           <label for="branch_name">TDS Rate</label><br>
                           <select class="form-control" name="supp_tds_rate" id="supp_tds_rate">
                              <option value="">TDS Rate</option>
                              <option value="2">2%</option>
                              <option value="5">5%</option>
                              <option value="10">10%</option>
                              <option value="20">20%</option>
                           </select>
                        </div>
                     </div>
                      <div class="col-md-2 mb-3">
                        <div>
                           <label for="supplier_category">Category</label>
                           <select class="form-control" name="supplier_category" id="supplier_category">
                              <option value="">Select Category</option>
                              @foreach($categorys as $category)
                                 <option value="{{$category->id}}">{{$category->name}}</option>
                              @endforeach
                           </select>
                        </div>
                        <ul style="color: red;">
                           @error('applicable_gst'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <input type="hidden" name="customer_id" id="customer_id">
                  </div>
                  <div class="mt-3">
                     <button class="btn btn-gray-800 mt-2 animate-up-2 save_supplier" type="button">Save all</button>
                     @if(session('success_msg'))
                        <span style="color:green">{{session('success_msg')}}</span>
                     @endif
                  </div>
               </form>
            </div>
            </div>
            <div class="tab-pane fade" id="nav-bank" role="tabpanel" aria-labelledby="nav-bank-tab">
<div class="fna-tablebox mt-2"><table class="table"><thead><tr><th>Bank</th><th>Account no.</th><th>IFSC</th><th>Branch</th></tr></thead><tbody>@forelse($lists["bank"] as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->account_no }}</td><td>{{ $r->ifsc_code }}</td><td>{{ $r->branch_name }}</td></tr>@empty<tr><td colspan="4" class="text-muted">Nothing added yet.</td></tr>@endforelse</tbody></table></div>
<div class="fw-bold mt-3" style="font-size:14px">Add new</div>
               <div class="card card-body border-0 shadow mb-4 mt-4">
               <form action="add-head" method="post" id="bank_form_data">
                  @csrf
                  <div class="row">
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="name">Bank Name</label>
                           <input class="form-control" type="text" placeholder="Bank Name" name="bank_name" id="bank_name" required>
                        </div>
                        <ul style="color: red;">
                           @error('bank_name'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="bank_account_no">Bank A/C No.</label>
                           <input class="form-control" type="text" placeholder="Bank A/C No."  name="bank_account_no" id="bank_account_no">
                        </div>
                        <ul style="color: red;">
                           @error('account_no'){{$message}}@enderror                        
                        </ul>
                     </div>  
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="bank_ifsc_code">IFSC CODE</label>
                           <input class="form-control" type="text" placeholder="IFSC CODE" name="bank_ifsc_code" id="bank_ifsc_code">
                        </div>
                        <ul style="color: red;">
                           @error('bank_ifsc_code'){{$message}}@enderror
                        </ul>
                     </div>      
                     <div class="col-md-4 mb-3">
                        <div>
                           <label for="branch_name">Branch Name</label>
                           <input class="form-control" type="text" placeholder="Branch Name" name="branch_name" id="branch_name">
                        </div>
                        <ul style="color: red;">
                           @error('branch_name'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="branch_address">Branch Address</label>
                           <input class="form-control" type="text"  name="branch_address" id="branch_address" placeholder="Branch Address">
                        </div>
                        <ul style="color: red;">
                           @error('branch_address'){{$message}}@enderror                        
                        </ul>
                     </div>
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="pan">Pincode</label>
                           <input class="form-control" type="text" placeholder="Pincode" name="pincode" id="pincode">
                        </div>
                        <ul style="color: red;">
                           @error('pincode'){{$message}}@enderror                        
                        </ul>
                     </div> 
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="pan">Opening Balance</label>
                           <input class="form-control" type="text" placeholder="Opening Balance" name="bank_opening_balance" id="bank_opening_balance">
                        </div>                        
                     </div>
                     <div class="col-md-2 mb-3">
                        <div class="form-group">
                           <label for="phone">Type</label>
                           <select class="form-control" id="bank_open_type" name="bank_open_type">
                              <option value="">Select Type</option>
                              <option value="Credit">Credit</option>
                              <option value="Debit">Debit</option>
                           </select>
                        </div>
                     </div>
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="pan">As of</label>
                           <input class="form-control" type="date" name="bank_opening_balance_date" id="bank_opening_balance_date">
                        </div>                        
                     </div>                    
                     <div class="col-md-2 mb-3">
                        <div>
                           <label for="bank_category">Category</label>
                           <select class="form-control" name="bank_category" id="bank_category">
                              <option value="">Select Category</option>
                              @foreach($categorys as $category)
                                 <option value="{{$category->id}}">{{$category->name}}</option>
                              @endforeach
                           </select>
                        </div>
                        <ul style="color: red;">
                           @error('applicable_gst'){{$message}}@enderror                        
                        </ul>
                     </div>
                  </div>
                  <div class="mt-3">
                     <button class="btn btn-gray-800 mt-2 animate-up-2 save_bank" type="button">Submit</button>
                     @if(session('success_msg'))
                        <span style="color:green">{{session('success_msg')}}</span>
                     @endif
                  </div>
               </form>
            </div>
            </div>
            <div class="tab-pane fade" id="nav-rent" role="tabpanel" aria-labelledby="nav-rent-tab">
<div class="fna-tablebox mt-2"><table class="table"><thead><tr><th>Landlord</th><th>PAN</th><th>Property</th><th>TDS</th></tr></thead><tbody>@forelse($lists["rent"] as $r)<tr><td>{{ $r->name }}</td><td>{{ $r->pan }}</td><td>{{ $r->property_address }}</td><td>{{ $r->tds_status ? ($r->tds_rate."%") : "No" }}</td></tr>@empty<tr><td colspan="4" class="text-muted">Nothing added yet.</td></tr>@endforelse</tbody></table></div>
<div class="fw-bold mt-3" style="font-size:14px">Add new</div>
                  <div class="card card-body border-0 shadow mb-4 mt-4">
                  <form action="add-head" method="post" id="rent_form_data">
                     @csrf
                     <div class="row">
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="landlord_name">Landlord Name</label>
                              <input class="form-control" type="text" placeholder="Landlord Name" name="landlord_name" id="landlord_name" required>
                           </div>
                           <ul style="color: red;">
                              @error('landlord_name'){{$message}}@enderror                        
                           </ul>
                        </div>
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="landlord_pan">Landlord Pan</label>
                              <input class="form-control" type="text" placeholder="Landlord Pan"  name="landlord_pan" id="landlord_pan">
                           </div>
                           <ul style="color: red;">
                              @error('landlord_pan'){{$message}}@enderror                        
                           </ul>
                        </div>  
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="property_address">Property Address</label>
                              <input class="form-control" type="text" placeholder="Property Address" name="property_address" id="property_address">
                           </div>
                           <ul style="color: red;">
                              @error('property_address'){{$message}}@enderror
                           </ul>
                        </div>      
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="branch_name">TDS</label><br>
                              <input type="radio" name="tds_status" class="tds_status" value="Yes"> Yes
                              <input type="radio" name="tds_status" class="tds_status" value="No"> No
                           </div>                           
                        </div>
                        <div class="col-md-2 mb-3 tds_rate_div" style="display:none">
                           <div>
                              <label for="branch_name">TDS Rate</label><br>
                              <select class="form-control" name="tds_rate" id="tds_rate">
                                 <option value="">TDS Rate</option>
                                 <option value="2">2%</option>
                                 <option value="5">5%</option>
                                 <option value="10">10%</option>
                                 <option value="20">20%</option>
                              </select>
                           </div>
                        </div>                
                        <div class="col-md-2 mb-3">
                           <div>
                              <label for="rent_category">Category</label>
                              <select class="form-control" name="rent_category" id="rent_category">
                                 <option value="">Select Category</option>
                                 @foreach($categorys as $category)
                                    <option value="{{$category->id}}">{{$category->name}}</option>
                                 @endforeach
                              </select>
                              
                           </div>
                           <ul style="color: red;">
                              @error('applicable_gst'){{$message}}@enderror                        
                           </ul>
                        </div>
                     </div>
                     <div class="mt-3">
                        <button class="btn btn-gray-800 mt-2 animate-up-2 save_rent" type="button">Submit</button>
                        @if(session('success_msg'))
                           <span style="color:green">{{session('success_msg')}}</span>
                        @endif
                     </div>
                  </form>
               </div>
            </div>
            <div class="tab-pane fade" id="nav-debtor" role="tabpanel" aria-labelledby="nav-debtor-tab">
<div class="fna-tablebox mt-2"><table class="table"><thead><tr><th>Name</th><th>GSTIN</th><th>City</th></tr></thead><tbody>@forelse($lists["debtor"] as $r)<tr><td>{{ $r->display_name ?: trim($r->first_name." ".$r->last_name) }}</td><td>{{ $r->gst }}</td><td>{{ $r->billing_city }}</td></tr>@empty<tr><td colspan="3" class="text-muted">Nothing added yet.</td></tr>@endforelse</tbody></table></div>
<div class="fw-bold mt-3" style="font-size:14px">Add new</div>
                  <div class="card card-body border-0 shadow mb-4 mt-4">
                     <form action="add-customer" method="post" id="debtor_form_data">
                        @csrf
                        <div class="row">
                           <div class="col-md-1 mb-3">
                              <div>
                                 <label for="title">Title</label>
                                 <input class="form-control" type="text" placeholder="Title"  name="title" id="title" required>
                              </div>
                           </div>            
                           <div class="col-md-3 mb-3">
                              <div>
                                 <label for="first_name">First Name</label>
                                 <input class="form-control" type="text" placeholder="First Name" name="first_name" id="first_name">
                              </div>
                              <ul style="color: red;">
                                 @error('first_name'){{$message}}@enderror                        
                              </ul>
                           </div>            
                           <div class="col-md-3 mb-3">
                              <div>
                                 <label for="last_name">Last Name</label>
                                 <input class="form-control" type="text" placeholder="Last Name" name="last_name" id="last_name">
                              </div>
                              <ul style="color: red;">
                                 @error('last_name'){{$message}}@enderror                        
                              </ul>
                           </div>  
                           <div class="col-md-3 mb-3">
                              <div>
                                 <label for="email">Email</label>
                                 <input class="form-control" type="email" placeholder="Email" name="email" id="email">
                              </div>
                              <ul style="color: red;">
                                 @error('email'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-2 mb-3">
                              <div class="form-group">
                                 <label for="phone">Phone</label>
                                 <input class="form-control" type="text" placeholder="Phone" required name="phone" id="phone">
                              </div>
                              <ul style="color: red;">
                                 @error('phone'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">
                                 <label for="mobile">Mobile</label>
                                 <input class="form-control" id="mobile" type="text" placeholder="Mobile" required name="mobile">
                              </div>
                              <ul style="color: red;">
                                 @error('mobile'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">
                                 <label for="email">Display Name As</label>
                                 <input class="form-control" id="display_name" type="text" placeholder="Display Name" required name="display_name" >
                              </div>
                              <ul style="color: red;">
                                 @error('display_name'){{$message}}@enderror                        
                              </ul>
                           </div>               
                           <div class="col-md-3 mb-3">
                              <div class="form-group">
                                 <label for="email">GST Registration Type</label>
                                 <select class="form-control" id="gst_type"  name="gst_type" >
                                    <option value="">GST Registration Type </option>
                                    <option value="Consumer">Consumer</option>
                                 </select>
                              </div>
                              <ul style="color: red;">
                                 @error('state'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">
                                 <label for="gst">GSTIN</label>
                                 <input class="form-control" id="gst" type="text" placeholder="GSTIN" required name="gst">
                              </div>
                              <ul style="color: red;">
                                 @error('state'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-2 mb-3">
                              <div>
                                 <label for="pan">Opening Balance</label>
                                 <input class="form-control" type="text" placeholder="Opening Balance" name="customer_opening_balance" id="customer_opening_balance">
                              </div>                        
                           </div>
                           <div class="col-md-2 mb-3">
                              <div class="form-group">
                                 <label for="phone">Type</label>
                                 <select class="form-control" id="customer_open_type" name="customer_open_type">
                                    <option value="">Select Type</option>
                                    <option value="Credit">Credit</option>
                                    <option value="Debit">Debit</option>
                                 </select>
                              </div>
                           </div>
                           <div class="col-md-2 mb-3">
                              <div>
                                 <label for="pan">As of</label>
                                 <input class="form-control" type="date" name="customer_opening_balance_date" id="customer_opening_balance_date">
                              </div>                        
                           </div>
                           <div class="col-md-6 mb-3"><h5>Billing Address</h5></div>
                           <div class="col-md-6 mb-3"><h5>Shipping Address <input type="checkbox" id="shipp_check1"> Same as billing address</h5> </div>
                           <div class="col-md-6 mb-3">
                              <div class="form-group">                     
                                 <textarea class="form-control" id="billing_street1" placeholder="Street" required name="billing_street1"></textarea> 
                              </div>
                              <ul style="color: red;">
                                 @error('billing_street'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-6 mb-3">
                              <div class="form-group">                     
                                 <textarea class="form-control" id="shipping_street1" placeholder="Street" required name="shipping_street1" ></textarea> 
                              </div>
                              <ul style="color: red;">
                                 @error('shipping_street'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">                     
                                 <input type="text"  class="form-control" id="billing_city1" type="text" placeholder="City/Town" required name="billing_city1">
                              </div>
                              <ul style="color: red;">
                                 @error('billing_city'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">                     
                                 <input type="text"  class="form-control" id="billing_state1" type="text" placeholder="State" required name="billing_state1">
                              </div>
                              <ul style="color: red;">
                                 @error('billing_state'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">                     
                                 <input type="text"  class="form-control" id="shipping_city1" type="text" placeholder="City/Town" required name="shipping_city1">
                              </div>
                              <ul style="color: red;">
                                 @error('shipping_city'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">                     
                                 <input type="text"  class="form-control" id="shipping_state1" type="text" placeholder="State" required name="shipping_state1">
                              </div>
                              <ul style="color: red;">
                                 @error('shipping_state'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">                     
                                 <input type="text"  class="form-control" id="billing_pincode1" type="text" placeholder="Pincode" required name="billing_pincode1">
                              </div>
                              <ul style="color: red;">
                                 @error('billing_pincode'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">                     
                                 <input type="text"  class="form-control" id="billing_country1" type="text" placeholder="Country" required name="billing_country1">
                              </div>
                              <ul style="color: red;">
                                 @error('billing_country'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">                     
                                 <input type="text"  class="form-control" id="shipping_pincode1" type="text" placeholder="Pincode" required name="shipping_pincode1">
                              </div>
                              <ul style="color: red;">
                                 @error('shipping_pincode'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <div class="col-md-3 mb-3">
                              <div class="form-group">                     
                                 <input type="text"  class="form-control" id="shipping_country1" type="text" placeholder="Country" required name="shipping_country1">
                              </div>
                              <ul style="color: red;">
                                 @error('shipping_country'){{$message}}@enderror                        
                              </ul>
                           </div>
                            <div class="col-md-2 mb-3">
                              <div>
                                 <label for="employee_category">Category</label>
                                 <select class="form-control" name="category" id="category">
                                    <option value="">Select Category</option>
                                    @foreach($categorys as $category)
                                       <option value="{{$category->id}}">{{$category->name}}</option>
                                    @endforeach
                                 </select>
                              </div>
                              <ul style="color: red;">
                                 @error('category'){{$message}}@enderror                        
                              </ul>
                           </div>
                           <input type="hidden" name="customer_id" id="customer_id">
                        </div>
                        <div class="mt-3">
                           <button class="btn btn-gray-800 mt-2 animate-up-2 save_debtor" type="submit">Save all</button>
                           @if(session('success_msg'))
                              <span style="color:green">{{session('success_msg')}}</span>
                           @endif
                        </div>
                     </form>
                  </div>
               </div>
        </div>
        <!-- End of tab -->
    </div>
</div>

<link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
@endsection
@push('script-head')

@endpush
<style type="text/css">
   .error{
      color:red
   }
</style>
@push('script-js')
<script type="text/javascript">
   $(function () {      
      var table = $('.yajra-datatable').DataTable({
          processing: true,
          serverSide: true,
          ajax: "{{ route('head.list') }}",
          columns: [
              {data: 'DT_RowIndex', name: 'DT_RowIndex'},
              {data: 'name', name: 'name'},
              {data: 'description', name: 'description'},
              {data: 'account_name', name: 'account_name'},
              {data: 'applicable_gst', name: 'applicable_gst'},
              {data: 'tax_code', name: 'tax_code'},
              {
                  data: 'action', 
                  name: 'action', 
                  orderable: true, 
                  searchable: true
              },
          ]
      });
      
    });  
   $("#shipp_check").click(function(){
      if($(this).prop('checked')==true){
         $("#shipping_street").val($("#billing_street").val());
         $("#shipping_city").val($("#billing_city").val());
         $("#shipping_state").val($("#billing_state").val());
         $("#shipping_pincode").val($("#billing_pincode").val());
         $("#shipping_country").val($("#billing_country").val());
         
      }else{
         $("#shipping_street").val("");
         $("#shipping_city").val("");
         $("#shipping_state").val("");
         $("#shipping_pincode").val("");
         $("#shipping_country").val("");
      }
   });
   if($("#employee_form_data").length > 0) {
      $("#employee_form_data").validate({
         rules: {
            employee_name: {
               required: true,
               maxlength: 50
            },
            fname: {
               required: true,
               maxlength: 50
            },
            employee_category: {
               required: true,
            },
         },
         messages: {
            employee_name: {
               required: "Please enter name",
               maxlength: "Your name maxlength should be 50 characters long."
            },
            fname: {
               required: "Please enter father's name",
            },
            employee_category: {
               required: "Please select category",
            },
         },
         submitHandler: function(form) {
            $.ajaxSetup({
               headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               }
            });
            $('.save_employee').html('Please Wait...');
            $(".save_employee"). attr("disabled", true);
            $.ajax({
               url: "{{url('merchant/add-employee')}}",
               type: "POST",
               data: $('#employee_form_data').serialize(),
               success: function( response ) {
                  $('.save_employee').html('Submit');
                  $('.save_employee').attr("disabled", false);
                  alert(response.success);
                  document.getElementById("employee_form_data").reset();
               },
               error: function(response){
                  //alert(response.success);
               },
               complete: function(response){
                  $('.save_employee').html('Submit');
               }
            });
         }
      });
   }
   // $(".save_employee").click(function(e){
   //    e.preventDefault();
   //    var data = $('#employee_form_data').serialize();
   //    $.ajax({
   //       type: 'post',
   //       url: "add-employee",
   //       data: data,
   //       headers: {
   //          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
   //       },
   //       beforeSend: function(){
   //          $('.save_employee').html('....Please wait');
   //       },
   //       success: function(response){
   //          alert(response.success);
   //       },
   //       error: function(response){
   //          //alert(response.success);
   //       },
   //       complete: function(response){
   //          $('.save_employee').html('Submit');
   //       }
   //    });
   // });
   $(".save_supplier").click(function(e){
      e.preventDefault();
      var data = $('#supplier_form_data').serialize();
      $.ajax({
         type: 'post',
         url: "add-supplier",
         data: data,
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         beforeSend: function(){
            $('.save_supplier').html('....Please wait');
         },
         success: function(response){
            alert(response.success);
         },
         error: function(response){
            var msg = (response.responseJSON && response.responseJSON.errors) ? Object.values(response.responseJSON.errors)[0][0] : "The form was not saved. Please check the fields.";
            alert(msg);
         },
         complete: function(response){
            $('.save_supplier').html('Submit');
         }
      });
   });
   $(".save_bank").click(function(e){
      e.preventDefault();
      var data = $('#bank_form_data').serialize();
      $.ajax({
         type: 'post',
         url: "add-bank",
         data: data,
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         beforeSend: function(){
            $('.save_bank').html('....Please wait');
         },
         success: function(response){
            alert(response.success);
         },
         error: function(response){
            var msg = (response.responseJSON && response.responseJSON.errors) ? Object.values(response.responseJSON.errors)[0][0] : "The form was not saved. Please check the fields.";
            alert(msg);
         },
         complete: function(response){
            $('.save_bank').html('Submit');
         }
      });
   });
   $(".save_rent").click(function(e){
      e.preventDefault();
      var data = $('#rent_form_data').serialize();
      $.ajax({
         type: 'post',
         url: "add-rent",
         data: data,
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         beforeSend: function(){
            $('.save_rent').html('....Please wait');
         },
         success: function(response){
            alert(response.success);
         },
         error: function(response){
            var msg = (response.responseJSON && response.responseJSON.errors) ? Object.values(response.responseJSON.errors)[0][0] : "The form was not saved. Please check the fields.";
            alert(msg);
         },
         complete: function(response){
            $('.save_rent').html('Submit');
         }
      });
   });
   $(".save_debtor").click(function(e){
      e.preventDefault();
      var data = $('#debtor_form_data').serialize();
      $.ajax({
         type: 'post',
         url: "add-customer",
         data: data,
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         beforeSend: function(){
            $('.save_supplier').html('....Please wait');
         },
         success: function(response){
            alert(response.success);
         },
         error: function(response){
            var msg = (response.responseJSON && response.responseJSON.errors) ? Object.values(response.responseJSON.errors)[0][0] : "The form was not saved. Please check the fields.";
            alert(msg);
         },
         complete: function(response){
            $('.save_supplier').html('Submit');
         }
      });
   });
   $(".tds_status").click(function(){
      $("#tds_rate").val('');

      if($('input[name="tds_status"]:checked').val()=='Yes'){
         $(".tds_rate_div").show();
      }else{
         $(".tds_rate_div").hide();
      }
   });
   $(".supp_tds_status").click(function(){
      $("#supp_tds_rate").val('');

      if($('input[name="supp_tds_status"]:checked').val()=='Yes'){
         $(".supp_tds_rate_div").show();
      }else{
         $(".supp_tds_rate_div").hide();
      }
   });
   $("#check_gst").click(function(){
      if($(this).prop('checked')==true){
         $("#gst").attr('required',false);
      }else{
         $("#gst").attr('required',true);
      }
   });
   $("#shipp_check1").click(function(){
      if($(this).prop('checked')==true){
         $("#shipping_street1").val($("#billing_street1").val());
         $("#shipping_city1").val($("#billing_city1").val());
         $("#shipping_state1").val($("#billing_state1").val());
         $("#shipping_pincode1").val($("#billing_pincode1").val());
         $("#shipping_country1").val($("#billing_country1").val());        
      }else{
         $("#shipping_street1").val("");
         $("#shipping_city1").val("");
         $("#shipping_state1").val("");
         $("#shipping_pincode1").val("");
         $("#shipping_country1").val("");
      }
   });
</script>
@endpush