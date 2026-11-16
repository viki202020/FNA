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
         <h2 class="h5 mb-2">Product</h2>   <button class="btn btn-danger" data-msg="text" data-type="text" id="notifyTopRight" style="display: none;">Top right danger</button>                            
         <form action="" method="post" id="product_form_data" name="product_form_data">
            @csrf
            <div class="row">

               <div class="col-md-3 mb-3">
                  <div>
                     <label for="name">Type</label><br>
                     <input type="radio" name="type" class="type" value="Goods" checked> Goods
                     <input type="radio" name="type" class="type" value="Service"> Service
                  </div>
               </div>
               <div class="col-md-3 mb-3">
                  <div>
                     <label for="name">Name</label>
                     <input class="form-control" type="text" placeholder="Name" name="name" id="name">
                  </div>
               </div>
               <div class="col-md-4 mb-3">
                  <div>
                     <label for="description">Description</label>
                     <input class="form-control" type="text" placeholder="Enter Description" name="description" id="description">
                  </div>
               </div> 
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="hsn_code" id="hsn_title">HSN Code</label>
                     <input class="form-control" type="text" placeholder="Enter HSN Code" name="hsn_code" id="hsn_code">
                  </div>
               </div>
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="unit">Unit</label>
                     <select class="form-control" name="unit" id="unit">
                        <option value="">Select Unit</option>
                        @foreach($units as $unit)
                           <option value="{{$unit->id}}">{{$unit->name}}</option>
                        @endforeach
                     </select>
                  </div>
               </div>
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="price">Selling Price</label>
                     <input class="form-control" type="text" placeholder="Enter Price" name="price" id="price">
                  </div>
               </div>
               <div class="col-md-3 mb-3">
                  <div>
                     <label for="tax_preference">Tax Preference</label>
                     <select class="form-control" name="tax_preference" id="tax_preference">
                        <option value="">Select Tax Preference</option>
                        <option value="1">Taxable</option>
                        <option value="0">Non-Taxable</option>
                     </select>
                  </div>
               </div>
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="detail_type">Applicable Tax Rate</label>
                     <select class="form-control" name="tax_rate" id="tax_rate">
                        <option value="">Select Tax Rate</option>
                        <option value="0">0%</option>
                        <option value="5">5%</option>
                        <option value="10">10%</option>
                        <option value="12">12%</option>
                        <option value="18">18%</option>
                        <option value="28">28%</option>
                     </select>
                  </div>                  
               </div>
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="head">Account Head</label>
                     <select class="form-control" name="head" id="head">
                        <option value="">Select Head</option>
                        @foreach($heads as $head)
                           <option value="{{$head->id}}">{{$head->name}}</option>
                        @endforeach
                     </select>
                  </div>
               </div>
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="status">Status</label>
                     <select class="form-control" name="status" id="status">
                        <option value="1">Enable</option>
                        <option value="0">Disable</option>
                     </select>
                  </div>
               </div>
               <input type="hidden" name="product_id" id="head_id">
            </div>
            <div class="mt-3">
               <button class="btn btn-gray-800 mt-2 animate-up-2 save_product" type="submit">Submit</button>
            </div>
         </form>
      </div>      
   </div>
</div>
<!-- <div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4">
         <h2 class="mb-4">Head List</h2>
         <table class="table table-bordered yajra-datatable">
            <thead>
               <tr>
                  <th>No</th>
                  <th>Name</th>
                  <th>Description</th>
                  <th>Account Type</th>
                  <th>Applicable GST(%)</th>
                  <th>Default Tax Code</th>
                  <th>Action</th>
               </tr>
            </thead>
            <tbody>
            </tbody>
         </table>
      </div>
   </div>
</div> -->
<link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
<style type="text/css">
   .error{
      color:red
   }
</style>
@endsection
@push('script-head')
@endpush
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

   $(document).on("click",".edit_head",function(){
      $("#cover-spin").show();
      let id  = $(this).attr('data-id');
      let account_type  = $(this).attr('data-account_type');      
      let name  = $(this).attr('data-name');
      let description  = $(this).attr('data-description');
      let applicable_gst  = $(this).attr('data-applicable_gst');
      let tax_code  = $(this).attr('data-tax_code');
      let status  = $(this).attr('data-status');
      let balance  = $(this).attr('data-balance');
      let balance_date  = $(this).attr('data-balance_date');
      $("#name").val(name);
      $("#description").val(description);
      $("#account_type").val(account_type);
      $("#applicable_gst").val(applicable_gst);
      $("#tax_code").val(tax_code);
      $("#balance").val(balance);
      $("#date").val(balance_date);     
      $("#head_id").val(id);
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
      setTimeout($("#cover-spin").hide(), 5000);
      //$("#cover-spin").hide();
   });
   
   $(".type").change(function(){
      if($('input[name="type"]:checked').val()=="Goods"){
         $("#hsn_title").html('HSN Code');
         $("#hsn_code").attr("placeholder","Enter HSN Code");
      }else{
         $("#hsn_title").html('SAC');
         $("#hsn_code").attr("placeholder","Enter SAC");
      }
   });
   $("#tax_preference").change(function(){
      $("#tax_rate").attr('disabled',false);
      if($(this).val()==1){

      }else if($(this).val()==0){
         $("#tax_rate").val(0);
         $("#tax_rate").attr('disabled',true);
      }else{

      }
   });
   if($("#product_form_data").length > 0) {
      $("#product_form_data").validate({
         rules: {
            name: {
               required: true,
               maxlength: 50
            },
            unit: {
               required: true,
            },
            price: {
               required: true,
            },
         },
         messages: {
            name: {
               required: "Please enter name",
               maxlength: "Your name maxlength should be 50 characters long."
            },
            unit: {
               required: "Please select unit",
            },
            price: {
               required: "Please enter price",
            },
         },
         submitHandler: function(form) {
            $.ajaxSetup({
               headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               }
            });
            $('.save_product').html('Please Wait...');
            $(".save_product"). attr("disabled", true);
            $.ajax({
               url: "{{url('merchant/add-product')}}",
               type: "POST",
               data: $('#product_form_data').serialize(),
               success: function( response ) {
                  $('.save_product').html('Submit');
                  $('.save_product').attr("disabled", false);
                  $("#notifyTopRight").attr('data-msg',response.message);
                  if(response.success==true){
                     $("#notifyTopRight").attr('data-type','success');
                  }else{
                     $("#notifyTopRight").attr('data-type','error');
                  }
                  
                  $("#notifyTopRight").click();
                  document.getElementById("product_form_data").reset();
               },
               error: function(response){
                  $("#notifyTopRight").attr('data-msg','Internal Server Error');
                  $("#notifyTopRight").attr('data-type','error');
                  $("#notifyTopRight").click();
                  
                  $('.save_product').html('Submit');
                  $('.save_product').attr("disabled", false);
               }
            });
         }
      });
   }
   document.getElementById('notifyTopRight').addEventListener('click', function () {
      let type = $(this).attr('data-type');
      let msg = $(this).attr('data-msg');
      const notyf = new Notyf({
         position: {
            x: 'right',
            y: 'top',
         },
         types: [
            {
               type: 'error',
               background: '#FA5252',
               icon: {
                  className: 'fas fa-times',
                  tagName: 'span',
                  color: '#fff'
               },
               dismissible: false
            }
         ]
      });
      notyf.open({
         type: type,//error
         message: msg
      });
   });
</script>
@endpush