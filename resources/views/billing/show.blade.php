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
   <form method="post" action="{{ url('merchant/billing/'.$doc->id.'/note') }}" class="d-flex flex-wrap gap-2 align-items-end" style="max-width:1000px">@csrf
      <div><label>DATE</label><input type="date" class="form-control" name="pay_date" required value="{{ date('Y-m-d') }}"></div>
      <div><label>AMOUNT</label><input class="form-control" name="amount" required value="{{ number_format($doc->total-$doc->settled,2,'.','') }}"></div>
      <div><label>BANK</label><input class="form-control" name="bank_name"></div>
      <div><label>UTR OR CHEQUE NO.</label><input class="form-control" name="reference"></div>
      <button class="btn btn-outline-primary">Note it</button>
   </form>
   <div class="fna-foot-note" style="margin-top:4px;padding-top:0">This is only a note. Nothing is booked until the bank line arrives and you match it on Receipts to match.</div>
@endif
@if(count($settlements))
   <div class="fna-section-title">Payments matched</div>
   <div class="fna-tablebox" style="max-width:1000px"><table class="table"><thead><tr><th>Date</th><th>Bank line</th><th>How</th><th style="text-align:right">Amount</th></tr></thead><tbody>
   @foreach($settlements as $s)<tr><td>{{ $s->st_date }}</td><td>{{ $s->remark }}</td><td>{{ ucfirst($s->method) }}</td><td style="text-align:right">{{ number_format($s->amount,2) }}</td></tr>@endforeach
   </tbody></table></div>
@endif
<div class="fna-foot-note">{{ $L['foot'] }}. Nothing is ever deleted: a posted {{ $L['one'] }} can only be reversed or voided.</div>
@endsection
