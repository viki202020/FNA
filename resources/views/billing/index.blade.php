@extends('layout.master')
@section('title', 'Invoices')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php include resource_path('views/billing/_labels.php'); @endphp
@php
   $filters = ['all'=>'All','draft'=>'Drafts','unpaid'=>$S?'Unpaid':'To pay','overdue'=>'Overdue','paid'=>'Paid'];
   $today = date('Y-m-d');
@endphp
<form method="get" id="bform" style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:8px">
      <h1 style="margin:0">{{ $L['title'] }}</h1>
      <div style="display:flex;align-items:center;gap:12px">
         <label style="display:inline-flex;align-items:center;gap:6px;margin:0">Financial year
            <select name="year" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="this.form.submit()">
               <option value="all" @if($year=='all') selected @endif>All years</option>
               @foreach($year_options as $y)<option value="{{ $y }}" @if($y==$year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>@endforeach
            </select>
         </label>
         <a class="btn btn-primary" href="{{ url($L['new']) }}">+ {{ $L['New'] }}</a>
      </div>
   </div>
   @if(session('success_msg'))<div class="alert alert-success">{{ session('success_msg') }}</div>@endif
   <div class="fna-radios mb-2" role="radiogroup">
      @foreach($filters as $k=>$n)
         <label class="{{ $k===$filter ? 'on' : '' }}"><input type="radio" name="show" value="{{ $k }}" @if($k===$filter) checked @endif onchange="this.form.submit()"> {{ $n }}</label>
      @endforeach
   </div>
   <div class="fna-tablebox">
      <table class="table">
         <thead><tr><th>Date</th><th>{{ $S ? 'Invoice #' : 'Bill #' }}</th><th>{{ $L['party'] }}</th><th style="text-align:right">Amount</th><th style="text-align:right">Balance</th><th>Status</th><th>Due</th></tr></thead>
         <tbody>
            @forelse($docs as $d)
               @php $overdue = in_array($d->status,['open','part']) && $d->due_date && $d->due_date < $today; @endphp
               <tr>
                  <td>{{ date('d/m/Y', strtotime($d->doc_date)) }}</td>
                  <td><a class="fw-bold" href="{{ url($L['base'].'/'.$d->id) }}">{{ $d->number ?: 'Draft #'.$d->id }}</a></td>
                  <td>{{ $d->party_name }}</td>
                  <td style="text-align:right">{{ number_format($d->total,2) }}</td>
                  <td style="text-align:right">{{ in_array($d->status,['draft','void']) ? number_format($d->total,2) : number_format($d->total-$d->settled,2) }}</td>
                  <td><span class="fna-chip {{ $overdue ? 'overdue' : $chipClass[$d->status] }}">{{ $overdue ? 'Overdue' : $chipText[$d->status] }}</span></td>
                  <td>{{ $d->due_date ? date('d/m/Y', strtotime($d->due_date)) : '' }}</td>
               </tr>
            @empty
               <tr><td colspan="7" class="text-muted">Nothing here yet.</td></tr>
            @endforelse
         </tbody>
      </table>
   </div>
   <div class="fna-foot-note">Overdue {{ $overdue_count }} {{ $S ? 'invoices' : 'bills' }}, {{ number_format($overdue_total,2) }} &middot; {{ $L['foot'] }}</div>
</form>
@endsection
