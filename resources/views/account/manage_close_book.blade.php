@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row">
    <div class="col-12">
        <table class="table table-bordered">
            <thead>
               <tr>
                  <th>Company Name</th>
                  <th>Accounting Year</th>
                  <th>Action</th>
               </tr>
            </thead>
            <tbody>
               <tr>
                  <td>{{$entity->name}}</td>
                  <td>{{$entity->financial_year}}</td>
                  <td> @if($button_status==1)<button class="btn btn-info close_book">Close Books</button>@endif</td>
               </tr>
            </tbody>
        </table>
    </div>
</div>
<div class="modal fade" id="closeBookModal" tabindex="-1" role="dialog" aria-labelledby="modal-form-signup" aria-hidden="true">
   <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
      <div class="modal-content ">
         <div class="modal-body p-0">
            <div class="card p-3 p-lg-4">
               <div class="col-md-12 mb-3">
                  <h3>Are you sure to close the books for AY {{$entity->financial_year}} ?</h3>
                  <p style="color: blue;">Please note this will lock all Transactions and this action cannot be reversed</p>
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
   .modal-backdrop {
      background-color: red;
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