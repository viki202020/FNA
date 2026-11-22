@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
@if(session('rule_apply_msg'))
   <div style="margin:10px 0;padding:10px 14px;border-radius:6px;border:1px solid #27ae60;background:#eafaf1;">
      {{ session('rule_apply_msg') }}
      <a href="{{ url('/merchant/rule-conflicts') }}">Rule conflicts</a> &middot; <a href="{{ url('/merchant/rule-audit') }}">Rule audit</a>
   </div>
@endif
@if(session('bank_upload_summary'))
   @php $b = session('bank_upload_summary'); @endphp
   <div style="margin:10px 0;padding:10px 14px;border-radius:6px;border:1px solid {{ count($b['rejected']) ? '#b9770e' : '#27ae60' }};background:{{ count($b['rejected']) ? '#fef5e7' : '#eafaf1' }};">
      <strong>Bank upload: {{ $b['saved'] }} line(s) saved in financial year {{ $b['year'] }}@if(count($b['rejected'])), {{ count($b['rejected']) }} rejected @endif.</strong>
      @if(!empty($b['returns']))<div style="margin-top:6px">{{ $b['returns'] }} returned payment(s) recognised and reversed.</div>@endif
      <div style="margin-top:6px">{{ $b['auto'] ?? 0 }} line(s) were categorised by a rule.
         @if(!empty($b['conflicts']))<strong>{{ $b['conflicts'] }} line(s) fit more than one rule and need your choice:</strong> <a href="{{ url('/merchant/rule-conflicts') }}">Rule conflicts</a>.@endif
         <a href="{{ url('/merchant/rule-audit') }}">Rule audit</a></div>
      @if(count($b['rejected']))
         <div style="margin-top:6px">Rejected lines were not saved (a closed year cannot be changed, and every line needs a date inside {{ $b['year'] }}):
            <ul style="margin:4px 0 0 18px">
               @foreach(array_slice($b['rejected'],0,8) as $r)
                  <li>{{ $r['date'] !== '' ? $r['date'] : '(no date)' }}: {{ $r['why'] }}@if($r['remark']!='') &mdash; {{ $r['remark'] }}@endif</li>
               @endforeach
               @if(count($b['rejected']) > 8)<li>... and {{ count($b['rejected']) - 8 }} more</li>@endif
            </ul>
         </div>
      @endif
   </div>
@endif
<style>
    table td {
        word-break: break-word;
        vertical-align: top;
        white-space: normal !important;
    }
</style>
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4" style="overflow: scroll;">
         <h2 class="mb-4">Uncategorized Entry</h2>
         <div class="row">
            <div class="col-3 col-xl-3" style="display:none">
               <select class="form-control" name="rule" id="rules" >
                  <option value="">Select Rule</option>
                  @forEach($rules as $rule)
                     <option value="{{$rule->id}}">{{$rule->rule_name}}</option>
                  @endforeach
               </select>
            </div>
            <div class="col-2 col-xl-2">
               <button class="btn btn-info apply_rule">Apply Rule(BULK)</button>
            </div>
            <div class="col-3 col-xl-3">
               <button class="btn btn-warning assign_manually_bulk">Assign Manually(BULK)</button>
            </div>
            <div class="col-3 col-xl-3">
               <select class="form-control" name="bank" id="bank" >
                  <option value="">Select the upload type</option>
                  @forEach($banks as $bank)
                     <option value="{{$bank->id}}" data-name="{{$bank->name}}">{{$bank->name}}</option>
                  @endforeach
                  <option value="JV" data-name="Journal Entry">Journal Entry</option>
               </select>
            </div>
            <div class="col-3 col-xl-3">
               <button class="btn btn-info fetch_data">Submit</button>
            </div>
         </div><br>
         <table class="table table-bordered yajra-datatable">
            <thead>
               <tr>
                  <th>No</th>
                  <th style="width: 13%;">Date</th>
                  <th>Voucher No.</th>
                  <th>Particular</th>
                  <th>Debit</th>
                  <th>Credit</th>
                  <th style="width: 24%;">Action</th>
               </tr>
            </thead>
            <tbody>
            </tbody>
         </table>
      </div>
   </div>
