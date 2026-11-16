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
      <button class="btn btn-info" onclick="history.back()">Back</button>
      <div class="card card-body border-0 shadow mb-4 mt-4" style="overflow: scroll;">
         <h2 class="mb-4" style="text-align: center;">Bank Statement</h2>
         <select id="bank">
            @foreach($bankname as $bank1)
            <option value="{{$bank1->id}}">{{$bank1->name}}</option>
            @endforeach
         </select>
         <p style="text-align:right;">Opening Balance : {{$bank}}</p>
         <table class="table table-bordered yajra-datatable">
            <thead>
               <tr>
                  <th>No</th>
                  <th>Date</th>
                  <th>Particular</th>
                  <th>Debit</th>
                  <th>Credit</th>
                  <th>Balance</th>
                  <th>Head</th>
               </tr>
            </thead>
            <tbody>
            </tbody>
         </table>
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
   var select_head = "";
   $(function () {      
      var table = $('.yajra-datatable').DataTable({
          processing: true,
          serverSide: true,
          ajax: "{{ route('statement.list') }}",
          columns: [
              {data: 'DT_RowIndex', name: 'DT_RowIndex'},
              {data: 'st_date', name: 'st_date'},
              {data: 'remark', name: 'remark'},              
              {data: 'credit', name: 'credit'},
              {data: 'debit', name: 'debit'},
              {data: 'balance', name: 'balance'},
              {data: 'name', name: 'name'},
              
              // {
              //     data: 'action', 
              //     name: 'action', 
              //     orderable: true, 
              //     searchable: true
              // },
          ]
      });
      
    });

   $("#bank").change(function(){
      window.location = "statement/"+$(this).val();
   });
</script>
@endpush