<!DOCTYPE html>
<html lang="en">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
   <title>Account - @yield('title', 'Sign in')</title>
   <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <link rel="icon" type="image/png" sizes="32x32" href="{{ URL::asset('public/assets/img/favicon/favicon-32x32.png')}}">
   <link type="text/css" href="{{ URL::asset('public/css/volt.css')}}" rel="stylesheet">
   <link rel="preconnect" href="https://fonts.googleapis.com">
   <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
   <link type="text/css" href="{{ URL::asset('public/css/fna-theme.css')}}" rel="stylesheet">
</head>
<body style="background:var(--fna-tint)">
   <main style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px">
      <div style="width:100%;max-width:@yield('width','360px');background:#fff;border:1px solid var(--fna-line);border-radius:12px;overflow:hidden">
         <div style="background:var(--fna-dark);color:#fff;padding:18px 22px">
            <div style="font-size:20px;font-weight:700;letter-spacing:1px">FNA</div>
            <div style="font-size:12px;opacity:.85;margin-top:2px">Books you can trust, year after year</div>
         </div>
         <div style="padding:22px">
            @yield('card')
            @if ($errors->any())
               <div class="alert alert-danger" style="margin:12px 0 0">
                  @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
               </div>
            @endif
         </div>
      </div>
   </main>
   @stack('scripts')
</body>
</html>
