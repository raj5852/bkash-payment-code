@extends('layout')
@section('main')
<div style="text-align: center;">
    <br><br>
    <form action="/bkash/search-transaction" method="GET">
        <label for="trxID">TrxID:</label>
        <input type="text" id="trxID" name="trxID"><br><br>
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