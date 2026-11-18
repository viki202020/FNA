@extends('layout.master')
@section('title', 'Voucher '.$voucher->voucher_no)
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <div style="color:var(--fna-muted);margin-bottom:6px"><a href="{{ url('/merchant/vouchers?year='.urlencode($voucher->fyear)) }}" style="text-decoration:none">Vouchers</a> &rsaquo; <strong style="color:var(--fna-ink)">{{ $voucher->voucher_no }}</strong></div>
   <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:12px">
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
         <h1 style="margin:0">Voucher {{ $voucher->voucher_no }}</h1>
         <span class="fna-chip {{ $voucher->status }}">{{ ucfirst($voucher->status) }}</span>
         <span style="color:var(--fna-muted)">Version {{ $voucher->version }} · FY {{ $voucher->fyear ?: '-' }}</span>
      </div>
   </div>
   @if($year_is_closed)<div class="alert alert-warning" style="margin-bottom:10px"><strong>Financial year {{ $voucher->fyear }} is closed.</strong> This voucher is read only.</div>@endif
   <div style="background:var(--fna-tint);border-radius:8px;padding:9px 14px;margin-bottom:10px;display:flex;flex-wrap:wrap;gap:8px 36px">
      <div><div style="font-size:10px;color:var(--fna-muted);font-weight:700">TYPE</div><div style="font-size:14px;font-weight:500">{{ \App\Http\Controllers\VoucherController::seriesLabel($voucher->series) }}</div></div>
      <div><div style="font-size:10px;color:var(--fna-muted);font-weight:700">DATE</div><div style="font-size:14px;font-weight:500">{{ $voucher->voucher_date }}</div></div>
      @if($voucher->reference)<div><div style="font-size:10px;color:var(--fna-muted);font-weight:700">REFERENCE</div><div style="font-size:14px;font-weight:500">{{ $voucher->reference }}</div></div>@endif
      @foreach($related as $r)<div><div style="font-size:10px;color:var(--fna-muted);font-weight:700">{{ $r->id==$voucher->reverses_id ? 'REVERSES' : 'REVERSED BY' }}</div><a href="{{ url('/merchant/voucher/'.$r->voucher_no) }}" style="font-size:14px;font-weight:700;text-decoration:none">{{ $r->voucher_no }}</a></div>@endforeach
      @if($voucher->narration)<div style="flex-basis:100%"><div style="font-size:10px;color:var(--fna-muted);font-weight:700">NARRATION</div><div style="font-size:14px">{{ $voucher->narration }}</div></div>@endif
   </div>
   <div class="fna-tablebox">
      <table class="table">
         <thead><tr><th>Date</th><th>Account</th><th>Narration</th><th style="text-align:right">Debit</th><th style="text-align:right">Credit</th><th>State</th></tr></thead>
         <tbody>
         @foreach($lines as $l)
            <tr>
               <td>{{ $l->st_date }}</td>
               <td>@if($l->head_id)<a href="{{ url('/merchant/ledger-by-head/'.$l->head_id.'/'.$l->fyear) }}" style="font-weight:700;text-decoration:none">{{ $l->head_name }}</a>@else <strong>! Not yet categorised</strong>@endif</td>
               <td>{{ $l->remark }}</td>
               <td style="text-align:right;font-variant-numeric:tabular-nums">{{ (float)$l->debit ? number_format((float)$l->debit,2) : '' }}</td>
               <td style="text-align:right;font-variant-numeric:tabular-nums">{{ (float)$l->credit ? number_format((float)$l->credit,2) : '' }}</td>
               <td style="color:var(--fna-muted)">{{ $l->status ? 'In the books' : 'Uncategorised' }}</td>
            </tr>
         @endforeach
         <tr style="font-weight:700;border-top:2px solid var(--fna-green)">
            <td colspan="3" style="text-align:right">Total</td>
            <td style="text-align:right;font-variant-numeric:tabular-nums">{{ number_format($total_debit,2) }}</td>
            <td style="text-align:right;font-variant-numeric:tabular-nums">{{ number_format($total_credit,2) }}</td>
            <td>@if(abs($total_debit-$total_credit)>0.005)! Out by {{ number_format(abs($total_debit-$total_credit),2) }}@else Balanced ✓ @endif</td>
         </tr>
         </tbody>
      </table>
   </div>
   <h2 style="margin:18px 0 6px;font-size:14px">History</h2>
   <div class="fna-tablebox">
      <table class="table">
         <thead><tr><th>Version</th><th>What happened</th><th>Reason</th><th>When</th></tr></thead>
         <tbody>
         @foreach($versions as $ver)
            <tr><td style="font-weight:700">{{ $ver->version }}</td><td>{{ ucfirst($ver->action) }}</td><td>{{ $ver->reason }}</td><td>{{ $ver->created_at }}</td></tr>
         @endforeach
         </tbody>
      </table>
   </div>
   <div class="fna-foot-note">Every earlier version is kept. Nothing is ever deleted.</div>
</div>
@endsection
