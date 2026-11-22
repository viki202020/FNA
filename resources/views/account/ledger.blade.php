@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<style>
    .ledger-page, .ledger-page table, .ledger-page td, .ledger-page th { color:#1f2937; }
    .ledger-page table.dataTable thead th { background:#111827; color:#fff !important; font-size:13px; font-weight:600; padding:10px 12px; border-bottom:0; }
    .ledger-page table.dataTable tbody td { font-size:15px; padding:9px 12px; color:#111827; background:#fff; }
    .ledger-page table.dataTable tbody tr:nth-child(even) td { background:#f6f8fb; }
    .ledger-page table.dataTable tbody tr:hover td { background:#e8f0fe; }
    .ledger-page td.num { text-align:right; font-variant-numeric:tabular-nums; font-weight:500; }
    .ledger-page .dataTables_wrapper, .ledger-page .dataTables_info, .ledger-page label { color:#111827; font-size:14px; }
    a.ledger-link { color:#0b5ed7; font-weight:600; text-decoration:none; }
    a.ledger-link:hover { text-decoration:underline; }
    table td {
        word-break: break-word;
        vertical-align: top;
        white-space: normal !important;
    }
</style>
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4 ledger-page" style="overflow: auto;">
         <h2 class="mb-4">Ledgers</h2>         
         <div class="row">
            <div class="col-3 col-xl-3">
               <input type="checkbox" id="empty_head"> Show Empty Heads
            </div>
            <div class="col-3 col-xl-3">
               <select class="form-select" id="date" aria-label="Financial year">
                  @foreach($year_options as $y)
                     <option value="{{ $y }}" @if($y==$selected_year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>
                  @endforeach
               </select>
            </div>            
         </div>
         <br>
         <table class="table table-bordered yajra-datatable">
            <thead>
               <tr>
                  <th>Account head</th>
                  <th style="text-align:right">Entries</th>
                  <th style="text-align:right">Debit</th>
                  <th style="text-align:right">Credit</th>
                  <th style="text-align:right">Balance</th>
                  <th>Dr/Cr</th>
               </tr>
            </thead>
            <tbody>
            </tbody>
         </table>
         <table class="table table-bordered empty_head_list" style="display:none">
            <thead>
               <tr>
                  <th>Empty Head Name</th>                  
               </tr>
            </thead>
            <tbody>
            </tbody>
         </table>
      </div>
   </div>
</div>
<div class="modal fade" id="viewAllModal" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content ">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
               <div class="text-center text-md-center mb-4 mt-md-0">
                  <h1 class="mb-0 h4">Head Entry</h1>
               </div> 
               <table class="table table-bordered head_data">
                  <thead>
                     <tr>
                        <th style="width: 18%;">Date</th>
                        <th>Particular</th>
                        <th style="width: 16%;">Debit</th>
                        <th style="width: 16%;">Credit</th>
                     </tr>
                  </thead>
                  <tbody></tbody>
               </table>
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
   //var select_head = "";
   $(function () {
      var table = $('.yajra-datatable').DataTable({
          processing: true,
          serverSide: true,
          "iDisplayLength": 100,
          ajax: { url: "{{ route('ledgers.list') }}", data: function(d){ d.year = $('#date').val(); } },
          columns: [
              {data: 'hname', name: 'head.name'},
              {data: 'cnt', name: 'cnt', className: 'num'},
              {data: 'debit_fmt', name: 'debitTotal', className: 'num', orderable:false, searchable:false},
              {data: 'credit_fmt', name: 'creditTotal', className: 'num', orderable:false, searchable:false},
              {data: 'balance_fmt', name: 'net', className: 'num', orderable:false, searchable:false},
              {data: 'amount_type', name: 'amount_type', orderable:false, searchable:false},
          ]
      });
      $('#date').on('change', function(){ table.ajax.reload(); $("#empty_head").prop('checked', false); $(".empty_head_list").hide(); });
   });
   $("#empty_head").click(function(){
      if($(this).prop('checked')==true){
         $(".empty_head_list").show();
         var head_html = "";
         var head_arr = [];
         $(".view_all").each(function(){
            head_arr.push($(this).html());
         });         
         $.ajax({
            headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url : "get-head",
            method : "post",
            data : "type=All",
            success : function(res){               
               if(res[0].head.length>0){              
                  res[0].head.forEach(function(e){                     
                     if(head_arr.indexOf(e.name)==-1){
                        head_html+='<tr><td>'+esc(e.name)+'</td></tr>';
                     }              
                     
                  });
               }
               $(".empty_head_list tbody").html(head_html);
            }
         });
      }else{
         $(".empty_head_list").hide();
      }
   });
   $(document).on("click",".view_all",function(){
      let id = $(this).attr("data-id");
      window.location = "{{ url('/merchant/ledger-by-head') }}/"+id+"/"+encodeURIComponent($("#date").val());
      // $.ajax({
      //    headers: {
      //       'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
      //    },
      //    url : "ledger-by-head",
      //    method : "post",
      //    data : "id="+id,
      //    success : function(res){      
      //       let head_html = "";   
      //       console.log(res);      
      //       if(res.length>0){              
      //          res.forEach(function(e){         
      //             head_html+='<tr><td>'+e.st_date+'</td><td>'+e.remark+'</td><td>'+e.debit+'</td><td>'+e.credit+'</td></tr>';
      //          });
      //       }
      //       $(".head_data tbody").html(head_html);
      //       $("#viewAllModal").modal('toggle');
      //    }
      // });
   });
</script>
@endpush