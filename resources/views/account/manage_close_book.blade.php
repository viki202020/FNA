@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row">
    <div class="col-12">
        <h1 class="mb-1">Close the books for {{$entity->financial_year}}</h1>
        <p class="text-muted mb-2">{{$entity->name}}. Every item below must be ticked before the year can close.</p>
        @php $canClose = $button_status==1 && count($blockers)==0; @endphp
        <div class="fna-tablebox" style="max-width:900px">
           <table class="table">
              <thead><tr><th style="width:36px"></th><th>Check</th><th style="width:190px">What to do</th></tr></thead>
              <tbody>
                 @foreach($blockers as $b)
                 <tr><td class="fw-bold" style="color:var(--fna-green)">!</td><td>{{ $b['message'] }}</td><td><a class="fw-bold" href="{{ url('merchant/uncategorized-data') }}">Categorise &rsaquo;</a></td></tr>
                 @endforeach
                 @if(!count($blockers))
                 <tr><td class="fw-bold" style="color:var(--fna-green)">&#10003;</td><td>Nothing is uncategorised and no entries are drafts or unbalanced</td><td></td></tr>
                 @endif
                 <tr>
                    @if($button_status==1)
                       <td class="fw-bold" style="color:var(--fna-green)">&#10003;</td><td>The financial year has ended</td><td></td>
                    @else
                       <td class="fw-bold" style="color:var(--fna-green)">!</td><td>The financial year has not ended yet ({{ $entity->financial_year_end }})</td><td></td>
                    @endif
                 </tr>
              </tbody>
           </table>
        </div>
        <div class="mt-3 d-flex align-items-center gap-3">
           @if($canClose)
              <button class="btn btn-info close_book">Close the books for {{$entity->financial_year}}</button>
           @else
              <span class="btn" style="background:#B5C9BC;color:#fff;cursor:not-allowed">Close the books for {{$entity->financial_year}}</span>
              <span class="text-muted" style="font-size:12px">The button turns green when every item is ticked.</span>
           @endif
        </div>
        <div class="fna-foot-note" style="max-width:900px;line-height:1.6">What happens when you close: the year becomes read only &middot; balance sheet heads carry into the next year &middot; profit or loss moves to Reserves and surplus &middot; this cannot be reversed from here.</div>
    </div>
</div>
<div class="modal fade" id="closeBookModal" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content ">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <div class="col-md-12 mb-3">
                  <h3>Are you sure to close the books for AY {{$entity->financial_year}} ?</h3>
                  <p style="color: var(--fna-green);">Please note this will lock all Transactions and this action cannot be reversed</p>
               </div>
               <div class="modal-footer">
                  <button type="button" class="btn btn-success yes_action">Yes</button>
                  <button type="button" class="btn btn-danger no_action">No</button>
               </div>
            </div>
         </div>
     </div>
   </div>
</div>
<link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">
<script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>
 <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
@endsection
@push('script-head')

@endpush
<style type="text/css">
   .error{
      color:red
   }
</style>
@push('script-js')
<script type="text/javascript">
   $(".close_book").click(function(){
      $("#closeBookModal").modal('toggle');
   });
   $(".no_action").click(function(){
      $("#closeBookModal").modal('toggle');
   });
   $(".yes_action").click(function(){
      $.ajax({
         headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
         },
         url : "{{ url('merchant/close-book') }}",
         method : "POST",
         data : "",
         success : function(res){
            if(res.success==true){
               alert(res.message);
               $("#closeBookModal").modal('toggle');
               location.reload();
            }else{
               alert(res.message ? res.message : "Something went wrong")
            }
         }
      });
   });
</script>
@endpush