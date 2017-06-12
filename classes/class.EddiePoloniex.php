<?php
// FINAL TESTED CODE - Created by Compcentral
       
// NOTE: currency pairs are reverse of what most exchanges use...
//       For instance, instead of XPM_BTC, use BTC_XPM

class poloniex {
    protected $api_key;
    protected $api_secret;
    protected $trading_url = "https://poloniex.com/tradingApi";
    protected $public_url = "https://poloniex.com/public";
   
    public function __construct($api_key, $api_secret) {
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;
    }
           
    private function callAPI(array $req = array()) {
        // API settings
        $key = $this->api_key;
        $secret = $this->api_secret;
 
        // generate a nonce to avoid problems with 32bit systems
        $mt = explode(' ', microtime());
        $req['nonce'] = $mt[1].substr($mt[0], 2, 6);
 
        // generate the POST data string
        $post_data = http_build_query($req, '', '&');
        $sign = hash_hmac('sha512', $post_data, $secret);
 
        // generate the extra headers
        $headers = array(
                'Key: '.$key,
                'Sign: '.$sign,
        );

        // curl handle (initialize if required)
        static $ch = null;
        if (is_null($ch)) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_USERAGENT,
                        'Mozilla/4.0 (compatible; Poloniex PHP bot; '.php_uname('a').'; PHP/'.phpversion().')'
                );
        }
        curl_setopt($ch, CURLOPT_URL, $this->trading_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        // run the query
        $res = curl_exec($ch);

        if ($res === false) throw new Exception('Curl error: '.curl_error($ch));
        //echo $res;
        $dec = json_decode($res, true);
        if (!$dec){
                //throw new Exception('Invalid data: '.$res);
                return false;
        }else{
                return $dec;
        }
    }
   
    protected function retrieveJSON($URL) {
        $opts = array('http' =>
                array(
                        'method'  => 'GET',
                        'timeout' => 10
                )
        );
        $context = stream_context_create($opts);
        $feed = file_get_contents($URL, false, $context);
        $json = json_decode($feed, true);
        return $json;
    }
   
    public function getAccounts() {
        $raw_accs = $this->callAPI(
                array(
                        'command' => 'returnBalances'
                )
        );

        return (array)$ret;

    }
   
    public function getOpenOrders($pair = "USDT_ETH") {               
        return $this->callAPI(
                array(
                        'command' => 'returnOpenOrders',
                        'currencyPair' => strtoupper($pair)
                )
        );
    }

    private function placeMarketOrder($side, $product, $amount, $rate='-1'){
        $params = array(
                'command' => $side,
                'currencyPair' => $pair,
                'rate' => $rate,
                'amount' => $amount,
                'fillOrKill' => "true"
        );

        return $this->callAPI($params);
    }

    private function placeLimitOrder($side, $product, $amount, $rate){
        $params = array(
                'command' => $side,
                'currencyPair' => $pair,
                'rate' => $rate,
                'amount' => $amount,
                'postOnly' => "true"
        );

        return $this->callAPI($params);
    }

    public function buyEth($funds){
        return $this->placeMarketOrder("buy", "USDT_ETH", $funds);
    }

    public function sellEth($funds){
        return $this->placeMarketOrder("sell", "USDT_ETH", $funds);
    }

    public function buyEthLimit($amount, $price){
        return $this->placeLimitOrder("buy", "USDT_ETH", $amount, $price);
    }

    public function sellEthLimit($amount, $price){
        return $this->placeLimitOrder("sell", "USDT_ETH", $amount, $price);
    }

    public function getOrders($pair = "USDT_ETH") {
        return $this->callAPI(
            array(
                'command' => 'returnOpenOrders',    
                'currencyPair' => strtoupper($pair)
            )
        );
    }
   
    public function cancelOrder($pair = "USDT_ETH", $order_number) {
        return $this->callAPI(
            array(
                'command' => 'cancelOrder',    
                'currencyPair' => strtoupper($pair),
                'orderNumber' => $order_number
            )
        );
    }

    public function cancelAllOrders($pair = "USDT_ETH"){
        $orders = $this->getOrders($pair);
        foreach($orders as $o){
            $this->cancelOrder($pair, $o->orderNumber);
        }
    }

    public function getTicker($pair = "USDT_ETH") {
        $pair = strtoupper($pair);
        $prices = $this->retrieveJSON($this->public_url.'?command=returnTicker');
        if($pair == "ALL"){
                return $prices;
        }else{
                $pair = strtoupper($pair);
                if(isset($prices[$pair])){
                        return $prices[$pair];
                }else{
                        return array();
                }
        }
    }

    public function getCandles($startTime, $endTime, $granularity, $pair = "USDT_ETH"){
        return $this->callAPI(
            array(
                'command' => 'returnChartData',
                'currencyPair' => $pair,
                'period' => $granularity,
                'start' => $startTime,
                'end' => $endTime
            )
        );
    }

    public function getClosingPrices($startTime, $endTime, $granularity){
        $candles = $this->getCandles($startTime, $endTime, $granularity);
        //Extract closing prices
        //Omit current candle from these calculations as it is not closed
        $closes = array();
        for($i=1;$i<LONG_TERM_MACD_PERIOD+MACD_SIGNAL_PERIOD;$i+=1){
            $closes[] = $candles[$i]->close;
        }

        return $closes;
    }

   
    public function getTradingPairs() {
        $tickers = $this->retrieveJSON($this->public_url.'?command=returnTicker');
        return array_keys($tickers);
    }
   
    public function getTotalBtcBalance() {
        $balances = $this->get_balances();
        $prices = $this->get_ticker();
       
        $tot_btc = 0;
       
        foreach($balances as $coin => $amount){
                $pair = "BTC_".strtoupper($coin);
       
                // convert coin balances to btc value
                if($amount > 0){
                        if($coin != "BTC"){
                                $tot_btc += $amount * $prices[$pair];
                        }else{
                                $tot_btc += $amount;
                        }
                }

                // process open orders as well
                if($coin != "BTC"){
                        $open_orders = $this->get_open_orders($pair);
                        foreach($open_orders as $order){
                                if($order['type'] == 'buy'){
                                        $tot_btc += $order['total'];
                                }elseif($order['type'] == 'sell'){
                                        $tot_btc += $order['amount'] * $prices[$pair];
                                }
                        }
                }
        }

        return $tot_btc;
    }
}
?>