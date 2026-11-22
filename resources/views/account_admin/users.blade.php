@extends('layout.master')
@section('title', 'Users')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<h1 class="mb-1">Users</h1>
<p class="text-muted mb-2" style="max-width:800px">An <b>admin</b> can close books, re-open the earlier year and change company setup. An <b>accountant</b> does the daily work (uploads, categorising, invoices, bills, matching, reports) but cannot do those three things. Each person signs in with their own email, password and authenticator.</p>
@if(session('success_msg'))<div class="alert alert-success" style="max-width:800px">{{ session('success_msg') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="max-width:800px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<div class="fna-tablebox" style="max-width:900px"><table class="table">
   <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead><tbody>
   @foreach($rows as $r)
      <tr><td class="fw-bold">{{ $r->name }}</td><td>{{ $r->email }}</td>
         <td><form method="post" action="{{ url('merchant/users/'.$r->id.'/role') }}" style="margin:0;display:flex;gap:6px">@csrf<select name="role" class="form-select" style="width:auto" onchange="this.form.submit()"><option value="admin" @if(($r->role ?: 'admin')==='admin') selected @endif>Admin</option><option value="accountant" @if($r->role==='accountant') selected @endif>Accountant</option></select></form></td>
         <td><span class="fna-chip {{ $r->status ? 'active' : 'off' }}">{{ $r->status ? 'Active' : 'Switched off' }}</span></td>
         <td><form method="post" action="{{ url('merchant/users/'.$r->id.'/toggle') }}" style="margin:0">@csrf<button class="btn btn-sm btn-outline-primary">{{ $r->status ? 'Switch off' : 'Switch on' }}</button></form></td></tr>
   @endforeach
   </tbody></table></div>
<div class="fna-section-title">New login</div>
<form method="post" action="{{ url('merchant/users') }}" class="d-flex flex-wrap gap-2 align-items-end" style="max-width:900px">@csrf
   <div><label>NAME</label><input class="form-control" name="name" required maxlength="100" value="{{ old('name') }}"></div>
   <div><label>EMAIL</label><input class="form-control" type="email" name="email" required maxlength="200" value="{{ old('email') }}"></div>
   <div><label>ROLE</label><select name="role" class="form-select"><option value="accountant">Accountant</option><option value="admin">Admin</option></select></div>
   <div><label>FIRST PASSWORD (10+ CHARACTERS)</label><input class="form-control" type="password" name="password" required minlength="10" autocomplete="new-password"></div>
   <button class="btn btn-primary">Create</button>
</form>
<div class="fna-foot-note">Logins are never deleted. Switch one off to stop it signing in; everything it did stays in the history under its name.</div>
@endsection
