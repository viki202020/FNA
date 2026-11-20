@extends('layout.master')
@section('title', 'Posting accounts')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <h1 class="mb-1">Posting accounts</h1>
   <p class="text-muted mb-2" style="max-width:900px">The accounts that invoices, bills, payments and tax post to. A change applies to what you post from now on. Nothing already posted is changed.</p>
   @if(session('success_msg'))<div class="alert alert-success" style="max-width:900px">{{ session('success_msg') }}</div>@endif
   @if($errors->any())<div class="alert alert-danger" style="max-width:900px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
   <form method="post" action="{{ url('merchant/posting-accounts') }}" style="max-width:900px">@csrf
      <div class="fna-tablebox"><table class="table">
         <thead><tr><th>Used for</th><th>Account</th><th>What it does</th></tr></thead>
         <tbody>
         @foreach($labels as $k=>$l)
            <tr><td class="fw-bold" style="white-space:nowrap">{{ $l[0] }}</td>
               <td><select class="form-select" name="{{ $k }}">@foreach($heads as $h)<option value="{{ $h->id }}" @if($values[$k]==$h->id) selected @endif>{{ $h->name }}</option>@endforeach</select></td>
               <td class="text-muted" style="font-size:12px">{{ $l[1] }}</td></tr>
         @endforeach
         </tbody>
      </table></div>
      <div class="mt-3"><button class="btn btn-primary">Save</button></div>
   </form>
   @if(count($log))
      <div class="fna-section-title">Recent changes</div>
      <div class="fna-tablebox" style="max-width:900px"><table class="table"><thead><tr><th>When</th><th>Setting</th><th>From</th><th>To</th><th>By</th></tr></thead><tbody>
      @foreach($log as $g)<tr><td>{{ date('d/m/Y H:i', strtotime($g->created_at)) }}</td><td>{{ $labels[$g->skey][0] ?? $g->skey }}</td><td>{{ $names[$g->old_head] ?? '' }}</td><td>{{ $names[$g->new_head] ?? '' }}</td><td>{{ $users[$g->changed_by] ?? '' }}</td></tr>@endforeach
      </tbody></table></div>
   @endif
   <div class="fna-foot-note">Posting to the wrong account is a classic accounting error, so every change here is recorded with who made it and when.</div>
</div>
@endsection
