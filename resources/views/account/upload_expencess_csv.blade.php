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
         <h3 class="h5 mb-2">Let's set up your file</h3>                               
         <form action="import-expencess-csv" method="post" enctype="multipart/form-data">
            @csrf
            <div class="row">
               <div class="col-md-12 mb-3"><h6>Tell us about the format of your data</h6></div>
               <div class="col-md-4 mb-3">
                  <div>
                     <label for="header_row">Which row in your file is the header?</label>
                     <input type="number" name="header_row" id='header_row' class="form-control">
                     <!-- <select class="form-control" name="header" id="header" required>
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                     </select> -->
                  </div>                  
               </div> 
               <div class="col-md-4 mb-3">
                  <div>
                     <label for="last_row">Which row is the totals row?</label>
                     <input type="number" name="last_row" id='last_row' class="form-control">
                     <!-- <select class="form-control" name="header" id="header" required>
                        <option value="">Select</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                     </select> -->
                  </div>                  
               </div>            
               <div class="col-md-4 mb-3">
                  <div>
                     <label for="description">How many columns show amounts ?</label>
                     <select class="form-control" name="amount_column" id="amount_column" required>
                        <option value="">Select</option>
                        <option value="One Column">One Column</option>
                        <option value="Two Column">Two Column</option>
                     </select>
                  </div>                 
               </div>
               <div class="col-md-4 mb-3 amount_contain_div" style="display:none">
                  <div>
                     <label for="description">Amount Contains</label>
                     <select class="form-control" name="amount_contain" id="amount_contain">
                        <option value="">Select</option>
                        <option value="Dr/Cr">Dr/Cr</option>
                        <option value="+/-">+/-</option>
                     </select>
                  </div>                 
               </div>
               <div class="col-md-12 mb-3"><h6>Select the fields that correspond to your file</h6>
               </div>
               <div class="col-md-12 mb-3">
                  <table class="table table-bordered">
                     <thead>
                        <td>Fields</td>
                        <td>Column no. in your file</td>
                     </thead>
                     <tbody>
                        <tr>
                           <td>Serial No</td>
                           <td>
                              <select class="form-control" id="serial_col" name="serial_col">
                                 <option value="">Select Column</option>
                                 <option value="1" class="option_1">Column 1</option>
                                 <option value="2" class="option_2">Column 2</option>
                                 <option value="3" class="option_3">Column 3</option>
                                 <option value="4" class="option_4">Column 4</option>
                                 <option value="5" class="option_5">Column 5</option>
                                 <option value="6" class="option_6">Column 6</option>
                                 <option value="7" class="option_7">Column 7</option>
                                 <option value="8" class="option_8">Column 8</option>
                              </select>
                           </td>
                        </tr>
                        <tr>
                           <td>Date</td>
                           <td>
                              <select class="form-control" id="date_col" name="date_col">
                                 <option value="">Select Column</option>
                                 <option value="1" class="option_1">Column 1</option>
                                 <option value="2" class="option_2">Column 2</option>
                                 <option value="3" class="option_3">Column 3</option>
                                 <option value="4" class="option_4">Column 4</option>
                                 <option value="5" class="option_5">Column 5</option>
                                 <option value="6" class="option_6">Column 6</option>
                                 <option value="7" class="option_7">Column 7</option>
                                 <option value="8" class="option_8">Column 8</option>
                              </select>
                           </td>
                        </tr>
                        <tr>
                           <td>Description</td>
                           <td>
                              <select class="form-control" id="description_col" name="description_col">
                                 <option value="">Select Column</option>
                                 <option value="1" class="option_1">Column 1</option>
                                 <option value="2" class="option_2">Column 2</option>
                                 <option value="3" class="option_3">Column 3</option>
                                 <option value="4" class="option_4">Column 4</option>
                                 <option value="5" class="option_5">Column 5</option>
                                 <option value="6" class="option_6">Column 6</option>
                                 <option value="7" class="option_7">Column 7</option>
                                 <option value="8" class="option_8">Column 8</option>
                              </select>
                           </td>
                        </tr>
                        <tr class="amount_div" style="display:none" >
                           <td>Amount</td>
                           <td>
                              <select class="form-control" id="amount_col" name="amount_col">
                                 <option value="">Select Column</option>
                                 <option value="1" class="option_1">Column 1</option>
                                 <option value="2" class="option_2">Column 2</option>
                                 <option value="3" class="option_3">Column 3</option>
                                 <option value="4" class="option_4">Column 4</option>
                                 <option value="5" class="option_5">Column 5</option>
                                 <option value="6" class="option_6">Column 6</option>
                                 <option value="7" class="option_7">Column 7</option>
                                 <option value="8" class="option_8">Column 8</option>
                              </select>
                           </td>
                        </tr>
                        <tr class="debit_amount_div" style="display:none" >
                           <td>Debit Amount</td>
                           <td>
                              <select class="form-control" id="debit_amount_col" name="debit_amount_col">
                                 <option value="">Select Column</option>
                                 <option value="1" class="option_1">Column 1</option>
                                 <option value="2" class="option_2">Column 2</option>
                                 <option value="3" class="option_3">Column 3</option>
                                 <option value="4" class="option_4">Column 4</option>
                                 <option value="5" class="option_5">Column 5</option>
                                 <option value="6" class="option_6">Column 6</option>
                                 <option value="7" class="option_7">Column 7</option>
                                 <option value="8" class="option_8">Column 8</option>
                              </select>
                           </td>
                        </tr>
                        <tr class="credit_amount_div" style="display:none" >
                           <td>Credit Amount</td>
                           <td>
                              <select class="form-control" id="credit_amount_col" name="credit_amount_col">
                                 <option value="">Select Column</option>
                                 <option value="1" class="option_1">Column 1</option>
                                 <option value="2" class="option_2">Column 2</option>
                                 <option value="3" class="option_3">Column 3</option>
                                 <option value="4" class="option_4">Column 4</option>
                                 <option value="5" class="option_5">Column 5</option>
                                 <option value="6" class="option_6">Column 6</option>
                                 <option value="7" class="option_7">Column 7</option>
                                 <option value="8" class="option_8">Column 8</option>
                              </select>
                           </td>
                        </tr>
                     </tbody>
                  </table>
               </div>
               <!-- <div class="col-md-12 mb-3">
                  <div>
                     <label for="description">Select/Add Rule</label>
                     <select class="form-control" name="select_rule" id="select_rule" required>
                        <option value="">Select Rule</option>
                        <option value="Add New Rule">Create New Rule</option>
                     </select>
                  </div>
               </div> -->
               <div class="col-md-4 mb-3">
                  <div>
                     <label for="description">Upload CSV</label>
                     <input type="file" class="form-control" name="csv_file" id="csv_file" required>
                  </div>
               </div>
            </div>
            <div class="mt-3">
               <button class="btn btn-gray-800 mt-2 animate-up-2" type="submit">Save all</button>
               @if(session('upload_summary'))
                  @php $u = session('upload_summary'); @endphp
                  <div style="margin:10px 0;padding:10px 14px;border-radius:6px;border:1px solid {{ count($u['unbalanced']) ? '#c0392b' : '#27ae60' }};background:{{ count($u['unbalanced']) ? '#fdecea' : '#eafaf1' }};">
                     @if(!empty($u['rejected']))
                        <div style="color:#7e5109;margin-bottom:6px"><strong>{{ count($u['rejected']) }} line(s) rejected</strong> (not saved: they must be dated inside financial year {{ $u['year'] }}):
                           <ul style="margin:4px 0 0 18px">
                              @foreach(array_slice($u['rejected'],0,8) as $r)
                                 <li>{{ $r['date'] }}: {{ $r['why'] }}@if($r['remark']!='') &mdash; {{ $r['remark'] }}@endif</li>
                              @endforeach
                           </ul>
                        </div>
                     @endif
                     @if($u['vouchers'] == 0)
                        <strong style="color:#c0392b">Nothing was saved.</strong> No line was read. Check the header row, the last row and the column choices.
                     @else
                        <strong>Saved {{ $u['lines'] }} lines in {{ $u['vouchers'] }} vouchers ({{ $u['first'] }} to {{ $u['last'] }}).</strong>
                        @if(count($u['unbalanced']) == 0)
                           <span style="color:#1e8449">Every voucher balances (debits = credits).</span>
                        @else
                           <div style="color:#c0392b;margin-top:6px"><strong>WARNING: {{ count($u['unbalanced']) }} voucher(s) do not balance:</strong>
                              <ul style="margin:4px 0 0 18px">
                              @foreach($u['unbalanced'] as $vno => $x)
                                 <li>{{ $vno }}@if($x['ref']!='') (file ref {{ $x['ref'] }})@endif: debits {{ number_format($x['debit'],2) }}, credits {{ number_format($x['credit'],2) }}, difference {{ number_format($x['difference'],2) }}</li>
                              @endforeach
                              </ul>
                           </div>
                        @endif
                     @endif
                  </div>
               @endif
               @if(session('success_msg'))
                  <span style="color:green">{{session('success_msg')}}</span>
               @endif
            </div>
         </form>
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
                  <h1 class="mb-0 h4">Add Rule</h1>
               </div> 
               <form id="idForm">
                  <div class="col-md-12 mb-3">
                     Rule Name
                     <input class="form-control" type="text" placeholder="Rule Name" name="rule_name[]" id="rule_name" required style="display: initial;width: 15%;"> for <select class="form-control" style="display: initial;width: 20%;" name="rule_for[]" id="rule_for" required><option value="">Create rule for</option>
                        <option value="Credit">Money coming into bank (Credit)</option>
                        <option value="Debit">Money going out from bank (Debit)</option>
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
                     <select name="rule_condition[]" id="rule_condition_1" class="form-control rule_condition" data-id="1" required style="display: initial;width: 18%;">
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
                     then              <input type="text" name="rule_action_type[]" id="rule_action_type_1" required class="form-control rule_action_type" data-id="1" style="display: initial;width: 12%;" readonly> 
                     to account head->
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
<meta name="csrf-token" content="{{ csrf_token() }}" />
@endsection
@push('script-head')
@endpush
@push('script-js')
<script type="text/javascript">
   $(document).ready(function(){
      $("#amount_column").change(function(){
         if($(this).val()=="One Column"){
            $(".amount_contain_div").show();
            $(".credit_amount_div").hide();
            $(".debit_amount_div").hide();
            $(".amount_div").show();
            $("#name").attr('required',true);
         }else{
            $(".credit_amount_div").show();
            $(".debit_amount_div").show();
            $(".amount_div").hide();
            $(".amount_contain_div").hide();
            $("#name").attr('required',false);
         }
      });
      $("#select_rule").change(function(){
         if($(this).val()=="Add New Rule"){
            $("#ruleModal").modal('toggle');
         }else{
            
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
                  // let c1 = e.content1+" ";
                  // if(e.rule_condition1=="Any"){
                  //    c1 = "";
                  // }
                  // if(e.col_type=="Amount"){
                  //    html = 'Rule '+e.rule_name+' : '+e.col_type+' is '+e.rule_condition+' '+e.content+' and '+e.col_type1+' contains '+e.rule_condition1+' '+c1+'Add To '+e.rule_action_type+' to account head '+e.name;
                  // }else{
                  //    html = 'Rule '+e.rule_name+': '+e.col_type+' contains '+e.rule_condition+' '+e.content+' and '+e.col_type1+' is '+e.rule_condition1+' '+c1+'Add To '+e.rule_action_type+' to account head '+e.name;
                  // }
                  html+='Rule '+e.rule_name;
                  rule_html+='<option value="'+e.id+'">'+html+'</option>';
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
         $("#rule_condition2_"+id).html('<option value="">Select</option><option value="Exact">Exactly</option><option value=">=">More than or equal to</option><option value="<=">less than or equal to</option><option value=">">More than</option><option value="<">Less than </option><option value="Any">Any Value</option>'); 
         $("#content_"+id).addClass('amount_val');
         $("#content2_"+id).removeClass('amount_val');

         $("#ftext").html('is');
         $("#stext").html('contains');
      }else if($(this).val()=="Description"){
         rule_condition = '<option value="">Select</option><option value="Exact">Exactly Equals</option><option value="Any">Any Value</option>';
         $("#content_"+id).attr("placeholder","Enter Description");
         $("#col_type2_"+id).html('<option value="Amount">Amount</option>');
         $("#rule_condition2_"+id).html('<option value="">Select</option><option value="Exact">Exactly</option><option value=">=">More than or equal to</option><option value="<=">less than or equal to</option><option value=">">More than</option><option value="<">Less than </option><option value="Any">Any Value</option>');
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
   
   function getHead(type){
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         url : "get-head",
         method : "post",
         data : "type="+type,
         success : function(res){
            let head_html = "<option value=''>Select Account Head</option>";
            if(res[0].head.length>0){              
               res[0].head.forEach(function(e){
                  head_html+='<option value="'+e.id+'">'+e.name+'</option>';
               });
            }
            $(".head").html(head_html);
            let account_html = "<option value=''>Select Account Head</option>";
            if(res[0].account_type.length>0){              
               res[0].account_type.forEach(function(e){
                  account_html+='<option value="'+e.id+'">'+e.name+'</option>';
               });
            }
            $("#account_type").html(account_html);
         }
      });
   }
   $("#rule_condition2_1").change(function(){
      if($(this).val()=="Any"){
         $("#content2_1").hide();
      }else{
         $("#content2_1").show();
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
            $("#ruleModal").modal('toggle');
            getRule();
         }
      });       
   });
   var date_sel = $("#date_col");
   date_sel.data("prev",date_sel.val());
   $("#date_col").change(function(){
      var jqThis = $(this);
      $(".option_"+jqThis.data("prev")).attr('disabled',false);
      let val = $(this).val();
      let selected1 = "";
      let selected2 = "";
      let selected3 = "";
      let selected4 = "";
      let selected5 = "";
      let selected6 = "";
      let selected7 = "";
      let selected8 = "";
      let selectedText1 = "";
      let selectedText2 = "";
      let selectedText3 = "";
      let selectedText4 = "";
      let selectedText5 = "";
      let selectedText6 = "";
      let selectedText7 = "";
      let selectedText8 = "";
      if(val==1){
         selected1 = 'selected';
         selectedText1 = ' : Date';
         $(".option_1").attr('disabled',true);
      }else if(val==2){
         selected2 = 'selected';
         selectedText2 = ' : Date';
         $(".option_2").attr('disabled',true);
      }else if(val==3){
         selected3 = 'selected';
         selectedText3 = ' : Date';
         $(".option_3").attr('disabled',true);
      }else if(val==4){
         selected4 = 'selected';
         selectedText4 = ' : Date';
         $(".option_4").attr('disabled',true);
      }else if(val==5){
         selected5 = 'selected';
         selectedText5 = ' : Date';
         $(".option_5").attr('disabled',true);
      }else if(val==6){
         selected6 = 'selected';
         selectedText6 = ' : Date';
         $(".option_6").attr('disabled',true);
      }else if(val==7){
         selected7 = 'selected';
         selectedText7 = ' : Date';
         $(".option_7").attr('disabled',true);
      }else if(val==8){
         selected8 = 'selected';
         selectedText8 = ' : Date';
         $(".option_8").attr('disabled',true);
      }
      $(this).html('<option value="">Select Column</option><option value="1" '+selected1+' class="option_1">Column 1'+selectedText1+'</option><option value="2" '+selected2+' class="option_2">Column 2'+selectedText2+'</option><option value="3" '+selected3+' class="option_3">Column 3'+selectedText3+'</option><option value="4" '+selected4+' class="option_4">Column 4'+selectedText4+'</option><option value="5" '+selected5+' class="option_5">Column 5'+selectedText5+'</option><option value="6" '+selected6+' class="option_6">Column 6'+selectedText6+'</option><option value="7" '+selected7+' class="option_7">Column 7'+selectedText7+'</option><option value="8" '+selected8+' class="option_8">Column 8'+selectedText8+'</option>');
      jqThis.data("prev",jqThis.val());
   });
   var desc_sel = $("#date_col");
   desc_sel.data("prev",desc_sel.val());
   $("#description_col").change(function(){
      let val = $(this).val();
      var jqThis = $(this);
      $(".option_"+jqThis.data("prev")).attr('disabled',false);
      let selected1 = "";
      let selected2 = "";
      let selected3 = "";
      let selected4 = "";
      let selected5 = "";
      let selected6 = "";
      let selected7 = "";
      let selected8 = "";
      let selectedText1 = "";
      let selectedText2 = "";
      let selectedText3 = "";
      let selectedText4 = "";
      let selectedText5 = "";
      let selectedText6 = "";
      let selectedText7 = "";
      let selectedText8 = "";
      if(val==1){
         selected1 = 'selected';
         selectedText1 = ' : Description';
         $(".option_1").attr('disabled',true);
      }else if(val==2){
         selected2 = 'selected';
         selectedText2 = ' : Description';
         $(".option_2").attr('disabled',true);
      }else if(val==3){
         selected3 = 'selected';
         selectedText3 = ' : Description';
         $(".option_3").attr('disabled',true);
      }else if(val==4){
         selected4 = 'selected';
         selectedText4 = ' : Description';
         $(".option_4").attr('disabled',true);
      }else if(val==5){
         selected5 = 'selected';
         selectedText5 = ' : Description';
         $(".option_5").attr('disabled',true);
      }else if(val==6){
         selected6 = 'selected';
         selectedText6 = ' : Description';
         $(".option_6").attr('disabled',true);
      }else if(val==7){
         selected7 = 'selected';
         selectedText7 = ' : Description';
         $(".option_7").attr('disabled',true);
      }else if(val==8){
         selected8 = 'selected';
         selectedText8 = ' : Description';
         $(".option_8").attr('disabled',true);
      }
      $(this).html('<option value="">Select Column</option><option value="1" '+selected1+' class="option_1">Column 1'+selectedText1+'</option><option value="2" '+selected2+' class="option_2">Column 2'+selectedText2+'</option><option value="3" '+selected3+' class="option_3">Column 3'+selectedText3+'</option><option value="4" '+selected4+' class="option_4">Column 4'+selectedText4+'</option><option value="5" '+selected5+' class="option_5">Column 5'+selectedText5+'</option><option value="6" '+selected6+' class="option_6">Column 6'+selectedText6+'</option><option value="7" '+selected7+' class="option_7">Column 7'+selectedText7+'</option><option value="8" '+selected8+' class="option_8">Column 8'+selectedText8+'</option>');
      jqThis.data("prev",jqThis.val());
   });
   $("#amount_col").change(function(){
      let val = $(this).val();
      var jqThis = $(this);
      $(".option_"+jqThis.data("prev")).attr('disabled',false);
      let selected1 = "";
      let selected2 = "";
      let selected3 = "";
      let selected4 = "";
      let selected5 = "";
      let selected6 = "";
      let selected7 = "";
      let selected8 = "";
      let selectedText1 = "";
      let selectedText2 = "";
      let selectedText3 = "";
      let selectedText4 = "";
      let selectedText5 = "";
      let selectedText6 = "";
      let selectedText7 = "";
      let selectedText8 = "";
      if(val==1){
         selected1 = 'selected';
         selectedText1 = ' : Amount';
         $(".option_1").attr('disabled',true);
      }else if(val==2){
         selected2 = 'selected';
         selectedText2 = ' : Amount';
         $(".option_2").attr('disabled',true);
      }else if(val==3){
         selected3 = 'selected';
         selectedText3 = ' : Amount';
         $(".option_3").attr('disabled',true);
      }else if(val==4){
         selected4 = 'selected';
         selectedText4 = ' : Amount';
         $(".option_4").attr('disabled',true);
      }else if(val==5){
         selected5 = 'selected';
         selectedText5 = ' : Date';
         $(".option_5").attr('disabled',true);
      }else if(val==6){
         selected6 = 'selected';
         selectedText6 = ' : Date';
         $(".option_6").attr('disabled',true);
      }else if(val==7){
         selected7 = 'selected';
         selectedText7 = ' : Date';
         $(".option_7").attr('disabled',true);
      }else if(val==8){
         selected8 = 'selected';
         selectedText8 = ' : Date';
         $(".option_8").attr('disabled',true);
      }
      $(this).html('<option value="">Select Column</option><option value="1" '+selected1+' class="option_1">Column 1'+selectedText1+'</option><option value="2" '+selected2+' class="option_2">Column 2'+selectedText2+'</option><option value="3" '+selected3+' class="option_3">Column 3'+selectedText3+'</option><option value="4" '+selected4+' class="option_4">Column 4'+selectedText4+'</option><option value="5" '+selected5+' class="option_5">Column 5'+selectedText5+'</option><option value="6" '+selected6+' class="option_6">Column 6'+selectedText6+'</option><option value="7" '+selected7+' class="option_7">Column 7'+selectedText7+'</option><option value="8" '+selected8+' class="option_8">Column 8'+selectedText8+'</option>');
      jqThis.data("prev",jqThis.val());
   });
   var debit_sel = $("#date_col");
   debit_sel.data("prev",debit_sel.val());
   $("#debit_amount_col").change(function(){
      let val = $(this).val();
      var jqThis = $(this);
      $(".option_"+jqThis.data("prev")).attr('disabled',false);
      let selected1 = "";
      let selected2 = "";
      let selected3 = "";
      let selected4 = "";
      let selected5 = "";
      let selected6 = "";
      let selected7 = "";
      let selected8 = "";
      let selectedText1 = "";
      let selectedText2 = "";
      let selectedText3 = "";
      let selectedText4 = "";
      let selectedText5 = "";
      let selectedText6 = "";
      let selectedText7 = "";
      let selectedText8 = "";
      if(val==1){
         selected1 = 'selected';
         selectedText1 = ' : Debit Amount';
         $(".option_1").attr('disabled',true);
      }else if(val==2){
         selected2 = 'selected';
         selectedText2 = ' : Debit Amount';
         $(".option_2").attr('disabled',true);
      }else if(val==3){
         selected3 = 'selected';
         selectedText3 = ' : Debit Amount';
         $(".option_3").attr('disabled',true);
      }else if(val==4){
         selected4 = 'selected';
         selectedText4 = ' : Debit Amount';
         $(".option_4").attr('disabled',true);
      }else if(val==5){
         selected5 = 'selected';
         selectedText5 = ' : Debit Amount';
         $(".option_5").attr('disabled',true);
      }else if(val==6){
         selected6 = 'selected';
         selectedText6 = ' : Debit Amount';
         $(".option_6").attr('disabled',true);
      }else if(val==7){
         selected7 = 'selected';
         selectedText7 = ' : Debit Amount';
         $(".option_7").attr('disabled',true);
      }else if(val==8){
         selected8 = 'selected';
         selectedText8 = ' : Debit Amount';
         $(".option_8").attr('disabled',true);
      }
      $(this).html('<option value="">Select Column</option><option value="1" '+selected1+' class="option_1">Column 1'+selectedText1+'</option><option value="2" '+selected2+' class="option_2">Column 2'+selectedText2+'</option><option value="3" '+selected3+' class="option_3">Column 3'+selectedText3+'</option><option value="4" '+selected4+' class="option_4">Column 4'+selectedText4+'</option><option value="5" '+selected5+' class="option_5">Column 5'+selectedText5+'</option><option value="6" '+selected6+' class="option_6">Column 6'+selectedText6+'</option><option value="7" '+selected7+' class="option_7">Column 7'+selectedText7+'</option><option value="8" '+selected8+' class="option_8">Column 8'+selectedText8+'</option>');
      jqThis.data("prev",jqThis.val());
   });
   var credit_sel = $("#date_col");
   credit_sel.data("prev",credit_sel.val());
   $("#credit_amount_col").change(function(){
      let val = $(this).val();
      var jqThis = $(this);
      $(".option_"+jqThis.data("prev")).attr('disabled',false);
      let selected1 = "";
      let selected2 = "";
      let selected3 = "";
      let selected4 = "";
      let selected5 = "";
      let selected6 = "";
      let selected7 = "";
      let selected8 = "";
      let selectedText1 = "";
      let selectedText2 = "";
      let selectedText3 = "";
      let selectedText4 = "";
      let selectedText5 = "";
      let selectedText6 = "";
      let selectedText7 = "";
      let selectedText8 = "";
      if(val==1){
         selected1 = 'selected';
         selectedText1 = ' : Credit Amount';
         $(".option_1").attr('disabled',true);
      }else if(val==2){
         selected2 = 'selected';
         selectedText2 = ' : Credit Amount';
         $(".option_2").attr('disabled',true);
      }else if(val==3){
         selected3 = 'selected';
         selectedText3 = ' : Credit Amount';
         $(".option_3").attr('disabled',true);
      }else if(val==4){
         selected4 = 'selected';
         selectedText4 = ' : Credit Amount';
         $(".option_4").attr('disabled',true);
      }else if(val==5){
         selected5 = 'selected';
         selectedText5 = ' : Credit Amount';
         $(".option_5").attr('disabled',true);
      }else if(val==6){
         selected6 = 'selected';
         selectedText6 = ' : Credit Amount';
         $(".option_6").attr('disabled',true);
      }else if(val==7){
         selected7 = 'selected';
         selectedText7 = ' : Credit Amount';
         $(".option_7").attr('disabled',true);
      }else if(val==8){
         selected8 = 'selected';
         selectedText8 = ' : Credit Amount';
         $(".option_8").attr('disabled',true);
      }
      $(this).html('<option value="">Select Column</option><option value="1" '+selected1+' class="option_1">Column 1'+selectedText1+'</option><option value="2" '+selected2+' class="option_2">Column 2'+selectedText2+'</option><option value="3" '+selected3+' class="option_3">Column 3'+selectedText3+'</option><option value="4" '+selected4+' class="option_4">Column 4'+selectedText4+'</option><option value="5" '+selected5+' class="option_5">Column 5'+selectedText5+'</option><option value="6" '+selected6+' class="option_6">Column 6'+selectedText6+'</option><option value="7" '+selected7+' class="option_7">Column 7'+selectedText7+'</option><option value="8" '+selected8+' class="option_8">Column 8'+selectedText8+'</option>');
      jqThis.data("prev",jqThis.val());
   });
   var serial_sel = $("#date_col");
   serial_sel.data("prev",serial_sel.val());
   $("#serial_col").change(function(){
      let val = $(this).val();
      var jqThis = $(this);
      $(".option_"+jqThis.data("prev")).attr('disabled',false);
      let selected1 = "";
      let selected2 = "";
      let selected3 = "";
      let selected4 = "";
      let selected5 = "";
      let selected6 = "";
      let selected7 = "";
      let selected8 = "";
      let selectedText1 = "";
      let selectedText2 = "";
      let selectedText3 = "";
      let selectedText4 = "";
      let selectedText5 = "";
      let selectedText6 = "";
      let selectedText7 = "";
      let selectedText8 = "";
      if(val==1){
         selected1 = 'selected';
         selectedText1 = ' : Serial No';
         $(".option_1").attr('disabled',true);
      }else if(val==2){
         selected2 = 'selected';
         selectedText2 = ' : Serial No';
         $(".option_2").attr('disabled',true);
      }else if(val==3){
         selected3 = 'selected';
         selectedText3 = ' : Serial No';
         $(".option_3").attr('disabled',true);
      }else if(val==4){
         selected4 = 'selected';
         selectedText4 = ' : Serial No';
         $(".option_4").attr('disabled',true);
      }else if(val==5){
         selected5 = 'selected';
         selectedText5 = ' : Serial No';
         $(".option_5").attr('disabled',true);
      }else if(val==6){
         selected6 = 'selected';
         selectedText6 = ' : Serial No';
         $(".option_6").attr('disabled',true);
      }else if(val==7){
         selected7 = 'selected';
         selectedText7 = ' : Serial No';
         $(".option_7").attr('disabled',true);
      }else if(val==8){
         selected8 = 'selected';
         selectedText8 = ' : Serial No';
         $(".option_8").attr('disabled',true);
      }
      $(this).html('<option value="">Select Column</option><option value="1" '+selected1+' class="option_1">Column 1'+selectedText1+'</option><option value="2" '+selected2+' class="option_2">Column 2'+selectedText2+'</option><option value="3" '+selected3+' class="option_3">Column 3'+selectedText3+'</option><option value="4" '+selected4+' class="option_4">Column 4'+selectedText4+'</option><option value="5" '+selected5+' class="option_5">Column 5'+selectedText5+'</option><option value="6" '+selected6+' class="option_6">Column 6'+selectedText6+'</option><option value="7" '+selected7+' class="option_7">Column 7'+selectedText7+'</option><option value="8" '+selected8+' class="option_8">Column 8'+selectedText8+'</option>');
      jqThis.data("prev",jqThis.val());
   });

</script>
@endpush