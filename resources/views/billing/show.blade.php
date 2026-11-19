@extends('layout.master')
@section('title', 'Invoice')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $kind = $doc->kind; @endphp
@php include resource_path('views/billing/_labels.php'); @endphp
<div style="display:flex;flex-wrap:wrap;justify-content:space-between;align-items:center;margin-bottom:6px">
   <h1 style="margin:0">{{ $doc->number ?: 'Draft '.$L['one'] }} <span class="fna-chip {{ $chipClass[$doc->status] }}" style="vertical-align:middle">{{ $chipText[$doc->status] }}</span></h1>
   <a href="{{ url($L['list']) }}">&lsaquo; Back to {{ strtolower($L['title']) }}</a>
</div>
@if(session('success_msg'))<div class="alert alert-success">{{ session('success_msg') }}</div>@endif
@if(session('error_msg'))<div class="alert alert-danger">{{ session('error_msg') }}</div>@endif
<p class="text-muted mb-2">{{ $L['party'] }}: <strong style="color:var(--fna-ink)">{{ $doc->party_name }}</strong> &middot; Date {{ date('d/m/Y', strtotime($doc->doc_date)) }} &middot; Due {{ $doc->due_date ? date('d/m/Y', strtotime($doc->due_date)) : '-' }} &middot; {{ $doc->supply==='intra' ? 'CGST + SGST' : 'IGST' }}@if($doc->reference) &middot; Their number {{ $doc->reference }}@endif</p>
<div class="fna-tablebox" style="max-width:1000px">
   <table class="table">
      <thead><tr><th>Description</th>@if(!$S)<th>Account</th>@endif<th style="text-align:right">Qty</th><th style="text-align:right">Rate</th><th style="text-align:right">GST %</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
         @foreach($lines as $l)
            <tr><td>{{ $l->description }}</td>@if(!$S)<td>{{ $heads[$l->head_id] ?? '' }}</td>@endif<td style="text-align:right">{{ rtrim(rtrim($l->qty,'0'),'.') }}</td><td style="text-align:right">{{ number_format($l->rate,2) }}</td><td style="text-align:right">{{ $l->tax_rate+0 }}</td><td style="text-align:right">{{ number_format($l->amount,2) }}</td></tr>
         @endforeach
         <tr><td colspan="{{ $S?4:5 }}" style="text-align:right">Total before tax</td><td style="text-align:right">{{ number_format($doc->subtotal,2) }}</td></tr>
         @if($doc->igst>0)<tr><td colspan="{{ $S?4:5 }}" style="text-align:right">IGST</td><td style="text-align:right">{{ number_format($doc->igst,2) }}</td></tr>@else
         <tr><td colspan="{{ $S?4:5 }}" style="text-align:right">CGST</td><td style="text-align:right">{{ number_format($doc->cgst,2) }}</td></tr>
         <tr><td colspan="{{ $S?4:5 }}" style="text-align:right">SGST</td><td style="text-align:right">{{ number_format($doc->sgst,2) }}</td></tr>@endif
         <tr><td colspan="{{ $S?4:5 }}" style="text-align:right;font-weight:700">Total</td><td style="text-align:right;font-weight:700">{{ number_format($doc->total,2) }}</td></tr>
      </tbody>
   </table>
</div>
@if($doc->status==='draft')
   <form method="post" action="{{ url('merchant/billing/'.$doc->id.'/post') }}" class="mt-3">@csrf<button class="btn btn-primary">Post to the books</button></form>
@else
   <p class="mt-3">Posted as voucher <a class="fw-bold" href="{{ url('merchant/voucher/'.$doc->number) }}">{{ $doc->number }}</a>. Settled {{ number_format($doc->settled,2) }} of {{ number_format($doc->total,2) }}.</p>
