@extends('login.auth')
@section('title', 'Recovery codes')
@section('width', '420px')
@section('card')
   <h1 style="margin:0 0 4px;font-size:18px">Your authenticator is ready</h1>
   <p style="margin:0 0 10px;color:var(--fna-muted)">Save these recovery codes somewhere safe. Each one works <strong>once</strong>, if you lose your phone. They are shown only now.</p>
   <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;font-family:monospace;font-size:15px;background:var(--fna-tint);padding:12px;border-radius:8px;margin-bottom:14px">
      @foreach($codes as $c)<div>{{ $c }}</div>@endforeach
   </div>
   <form action="{{URL('login/finish')}}" method="post">
      @csrf
      <label style="display:flex;gap:8px;align-items:center;font-weight:400;font-size:13px;margin-bottom:12px"><input type="checkbox" required> I have saved these codes</label>
      <button type="submit" class="btn btn-success" style="width:100%;padding:9px;font-size:14px">Continue to FNA</button>
   </form>
@endsection
