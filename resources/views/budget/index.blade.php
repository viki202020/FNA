@extends('layout.master')
@section('title', 'Budgets')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $m = fn($v) => number_format($v, 2); $tabs = ['plan' => 'Budget by month', 'variance' => 'Budget vs actual', 'upload' => 'Upload']; @endphp
<style>.bud td.n,.bud th.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.bud input.c{width:88px;text-align:right;padding:2px 4px}.bud th,.bud td{padding:4px 6px}</style>
<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:6px">
   <h1 style="margin:0">Budgets</h1>
   <form method="get" style="margin:0"><input type="hidden" name="tab" value="{{ $tab }}"><label style="display:inline-flex;gap:6px;align-items:center;margin:0">Financial year
      <select name="year" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="this.form.submit()">@foreach($year_options as $y)<option value="{{ $y }}" @if($y==$year) selected @endif>{{ $y }}</option>@endforeach</select></label></form>
</div>
<div class="fna-radios mb-2">@foreach($tabs as $k=>$tt)<label class="{{ $k===$tab ? 'on' : '' }}"><a href="?tab={{ $k }}&year={{ $year }}" style="color:inherit;text-decoration:none">{{ $tt }}</a></label>@endforeach</div>
@if(session('success_msg'))<div class="alert alert-success" style="max-width:1100px">{{ session('success_msg') }}</div>@endif
@if(session('warn_list'))<div class="alert alert-warning" style="max-width:1100px">@foreach(session('warn_list') as $w)<div>{{ $w }}</div>@endforeach</div>@endif
@if($errors->any())<div class="alert alert-danger" style="max-width:1100px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

@if($tab==='plan')
<p class="text-muted" style="max-width:950px">Enter what you plan to earn (income accounts) and spend (expense accounts) in each month. Leave a month blank for no plan. Amounts are before GST. Every change is logged with who made it.</p>
<form method="post" action="{{ url('merchant/budgets/save') }}">@csrf<input type="hidden" name="year" value="{{ $year }}">
<div class="fna-tablebox bud" style="overflow-x:auto"><table class="table">
   <thead><tr><th>Account</th>@foreach($months as $x)<th class="n">{{ date('M y', strtotime($x.'-01')) }}</th>@endforeach<th class="n">Year</th></tr></thead><tbody>
   @php $last=null; @endphp
   @foreach($heads as $h)
      @if($last!==$h['kind'])<tr><td colspan="{{ count($months)+2 }}" class="fw-bold" style="background:#f3f7f4">{{ $h['kind']==='income' ? 'Income' : 'Expenses' }}</td></tr>@php $last=$h['kind']; @endphp @endif
      <tr><td style="white-space:nowrap">{{ $h['name'] }}</td>
      @foreach($months as $x)<td class="n"><input class="form-control c" name="b[{{ $h['id'] }}][{{ $x }}]" value="{{ isset($plan[$h['id']][$x]) && $plan[$h['id']][$x] != 0 ? number_format($plan[$h['id']][$x], 2, '.', '') : '' }}"></td>@endforeach
      <td class="n fw-bold">{{ $m(array_sum($plan[$h['id']] ?? [])) }}</td></tr>
   @endforeach
   </tbody></table></div>
<button class="btn btn-primary mt-2">Save budget</button></form>

@elseif($tab==='variance')
<form method="get" style="margin:0 0 8px"><input type="hidden" name="tab" value="variance"><input type="hidden" name="year" value="{{ $year }}">
   <label style="display:inline-flex;gap:6px;align-items:center">From the start of the year to the end of <select name="upto" class="form-select" style="width:auto" onchange="this.form.submit()">@foreach($months as $x)<option value="{{ $x }}" @if($x===$upTo) selected @endif>{{ date('F Y', strtotime($x.'-01')) }}</option>@endforeach</select></label></form>
