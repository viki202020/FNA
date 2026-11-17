@extends('layout.master')
@section('title', 'Rule audit')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@include('voucher._style')
<div class="row"><div class="col-12 col-xl-12">
<div class="card card-body border-0 shadow mb-4 mt-4 vch-page" style="overflow:auto;">
   <h2 class="mb-1">Rule audit</h2>
   <p>For every uploaded bank line: which rules fitted, and what happened. Nothing here is ever deleted.</p>
   <form method="get" class="row g-2 mb-3 align-items-end">
      <div class="col-md-2"><label>Financial year</label>
         <select name="year" class="form-select">
            <option value="all" @if($selected_year=='all') selected @endif>All years</option>
            @foreach($year_options as $y)<option value="{{ $y }}" @if($y==$selected_year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>@endforeach
         </select></div>
      <div class="col-md-3"><label>What happened</label>
         <select name="outcome" class="form-select"><option value="">All</option>
            @foreach(['auto'=>'Applied automatically','conflict'=>'Waiting for a choice','resolved'=>'Chosen by a person','none'=>'No rule fitted'] as $k=>$n)<option value="{{ $k }}" @if($k==$outcome) selected @endif>{{ $n }}</option>@endforeach
         </select></div>
      <div class="col-md-3"><button class="btn btn-primary" type="submit">Show</button></div>
   </form>
   <table class="vch">
      <thead><tr><th>Date</th><th>Voucher</th><th>Description</th><th class="num">Amount</th><th>Rules that fitted</th><th>What happened</th></tr></thead>
      <tbody>
      @forelse($rows as $r)
         @php $m = json_decode($r->matched,true) ?: []; $mi = array_values(array_unique(array_column($m,'rule'))); @endphp
         <tr>
            <td>{{ $r->line_date }}</td>
            <td><a class="vlink" href="{{ url('/merchant/voucher/'.$r->voucher_no) }}">{{ $r->voucher_no }}</a></td>
            <td>{{ \Illuminate\Support\Str::limit($r->description,60) }}</td>
            <td class="num">{{ $r->direction=='Credit' ? 'in ' : 'out ' }}{{ number_format($r->amount,2) }}</td>
            <td>@forelse($mi as $id){{ isset($rules[$id]) ? 'Rule '.$id.' "'.$rules[$id]->rule_name.'"' : 'Rule '.$id }}@if(!$loop->last), @endif @empty <span style="color:#9ca3af">none</span> @endforelse</td>
            <td>
               @if($r->outcome=='auto') Applied automatically: Rule {{ $r->applied_rule_id }} &rarr; {{ $r->head_name }}
               @elseif($r->outcome=='resolved') Chosen by {{ $r->decided_name ?: 'a user' }} ({{ $r->decided_at }}): Rule {{ $r->chosen_rule_id }} &rarr; {{ $r->head_name }}
               @elseif($r->outcome=='conflict') <span class="vch-warn">Waiting for a choice</span> (<a class="vlink" href="{{ url('/merchant/rule-conflicts') }}">decide</a>)
               @else No rule fitted @endif
            </td>
         </tr>
      @empty
         <tr><td colspan="6" style="text-align:center;padding:24px;">Nothing recorded yet.</td></tr>
      @endforelse
      </tbody>
   </table>
   <div class="mt-3" style="display:flex;gap:12px;align-items:center;">
      @if($rows->previousPageUrl())<a class="btn btn-outline-secondary btn-sm" href="{{ $rows->previousPageUrl() }}">&laquo; Previous</a>@endif
      <span>Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }} &middot; {{ $rows->total() }} lines</span>
      @if($rows->nextPageUrl())<a class="btn btn-outline-secondary btn-sm" href="{{ $rows->nextPageUrl() }}">Next &raquo;</a>@endif
   </div>
</div></div></div>
@endsection
