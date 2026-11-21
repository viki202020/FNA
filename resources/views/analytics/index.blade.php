@extends('layout.master')
@section('title', 'Data analytics')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php
   if($tab==='recon'){
   $t = $r['trial']; $sm = $r['summary']; $ex = $r['extras']; $carry = $r['carry'];
   $m = fn($v) => number_format(abs($v), 2);
   $tiesBad = collect($r['ties'])->filter(fn($x) => abs($x['diff']) > 0.5)->count();
   $ok = abs($t['difference']) < 0.5 && abs($sm['difference']) < 0.5;
   }
   $tabs = ['recon'=>'Balance sheet reconciliation','receivables'=>'Receivables','payables'=>'Payables','ratios'=>'Ratio analysis','tds'=>'TDS'];
@endphp
<style>.ana td.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.ana .tot td{font-weight:700;border-top:2px solid var(--fna-green)}</style>
<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:6px">
   <h1 style="margin:0">Data analytics</h1>
   <form method="get" style="margin:0"><input type="hidden" name="tab" value="{{ $tab }}"><label style="display:inline-flex;gap:6px;align-items:center;margin:0">Financial year
      <select name="year" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="this.form.submit()">@foreach($year_options as $y)<option value="{{ $y }}" @if($y==$year) selected @endif>{{ $y }}@if(!\App\Support\FinancialYears::isOpen($y)) (closed)@endif</option>@endforeach</select></label></form>
</div>
<div class="fna-radios mb-2">@foreach($tabs as $k=>$tt)<label class="{{ $k===$tab ? 'on' : '' }}"><a href="?tab={{ $k }}&year={{ $year }}" style="color:inherit;text-decoration:none">{{ $tt }}</a></label>@endforeach</div>
@if($tab==='receivables' || $tab==='payables')
@php $isRec = $tab==='receivables'; @endphp
<p class="text-muted" style="max-width:900px">{{ $isRec ? 'Which customers owe you money, how much, by when, and how long overdue. Chase the ones in the right-hand columns first.' : 'What you owe, to whom, by when, and how long overdue. Pay in time to keep suppliers and cash flow in order.' }}</p>
<div class="fna-tiles" style="max-width:1000px">
   <div class="fna-tile"><div class="k">{{ $isRec ? 'Receivable' : 'Payable' }} in total</div><div class="v">{{ number_format($out['total'],2) }}</div><div class="s">{{ $out['count'] }} open {{ $isRec ? 'invoices' : 'bills' }}, {{ count($out['parties']) }} {{ count($out['parties'])==1 ? 'party' : 'parties' }}</div></div>
   <div class="fna-tile dark"><div class="k">Overdue</div><div class="v">{{ number_format($out['overdue'],2) }}</div><div class="s">as at {{ date('d/m/Y',strtotime($as_at)) }}</div></div>
   <div class="fna-tile grey"><div class="k">Not yet due</div><div class="v">{{ number_format($out['buckets']['Not yet due'],2) }}</div></div>
</div>
<div class="fna-tablebox ana" style="max-width:1100px"><table class="table">
   <thead><tr><th>Party</th>@foreach(\App\Support\Outstanding::BUCKETS as $bk)<th style="text-align:right">{{ $bk }}</th>@endforeach<th style="text-align:right">Total</th></tr></thead><tbody>
   @forelse($out['parties'] as $p)
      <tr style="font-weight:700"><td>{{ $p['name'] }}</td>@foreach(\App\Support\Outstanding::BUCKETS as $bk)<td class="n">{{ $p['buckets'][$bk] ? number_format($p['buckets'][$bk],2) : '' }}</td>@endforeach<td class="n">{{ number_format($p['total'],2) }}</td></tr>
      @foreach($p['docs'] as $d)
         <tr class="ind"><td style="padding-left:28px;font-weight:400"><a href="{{ url('merchant/'.($isRec?'invoices':'bills').'/'.$d['id']) }}" style="font-weight:700;text-decoration:none">{{ $d['number'] }}</a> <span class="text-muted" style="font-size:12px">raised {{ date('d/m/Y',strtotime($d['date'])) }}, due {{ date('d/m/Y',strtotime($d['due'])) }}@if($d['overdue_days']), {{ $d['overdue_days'] }} days overdue @endif</span></td>
            @foreach(\App\Support\Outstanding::BUCKETS as $bk)<td class="n" style="font-weight:400">{{ $d['bucket']===$bk ? number_format($d['balance'],2) : '' }}</td>@endforeach<td class="n" style="font-weight:400">{{ number_format($d['balance'],2) }}</td></tr>
      @endforeach
   @empty
      <tr><td colspan="7" class="text-muted">Nothing outstanding.</td></tr>
   @endforelse
   @if(count($out['parties']))<tr class="tot"><td>Total</td>@foreach(\App\Support\Outstanding::BUCKETS as $bk)<td class="n">{{ number_format($out['buckets'][$bk],2) }}</td>@endforeach<td class="n">{{ number_format($out['total'],2) }}</td></tr>@endif
   </tbody></table></div>
