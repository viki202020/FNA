@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4" style="overflow: scroll;">
         <h2 class="h5 mb-2">Account Ledger ({{$name}})</h2> 
         <p>Opening Balance : {{$open}}</p>       
         <div class="row">
            <table class="table table-bordered yajra-datatable">
               <thead>
                  <tr>
                     <th>Date</th>
                     <th style="width: 200px">Particular</th>
                     <th>Debit</th>
                     <th>Credit</th>
                     <th>Balance</th>
                     <th>Contra Head</th>
                  </tr>
               </thead>
               <tbody>
                  
                  @foreach($ledgers as $ledger)
                     <tr>
                        <td>{{$ledger->st_date}}</td>
                        <td>{{$ledger->remark}}</td>
                        <td>{{$ledger->debit}}</td>
                        <td>{{$ledger->credit}}</td>
                        <td>{{$ledger->balance}}</td>
                        <td>Bank-AU SFB-5398</td>
                     </tr>
                  @endforeach
               </tbody>
            </table>

               
         </div>
      </div>      
   </div>
</div>
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
   // 
</script>
@endpush