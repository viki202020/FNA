@extends('layout.master')
@section('title', 'Journal voucher')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php
   $isEdit = (bool) $voucher;
   $old = old('head_id');
   if($old){ $rowsData = []; foreach($old as $i=>$h){ $rowsData[] = ['head_id'=>$h,'narration'=>old('narration')[$i] ?? '','department_id'=>old('department_id')[$i] ?? '','party_id'=>old('party_id')[$i] ?? '','debit'=>old('debit')[$i] ?? '','credit'=>old('credit')[$i] ?? '']; } }
   elseif(count($lines)){ $rowsData = []; foreach($lines as $l){ $rowsData[] = ['head_id'=>$l->head_id,'narration'=>$l->narration,'department_id'=>$l->department_id,'party_id'=>$l->party_id,'debit'=>$l->debit>0?$l->debit:'','credit'=>$l->credit>0?$l->credit:'']; } }
   else { $rowsData = array_fill(0, 3, ['head_id'=>'','narration'=>'','department_id'=>'','party_id'=>'','debit'=>'','credit'=>'']); }
@endphp
<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;margin-bottom:6px">
   <h1 style="margin:0">{{ $isEdit ? 'Edit draft ' . $voucher->voucher_no : 'New journal' }}</h1>
   <a href="{{ url('merchant/vouchers') }}" style="font-weight:700;text-decoration:none">&lsaquo; Back to vouchers</a>
</div>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="post" action="{{ url($isEdit ? 'merchant/journal/'.$voucher->id.'/save' : 'merchant/journal/save') }}" id="jform">@csrf
   <div class="row">
      <div class="col-md-2 mb-3"><label>DATE *</label><input type="date" class="form-control" name="voucher_date" required value="{{ old('voucher_date', $voucher->voucher_date ?? $defaultDate) }}"></div>
      <div class="col-md-2 mb-3"><label>VOUCHER NO *</label><input class="form-control" value="{{ $next }}" disabled><small class="text-muted">auto</small></div>
      <div class="col-md-4 mb-3"><label>REFERENCE</label><input class="form-control" name="reference" placeholder="Bill, cheque or file number" value="{{ old('reference', $voucher->reference ?? '') }}"></div>
   </div>
   <div class="fna-tablebox" style="overflow-x:auto"><table class="table" id="jt" style="min-width:980px">
      <thead><tr><th>Account</th><th>Narration *</th><th style="width:150px">Department</th><th style="width:170px">Customer / supplier</th><th style="width:120px;text-align:right">Debit</th><th style="width:120px;text-align:right">Credit</th></tr></thead>
      <tbody id="jbody">
      @foreach($rowsData as $r)
         <tr class="jr">
            <td><select class="form-select jhead" name="head_id[]"><option value="">Select</option>@foreach($heads as $h)<option value="{{ $h->id }}" data-dept="{{ $h->department_required }}" @if($r['head_id']==$h->id) selected @endif>{{ $h->name }}</option>@endforeach</select></td>
            <td><input class="form-control jnarr" name="narration[]" value="{{ $r['narration'] }}"></td>
            <td><select class="form-select jdept" name="department_id[]"><option value="">-</option>@foreach($departments as $d)<option value="{{ $d->id }}" @if($r['department_id']==$d->id) selected @endif>{{ $d->name }}</option>@endforeach</select><div class="jneed text-muted" style="font-size:11px;display:none">Required for this account</div></td>
            <td><select class="form-select" name="party_id[]"><option value="">-</option>@foreach($parties as $p)<option value="{{ $p->id }}" @if($r['party_id']==$p->id) selected @endif>{{ $p->display_name ?: trim($p->first_name.' '.$p->last_name) }}</option>@endforeach</select></td>
            <td><input class="form-control jdr" style="text-align:right" name="debit[]" value="{{ $r['debit'] }}"></td>
            <td><input class="form-control jcr" style="text-align:right" name="credit[]" value="{{ $r['credit'] }}"></td>
         </tr>
      @endforeach
      </tbody>
      <tfoot>
         <tr style="font-weight:700"><td colspan="4" style="text-align:right">Total</td><td style="text-align:right" id="tdr">0.00</td><td style="text-align:right" id="tcr">0.00</td></tr>
         <tr style="font-weight:700"><td colspan="4" style="text-align:right">Difference</td><td colspan="2" style="text-align:right" id="tdiff"></td></tr>
      </tfoot>
   </table></div>
   <div class="mt-2"><a href="#" id="addrow" class="fw-bold" style="text-decoration:none">+ Add new row</a></div>
   <div class="mt-3 d-flex gap-2 flex-wrap align-items-center">
      <button class="btn btn-primary" name="action" value="post" id="postbtn">Save and post</button>
      <button class="btn btn-outline-primary" name="action" value="draft">Save as draft</button>
      <a class="btn btn-outline-primary" href="{{ url('merchant/vouchers') }}">Cancel</a>
   </div>
</form>
<div class="fna-foot-note">Department is required on a row when its account is set to require it (Setup &rsaquo; Account heads). Narration is per row: a new row starts with the narration of the row above, and you can edit it. An unbalanced voucher can only be saved as a draft; posting needs a zero difference and every required field filled. Nothing is ever deleted.</div>
@endsection
@push('script-js')
<script>
$(function(){
   function num(v){ v=parseFloat(String(v).replace(/,/g,'')); return isNaN(v)?0:v; }
   function recalc(){
      var dr=0,cr=0;
      $('#jbody .jr').each(function(){
         dr+=num($(this).find('.jdr').val()); cr+=num($(this).find('.jcr').val());
         var need=$(this).find('.jhead option:selected').data('dept')==1;
         $(this).find('.jneed').toggle(need && !$(this).find('.jdept').val());
      });
      dr=Math.round(dr*100)/100; cr=Math.round(cr*100)/100;
      $('#tdr').text(dr.toFixed(2)); $('#tcr').text(cr.toFixed(2));
      var d=Math.round((dr-cr)*100)/100;
      $('#tdiff').text(d===0 ? (dr>0?'Balanced ✓':'') : '! '+Math.abs(d).toFixed(2)+(d>0?' more debit':' more credit'));
      $('#postbtn').prop('disabled', d!==0 || dr<=0);
   }
   $('#jbody').on('input change','input,select',recalc);
   $('#jbody').on('input','.jdr',function(){ if(num($(this).val())>0) $(this).closest('tr').find('.jcr').val(''); });
   $('#jbody').on('input','.jcr',function(){ if(num($(this).val())>0) $(this).closest('tr').find('.jdr').val(''); });
   $('#addrow').on('click',function(e){
      e.preventDefault();
      var last=$('#jbody .jr').last(), row=last.clone();
      row.find('input,select').val(''); row.find('.jneed').hide();
      row.find('.jnarr').val(last.find('.jnarr').val());           // a new row starts with the narration above
      var dr=0,cr=0; $('#jbody .jr').each(function(){dr+=num($(this).find('.jdr').val());cr+=num($(this).find('.jcr').val());});
      var d=Math.round((dr-cr)*100)/100; if(d>0) row.find('.jcr').val(d.toFixed(2)); else if(d<0) row.find('.jdr').val((-d).toFixed(2));
      $('#jbody').append(row); recalc();
   });
   recalc();
});
</script>
@endpush
