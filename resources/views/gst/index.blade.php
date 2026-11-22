@extends('layout.master')
@section('title', 'GST reports')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php
   $tabs = ['3b' => 'GSTR-3B summary', 'gstr1' => 'GSTR-1 invoices', 'hsn' => 'HSN / SAC summary'];
   $m = fn($v) => number_format($v, 2);
   $label = date('F Y', strtotime($ym . '-01'));
@endphp
<style>.gst td.n,.gst th.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}.gst .tot td{font-weight:700;border-top:2px solid var(--fna-green)}</style>
<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:space-between;align-items:center;margin-bottom:6px">
   <h1 style="margin:0">GST reports</h1>
   <form method="get" style="margin:0"><input type="hidden" name="tab" value="{{ $tab }}"><label style="display:inline-flex;gap:6px;align-items:center;margin:0">Month
      <select name="month" class="form-select" style="width:auto;font-weight:700;color:var(--fna-green)" onchange="this.form.submit()">@foreach($months as $x)<option value="{{ $x }}" @if($x==$ym) selected @endif>{{ date('F Y', strtotime($x.'-01')) }}</option>@endforeach</select></label></form>
</div>
<div class="fna-radios mb-2">@foreach($tabs as $k=>$tt)<label class="{{ $k===$tab ? 'on' : '' }}"><a href="?tab={{ $k }}&month={{ $ym }}" style="color:inherit;text-decoration:none">{{ $tt }}</a></label>@endforeach</div>
<p class="text-muted" style="max-width:900px">Built from the invoices and bills you have <b>posted</b> in {{ $label }} (drafts and voided documents are left out). These are working papers for preparing the return; the filing itself is done on the GST portal.</p>

@if($tab==='3b')
<div class="fna-tablebox gst" style="max-width:1000px"><table class="table">
   <thead><tr><th>Table 3.1 and 4 of GSTR-3B</th><th class="n">Taxable value</th><th class="n">IGST</th><th class="n">CGST</th><th class="n">SGST</th></tr></thead><tbody>
   <tr><td><b>3.1(a)</b> Outward taxable supplies (rated) <div class="text-muted" style="font-size:12px">{{ $d['sales_count'] }} sales invoice(s)</div></td><td class="n">{{ $m($d['out']['taxable']) }}</td><td class="n">{{ $m($d['out']['igst']) }}</td><td class="n">{{ $m($d['out']['cgst']) }}</td><td class="n">{{ $m($d['out']['sgst']) }}</td></tr>
   <tr><td><b>3.1(c)</b> Nil-rated / exempt outward supplies <div class="text-muted" style="font-size:12px">invoice lines with GST at 0%</div></td><td class="n">{{ $m($d['nil']) }}</td><td class="n">-</td><td class="n">-</td><td class="n">-</td></tr>
   <tr><td><b>4(A)</b> Input tax credit from purchase bills <div class="text-muted" style="font-size:12px">{{ $d['purchase_count'] }} purchase bill(s) booked in FNA</div></td><td class="n">{{ $m($d['itc']['taxable']) }}</td><td class="n">{{ $m($d['itc']['igst']) }}</td><td class="n">{{ $m($d['itc']['cgst']) }}</td><td class="n">{{ $m($d['itc']['sgst']) }}</td></tr>
   <tr class="tot"><td>Tax payable in cash (output tax less credit)</td><td></td>
      @foreach(['igst','cgst','sgst'] as $k)<td class="n" @if($d['net'][$k] < 0) style="color:#a05a00" @endif>{{ $d['net'][$k] >= 0 ? $m($d['net'][$k]) : '('.$m(-$d['net'][$k]).') carry forward' }}</td>@endforeach</tr>
   </tbody></table></div>
<p class="text-muted" style="max-width:900px;margin-top:10px">
   Credit is first used against the same tax, and IGST credit can also be used against CGST and SGST. The portal makes that set-off for you, so treat the last row as a guide.
   Reverse-charge purchases, credit reversals and interest are not in these figures.</p>
@if($d['two_b']['n'])
<div class="alert @if(abs($d['two_b']['cgst']+$d['two_b']['sgst']+$d['two_b']['igst'] - ($d['itc']['cgst']+$d['itc']['sgst']+$d['itc']['igst'])) > 1) alert-warning @else alert-success @endif" style="max-width:1000px">
   <b>Check against GSTR-2B:</b> credit available in the uploaded GSTR-2B for {{ $label }} is {{ $m($d['two_b']['igst']+$d['two_b']['cgst']+$d['two_b']['sgst']) }} (IGST {{ $m($d['two_b']['igst']) }}, CGST {{ $m($d['two_b']['cgst']) }}, SGST {{ $m($d['two_b']['sgst']) }}), against {{ $m($d['itc']['igst']+$d['itc']['cgst']+$d['itc']['sgst']) }} in the bills you booked.
   Claim only what is in 2B; see <a href="{{ url('merchant/gstr2b') }}">GSTR-2B</a> for the differences.</div>
@else
<div class="alert alert-secondary" style="max-width:1000px">No GSTR-2B has been uploaded for invoices dated in {{ $label }}, so the credit above is not checked against 2B. <a href="{{ url('merchant/gstr2b') }}">Upload GSTR-2B</a>.</div>
@endif

