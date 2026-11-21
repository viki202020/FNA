@extends('layout.master')
@section('title', 'New')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php include resource_path('views/billing/_labels.php'); @endphp
<h1 class="mb-1">{{ $L['New'] }}</h1>
<p class="text-muted mb-2">Saved as a draft first. It reaches the books only when you post it. @if($entity) Company state: <strong>{{ $entity->state }}</strong>: same state as the {{ strtolower($L['party']) }} gives CGST + SGST, a different state gives IGST. @endif</p>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="post" action="{{ url('merchant/billing/save') }}" style="max-width:1000px">
   @csrf
   <input type="hidden" name="kind" value="{{ $kind }}">
   <div class="row">
      <div class="col-md-4 mb-3"><label>{{ strtoupper($L['party']) }}</label>
         <select class="form-select" name="party_id" required>
            <option value="">Select</option>
            @foreach($parties as $p)<option value="{{ $p->id }}" @if(old('party_id')==$p->id) selected @endif>{{ $p->display_name ?: trim($p->first_name.' '.$p->last_name) }} @if($p->billing_state)({{ $p->billing_state }})@endif</option>@endforeach
         </select></div>
      <div class="col-md-2 mb-3"><label>DATE</label><input type="date" class="form-control" name="doc_date" required value="{{ old('doc_date', date('Y-m-d')) }}"></div>
      <div class="col-md-2 mb-3"><label>DUE DATE</label><input type="date" class="form-control" name="due_date" value="{{ old('due_date') }}"></div>
      @if(!$S)<div class="col-md-4 mb-3"><label>SUPPLIER'S BILL NUMBER</label><input class="form-control" name="reference" value="{{ old('reference') }}"></div>@endif
   </div>
   <div id="partyAlert" class="alert alert-danger" style="display:none;max-width:1000px"></div>
   <div class="fna-tablebox">
      <table class="table" id="lines">
         <thead><tr><th>{{ $S ? 'Item' : 'Description' }}</th>@if(!$S)<th>Expense account</th>@endif<th style="width:130px">Department</th><th style="width:80px">Qty</th><th style="width:110px">Rate</th><th style="width:80px">GST %</th><th style="width:120px;text-align:right">Amount</th></tr></thead>
         <tbody>
            @for($i=0;$i<5;$i++)
            <tr class="ln">
               <td>
                  @if($S)<select class="form-select pick" name="product_id[]"><option value="">(type below or pick)</option>@foreach($products as $pr)<option value="{{ $pr->id }}" data-rate="{{ $pr->price }}" data-tax="{{ $pr->tax_rate }}" data-hsn="{{ $pr->hsn_code }}" data-name="{{ $pr->name }}">{{ $pr->name }}</option>@endforeach</select>@endif
                  <input class="form-control mt-1" name="description[]" placeholder="Description">
                  <input type="hidden" name="hsn[]">
               </td>
               @if(!$S)<td><select class="form-select" name="head_id[]"><option value="">Select</option>@foreach($heads as $h)<option value="{{ $h->id }}">{{ $h->name }}</option>@endforeach</select></td>@endif
               <td><select class="form-select" name="department_id[]"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></td>
               <td><input class="form-control qty" name="qty[]" value="1"></td>
               <td><input class="form-control rate" name="rate[]"></td>
               <td><input class="form-control tax" name="tax_rate[]" value="18"></td>
               <td class="amt" style="text-align:right">0.00</td>
            </tr>
            @endfor
         </tbody>
      </table>
   </div>
   <div class="mt-2" style="text-align:right;font-size:14px">Total before tax <strong id="sub">0.00</strong> &middot; GST <strong id="gst">0.00</strong> &middot; Total <strong id="tot">0.00</strong></div>
   <div class="mt-2"><label>NOTES</label><input class="form-control" name="notes" value="{{ old('notes') }}"></div>
   <div class="mt-3 d-flex gap-2">
      <button class="btn btn-outline-primary" type="submit" name="action" value="draft">Save as draft</button>
      <button class="btn btn-primary" type="submit" name="action" value="post">Save and post</button>
   </div>
</form>
<div class="fna-foot-note">{{ $L['foot'] }}. Nothing is ever deleted: a posted {{ $L['one'] }} can only be reversed or voided.</div>
@endsection
@push('script-js')
<script>
$(function(){
   var issues = @json($issues);
   $('select[name=party_id]').on('change', function(){
      var list = issues[$(this).val()] || [], box = $('#partyAlert');
      if(!$(this).val() || !list.length){ box.hide(); return; }
      var h = '<strong>Details missing for this party:</strong>'; list.forEach(function(i){ h += '<div>&bull; ' + i.text + '</div>'; });
      h += '<div style="margin-top:4px">You can still save. Add the details under Customers so it is right next time.</div>'; box.html(h).show();
   }).trigger('change');
   function recalc(){
      var sub=0,gst=0;
      $('#lines tr.ln').each(function(){
         var q=parseFloat($(this).find('.qty').val())||0, r=parseFloat($(this).find('.rate').val())||0, t=parseFloat($(this).find('.tax').val())||0;
         var a=Math.round(q*r*100)/100; sub+=a; gst+=Math.round(a*t)/100; $(this).find('.amt').text(a.toFixed(2));
      });
      $('#sub').text(sub.toFixed(2)); $('#gst').text(gst.toFixed(2)); $('#tot').text((sub+gst).toFixed(2));
   }
   $('#lines').on('input','.qty,.rate,.tax',recalc);
   $('#lines').on('change','.pick',function(){
      var o=$(this).find(':selected'), tr=$(this).closest('tr');
      if(!o.val()) return;
      tr.find('[name="description[]"]').val(o.data('name')); tr.find('.rate').val(o.data('rate')); tr.find('.tax').val(o.data('tax')||18); tr.find('[name="hsn[]"]').val(o.data('hsn')); recalc();
   });
   recalc();
});
</script>
@endpush
