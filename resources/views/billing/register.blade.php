@extends('layout.master')
@section('title', 'Cheque register')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $chip = ['outstanding'=>'waiting','cleared'=>'paid','dishonoured'=>'void','sent'=>'draft','matched'=>'paid','withdrawn'=>'void']; $txt = ['outstanding'=>'Outstanding','cleared'=>'Cleared','dishonoured'=>'Dishonoured']; @endphp
<div style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <h1 class="mb-1">Cheque register</h1>
   <p class="text-muted mb-2">Built from the payments you record on bills and invoices with the mode Cheque. Nothing is typed here.</p>
   <div class="fna-radios mb-2">
      <label class="{{ $tab==='out' ? 'on' : '' }}"><a href="?tab=out" style="color:inherit;text-decoration:none">Issued (suppliers)</a></label>
      <label class="{{ $tab==='in' ? 'on' : '' }}"><a href="?tab=in" style="color:inherit;text-decoration:none">Received (customers)</a></label>
   </div>
   <div class="fna-tablebox"><table class="table">
      <thead><tr><th>Cheque no.</th><th>Date</th><th>Party</th><th>{{ $tab==='out' ? 'Bill' : 'Invoice' }}</th><th>Bank</th><th style="text-align:right">Amount</th><th>Status</th><th>Cleared on</th></tr></thead>
      <tbody>
      @forelse($rows as $r)
         <tr><td class="fw-bold">{{ $r->cheque_no }}</td><td>{{ date('d/m/Y', strtotime($r->pay_date)) }}</td><td>{{ $r->party_name }}</td>
            <td><a href="{{ url('merchant/'.($tab==='out'?'bills':'invoices').'/'.$r->doc_id) }}">{{ $r->doc_number }}</a></td>
            <td>{{ $banks[$r->bank_head_id] ?? '' }}</td><td style="text-align:right">{{ number_format($r->amount,2) }}</td>
            <td><span class="fna-chip {{ $chip[$r->status] ?? 'draft' }}">{{ $txt[$r->status] ?? ucfirst($r->status) }}</span></td>
            <td>{{ $r->cleared_on ? date('d/m/Y', strtotime($r->cleared_on)) : '' }}</td></tr>
      @empty
         <tr><td colspan="8" class="text-muted">No cheques yet.</td></tr>
      @endforelse
      </tbody>
   </table></div>
   <div class="fna-foot-note">A cheque is posted to the books on the date it is handed over. It shows Outstanding until the bank line arrives and you match it on <a href="{{ url('merchant/receipts-to-match') }}">Receipts to match</a>. See <a href="{{ url('merchant/bank-reconciliation') }}">Bank reconciliation</a>.</div>
</div>
@endsection
