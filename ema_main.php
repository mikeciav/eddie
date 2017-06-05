<?php
require_once "classes/class.Eddie.php";
require_once "classes/class.Calculator.php";
require_once "classes/class.Logger.php";

date_default_timezone_set('America/New_York');

$eddie = new Eddie("GDAX");
$calc = new Calculator;

$now = date(DATE_ATOM, time());
$limit_time = date(DATE_ATOM, time() - (EMA_CROSSOVER_CANDLE_WIDTH*LONG_TERM_EMA_PERIOD));

$candles = $eddie->getCandles($limit_time, $now, EMA_CROSSOVER_CANDLE_WIDTH);

//Extract closing prices
//Omit current candle from these calculations as it is too volatile
$closes1 = array();
for($i=1;$i<SHORT_TERM_EMA_PERIOD;$i+=1){
	$closes1[] = $candles[$i][4];
}
$closes2 = array();
for($i=1;$i<LONG_TERM_EMA_PERIOD;$i+=1){
	$closes2[] = $candles[$i][4];
}

//Calculate SMA and EMA
$ema_s = $calc->EMA($closes1);
$ema_l = $calc->EMA($closes2);
if($ema_s < 0.01 || $ema_l < 0.01){ //They would never actually be this low unless ETH completely tanked
	echo "Exiting - Error in EMA calculation\n";
	exit(0);
}
else{
	echo "Short term EMA: " . $ema_s . "\n";
	echo "Long term EMA: " . $ema_l . "\n";
}

//Only swap if there is enough separation between short and long term EMA
if(abs($ema_s - $ema_l) < MIN_SEPARATION){
	echo "Exiting - Not enough separation between short and long term EMA.\n
			Required: " . MIN_SEPARATION . "\n
			Actual: ". abs($ema_s - $ema_l) . "\n";
	exit(0);
}

$accounts = $eddie->getAccounts();

echo "\n";

$side = "";
$size = 0;
if($ema_s > $ema_l){
	//Buy
	echo "EMA-S > EMA-L: Buying procedure triggered\n";
	$side = "buy";
	$size = $accounts["USD"]->balance;
	if($accounts["USD"]->balance  < 0.01){ //Minimum transaction = 1 cent
		echo "Exiting - No funds available.\n";
		exit(0);
	}
}
else{
	//Sell
	echo "EMA-S < EMA-L: Selling procedure triggered\n";
	$side = "sell";
	$size = $accounts["ETH"]->balance;
	if($accounts["ETH"]->balance < 0.01){ //Minimum transaction = 0.01 ETH
		echo "Exiting - No funds available.\n";
		exit(0);
	}
}
$price = $eddie->placeOrder($side, $size);
if($price < 0){
	echo "Exiting - failed to fulfill an order in " . MAX_WAIT_TIME . " seconds\n";
	exit(0);
}

//Log transaction
if($side == "buy"){
	$size = $size/$price;
}
$log = new Logger("log/log.csv");
$log->logTransaction($side, $size, $price);

?>