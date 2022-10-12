<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Util\BkashCredential;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BkashController extends Controller
{
    private $base_url;

    public function __construct()
    {
        $this->base_url = 'https://checkout.sandbox.bka.sh/v1.2.0-beta';
        //$this->base_url = 'https://checkout.pay.bka.sh/v1.2.0-beta'; 
    }

    public function authHeaders(){
        return array(
            'Content-Type:application/json',
            'Authorization:' .Session::get('bkash_token'),
            'X-APP-Key:'.env('BKASH_CHECKOUT_APP_KEY')
        );
    }
         
    public function curlWithBody($url,$header,$method,$body_data_json){
        $curl = curl_init($this->base_url.$url);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_POSTFIELDS, $body_data_json);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function curlWithoutBody($url,$header,$method){
        $curl = curl_init($this->base_url.$url);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION, 1);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }

    public function storeLog($url,$header,$body_data,$response){
        $log_data=["url"=>$this->base_url.$url,"header"=>$header,"body"=> $body_data,"api response"=>json_decode($response)];
        return Log::channel('bkash')->info($log_data);
    }

    public function grant()
    {
        $header = array(
                'Content-Type:application/json',
                'username:'.env('BKASH_CHECKOUT_USER_NAME'),
                'password:'.env('BKASH_CHECKOUT_PASSWORD')
                );
        $header_data_json=json_encode($header);

        $body_data = array('app_key'=> env('BKASH_CHECKOUT_APP_KEY'), 'app_secret'=>env('BKASH_CHECKOUT_APP_SECRET'));
        $body_data_json=json_encode($body_data);

        $response = $this->curlWithBody('/checkout/token/grant',$header,'POST',$body_data_json);

        $token = json_decode($response)->id_token;
        
        $this->storeLog('/checkout/token/grant',$header,$body_data,$response);

        return $token;
    }

    public function pay(Request $request)
    {
        $amount = 100;
        Session::put('payment_amount', $amount);
         
        $token = $this->grant();
        Session::put('bkash_token', $token);

        return view('Iframe.pay')->with([
            'amount' => $amount,
        ]);
    }

    public function create(Request $request)
    {    
        $header =$this->authHeaders();

        $body_data = array(
            'amount' => Session::get('payment_amount'),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => "Inv".Str::random(20)
        );
        $body_data_json=json_encode($body_data);

        $response = $this->curlWithBody('/checkout/payment/create',$header,'POST',$body_data_json);
        
        Session::put('paymentID', json_decode($response)->paymentID);

        $this->storeLog('/checkout/payment/create',$header,$body_data,$response);
        // your database operation
        return json_decode($response);
    }

    public function execute(Request $request)
    {
        $paymentID = Session::get('paymentID');

        $header =$this->authHeaders();

        $response = $this->curlWithoutBody('/checkout/payment/execute/'.$paymentID,$header,'POST');
        
        $arr = json_decode($response,true);

        if(array_key_exists("errorCode",$arr) && $arr['errorCode'] != '0000'){
            Session::put('errorMessage', $arr['errorMessage']);
        }else if(array_key_exists("message",$arr)){
            // if execute api failed to response
            sleep(1);
            $response = $this->queryIframe($paymentID);
        }
        
        Session::put('response',$response);

        $this->storeLog('/checkout/payment/execute/'.$paymentID,$header,$body_data = null,$response);
        
        // your database operation

        return json_decode($response);
    }

    public function queryIframe($paymentID){

        $header =$this->authHeaders();

        $response = $this->curlWithoutBody('/checkout/payment/query/'.$paymentID,$header,'GET');

        $this->storeLog('/checkout/payment/query/'.$paymentID,$header,$body_data = null,$response);

         return $response;
    }

    public function success(Request $request)
    {
        return view('Iframe.success')->with([
            'response' => Session::get('response')
        ]);
    }
    
    public function fail(Request $request)
    {
        return view('Iframe.fail')->with([
            'errorMessage' => Session::get('errorMessage')
        ]);
    }
    
    public function query(Request $request){
        return view('Iframe.query-payment');
    }

    public function queryPayment(Request $request){
        $paymentID = $request->paymentID;
        
        $token = $this->grant();
        Session::put('bkash_token', $token);

        $header =$this->authHeaders();

        $response = $this->curlWithoutBody('/checkout/payment/query/'.$paymentID,$header,'GET');

        $this->storeLog('/checkout/payment/query/'.$paymentID,$header,$body_data = null,$response);

         return view('Iframe.query-payment')->with([
            'response' => $response,
        ]);
    }

    public function search(Request $request){
        return view('Iframe.search-transaction');
    }

    public function searchTransaction(Request $request){
        $trxID = $request->trxID;

        $token = $this->grant();
        Session::put('bkash_token', $token);

        $header =$this->authHeaders();

        $response = $this->curlWithoutBody('/checkout/payment/search/'.$trxID,$header,'GET');
            
        $this->storeLog('/checkout/payment/search/'.$trxID,$header,$body_data = null,$response);

        return view('Iframe.search-transaction')->with([
            'response' => $response,
        ]);
    }

    public function getRefund(Request $request)
    {
        return view('Iframe.refund');
    }

    public function refund(Request $request)
    {
        $token = $this->grant();
        Session::put('bkash_token', $token);

        $header =$this->authHeaders();

        $body_data = array(
            'paymentID' => $request->paymentID,
            'amount' => $request->amount,
            'trxID' => $request->trxID,
            'sku' => 'sku',
            'reason' => 'Quality issue'
        );
     
        $body_data_json=json_encode($body_data);

        $response = $this->curlWithBody('/checkout/payment/refund',$header,'POST',$body_data_json);

        $arr = json_decode($response,true);

        $this->storeLog('/checkout/payment/refund',$header,$body_data,$response);

        if(array_key_exists("message",$arr)){
            // if refund api failed to response
            sleep(1);
            $response = $this-> refundStatusIframe($request->paymentID,$request->trxID);
        }
        
        // your database operation
        return view('Iframe.refund')->with([
            'response' => $response,
        ]);
    }

    public function refundStatusIframe($paymentID,$trxID)
    {      
        $header =$this->authHeaders();

        $body_data = array(
            'paymentID' => $paymentID,
            'trxID' => $trxID,
        );
        $body_data_json = json_encode($body_data);

        $response = $this->curlWithBody('/checkout/payment/refund',$header,'POST',$body_data_json);
                
        $this->storeLog('/checkout/payment/refund-status',$header,$body_data,$response);
        
        return $response;
    }

    public function getRefundStatus(Request $request)
    {
        return view('Iframe.refund-status');
    }
    
    public function refundStatus(Request $request)
    {       
         $token = $this->grant();
        Session::put('bkash_token', $token);
        
        $header =$this->authHeaders();

        $body_data = array(
            'paymentID' => $request->paymentID,
            'trxID' => $request->trxID,
        );
        $body_data_json = json_encode($body_data);

        $response = $this->curlWithBody('/checkout/payment/refund',$header,'POST',$body_data_json);
                
        $this->storeLog('/checkout/payment/refund-status',$header,$body_data,$response);
        

        return view('Iframe.refund-status')->with([
            'response' => $response,
        ]);
    }

}
