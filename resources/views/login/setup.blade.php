@extends('login.auth')
@section('title', 'Set up authenticator')
@section('width', '460px')
@section('card')
   <h1 style="margin:0 0 4px;font-size:18px">Set up your authenticator</h1>
   <p style="margin:0 0 12px;color:var(--fna-muted)">You only do this once. It protects your books even if your password leaks.</p>
   <ol style="margin:0 0 12px;padding-left:18px;line-height:1.7">
      <li>Install <strong>Google Authenticator</strong>, <strong>Microsoft Authenticator</strong> or <strong>Authy</strong> on your phone.</li>
      <li>In the app, add an account and scan this picture.</li>
      <li>Type the 6-digit code the app shows.</li>
   </ol>
   <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-bottom:12px">
      <div id="qr" style="padding:8px;border:1px solid var(--fna-line);border-radius:8px;background:#fff;min-width:150px;min-height:150px"></div>
      <div style="flex:1 1 180px">
         <div style="font-size:11px;font-weight:700;color:#3E5A48">CAN'T SCAN? TYPE THIS KEY IN THE APP</div>
         <div style="font-family:monospace;font-size:14px;word-break:break-all;margin-top:4px;background:var(--fna-tint);padding:8px;border-radius:6px">{{ trim(chunk_split($secret, 4, ' ')) }}</div>
         <div style="font-size:11px;color:var(--fna-muted);margin-top:4px">Time-based, 6 digits. Keep this key private.</div>
         <div style="font-size:12px;margin-top:6px">On the phone with the app? <a href="{{ $uri }}" style="font-weight:700">Tap here to add it</a></div>
      </div>
   </div>
   <form action="{{URL('login/setup')}}" method="post" autocomplete="off">
      @csrf
      <label for="code">CODE FROM THE APP</label>
      <input type="text" class="form-control" id="code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="8" required placeholder="123456"
             style="margin:4px 0 14px;font-size:22px;letter-spacing:6px;text-align:center">
      <button type="submit" class="btn btn-success" style="width:100%;padding:9px;font-size:14px">Confirm and continue</button>
   </form>
@endsection
@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
   try { new QRCode(document.getElementById('qr'), { text: @json($uri), width: 150, height: 150, correctLevel: QRCode.CorrectLevel.M }); }
   catch (e) { document.getElementById('qr').innerHTML = '<small>The picture could not load. Use the key on the right.</small>'; }
</script>
@endpush
