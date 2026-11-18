@extends('login.auth')
@section('card')
   <form action="{{URL('login/code')}}" method="post" autocomplete="off">
      @csrf
      <h1 style="margin:0 0 4px;font-size:18px">Enter your code</h1>
      <p style="margin:0 0 16px;color:var(--fna-muted)">Step 2 of 2: open your authenticator app and type the 6-digit code for FNA.</p>
      <label for="code">6-DIGIT CODE</label>
      <input type="text" class="form-control" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="20" autofocus required placeholder="123456"
             style="margin:4px 0 6px;font-size:22px;letter-spacing:6px;text-align:center;font-variant-numeric:tabular-nums">
      <div style="font-size:12px;color:var(--fna-muted);margin-bottom:14px">Lost your phone? Type one of your recovery codes here instead.</div>
      <button type="submit" class="btn btn-success" style="width:100%;padding:9px;font-size:14px">Sign in</button>
      <div style="text-align:center;margin-top:12px"><a href="{{ URL('logout') }}" style="font-size:12px">Start again</a></div>
   </form>
@endsection
