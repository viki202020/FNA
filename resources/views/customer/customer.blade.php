
@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4">
         <h2 class="mb-4">Customer List</h2>
         <table class="table table-bordered yajra-datatable">
            <thead>
               <tr>
                  <th>No</th>
                  <th>Name</th>
                  <th>Mobile</th>
                  <th>GSTIN</th>
                  <th>GST Registration Type</th>
                  <th>Action</th>
               </tr>
            </thead>
            <tbody>
            </tbody>
         </table>
      </div>
   </div>
</div>
<div class="fna-section-title">New customer</div>
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4">
         <h2 class="h5 mb-2">Customer Information</h2>                               
         <form action="add-customer" method="post">
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
                     <input class="form-control" id="gst" type="text" placeholder="GSTIN / ABN" required name="gst">
                  </div>
                  <ul style="color: red;">
                     @error('gst'){{$message}}@enderror                        
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
               <button class="btn btn-gray-800 mt-2 animate-up-2" type="submit">Save all</button>
               @if(session('success_msg'))
                  <span style="color:green">{{session('success_msg')}}</span>
               @endif
            </div>
         </form>
      </div>      
   </div>
</div>

<link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

@endsection
@push('script-head')
@endpush
@push('script-js')
<script type="text/javascript">
   $(document).ready(function(){
      $("#check_gst").click(function(){
         if($(this).prop('checked')==true){
            $("#gst").attr('required',false);
         }else{
            $("#gst").attr('required',true);
         }
      });
      $("#shipp_check").click(function(){
         if($(this).prop('checked')==true){
            $("#shipping_street").val($("#billing_street").val());
            $("#shipping_city").val($("#billing_city").val());
            $("#shipping_state").val($("#billing_state").val());
            $("#shipping_pincode").val($("#billing_pincode").val());
            $("#shipping_country").val($("#billing_country").val());
            // $("#shipping_street").attr("disabled",true);
            // $("#shipping_city").attr("disabled",true);
            // $("#shipping_state").attr("disabled",true);
            // $("#shipping_pincode").attr("disabled",true);
            // $("#shipping_country").attr("disabled",true);
         }else{
            $("#shipping_street").val("");
            $("#shipping_city").val("");
            $("#shipping_state").val("");
            $("#shipping_pincode").val("");
            $("#shipping_country").val("");
            // $("#shipping_street").attr("disabled",false);
            // $("#shipping_city").attr("disabled",false);
            // $("#shipping_state").attr("disabled",false);
            // $("#shipping_pincode").attr("disabled",false);
            // $("#shipping_country").attr("disabled",false);
         }
      });
   });
   $(function () {      
      var table = $('.yajra-datatable').DataTable({
          processing: true,
          serverSide: true,
          ajax: "{{ route('customers.list') }}",
          columns: [
              {data: 'DT_RowIndex', name: 'DT_RowIndex'},
              {data: 'first_name', name: 'first_name'},
              {data: 'mobile', name: 'mobile'},
              {data: 'gst', name: 'gst'},
              {data: 'gst_type', name: 'gst_type'},
              {
                  data: 'action', 
                  name: 'action', 
                  orderable: true, 
                  searchable: true
              },
          ]
      });
      
    });

   $(document).on("click",".edit_customer",function(){
      $("#cover-spin").show();
      let id  = $(this).attr('data-id');
      let title  = $(this).attr('data-title');
      let first_name  = $(this).attr('data-first_name');
      let last_name  = $(this).attr('data-last_name');
      let email  = $(this).attr('data-email');
      let phone  = $(this).attr('data-phone');
      let mobile  = $(this).attr('data-mobile');
      let display_name  = $(this).attr('data-display_name');
      let gst_type  = $(this).attr('data-gst_type');
      let gst  = $(this).attr('data-gst');
      let billing_street  = $(this).attr('data-billing_street');
      let billing_city  = $(this).attr('data-billing_city');
      let billing_state  = $(this).attr('data-billing_state');
      let billing_pincode  = $(this).attr('data-billing_pincode');
      let billing_country  = $(this).attr('data-billing_country');
      let shipping_street  = $(this).attr('data-shipping_street');
      let shipping_city  = $(this).attr('data-shipping_city');
      let shipping_state  = $(this).attr('data-shipping_state');
      let shipping_pincode  = $(this).attr('data-shipping_pincode');
      let shipping_country  = $(this).attr('data-shipping_country');
      let category  = $(this).attr('data-category');
      $("#title").val(title);
      $("#first_name").val(first_name);
      $("#last_name").val(last_name);
      $("#email").val(email);
      $("#phone").val(phone);
      $("#mobile").val(mobile);
      $("#display_name").val(display_name);
      $("#gst_type").val(gst_type);
      $("#gst").val(gst);
      $("#billing_street").val(billing_street);
      $("#billing_city").val(billing_city);
      $("#billing_state").val(billing_state);
      $("#billing_pincode").val(billing_pincode);
      $("#billing_country").val(billing_country);
      $("#shipping_street").val(shipping_street);
      $("#shipping_city").val(shipping_city);
      $("#shipping_state").val(shipping_state);
      $("#shipping_pincode").val(shipping_pincode);
      $("#shipping_country").val(shipping_country);
      $("#category").val(category);
      $("#customer_id").val(id);
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
      setTimeout($("#cover-spin").hide(), 5000);
      //$("#cover-spin").hide();
   });
</script>
@endpush