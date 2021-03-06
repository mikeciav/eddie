<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

require_once PROJ_ROOT . "/strategies/StrategyInterface.php";
require_once PROJ_ROOT . "/strategies/class.MACD.php";
require_once PROJ_ROOT . "/strategies/class.EMA.php";

date_default_timezone_set('America/New_York');

sleep(5); //initial delay to potentially be a little more random

echo "\n====================================\nRun DateTime: " . date(DATE_ATOM, time()) . "\n====================================\n";

$execute_order_flag = ($argc > 1 && $argv[1] == "true") ? true : false;
$use_first_candle = true;

$ret = mainProc($execute_order_flag, $use_first_candle);
while(empty($ret)){
	//If we get here, we know an order has been placed.
	//We can re-evaluate the position mid-candle for more accuracy
	//Keep re-evaluating until an order is not filled
	echo "\n----Recalculating after order filled----\n";
	$ret = mainProc($execute_order_flag, $use_first_candle);
}
//An order was not placed in mainProc(), so print out the reason why
echo $ret;

function mainProc($execute_order_flag, $use_first_candle){
	$take_some_profit = true;

	$eddie = new Eddie("GDAX");
	$calc = new Calculator;

	//Replace this class with whichever strategy you would like to use
	$strategy = new MACDStrategy($eddie, $use_first_candle);
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
		$size = $accounts["USD"]->balance;
	}
	else{ //$action = Strategy::SELL_STRONG || $action == SELL_WEAK
		//Sell
		$side = "sell";
		$size = $accounts["ETH"]->balance;
	}
	$last_position = file_get_contents(PROJ_ROOT . "/data/last_position");
	if($side == $last_position){
		return "Exiting - No position change.\n";
	}

	$price = $eddie->placeOrder($side, $size, $execute_order_flag, $take_some_profit);
	if($price < 0){
		return "Exiting - failed to fulfill an order in a reasonable time\n";
	}

	//Log transaction
	if($side == "buy"){
		$size = $size/$price;
	}
	$log = new Logger("/log/log.csv");
	$log->logTransaction($side, $size, $price, false);

	file_put_contents(PROJ_ROOT . "/data/last_position", $side);
}

?>