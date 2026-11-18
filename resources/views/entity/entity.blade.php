@extends('layout.master')
@section('title', 'Company')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php
   $c = $company;
   $v = function($field, $default = '') use ($c){ return old($field, $c ? ($c->{$field === 'gst' ? 'gst_number' : $field} ?? $default) : $default); };
   $states = ['Andhra Pradesh'=>'37','Arunachal Pradesh'=>'12','Assam'=>'18','Bihar'=>'10','Chhattisgarh'=>'22','Delhi'=>'07','Goa'=>'30','Gujarat'=>'24','Haryana'=>'06','Himachal Pradesh'=>'02','Jharkhand'=>'20','Karnataka'=>'29','Kerala'=>'32','Madhya Pradesh'=>'23','Maharashtra'=>'27','Odisha'=>'21','Punjab'=>'03','Rajasthan'=>'08','Tamil Nadu'=>'33','Telangana'=>'36','Uttar Pradesh'=>'09','Uttarakhand'=>'05','West Bengal'=>'19','New South Wales'=>'','Queensland'=>'','South Australia'=>'','Tasmania'=>'','Victoria'=>'','Western Australia'=>''];
   $curState = $v('state');
@endphp
<style>
   .fna-co label{display:block;margin-bottom:3px}
   .fna-co .fna-note{font-size:12px;color:var(--fna-muted);line-height:1.6;max-width:900px}
   #gst_msg{font-size:12px;font-weight:700;margin-top:2px}
</style>
<h1 class="mb-1">Company</h1>
<p class="text-muted mb-2" style="max-width:900px">@if($c) Your company details, used on invoices and for GST. @else Set up your company. This is used to keep you GST compliant and to send invoices. @endif</p>
<div class="fna-co" style="max-width:900px">
   <form action="{{ url('merchant/add-entity') }}" method="post">
      @csrf
      <input type="hidden" name="entity_id" value="{{ $c ? $c->id : '' }}">
      <div class="row">
         <div class="col-md-6 mb-3">
            <label for="name">COMPANY NAME</label>
            <input class="form-control" type="text" name="name" id="name" required value="{{ $v('name') }}">
            @error('name')<div class="fna-err">{{ $message }}</div>@enderror
         </div>
         <div class="col-md-6 mb-3 gst_div">
            <label for="gst">GSTIN (INDIA) OR ABN (AUSTRALIA)</label>
            <input class="form-control" type="text" name="gst" id="gst" placeholder="e.g. 29APPCK7465F1Z1" value="{{ $v('gst') }}">
            <div id="gst_msg"></div>
            @error('gst')<div class="fna-err" style="font-weight:700">{{ $message }}</div>@enderror
            <small><input type="checkbox" id="check_gst"> <label for="check_gst" style="display:inline;font-weight:500">I don't have a tax number</label></small>
         </div>
         <div class="col-md-3 mb-3 FrequencyDiv">
            <label for="frequency">RETURN FREQUENCY</label>
            <select class="form-select" name="frequency" id="frequency">
               <option value="">Select</option>
               @foreach(['Monthly','Quarterly','Yearly'] as $f)<option value="{{ $f }}" @if($v('frequency')==$f) selected @endif>{{ $f }}</option>@endforeach
            </select>
         </div>
         <div class="col-md-9 mb-3">
            <label for="address">ADDRESS</label>
            <input class="form-control" type="text" name="address" id="address" required value="{{ $v('address') }}">
         </div>
         <div class="col-md-3 mb-3">
            <label for="city">CITY</label>
            <input class="form-control" type="text" name="city" id="city" required value="{{ $v('city') }}">
         </div>
         <div class="col-md-3 mb-3">
            <label for="state">STATE</label>
            <select class="form-select" name="state" id="state" required>
               @if($curState !== '' && !array_key_exists($curState,$states))<option selected>{{ $curState }}</option>@endif
               @foreach($states as $name=>$code)<option value="{{ $name }}" data-code="{{ $code }}" @if($curState==$name) selected @endif>{{ $name }}</option>@endforeach
            </select>
         </div>
         <div class="col-md-2 mb-3">
            <label for="pincode">PINCODE</label>
            <input class="form-control" type="text" name="pincode" id="pincode" required value="{{ $v('pincode') }}">
         </div>
         <div class="col-md-4 mb-3">
            <label for="mobile">PHONE</label>
            <input class="form-control" type="text" name="mobile" id="mobile" required value="{{ $v('mobile') }}">
         </div>
         <div class="col-md-6 mb-3">
            <label for="email">EMAIL</label>
            <input class="form-control" type="email" name="email" id="email" required value="{{ $v('email') }}">
         </div>
         <div class="col-md-3 mb-3">
            <label for="start_year">FINANCIAL YEAR FROM</label>
            <input type="date" name="start_year" id="start_year" class="form-control" value="{{ $c ? $c->financial_year_start : $financial_year_start }}" @if($c) disabled @endif>
         </div>
         <div class="col-md-3 mb-3">
            <label for="end_year">FINANCIAL YEAR TO</label>
            <input type="date" name="end_year" id="end_year" class="form-control" value="{{ $c ? $c->financial_year_end : $financial_year_end }}" @if($c) disabled @endif>
         </div>
      </div>
      <div class="mt-1 d-flex align-items-center gap-3">
         <button class="btn btn-gray-800" type="submit">Save company</button>
         @if(session('success_msg'))<span style="color:var(--fna-green);font-weight:700">{{ session('success_msg') }}</span>@endif
      </div>
   </form>
