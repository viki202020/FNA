@extends('layout.master')
@section('title', 'Receipts to match')
@section('sidebar')
   @parent
   @include('layout.sidebar')
@endsection
@section('contentbody')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
   .fna-modal-scrim{position:fixed;inset:0;background:rgba(1,54,2,.35);display:none;align-items:flex-start;justify-content:center;padding:40px 16px;overflow:auto;z-index:2000}
   .fna-modal-scrim.show{display:flex}
   .fna-modal{background:#fff;border-radius:12px;width:100%;max-width:860px;padding:16px 18px;box-shadow:0 10px 40px rgba(0,0,0,.25)}
   .fna-line{background:var(--fna-tint);border:1px solid var(--fna-line);border-radius:8px;padding:8px 12px;margin:8px 0 10px;display:flex;flex-wrap:wrap;gap:6px 22px}
   .fna-cand{display:grid;grid-template-columns:26px 1fr 110px 110px 110px;gap:6px;align-items:center;border-top:1px solid var(--fna-line);padding:8px 6px;cursor:pointer;margin:0;font-weight:400;font-size:13px;color:var(--fna-ink)}
   .fna-cand:hover{background:var(--fna-tint)}
   .fna-why{font-size:12px;color:var(--fna-muted)}
   .fna-sum{display:flex;justify-content:space-between;margin-top:10px;padding:8px 12px;border-radius:8px;background:var(--fna-band);font-weight:700;color:var(--fna-green)}
   .fna-sum.bad{background:#EEF0EE;color:var(--fna-grey)}
</style>
<div style="display:flex;flex-direction:column;min-height:calc(100vh - 40px)">
   <div class="mb-2"><h1 style="margin:0">Receipts to match</h1>
      <span class="text-muted">Bank lines whose amount fits an open invoice or bill. No rule has touched them, and nothing is matched until you confirm.</span></div>
   <div class="fna-radios mb-2">
      <label class="{{ $tab==='in' ? 'on' : '' }}"><a href="?tab=in" style="color:inherit;text-decoration:none">Receipts (money in) {{ count($rows['in']) }}</a></label>
      <label class="{{ $tab==='out' ? 'on' : '' }}"><a href="?tab=out" style="color:inherit;text-decoration:none">Payments (money out) {{ count($rows['out']) }}</a></label>
   </div>
   <div class="fna-tablebox"><table class="table">
      <thead><tr><th>Date</th><th>Bank line</th><th style="text-align:right">Amount</th><th>Best guess</th><th></th></tr></thead>
      <tbody>
      @forelse($rows[$tab] as $r)
         <tr style="cursor:pointer" class="match-row" data-id="{{ $r['line']->id }}">
            <td>{{ $r['line']->st_date }}</td><td>{{ $r['line']->remark }}</td>
            <td style="text-align:right">{{ number_format($r['amount'],2) }}</td>
            <td>{{ $r['best']['number'] }} &middot; {{ $r['best']['party'] }} @if($r['count']>1)<span class="text-muted">(+{{ $r['count']-1 }} more)</span>@endif</td>
            <td><b style="color:var(--fna-green)">Match &rsaquo;</b></td>
         </tr>
      @empty
         <tr><td colspan="5" class="text-muted">Nothing to match here. Lines that fit no invoice or bill stay in Uncategorised entry.</td></tr>
      @endforelse
      </tbody>
   </table></div>
   <div class="fna-foot-note">Guesses use the document number in the bank wording, the party's name, the same amount, and any "payment sent" or "payment received" note. A payment is booked on the bank's own date.</div>
</div>
<div class="fna-modal-scrim" id="scrim"><div class="fna-modal">
   <div style="display:flex;justify-content:space-between;align-items:center"><h1 style="font-size:18px;margin:0" id="mt">Match this bank line</h1><a href="#" id="mx" style="font-weight:700;text-decoration:none">Close &#10005;</a></div>
   <div class="fna-line" id="ml"></div>
   <div id="cands"></div>
   <div class="fna-sum bad" id="sum"></div>
   <div class="mt-3 d-flex gap-2 align-items-center">
      <button class="btn btn-primary" id="go" disabled>Match selected</button>
      <a class="btn btn-outline-primary" href="{{ url('merchant/uncategorized-data') }}">None of these: choose an account instead</a>
      <span class="text-danger" id="err" style="font-weight:700"></span>
   </div>
</div></div>
@endsection
@push('script-js')
<script>
$(function(){
   var cur=null;
   function inr(n){return Number(n).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2});}
   function upd(){
      if($('#cands input[name=chq]:checked').length){$('#sum').removeClass('bad').html('<span>&#10003; Clears this cheque, same amount as the bank line</span><span>1 selected</span>');$('#go').prop('disabled',false);return;}
      var sel=0,n=0;$('#cands input[type=checkbox]:checked').not('.tds-on').each(function(){var i=+$(this).data('i');sel+=cur.candidates[i].balance;n++;var t=$('#cands .tds-on[data-i='+i+']:checked');if(t.length){sel-=parseFloat($('#cands .tds-amt[data-i='+i+']').val())||0;}});
      var s=$('#sum'),go=$('#go'); var left=Math.round((cur.amount-sel)*100)/100;
      if(!n){s.addClass('bad').html('<span>Nothing ticked</span><span>Money '+inr(cur.amount)+'</span>');go.prop('disabled',true);}
      else if(left>0.004){s.addClass('bad').html('<span>Ticked '+inr(sel)+' is less than the money ('+inr(cur.amount)+')</span><span>Tick more</span>');go.prop('disabled',true);}
      else if(left<-0.004){s.removeClass('bad').html('<span>Part payment: '+inr(cur.amount)+' goes to the oldest ticked document first</span><span>'+n+' selected</span>');go.prop('disabled',false);}
      else{s.removeClass('bad').html('<span>&#10003; Ticked '+inr(sel)+' equals the bank amount</span><span>'+n+' selected</span>');go.prop('disabled',false);}
   }
   $('.match-row').on('click',function(){
      $.get("{{ url('merchant/match/candidates') }}/"+$(this).data('id'),function(r){
         cur=r;cur.id=$('.match-row:hover').data('id');
         $('#mt').text(r.in?'Which invoice did this money pay?':'Which bill did this payment settle?');
         $('#ml').html('<span>Date <b>'+r.date+'</b></span><span>Amount <b>'+inr(r.amount)+'</b></span><span style="flex:1 1 100%">Bank wording <b></b></span>');
         $('#ml b').last().text(r.remark||'');
         var h='';$.each(r.cheques||[],function(i,c){
            h+='<label class="fna-cand"><input type="radio" name="chq" data-chq="'+c.id+'"><span><b></b><div class="fna-why"></div></span><span style="text-align:right">'+c.date+'</span><span style="text-align:right">'+inr(c.amount)+'</span><span><span class="fna-chip posted">Cheque</span></span></label>';});
         $.each(r.candidates,function(i,c){
            h+='<label class="fna-cand"><input type="checkbox" data-i="'+i+'" value="'+c.id+'" '+''+'><span><b></b><div class="fna-why"></div>'+(c.tds>0?'<div class="fna-why"><label style="display:inline;font-weight:400"><input type="checkbox" class="tds-on" data-i="'+i+'" '+(c.tds_fit?'checked':'')+'> TDS deducted <input class="tds-amt" data-i="'+i+'" value="'+Number(c.tds).toFixed(2)+'" style="width:90px;text-align:right;border:1px solid #BFD3C7;border-radius:4px;padding:1px 4px"></label></div>':'')+'</span><span style="text-align:right">Due '+c.due+'</span><span style="text-align:right">'+inr(c.balance)+'</span><span><span class="fna-chip '+(c.exact?'posted':'waiting')+'">'+(c.exact?'Same amount':'Possible')+'</span></span></label>';});
         $('#cands').html(h);
         var nc=(r.cheques||[]).length;
         $.each(r.cheques||[],function(i,c){var l=$('#cands label').eq(i);l.find('b').text('Cheque '+c.cheque_no+' · '+c.party+' ('+c.doc+')');l.find('.fna-why').text(c.why+'. Clears the cheque; no new entry is made.');});
         $.each(r.candidates,function(i,c){var l=$('#cands label').eq(nc+i);l.find('b').text(c.number+' · '+c.party);l.find('.fna-why').text(c.why);});
         $('#err').text('');$('#scrim').addClass('show');upd();
      });
   });
   $('#cands').on('change input','input',upd);
   $('#mx').on('click',function(e){e.preventDefault();$('#scrim').removeClass('show');});
   $('#go').on('click',function(){
      var ids=$('#cands input[type=checkbox]:checked').not('.tds-on').map(function(){return this.value;}).get();
      var tds={};$('#cands .tds-on:checked').each(function(){var i=+$(this).data('i');tds[cur.candidates[i].id]=$('#cands .tds-amt[data-i='+i+']').val();});
      var chq=$('#cands input[name=chq]:checked').data('chq');
      $.ajax({url:"{{ url('merchant/match/confirm') }}",method:'POST',headers:{'X-CSRF-TOKEN':$('meta[name=csrf-token]').attr('content')},data:{statement_id:cur.line_id,doc_ids:ids,cheque_id:chq,tds:tds},
         success:function(){location.reload();},error:function(x){$('#err').text((x.responseJSON&&x.responseJSON.message)||'Could not match.');}});
   });
});
</script>
@endpush
