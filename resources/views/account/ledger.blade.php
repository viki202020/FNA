@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
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
         <h2 class="mb-4">Ledgers</h2>         
         <div class="row">
            <div class="col-3 col-xl-3">
               <input type="checkbox" id="empty_head"> Show Empty Heads
            </div>
            <div class="col-3 col-xl-3">
               <select class="form-control" id="date">
                  <!--<option value="1">Since Start</option>-->
                  <option value="2">Current Year</option>
                  <!--<option value="3">Current Month</option>-->
               </select>
            </div>            
         </div>
         <br>
         <table class="table table-bordered yajra-datatable">
            <thead>
               <tr>
                  <th>Acct_head_name</th>
                  <th>#of entries</th>
                  <th>Amount</th>
                  <th>Dr/Cr</th>
               </tr>
            </thead>
            <tbody style="
    background-color: #010b37;
    color: #146005;
">
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
          ajax: "{{ route('ledgers.list') }}",
          columns: [
              {data: 'hname', name: 'hname'},
              {data: 'cnt', name: 'cnt'},
              {data: 'debitAmount', name: 'debitAmount'},
              {data: 'amount_type', name: 'amount_type'},
              
          ]
      });      
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
                        head_html+='<tr><td>'+e.name+'</td></tr>';
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
      window.location = "ledger-by-head/"+id;
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