<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

require_once PROJ_ROOT . "/strategies/StrategyInterface.php";
require_once PROJ_ROOT . "/strategies/class.MACD.php";
require_once PROJ_ROOT . "/strategies/class.EMA.php";

date_default_timezone_set('America/New_York');

sleep(60); //A small offset to allow the exchange to generate the candle for the last period

echo "====================================\nRun DateTime: " . date(DATE_ATOM, time()) . "\n====================================\n";

$execute_order_flag = ($argc > 1 && $argv[1] == "true") ? true : false;
$mid_candle = false;

$ret = mainProc($execute_order_flag, $mid_candle);
if(!empty($ret)){
	echo $ret;
}
else{
	//If we get here, we know an order has been placed.
	//We can re-evaluate the position mid-candle for more accuracy
	echo "\n----Sleeping...\n";
	sleep(MACD_CROSSOVER_CANDLE_WIDTH/2);
	echo "\n----Checking for mid-candle swap\n";
	$mid_candle = true;
	$ret = mainProc($execute_order_flag, $mid_candle);
	if(!empty($ret)){
		echo $ret;
	}
}


function mainProc($execute_order_flag, $mid_candle){
	$eddie = new Eddie("GDAX");
	$calc = new Calculator;

	//Replace this class with whichever strategy you would like to use
	$strategy = new MACDStrategy($eddie, $mid_candle);
	$action = $strategy->evaluate();

	if($action == Strategy::DO_NOTHING){
		return "Exiting - Strategy says to do nothing.\n";
	}

	$accounts = $eddie->getAccounts();

	echo "\n";

	$side = "";
	$size = 0;
	if($action == Strategy::BUY_STRONG || $action == Strategy::BUY_WEAK){
		//Buy
		$side = "buy";
		if($accounts["USD"]->balance  < 0.06){ //Minimum transaction = 6 cents
			return "Exiting - No funds available.\n";
		}
		$size = $accounts["USD"]->balance;
	}

	else{ //$action = Strategy::SELL_STRONG || $action == SELL_WEAK
		//Sell
		$side = "sell";
		if($accounts["ETH"]->balance < 0.01){ //Minimum transaction = 0.01 ETH
			return "Exiting - No funds available.\n";
		}
		$size = $accounts["ETH"]->balance;
	}

	$price = $eddie->placeOrder($side, $size, $execute_order_flag);
	if($price < 0){
		return "Exiting - failed to fulfill an order in a reasonable time\n";
	}

	//Log transaction
	if($side == "buy"){
		$size = $size/$price;
	}
	$log = new Logger("/log/log.csv");
	$log->logTransaction($side, $size, $price);
}

?>