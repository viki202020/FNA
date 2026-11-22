@extends('layout.master')
@section('title', 'Admin only')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<h1 class="mb-1">Admin only</h1>
<p class="text-muted" style="max-width:700px">Closing books, re-opening a year, users and company setup can only be done by an admin. Ask your admin, or sign in with an admin login.</p>
@endsection
