@extends('layout.master')
@section('title', 'Rule audit')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $outs = [''=>'All','auto'=>'Applied automatically','conflict'=>'Waiting for a choice','resolved'=>'Chosen by a person','none'=>'No rule fitted']; $chip=['auto'=>'auto','conflict'=>'waiting','resolved'=>'chosen','none'=>'none']; @endphp
<form method="get" id="aform" style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:8px">
      <h1 style="margin:0">Rule audit</h1>
      <label style="display:inline-flex;align-items:center;gap:6px;margin:0">Financial year
         <select name="year" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="document.getElementById('aform').submit()">
            <option value="all" @if($selected_year=='all') selected @endif>All years</option>
            @foreach($year_options as $y)<option value="{{ $y }}" @if($y==$selected_year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>@endforeach
         </select>
      </label>
   </div>
   <div class="fna-radios" style="margin-bottom:8px">
      @foreach($outs as $k=>$n)
         <label class="{{ (string)$k===(string)$outcome ? 'on' : '' }}"><input type="radio" name="outcome" value="{{ $k }}" @if((string)$k===(string)$outcome) checked @endif onchange="document.getElementById('aform').submit()"> {{ $n }}</label>
      @endforeach
   </div>
   <div class="fna-tablebox">
      <table class="table">
         <thead><tr><th>Date</th><th>Voucher</th><th>Description</th><th style="text-align:right">Amount</th><th>Rules that fitted</th><th>What happened</th></tr></thead>
         <tbody>
         @forelse($rows as $r)
            @php $m = json_decode($r->matched,true) ?: []; $mi = array_values(array_unique(array_column($m,'rule'))); @endphp
            <tr>
               <td>{{ $r->line_date }}</td>
               <td><a href="{{ url('/merchant/voucher/'.$r->voucher_no) }}" style="font-weight:700;text-decoration:none">{{ $r->voucher_no }}</a></td>
               <td>{{ \Illuminate\Support\Str::limit($r->description,60) }}</td>
               <td style="text-align:right;font-variant-numeric:tabular-nums">{{ $r->direction=='Credit' ? 'in ' : 'out ' }}{{ number_format($r->amount,2) }}</td>
               <td style="color:#3E5A48">@forelse($mi as $id){{ isset($rules[$id]) ? 'Rule '.$id.' "'.$rules[$id]->rule_name.'"' : 'Rule '.$id }}@if(!$loop->last), @endif @empty none @endforelse</td>
               <td>
                  <span class="fna-chip {{ $chip[$r->outcome] ?? 'none' }}">{{ ['auto'=>'Automatic','conflict'=>'Waiting','resolved'=>'By a person','none'=>'No rule fitted'][$r->outcome] ?? $r->outcome }}</span>
                  <span style="font-size:12px;color:var(--fna-muted)">
                     @if($r->outcome=='auto') Rule {{ $r->applied_rule_id }} &rarr; {{ $r->head_name }}
                     @elseif($r->outcome=='resolved') {{ $r->decided_name ?: 'A user' }} chose Rule {{ $r->chosen_rule_id }} &rarr; {{ $r->head_name }}
                     @elseif($r->outcome=='conflict') <a href="{{ url('/merchant/rule-conflicts') }}">decide</a> @endif
                  </span>
               </td>
            </tr>
         @empty
            <tr><td colspan="6" style="text-align:center;padding:18px;color:var(--fna-muted)">Nothing recorded yet.</td></tr>
         @endforelse
         </tbody>
      </table>
   </div>
   <div class="fna-foot-note" style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between">
      <span>For every uploaded bank line: which rules fitted, and what happened. Nothing here is ever deleted.</span>
      <span>@if($rows->previousPageUrl())<a href="{{ $rows->previousPageUrl() }}">&lsaquo; Previous</a>@endif Page {{ $rows->currentPage() }} of {{ $rows->lastPage() }} · {{ $rows->total() }} lines @if($rows->nextPageUrl())<a href="{{ $rows->nextPageUrl() }}">Next &rsaquo;</a>@endif</span>
   </div>
</form>
@endsection
