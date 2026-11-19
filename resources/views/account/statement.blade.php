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
         <h2 class="mb-3">Bank Statement</h2>
         <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <select id="bank" class="form-control" style="max-width:380px;">
               @foreach($bankname as $bank1)
               <option value="{{$bank1->id}}" @if($selected_bank==$bank1->id) selected @endif>{{$bank1->name}}</option>
               @endforeach
            </select>
            <select class="form-control" style="max-width:190px;" id="financial_year">
            @foreach($year_options as $y)
               <option value="{{ $y }}" @if($selected_year==$y) selected @endif>{{ $y }}</option>
            @endforeach
         </select>
         @include('account._year_controls')
         </div>
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
                  <th>Account</th>
                  <th>Categorised</th>
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
          ajax: "{{ url('merchant/statement/'.$selected_year.'/'.$selected_bank) }}",
          createdRow: function(row, data){
              if(data.account === 'Not categorised'){
                  $(row).css({'color':'#8A958E','font-style':'italic'});
                  $('td', row).eq(2).append('<div style="font-size:11px;font-style:normal;color:#6B726D">Not categorised: not in the accounts yet, so it does not change the balance</div>');
              }
          },
          columns: [
              {data: 'DT_RowIndex', name: 'DT_RowIndex'},
              {data: 'st_date', name: 'st_date'},
              {data: 'remark', name: 'remark'},              
              {data: 'credit', name: 'credit'},
              {data: 'debit', name: 'debit'},
              {data: 'balance', name: 'balance'},
              {data: 'account', name: 'account', orderable: false, searchable: false},
              {data: 'how', name: 'how', orderable: false, searchable: false},
              
              // {
              //     data: 'action', 
              //     name: 'action', 
              //     orderable: true, 
              //     searchable: true
              // },
          ]
      });
      
    });

   function openStatement(){
      window.location = "{{ url('merchant/statement') }}/" + $("#financial_year").val() + "/" + $("#bank").val() + "{{ request('closed') ? '?closed=1' : '' }}";
   }
   $("#bank").change(openStatement);
   $("#financial_year").change(openStatement);
</script>
@endpush