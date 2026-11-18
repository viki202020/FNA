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
         <select class="form-control" style="width:auto;min-width:150px;" id="financial_year">
            @foreach($year_options as $y)
               <option value="{{ $y }}" @if($selected_year==$y) selected @endif>{{ $y }}</option>
            @endforeach
         </select>
         @include('account._year_controls')
         <p class="fna-title"> @if($entity) {{$entity->name}} @endif</p>
         <p class="fna-sub">@if($entity) ({{$entity->address}},{{$entity->city}},{{$entity->state}},{{$entity->pincode}}) @endif</p>
         <h1 class="fna-title" style="font-size:16px">Balance Sheet as on {{$financial_year}}</h1>            
         <div class="row">
            <div class="col-md-12 mb-3">                  
               @foreach($account_type_parents as $account_type_parent)
                  <ul><h3>{{$account_type_parent->name}} <span style="float: right">{{$account_type_parent->account_total}}</span></h3>
                     @foreach($account_type_parent->child as $child)
                        <li>{{$child->name}} <span style="float: right;">{{$child->head_total}}</span>
                           <ul>
                              @foreach($child->head as $head)
                                 @if($head->id==24)
                                                                     <a href="{{URL('/merchant/statement/')}}/{{$selected_year}}" style="color:#0000EE"><li>{{$head->name}} <span style="float: right;margin-right: 20%;">{{$head->amount}}</span></li></a>
                                                                  @else
                                                                     <a href="{{URL('/merchant/ledger-by-head')}}/{{$head->id}}/{{$selected_year}}" style="color:#0000EE"><li>{{$head->name}} <span style="float: right;margin-right: 20%;">{{$head->amount}}</span></li></a>
                                                                  @endif
                                 
                              @endforeach
                           </ul>
                        </li>
                     @endforeach
                  </ul>
               @endforeach
               <div style="margin:12px 0;padding:10px 14px;border-radius:6px;border:1px solid {{ abs($bs_difference) < 0.005 ? "#27ae60" : "#c0392b" }};background:{{ abs($bs_difference) < 0.005 ? "#eafaf1" : "#fdecea" }};color:{{ abs($bs_difference) < 0.005 ? "#1e8449" : "#922b21" }};font-weight:bold;">
   @if(abs($bs_difference) < 0.005)
      Balanced: Assets equal Equity and Liabilities.
   @else
      OUT BY {{ number_format(abs($bs_difference),2) }}: {{ $bs_difference > 0 ? "Assets are higher than Equity and Liabilities" : "Equity and Liabilities are higher than Assets" }}.
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
   $("#financial_year").change(function(){
      window.location = "{{url('merchant/balance-sheet')}}/"+$(this).val()+"{{ request('closed') ? '?closed=1' : '' }}";
   });
</script>
@endpush