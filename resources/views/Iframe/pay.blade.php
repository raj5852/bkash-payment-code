@extends('layout')
@section('main')
<center>
  <p class="text-success"><b>Bkash Demo Payment</b></p>
  <p><b> Customer wallet numbers:</b> 01619777283</p>
  <p><b>PIN:</b> 12121</p>
  <p><b>OTP:</b> 123456</p>
</center>

<div class="d-flex justify-content-center container mt-5">
  <div class="card p-3 bg-white"><i class="fa fa-apple"></i>
    <div class="about-product text-center mt-2"><img src="https://9to5mac.com/wp-content/uploads/sites/6/2021/10/MacBook-Pro-2021.jpg?quality=82&strip=all&w=1000" width="300">
      <div>
        <h4>Believing is seeing</h4>
        <h6 class="mt-0 text-black-50">Apple pro display XDR</h6>
      </div>
    </div>
    <div class="stats mt-2">
      <div class="d-flex justify-content-between p-price"><span>Pro Display XDR</span><span>$5,999</span></div>
      <div class="d-flex justify-content-between p-price"><span>Pro stand</span><span>$999</span></div>
      <div class="d-flex justify-content-between p-price"><span>Vesa Mount Adapter</span><span>$199</span></div>
    </div>
    <div class="d-flex justify-content-between total font-weight-bold mt-4"><span>Total</span><span>$7,197.00</span></div><br>
    <input type="hidden" id="price" name="price" value="{{ $amount }}">
    <button class="btn btn-primary" id="bKash_button">
      <span class="spinner-border spinner-border-sm" id="spinner" role="status" aria-hidden="true"></span>
      Pay with Bkash
    </button>

  </div>
</div>

@endsection
@section('js')

<script>
  $("#spinner").hide();
  var price = document.getElementById('price').value;
  console.log(price);
  var paymentID = '';
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  });
  bKash.init({
    paymentMode: 'checkout', //fixed value ‘checkout’
    //paymentRequest format: {amount: AMOUNT, intent: INTENT}
    //intent options
    //1) ‘sale’ – immediate transaction (2 API calls)
    //2) ‘authorization’ – deferred transaction (3 API calls)
    paymentRequest: {
      amount: price, //max two decimal points allowed
      intent: 'sale'
    },
    createRequest: function(request) { //request object is basically the paymentRequest object, automatically pushed by the script in createRequest method
      console.log("create working !!")
      $("#bKash_button").prop('disabled', true);
      $("#spinner").show();

      $.ajax({
        url: 'bkash/create',
        type: 'POST',
        data: JSON.stringify(request),
        contentType: 'application/json',
        success: function(data) {
          console.log(data)
          if (data && data.paymentID != null) {
            paymentID = data.paymentID;
            bKash.create().onSuccess(data); //pass the whole response data in bKash.create().onSucess() method as a parameter
            $("#bKash_button").prop('disabled', false);
            $("#spinner").hide();
          } else {
            bKash.create().onError();
          }
        },
        error: function() {
          bKash.create().onError();
        }
      });
    },
    executeRequestOnAuthorization: function() {
      console.log("execute working !!")
      $.ajax({
        url: 'bkash/execute',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({
          "paymentID": paymentID
        }),
        success: function(data) {

          console.log("execute response ", data)

          if (data && data.paymentID != null) {
            console.log("trxID: ", data.trxID)
            window.location.href = '/success'; // Your redirect route when successful payment
          } else {
            console.log("error ");
            window.location.href = '/fail'; // Your redirect route when fail payment
            bKash.execute().onError();
          }
        },
        error: function() {
          bKash.execute().onError();
        }
      });
    },
    onClose: function() {
      window.location.href = '/'; // Your redirect route when cancel payment
    },
  });
</script>

@endsection



