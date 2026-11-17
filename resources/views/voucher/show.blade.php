@extends('layout.master')
@section('title', 'Voucher '.$voucher->voucher_no)
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@include('voucher._style')
<div class="row"><div class="col-12 col-xl-12">
<a class="btn btn-outline-secondary btn-sm mt-3" href="{{ url('/merchant/vouchers?year='.urlencode($voucher->fyear)) }}">&laquo; All vouchers</a>
<div class="card card-body border-0 shadow mb-4 mt-3 vch-page" style="overflow:auto;">
   <h2 class="mb-1">Voucher {{ $voucher->voucher_no }}
      <span class="badge-s badge-{{ $voucher->status }}" style="font-size:14px;vertical-align:middle;">{{ ucfirst($voucher->status) }}</span></h2>
   <p style="margin-bottom:10px;">
      {{ \App\Http\Controllers\VoucherController::seriesLabel($voucher->series) }} &middot;
      Date {{ $voucher->voucher_date }} &middot; Financial year {{ $voucher->fyear ?: '-' }} &middot; Version {{ $voucher->version }}
      @if($voucher->reference) &middot; Ref {{ $voucher->reference }} @endif
   </p>
   @if($year_is_closed)
      <div class="vch-box" style="border:1px solid #b9770e;background:#fef5e7;color:#7e5109;"><strong>Financial year {{ $voucher->fyear }} is closed.</strong> This voucher is read only.</div>
   @endif
   @foreach($related as $r)
      <div class="vch-box" style="background:#eef2ff;">@if($r->id==$voucher->reverses_id) Reverses @else Reversed by @endif
         <a class="vlink" href="{{ url('/merchant/voucher/'.$r->voucher_no) }}">{{ $r->voucher_no }}</a></div>
   @endforeach
   @if($voucher->narration)<p><strong>Narration:</strong> {{ $voucher->narration }}</p>@endif

   <table class="vch">
      <thead><tr><th>Date</th><th>Account</th><th>Narration</th><th class="num">Debit</th><th class="num">Credit</th><th>State</th></tr></thead>
      <tbody>
      @foreach($lines as $l)
         <tr>
            <td>{{ $l->st_date }}</td>
            <td>@if($l->head_id)<a class="vlink" href="{{ url('/merchant/ledger-by-head/'.$l->head_id.'/'.$l->fyear) }}">{{ $l->head_name }}</a>@else <span class="vch-warn">Not yet categorised</span>@endif</td>
            <td>{{ $l->remark }}</td>
            <td class="num">{{ (float)$l->debit ? number_format((float)$l->debit,2) : '' }}</td>
            <td class="num">{{ (float)$l->credit ? number_format((float)$l->credit,2) : '' }}</td>
            <td>{{ $l->status ? 'In the books' : 'Uncategorised' }}</td>
         </tr>
      @endforeach
      <tr style="font-weight:700;">
         <td colspan="3" class="num">Total</td>
         <td class="num">{{ number_format($total_debit,2) }}</td><td class="num">{{ number_format($total_credit,2) }}</td>
         <td>@if(abs($total_debit-$total_credit)>0.005)<span class="vch-warn">Out by {{ number_format(abs($total_debit-$total_credit),2) }}</span>@else Balanced @endif</td>
      </tr>
      </tbody>
   </table>

   <h4 class="mt-4">History</h4>
   <table class="vch">
      <thead><tr><th>Version</th><th>What happened</th><th>Reason</th><th>When</th></tr></thead>
      <tbody>
      @foreach($versions as $ver)
         <tr><td>{{ $ver->version }}</td><td>{{ ucfirst($ver->action) }}</td><td>{{ $ver->reason }}</td><td>{{ $ver->created_at }}</td></tr>
      @endforeach
      </tbody>
   </table>
</div></div></div>
@endsection
