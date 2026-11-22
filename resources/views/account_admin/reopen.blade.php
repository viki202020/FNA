@extends('layout.master')
@section('title', 'Re-open a year')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<h1 class="mb-1">Re-open an earlier year</h1>
<p class="text-muted mb-2" style="max-width:800px">Only the year just before the current year ({{ $current }}) can be re-opened, and at most two consecutive years are ever open together. A reason is recorded with your name and the time. After your changes, close the year again here and check the next year's opening balances.</p>
@if(session('success_msg'))<div class="alert alert-success" style="max-width:800px">{{ session('success_msg') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="max-width:800px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
@if(!$prev)
   <div class="alert alert-secondary" style="max-width:800px">There is no earlier year with entries to re-open.</div>
@elseif(in_array($prev, $open))
   <div class="alert alert-warning" style="max-width:800px"><b>{{ $prev }} is open for changes.</b></div>
   @if(count($blockers))<div class="alert alert-danger" style="max-width:800px">It cannot be closed again yet:@foreach($blockers as $b)<div>- {{ $b['message'] }}</div>@endforeach</div>@endif
   <form method="post" action="{{ url('merchant/reopen-year/close-again') }}" onsubmit="return confirm('Close {{ $prev }} again?')">@csrf<button class="btn btn-primary" @if(count($blockers)) disabled @endif>Close {{ $prev }} again</button></form>
@else
   <form method="post" action="{{ url('merchant/reopen-year') }}" style="max-width:800px">@csrf
      <label>REASON FOR RE-OPENING {{ $prev }} (REQUIRED)</label>
      <textarea class="form-control mb-2" name="reason" rows="3" required minlength="10" maxlength="500">{{ old('reason') }}</textarea>
      <button class="btn btn-primary" onclick="return confirm('Re-open {{ $prev }}?')">Re-open {{ $prev }}</button>
   </form>
@endif
<div class="fna-section-title">History</div>
<div class="fna-tablebox" style="max-width:900px"><table class="table">
   <thead><tr><th>Year</th><th>Re-opened</th><th>By</th><th>Reason</th><th>Closed again</th></tr></thead><tbody>
   @forelse($log as $l)<tr><td>{{ $l->financial_year }}</td><td>{{ $l->reopened_at }}</td><td>{{ $l->who }}</td><td>{{ $l->reason }}</td><td>{{ $l->closed_again_at ?: 'Still open' }}</td></tr>
   @empty<tr><td colspan="5" class="text-muted">Nothing has been re-opened.</td></tr>@endforelse
   </tbody></table></div>
@endsection