@else
@if($d['missing_hsn'])<div class="alert alert-warning" style="max-width:1000px">{{ $d['missing_hsn'] }} invoice line(s) have no HSN/SAC code. Open the invoice and fill the code on each line (needed for table 12 of GSTR-1 once turnover is above the limit that applies to you).</div>@endif
@foreach($d['bad_gstin'] as $msg)<div class="alert alert-warning" style="max-width:1000px">{{ $msg }}. It is shown under B2C until the customer's GSTIN is corrected.</div>@endforeach

@if($tab==='gstr1')
<h5 style="margin-top:14px">B2B invoices (to registered customers) <a class="btn btn-sm btn-outline-primary" style="margin-left:8px" href="{{ url('merchant/gst-reports/b2b.csv?month='.$ym) }}">Download CSV</a></h5>
<div class="fna-tablebox gst" style="max-width:1100px"><table class="table"><thead><tr><th>GSTIN</th><th>Customer</th><th>Invoice</th><th>Date</th><th class="n">Rate</th><th class="n">Taxable</th><th class="n">IGST</th><th class="n">CGST</th><th class="n">SGST</th></tr></thead><tbody>
   @forelse($d['b2b'] as $r)@foreach($r['rates'] as $x)<tr><td>{{ $r['gstin'] }}</td><td>{{ $r['party'] }}</td><td><a href="{{ url('merchant/invoices/'.$r['doc']->id) }}">{{ $r['doc']->number }}</a></td><td>{{ date('d/m/Y', strtotime($r['doc']->doc_date)) }}</td><td class="n">{{ $x['rate'] }}%</td><td class="n">{{ $m($x['taxable']) }}</td><td class="n">{{ $m($x['igst']) }}</td><td class="n">{{ $m($x['cgst']) }}</td><td class="n">{{ $m($x['sgst']) }}</td></tr>@endforeach
   @empty<tr><td colspan="9" class="text-muted">No invoices to registered customers this month.</td></tr>@endforelse
</tbody></table></div>
<h5 style="margin-top:18px">B2C invoices (to unregistered customers) <a class="btn btn-sm btn-outline-primary" style="margin-left:8px" href="{{ url('merchant/gst-reports/b2c.csv?month='.$ym) }}">Download CSV</a></h5>
<div class="fna-tablebox gst" style="max-width:1100px"><table class="table"><thead><tr><th>Type</th><th>Place of supply</th><th>Customer</th><th>Invoice</th><th>Date</th><th class="n">Rate</th><th class="n">Taxable</th><th class="n">IGST</th><th class="n">CGST</th><th class="n">SGST</th></tr></thead><tbody>
   @forelse($d['b2c'] as $r)@foreach($r['rates'] as $x)<tr><td>{{ $r['large'] ? 'Large' : 'Small' }}</td><td>{{ $r['pos'] }}</td><td>{{ $r['party'] }}</td><td><a href="{{ url('merchant/invoices/'.$r['doc']->id) }}">{{ $r['doc']->number }}</a></td><td>{{ date('d/m/Y', strtotime($r['doc']->doc_date)) }}</td><td class="n">{{ $x['rate'] }}%</td><td class="n">{{ $m($x['taxable']) }}</td><td class="n">{{ $m($x['igst']) }}</td><td class="n">{{ $m($x['cgst']) }}</td><td class="n">{{ $m($x['sgst']) }}</td></tr>@endforeach
   @empty<tr><td colspan="10" class="text-muted">No invoices to unregistered customers this month.</td></tr>@endforelse
</tbody></table></div>
<p class="text-muted" style="max-width:900px;margin-top:10px">Document summary (table 13): {{ $d['doc_count'] }} invoice(s) issued @if($d['doc_count']) from {{ $d['doc_from'] }} to {{ $d['doc_to'] }}@endif, {{ $d['cancelled'] }} cancelled. Large means an inter-state invoice above Rs 2,50,000.</p>
@else
<h5 style="margin-top:14px">HSN / SAC summary of outward supplies <a class="btn btn-sm btn-outline-primary" style="margin-left:8px" href="{{ url('merchant/gst-reports/hsn.csv?month='.$ym) }}">Download CSV</a></h5>
<div class="fna-tablebox gst" style="max-width:1000px"><table class="table"><thead><tr><th>HSN / SAC</th><th>Description</th><th class="n">Rate</th><th class="n">Qty</th><th class="n">Taxable</th><th class="n">IGST</th><th class="n">CGST</th><th class="n">SGST</th></tr></thead><tbody>
   @forelse($d['hsn'] as $h)<tr><td>{{ $h['hsn'] ?: '(blank)' }}</td><td>{{ $h['desc'] }}</td><td class="n">{{ $h['rate'] }}%</td><td class="n">{{ $h['qty'] + 0 }}</td><td class="n">{{ $m($h['taxable']) }}</td><td class="n">{{ $m($h['igst']) }}</td><td class="n">{{ $m($h['cgst']) }}</td><td class="n">{{ $m($h['sgst']) }}</td></tr>
   @empty<tr><td colspan="8" class="text-muted">No sales this month.</td></tr>@endforelse
</tbody></table></div>
@endif
@endif
@endsection
