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
         @if($entity)
         <select class="form-control" style="width: 12%;" id="financial_year">
            @foreach($entity_financial_year as $year)
               <option value="{{$year->financial_year}}" @if($selected_year==$year->financial_year) selected @endif>{{$year->financial_year}}</option>
            @endforeach
            @if(!collect($entity_financial_year)->contains('financial_year',$entity->financial_year))
               <option value="{{$entity->financial_year}}" @if($selected_year==$entity->financial_year) selected @endif>{{$entity->financial_year}}</option>
            @endif
         </select>
         <p style="text-align: center;font-size: 26px;">{{$entity->name}}</p>
         <p style="text-align: center;font-size: 12px;">({{$entity->address}},{{$entity->city}},{{$entity->state}},{{$entity->pincode}})</p>
         <h5 class="h1 mb-2" style="text-align: center;">@php
            $chosen_year = collect($entity_financial_year)->firstWhere('financial_year',$selected_year);
            $period_start = $chosen_year ? $chosen_year->start_year : $entity->financial_year_start;
            $period_end = $chosen_year ? $chosen_year->end_year : $entity->financial_year_end;
         @endphp
         Trial Balance for the period  {{date('d-m-Y',strtotime($period_start))}} to {{date('d-m-Y',strtotime($period_end))}}</h5>
         @endif
         <?php date_default_timezone_set("Asia/Kolkata");   //India time (GMT+5:30)?>
         <p style="text-align: center;">(Generated on {{date('D-M-Y G:i:s')}})</p>
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
      window.location = "{{ url('merchant/trial-balance') }}/"+$(this).val();
  });
  $("#financial_year").change(function(){
     window.location = "{{url('merchant/trial-balance')}}/"+$(this).val();
  });
</script>
@endpush