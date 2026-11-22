@extends('layout.master')
@section('title', 'TDS return')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $m = fn($v) => number_format($v, 2); $tabs = ['deposits' => 'Deposits and challans', 'return' => 'Quarterly return']; @endphp
<style>.tdsx td.n,.tdsx th.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.tdsx input,.tdsx select{min-width:0}</style>
<h1 class="mb-1">TDS return</h1>
<div class="fna-radios mb-2">@foreach($tabs as $k=>$tt)<label class="{{ $k===$tab ? 'on' : '' }}"><a href="?tab={{ $k }}@if($cur)&q={{ $cur['key'] }}@endif" style="color:inherit;text-decoration:none">{{ $tt }}</a></label>@endforeach</div>
@if(session('success_msg'))<div class="alert alert-success" style="max-width:1100px">{{ session('success_msg') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="max-width:1100px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

@if($tab==='deposits')
<p class="text-muted" style="max-width:950px">Each payment you made to the government (a debit to TDS Payable) is listed here. Enter the challan details from the receipt, and the month of deduction it pays for. Challan details go into the quarterly return. TDS is due on the 7th of the next month (30 April for March).</p>
<div class="fna-tablebox tdsx" style="max-width:1200px"><table class="table">
   <thead><tr><th>Paid on</th><th class="n">Amount</th><th>For month</th><th>BSR code</th><th>Challan serial</th><th class="n">Interest</th><th class="n">Fee</th><th></th></tr></thead><tbody>
   @forelse($deposits as $d)
   <tr>
      <td><form id="f{{ $d->id }}" method="post" action="{{ url('merchant/tds/challan') }}" style="margin:0">@csrf<input type="hidden" name="statement_id" value="{{ $d->id }}"></form>{{ date('d/m/Y', strtotime($d->st_date)) }}<div class="text-muted" style="font-size:12px">{{ $d->voucher_no }}</div>@if($d->late_days)<div style="font-size:12px;color:#a05a00">{{ $d->late_days }} day(s) after the due date ({{ date('d/m/Y', strtotime($d->due)) }}). Interest may apply</div>@endif</td>
      <td class="n">{{ $m($d->amount) }}</td>
      <td><input form="f{{ $d->id }}" type="month" name="for_month" class="form-control" value="{{ $d->suggested }}" required style="width:150px"></td>
      <td><input form="f{{ $d->id }}" name="bsr_code" class="form-control" value="{{ $d->bsr_code }}" placeholder="7 digits" maxlength="7" style="width:100px"></td>
      <td><input form="f{{ $d->id }}" name="challan_serial" class="form-control" value="{{ $d->challan_serial }}" placeholder="up to 5 digits" maxlength="5" style="width:110px"></td>
      <td><input form="f{{ $d->id }}" name="interest" class="form-control" value="{{ $d->interest ?: '' }}" style="width:90px"></td>
      <td><input form="f{{ $d->id }}" name="fee" class="form-control" value="{{ $d->fee ?: '' }}" style="width:90px"></td>
      <td><button form="f{{ $d->id }}" class="btn btn-sm btn-primary">{{ $d->for_month ? 'Update' : 'Save' }}</button></td></tr>
   @empty<tr><td colspan="8" class="text-muted">No TDS deposits in the books yet. Categorise the bank line that paid the challan to the TDS Payable account.</td></tr>@endforelse
</tbody></table></div>
<h5 style="margin-top:18px">What is still to pay</h5>
<div class="fna-tablebox tdsx" style="max-width:900px"><table class="table"><thead><tr><th>Month of deduction</th><th>Due date</th><th class="n">Deducted</th><th class="n">Deposited</th><th class="n">Still to pay</th></tr></thead><tbody>
   @forelse($months as $x)<tr><td>{{ $x['label'] }}</td><td>{{ date('d/m/Y', strtotime($x['due_date'])) }}</td><td class="n">{{ $m($x['deducted']) }}</td><td class="n">{{ $m($x['deposited']) }}</td><td class="n">{{ $m($x['outstanding']) }}</td></tr>@empty<tr><td colspan="5" class="text-muted">No TDS deducted yet.</td></tr>@endforelse
</tbody></table></div>

@else
@if(!$cur)
   <div class="alert alert-secondary" style="max-width:900px">No TDS has been deducted yet, so there is nothing to report.</div>
@else
<form method="get" style="margin:0 0 8px"><input type="hidden" name="tab" value="return"><label style="display:inline-flex;gap:6px;align-items:center">Quarter
   <select name="q" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="this.form.submit()">@foreach($quarters as $x)<option value="{{ $x['key'] }}" @if($x['key']===$cur['key']) selected @endif>{{ $x['label'] }}</option>@endforeach</select></label></form>
<p class="text-muted" style="max-width:950px"><b>{{ $form }}</b> (TDS on payments to residents other than salary). Due by {{ date('d/m/Y', strtotime($due)) }}. These are working papers: download the two tables and enter or import them in the return preparation utility, then file from your TDS login. @if($cur['fy']>=2026)From 1 April 2026 the Income-tax Act, 2025 applies, so the return uses its section and payment codes, not the old 194 numbers. Please check the codes against the department's current list.@endif</p>
<h5>1. Deducted and deposited, by month</h5>
<div class="fna-tablebox tdsx" style="max-width:900px"><table class="table"><thead><tr><th>Month</th><th>Deposit due</th><th class="n">Deducted</th><th class="n">Deposited (challans)</th><th class="n">Difference</th></tr></thead><tbody>
   @foreach($monthly as $x)<tr><td>{{ $x['label'] }}</td><td>{{ date('d/m/Y', strtotime($x['due'])) }}</td><td class="n">{{ $m($x['deducted']) }}</td><td class="n">{{ $m($x['deposited']) }}</td><td class="n" @if(abs($x['diff'])>0.5) style="color:#a05a00;font-weight:700" @endif>{{ $m($x['diff']) }}</td></tr>@endforeach
</tbody></table></div>
<p class="text-muted" style="max-width:950px">The difference must be nil before filing. A positive difference means tax deducted but not yet deposited with challan details (see the Deposits tab).</p>

<h5 style="margin-top:16px">2. Challan details <a class="btn btn-sm btn-outline-primary" style="margin-left:8px" href="{{ url('merchant/tds/challans.csv?q='.$cur['key']) }}">Download CSV</a></h5>
<div class="fna-tablebox tdsx" style="max-width:900px"><table class="table"><thead><tr><th>Month</th><th>Deposited on</th><th>BSR</th><th>Serial</th><th class="n">Tax</th><th class="n">Interest</th><th class="n">Fee</th></tr></thead><tbody>
   @forelse($challans as $c)<tr><td>{{ date('M Y', strtotime($c->for_month.'-01')) }}</td><td>{{ date('d/m/Y', strtotime($c->st_date)) }}</td><td>{{ $c->bsr_code ?: '(missing)' }}</td><td>{{ $c->challan_serial ?: '(missing)' }}</td><td class="n">{{ $m((float)$c->debit) }}</td><td class="n">{{ $m($c->interest) }}</td><td class="n">{{ $m($c->fee) }}</td></tr>@empty<tr><td colspan="7" class="text-muted">No challan details entered for this quarter.</td></tr>@endforelse
</tbody></table></div>

<h5 style="margin-top:16px">3. Deductees <a class="btn btn-sm btn-outline-primary" style="margin-left:8px" href="{{ url('merchant/tds/deductees.csv?q='.$cur['key']) }}">Download CSV</a></h5>
<div class="fna-tablebox tdsx" style="max-width:1200px"><table class="table"><thead><tr><th>Deductee</th><th>PAN</th><th>Section</th><th>Credited on</th><th class="n">Amount</th><th>Deducted on</th><th class="n">Rate</th><th class="n">TDS</th><th>Check</th></tr></thead><tbody>
   @forelse($ded as $d)<tr><td>{{ $d['name'] ?: '(unknown)' }}<div class="text-muted" style="font-size:12px">{{ $d['voucher_no'] }}</div></td><td>{{ $d['pan'] }}</td><td>{{ $d['section'] }}</td><td>{{ $d['credit_date'] ? date('d/m/Y', strtotime($d['credit_date'])) : '' }}</td><td class="n">{{ $d['base']!==null ? $m($d['base']) : '' }}</td><td>{{ date('d/m/Y', strtotime($d['date'])) }}</td><td class="n">{{ $d['rate'] }}</td><td class="n">{{ $m($d['tds']) }}</td>
      <td style="font-size:12px;color:#a05a00">@foreach($d['issues'] as $i)<div>{{ $i }}</div>@endforeach</td></tr>
   @empty<tr><td colspan="9" class="text-muted">No TDS deducted in this quarter.</td></tr>@endforelse
</tbody></table></div>
<p class="text-muted" style="max-width:950px">Amount is worked out as TDS divided by the deductee's rate, so check it when a different rate was applied. Reversed entries are left out.</p>
@endif
@endif
@endsection