</div>
<div class="modal fade" id="ruleModal" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content ">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               <div class="text-center text-md-center mb-4 mt-md-0">
                  <h1 class="mb-0 h4">Apply Rule</h1>
               </div> 
               <form id="applyRuleForm">
                  <div class="col-md-12 mb-3">
                     <select class="form-control" name="select_rule" id="select_rule" required>
                        <option value="">Select Rule</option>
                        <option value="Add New Rule">Create New Rule</option>
                     </select>                  
                  </div><br>
                  <input type="hidden" name="apply_row_id[]" id="apply_row_id">
                  <div class="d-grid">
                      <button type="submit" class="btn btn-gray-800 applyRuleBtn">Submit</button>
                  </div>
               </form>
             </div>
         </div>
     </div>
   </div>
</div>
<div class="modal fade" id="addRuleModal" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content ">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               <div class="text-center text-md-center mb-4 mt-md-0">
                  <h1 class="mb-0 h4">Add Rule</h1>
               </div> 
               <form id="idForm">
                  <div class="col-md-12 mb-3">
                     Rule Name
                     <input class="form-control" type="text" placeholder="Rule Name" name="rule_name[]" id="rule_name" required style="display: initial;width: 15%;"> for <select class="form-control" style="display: initial;width: 20%;" name="rule_for[]" id="rule_for" required><option value="">Select For</option>
                        <option value="Credit">Money coming in (Credit)</option>
                        <option value="Debit">Money going out (Debit)</option>
                     </select>
                  
                  </div><br>        
                  <div class="col-md-12 mb-3">
                     Create a rule where 
                     <select  name="col_type[]" id="col_type_1" required class="form-control col_type" style="display: initial;width: 15%;" data-id="1">
                        <option value="">Select</option>
                        <option value="Amount">Amount</option>
                        <option value="Description">Description</option>
                     </select> 
                     <span id="ftext">is</span> 
                     <select name="rule_condition[]" id="rule_condition_1" class="form-control rule_condition" data-id="1" required style="display: initial;width: 12%;">
                        <option value="">Select</option>
                        
                     </select> 
                     <input type="text"  name="content[]" id="content_1" class="form-control contents" style="display: initial;width: 20%;" data-id="1" required> 
                     and 
                     <input type="text" name="col_type2[]" id="col_type2_1" required class="form-control col_type2" data-id="1" style="display: initial;width: 11%;" readonly> <br><br>
                     <span id="stext">contains</span> 
                     <select  name="rule_condition2[]" id="rule_condition2_1" required class="form-control rule_condition2" data-id="1" style="display: initial;width: 12%;">
                        <option value="">Select</option>                     
                     </select> 
                     <input type="text"  name="content2[]" id="content2_1" class="form-control content2" data-id="1" style="display: initial;width: 20%;"> 
                     and 
                     <input type="text" name="rule_action_type[]" id="rule_action_type_1" required class="form-control rule_action_type" data-id="1" style="display: initial;width: 12%;" readonly> 
                     to account head
                     <select  name="head[]" id="head_1" required class="form-control head" style="display: initial;width: 12%;" data-id="1">
                        <option value="">Select</option>                     
                     </select>
                  </div>
                  <div class="d-grid">
                      <button type="submit" class="btn btn-gray-800 addRule">Submit</button>
                  </div>
               </form>
             </div>
         </div>
     </div>
   </div>
</div>
<div class="modal fade" id="manuallyModal" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content ">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               <div class="text-center text-md-center mb-4 mt-md-0">
                  <h1 class="mb-0 h4">Assign Manually Head</h1>
               </div> 
               <form id="assignManuallyForm">
                  <div class="col-md-12 mb-3 voucher_entry">
                     
                  </div>
                  <div class="col-md-12 mb-3 without_voucher_entry">                     
                     <select class="form-control" id="select_head" required name="select_head">
                        <option value="">Select Head</option>
                     </select> 
                     <input type="hidden" name="manually_id" id="manually_id">                 
                  </div><br>
                  <div class="d-grid">
                      <button type="submit" class="btn btn-gray-800 assignManually">Submit</button>
                  </div>
               </form>
             </div>
         </div>
     </div>
   </div>
