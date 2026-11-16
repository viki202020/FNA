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

            @if($entity)
            @if(!collect($entity_financial_year)->contains('financial_year',$entity->financial_year))
               <option value="{{$entity->financial_year}}" @if($selected_year==$entity->financial_year) selected @endif> {{$entity->financial_year}}</option>
            @endif
            @endif
         </select>
         <p style="text-align: center;font-size: 26px;"> @if($entity) {{$entity->name}} @endif</p>
         <p style="text-align: center;font-size: 12px;">@if($entity) ({{$entity->address}},{{$entity->city}},{{$entity->state}},{{$entity->pincode}}) @endif</p>
         <h1 class="h3 mb-2" style="text-align: center;">Balance Sheet as on {{$financial_year}}</h3>            
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
      window.location = "{{url('merchant/balance-sheet')}}/"+$(this).val();
   });
</script>
@endpush