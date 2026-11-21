@extends('layout.master')
@section('title', 'Opening balances: preview')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<h1 class="mb-1">Opening balances: preview</h1>
<p class="text-muted mb-2">As at {{ date('d/m/Y', strtotime($as_at)) }}, financial year {{ $fyear }}.</p>
@if(count($issues))
   <div class="alert alert-danger" style="max-width:1000px"><strong>This cannot be posted yet:</strong>@foreach($issues as $i)<div>&bull; {{ $i }}</div>@endforeach</div>
@else
   <div class="alert alert-success" style="max-width:1000px">Every row matched an account and debits equal credits. Nothing is posted until you confirm.</div>
@endif
<div class="fna-tablebox" style="max-width:1000px"><table class="table">
   <thead><tr><th>Row</th><th>Name in your file</th><th>Account</th><th>Party</th><th style="text-align:right">Debit</th><th style="text-align:right">Credit</th></tr></thead><tbody>
   @foreach($rows as $r)
      <tr><td>{{ $r['row'] }}</td><td>{{ $r['name'] }}</td>
         <td>@if($r['head_id']) <span class="fna-chip posted">Matched</span> @else <span class="fna-chip draft">Not found</span> @endif</td>
         <td>{{ $r['party'] }}@if($r['party'] && !$r['party_id']) <span class="text-muted" style="font-size:12px">(not on your list)</span>@endif</td>
         <td style="text-align:right">{{ $r['debit']>0 ? number_format($r['debit'],2) : '' }}</td><td style="text-align:right">{{ $r['credit']>0 ? number_format($r['credit'],2) : '' }}</td></tr>
   @endforeach
   <tr style="font-weight:700;border-top:2px solid var(--fna-green)"><td colspan="4" style="text-align:right">Total</td><td style="text-align:right">{{ number_format($dr,2) }}</td><td style="text-align:right">{{ number_format($cr,2) }}</td></tr>
   </tbody></table></div>
<div class="mt-3 d-flex gap-2">
   @if(!count($issues))<form method="post" action="{{ url('merchant/opening-balance/confirm') }}">@csrf<button class="btn btn-primary">Post opening balances</button></form>@endif
   <a class="btn btn-outline-primary" href="{{ url('merchant/opening-balance') }}">&lsaquo; Back</a>
</div>
@endsection
