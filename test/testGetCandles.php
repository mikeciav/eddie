<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

require_once PROJ_ROOT . "/strategies/StrategyInterface.php";
require_once PROJ_ROOT . "/strategies/class.MACD.php";

date_default_timezone_set('America/New_York');

	$eddie = new Eddie("GDAX");

	//Longest MACD period + the MACD signal period to reach back + 2 to reach back for MACDR1, all times 2 to get the SMA seeds for the EMAs
	$candle_count = (LONG_TERM_MACD_PERIOD + MACD_SIGNAL_PERIOD + 2)*2;
	$inc = 0;

	for($wait_count = 0; $wait_count < 40; $wait_count += 1){
		$now = date(DATE_ATOM, time());
		$limit_time = date(DATE_ATOM, time() - (MACD_CROSSOVER_CANDLE_WIDTH*$candle_count));

		//Extract closing prices
		//Retry if not enough candles were returned (thanks GDAX)
		$closes;
		do{
			$closes = array();
			$candles = $eddie->getCandles($limit_time, $now, MACD_CROSSOVER_CANDLE_WIDTH);
			if(is_array($candles)){
				for($i=$inc;$i<$candle_count+$inc;$i+=1){
					if(isset($candles[$i])){
						$closes[] = $candles[$i][4];
					}
				}
			}
			sleep(1);
		} while(count($closes) < $candle_count);

		var_dump($closes);
		echo $now;

		sleep(15);

	}

?>