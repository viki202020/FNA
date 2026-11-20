@extends('layout.master')
@section('title', 'Vouchers')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php
   $types = ['SBV'=>'Bank statement line (SBV)','UJV'=>'Uploaded journal (UJV)','MJV'=>'Manual journal (MJV)','RJV'=>'Reversal (RJV)','SRV'=>'Auto reversal (SRV)','SIV'=>'Sales invoice (SIV)','OLD'=>'Older journal entry'];
   $statuses = [''=>'All','draft'=>'Drafts','posted'=>'Posted','reversed'=>'Reversed','void'=>'Voids'];
@endphp
<form method="get" id="vform" style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:10px">
      <h1 style="margin:0">All Vouchers</h1>
      <div style="display:flex;align-items:center;gap:10px">
         <a class="btn btn-primary" href="{{ url('merchant/journal/new') }}">+ New journal</a>
         <label style="display:inline-flex;align-items:center;gap:6px;margin:0">Financial year
            <select name="year" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="document.getElementById('vform').submit()">
               <option value="all" @if($selected_year=='all') selected @endif>All years</option>
               @foreach($year_options as $y)
                  <option value="{{ $y }}" @if($y==$selected_year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>
               @endforeach
            </select>
         </label>
      </div>
   </div>
   <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:8px">
      <div class="fna-radios" role="radiogroup" aria-label="Show vouchers by status">
         @foreach($statuses as $k=>$n)
            <label class="{{ (string)$k===(string)$status ? 'on' : '' }}"><input type="radio" name="status" value="{{ $k }}" @if((string)$k===(string)$status) checked @endif onchange="document.getElementById('vform').submit()"> {{ $n }}</label>
         @endforeach
      </div>
      <label style="display:inline-flex;align-items:center;gap:6px;margin:0 0 0 6px">Type
         <select name="series" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="document.getElementById('vform').submit()">
            <option value="">All types</option>
            @foreach($types as $k=>$n)<option value="{{ $k }}" @if($k===$series) selected @endif>{{ $n }}</option>@endforeach
         </select>
      </label>
      <div style="margin-left:auto;display:flex;align-items:stretch;border:1px solid var(--fna-field);border-radius:8px;overflow:hidden;min-width:300px;flex:0 1 400px">
         <select name="in" aria-label="Search in" style="font:inherit;font-weight:700;color:var(--fna-green);background:var(--fna-band);border:0;border-right:1px solid var(--fna-field);padding:4px 8px">
            @foreach(['all'=>'All fields','number'=>'Number','amount'=>'Amount','narration'=>'Narration'] as $k=>$n)<option value="{{ $k }}" @if($k===$in) selected @endif>{{ $n }}</option>@endforeach
         </select>
         <input type="search" name="q" value="{{ $q }}" aria-label="Search text" placeholder="e.g. 0033, MJV0033, 35000, 10000-50000, bank charges" style="flex:1;min-width:0;border:0;outline:0;padding:4px 10px;font:inherit">
         <button type="submit" class="btn btn-success" style="border-radius:0;padding:4px 12px">Search</button>
      </div>
   </div>
   <div class="fna-tablebox">
      <table class="table">
         <thead><tr><th>Date</th><th>Voucher #</th><th>Type</th><th>Narration</th><th>Status</th><th style="text-align:right">Amount</th><th>Check</th></tr></thead>
         <tbody>
         @forelse($vouchers as $v)
            <tr>
               <td>{{ $v->voucher_date }}</td>
               <td><a href="{{ url('/merchant/voucher/'.$v->voucher_no) }}" style="font-weight:700;text-decoration:none">{{ $v->voucher_no }}</a>@if($v->version>1) <small>(v{{ $v->version }})</small>@endif</td>
               <td style="color:var(--fna-muted)">{{ \App\Http\Controllers\VoucherController::seriesLabel($v->series) }}</td>
               <td>{{ \Illuminate\Support\Str::limit($v->narration ?: $v->first_remark, 70) }}</td>
               <td><span class="fna-chip {{ $v->status }}">{{ ucfirst($v->status) }}</span></td>
               <td style="text-align:right;font-variant-numeric:tabular-nums">{{ number_format($v->total_debit,2) }}</td>
               <td>@if(abs($v->total_debit-$v->total_credit)>0.005)<strong>! Out by {{ number_format(abs($v->total_debit-$v->total_credit),2) }}</strong>@elseif($v->open_lines>0){{ $v->open_lines }} uncategorised @else <span style="color:var(--fna-muted)">OK</span> @endif</td>
            </tr>
         @empty
            <tr><td colspan="7" style="text-align:center;padding:18px;color:var(--fna-muted)">No vouchers match.</td></tr>
         @endforelse
         </tbody>
      </table>
   </div>
   <div class="fna-foot-note" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between">
      <span>{{ $vouchers->total() }} {{ $vouchers->total()==1 ? 'voucher' : 'vouchers' }} shown · {{ $summary['total'] }} in the selected year · {{ $summary['drafts'] }} {{ $summary['drafts']==1 ? 'draft' : 'drafts' }}</span>
      <span>
         @if($vouchers->previousPageUrl())<a href="{{ $vouchers->previousPageUrl() }}">&lsaquo; Previous</a>@endif
         Page {{ $vouchers->currentPage() }} of {{ $vouchers->lastPage() }}
         @if($vouchers->nextPageUrl())<a href="{{ $vouchers->nextPageUrl() }}">Next &rsaquo;</a>@endif
      </span>
   </div>
</form>
@endsection
