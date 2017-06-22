<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

require_once PROJ_ROOT . "/strategies/StrategyInterface.php";
require_once PROJ_ROOT . "/strategies/class.MACD.php";
require_once PROJ_ROOT . "/strategies/class.EMA.php";

date_default_timezone_set('America/New_York');

$execute_order_flag = ($argc > 1 && $argv[1] == "true") ? true : false;
$mid_candle = false;

mainProc($execute_order_flag, $mid_candle);
//If this returns, we know an order has been placed.
//We can re-evaluate the position mid-candle for more accuracy
sleep(5);
$mid_candle = true;
mainProc($execute_order_flag, $mid_candle);


function mainProc($execute_order_flag, $mid_candle){
	$eddie = new Eddie("GDAX");
	$calc = new Calculator;

	//Replace this class with whichever strategy you would like to use
	$strategy = new MACDStrategy($eddie, $mid_candle);
	$action = $strategy->evaluate();

	switch($action){
		case Strategy::DO_NOTHING:
			echo "\nDo nothing\n";
		break;
		case Strategy::BUY_STRONG:
			echo "\nBuy\n";
		break;
		case Strategy::SELL_STRONG:
		default:
			echo "\nSell\n";
		break;
	}

	$accounts = $eddie->getAccounts();
	var_dump($accounts);

	echo "\n";
}

?>