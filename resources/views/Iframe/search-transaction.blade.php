<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Search Transaction</title>
</head>
<body>
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
</body>
</html>