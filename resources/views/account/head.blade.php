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
</div>
<div class="fna-section-title">New account head</div>
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4">
         <h2 class="h5 mb-2">Account Head</h2>                               
         <form action="add-head" method="post">
            @csrf
            <div class="row">
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="name">Name</label>
                     <input class="form-control" type="text" placeholder="Head" name="name" id="name" required>
                  </div>
                  <ul style="color: red;">
                     @error('name'){{$message}}@enderror                        
                  </ul>
               </div>            
               <div class="col-md-4 mb-3">
                  <div>
                     <label for="description">Description</label>
                     <input class="form-control" type="text" placeholder="Description" name="description" id="description">
                  </div>
                  <ul style="color: red;">
                     @error('description'){{$message}}@enderror                        
                  </ul>
               </div>
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="last_name">Account Type</label>
                     <select class="form-control" name="account_type" id="account_type">
                        <option value="">Account Type</option>
                        @forEach($account_type as $type)
                        <option value="{{$type->id}}" data-parent_id="{{$type->parent_id}}">{{$type->name}}</option>
                        @endforeach
                     </select>
                  </div>
                  <ul style="color: red;">
                     @error('account_type'){{$message}}@enderror                        
                  </ul>
               </div>
                <div class="col-md-2 mb-3">
                  <div>
                     <label for="detail_type">Applicable GST Rate</label>
                     <select class="form-control" name="applicable_gst" id="applicable_gst">
                        <option value="2">2%</option>
                        <option value="5">5%</option>
                        <option value="10">10%</option>
                        <option value="12">12%</option>
                        <option value="18">18%</option>
                        <option value="28">28%</option>
                        <option value="NA">NA</option>
                     </select>
                  </div>
                  <ul style="color: red;">
                     @error('applicable_gst'){{$message}}@enderror                        
                  </ul>
               </div>
               <div class="col-md-2 mb-3">
                  <div>
                     <label for="tax_code">Default Tax Code</label>
                     <input class="form-control" type="text" placeholder="Tax Code" name="tax_code" id="tax_code">
                  </div>
                  <ul style="color: red;">
                     @error('tax_code'){{$message}}@enderror                        
                  </ul>
               </div>
               <div class="col-md-2 mb-3 open_div" style="display:none">
                  <div class="form-group">
                     <label for="phone">Opening Balance</label>
                     <input class="form-control" type="text" placeholder="Opening Balance" name="balance" id="balance">
                  </div>
                  <ul style="color: red;">
                     @error('balance'){{$message}}@enderror                        
                  </ul>
               </div>
               <div class="col-md-2 mb-3 open_type" style="display:none">
                  <div class="form-group">
                     <label for="phone">Type</label>
                     <select class="form-control" id="open_type" name="open_type">
                        <option value="">Select Type</option>
                        <option value="Credit">Credit</option>
                        <option value="Debit">Debit</option>
                     </select>
                  </div>
               </div>
               <div class="col-md-3 mb-3 as_of_div" style="display:none">
                  <div>
                     <label for="date">As of</label>
                     <input class="form-control" type="date" name="date" id="date" value="{{$entity}}" style="width: 65%" readonly>
                  </div>
                  <ul style="color: red;">
                     @error('date'){{$message}}@enderror                        
                  </ul>
               </div>
              <div class="col-md-6 mb-3">
                  <div class="form-group">
                     <label for="phone">Mandatory Fields</label><br>
                     <input type="checkbox" name="mandatory_category" id="mandatory_category" value="Category"> Category 
                     <input type="checkbox" name="mandatory_customer" id="mandatory_customer" value="Customer"> Customer 
                     <input type="checkbox" name="mandatory_supplier" id="mandatory_supplier" value="Supplier"> Supplier
                     <input type="checkbox" name="department_required" id="department_required" value="1"> Department
                  </div>
               </div>
               <input type="hidden" name="head_id" id="head_id">
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
      let balance_type  = $(this).attr('data-balance_type');
      if($(this).attr('data-parent_id')==1 || $(this).attr('data-parent_id')==4){
         $(".open_div").show();
         $(".as_of_div").show();
         $(".open_type").show();
         $("#balance").attr('required',true);
         $("#date").attr('required',true);
      }else{
         $(".open_div").hide();
         $(".as_of_div").hide();
         $(".open_type").hide();
         $("#balance").attr('required',false);
         $("#date").attr('required',false);
      }
      $("#balance").val(balance);
      $("#open_type").val(balance_type);
      $("#date").val(balance_date);
      if($(this).attr('data-mandatory_category')==1){
         $("#mandatory_category").prop('checked',true);
      }
      if($(this).attr('data-mandatory_customer')==1){
         $("#mandatory_customer").prop('checked',true);
      }
      $("#department_required").prop('checked', $(this).attr('data-department_required')==1);
      if($(this).attr('data-mandatory_supplier')==1){
         $("#mandatory_supplier").prop('checked',true);
      }
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
   $("#applicable_gst").change(function(){
      $("#tax_code").val($(this).val());
   });

   $("#account_type").change(function(){
      let parent_id = $('option:selected', this).attr('data-parent_id');
      if(parent_id=="1" || parent_id=="4"){
         $(".open_div").show();
         $(".as_of_div").show();
         $(".open_type").show();
         $("#balance").attr('required',true);
         $("#date").attr('required',true);
      }else{
         $(".open_div").hide();
         $(".as_of_div").hide();
         $(".open_type").hide();
         $("#balance").attr('required',false);
         $("#date").attr('required',false);
      }
   });
</script>
@endpush