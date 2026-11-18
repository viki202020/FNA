@extends('layout.master')
@section('title', 'Rule conflicts')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <h1 style="margin:0 0 4px">Rule conflicts</h1>
   <p style="margin:0 0 10px;color:var(--fna-muted)">These lines fit more than one rule, and the rules point to different accounts. Choose for each line, then save once at the bottom.</p>
   @if(session('rule_msg'))<div class="alert alert-success">{{ session('rule_msg') }}</div>@endif
   @if(count($rows)==0)
      <div class="fna-tablebox" style="padding:18px;text-align:center;color:var(--fna-muted)">No lines are waiting for a choice.</div>
   @else
   <form method="post" action="{{ url('/merchant/rule-conflicts') }}">
      @csrf
      <div class="fna-tablebox">
         <table class="table">
            <thead><tr><th style="width:110px">Date</th><th>Description and the rules that fit</th><th style="text-align:right;width:140px">Amount</th></tr></thead>
            <tbody>
            @foreach($rows as $r)
               <tr>
                  <td style="vertical-align:top">{{ $r->line_date }}</td>
                  <td>
                     <div style="font-weight:700;margin-bottom:6px">{{ $r->description }}</div>
                     @foreach($r->candidates as $c)
                        <label style="display:flex;gap:8px;align-items:center;margin:2px 0;font-weight:400;font-size:13px;color:var(--fna-ink)">
                           <input type="radio" name="choice[{{ $r->id }}]" value="{{ $c->id }}"> <span><strong>Rule {{ $c->id }}</strong> · {{ $c->text }} &rarr; <strong>{{ $c->head }}</strong></span>
                        </label>
                     @endforeach
                     <label style="display:flex;gap:8px;align-items:center;margin:2px 0;font-weight:400;font-size:13px;color:var(--fna-muted)"><input type="radio" name="choice[{{ $r->id }}]" value="" checked> Leave uncategorised for now</label>
                     @if($r->group_size>1)
                        <label style="display:flex;gap:8px;align-items:center;margin:4px 0 0;font-weight:400;font-size:13px;color:#3E5A48"><input type="checkbox" name="same[{{ $r->id }}]" value="1"> Use this choice for all {{ $r->group_size }} lines that clash between these same rules</label>
                     @endif
                  </td>
                  <td style="text-align:right;vertical-align:top;font-variant-numeric:tabular-nums">{{ $r->direction=='Credit' ? 'Money in' : 'Money out' }} {{ number_format($r->amount,2) }}</td>
               </tr>
            @endforeach
            </tbody>
         </table>
      </div>
      <div style="margin-top:12px;display:flex;gap:10px;align-items:center"><button class="btn btn-success" type="submit">Save all choices</button><span style="color:var(--fna-muted);font-size:12px">Every choice is recorded in the Rule audit.</span></div>
   </form>
   @endif
   <div class="fna-foot-note">Lines you leave blank stay uncategorised and still block closing the year.</div>
</div>
@endsection
