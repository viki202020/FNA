@extends('layout.master')
@section('title', 'Entity')  
@section('sidebar')
   @parent 
   @include('layout.sidebar')
@endsection
@section('contentbody')
<div class="row">
   <div class="col-12 col-xl-12">
      <div class="card card-body border-0 shadow mb-4 mt-4">
         <h2 class="h5 mb-2">Invoice</h2>   <button class="btn btn-danger" data-msg="text" data-type="text" id="notifyTopRight" style="display: none;">Top right danger</button><form action="" method="post" id="product_form_data" name="product_form_data">
            @csrf
            <div class="row">
               <div class="col-md-3 mb-3">                  
                  <label for="customer_name">Customer Name</label><br>
                  <select class="form-control" id="customer_name" name="customer_name">
                     <option value="">Select Customer</option>
                     @foreach($customers as $customer)
                        <option value="{{$customer->id}}" data-gst="{{$customer->gst}}">{{$customer->first_name}}</option>
                     @endforeach
                  </select>                  
               </div>
               <div class="col-md-2 mb-3">
                  <label for="invoice_no">Invoice No.</label>
                  <input class="form-control" type="text" name="invoice_no" id="invoice_no" readonly value="123">
               </div>
               <div class="col-md-3 mb-3">
                  <label for="order_no">Order No.</label>
                  <input class="form-control" type="text" placeholder="Enter Order No" name="order_no" id="order_no">
               </div> 
               <div class="col-md-2 mb-3">
                  <label for="invoice_date">Invoice Date</label>
                  <input class="form-control" type="date" name="invoice_date" id="invoice_date" value="{{date('Y-m-d')}}">
               </div>
               <div class="col-md-2 mb-3">
                  <label for="due_date">Due Date</label>
                  <input class="form-control" type="date" name="due_date" id="due_date" value="{{date('Y-m-d', strtotime('+30 days'))}}">
               </div>
               <div class="col-md-3 mb-3">
                  <label for="sales_person">Sales Person</label>
                  <input class="form-control" type="text" name="sales_person" id="sales_person" placeholder="Sales Person">
               </div>
               <div class="col-md-12 mb-3">
                  <table class="table table-bordered">
                     <thead>
                        <th style="width: 17%;">Item Details</th>
                        <th style="width: 5%;">Quantity</th>
                        <th style="width: 12%;">Rate</th>
                        <th style="width: 14%;">Discount(%)</th>
                        <th style="width: 15%;">Amount</th>
                        <th>Tax</th>
                        <th>GST</th>
                        <th></th>
                     </thead>
                     <tbody>
                        <tr>
                           <td>
                              <select class="form-control product" data-id="1" id="product_1" name="product[]">
                                 <option value="">Select Product</option>
                                 @foreach($products as $product)
                                    <option value="{{$product->id}}" data-price="{{$product->price}}" data-hsn_code="{{$product->hsn_code}}" data-tax_preference="{{$product->tax_preference}}" data-tax_rate="{{$product->tax_rate}}" data-description="{{$product->description}}">{{$product->name}}</option>
                                 @endforeach
                              </select>
                           </td>
                           <td>
                              <input type="text" name="quantity[]" class="form-control quantity" data-id="1" id="quantity_1" value="1">
                           </td>
                           <td>
                              <input type="text" class="form-control price" data-id="1" id="price_1" readonly placeholder="Rate">
                           </td>
                           <td>
                              <input type="text" name="discount[]" class="form-control discount" data-id="1" id="discount_1" placeholder="Discount">
                           </td>
                           <td>
                              <input type="text" class="form-control amount" data-id="1" id="amount_1" readonly placeholder="Amount">
                           </td>
                           <td>
                              <input type="text" class="form-control tax" data-id="1" id="tax_1" readonly>
                           </td>
                           <td>
                              <input type="text" class="form-control gst" data-id="1" id="gst_1" readonly>
                           </td>
                           <td></td>
                        </tr>
                        <tr>
                           <td><button class="btn btn-success btn-xs add_product" type="button">Add</button></td>
                           <td></td>
                           <td></td>
                           <td></td>
                           <td></td>
                           <td></td>
                           <td></td>
                           <td></td>
                        </tr>
                     </tbody>
                  </table>
               </div>
               <div class="col-md-6 mb-3">
                  <table class="table table-bordered">
                     <tr>
                        <td>Customer Notes</td>
                        <td><textarea cols="40" rows="5"></textarea></td>
                     </tr>
                     <tr>
                        <td>Terms&conditions</td>
                        <td><textarea cols="40" rows="5" readonly></textarea></td>
                     </tr>
                  </table>
               </div>
               <div class="col-md-6 mb-3">
                  <table class="table table-bordered">
                     <tr>
                        <td>Sub Total</td>
                        <td id="sub_total"></td>
                     </tr>
                     <tr>
                        <td>IGST</td>
                        <td id="igst_amount"></td>
                     </tr>

                     <tr>
                        <td>
                           <input type="radio" value="tds" name="other_tax" onclick="calculation()"> TDS <input type="radio" value="tcs" name="other_tax" onclick="calculation()"> TCS 
                           <select name="other_tax_rate" class="form-control" id="other_tax_rate" onchange="calculation()">
                              <option value="">Select Tax</option>
                              <option value="5">U/S 194 H(5%)</option>
                              <option value="10">U/S 194(10%)</option>
                              <option value="10">U/S 194 A(10%)</option>
                           </select></td>
                        <td id="other_tax_amount"></td>
                     </tr>
                     <tr>
                        <td>Adustment</td>
                        <td><input type="text" class="form-control" id="adustment" name="adustment" placeholder="Adustment" onkeyup="calculation();"></td>
                     </tr>
                     <tr>
                        <td>Total</td>
                        <td id="grand_total"></td>
                     </tr>
                  </table>                  
               </div>
               <div class="col-md-12 md-3">
                  <input type="checkbox" name=""> Collect payment via Mobilpay payment gateway ? 
               </div>
            </div>
            <div class="mt-3">
               <button class="btn btn-gray-800 mt-2 animate-up-2 save_product" type="submit" disabled>Submit</button>
            </div>
         </form>
      </div>      
   </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.min.js"></script>
