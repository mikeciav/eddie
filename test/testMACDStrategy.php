<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

require_once PROJ_ROOT . "/strategies/StrategyInterface.php";
require_once PROJ_ROOT . "/strategies/class.MACD.php";

date_default_timezone_set('America/New_York');

$execute_order_flag = ($argc > 1 && $argv[1] == "true") ? true : false;
$mid_candle = false;

mainProc($execute_order_flag, $mid_candle);
//If this returns, we know an order has been placed.
//We can re-evaluate the position mid-candle for more accuracy
$mid_candle = true;
mainProc($execute_order_flag, $mid_candle);


function mainProc($execute_order_flag, $mid_candle){
	$eddie = new Eddie("GDAX");

	//Replace this class with whichever strategy you would like to use
	$strategy = new MACDStrategy($eddie, $mid_candle);
	$action = $strategy->evaluate();
}

?>