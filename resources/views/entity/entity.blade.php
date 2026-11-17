@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row form_div" style="display: @if($entity_count>0) none @else @endif;">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4">
         <h2 class="h5 mb-2">Set up your company</h2>
         <p>This information is used to keep you GST compliant, send invoices, file tax returns, and more. <br>Make sure it's correct. </p>                       
         <form action="add-entity" method="post">
            @csrf
            <div class="row">
            <div class="col-md-4 mb-3">
               <div>
                  <label for="name">Name*</label>
                  <input class="form-control" type="text" placeholder="Name" name="name" id="name" required>
               </div>
            </div>
            
            <div class="col-md-3 mb-3">
               <div>
                  <label for="GSTIN">Your GSTIN*</label>
                  <input class="form-control" type="text" placeholder="e.g 29APPCK7465F1Z1"  name="gst" id="gst" required>
                  <small><input type="checkbox" id="check_gst"> <label for="">I don't have GSTIN</label> </small>
                  @error('gst')<div style="color:#c0392b;font-size:13px;margin-top:4px;">{{ $message }}</div>@enderror
               </div>
            </div>
            <div class="col-md-3 mb-3 FrequencyDiv">
               <label for="Frequency">GST Return Filling Frequency</label>
               <select class="form-select mb-0" aria-label="Frequency select example" name="frequency" id="frequency">
                  <option value="">Select Frequency</option>
                  <option value="Monthly">Monthly</option>
                  <option value="Quarterly">Quarterly</option>
                  <option value="Yearly">Yearly</option>
               </select>
               <ul style="color: red;">
                  @error('frequency'){{$message}}@enderror                        
               </ul>
            </div>
            <div class="col-md-6 mb-3">
               <div>
                  <label for="company_address">Company address</label>
                  <input class="form-control" type="text" placeholder="Street address" name="address" id="address" required>
               </div>
               <ul style="color: red;">
                  @error('address'){{$message}}@enderror                        
               </ul>
            </div>
            
               <div class="col-md-3 mb-3">
                  <div>
                     <label for="city_name">City</label>
                     <input class="form-control" type="text" name="city" id="city" required placeholder="City">
                     <ul style="color: red;">
                        @error('city'){{$message}}@enderror                        
                     </ul>
                  </div>
               </div>
               <div class="col-md-3 mb-3">
                  <label for="state">State</label>
                  <select class="form-select mb-0" aria-label="state select example" name="state" id="state" required>
                     <option selected>Delhi</option>
                     <option value="1">MP</option>
                     <option value="2">UP</option>
                  </select>
                  <ul style="color: red;">
                     @error('state'){{$message}}@enderror                        
                  </ul>
               </div>
               <div class="col-md-2 mb-3">
                  <div class="form-group">
                     <label for="pincode">Pincode</label>
                     <input class="form-control"  type="text" placeholder="Pincode" required name="pincode" id="pincode">
                  </div>
                  <ul style="color: red;">
                     @error('state'){{$message}}@enderror                        
                  </ul>
               </div>
            </div>
            <div class="row">
               <div class="col-md-3 mb-3">
                  <div class="form-group">
                     <label for="phone">Company phone</label>
                     <input class="form-control" type="number" placeholder="Company phone" required name="mobile" id="mobile">
                  </div>
                  <ul style="color: red;">
                     @error('state'){{$message}}@enderror                        
                  </ul>
               </div>
               <div class="col-md-3 mb-3">
                  <div class="form-group">
                     <label for="email">Company email</label>
                     <input class="form-control" id="email" type="email" placeholder="name@company.com" required name="email" id="email">
                  </div>
                  <ul style="color: red;">
                     @error('state'){{$message}}@enderror                        
                  </ul>
               </div>
               <div class="col-md-2 mb-3">
                  <div class="form-group">
                     <label for="start_year">Financial Year From</label><br>
                     <input type="date" name="start_year" id="start_year" class="form-control" value="{{$financial_year_start}}">
                     
                     <!-- <select class="form-control" id="financial_year" name="financial_year">
                        <option value="2022-2023">2022-2023</option>
                     </select> -->
                  </div>                  
               </div>
               <div class="col-md-2 mb-3">
                  <div class="form-group">
                     <label for="end_year">Financial Year End</label><br>
                     <input type="date" name="end_year" id="end_year" class="form-control" value="{{$financial_year_end}}">                     
                     <small class="text-muted">Indian companies usually run April to March; Australian companies July to June. Change both dates if yours differs.</small>
                  </div>                  
               </div>
               <input type="hidden" name="entity_id" id="entity_id">
            </div>
            <div class="mt-3">
               <button class="btn btn-gray-800 mt-2 animate-up-2" type="submit">Save all</button>
               @if(session('success_msg'))
                  <span style="color:green">{{session('success_msg')}}</span>
               @endif
            </div>
         </form>
      </div>      
   </div>
