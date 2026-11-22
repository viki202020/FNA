@extends('layout.master')
@section('title', 'Change password')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<h1 class="mb-1">Change password</h1>
<p class="text-muted mb-2">Use at least 10 characters. You will stay signed in.</p>
@if(session('success_msg'))<div class="alert alert-success" style="max-width:500px">{{ session('success_msg') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="max-width:500px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="post" action="{{ url('merchant/change-password') }}" style="max-width:500px">@csrf
   <label>OLD PASSWORD</label><input class="form-control mb-2" type="password" name="old_password" required autocomplete="current-password">
   <label>NEW PASSWORD</label><input class="form-control mb-2" type="password" name="new_password" required minlength="10" autocomplete="new-password">
   <label>NEW PASSWORD AGAIN</label><input class="form-control mb-2" type="password" name="new_password_confirmation" required minlength="10" autocomplete="new-password">
   <button class="btn btn-primary">Change password</button>
</form>
@endsection
