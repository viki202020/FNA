@extends('layout.master')
@section('title', 'Financial statements')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php
   $b = $s['bs']; $p = $s['pl']; $prev = $s['prev_year'];
   $fmt = function($v){ if($v === null){ return ''; } if($v == 0){ return '-'; } return $v < 0 ? '('.number_format(abs($v)).')' : number_format($v); };
   $tabs = ['bs'=>'Balance sheet','pl'=>'Profit and loss','dept'=>'By department','tb'=>'Trial balance','notes'=>'Notes','ageing'=>'Ageing','checks'=>'Checks'];
   $dq = $dept ? '&dept='.$dept : '';
   $ck = $s['checks'];
   $warn = abs($ck['bs_difference'])>0.5 || count($ck['unmapped']) || count($ck['clear']) || $ck['uncategorised'];
@endphp
<style>
   .s3 td.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}
   .s3 .grp td{font-weight:700;color:var(--fna-green);background:var(--fna-tint)}
   .s3 .tot td{font-weight:700;border-top:2px solid var(--fna-green)}
   .s3 .ind td:first-child{padding-left:28px}
   @media print{ .fna-sidebar,.no-print{display:none!important} .content{margin-left:0!important;padding:0!important} }
</style>
<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:6px" class="no-print">
   <h1 style="margin:0">Financial statements</h1>
   <form method="get" style="display:flex;gap:10px;align-items:center;margin:0">
      <input type="hidden" name="tab" value="{{ $tab }}">
      <label style="display:inline-flex;gap:6px;align-items:center;margin:0">Financial year
         <select name="year" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="this.form.submit()">@foreach($year_options as $y)<option value="{{ $y }}" @if($y==$year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>@endforeach</select></label>
      <label style="display:inline-flex;gap:6px;align-items:center;margin:0">Department
         <select name="dept" class="form-select" style="width:auto" onchange="this.form.submit()"><option value="all">All</option>@foreach($departments as $dd)<option value="{{ $dd->id }}" @if((string)$dept===(string)$dd->id) selected @endif>{{ $dd->name }}</option>@endforeach<option value="none" @if($dept==='none') selected @endif>No department</option></select></label>
      <label style="display:inline-flex;gap:6px;align-items:center;margin:0">Amounts in
         <select name="unit" class="form-select" style="width:auto" onchange="this.form.submit()">@foreach($units as $k=>$u)<option value="{{ $k }}" @if($k==$unit) selected @endif>{{ ucfirst($u[1]) }}</option>@endforeach</select></label>
      <a class="btn btn-outline-primary" href="{{ url('merchant/financial-statements/export?year='.$year.'&unit='.$unit) }}">Excel</a>
      <a class="btn btn-outline-primary" href="#" onclick="window.print();return false;">Print / PDF</a>
   </form>
</div>
<div class="fna-radios mb-2 no-print">
   @foreach($tabs as $k=>$t)<label class="{{ $k===$tab ? 'on' : '' }}"><a href="?tab={{ $k }}&year={{ $year }}&unit={{ $unit }}{{ $dq }}" style="color:inherit;text-decoration:none">{{ $t }}@if($k==='checks' && $warn) &nbsp;!@endif</a></label>@endforeach
</div>
<div style="text-align:center;margin:4px 0 8px"><div style="font-size:18px;font-weight:700">{{ $entity ? $entity->name : '' }}</div>
   <div class="text-muted" style="font-size:12px">{{ $tab==='bs' ? 'Balance sheet as at 31 March '.substr($year,-4) : ($tab==='pl' ? 'Statement of profit and loss for the year ended 31 March '.substr($year,-4) : ($tab==='notes' ? 'Notes forming part of the financial statements' : ($tab==='ageing' ? 'Ageing as at '.date('d/m/Y',strtotime($asat)) : 'Checks before you rely on these statements'))) }} &middot; Schedule III (Division I) &middot; amounts in &#8377; {{ $s['unit_name'] }}</div></div>

@if($tab==='bs')
<div class="fna-tablebox s3" style="max-width:900px;margin:auto"><table class="table">
   <thead><tr><th>Particulars</th><th style="width:60px;text-align:center">Note</th><th style="text-align:right;width:130px">{{ $year }}</th><th style="text-align:right;width:130px">{{ $prev ?: 'Previous year' }}</th></tr></thead><tbody>
   <tr class="grp"><td colspan="4">EQUITY AND LIABILITIES</td></tr>
   @foreach($b['equity'] as $sec)
      <tr class="grp"><td colspan="4" style="padding-left:16px">{{ $loop->iteration }}. {{ $sec['title'] }}</td></tr>
      @foreach($sec['items'] as $i)<tr class="ind"><td>{{ $i['label'] }}</td><td style="text-align:center">{{ $i['note'] }}</td><td class="n">{{ $fmt($i['cur']) }}</td><td class="n">{{ $fmt($i['prev']) }}</td></tr>@endforeach
      <tr style="font-weight:700"><td style="padding-left:28px">Total {{ strtolower($sec['title']) }}</td><td></td><td class="n">{{ $fmt($sec['cur']) }}</td><td class="n">{{ $fmt($sec['prev']) }}</td></tr>
   @endforeach
   <tr class="tot"><td>TOTAL EQUITY AND LIABILITIES</td><td></td><td class="n">{{ $fmt($b['equity_total']['cur']) }}</td><td class="n">{{ $fmt($b['equity_total']['prev']) }}</td></tr>
   <tr class="grp"><td colspan="4">ASSETS</td></tr>
   @foreach($b['assets'] as $sec)
      <tr class="grp"><td colspan="4" style="padding-left:16px">{{ $loop->iteration }}. {{ $sec['title'] }}</td></tr>
      @foreach($sec['items'] as $i)<tr class="ind"><td>{{ $i['label'] }}</td><td style="text-align:center">{{ $i['note'] }}</td><td class="n">{{ $fmt($i['cur']) }}</td><td class="n">{{ $fmt($i['prev']) }}</td></tr>@endforeach
      <tr style="font-weight:700"><td style="padding-left:28px">Total {{ strtolower($sec['title']) }}</td><td></td><td class="n">{{ $fmt($sec['cur']) }}</td><td class="n">{{ $fmt($sec['prev']) }}</td></tr>
   @endforeach
   <tr class="tot"><td>TOTAL ASSETS</td><td></td><td class="n">{{ $fmt($b['assets_total']['cur']) }}</td><td class="n">{{ $fmt($b['assets_total']['prev']) }}</td></tr>
   </tbody></table></div>
@elseif($tab==='pl')
<div class="fna-tablebox s3" style="max-width:900px;margin:auto"><table class="table">
   <thead><tr><th>Particulars</th><th style="width:60px;text-align:center">Note</th><th style="text-align:right;width:130px">{{ $year }}</th><th style="text-align:right;width:130px">{{ $prev ?: 'Previous year' }}</th></tr></thead><tbody>
   <tr><td>I. Revenue from operations</td><td style="text-align:center">{{ $p['revenue']['note'] }}</td><td class="n">{{ $fmt($p['revenue']['cur']) }}</td><td class="n">{{ $fmt($p['revenue']['prev']) }}</td></tr>
   <tr><td>II. Other income</td><td style="text-align:center">{{ $p['other_income']['note'] }}</td><td class="n">{{ $fmt($p['other_income']['cur']) }}</td><td class="n">{{ $fmt($p['other_income']['prev']) }}</td></tr>
   <tr class="tot"><td>III. Total income</td><td></td><td class="n">{{ $fmt($p['total_income']['cur']) }}</td><td class="n">{{ $fmt($p['total_income']['prev']) }}</td></tr>
   <tr class="grp"><td colspan="4">IV. Expenses</td></tr>
   @foreach($p['expenses'] as $e)<tr class="ind"><td>{{ $e['label'] }}</td><td style="text-align:center">{{ $e['note'] }}</td><td class="n">{{ $fmt($e['cur']) }}</td><td class="n">{{ $fmt($e['prev']) }}</td></tr>@endforeach
   <tr class="tot"><td>Total expenses</td><td></td><td class="n">{{ $fmt($p['total_expenses']['cur']) }}</td><td class="n">{{ $fmt($p['total_expenses']['prev']) }}</td></tr>
   <tr><td>V. Profit before tax (III &minus; IV)</td><td></td><td class="n">{{ $fmt($p['pbt']['cur']) }}</td><td class="n">{{ $fmt($p['pbt']['prev']) }}</td></tr>
   <tr><td>VI. Tax expense</td><td style="text-align:center">{{ $p['tax']['note'] }}</td><td class="n">{{ $fmt($p['tax']['cur']) }}</td><td class="n">{{ $fmt($p['tax']['prev']) }}</td></tr>
   <tr class="tot"><td>VII. Profit for the year (V &minus; VI)</td><td></td><td class="n">{{ $fmt($p['profit']['cur']) }}</td><td class="n">{{ $fmt($p['profit']['prev']) }}</td></tr>
   </tbody></table></div>
@elseif($tab==='dept')
@php $f2 = $byDept['factor']; $v2 = fn($v) => (int)round($v/$f2) == 0 ? '-' : ((int)round($v/$f2) < 0 ? '('.number_format(abs((int)round($v/$f2))).')' : number_format((int)round($v/$f2))); @endphp
<div class="fna-tablebox s3" style="max-width:1100px;margin:auto"><table class="table">
   <thead><tr><th>Statement of profit and loss by department</th>@foreach($byDept['cols'] as $c)<th style="text-align:right">{{ $c['name'] }}</th>@endforeach<th style="text-align:right">Total</th></tr></thead><tbody>
   @foreach($byDept['lines'] as [$k,$label])
      <tr class="{{ $k==='profit' ? 'tot' : '' }}"><td>{{ $label }}</td>@foreach($byDept['cols'] as $c)<td class="n">{{ $v2($byDept['data'][$c['key']][$k] ?? 0) }}</td>@endforeach<td class="n">{{ $v2($byDept['totals'][$k] ?? 0) }}</td></tr>
   @endforeach
   </tbody></table></div>
<div class="fna-foot-note" style="text-align:center">Entries carry a department when it is chosen on a journal row or an invoice or bill line. Everything else shows under "No department". Columns may differ from the total by rounding.</div>
@elseif($tab==='tb')
<div class="fna-tablebox s3" style="max-width:900px;margin:auto"><table class="table">
   <thead><tr><th>Account</th><th style="text-align:right;width:160px">Debit</th><th style="text-align:right;width:160px">Credit</th></tr></thead><tbody>
   @foreach($tb['rows'] as $r)<tr><td>{{ $r['name'] }}</td><td class="n">{{ $r['debit'] ? number_format($r['debit'],2) : '' }}</td><td class="n">{{ $r['credit'] ? number_format($r['credit'],2) : '' }}</td></tr>@endforeach
   <tr class="tot"><td>Total</td><td class="n">{{ number_format($tb['debit'],2) }}</td><td class="n">{{ number_format($tb['credit'],2) }}</td></tr>
   </tbody></table></div>
<div class="fna-foot-note" style="text-align:center">{{ $dept ? 'For the department chosen above. Pick "All" to see the whole company.' : 'Whole company.' }} A single department will not balance on its own, because the other side of an entry (a bank or a payable) usually has no department.</div>
@elseif($tab==='notes')
@php $f = $s['factor']; $nf = fn($v) => $v===null ? '' : ((int)round($v/$f) == 0 ? '-' : ((int)round($v/$f) < 0 ? '('.number_format(abs((int)round($v/$f))).')' : number_format((int)round($v/$f)))); @endphp
@forelse($s['notes'] as $n)
   <div class="s3" style="max-width:900px;margin:0 auto 14px"><div style="font-weight:700;margin-bottom:3px">Note {{ $n['no'] }}: {{ $n['label'] }}</div>
   <div class="fna-tablebox"><table class="table"><thead><tr><th>Particulars</th><th style="text-align:right;width:130px">{{ $year }}</th><th style="text-align:right;width:130px">{{ $prev ?: 'Previous year' }}</th></tr></thead><tbody>
   @php $totC = 0; $totP = 0; @endphp
   @php $subs = $n['subs']; if(isset($subs['_'])){ $subs = ['_'=>$subs['_']] + $subs; } $multi = count($subs) > 1; @endphp
   @foreach($subs as $sub=>$d)
      @if($sub!=='_' || $multi)<tr class="grp"><td colspan="3">{{ $sub==='_' ? 'Other' : $sub }}</td></tr>@endif
      @php $heads = []; foreach(($d['cur'] ?? []) as $r){ $heads[$r['head']]['cur'] = ($heads[$r['head']]['cur'] ?? 0) + $r['amount']; } foreach(($d['prev'] ?? []) as $r){ $heads[$r['head']]['prev'] = ($heads[$r['head']]['prev'] ?? 0) + $r['amount']; } @endphp
      @foreach($heads as $hn=>$v)@php $totC += $v['cur'] ?? 0; $totP += $v['prev'] ?? 0; @endphp<tr class="ind"><td>{{ $hn }}</td><td class="n">{{ $nf($v['cur'] ?? 0) }}</td><td class="n">{{ $prev ? $nf($v['prev'] ?? 0) : '' }}</td></tr>@endforeach
   @endforeach
   <tr class="tot"><td>Total</td><td class="n">{{ $nf($totC) }}</td><td class="n">{{ $prev ? $nf($totP) : '' }}</td></tr>
   </tbody></table></div></div>
@empty
   <p class="text-muted" style="text-align:center">No notes: there are no amounts in this year.</p>
@endforelse
@elseif($tab==='ageing')
@php $bandsFmt = fn($v) => $v == 0 ? '-' : number_format($v, 2); @endphp
@foreach([['Trade receivables (open sales invoices)','receivables'],['Trade payables (open purchase bills)','payables']] as [$title,$k])
   <div class="s3" style="max-width:900px;margin:0 auto 14px"><div style="font-weight:700;margin-bottom:3px">{{ $title }}</div>
   <div class="fna-tablebox"><table class="table"><thead><tr><th>Outstanding for (from the due date)</th><th style="text-align:right;width:160px">Amount (&#8377;)</th></tr></thead><tbody>
   @foreach($ageing[$k]['bands'] as $band=>$amt)<tr><td>{{ $band }}</td><td class="n">{{ $bandsFmt($amt) }}</td></tr>@endforeach
   <tr class="tot"><td>Total of open {{ $k==='receivables' ? 'invoices' : 'bills' }} ({{ $ageing[$k]['count'] }})</td><td class="n">{{ $bandsFmt($ageing[$k]['total']) }}</td></tr>
   </tbody></table></div></div>
@endforeach
<div class="fna-foot-note" style="max-width:900px;margin:auto">Ageing is built from the invoices and bills raised in FNA. Balances that were brought forward without invoices (opening balances) are not in these bands.</div>
@else
<div style="max-width:900px;margin:auto">
   <div class="alert {{ abs($ck['bs_difference'])>0.5 ? 'alert-danger' : 'alert-success' }}">Balance sheet: assets less equity and liabilities = <strong>{{ number_format($ck['bs_difference'],2) }}</strong> {{ abs($ck['bs_difference'])>0.5 ? '(the books do not balance: do not rely on this)' : '(balanced)' }}</div>
   <div class="alert {{ $ck['uncategorised'] ? 'alert-danger' : 'alert-success' }}">{{ $ck['uncategorised'] }} uncategorised bank line(s) in {{ $year }}. {{ $ck['uncategorised'] ? 'They are not in these statements until categorised.' : '' }}</div>
   <div class="alert {{ count($ck['unmapped']) ? 'alert-danger' : 'alert-success' }}">{{ count($ck['unmapped']) }} account(s) with a balance are not in the approved Schedule III mapping.@foreach($ck['unmapped'] as $u)<div>&bull; {{ $u['name'] }}: {{ number_format($u['amount'],2) }} (shown under "UNMAPPED" so nothing is left out)</div>@endforeach</div>
   <div class="alert {{ count($ck['clear']) ? 'alert-danger' : 'alert-success' }}">{{ count($ck['clear']) }} ledger(s) that you said should be removed still have a balance.@foreach($ck['clear'] as $u)<div>&bull; {{ $u['name'] }}: {{ number_format($u['amount'],2) }}: clear it before finalising</div>@endforeach</div>
</div>
@endif
<div class="fna-foot-note no-print" style="text-align:center">Only categorised entries are included. Cash flow statement is not part of these statements.@if(!$prev) The previous year has no entries in FNA, so its column is empty.@endif</div>
@endsection