@endif
@if(in_array($doc->status,['open','part']))
   <div class="fna-section-title">{{ $S ? 'Payment received' : 'Payment sent' }}</div>
   @if($errors->any())<div class="alert alert-danger" style="max-width:1000px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
   <form method="post" action="{{ url('merchant/billing/'.$doc->id.'/note') }}" class="d-flex flex-wrap gap-2 align-items-end" style="max-width:1000px" id="payform">@csrf
      <div><label>MODE</label><select class="form-select" name="mode" id="pmode">@foreach(['Cheque','NEFT','RTGS','IMPS','UPI','Cash','Other'] as $m)<option>{{ $m }}</option>@endforeach</select></div>
      <div><label>{{ $S ? 'DATE RECEIVED' : 'DATE PAID' }} (CHEQUE DATE)</label><input type="date" class="form-control" name="pay_date" required value="{{ date('Y-m-d') }}"></div>
      <div><label>AMOUNT</label><input class="form-control" name="amount" required value="{{ number_format($doc->total-$doc->settled,2,'.','') }}"></div>
      <div class="chq"><label>CHEQUE NO.</label><input class="form-control" name="cheque_no"></div>
      <div class="chq"><label>BANK ACCOUNT</label><select class="form-select" name="bank_head_id"><option value="">Select</option>@foreach($banks as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
      <div class="nochq" style="display:none"><label>UTR / REFERENCE</label><input class="form-control" name="reference"></div>
      <button class="btn btn-outline-primary">Record</button>
   </form>
   <div class="fna-foot-note" style="margin-top:4px;padding-top:0" id="payhelp"></div>
   <script>
   (function(){var m=document.getElementById('pmode');function f(){var c=m.value==='Cheque';
     document.querySelectorAll('#payform .chq').forEach(function(e){e.style.display=c?'':'none';});
     document.querySelectorAll('#payform .nochq').forEach(function(e){e.style.display=c?'none':'';});
     document.getElementById('payhelp').textContent=c?'A cheque is posted on this date and goes into the cheque register. It stays Outstanding until the bank line is matched.':'This is only a note. Nothing is booked until the bank line arrives and you match it on Receipts to match.';}
     m.onchange=f;f();})();
   </script>
@endif
@php
   $isReceipt = $S;
   $rows = [];
   foreach($notes as $n){ $rows[] = ['type'=>'note','ref'=>$n->id,'date'=>$n->pay_date,'what'=>'Cheque '.$n->cheque_no,'amount'=>$n->amount,'mode'=>'Cheque','by'=>$n->created_by,
      'status'=>['outstanding'=>'Outstanding','cleared'=>'Cleared','dishonoured'=>'Dishonoured','reversed'=>'Reversed'][$n->status] ?? $n->status,'can'=>$n->status==='outstanding','why'=>$n->reason]; }
   $seen = [];
   foreach($lineSettlements as $s2){ $rows[] = ['type'=>'line','ref'=>$s2->statement_id,'date'=>$s2->st_date,'what'=>'Bank line '.$s2->voucher_no.': '.$s2->remark,'amount'=>$s2->amount,'mode'=>'Bank','by'=>$s2->chosen_by,
      'status'=>$s2->reversed_at ? 'Reversed' : 'Matched','can'=>!$s2->reversed_at,'why'=>null]; }
   $reasonsFor = function($mode) use ($isReceipt){ return \App\Support\ReversalReasons::list($isReceipt, $mode); };
@endphp
@if(count($rows))
   <div class="fna-section-title">Payments</div>
   <div class="fna-tablebox" style="max-width:1000px"><table class="table"><thead><tr><th>Date</th><th>Payment</th><th style="text-align:right">Amount</th><th>Status</th><th></th></tr></thead><tbody>
   @foreach($rows as $r)
      <tr><td>{{ date('d/m/Y', strtotime($r['date'])) }}</td><td>{{ $r['what'] }}@if($r['why'])<div class="text-muted" style="font-size:12px">Reason: {{ $r['why'] }}</div>@endif</td><td style="text-align:right">{{ number_format($r['amount'],2) }}</td>
         <td><span class="fna-chip {{ in_array($r['status'],['Reversed','Dishonoured']) ? 'void' : ($r['status']==='Outstanding' ? 'waiting' : 'paid') }}">{{ $r['status'] }}</span></td>
         <td>@if($r['can'] && (int)$r['by']===(int)$me)<a href="#" class="fw-bold rev-open" data-type="{{ $r['type'] }}" data-ref="{{ $r['ref'] }}" data-mode="{{ $r['mode'] }}" data-what="{{ $r['what'] }}" data-amount="{{ number_format($r['amount'],2) }}" style="text-decoration:none">Reverse &rsaquo;</a>@elseif($r['can'])<span class="text-muted" style="font-size:12px">Only {{ $isReceipt ? 'the person who recorded it' : 'the person who recorded it' }} can reverse</span>@endif</td></tr>
   @endforeach
   </tbody></table></div>
   <div class="fna-foot-note" style="margin-top:4px;padding-top:0">A reversal keeps the original and records who reversed it, why, and when. A cheque that has already cleared the bank cannot be reversed from here.</div>
@endif
<div class="fna-modal-scrim" id="revscrim" style="position:fixed;inset:0;background:rgba(1,54,2,.35);display:none;align-items:flex-start;justify-content:center;padding:60px 16px;z-index:2000">
   <div style="background:#fff;border-radius:12px;width:100%;max-width:560px;padding:16px 18px;box-shadow:0 10px 40px rgba(0,0,0,.25)">
      <div style="display:flex;justify-content:space-between;align-items:center"><h1 style="font-size:18px;margin:0">Reverse this {{ $isReceipt ? 'receipt' : 'payment' }}</h1><a href="#" id="revx" style="font-weight:700;text-decoration:none">Close &#10005;</a></div>
      <p class="text-muted mt-2" id="revwhat"></p>
      <form method="post" action="{{ url('merchant/billing/'.$doc->id.'/reverse') }}">@csrf
         <input type="hidden" name="type" id="revtype"><input type="hidden" name="ref" id="revref">
         <div id="revdatebox" style="margin-bottom:8px"><label>REVERSAL DATE</label><input type="date" class="form-control" name="rev_date" value="{{ \App\Http\Controllers\PaymentReversalController::defaultDate() }}" style="max-width:180px"><small class="text-muted">The reversal is booked on this date. It must be in the open financial year.</small></div>
         <label>REASON</label>
         <select class="form-select" name="reason_code" id="revreason" required></select>
         <div id="revtextbox" style="display:none;margin-top:8px"><label>EXPLAIN (REQUIRED FOR "OTHERS")</label><textarea class="form-control" name="reason_text" rows="2" placeholder="A short, specific reason"></textarea></div>
         <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Reverse</button><a href="#" class="btn btn-outline-primary" id="revcancel">Cancel</a></div>
      </form>
   </div>
</div>
<script>
(function(){
   var lists={Cheque:@json($reasonsFor('Cheque')),Bank:@json($reasonsFor('Bank'))};
   var sc=document.getElementById('revscrim'),sel=document.getElementById('revreason'),box=document.getElementById('revtextbox');
   function fill(mode){sel.innerHTML='<option value="">Select a reason</option>';var l=lists[mode]||lists.Bank;for(var k in l){var o=document.createElement('option');o.value=k;o.textContent=l[k];sel.appendChild(o);}box.style.display='none';}
   sel.onchange=function(){box.style.display=sel.value==='OTHERS'?'':'none';};
   [].forEach.call(document.querySelectorAll('.rev-open'),function(a){a.onclick=function(e){e.preventDefault();
      document.getElementById('revtype').value=a.dataset.type;document.getElementById('revref').value=a.dataset.ref;
      document.getElementById('revwhat').textContent=a.dataset.what+', '+a.dataset.amount;fill(a.dataset.mode);document.getElementById('revdatebox').style.display=a.dataset.type==='note'?'':'none';sc.style.display='flex';};});
   function close(e){if(e)e.preventDefault();sc.style.display='none';}
   document.getElementById('revx').onclick=close;document.getElementById('revcancel').onclick=close;
})();
</script>
@if($doc->status!=='void' && (int)$doc->created_by===(int)$me)
   <div class="mt-3"><a href="#" id="voidopen" class="btn btn-outline-primary">Void this {{ $L['one'] }}</a>
   @if($doc->settled>0.004)<span class="text-muted" style="font-size:12px;margin-left:8px">Reverse its payments first.</span>@endif</div>
@endif
@if($doc->status==='void')
   <p class="mt-3"><span class="fna-chip void">Void</span> {{ $doc->void_reason }} <span class="text-muted">&middot; {{ date('d/m/Y', strtotime($doc->voided_at)) }}</span></p>
@endif
<div id="voidscrim" style="position:fixed;inset:0;background:rgba(1,54,2,.35);display:none;align-items:flex-start;justify-content:center;padding:60px 16px;z-index:2000">
   <div style="background:#fff;border-radius:12px;width:100%;max-width:560px;padding:16px 18px;box-shadow:0 10px 40px rgba(0,0,0,.25)">
      <div style="display:flex;justify-content:space-between;align-items:center"><h1 style="font-size:18px;margin:0">Void {{ $doc->number ?: 'this draft' }}</h1><a href="#" id="voidx" style="font-weight:700;text-decoration:none">Close &#10005;</a></div>
      <p class="text-muted mt-2">The {{ $L['one'] }} stays visible and is removed from the reports. This cannot be undone.</p>
      <form method="post" action="{{ url('merchant/billing/'.$doc->id.'/void') }}">@csrf
         @if($doc->voucher_id)<div style="margin-bottom:8px"><label>VOID DATE</label><input type="date" class="form-control" name="void_date" value="{{ \App\Http\Controllers\PaymentReversalController::defaultDate() }}" style="max-width:180px"></div>@endif
         <label>REASON</label>
         <select class="form-select" name="reason_code" id="voidreason" required><option value="">Select a reason</option>@foreach(\App\Support\ReversalReasons::voidList() as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select>
         <div id="voidtext" style="display:none;margin-top:8px"><label>EXPLAIN (REQUIRED FOR "OTHERS")</label><textarea class="form-control" name="reason_text" rows="2" placeholder="A short, specific reason"></textarea></div>
         <div class="mt-3 d-flex gap-2"><button class="btn btn-primary">Void</button><a href="#" class="btn btn-outline-primary" id="voidcancel">Cancel</a></div>
      </form>
   </div>
</div>
<script>
(function(){var o=document.getElementById('voidopen');if(!o)return;var sc=document.getElementById('voidscrim');
 o.onclick=function(e){e.preventDefault();sc.style.display='flex';};
 function c(e){if(e)e.preventDefault();sc.style.display='none';}document.getElementById('voidx').onclick=c;document.getElementById('voidcancel').onclick=c;
 document.getElementById('voidreason').onchange=function(){document.getElementById('voidtext').style.display=this.value==='OTHERS'?'':'none';};})();
</script>
<div class="fna-foot-note">{{ $L['foot'] }}. Nothing is ever deleted: a posted {{ $L['one'] }} can only be reversed or voided.</div>
@endsection
