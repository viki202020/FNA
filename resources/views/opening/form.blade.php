@extends('layout.master')
@section('title', 'Opening balances')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $cols = range(1, 20); @endphp
<h1 class="mb-1">Opening balances</h1>
<p class="text-muted mb-2" style="max-width:900px">Upload the trial balance or balance sheet you start from. Say which row is the header and which is the last, and which column holds what. You see a preview first; nothing is posted until every row matches an account and debits equal credits. A year that already has opening entries is never overwritten.</p>
@if($errors->any())<div class="alert alert-danger" style="max-width:900px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="post" action="{{ url('merchant/opening-balance/preview') }}" enctype="multipart/form-data" style="max-width:900px">@csrf
   <div class="row">
      <div class="col-md-3 mb-3"><label>WHICH ROW IS THE HEADER?</label><input type="number" min="0" class="form-control" name="header_row" required value="{{ old('header_row', 1) }}"></div>
      <div class="col-md-3 mb-3"><label>LAST ROW WITH DATA</label><input type="number" min="1" class="form-control" name="last_row" required value="{{ old('last_row') }}"></div>
      <div class="col-md-3 mb-3"><label>BALANCES AS AT</label><input type="date" class="form-control" name="as_at" required value="{{ old('as_at') }}"></div>
      <div class="col-md-3 mb-3"><label>HOW ARE AMOUNTS SHOWN?</label>
         <select class="form-select" name="layout" id="layout"><option value="two">Debit and Credit columns</option><option value="one">One amount column with Dr / Cr</option></select></div>
   </div>
   <div class="fna-section-title">Which column is which (column numbers in your file)</div>
   <div class="row">
      <div class="col-md-3 mb-3"><label>ACCOUNT NAME *</label><select class="form-select" name="name_col" required><option value="">Select</option>@foreach($cols as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
      <div class="col-md-3 mb-3 two"><label>DEBIT *</label><select class="form-select" name="debit_col"><option value="">Select</option>@foreach($cols as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
      <div class="col-md-3 mb-3 two"><label>CREDIT *</label><select class="form-select" name="credit_col"><option value="">Select</option>@foreach($cols as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
      <div class="col-md-3 mb-3 one" style="display:none"><label>AMOUNT *</label><select class="form-select" name="amount_col"><option value="">Select</option>@foreach($cols as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
      <div class="col-md-3 mb-3 one" style="display:none"><label>DR / CR *</label><select class="form-select" name="side_col"><option value="">Select</option>@foreach($cols as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
      <div class="col-md-3 mb-3"><label>CUSTOMER / SUPPLIER (OPTIONAL)</label><select class="form-select" name="party_col"><option value="">None</option>@foreach($cols as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
      <div class="col-md-3 mb-3"><label>SCHEDULE OR NOTE (OPTIONAL)</label><select class="form-select" name="note_col"><option value="">None</option>@foreach($cols as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
   </div>
   <div class="mb-3" style="max-width:420px"><label>UPLOAD FILE (EXCEL OR CSV)</label><input type="file" class="form-control" name="csv_file" required></div>
   <div class="d-flex gap-2 align-items-center"><button class="btn btn-primary">Preview</button><a href="{{ url('merchant/opening-balance/template') }}" class="fw-bold" style="text-decoration:none">Download a blank template</a></div>
</form>
<div class="fna-section-title">The fields FNA reads</div>
<div class="fna-tablebox" style="max-width:900px"><table class="table"><thead><tr><th>Field</th><th>Needed?</th><th>What it is used for</th></tr></thead><tbody>
   <tr><td class="fw-bold">Account name</td><td>Yes</td><td>Matched to an account head by name (ignoring capitals and extra spaces). An unmatched name stops the upload and is listed.</td></tr>
   <tr><td class="fw-bold">Debit and Credit<br><span class="text-muted" style="font-weight:400">or Amount and Dr/Cr</span></td><td>Yes</td><td>A row has either a debit or a credit. Total debits must equal total credits.</td></tr>
   <tr><td class="fw-bold">Customer / supplier</td><td>No</td><td>For debtor and creditor schedules: the opening line is tagged with the party when the name matches one on your list.</td></tr>
   <tr><td class="fw-bold">Schedule or note</td><td>No</td><td>Kept in the line's narration, so the schedule it came from stays visible.</td></tr>
   <tr><td class="fw-bold">Balances as at</td><td>Yes</td><td>Decides the financial year (31 March cut-off). That year must be open and must not already have opening entries.</td></tr>
</tbody></table></div>
<div class="fna-foot-note">All opening entries of a year are posted as one voucher (OBV). Nothing is ever deleted: a wrong opening balance is corrected with a journal voucher.</div>
@endsection
@push('script-js')
<script>
(function(){var l=document.getElementById('layout');function f(){var one=l.value==='one';document.querySelectorAll('.one').forEach(function(e){e.style.display=one?'':'none';});document.querySelectorAll('.two').forEach(function(e){e.style.display=one?'none':'';});}l.onchange=f;f();})();
</script>
@endpush
