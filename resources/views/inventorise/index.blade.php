@extends('layout.master')
@section('title', 'Inventorise')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@php $m = fn($v) => number_format($v, 2); $tabs = ['purchase' => 'Purchase orders', 'sales' => 'Sales orders', 'held' => 'To confirm' . ($heldCount ? ' (' . $heldCount . ')' : ''), 'log' => 'Log']; $typeName = ['purchase_order'=>'Purchase order','sales_order'=>'Sales order','purchase_bill'=>'Bill','sales_invoice'=>'Invoice']; @endphp
<style>.lk td.n,.lk th.n{text-align:right;font-variant-numeric:tabular-nums;white-space:nowrap}</style>
<h1 class="mb-1">Inventorise</h1>
<p class="text-muted" style="max-width:950px">Orders, bills and invoices sent from Inventorise. Orders change no balances. A bill or invoice arrives as a <b>draft</b> in Bills or Invoices for you to check and post. Nothing is sent back to Inventorise.</p>
<div class="fna-radios mb-2">@foreach($tabs as $k=>$tt)<label class="{{ $k===$tab ? 'on' : '' }}"><a href="?tab={{ $k }}" style="color:inherit;text-decoration:none">{{ $tt }}</a></label>@endforeach</div>
@if(session('success_msg'))<div class="alert alert-success" style="max-width:1100px">{{ session('success_msg') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="max-width:1100px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

@if($tab==='purchase' || $tab==='sales')
<div class="fna-tablebox lk" style="max-width:1100px"><table class="table"><thead><tr><th>Order</th><th>{{ $tab==='purchase' ? 'Supplier' : 'Customer' }}</th><th>Date</th><th>Status in Inventorise</th><th class="n">Total</th><th>{{ $tab==='purchase' ? 'Bill in FNA' : 'Invoice in FNA' }}</th></tr></thead><tbody>
   @forelse($orders as $o)<tr><td class="fw-bold">{{ $o->ref }}</td><td>{{ $o->party_name }}</td><td>{{ $o->order_date ? date('d/m/Y', strtotime($o->order_date)) : '' }}</td><td>{{ ucfirst(str_replace('_',' ',$o->status)) }}</td><td class="n">{{ $m($o->total) }}</td>
      <td>@foreach($docs[$o->id] ?? [] as $d)<a href="{{ url('merchant/'.($d->kind==='sales'?'invoices':'bills').'/'.$d->id) }}">{{ $d->number ?: 'Draft' }}</a> ({{ $d->status }}) @endforeach</td></tr>
   @empty<tr><td colspan="6" class="text-muted">Nothing received yet.</td></tr>@endforelse
</tbody></table></div>

@elseif($tab==='held')
<p class="text-muted" style="max-width:950px">These came from Inventorise but the supplier or customer is not in FNA. Match each to a party you already have, or create it from what Inventorise sent. Once confirmed, the same party is recognised automatically.</p>
@forelse($held as $r)
<div class="fna-tablebox" style="max-width:1000px;padding:12px;margin-bottom:10px">
   <div><b>{{ $typeName[$r->type] ?? $r->type }} {{ $r->ref }}</b> from <b>{{ $r->p['party']['name'] ?? '?' }}</b> @if(!empty($r->p['party']['gstin'])) (GSTIN {{ $r->p['party']['gstin'] }}) @endif</div>
   <div class="text-muted" style="font-size:13px">{{ $r->note }}</div>
   <div class="d-flex flex-wrap gap-2 align-items-end mt-2">
      <form method="post" action="{{ url('merchant/inventorise/confirm') }}" class="d-flex gap-2 align-items-end" style="margin:0">@csrf<input type="hidden" name="ext_key" value="{{ $r->party_key ?: 'name:'.mb_strtolower($r->p['party']['name'] ?? '') }}">
         <div><label>MATCH TO EXISTING</label><select name="customer_id" class="form-select" required style="min-width:260px"><option value="">Choose</option>@foreach($parties as $x)@if($x->type == (str_starts_with($r->type,'purchase') ? 4 : 1))<option value="{{ $x->id }}">{{ $x->display_name ?: trim($x->first_name.' '.$x->last_name) }} ({{ $x->type==4 ? 'supplier' : 'customer' }})</option>@endif @endforeach</select></div>
         <button class="btn btn-primary">Confirm</button></form>
      <form method="post" action="{{ url('merchant/inventorise/create-party') }}" style="margin:0">@csrf<input type="hidden" name="inbox_id" value="{{ $r->id }}"><button class="btn btn-outline-primary">Create as new</button></form>
   </div></div>
@empty<div class="alert alert-secondary" style="max-width:900px">Nothing to confirm.</div>@endforelse

@else
<div class="fna-tablebox lk" style="max-width:1000px"><table class="table"><thead><tr><th>Last received</th><th>Type</th><th>Reference</th><th>Times sent</th><th>Result</th></tr></thead><tbody>
   @forelse($log as $l)<tr><td>{{ $l->updated_at }}</td><td>{{ $typeName[$l->type] ?? $l->type }}</td><td>{{ $l->ref }}</td><td>{{ $l->hits }}</td><td>{{ ucfirst($l->status) }}@if($l->note) <div class="text-muted" style="font-size:12px">{{ $l->note }}</div>@endif</td></tr>@empty<tr><td colspan="5" class="text-muted">Nothing received yet.</td></tr>@endforelse
</tbody></table></div>
@endif
@endsection
