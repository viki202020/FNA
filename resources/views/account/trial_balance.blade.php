@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-2 fna-report">
         @if($entity)
         <select class="form-control" style="width:auto;min-width:150px;" id="financial_year">
            @foreach($year_options as $y)
               <option value="{{ $y }}" @if($selected_year==$y) selected @endif>{{ $y }}</option>
            @endforeach
         </select>
         @include('account._year_controls')
         <p class="fna-title">{{$entity->name}}</p>
         <p class="fna-sub">({{$entity->address}},{{$entity->city}},{{$entity->state}},{{$entity->pincode}})</p>
         <h5 class="fna-title" style="font-size:16px">@php
            $chosen_year = collect($entity_financial_year)->firstWhere('financial_year',$selected_year);
            $period_start = $chosen_year ? $chosen_year->start_year : $entity->financial_year_start;
            $period_end = $chosen_year ? $chosen_year->end_year : $entity->financial_year_end;
         @endphp
         Trial Balance for the period  {{date('d-m-Y',strtotime($period_start))}} to {{date('d-m-Y',strtotime($period_end))}}</h5>
         @endif
         <?php date_default_timezone_set("Asia/Kolkata");   //India time (GMT+5:30)?>
         <p class="fna-sub">(Generated on {{date('D-M-Y G:i:s')}})</p>
         <!-- <p>Custom Period? <input type="date" id="from"></p> -->
         <div class="row">
            <div class="col-md-12 mb-3">
               <table class="table table-bordered">
                  <thead>
                     <tr>
                        <th>Account</th>
                        <th>Opening Balance</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Balance</th>
                     </tr>
                  </thead>
                  <tbody>
                     @foreach($heads as $head)
                        <tr>
                           <td>{{$head->name}}</td>
                           <td>{{$head->open_bal}}</td>
                           <td>{{$head->debit}}</td>
                           <td>{{$head->credit}}</td>
                           <td>{{$head->balance}}</td>
                        </tr>
                     @endforeach 
                     <tr>
                        <th>Total</th>
                        <th></th>
                        <th>{{$debit_total}}</th>
                        <th>{{$credit_total}}</th>
                        <th></th>
                     </tr>                     
                  </tbody>
               </table>
         <div style="margin:12px 0;padding:10px 14px;border-radius:6px;border:1px solid {{ abs($tb_difference) < 0.005 ? "#27ae60" : "#c0392b" }};background:{{ abs($tb_difference) < 0.005 ? "#eafaf1" : "#fdecea" }};color:{{ abs($tb_difference) < 0.005 ? "#1e8449" : "#922b21" }};font-weight:bold;">
   @if(abs($tb_difference) < 0.005)
      Balanced: total debits equal total credits.
   @else
      OUT BY {{ number_format(abs($tb_difference),2) }}: {{ $tb_difference > 0 ? "debits are higher than credits" : "credits are higher than debits" }}.
   @endif
</div>
                   
            </div>
         </div>
      </div>      
   </div>
</div>
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
  $("#from").change(function(){
      window.location = "{{ url('merchant/trial-balance') }}/"+$(this).val()+"{{ request('closed') ? '?closed=1' : '' }}";
  });
  $("#financial_year").change(function(){
     window.location = "{{url('merchant/trial-balance')}}/"+$(this).val();
  });
</script>
@endpush