<style type="text/css">
   .error{
      color:red
   }
</style>
@endsection
@push('script-head')
@endpush
@push('script-js')
<script type="text/javascript">   
   $(".type").change(function(){
      if($('input[name="type"]:checked').val()=="Goods"){
         $("#hsn_title").html('HSN Code');
         $("#hsn_code").attr("placeholder","Enter HSN Code");
      }else{
         $("#hsn_title").html('SAC');
         $("#hsn_code").attr("placeholder","Enter SAC");
      }
   });
   $("#tax_preference").change(function(){
      $("#tax_rate").attr('disabled',false);
      if($(this).val()==1){

      }else if($(this).val()==0){
         $("#tax_rate").val(0);
         $("#tax_rate").attr('disabled',true);
      }else{

      }
   });
   if($("#product_form_data").length > 0) {
      $("#product_form_data").validate({
         rules: {
            name: {
               required: true,
               maxlength: 50
            },
            unit: {
               required: true,
            },
            price: {
               required: true,
            },
         },
         messages: {
            name: {
               required: "Please enter name",
               maxlength: "Your name maxlength should be 50 characters long."
            },
            unit: {
               required: "Please select unit",
            },
            price: {
               required: "Please enter price",
            },
         },
         submitHandler: function(form) {
            $.ajaxSetup({
               headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               }
            });
            $('.save_product').html('Please Wait...');
            $(".save_product"). attr("disabled", true);
            $.ajax({
               url: "{{url('merchant/add-product')}}",
               type: "POST",
               data: $('#product_form_data').serialize(),
               success: function( response ) {
                  $('.save_product').html('Submit');
                  $('.save_product').attr("disabled", false);
                  $("#notifyTopRight").attr('data-msg',response.message);
                  if(response.success==true){
                     $("#notifyTopRight").attr('data-type','success');
                  }else{
                     $("#notifyTopRight").attr('data-type','error');
                  }
                  
                  $("#notifyTopRight").click();
                  document.getElementById("product_form_data").reset();
               },
               error: function(response){
                  $("#notifyTopRight").attr('data-msg','Internal Server Error');
                  $("#notifyTopRight").attr('data-type','error');
                  $("#notifyTopRight").click();
                  
                  $('.save_product').html('Submit');
                  $('.save_product').attr("disabled", false);
               }
            });
         }
      });
   }
   document.getElementById('notifyTopRight').addEventListener('click', function () {
      let type = $(this).attr('data-type');
      let msg = $(this).attr('data-msg');
      const notyf = new Notyf({
         position: {
            x: 'right',
            y: 'top',
         },
         types: [
            {
               type: 'error',
               background: '#FA5252',
               icon: {
                  className: 'fas fa-times',
                  tagName: 'span',
                  color: '#fff'
               },
               dismissible: false
            }
         ]
      });
      notyf.open({
         type: type,//error
         message: msg
      });
   });
   var add_more_count = 1;
   $(".add_product").click(function(){
      add_more_count++;
      let $curRow = $(this).closest('tr');
      $newRow = '<tr id="tr_'+add_more_count+'"><td><select class="form-control product" data-id="'+add_more_count+'" id="product_'+add_more_count+'" name="product[]"><option value="">Select Product</option>@foreach($products as $product)<option value="{{$product->id}}" data-price="{{$product->price}}" data-hsn_code="{{$product->hsn_code}}" data-tax_preference="{{$product->tax_preference}}" data-tax_rate="{{$product->tax_rate}}" data-description="{{$product->description}}">{{$product->name}}</option>@endforeach</select></td><td><input type="text" name="quantity[]" class="form-control quantity" data-id="'+add_more_count+'" id="quantity_'+add_more_count+'" value="1"></td><td><input type="text" class="form-control price" readonly placeholder="Rate" data-id="'+add_more_count+'" id="price_'+add_more_count+'"></td><td><input type="text" name="discount[]" class="form-control discount" data-id="'+add_more_count+'" id="discount_'+add_more_count+'" placeholder="Discount"></td><td><input type="text" class="form-control amount" data-id="'+add_more_count+'" id="amount_'+add_more_count+'" readonly placeholder="Amount"></td><td><input type="text" class="form-control tax" data-id="'+add_more_count+'" id="tax_'+add_more_count+'" readonly></td><td><input type="text" class="form-control gst" data-id="'+add_more_count+'" id="gst_'+add_more_count+'" readonly></td><td><button class="btn btn-danger btn-xs remove_product" type="button" data-id="'+add_more_count+'">X</button></td></tr>';
      $curRow.before($newRow);
   });
   $(document).on("click",".remove_product",function(){
      let id = $(this).attr('data-id');      
      $("#tr_"+id).remove();   
      calculation();   
   });
   $(document).on("change",".product",function(){
      let id = $(this).attr('data-id');
      itemCalculation(id);
   });
   $(document).on("keyup",".quantity",function(){
      let id = $(this).attr('data-id');
      itemCalculation(id);
   });
   $(document).on("keyup",".discount",function(){
      let id = $(this).attr('data-id');
      itemCalculation(id);
   });
   function itemCalculation(id){
      let price = $("#product_"+id+" option:selected").attr('data-price');
      let hsn_code = $("#product_"+id+" option:selected").attr('data-hsn_code');
      let tax_preference = $("#product_"+id+" option:selected").attr('data-tax_preference');
      let tax_rate = $("#product_"+id+" option:selected").attr('data-tax_rate');
      let description = $("#product_"+id+" option:selected").attr('data-description');
      let quantity = $("#quantity_"+id).val();
      let discount = $("#discount_"+id).val();
      let product_amount = quantity*price;
      $("#price_"+id).val(price);
      if(discount!="" && discount!=0){
         product_discount = (product_amount*discount)/100;
         product_amount = product_amount - product_discount;
      }
      if(tax_preference==1){
         $("#tax_"+id).val(tax_rate+"%");
         product_tax = (product_amount*tax_rate)/100;
         $("#gst_"+id).val(product_tax);
      }else{
         $("#tax_"+id).val("0%");
         $("#gst_"+id).val(0);
      }
      product_amount = product_amount.toFixed(2);
      $("#amount_"+id).val(product_amount);
      calculation();
   }
   function calculation(){
      let sub_total = 0;
      $(".product").each(function(){
         let id = $(this).attr('data-id');
         if($(this).val()!='' && $("#amount_"+id).val()!=''){
            sub_total = parseFloat(sub_total) + parseFloat($("#amount_"+id).val());
         }
      });
      sub_total = sub_total.toFixed(2);
      $("#sub_total").html(sub_total);
      let gst_amount = 0;
      $(".product").each(function(){
         let id = $(this).attr('data-id');
         if($(this).val()!='' && $("#gst_"+id).val()!=''){
            gst_amount = parseFloat(gst_amount) + parseFloat($("#gst_"+id).val());
         }
      });
      gst_amount = gst_amount.toFixed(2);
      $("#igst_amount").html(gst_amount);
      let other_tax_amount = 0;
      let other_tax = $('input[name="other_tax"]:checked').val();
      let other_tax_rate = $("#other_tax_rate").val();
      if((other_tax=="tcs" || other_tax=="tds") && other_tax_rate!=""){
         let taxable_amount = parseFloat(sub_total) + parseFloat(gst_amount);
         other_tax_amount = (taxable_amount*other_tax_rate)/100;
         $("#other_tax_amount").html(other_tax_amount);
      }else{
         $("#other_tax_amount").html('');
         other_tax_amount = 0;
      }

      let adustment = $("#adustment").val();
      if(adustment==""){
         adustment = 0;
      }
      let grand_total = parseFloat(sub_total) + parseFloat(gst_amount) - parseFloat(other_tax_amount) - parseFloat(adustment);
      grand_total = grand_total.toFixed(2);
      $("#grand_total").html(grand_total);
   }
   function addCommas(nStr){
      nStr += '';
      x = nStr.split('.');
      x1 = x[0];
      x2 = x.length > 1 ? '.' + x[1] : '';
      var rgx = /(\d+)(\d{3})/;
      while (rgx.test(x1)) {
         x1 = x1.replace(rgx, '$1' + ',' + '$2');
      }
      return x1 + x2;
   }
</script>
@endpush