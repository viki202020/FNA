@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row">

   <div class="col-12 col-xl-12">
      <button class="btn btn-info" onclick="history.back()" style="
    margin-top: 18px;background-color: #c9143ffa;
">Back</button>
      <div class="card card-body border-0 shadow mb-4 mt-4" style="overflow: scroll;">
         <h2 class="mb-2"> Ledger for : {{$name}}</h2> 
         <div style="margin:6px 0 10px 0;">
            <label for="ledger_year"><strong>Financial year</strong></label>
            <select id="ledger_year" class="form-select" style="width:auto;display:inline-block;margin-left:8px;"
                    onchange="window.location='{{ url('/merchant/ledger-by-head/'.$head_id) }}/'+this.value">
               @foreach($year_options as $y)
                  <option value="{{ $y }}" @if($y==$selected_year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>
               @endforeach
            </select>
         </div>
         @include('account._year_controls_banner')
@if($open!=0)
            <p style="
       font-size: large;
       color: #e11d48;
       text-align: right;
   "></p>
@endif
                
         <div class="row">
            <table class="table table-bordered yajra-datatable">
               <thead>
                  <tr style="
    background-color: black;
    color: white;
    font-weight: 700;
    font-size: 13px;
">
                     <th>Date</th>
					 <th style="width: 10px">Voucher#</th>
                     <th style="width: 170px;word-wrap:break-word;">Narration</th>
					  
                     <th>Debit</th>
                     <th>Credit</th>
                     <th>Balance</th>
                     <th>Contra Head</th>
                  </tr>
               </thead>
               <tbody>
                  
                  @foreach($ledgers as $ledger)
                     <tr style="font-variant: all-caps;font-size: xx-large;">
                        <td>{{$ledger->st_date}}</td>
						      <td ><a href="javascript:void(0)" class="view_voucher_entry" data-voucher_no="{{$ledger->voucher_no}}">{{$ledger->voucher_no}}</a></td>
                        <td>{{$ledger->remark}}</td>
						  
                        <td style="text-align:right">{{$ledger->debit}}</td> 
                        <td style="text-align:right">{{$ledger->credit}}</td> 
                        <td style="text-align:right">{{$ledger->balance}}</td>
                        <td>@if(empty($ledger->voucher_no)) Bank entry @elseif(str_starts_with($ledger->voucher_no,'SBV-')) Bank upload @elseif(str_starts_with($ledger->voucher_no,'UJV')) Uploaded journal @elseif(str_starts_with($ledger->voucher_no,'MJV')) Manual journal @else Journal Entry @endif</td>
                     </tr>
                  @endforeach
               </tbody>
            </table>

               
         </div>
      </div>      
   </div>
</div>
<div class="modal fade" id="manuallyModal" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
      <div class="modal-content ">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               <div class="text-center text-md-center mb-4 mt-md-0">
                  <h1 class="mb-0 h4 voucher_title"></h1>
               </div> 
                  <div class="col-md-12 mb-3 voucher_entry">
                     
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
   $(".view_voucher_entry").click(function(){
      let voucher_no = $(this).attr('data-voucher_no');
      if(voucher_no==""){
         alert("Voucher no ?");
         return;
      }
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         url : "{{ url('merchant/voucher-entry') }}",
         method : "POST",
         data : "voucher_no="+voucher_no,
         success : function(res){
            let statement_row = "<table class='table'><thead><tr style='background-color:#042e46;color:white;'><th>Date</th><th>Remark</th><th>Account Head</th><th>Debit</th><th>Credit</th></tr></thead><tbody>";
            let debit_total = 0; let credit_total = 0;        
            res.data.forEach(function(e){                   
               statement_row+='<tr><td>'+e.st_date+'</td><td>'+e.remark+'</td><td>'+e.head+'</td><td style="text-align:right">'+e.debit+'</td><td style="text-align:right">'+e.credit+'</td></tr>';
               if(e.debit!=""){
                  debit_total = parseFloat(debit_total) + parseFloat(e.debit);
               }
               if(e.credit!=""){
                  credit_total = parseFloat(credit_total) + parseFloat(e.credit);
               }
               
            });
            statement_row+='<tr style="background-color:#0d79b5;color:white;""><th></th><th></th><th>Total</th><th style="text-align:right">'+debit_total+'</th><th style="text-align:right">'+credit_total+'</th></tr>';
            $(".voucher_title").html("Voucher No. "+voucher_no);
            statement_row+="</tbody></table>";
            $(".voucher_entry").html(statement_row);
            $("#manuallyModal").modal("toggle");
         }
      });
   });
</script>
@endpush