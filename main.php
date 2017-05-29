<?php
require_once "classes/class.Eddie.php";
require_once "classes/class.Calculator.php";
require_once "classes/class.Logger.php";

$eddie = new Eddie("GDAX");
$calc = new Calculator;

DEFINE("SHORT_TERM_EMA_PEDIOD", 20);
DEFINE("LONG_TERM_EMA_PERIOD", 42);

$candle_width = 900; //seconds
$now = date(DATE_ATOM, time());
$time1 = date(DATE_ATOM, time() - ($candle_width*SHORT_TERM_EMA_PEDIOD));
$time2 = date(DATE_ATOM, time() - ($candle_width*LONG_TERM_EMA_PERIOD));

$candles1 = $eddie->getCandles($time1, $now, $candle_width);
$candles2 = $eddie->getCandles($time2, $now, $candle_width);

//Extract closing prices
$closes1 = array();
foreach($candles1 as $candle){
	$closes1[] = $candle[4];
}

$closes2 = array();
foreach($candles2 as $candle){
	$closes2[] = $candle[4];
}

//Calculate SMA and EMA
$ema_s = $calc->EMA($closes1);
$ema_l = $calc->EMA($closes2);
echo "Short term EMA: " . $ema_s . "\n";
echo "Long term EMA: " . $ema_l . "\n";

$raw_accounts = $eddie->getAccounts();

$accounts = array();
$eth_account = null;
$usd_account = null;
foreach($raw_accounts as $account){
	$accounts[$account->currency] = $account;
}

//Only swap if there is enough separation between short and long term EMA
if(abs($ema_s - $ema_l) < 0.02) echo "Warning: Very little separation\n";//exit(0);
echo "\n";

$ret = $eddie->sellETHLimit(0.02, 300);
var_dump($ret);

$side = "";
$size = 0;
if($ema_s > $ema_l){
	//Buy
	$side = "buy";
	$size = $accounts["USD"]->balance;
	echo "EMA-S > EMA-L: Buying procedure triggered\n";
	$ret = $eddie->cancelAllOrders();
	var_dump($ret);
	if($accounts["USD"]->balance  < 0.01){
		echo "Exiting: No funds available\n";
		//exit(0);
	}
	$eddie->cancelAllOrders();
	//$eddie->buyETH($accounts["USD"]->balance);

}
else{
	//Sell
	$side = "sell";
	$size = $accounts["ETH"]->balance;
	echo "EMA-S < EMA-L: Selling procedure triggered\n";
	$ret = $eddie->cancelAllOrders();
	var_dump($ret);
	if($accounts["ETH"]->balance < 0.001){
		echo "Exiting - No funds available\n";
		//exit(0);
	}
	//$eddie->sellETH($accounts["ETH"]->balance);
}

$log = new Logger("log/log.csv");
//$log->logTransaction($side, $size, )

?>