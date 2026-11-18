@extends('layout.master')
@section('title', 'Bank reconciliation')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <form method="get" class="d-flex flex-wrap justify-content-between align-items-center mb-2">
      <h1 style="margin:0">Bank reconciliation</h1>
      <label style="display:inline-flex;align-items:center;gap:6px;margin:0">As at <input type="date" name="asat" class="form-control" value="{{ $asat }}" onchange="this.form.submit()" style="width:auto"></label>
   </form>
   @foreach($banks as $b)
   <div class="fna-section-title">{{ $b['bank'] }}</div>
   <div class="fna-tablebox" style="max-width:820px"><table class="table">
      <thead><tr><th>Item</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
         <tr><td class="fw-bold">Balance as per the bank statement</td><td style="text-align:right" class="fw-bold">{{ number_format($b['statement'],2) }}</td></tr>
         @foreach($b['issued'] as $c)<tr><td style="padding-left:26px">Less: cheque {{ $c->cheque_no }} to {{ $c->party_name }}, {{ date('d/m/Y', strtotime($c->pay_date)) }}, not yet presented</td><td style="text-align:right">({{ number_format($c->amount,2) }})</td></tr>@endforeach
         @foreach($b['received'] as $c)<tr><td style="padding-left:26px">Add: cheque {{ $c->cheque_no }} from {{ $c->party_name }}, {{ date('d/m/Y', strtotime($c->pay_date)) }}, not yet cleared</td><td style="text-align:right">{{ number_format($c->amount,2) }}</td></tr>@endforeach
         <tr style="border-top:2px solid var(--fna-green)"><td class="fw-bold">Balance as per the books</td><td style="text-align:right" class="fw-bold">{{ number_format($b['statement'] - $b['issuedTotal'] + $b['receivedTotal'],2) }}</td></tr>
      </tbody>
   </table></div>
   @endforeach
   <div class="fna-foot-note">Cheques are posted on the date handed over, so the books are right at year-end even when the bank clears the cheque later. Items drop off this list when the bank line is matched on Receipts to match.</div>
</div>
@endsection
