@extends('layout.master')
@section('title', 'Dashboard')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $money = function($n){ return number_format($n, 2); }; @endphp
<div style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:14px">
   <div><h1 style="margin:0">Good morning</h1><div style="color:var(--fna-muted)">Financial year {{ $year }} is open</div></div>
   <div style="display:flex;gap:8px">
      <a class="btn btn-success" href="{{ url('/merchant/vouchers') }}">Vouchers</a>
      <a class="btn btn-outline-success" href="{{ url('/merchant/upload-csv') }}">Upload bank file</a>
   </div>
</div>
<div class="fna-tiles">
   <div class="fna-tile"><div class="k">Bank balance</div><div class="v">{{ $money($bank) }}</div><div class="s">{{ $bankCount }} bank {{ $bankCount==1 ? 'account' : 'accounts' }}</div></div>
   <div class="fna-tile"><div class="k">Sales for {{ $monthName }}</div><div class="v">{{ $money($sales) }}</div></div>
   <div class="fna-tile deep"><div class="k">Purchases for {{ $monthName }}</div><div class="v">{{ $money($purchases) }}</div></div>
   <div class="fna-tile dark"><div class="k">Uploads for review</div><div class="v">{{ $uncategorised }} <span style="font-size:10px;font-weight:500">Uncategorised entries</span></div></div>
   <div class="fna-tile grey"><div class="k">Draft vouchers</div><div class="v">{{ $drafts }}</div><div class="s">Not yet posted</div></div>
</div>
<div class="fna-tablebox">
   <h2 style="margin:4px 0 8px 8px;font-size:14px">Recent vouchers</h2>
   <table class="table">
      <thead><tr><th>Date</th><th>Voucher #</th><th>Narration</th><th>Status</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
      @forelse($recent as $v)
         <tr>
            <td>{{ $v->voucher_date }}</td>
            <td><a href="{{ url('/merchant/voucher/'.$v->voucher_no) }}" style="font-weight:700;text-decoration:none">{{ $v->voucher_no }}</a></td>
            <td>{{ \Illuminate\Support\Str::limit($v->narration ?: $v->remark, 70) }}</td>
            <td><span class="fna-chip {{ $v->status }}">{{ ucfirst($v->status) }}</span></td>
            <td style="text-align:right;font-variant-numeric:tabular-nums">{{ $money((float)$v->amount) }}</td>
         </tr>
      @empty
         <tr><td colspan="5" style="text-align:center;padding:18px;color:var(--fna-muted)">No vouchers in this year yet.</td></tr>
      @endforelse
      </tbody>
   </table>
</div>
<div class="fna-foot-note">
   <div style="background:var(--fna-tint);border-radius:8px;padding:7px 12px;display:flex;flex-wrap:wrap;gap:6px 18px;align-items:center;color:#3E5A48">
      <strong>Before you can close the year</strong>
      <span>{{ $uncategorised }} uncategorised {{ $uncategorised==1 ? 'line' : 'lines' }}</span>
      <span>{{ $drafts }} draft {{ $drafts==1 ? 'voucher' : 'vouchers' }}</span>
      <span>Trial balance is {{ $balanced ? 'balanced ✓' : 'NOT balanced' }}</span>
   </div>
</div>
</div>
@endsection
