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
         <h2 class="mb-4">Rule List</h2>
         <table class="table table-bordered yajra-datatable">
            <thead>
               <tr>
                  <th>No</th>
                  <th>Rule Name</th>
                  <th>Type</th>
                  <th>Condition</th>
                  <th>Content</th>
                  <th>Debit/Credit</th>
                  <th>Head</th>
                  <th>Action</th>
               </tr>
            </thead>
            <tbody>
            </tbody>
         </table>
      </div>
   </div>
</div>
<div class="fna-section-title">New rule</div>
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4">
         <h2 class="h5 mb-2">Account Rules</h2>                               
         <form action="add-rule" method="post">
            @csrf
            <div class="row">
               <div class="col-md-12 mb-3">
                     Create a rule called
                     <input class="form-control" type="text" placeholder="Rule Name" name="rule_name[]" id="rule_name" required style="display: initial;width: 15%;"> for <select class="form-control" style="display: initial;width: 20%;" name="rule_for[]" id="rule_for" required><option value="">Select For</option>
                        <option value="Credit">Money coming in (Credit)</option>
                        <option value="Debit">Money going out (Debit)</option>
                     </select>
                     in <select class="form-control" style="display: initial;width: 20%;" name="rule_in[]" id="rule_in" required><option value="">Select In</option>
                        <option value="ANY">Any bank</option>
                        @foreach($banks as $bank)
                           <option value="{{$bank->id}}">{{$bank->name}}</option>
                        @endforeach
                        <option value="JV">Journal Voucher</option>
                     </select>
               </div><br>            
               <div class="col-md-12 mb-3">
                  Where the first thing checked is the
                  <select name="col_type[]" id="col_type_1" required class="form-control col_type" style="display: initial;width: 12%;" data-id="1">
                     <option value="Amount">Amount</option>
                     <option value="Description">Description</option>
                  </select>
                  <!-- <select  name="col_type[]" id="col_type_1" required class="form-control col_type" style="display: initial;width: 11%;" data-id="1">
                     
                     <option value="Amount">Amount</option>
                  </select>  -->
                  <span id="ftext">is</span> 
                  <select name="rule_condition[]" id="rule_condition_1" class="form-control rule_condition" data-id="1" required style="display: initial;width: 12%;">
                     <option value="">Select</option>
                     
                  </select> 
                  <input type="text"  name="content[]" id="content_1" class="form-control contents" style="display: initial;width: 20%;" data-id="1" required> 
                  and the <strong id="second_label" style="color:red">Description</strong>
                  <input type="hidden" name="col_type2[]" id="col_type2_1" required class="form-control col_type2" data-id="1" style="display: initial;width: 11%;    background-color: white;" readonly>
                  <!-- <select  name="col_type2[]" id="col_type2_1" required class="form-control col_type2" data-id="1" style="display: initial;width: 11%;">
                     <option value="">Select</option>
                     
                  </select>  -->
                  <span id="stext">contains</span> 
                  <select  name="rule_condition2[]" id="rule_condition2_1" required class="form-control rule_condition2" data-id="1" style="display: initial;width: 12%;">
                     <option value="">Select</option>                     
                  </select> <br><br>
                  <input type="text"  name="content2[]" id="content2_1" class="form-control content2" data-id="1" style="display: initial;width: 20%;"> 
                  Then <strong id="ac_type"></strong>
                  <input type="hidden" name="rule_action_type[]" id="rule_action_type_1" required class="form-control rule_action_type" data-id="1" style="display: initial;width: 12%;background-color: white;" readonly> 
                  <!-- <select  name="rule_action_type[]" id="rule_action_type_1" required class="form-control rule_action_type" data-id="1" style="display: initial;width: 12%;">
                     <option value="">Debit/Credit</option>
                     <option value="Debit">Debit</option>
                     <option value="Credit">Credit</option>
                  </select>  -->
                  to account head
                  <select  name="head[]" id="head_1" required class="form-control head" style="display: initial;width: 12%;" data-id="1">
                     <option value="">Select</option>
                     <option value="New">Create New</option>                     
                  </select>
                  <select name="category[]" id="category"  class="form-control category" style="display: none;width: 15%;">
                     <option value="">Select Category</option>                     
                     @foreach($categorys as $category)
                        <option value="{{$category->id}}">{{$category->name}}</option>
                     @endforeach              
                  </select>
                  <select name="customer[]" id="customer"  class="form-control customer" style="display: none;width: 15%;">
                     <option value="">Select customer</option>                     
                     @foreach($customers as $customer)
                        <option value="{{$customer->id}}">{{$customer->first_name}} {{$customer->last_name}}</option>
                     @endforeach              
                  </select>
                  <br><br>
                  <select name="supplier[]" id="supplier"  class="form-control supplier" style="display: none;width: 15%;">
                     <option value="">Select supplier</option>                     
                     @foreach($suppliers as $supplier)
                        <option value="{{$supplier->id}}">{{$supplier->first_name}}</option>
                     @endforeach              
                  </select>
               </div>                
               <input type="hidden" name="head_id" id="head_id">
            </div>
            <div class="mt-3">
               <label for="rule_status"><strong>Rule is</strong></label>
               <select name="rule_status" id="rule_status" class="form-control" style="display:inline-block;width:auto;margin-left:8px;">
                  <option value="1">Active</option>
                  <option value="0">Switched off</option>
               </select>
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
<div class="modal fade" id="modal-form-signup" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               <div class="text-center text-md-center mb-4 mt-md-0">
                  <h1 class="mb-0 h4">Add Head</h1>
               </div> 
               <form id="idForm">              
               <div class="form-group mb-4">
                   <label for="name">Name</label>
                   <div class="input-group">
                       <input type="text" class="form-control" placeholder="Head Name" id="name" required name="name">
                   </div>  
               </div>
               <div class="form-group mb-4">
                   <label for="description">Description</label>
                   <div class="input-group">
                      <input class="form-control" type="text" placeholder="Description" name="description" id="description">
                   </div>  
               </div>
               <div class="form-group mb-4">
                   <label for="name">Account Type</label>
                   <div class="input-group">
                      <select class="form-control" name="account_type" id="account_type">
                        <option value="">Account Type</option>
                        
                     </select>
                   </div>  
               </div>
               <div class="form-group mb-4">
                   <label for="name">Applicable GST Rate</label>
                   <div class="input-group">
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
               </div>
               <div class="form-group mb-4">
                   <label for="name">Default Tax Code</label>
                   <div class="input-group">
                       <input class="form-control" type="text" placeholder="Tax Code" name="tax_code" id="tax_code">
                   </div>  
               </div>
               <div class="form-group mb-4">
                   <label for="name">Balance</label>
                   <div class="input-group">
                       <input class="form-control" type="text" placeholder="Balance" name="balance" id="balance">
                   </div>  
               </div>
               <div class="form-group mb-4">
                   <label for="name">As of</label>
                   <div class="input-group">
                       <input class="form-control" type="date" name="date" id="date" value="{{date('Y-m-d')}}" style="width: 65%">
                   </div>  
               </div>
               <div class="d-grid">
                   <button type="submit" class="btn btn-gray-800 addHead">Submit</button>
               </div>
            </form>
             </div>
         </div>
     </div>
 </div>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}" />
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
      $(".col_type").change();
   });
   var select_head = "";
   $(function () {      
      var table = $('.yajra-datatable').DataTable({
          processing: true,
          serverSide: true,
          ajax: "{{ route('rule.list') }}",
          columns: [
              {data: 'DT_RowIndex', name: 'DT_RowIndex'},
              {data: 'rule_name', name: 'rule_name'},
              {data: 'col_type', name: 'col_type'},
              {data: 'rule_condition', name: 'rule_condition'},
              {data: 'content', name: 'content'},
              {data: 'rule_action_type', name: 'rule_action_type'},
              {data: 'name', name: 'name'},
              {
                  data: 'action', 
                  name: 'action', 
                  orderable: true, 
                  searchable: true
              },
          ]
      });
      
    });

   $(document).on("click",".edit_rule",function(){
      //$("#cover-spin").show();     
      let id  = $(this).attr('data-id');
      let col_type  = $(this).attr('data-col_type');      
      let rule_condition  = $(this).attr('data-rule_condition');
      let content  = $(this).attr('data-content');
      let rule_action_type  = $(this).attr('data-rule_action_type');
      let head_id  = $(this).attr('data-head_id');
      let col_type1  = $(this).attr('data-col_type1');
      let rule_condition1  = $(this).attr('data-rule_condition1');
      let content1  = $(this).attr('data-content1');
      let rule_name  = $(this).attr('data-rule_name');
      let rule_for  = $(this).attr('data-rule_for');
      let category  = $(this).attr('data-category');
      let customer  = $(this).attr('data-customer');
      let supplier  = $(this).attr('data-supplier');
      let rule_in  = $(this).attr('data-rule_in');
      select_head = head_id;
      if(rule_condition1=="Any"){
            $("#content2_1").hide();
      }

      $("#col_type_1").val(col_type);
      $("#col_type_1").trigger('change');
      getHead(rule_action_type);
      // older description rules were saved with the condition "Any" meaning "contains"
      if(col_type=="Description" && rule_condition!="Exact"){ rule_condition = "like"; }
      if(col_type=="Description" && (rule_condition1=="Any Value")){ rule_condition1 = "Any"; }
      $("#rule_condition_1").val(rule_condition);
      $("#content_1").val(content);
      $("#ac_type").html(rule_action_type);
      $("#rule_action_type_1").val(rule_action_type);
      $("#head_1").val(head_id);
      
      $("#col_type2_1").val(col_type1);
      $("#rule_condition2_1").val(rule_condition1);
      $("#content2_1").val(content1);  
      $("#head_id").val(id);
      $("#rule_name").val(rule_name);
      $("#category").val(category);
      $("#customer").val(customer);
      $("#supplier").val(supplier);
      $("#rule_for").val(rule_for);
      $("#rule_in").val(rule_in);
      if(rule_in=="" || rule_in==null){ $("#rule_in").val("ANY"); }
      $("#rule_status").val($(this).attr('data-status')=="0" ? "0" : "1");
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
      setTimeout(function () {
                           getCustomer();
                       }, 1000);
      
   });

   $(".col_type").change(function(){
      let id = $(this).attr('data-id');
      let rule_condition = "";
      if($(this).val()=="Amount"){
         rule_condition = '<option value="">Select</option><option value="Exact">Exactly equal to</option><option value=">=">More than or equal to</option><option value="<=">Less than or equal to</option><option value=">">More than</option><option value="<">Less than</option><option value="Any">Any Amount</option>';
         $("#content_"+id).attr("placeholder","Enter Amount");
         $("#col_type2_"+id).val('Description');
         $("#rule_condition2_"+id).html('<option value="">Select</option><option value="like">Like</option><option value="Exact">Exactly</option><option value="Any">Any Text</option>'); 
         $("#content_"+id).addClass('amount_val');
         $("#content2_"+id).removeClass('amount_val');

         $("#ftext").html('is');
         $("#stext").html('contains');
      }else if($(this).val()=="Description"){
         // A description is text: it can contain something, or match it exactly. Numbers belong to the amount.
         rule_condition = '<option value="">Select</option><option value="like">Contains</option><option value="Exact">Is exactly</option>';
         $("#content_"+id).attr("placeholder","Enter text to look for");
         $("#col_type2_"+id).val('Amount');
         $("#rule_condition2_"+id).html('<option value="">Select</option><option value="Exact">Exactly equal to</option><option value=">=">More than or equal to</option><option value="<=">Less than or equal to</option><option value=">">More than</option><option value="<">Less than</option><option value="Any">Any Amount</option>');
         $("#content_"+id).removeClass('amount_val');
         $("#content2_"+id).addClass('amount_val');
         $("#ftext").html('');
         $("#stext").html('is');
      }
      $("#second_label").text($(this).val()=="Amount" ? "Description" : "Amount");
      $("#rule_condition_"+id).html(rule_condition);
   });
   $(document).on("keypress",".amount_val",function(e){
     //if the letter is not digit then display error and don't type anything
      if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
         return false;
      }
   });
   $(".head").change(function(){
      if($(this).val()=="New"){
         $("#modal-form-signup").modal('toggle');
      }
   });
   $("#rule_for").change(function(){
      if($(this).val()=="Credit"){
         $("#ac_type").css('color','green');
      }else if($(this).val()=="Debit"){
         $("#ac_type").css('color','red');
      }
      $("#ac_type").html($(this).val());
      $("#rule_action_type_1").val($(this).val());
      getHead($(this).val());
   });
   
   function getHead(type){
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         url : "get-head",
         method : "post",
         data : "type="+type,
         success : function(res){
            let head_html = "<option value=''>Select Head</option><option value='New'>Create New</option>";
            if(res[0].head.length>0){              
               res[0].head.forEach(function(e){
                  let selected = "";
                  if(select_head==e.id){
                     selected = "selected";
                  }
                  head_html+='<option value="'+e.id+'" '+selected+' data-mandatory_category="'+e.mandatory_category+'" data-mandatory_customer="'+e.mandatory_customer+'" data-mandatory_supplier="'+e.mandatory_supplier+'">'+e.name+'</option>';
               });
            }
            $(".head").html(head_html);
            let account_html = "<option value=''>Select Account</option>";
            if(res[0].account_type.length>0){              
               res[0].account_type.forEach(function(e){
                  account_html+='<option value="'+e.id+'">'+e.name+'</option>';
               });
            }
            $("#account_type").html(account_html);
         }
      });
   }
   $("#applicable_gst").change(function(){
      $("#tax_code").val($(this).val());
   });
   
   $("#idForm").submit(function(e) {
       e.preventDefault(); // avoid to execute the actual submit of the form.

       var form = $(this);
       $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
           type: "POST",
           url: "add-head",
           data: form.serialize(), // serializes the form's elements.
           success: function(data)
           {
            $("#modal-form-signup").modal('toggle');
              getHead($(".rule_action_type").val());
           }
       });
       
   });

   $("#rule_condition2_1").change(function(){
      if($(this).val()=="Any"){
         $("#content2_1").hide();
      }else{
         $("#content2_1").show();
      }
   });
   function getCustomer(){
      $("#category").hide();
      $("#customer").hide();
      $("#supplier").hide();
      let mandatory_category = $("#head_1 option:selected").attr('data-mandatory_category');
      let mandatory_customer = $("#head_1 option:selected").attr('data-mandatory_customer');
      let mandatory_supplier = $("#head_1 option:selected").attr('data-mandatory_supplier');
      if(mandatory_category==1){
         $("#category").css('display','initial');
      }
      if(mandatory_customer==1){
         $("#customer").css('display','initial');
      }
      if(mandatory_supplier==1){
         $("#supplier").css('display','initial');
      }
   }
   $("#head_1").change(function(){
      getCustomer();
   });
   $("#rule_condition_1").change(function(){
      $("#content_1").show();
      $("#content_1").val('');
      if($(this).val()=="Any"){
         $("#content_1").hide();
      }
   });
</script>
@endpush