<p class="text-muted" style="max-width:950px">Variance is shown as <b>favourable when positive</b>: income above plan, or spending below plan. Actuals are entries in the books (uncategorised bank lines are not counted).</p>
@foreach(['income'=>'Income','expense'=>'Expenses'] as $k=>$lab)
<h5 style="margin-top:14px">{{ $lab }}</h5>
<div class="fna-tablebox bud" style="max-width:1000px"><table class="table"><thead><tr><th>Account</th><th class="n">Budget</th><th class="n">Actual</th><th class="n">Variance</th><th class="n">%</th><th class="n">Budget, full year</th></tr></thead><tbody>
   @forelse($v['rows'][$k] as $r)<tr><td>{{ $r['name'] }}</td><td class="n">{{ $m($r['budget']) }}</td><td class="n">{{ $m($r['actual']) }}</td><td class="n" style="color:{{ $r['variance'] < -0.004 ? '#b03a2e' : 'inherit' }}">{{ $m($r['variance']) }}</td><td class="n">{{ $r['pct']===null ? '' : number_format($r['pct'],1).'%' }}</td><td class="n">{{ $m($r['year']) }}</td></tr>
   @empty<tr><td colspan="6" class="text-muted">Nothing planned or booked.</td></tr>@endforelse
   <tr style="font-weight:700;border-top:2px solid var(--fna-green)"><td>Total {{ strtolower($lab) }}</td><td class="n">{{ $m($v['tot'][$k]['b']) }}</td><td class="n">{{ $m($v['tot'][$k]['a']) }}</td><td class="n">{{ $m($k==='income' ? $v['tot'][$k]['a']-$v['tot'][$k]['b'] : $v['tot'][$k]['b']-$v['tot'][$k]['a']) }}</td><td></td><td class="n">{{ $m($v['tot'][$k]['fy']) }}</td></tr>
</tbody></table></div>
@endforeach
<div class="fna-tablebox bud" style="max-width:1000px;margin-top:12px"><table class="table"><tbody><tr style="font-weight:700"><td>Profit (income less expenses)</td><td class="n">{{ $m($v['profit']['budget']) }}</td><td class="n">{{ $m($v['profit']['actual']) }}</td><td class="n">{{ $m($v['profit']['variance']) }}</td><td></td><td class="n">{{ $m($v['profit']['year']) }}</td></tr></tbody></table></div>

@else
<p class="text-muted" style="max-width:950px">Download the sheet (it lists every income and expense account with the twelve months of {{ $year }} and any amounts already planned), fill it in, and upload it. Accounts are matched by name. Only the cells that are filled in are changed, and every change is logged.</p>
<p><a class="btn btn-outline-primary" href="{{ url('merchant/budgets/template?year='.$year) }}">Download the sheet</a></p>
<form method="post" action="{{ url('merchant/budgets/upload') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end" style="max-width:800px">@csrf<input type="hidden" name="year" value="{{ $year }}">
   <div><label>FILLED-IN SHEET (EXCEL OR CSV)</label><input type="file" name="csv_file" class="form-control" required accept=".xlsx,.xls,.csv,.txt"></div>
   <button class="btn btn-primary">Upload</button></form>
<div class="fna-section-title">Latest changes</div>
<div class="fna-tablebox bud" style="max-width:1000px"><table class="table"><thead><tr><th>When</th><th>Account</th><th>Month</th><th class="n">From</th><th class="n">To</th><th>By</th><th>How</th></tr></thead><tbody>
   @forelse($log as $l)<tr><td>{{ $l->created_at }}</td><td>{{ $l->head }}</td><td>{{ date('M Y', strtotime($l->ym.'-01')) }}</td><td class="n">{{ $l->old_amount===null ? '' : $m($l->old_amount) }}</td><td class="n">{{ $m($l->new_amount) }}</td><td>{{ $l->who }}</td><td>{{ $l->source }}</td></tr>@empty<tr><td colspan="7" class="text-muted">No changes yet.</td></tr>@endforelse
</tbody></table></div>
@endif
@endsection
