<?php
//Eddie is a swing-trading Ether trader built to take advantage of large dips and conservatively maximize long-term profits

require_once "private/defines.php";
require_once "classes/class.CoinbaseExchange.php";

class Eddie{

	public function __construct($exchange){
		switch ($exchange){
			case "Poloniex":
				echo "Poloniex API is currently not supported";
				exit(0);
			break;
			case "GDAX":
				$this->cb = new CoinbaseExchange(API_KEY, API_SECRET, API_PASSPHRASE);
			break;
		}
	}
	
	// Method: POST, PUT, GET etc
	// Data: array("param" => "value") ==> index.php?param=value
	private function CallAPI($method, $url, $data = false)
	{
	    $curl = curl_init();

	    $timestamp = time();
	    $data_enc = "";
	    if($data && $method == "POST"){
			$data_enc = json_encode($data);
	    }
	    $signature = $this->cb->signature($url, $data_enc, $timestamp, $method);

	    $url = "https://api.gdax.com" . $url;

	    $header_params = array(
				"CB-ACCESS-KEY: " . API_KEY,
				"CB-ACCESS-TIMESTAMP: " . $timestamp,
				"CB-ACCESS-PASSPHRASE: " . API_PASSPHRASE,
				"CB-ACCESS-SIGN: " . $signature,
				"Content-Type: application/json",
				"User-Agent: mciav5"
		);

	    switch ($method)
	    {
			case "GET":
				if($data){
					$url .= "?" . http_build_query($data);
				}
			break;
	        case "POST":
	            curl_setopt($curl, CURLOPT_POST, 1);
	            curl_setopt($curl, CURLOPT_POSTFIELDS, $data_enc);
	        break;
	        case "PUT":
	            curl_setopt($curl, CURLOPT_PUT, 1);
	        break;
	        case "DELETE":
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
	        break;

	    }

	    curl_setopt($curl, CURLOPT_HTTPHEADER, $header_params);

	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

	    //curl_setopt($curl, CURLOPT_VERBOSE, true);

	    $result = curl_exec($curl);

	    curl_close($curl);

	    return json_decode($result);
	}

	public function getCandles($startTime, $endTime, $granularity){
		$params = array(
			'start' => $startTime,
			'end' => $endTime,
			'granularity' => $granularity
		);
		return $this->CallAPI("GET", "/products/ETH-USD/candles", $params);
	}

	public function getTicker(){
		return $this->CallAPI("GET", "/products/ETH-USD/ticker");
	}

	public function getAccounts(){
		return $this->callAPI("GET", "/accounts");
	}

	public function getOrders(){
		return $this->callAPI("GET", "/accounts");
	}

	public function getOrder($order_id){
		$params = array('order-id' => $order_id);
		return $this->callAPI("GET", "/order", $params);
	}

	private function placeMarketOrder($side, $product, $amount){
		$params = array(
			'type' => "market",
			'side' => $side,
			'product_id' => $product
		);
		if($side == "buy")
			$params["funds"] = $amount;
		else //sell
			$params["size"] = $amount;

		return $this->callAPI("POST", "/orders", $params);
	}

	private function placeLimitOrder($side, $product, $amount, $price){
		$params = array(
			'type' => "limit",
			'side' => $side,
			'product_id' => $product,
			'price' => $price,
			'size' => $amount,
			'post_only' => "true"
		);

		return $this->callAPI("POST", "/orders", $params);
	}

	public function buyEth($funds){
		return $this->placeMarketOrder("buy", "ETH-USD", $funds);
	}

	public function sellEth($funds){
		return $this->placeMarketOrder("sell", "ETH-USD", $funds);
	}

	public function buyEthLimit($amount, $price){
		return $this->placeLimitOrder("buy", "ETH-USD", $amount, $price);
	}

	public function sellEthLimit($amount, $price){
		return $this->placeLimitOrder("sell", "ETH-USD", $amount, $price);
	}

	public function cancelAllOrders(){
		return $this->callAPI("DELETE", "/orders");
	}
}
?>