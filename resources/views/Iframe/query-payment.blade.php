@extends('layout')
@section('main')
<div style="text-align: center;">
    <br><br>
    <form action="/bkash/query-payment" method="GET">
        <label for="paymentID">PaymentID:</label>
        <input type="text" id="paymentID" name="paymentID"><br><br>
        <input type="submit" value="Submit">
    </form>
</div>
<br><br>
<div style="text-align: center;">
    @if(isset($response))
    {{ $response }}
    @endif
</div>
@endsection