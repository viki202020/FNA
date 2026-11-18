<!DOCTYPE html>
<html lang="en">
   <head> 
      <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
      <title>Account | @yield('title')</title>
      @stack('script-head') 
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <meta name="title" content="">
      <meta name="author" content="">
      <meta name="description" content="">
      <meta name="keywords" content="" />
      <link rel="canonical" href="">
      <meta property="og:type" content="website">
      <meta property="og:url" content="">
      <meta property="og:title" content="">
      <meta property="og:description" content="">
      <meta property="og:image" content="">
      <!-- Twitter -->
      <meta property="twitter:card" content="summary_large_image">
      <meta property="twitter:url" content="">
      <meta property="twitter:title" content="">
      <meta property="twitter:description" content="">
      <meta property="twitter:image" content="">
      <!-- Favicon -->
      <link rel="apple-touch-icon" sizes="120x120" href="assets/img/favicon/apple-touch-icon.png">
      <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon/favicon-32x32.png">
      <link rel="icon" type="image/png" sizes="16x16" href="assets/img/favicon/favicon-16x16.png">
      
      <link rel="mask-icon" href="assets/img/favicon/safari-pinned-tab.svg" color="#ffffff">
      <meta name="msapplication-TileColor" content="#ffffff">
      <meta name="theme-color" content="#ffffff">
      <!-- Sweet Alert -->
      <link type="text/css" href="{{ URL::asset('public/vendor/sweetalert2/dist/sweetalert2.min.css')}}" rel="stylesheet">
      <!-- Notyf -->
      <link type="text/css" href="{{ URL::asset('public/vendor/notyf/notyf.min.css')}}" rel="stylesheet">
      <!-- Volt CSS -->
      <link type="text/css" href="{{ URL::asset('public/css/volt.css')}}" rel="stylesheet">
      <link rel="preconnect" href="https://fonts.googleapis.com">
      <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
      <link type="text/css" href="{{ URL::asset('public/css/fna-theme.css')}}" rel="stylesheet">
      <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.js"></script>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
      <style type="text/css">
              #cover-spin {
                  position:fixed;
                  width:100%;
                  left:0;right:0;top:0;bottom:0;
                  background-color: rgba(255,255,255,0.7);
                  z-index:9999;
                  display:none;
              }

              @-webkit-keyframes spin {
                from {-webkit-transform:rotate(0deg);}
                to {-webkit-transform:rotate(360deg);}
              }

              @keyframes spin {
                from {transform:rotate(0deg);}
                to {transform:rotate(360deg);}
              }

              #cover-spin::after {
                  content:'';
                  display:block;
                  position:absolute;
                  left:48%;top:40%;
                  width:40px;height:40px;
                  border-style:solid;
                  border-color:black;
                  border-top-color:transparent;
                  border-width: 4px;
                  border-radius:50%;
                  -webkit-animation: spin .8s linear infinite;
                  animation: spin .8s linear infinite;
              }
              @media (max-width: 991px){
                  .mobile_side{
                     margin-top: 180px;
                  }
              }
             
            </style>
   </head>
   <body>
      <div id="cover-spin"></div>
      <nav class="navbar navbar-dark navbar-theme-primary px-4 col-12 d-lg-none">
         <a class="navbar-brand me-lg-5" href="index.html">
            <img class="navbar-brand-dark" src="{{ URL::asset('public/assets/img/brand/light.svg')}}" alt="Volt logo" /> <img class="navbar-brand-light" src="{{ URL::asset('public/assets/img/brand/dark.svg')}}" alt="Volt logo" />
         </a>
         <div class="d-flex align-items-center">
            <button class="navbar-toggler d-lg-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
               <span class="navbar-toggler-icon"></span>
            </button>
         </div>
      </nav>
      @section('sidebar')           
      @show 
      <main class="content">
         @yield('contentbody')         
      </main>
      <!-- jQuery -->

      <script src="{{ URL::asset('public/vendor/@popperjs/core/dist/umd/popper.min.js')}}"></script>
      <script src="{{ URL::asset('public/vendor/bootstrap/dist/js/bootstrap.min.js')}}"></script>
      <!-- Vendor JS -->
      <script src="{{ URL::asset('public/vendor/onscreen/dist/on-screen.umd.min.js')}}"></script>
      <!-- Slider -->
      <!-- <script src="{{ URL::asset('public/vendor/nouislider/distribute/nouislider.min.js')}}"></script> -->
      <!-- Smooth scroll -->
      <script src="{{ URL::asset('public/vendor/smooth-scroll/dist/smooth-scroll.polyfills.min.js')}}"></script>
      <!-- Charts -->
      <script src="{{ URL::asset('public/vendor/chartist/dist/chartist.min.js')}}"></script>
      <script src="{{ URL::asset('public/vendor/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js')}}"></script>
      <!-- Datepicker -->
      <script src="{{ URL::asset('public/vendor/vanillajs-datepicker/dist/js/datepicker.min.js')}}"></script>
      <!-- Sweet Alerts 2 -->
      <script src="{{ URL::asset('public/vendor/sweetalert2/dist/sweetalert2.all.min.js')}}"></script>
      <!-- Moment JS -->
      <script src="{{ URL::asset('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.27.0/moment.min.js')}}"></script>
      <!-- Vanilla JS Datepicker -->
      <script src="{{ URL::asset('public/vendor/vanillajs-datepicker/dist/js/datepicker.min.js')}}"></script>
      <!-- Notyf -->
      <script src="{{ URL::asset('public/vendor/notyf/notyf.min.js')}}"></script>
      <!-- Simplebar -->
      <script src="{{ URL::asset('public/vendor/simplebar/dist/simplebar.min.js')}}"></script>
      <!-- Github buttons -->
      
      <!-- Volt JS -->
      <script src="{{ URL::asset('public/assets/js/volt.js')}}"></script> 
      @stack('script-js')
  </body>
</html>