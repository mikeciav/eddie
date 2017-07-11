<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

date_default_timezone_set('America/New_York');

$eddie = new Eddie("GDAX");

$now = date(DATE_ATOM, time());
$limit_time = date(DATE_ATOM, time() - (MACD_CROSSOVER_CANDLE_WIDTH*2));

$candles = $this->ex->getCandles($limit_time, $now, MACD_CROSSOVER_CANDLE_WIDTH);
$low = $candles[0][1];
$high = $candles[0][2];

$log = new Logger("/log/log.csv");

//Input structure: {side}|{first target amount}:{first target price}|{second target amount}:{second target price}
$input = file_get_contents("/data/targets");
$input = explode('|', $input);
if(count($input) != 3){
	echo "\nExiting target_check - Wrong number of parameters supplied";
	exit();
}
$side = $input[0];
$first = explode(':', $input[1]);
if(count($first) == 2){
	if(($side == "buy" && $low < $first[1]) || ($side == "sell" && $high > $first[1])){
		$log->logTransaction($side, $first[0], $first[1], true);
		$first[2] = "MET";
	}
}
$second = explode(':', $input[1]);
if(count($second) == 2){
	if(($side == "buy" && $low < $second[1]) || ($side == "sell" && $high > $second[1])){
		$log->logTransaction($side, $second[0], $second[1], true);
		$second[2] = "MET";
	}
}
$input[1] = implode(':', $first);
$input[2] = implode(':', $second);
$output = implode('|', $input);
file_put_contents("/data/targets", $output);


?>