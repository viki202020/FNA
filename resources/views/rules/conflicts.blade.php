@extends('layout.master')
@section('title', 'Rule conflicts')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
@include('voucher._style')
<div class="row"><div class="col-12 col-xl-12">
<div class="card card-body border-0 shadow mb-4 mt-4 vch-page" style="overflow:auto;">
   <h2 class="mb-1">Rule conflicts</h2>
   <p>These lines fit more than one rule, and the rules point to different accounts. Choose the rule that should decide each line, then press <strong>Save all choices</strong> once at the bottom. Lines you leave blank stay uncategorised.</p>
   @if(session('rule_msg'))<div class="vch-box" style="background:#eafaf1;border:1px solid #27ae60;">{{ session('rule_msg') }}</div>@endif
   @if(count($rows)==0)
      <div class="vch-box" style="background:#f3f4f6;">No lines are waiting for a choice.</div>
   @else
   <form method="post" action="{{ url('/merchant/rule-conflicts') }}">
      @csrf
      <table class="vch">
         <thead><tr><th style="width:14%">Date / amount</th><th>Description and the rules that fit</th><th style="width:20%">Same answer for similar lines</th></tr></thead>
         <tbody>
         @foreach($rows as $r)
            <tr>
               <td>{{ $r->line_date }}<br><strong>{{ $r->direction=='Credit' ? 'Money in' : 'Money out' }} {{ number_format($r->amount,2) }}</strong></td>
               <td>
                  <div style="margin-bottom:6px;"><strong>{{ $r->description }}</strong></div>
                  @foreach($r->candidates as $c)
                     <label style="display:block;margin:3px 0;cursor:pointer;">
                        <input type="radio" name="choice[{{ $r->id }}]" value="{{ $c->id }}">
                        Rule {{ $c->id }} "{{ $c->name }}" &mdash; {{ $c->text }} &rarr; <strong>{{ $c->head }}</strong>
                     </label>
                  @endforeach
                  <label style="display:block;margin:3px 0;color:#6b7280;cursor:pointer;">
                     <input type="radio" name="choice[{{ $r->id }}]" value="" checked> Leave uncategorised for now
                  </label>
               </td>
               <td>
                  @if($r->group_size>1)
                     <label><input type="checkbox" name="same[{{ $r->id }}]" value="1"> Use this choice for all {{ $r->group_size }} lines that clash between these same rules</label>
                  @else <span style="color:#9ca3af;">&mdash;</span> @endif
               </td>
            </tr>
         @endforeach
         </tbody>
      </table>
      <button class="btn btn-primary mt-3" type="submit">Save all choices</button>
   </form>
   @endif
</div></div></div>
@endsection