</div>
<div class="modal fade" id="manuallyBulkModal" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content ">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               <div class="text-center text-md-center mb-4 mt-md-0">
                  <h1 class="mb-0 h4">Assign Manually Head</h1>
               </div> 
               <div class="col-md-12 mb-3 bulk_entry"> 
               </div>                
               <div class="col-md-12 mb-3">                     
                  <select class="form-control" id="select_bulk_head" required name="select_bulk_head">
                     <option value="">Select Head</option>
                  </select>                  
               </div><br>
               <div class="d-grid">
                   <button type="button" class="btn btn-gray-800 assignBulkManually">Submit</button>
               </div>
               
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
   getData();
   var row_id = [];
   var retick = [];
   function getData(){
      $('.yajra-datatable').dataTable().fnDestroy();
      var table = $('.yajra-datatable').DataTable({
          processing: true,
          serverSide: true,
          "iDisplayLength": 100,
          ajax: "{{ route('uncategorized_data.list') }}",
          drawCallback: function(){ if(retick.length){ $('.check').each(function(){ if(retick.indexOf(String($(this).val()))>-1){ $(this).prop('checked', true); } }); retick = []; } },
          columns: [
              {data: 'bulk_check', name: 'bulk_check'},
              {data: 'st_date', name: 'st_date'},
              {data: 'voucher_no', name: 'voucher_no'},
              {data: 'remark', name: 'remark'},
              {data: 'debit', name: 'debit'},
              {data: 'credit', name: 'credit'},
              {
                  data: 'action', 
                  name: 'action', 
                  orderable: true, 
                  searchable: true
              },
          ]
      });
   }
   $(function () {   
            
    });
   $(".apply_rule").click(function(){
      let rules = $("#rules").val();
      let row_id = [];
      $(".check").each(function(){
         if($(this).prop("checked")==true){
            row_id.push($(this).val());
         }         
      });
      // if(row_id.length==0){
      //    alert("Please select row");
      //    return;
      // }
      // if(rules==""){
      //    alert("Please select rule");
      //    return;
      // }
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         url : "apply-bulk-rule",
         method : "POST",
         data : "select_rule="+rules+"&apply_row_id="+row_id,
         success : function(res){
            if(res==1){
               alert("Rule Applied Successfully.");
               location.reload();
            }else{
               alert((res && res.message) ? res.message : "Something went wrong.");
            }
         }
      });
   });
   $(document).on("click",".row_apply_rule",function(){
      let id = $(this).attr('data-id');
      $("#apply_row_id").val(id);
      $("#ruleModal").modal('toggle');      
   });
   $("#select_rule").change(function(){
      if($(this).val()=="Add New Rule"){
         $("#ruleModal").modal('toggle');
         $("#addRuleModal").modal('toggle');
      }
   });
   $("#idForm").submit(function(e) {
      e.preventDefault(); // avoid to execute the actual submit of the form.
      var form = $(this);
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         type: "POST",
         url: "add-rule",
         data: form.serialize(), // serializes the form's elements.
         success: function(data){
            $("#addRuleModal").modal('toggle');
            getRule();
            $("#ruleModal").modal('toggle');
         }
      });       
   });
   getRule();
   function getRule(){
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         url : "get-rule",
         type : "POST",
         success : function(res){
            if(res.length>0){ 
               var rule_html = '<option value="">Select Rule</option><option value="Add New Rule">Create New Rule</option>'; 
               let i=1;          
               res.forEach(function(e){
                  let html = "";                  
                  html+='Rule '+e.rule_name;
                  rule_html+='<option value="'+esc(e.id)+'">'+html+'</option>';
               });
               $("#select_rule").html(rule_html);
            }
         }
      });
   }
   $(".col_type").change(function(){
      let id = $(this).attr('data-id');
      let rule_condition = "";
      if($(this).val()=="Amount"){
         rule_condition = '<option value="">Select</option><option value="Exact">Exactly</option><option value=">=">>=</option><option value="<="><=</option><option value=">">></option><option value="<"><</option>';
         $("#content_"+id).attr("placeholder","Enter Amount");
         $("#col_type2_"+id).html('<option value="Description">Description</option>');
         $("#rule_condition2_"+id).html('<option value="">Select</option><option value="Exact">Exactly</option><option value="Any">Any</option>'); 
         $("#content_"+id).addClass('amount_val');
         $("#content2_"+id).removeClass('amount_val');

         $("#ftext").html('is');
         $("#stext").html('contains');
      }else if($(this).val()=="Description"){
         rule_condition = '<option value="">Select</option><option value="Exact">Exactly</option><option value="Any">Any</option>';
         $("#content_"+id).attr("placeholder","Enter Description");
         $("#col_type2_"+id).html('<option value="Amount">Amount</option>');
         $("#rule_condition2_"+id).html('<option value="">Select</option><option value="Exact">Exactly</option><option value=">=">>=</option><option value="<="><=</option><option value=">">></option><option value="<"><</option><option value="Any">Any</option>');
         $("#content_"+id).removeClass('amount_val'); 
         $("#content2_"+id).addClass('amount_val'); 
         $("#ftext").html('contains');
         $("#stext").html('is');      
      }
      $("#rule_condition_"+id).html(rule_condition);
   });
   $(document).on("keypress",".amount_val",function(e){
      if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
         return false;
      }
   });
   $("#rule_for").change(function(){
      $("#rule_action_type_1").val($(this).val())
      getHead($(this).val());
   });
   $("#rule_condition2_1").change(function(){
      if($(this).val()=="Any"){
         $("#content2_1").hide();
      }else{
         $("#content2_1").show();
      }
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
            let head_html = "<option value=''>Select Head</option>";
            if(res[0].head.length>0){              
               res[0].head.forEach(function(e){
                  head_html+='<option value="'+esc(e.id)+'">'+esc(e.name)+'</option>';
               });
            }
            $(".head").html(head_html);
            let account_html = "<option value=''>Select Account</option>";
            if(res[0].account_type.length>0){              
               res[0].account_type.forEach(function(e){
                  account_html+='<option value="'+esc(e.id)+'">'+esc(e.name)+'</option>';
               });
            }
            $("#account_type").html(account_html);
         }
      });
   }
   $("#applyRuleForm").submit(function(e) {
      e.preventDefault(); // avoid to execute the actual submit of the form.
      var form = $(this);
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         type: "POST",
         url: "apply-rule",
         data: form.serialize(), // serializes the form's elements.
         success: function(data){
           if(data==1){
               alert("Rule Applied Successfully");
               location.reload();
           }else if(data && data.message){
               alert(data.message);
           }
         }
      });       
   });
   $(document).on("click",".row_manually",function(){
      $(".voucher_entry").html('');
      $(".without_voucher_entry").show(); 
      $("#select_head").attr('required',true);
      let id = $(this).attr('data-id');
      let credit = $(this).attr('data-credit');
      let debit = $(this).attr('data-debit');
      let voucher_no = $(this).attr('data-voucher_no');
      let type = "Credit";
      if(credit=="" || credit=="-"){
         type = "Debit";
      }
      if(id==""){
         alert("Something went wrong");
         return;
      }
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         type: "POST",
         url: "get-head",
         data: "type="+type+"&voucher_no="+voucher_no, // serializes the form's elements.
         success: function(res){
            let head_html = "<option value=''>Select Head</option>";
            if(res[0].head.length>0){              
              res[0].head.forEach(function(e){
                 let selected = "";                 
                 head_html+='<option value="'+esc(e.id)+'" '+selected+'>'+esc(e.name)+'</option>';
              });
            }
            $("#manually_id").val(id);            
            $("#select_head").html(head_html);

            if(res[0].statements.length>0){     
               let statement_row = "<table class='table'><thead><tr><th>Date</th><th>Voucher</th><th>Remark</th><th>Debit</th><th>Credit</th><th>Head</th><th></th></tr></thead><tbody>"; 
               let assign_status = "";        
               res[0].statements.forEach(function(e){
                  let head_html = "<select class='form-control' name='voucher_head[]' required><option value=''>Select Head</option>";
                  if(e.head.length>0){              
                    e.head.forEach(function(e1){
                       let selected = ""; 
                       if(e.head_id==e1.id){
                        selected = "selected";
                        assign_status = "A";
                       }                
                       head_html+='<option value="'+esc(e1.id)+'" '+selected+'>'+esc(e1.name)+'</option>';
                    });
                  }
                  head_html+='</select>';                      
                  statement_row+='<tr><td>'+esc(e.st_date)+'</td><td>'+esc(e.voucher_no)+'</td><td>'+esc(e.remark)+'</td><td><input type="hidden" name="voucher_row[]" value="'+esc(e.id)+'">'+esc(e.debit)+'</td><td>'+esc(e.credit)+'</td><td>'+head_html+'</td><td>'+esc(assign_status)+'</td></tr>';
               });
               statement_row+="</tbody></table>";
               $(".voucher_entry").html(statement_row);
               $(".without_voucher_entry").hide(); 
               $("#manually_id").val("");            
               $("#select_head").html("");
               $("#select_head").attr('required',false);
            }
            $("#manuallyModal").modal("toggle");           
         }
      }); 
   });

   $("#assignManuallyForm").submit(function(e) {
      e.preventDefault(); // avoid to execute the actual submit of the form.
      var form = $(this);
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         type: "POST",
         url: "assign-head-manually",
         data: form.serialize(), // serializes the form's elements.
         success: function(data){
           if(data==1){
               alert("Head Assigned Successfully");
               location.reload();
           }else if(data && data.message){
               alert(data.message);
           }
         }
      });       
   });
   $(".assignBulkManually").click(function() {
      let select_bulk_head = $("#select_bulk_head").val();
      if(select_bulk_head==""){
         alert("Please select head")
         return;
      }
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         type: "POST",
         url: "assign-bulk-head-manually",
         data: "select_bulk_head="+select_bulk_head+"&row_id="+row_id, // serializes the form's elements.
         success: function(data){

           if(data.success==true){
               var failed = data.failed || [];
               if(failed.length){
                  var lines = failed.map(function(f){ return '- '+f.st_date+' '+f.remark+': '+f.reason; }).join('\n');
                  alert(data.done+" line(s) assigned.\n"+failed.length+" line(s) could not be assigned and are still ticked:\n"+lines);
                  retick = failed.map(function(f){ return String(f.id); });
               }else{
                  alert(data.done+" line(s) assigned successfully.");
               }
               getData();
               $("#manuallyBulkModal").modal('toggle');
           }else{
            alert((data && data.message) ? data.message : "Something went wrong");
           }
         }
      });       
   });
   $(".fetch_data").click(function(){      
      let bank = $("#bank").val();
      if(bank==""){
         alert("Please Select One");
         return
      }
      let name = $("#bank option:selected").attr('data-name');
      if(confirm("Are you sure to fetch "+name+" data?")){

      }
   });

   $(".assign_manually_bulk").click(function(){
      row_id = [];
      let html = "<table class='table table-bordered'><thead><tr><th>Date</th><th>Remark</th><th>Debit</th><th>Credit</th></tr></head><tbody>";
      $(".check").each(function(){
         if($(this).prop("checked")==true){
            row_id.push($(this).val());
            html+='<tr><td>'+esc($(this).attr('data-st_date'))+'</td><td>'+esc($(this).attr('data-remark'))+'</td><td>'+esc($(this).attr('data-debit'))+'</td><td>'+esc($(this).attr('data-credit'))+'</td></tr>';
         }         
      });
      html+='</tbody></table>';
      if(row_id.length==0){
         alert("Please select row");
         return;
      }
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         type: "POST",
         url: "get-head",
         data: "type=All", // serializes the form's elements.
         success: function(res){
            let head_html = "<option value=''>Select Head</option>";
            if(res[0].head.length>0){              
              res[0].head.forEach(function(e){
                 let selected = "";                 
                 head_html+='<option value="'+esc(e.id)+'" '+selected+'>'+esc(e.name)+'</option>';
              });
            }
            $(".bulk_entry").html(html);
            $("#select_bulk_head").html(head_html);
            $("#manuallyBulkModal").modal("toggle");           
         }
      });
      // $.ajax({
      //    headers: {
      //       'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      //    },
      //    url : "apply-bulk-rule",
      //    method : "POST",
      //    data : "apply_row_id="+row_id,
      //    success : function(res){
      //       if(res==1){
      //          alert("Rule Applied Successfully.");
      //          location.reload();
      //       }else{
      //          alert("Something went wrong.");
      //       }
      //    }
      // });
   });
</script>
@endpush