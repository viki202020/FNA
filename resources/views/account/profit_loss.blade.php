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
         <select class="form-control" style="width: 12%;" id="financial_year">
            @foreach($entity_financial_year as $year)
               <option value="{{$year->financial_year}}" @if($selected_year==$year->financial_year) selected @endif>{{$year->financial_year}}</option>
            @endforeach
            <option value="{{$entity->financial_year}}" @if($selected_year==$entity->financial_year) selected @endif>{{$entity->financial_year}}</option>
         </select>
         <p style="text-align: center;font-size: 26px;">{{$entity->name}}</p>
         <p style="text-align: center;font-size: 12px;">({{$entity->address}},{{$entity->city}},{{$entity->state}},{{$entity->pincode}})</p>
         <h2 class="h1 mb-2" style="text-align: center;">Profit & Loss for the year {{$entity->financial_year}}</h2>            
         <div class="row">
            <div class="col-md-12 mb-3">                  
               @foreach($account_type_parents as $account_type_parent)
                  <ul><h3>{{$account_type_parent->name}} <span style="float: right">{{$account_type_parent->account_total}}</span></h3>
                     @foreach($account_type_parent->child as $child)
                        <li>{{$child->name}} <span style="float: right;">{{$child->head_total}}</span>
                           <ul>
                              @foreach($child->head as $head)
                                 <a href="ledger-by-head/{{$head->id}}" style="color:#0000EE"><li>{{$head->name}} <span style="float: right;margin-right: 20%;">{{$head->amount}}</span></li></a>
                              @endforeach
                           </ul>
                        </li>
                     @endforeach
                  </ul>
               @endforeach                     
            </div>
            @if($pl<0) <p style="color: red;"> @else <p style="color: green;"> @endif
            @if($pl<0) Loss @else Profit @endif for the year {{$entity->financial_year}} taken to Balance Sheet <span style="float: right;">@if($pl<0) {{abs($pl)}} DR @else {{$pl}} Cr @endif</span></p>
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
  $("#financial_year").change(function(){
     window.location = "{{url('merchant/profit-loss')}}/"+$(this).val();
  });
</script>
@endpush