</div>
<div class="fna-foot-note fna-note">The tax number is checked as you type. The state code in a GSTIN (for example 06) should match the state, because the company's state decides CGST + SGST or IGST on invoices. Indian companies usually run April to March, Australian companies July to June. Closing the year needs every entry categorised and every voucher posted: see <a href="{{ url('merchant/manage-book') }}">Close book</a>.</div>
@endsection
@push('script-js')
<script type="text/javascript">
   $(function(){
      var chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      function gstinOk(v){
         if(!/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/.test(v)) return false;
         var sum = 0;
         for(var i=0;i<14;i++){ var p = chars.indexOf(v[i]) * (i%2==0 ? 1 : 2); sum += Math.floor(p/36) + (p%36); }
         return v[14] === chars[(36 - (sum % 36)) % 36];
      }
      function abnOk(v){
         if(!/^[0-9]{11}$/.test(v)) return false;
         var w=[10,1,3,5,7,9,11,13,15,17,19], d=v.split('').map(Number); d[0]-=1;
         var s=0; for(var i=0;i<11;i++) s+=d[i]*w[i]; return s%89===0;
      }
      function check(){
         var v = $('#gst').val().replace(/\s+/g,'').toUpperCase(), m = $('#gst_msg');
         if(!v){ m.text('').css('color',''); return; }
         if(gstinOk(v)){
            var code = v.substr(0,2), opt = $('#state option').filter(function(){ return $(this).data('code') == code; });
            var chosen = $('#state option:selected').data('code');
            if(opt.length && chosen && chosen != code){ m.text('✓ valid GSTIN, but its state code ('+code+') does not match the state chosen').css('color','#4B5563'); }
            else { m.text('✓ valid GSTIN').css('color','#0B5A2E'); }
         } else if(abnOk(v)){ m.text('✓ valid ABN').css('color','#0B5A2E'); }
         else { m.text('! not a valid GSTIN (15 characters) or ABN (11 digits)').css('color','#013602'); }
      }
      $('#gst').on('input blur', check); $('#state').on('change', check); check();
      $('#check_gst').on('click', function(){
         var none = $(this).prop('checked');
         $('#gst').prop('required', !none).prop('disabled', none);
         if(none){ $('#gst').val(''); check(); $('.FrequencyDiv').hide(); } else { $('.FrequencyDiv').show(); }
      });
      if(!$('#gst').val()){ /* no number yet: leave field editable */ }
   });
</script>
@endpush
