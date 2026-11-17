@extends('layout.master')
@section('title', 'Vouchers')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@include('voucher._style')
<div class="row"><div class="col-12 col-xl-12">
<div class="card card-body border-0 shadow mb-4 mt-4 vch-page" style="overflow:auto;">
   <h2 class="mb-3">Vouchers</h2>
   <form method="get" class="row g-2 mb-3 align-items-end">
      <div class="col-md-3"><label>Search (voucher number or narration)</label>
         <input type="text" name="q" value="{{ $q }}" class="form-control" placeholder="e.g. SBV-00012 or bank charges"></div>
      <div class="col-md-2"><label>Financial year</label>
         <select name="year" class="form-select">
            <option value="all" @if($selected_year=='all') selected @endif>All years</option>
            @foreach($year_options as $y)
               <option value="{{ $y }}" @if($y==$selected_year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>
            @endforeach
         </select></div>
      <div class="col-md-2"><label>Type</label>
         <select name="series" class="form-select"><option value="">All</option>
            @foreach($series_options as $s)
               <option value="{{ $s }}" @if($s==$series) selected @endif>{{ $s }} - {{ \App\Http\Controllers\VoucherController::seriesLabel($s) }}</option>
            @endforeach
         </select></div>
      <div class="col-md-2"><label>Status</label>
         <select name="status" class="form-select"><option value="">All</option>
            @foreach(['draft'=>'Draft','posted'=>'Posted','reversed'=>'Reversed','void'=>'Void'] as $k=>$n)
               <option value="{{ $k }}" @if($k==$status) selected @endif>{{ $n }}</option>
            @endforeach
         </select></div>
      <div class="col-md-3"><button class="btn btn-primary" type="submit">Search</button>
         <a class="btn btn-outline-secondary" href="{{ url('/merchant/vouchers') }}">Clear</a></div>
   </form>
   <table class="vch">
      <thead><tr>
         <th>Voucher #</th><th>Date</th><th>Type</th><th>Narration</th><th>Status</th>
         <th class="num">Lines</th><th class="num">Debit</th><th class="num">Credit</th><th>Check</th>
      </tr></thead>
      <tbody>
      @forelse($vouchers as $v)
         <tr>
            <td><a class="vlink" href="{{ url('/merchant/voucher/'.$v->voucher_no) }}">{{ $v->voucher_no }}</a>@if($v->version>1) <small>(v{{ $v->version }})</small>@endif</td>
            <td>{{ $v->voucher_date }}</td>
            <td>{{ \App\Http\Controllers\VoucherController::seriesLabel($v->series) }}</td>
            <td>{{ \Illuminate\Support\Str::limit($v->narration ?: $v->first_remark, 70) }}</td>
            <td><span class="badge-s badge-{{ $v->status }}">{{ ucfirst($v->status) }}</span></td>
            <td class="num">{{ $v->line_count }}</td>
            <td class="num">{{ number_format($v->total_debit,2) }}</td>
            <td class="num">{{ number_format($v->total_credit,2) }}</td>
            <td>@if(abs($v->total_debit-$v->total_credit)>0.005)<span class="vch-warn">Out by {{ number_format(abs($v->total_debit-$v->total_credit),2) }}</span>@elseif($v->open_lines>0)<span class="vch-warn">{{ $v->open_lines }} uncategorised</span>@else OK @endif</td>
         </tr>
      @empty
         <tr><td colspan="9" style="text-align:center;padding:24px;">No vouchers match.</td></tr>
      @endforelse
      </tbody>
   </table>
   <div class="mt-3" style="display:flex;gap:12px;align-items:center;">
      @if($vouchers->previousPageUrl())<a class="btn btn-outline-secondary btn-sm" href="{{ $vouchers->previousPageUrl() }}">&laquo; Previous</a>@endif
      <span>Page {{ $vouchers->currentPage() }} of {{ $vouchers->lastPage() }} &middot; {{ $vouchers->total() }} vouchers</span>
      @if($vouchers->nextPageUrl())<a class="btn btn-outline-secondary btn-sm" href="{{ $vouchers->nextPageUrl() }}">Next &raquo;</a>@endif
   </div>
</div></div></div>
@endsection
