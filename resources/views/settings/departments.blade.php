@extends('layout.master')
@section('title', 'Departments')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <h1 class="mb-1">Departments</h1>
   <p class="text-muted mb-2">The departments a voucher line can be tagged with. Account heads can require one (Account heads &rsaquo; Department).</p>
   @if(session('success_msg'))<div class="alert alert-success" style="max-width:700px">{{ session('success_msg') }}</div>@endif
   @if($errors->any())<div class="alert alert-danger" style="max-width:700px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
   <div class="fna-tablebox" style="max-width:700px"><table class="table">
      <thead><tr><th>Name</th><th>Status</th><th></th></tr></thead><tbody>
      @forelse($rows as $r)
         <tr><td class="fw-bold">{{ $r->name }}</td><td><span class="fna-chip {{ $r->active ? 'active' : 'off' }}">{{ $r->active ? 'Active' : 'Switched off' }}</span></td>
            <td><form method="post" action="{{ url('merchant/departments/'.$r->id.'/toggle') }}" style="margin:0">@csrf<button class="btn btn-sm btn-outline-primary">{{ $r->active ? 'Switch off' : 'Switch on' }}</button></form></td></tr>
      @empty
         <tr><td colspan="3" class="text-muted">No departments yet.</td></tr>
      @endforelse
      </tbody></table></div>
   <div class="fna-section-title">New department</div>
   <form method="post" action="{{ url('merchant/departments') }}" class="d-flex gap-2 align-items-end" style="max-width:700px">@csrf
      <div style="flex:1"><label>NAME</label><input class="form-control" name="name" required maxlength="100" value="{{ old('name') }}"></div>
      <button class="btn btn-primary">Add</button>
   </form>
   <div class="fna-foot-note">A department is never deleted. Switching one off hides it from new entries and keeps it on everything already tagged.</div>
</div>
@endsection