</div>
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4">
         <h2 class="mb-4">Company Name</h2> <button class="btn btn-info close_book">Close Book</button>
         <table class="table table-bordered yajra-datatable">
            <thead>
               <tr>
                  <th>No</th>
                  <th>GSTIN</th>
                  <th>Frequency</th>
                  <!-- <th>Address</th> -->
                  <th>City</th>
                  <th>State</th>
                  <th>Pincode</th>
                  <th>Mobile</th>
                  <th>Email</th>
                  <th>Action</th>
               </tr>
            </thead>
            <tbody>
            </tbody>
         </table>
      </div>
   </div>
</div>
<meta name="csrf-token" content="{{ csrf_token() }}" />
<link href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.10.21/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.21/js/dataTables.bootstrap4.min.js"></script>

@endsection
@push('script-head')
@endpush
@push('script-js')
<script type="text/javascript">
   $(document).ready(function(){
      $("#check_gst").click(function(){
         if($(this).prop('checked')==true){
            $("#gst").attr('required',false);
            $(".FrequencyDiv").hide();
         }else{
            $("#gst").attr('required',true);
            $(".FrequencyDiv").show();
         }
      });
   });
   $(function () {      
      var table = $('.yajra-datatable').DataTable({
          processing: true,
          serverSide: true,
          ajax: "{{ route('students.list') }}",
          columns: [
              {data: 'DT_RowIndex', name: 'DT_RowIndex'},
              {data: 'gst_number', name: 'gst_number'},
              {data: 'frequency', name: 'frequency'},
              // {data: 'address', name: 'address'},
              {data: 'city', name: 'city'},
              {data: 'state', name: 'state'},
              {data: 'pincode', name: 'pincode'},
              {data: 'mobile', name: 'mobile'},
              {data: 'email', name: 'email'},
              {
                  data: 'action', 
                  name: 'action', 
                  orderable: true, 
                  searchable: true
              },
          ]
      });
      
    });

   $(document).on("click",".edit_entity",function(){
      $("#cover-spin").show();
      let id  = $(this).attr('data-id');
      let gst_number  = $(this).attr('data-gst_number');
      let frequency  = $(this).attr('data-frequency');
      let address  = $(this).attr('data-address');
      let city  = $(this).attr('data-city');
      let state  = $(this).attr('data-state');
      let pincode  = $(this).attr('data-pincode');
      let mobile  = $(this).attr('data-mobile');
      let email  = $(this).attr('data-email');
      let name  = $(this).attr('data-name');
      $("#start_year").prop('disabled',true);
      $("#end_year").prop('disabled',true);
      $("#start_year").val($(this).attr('data-start_year'));
      $("#end_year").val($(this).attr('data-end_year'));
      $("#gst").val(gst_number);
      $("#frequency").val(frequency);
      $("#address").val(address);
      $("#city").val(city);
      $("#state").val(state);
      $("#pincode").val(pincode);
      $("#mobile").val(mobile);
      $("#email").val(email);
      $("#entity_id").val(id);
      $("#name").val(name);
      $(".form_div").show();
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
      setTimeout($("#cover-spin").hide(), 5000);
      //$("#cover-spin").hide();
   });
   $(".close_book").click(function(){
      if(confirm("Are you confirm to close book?")==true){
         
         $.ajax({
            headers: {
               'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            url : "{{ url('merchant/close-book') }}",
            method : "POST",
            data : "",
            success : function(res){
               if(res.success==true){
                  alert(res.message)
               }else{
                  alert("Something went wrong")
               }
            }
         });
      }
   });
</script>
@endpush