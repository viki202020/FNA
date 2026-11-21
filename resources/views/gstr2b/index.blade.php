@extends('layout.master')
@section('title', 'GSTR-2B')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $chip = ['matched'=>'posted','mismatch'=>'draft','missing'=>'waiting','drafted'=>'chosen']; $txt = ['matched'=>'Matched','mismatch'=>'Mismatch','missing'=>'Not in FNA','drafted'=>'Draft created']; @endphp
<h1 class="mb-1">GSTR-2B reconciliation</h1>
<p class="text-muted mb-2" style="max-width:900px">Download your GSTR-2B from the GST portal (the JSON file, or the Excel with a B2B sheet) and upload it. FNA matches every supplier invoice to the purchase bills you booked, by supplier GSTIN and invoice number, then by amount. A bill is matched to a 2B line only if you entered the supplier's own invoice number on it.</p>
@if(session('success_msg'))<div class="alert alert-success" style="max-width:1000px">{{ session('success_msg') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="max-width:1000px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="post" action="{{ url('merchant/gstr2b/upload') }}" enctype="multipart/form-data" class="d-flex flex-wrap gap-2 align-items-end" style="max-width:1000px">@csrf
   <div><label>GSTR-2B FILE (JSON OR EXCEL)</label><input type="file" name="gstr_file" class="form-control" required></div>
   <div><label>RETURN PERIOD (OPTIONAL, MMYYYY)</label><input name="period" class="form-control" placeholder="042024" style="max-width:150px"></div>
   <button class="btn btn-primary">Upload and match</button>
</form>
@if($batch)
<div class="fna-tiles mt-3" style="max-width:1000px">
   <div class="fna-tile"><div class="k">Matched</div><div class="v">{{ $counts['matched'] }}</div><div class="s">credit claimable: {{ number_format($itc['claimable'],2) }}</div></div>
   <div class="fna-tile dark"><div class="k">Mismatch</div><div class="v">{{ $counts['mismatch'] }}</div></div>
   <div class="fna-tile deep"><div class="k">Not in FNA</div><div class="v">{{ $counts['missing'] }}</div></div>
   <div class="fna-tile grey"><div class="k">Not in 2B</div><div class="v">{{ count($notIn) }}</div><div class="s">supplier has not filed</div></div>
</div>
<div class="d-flex justify-content-between align-items-center mb-1" style="max-width:1100px"><div class="fna-section-title" style="margin:8px 0 2px">Supplier invoices in the 2B file</div>
   @if($counts['missing'])<form method="post" action="{{ url('merchant/gstr2b/draft-all') }}" style="margin:0">@csrf<input type="hidden" name="batch" value="{{ $batch }}"><button class="btn btn-outline-primary btn-sm">Create draft bills for all {{ $counts['missing'] }} missing</button></form>@endif</div>
<div class="fna-tablebox" style="max-width:1100px"><table class="table">
   <thead><tr><th>Supplier</th><th>Invoice</th><th>Date</th><th style="text-align:right">Taxable</th><th style="text-align:right">Tax</th><th>Credit</th><th>Status</th><th></th></tr></thead><tbody>
   @foreach($lines as $l)
      <tr><td>{{ $l->supplier_name }}<div class="text-muted" style="font-size:12px">{{ $l->supplier_gstin }}</div></td><td class="fw-bold">{{ $l->inv_no }}</td><td>{{ $l->inv_date ? date('d/m/Y',strtotime($l->inv_date)) : '' }}</td>
         <td style="text-align:right">{{ number_format($l->taxable,2) }}</td><td style="text-align:right">{{ number_format($l->igst+$l->cgst+$l->sgst,2) }}</td>
         <td>{{ strtoupper((string)$l->itc_available)==='N' ? 'Not available' : (strtoupper((string)$l->itc_available)==='Y' ? 'Available' : '') }}</td>
         <td><span class="fna-chip {{ $chip[$l->status] ?? 'draft' }}">{{ $txt[$l->status] ?? $l->status }}</span>@if($l->note)<div class="text-muted" style="font-size:12px">{{ $l->note }}</div>@endif</td>
         <td>@if($l->doc_id)<a class="fw-bold" href="{{ url('merchant/bills/'.$l->doc_id) }}" style="text-decoration:none">Open bill &rsaquo;</a>@elseif($l->status==='missing')<form method="post" action="{{ url('merchant/gstr2b/'.$l->id.'/draft') }}" style="margin:0">@csrf<button class="btn btn-sm btn-outline-primary">Create draft bill</button></form>@endif</td></tr>
   @endforeach
   </tbody></table></div>
@if(count($notIn))
<div class="fna-section-title">In FNA but not in this 2B (the supplier has not filed it, or it is for another period)</div>
<div class="fna-tablebox" style="max-width:1100px"><table class="table"><thead><tr><th>Supplier</th><th>Bill</th><th>Their invoice no.</th><th>Date</th><th style="text-align:right">Tax</th></tr></thead><tbody>
   @foreach($notIn as $d)<tr><td>{{ $d->party_name }}<div class="text-muted" style="font-size:12px">{{ $d->p_gst }}</div></td><td><a class="fw-bold" href="{{ url('merchant/bills/'.$d->id) }}" style="text-decoration:none">{{ $d->number }}</a></td><td>{{ $d->reference }}</td><td>{{ date('d/m/Y',strtotime($d->doc_date)) }}</td><td style="text-align:right">{{ number_format($d->cgst+$d->sgst+$d->igst,2) }}</td></tr>@endforeach
</tbody></table></div>
<div class="fna-foot-note">Do not claim input credit on these until they appear in a later 2B.</div>
@endif
@endif
<div class="fna-foot-note">Nothing is posted by this page. Draft bills created here need an expense account chosen before they can be posted. Credit notes in the file are not read yet.</div>
@endsection