<div class="fna-foot-note">Built from the {{ $isRec ? 'sales invoices' : 'purchase bills' }} raised in FNA and not yet fully settled. Balances brought forward without invoices are not listed here.</div>
@elseif($tab==='tds')
<p class="text-muted" style="max-width:900px">Tax you have deducted from payments and still owe the tax department, month by month. It is due on the 7th of the next month (30 April for March). Deposits are applied to the oldest month first. A deposit shows here once the bank line paying the government is categorised to the TDS Payable account.</p>
<div class="fna-tablebox ana" style="max-width:1000px"><table class="table">
   <thead><tr><th>Month</th><th style="text-align:right">Deductions</th><th style="text-align:right">Deducted</th><th style="text-align:right">Deposited</th><th style="text-align:right">Still to deposit</th><th>Due date</th><th>Status</th></tr></thead><tbody>
   @forelse($tdsMonths as $m)
      @php $days = (int) floor((strtotime($m['due_date']) - strtotime(date('Y-m-d'))) / 86400); @endphp
      <tr><td class="fw-bold">{{ $m['label'] }}</td><td class="n">{{ $m['count'] }}</td><td class="n">{{ number_format($m['deducted'],2) }}</td><td class="n">{{ number_format($m['deposited'],2) }}</td><td class="n fw-bold">{{ number_format($m['outstanding'],2) }}</td><td>{{ date('d/m/Y', strtotime($m['due_date'])) }}</td>
         <td>@if($m['outstanding'] <= 0.004)<span class="fna-chip posted">Deposited</span>@elseif($days < 0)<span class="fna-chip draft">Overdue {{ abs($days) }} days</span>@else<span class="fna-chip waiting">Due in {{ $days }} days</span>@endif</td></tr>
   @empty
      <tr><td colspan="7" class="text-muted">No TDS has been deducted yet.</td></tr>
   @endforelse
   </tbody></table></div>
@foreach($tdsEntries as $ym=>$entries)
   <div class="fna-section-title">Deductions in {{ date('F Y', strtotime($ym.'-01')) }} still to deposit</div>
   <div class="fna-tablebox ana" style="max-width:1000px"><table class="table"><thead><tr><th>Date</th><th>Voucher</th><th>Narration</th><th style="text-align:right">TDS</th></tr></thead><tbody>
      @foreach($entries as $e)<tr><td>{{ date('d/m/Y', strtotime($e->st_date)) }}</td><td><a href="{{ url('merchant/voucher/'.$e->voucher_no) }}" class="fw-bold" style="text-decoration:none">{{ $e->voucher_no }}</a></td><td>{{ $e->narration }}</td><td class="n">{{ number_format($e->credit,2) }}</td></tr>@endforeach
   </tbody></table></div>
