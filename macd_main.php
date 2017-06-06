<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

sleep(5); //A small offset to allow the exchange to generate the candle for the last period

date_default_timezone_set('America/New_York');

$eddie = new Eddie("GDAX");
$calc = new Calculator;

$now = date(DATE_ATOM, time());
$limit_time = date(DATE_ATOM, time() - (MACD_CROSSOVER_CANDLE_WIDTH*(LONG_TERM_MACD_PERIOD + MACD_SIGNAL_PERIOD + 3)));

$candles = $eddie->getCandles($limit_time, $now, MACD_CROSSOVER_CANDLE_WIDTH);

//Extract closing prices
//Omit current candle from these calculations as it is too volatile
$closes = array();
for($i=1;$i<LONG_TERM_MACD_PERIOD+MACD_SIGNAL_PERIOD;$i+=1){
	$closes[] = $candles[$i][4];
}

//Calculate MACD Crossover
$crossover_value = $calc->MACDWithSignal($closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD);
$macdr1 = $calc->MACDR1($closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD, MACD_WAIT_PERIOD);
$macdr2 = $calc->MACDR2($closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD, MACD_MIN_AMPLITUDE*$closes[0]);
echo "MACD crossover value: " . $crossover_value . "\n";
echo "MACDR1 value: " . $macdr1 . "\n";
echo "MACDR2 value: " . $macdr2 . "\n";

//Safeguards in times of stability
if(!$macdr1 && !$macdr2){
	echo "Exiting - MACDR1 and MACDR2 are false\n";
	exit(0);
}

$accounts = $eddie->getAccounts();

echo "\n";

$side = "";
$size = 0;
if($crossover_value >= 0){
	//Buy
	echo "MACD > Signal: Buying procedure triggered\n";
	$side = "buy";
	if($accounts["USD"]->balance  < 0.01){ //Minimum transaction = 1 cent
		echo "Exiting - No funds available.\n";
		exit(0);
	}
	$size = $accounts["USD"]->balance;
}

else{
	//Sell
	echo "MACD < Signal: Selling procedure triggered\n";
	$side = "sell";
	if($accounts["ETH"]->balance < 0.01){ //Minimum transaction = 0.01 ETH
		echo "Exiting - No funds available.\n";
		exit(0);
	}
	$size = $accounts["ETH"]->balance;
}
echo "Side: " . $side . "\n";
echo "Size: " . $size . "\n";
$price = $eddie->placeOrder($side, $size);
if($price < 0){
	echo "Exiting - failed to fulfill an order in a reasonable time\n";
	exit(0);
}

//Log transaction
if($side == "buy"){
	$size = $size/$price;
}
$log = new Logger("log/log.csv");
$log->logTransaction($side, $size, $price);


?>