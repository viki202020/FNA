@extends('login.auth')
@section('card')
   <form action="{{URL('login')}}" method="post">
      @csrf
      <h1 style="margin:0 0 4px;font-size:18px">Sign in</h1>
      <p style="margin:0 0 16px;color:var(--fna-muted)">Step 1 of 2: your email and password.</p>
      <label for="email">EMAIL</label>
      <input type="email" class="form-control" style="margin:4px 0 12px;font-size:14px" placeholder="you@company.com" id="email" autofocus required name="username">
      <label for="password">PASSWORD</label>
      <input type="password" class="form-control" style="margin:4px 0 16px;font-size:14px" placeholder="Your password" id="password" required name="password">
      <button type="submit" class="btn btn-success" style="width:100%;padding:9px;font-size:14px" name="submit">Continue</button>
   </form>
@endsection
