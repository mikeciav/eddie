<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

require_once PROJ_ROOT . "/strategies/StrategyInterface.php";
require_once PROJ_ROOT . "/strategies/class.MACD.php";
require_once PROJ_ROOT . "/strategies/class.EMA.php";

sleep(5); //A small offset to allow the exchange to generate the candle for the last period

date_default_timezone_set('America/New_York');

$eddie = new Eddie("GDAX");
$calc = new Calculator;

//Replace this class with whichever strategy you would like to use
$strategy = new MACDStrategy($eddie);
$action = $strategy->evaluate();

if($action == Strategy::DO_NOTHING){
	exit(0);
}

$accounts = $eddie->getAccounts();

echo "\n";

$side = "";
$size = 0;
if($action == Strategy::BUY_STRONG || $action == Strategy::BUY_WEAK){
	//Buy
	$side = "buy";
	if($accounts["USD"]->balance  < 0.06){ //Minimum transaction = 6 cents
		echo "Exiting - No funds available.\n";
		exit(0);
	}
	$size = $accounts["USD"]->balance;
}

else{ //$action = Strategy::SELL_STRONG || $action == SELL_WEAK
	//Sell
	$side = "sell";
	if($accounts["ETH"]->balance < 0.01){ //Minimum transaction = 0.01 ETH
		echo "Exiting - No funds available.\n";
		exit(0);
	}
	$size = $accounts["ETH"]->balance;
}

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