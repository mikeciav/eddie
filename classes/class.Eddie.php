<?php
//Eddie is a swing-trading Ether trader built to take advantage of large dips and conservatively maximize long-term profits

require_once PROJ_ROOT . "/private/defines.php";
require_once PROJ_ROOT . "/classes/class.CoinbaseExchange.php";

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

	    $url = "https://api.pro.coinbase.com" . $url;

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
	    var_dump($result);

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
		$raw_accs = $this->callAPI("GET", "/accounts");
		//This API call likes to fail, so try it again if it does...
		if(empty($raw_accs)){
			sleep(5);
			$raw_accs = $this->callAPI("GET", "/accounts");
		}
		$accounts = array();
		foreach($raw_accs as $account){
			$accounts[$account->currency] = $account;
		}
		return $accounts;
	}

	public function getOrders(){
		return $this->callAPI("GET", "/orders");
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

	//This function will repeatedly match the best bid or ask on the book until an order is fulfilled
	//Because oldest orders are honored first, this function only cancels and replaces an order if the price changes
	// $side = buy or sell
	// $size = amount of ETH to sell, or amount of USD to buy ETH with (conversion will happen based on best bid price)
	//Returns - the bid or ask price the order was honored at
	public function placeOrder($side, $size, $execute_order_flag, $take_some_profit_flag){
		$log_offer = $current_offer = -1;
		$current_size = $size;
		$continue = true;
		$wait_count = 0;
		do{
			$ticker = $this->getTicker();
			if($side == "buy"){
				if($current_offer != $ticker->ask - 0.01){
					$current_offer = $ticker->ask - 0.01;
					if($log_offer < 0){
						$log_offer = $current_offer;
					}
					if($execute_order_flag){
						$this->cancelAllOrders();
						$current_size = $this->formatNumber($size, 2);
						echo "\nPlacing order to buy " . $current_size . " ETH at $" . $current_offer;
						$this->buyETH($current_size);
					}
				}
				$accounts = $this->getAccounts();
				$continue = ($accounts["USD"]->balance > 0.05);
			}
			else{
				if($current_offer != $ticker->bid + 0.01){
					$current_offer = $ticker->bid + 0.01;
					if($log_offer < 0){
						$log_offer = $current_offer;
					}
					if($execute_order_flag){
						$this->cancelAllOrders();
						$current_size = $this->formatNumber($size, 4);
						echo "\nPlacing order to sell " . $current_size . " ETH at $" . $current_offer;
						$this->sellETH($current_size);
					}
				}
				$accounts = $this->getAccounts();
				$continue = ($accounts["ETH"]->balance >= 0.001);
			}
			echo ".";
			sleep(1); //To avoid spamming the exchange and getting banned
			$wait_count+=1;
		} while($execute_order_flag && $continue && $wait_count < MAX_WAIT_COUNT);

		//If order was placed and we have profit goals, set a limit buy/sell at each profit goal
			if(!$continue || !$execute_order_flag){
				if($take_some_profit_flag){
					$accounts = $this->getAccounts();
					if($side == "buy"){
						$target_side = "sell";
						$target1 = $this->formatNumber($current_offer*TAKE_PROFIT_AT_LONG, 2);
						$target2 = $this->formatNumber($current_offer*TAKE_PROFIT_AT_LONG*TAKE_PROFIT_AT_LONG, 2);
						if($execute_order_flag){
							$size = $accounts["ETH"]->balance;
							$size1 = $this->formatNumber($size*TAKE_PROFIT_PERCENTAGE_LONG, 4);
							$size2 = $this->formatNumber($size*TAKE_PROFIT_PERCENTAGE_2, 4);
							$this->sellEthLimit($size1, $target1);
							$this->sellEthLimit($size2, $target2);
						}
						else{
							$size1 = 0;
							$size2 = 0;
						}
						file_put_contents(PROJ_ROOT . "/data/targets", "{$target_side}|{$size1}:{$target1}|{$size2}:{$target2}");
					}
					else{
						$target_side = "buy";
						$target1 = $this->formatNumber($current_offer*TAKE_PROFIT_AT_SHORT, 2);
						$target2 = $this->formatNumber($current_offer*TAKE_PROFIT_AT_SHORT*TAKE_PROFIT_AT_SHORT, 2);
						if($execute_order_flag){
							$size = ($accounts["USD"]->balance/$log_offer);
							$size1 = $this->formatNumber($size*TAKE_PROFIT_PERCENTAGE_SHORT, 4);
							$size2 = $this->formatNumber($size*TAKE_PROFIT_PERCENTAGE_2, 4);
							$this->buyEthLimit($size1, $target1);
							$this->buyEthLimit($size2, $target2);
						}
						else{
							$size1 = 0;
							$size2 = 0;
						}
						file_put_contents(PROJ_ROOT . "/data/targets", "{$target_side}|{$size1}:{$target1}|{$size2}:{$target2}");
					}
				}
			}
			else{
				$this->cancelAllOrders();
				$current_offer = $log_offer = -1;
			}
		return $log_offer;
	}

	private function formatNumber($number, $sig_figs){
		$number = (double)$number;
		$factor = pow(10,$sig_figs);
		$number = floor($number*$factor) / $factor;
		$number = number_format($number, $sig_figs, '.', '');
		return $number;
	}
}
?>