@endforeach
<div class="fna-foot-note">Paying the tax department: categorise the bank payment to the TDS Payable account, and the deposit appears here. FNA does not prepare the TDS return (Form 26Q) yet.</div>
@elseif($tab==='ratios')
<p class="text-muted" style="max-width:900px">The business's financial health, this year against last year. Ratios come from the Schedule III figures and use only categorised entries.</p>
@php $fmtR = function($v,$u){ if($v===null){ return '-'; } return number_format($v, $u==='days' ? 0 : ($u==='%' ? 1 : 2)).($u==='x' ? '' : ($u==='%' ? '%' : ' days')); }; @endphp
<div class="fna-tablebox ana" style="max-width:1000px"><table class="table">
   <thead><tr><th>Ratio</th><th>How it is worked out</th><th style="text-align:right">{{ $ratios['year'] }}</th><th style="text-align:right">{{ $ratios['prev_year'] ?: 'Previous year' }}</th><th>Change</th></tr></thead><tbody>
   @php $g=''; @endphp
   @foreach($ratios['rows'] as $row)
      @if($row['group']!==$g)@php $g=$row['group']; @endphp<tr class="grp"><td colspan="5" style="font-weight:700;color:var(--fna-green);background:var(--fna-tint)">{{ $g }}</td></tr>@endif
      <tr><td class="fw-bold">{{ $row['name'] }}<div class="text-muted" style="font-size:12px;font-weight:400">{{ $row['means'] }}</div></td><td class="text-muted" style="font-size:12px">{{ $row['formula'] }}</td>
         <td class="n fw-bold">{{ $fmtR($row['cur'],$row['unit']) }}</td><td class="n">{{ $fmtR($row['prev'],$row['unit']) }}</td>
         <td>@if($row['cur']!==null && $row['prev']!==null){{ $row['cur']>$row['prev'] ? 'Up' : ($row['cur']<$row['prev'] ? 'Down' : 'Same') }}@endif</td></tr>
   @endforeach
   </tbody></table></div>
<div class="fna-foot-note">Ratios are most useful as a trend over several years and against similar businesses. A dash means the ratio cannot be worked out (for example, no revenue or no liabilities). Comparison with other businesses needs an industry data source, which FNA does not have.</div>
@else
<p class="text-muted" style="max-width:900px">Verifies the balance sheet: every component's closing balance, assets against liabilities, and each component against its own records. A difference is shown with its amount so it can be traced and fixed. Only categorised entries are included.</p>

<div class="alert {{ $ok ? 'alert-success' : 'alert-danger' }}" style="max-width:1000px">
   @if($ok)<strong>The balance sheet reconciles.</strong> Debits equal credits, and total assets equal total liabilities ({{ $m($sm['total_assets']) }}).@else<strong>The balance sheet does not reconcile.</strong> Trial difference {{ number_format($t['difference'],2) }}; assets less liabilities {{ number_format($sm['difference'],2) }}.@endif
   @if($tiesBad) <div>{{ $tiesBad }} component(s) differ from their own records: see section 3.</div>@endif
</div>

<div class="fna-section-title">1. Closing balance of every component</div>
<div class="fna-tablebox ana" style="max-width:1000px"><table class="table">
   <thead><tr><th>Particulars</th><th style="text-align:right;width:150px">Debit</th><th style="text-align:right;width:150px">Credit</th></tr></thead><tbody>
   @foreach($t['components'] as $c)<tr><td>{{ $c['label'] }}</td><td class="n">{{ $c['debit'] ? $m($c['debit']) : '' }}</td><td class="n">{{ $c['credit'] ? $m($c['credit']) : '' }}</td></tr>@endforeach
   <tr class="tot"><td>Total</td><td class="n">{{ $m($t['debit']) }}</td><td class="n">{{ $m($t['credit']) }}</td></tr>
   <tr><td>Difference</td><td colspan="2" class="n" style="font-weight:700;color:{{ abs($t['difference'])<0.5 ? 'var(--fna-green)' : '#013602' }}">{{ abs($t['difference'])<0.5 ? 'Nil: balanced' : number_format($t['difference'],2) }}</td></tr>
   </tbody></table></div>
<div class="text-muted" style="font-size:12px;max-width:1000px;margin-top:3px">The year's profit of {{ number_format($ex['profit'],2) }} is included in Reserves and surplus, so the profit and loss accounts are inside this check.</div>

<div class="fna-section-title">2. Assets against liabilities</div>
<div class="fna-tablebox ana" style="max-width:1000px"><table class="table">
   <thead><tr><th>Assets</th><th style="text-align:right;width:150px">Amount</th><th style="width:30px"></th><th>Liabilities</th><th style="text-align:right;width:150px">Amount</th></tr></thead><tbody>
   @php $rows = max(count($sm['assets']), count($sm['liabilities'])); @endphp
   @for($i=0;$i<$rows;$i++)<tr><td>{{ $sm['assets'][$i][0] ?? '' }}</td><td class="n">{{ isset($sm['assets'][$i]) ? number_format($sm['assets'][$i][1],2) : '' }}</td><td></td><td>{{ $sm['liabilities'][$i][0] ?? '' }}</td><td class="n">{{ isset($sm['liabilities'][$i]) ? number_format($sm['liabilities'][$i][1],2) : '' }}</td></tr>@endfor
   <tr class="tot"><td>Total assets</td><td class="n">{{ number_format($sm['total_assets'],2) }}</td><td></td><td>Total liabilities</td><td class="n">{{ number_format($sm['total_liabilities'],2) }}</td></tr>
   </tbody></table></div>
