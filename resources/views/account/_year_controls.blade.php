{{-- Under the year box: show/hide closed years, and the closed or re-opened banner --}}
<div style="margin:6px 0 10px 0;font-size:13px;">
   @if(request('closed'))
      <a href="?">Hide closed years</a>
   @else
      <a href="?closed=1">Show closed years</a>
   @endif
</div>
@if(!empty($year_is_closed))
   <div style="margin:8px 0;padding:10px 14px;border-radius:6px;border:1px solid #b9770e;background:#fef5e7;color:#7e5109;">
      <strong>Financial year {{ $selected_year }} is closed. Read only.</strong>
      Next year's opening balances were taken from it, so it cannot be changed here.
   </div>
@elseif(!empty($year_is_reopened))
   <div style="margin:8px 0;padding:10px 14px;border-radius:6px;border:1px solid #c0392b;background:#fdecea;color:#922b21;">
      <strong>Financial year {{ $selected_year }} has been re-opened.</strong>
      After any change, check the opening balances of the following year.
   </div>
@endif