<div class="text-muted" style="font-size:12px;max-width:1000px;margin-top:3px">{{ abs($sm['difference'])<0.5 ? 'The two totals match, so the values are accurate.' : 'The totals differ by '.number_format(abs($sm['difference']),2).'. Find the error: check the tie-outs below, then the uncategorised lines and the opening balances.' }}</div>

<div class="fna-section-title">3. Each component against its own records</div>
<div class="fna-tablebox ana" style="max-width:1000px"><table class="table">
   <thead><tr><th>Check</th><th style="text-align:right">This side</th><th style="text-align:right">Other side</th><th style="text-align:right;width:130px">Difference</th></tr></thead><tbody>
   @foreach($r['ties'] as $x)
      <tr><td>{{ $x['name'] }}<div class="text-muted" style="font-size:12px;font-weight:400">{{ $x['note'] }}</div></td>
         <td class="n">{{ number_format($x['a'],2) }}<div class="text-muted" style="font-size:11px">{{ $x['a_label'] }}</div></td>
         <td class="n">{{ number_format($x['b'],2) }}<div class="text-muted" style="font-size:11px">{{ $x['b_label'] }}</div></td>
         <td class="n"><span class="fna-chip {{ abs($x['diff'])<0.5 ? 'posted' : 'draft' }}">{{ abs($x['diff'])<0.5 ? 'Agrees' : number_format($x['diff'],2) }}</span></td></tr>
   @endforeach
   <tr><td>This year's opening balances against {{ $carry['prev_year'] }}'s closing balances
         <div class="text-muted" style="font-size:12px;font-weight:400">@if($carry['checked']) Each balance sheet account's opening entry should equal last year's closing balance. @else Not checked: this year has no opening entries, or the previous year has no entries in FNA. @endif</div></td>
      <td class="n"></td><td class="n"></td><td class="n">@if($carry['checked'])<span class="fna-chip {{ count($carry['differences']) ? 'draft' : 'posted' }}">{{ count($carry['differences']) ? count($carry['differences']).' differ' : 'Agrees' }}</span>@endif</td></tr>
   @foreach($carry['differences'] as $d)<tr class="ind"><td style="padding-left:28px">{{ $d['head'] }}</td><td class="n">{{ number_format($d['opening'],2) }}<div class="text-muted" style="font-size:11px">Opening</div></td><td class="n">{{ number_format($d['closing'],2) }}<div class="text-muted" style="font-size:11px">Last year's closing</div></td><td class="n">{{ number_format($d['diff'],2) }}</td></tr>@endforeach
   </tbody></table></div>

<div class="fna-section-title">4. Things that can cause a difference</div>
<div style="max-width:1000px">
   <div class="alert {{ $ex['uncategorised'] ? 'alert-danger' : 'alert-success' }}">{{ $ex['uncategorised'] }} uncategorised bank line(s) in {{ $year }}. They are left out of the balance sheet until categorised.</div>
   <div class="alert {{ count($ex['unmapped']) ? 'alert-danger' : 'alert-success' }}">{{ count($ex['unmapped']) }} account(s) with a balance are not in the approved Schedule III mapping.@foreach($ex['unmapped'] as $u)<div>&bull; {{ $u['name'] }}: {{ number_format($u['amount'],2) }}</div>@endforeach</div>
   <div class="alert {{ count($ex['clear']) ? 'alert-danger' : 'alert-success' }}">{{ count($ex['clear']) }} ledger(s) marked for removal still have a balance.@foreach($ex['clear'] as $u)<div>&bull; {{ $u['name'] }}: {{ number_format($u['amount'],2) }}</div>@endforeach</div>
</div>
<div class="fna-foot-note">Because FNA posts every entry in double entry, the balance sheet should always reconcile. This page is the proof, and the place to look first if a figure ever looks wrong.</div>
@endif
@endsection
