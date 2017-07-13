<?php
require_once PROJ_ROOT . "/private/defines.php";

require_once PROJ_ROOT . "/strategies/StrategyInterface.php";

require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Eddie.php";

class MACDStrategy implements Strategy{

	public function __construct(&$exchange, $mid_candle){
		$this->ex = $exchange;
		$inc = $mid_candle ? 0 : 1;
		//Longest MACD period + the MACD signal period to reach back + 2 to reach back for MACDR1, all times 2 to get the SMA seeds for the EMAs
		$candle_count = (LONG_TERM_MACD_PERIOD + MACD_SIGNAL_PERIOD + 2)*2;

		$now = date(DATE_ATOM, time());
		$limit_time = date(DATE_ATOM, time() - (MACD_CROSSOVER_CANDLE_WIDTH*$candle_count));

		//Extract closing prices
		//Retry if not enough candles were returned (thanks GDAX)
		$closes;
		do{
			$closes = array();
			$candles = $this->ex->getCandles($limit_time, $now, MACD_CROSSOVER_CANDLE_WIDTH);
			if(is_array($candles)){
				for($i=$inc;$i<$candle_count+$inc;$i+=1){
					if(isset($candles[$i])){
						$closes[] = $candles[$i][4];
					}
				}
			}
			sleep(1);
		} while(count($closes) < $candle_count);

		$this->closes = $closes;
	}

	public function evaluate(){
		$calc = new Calculator;

		//Calculate MACD Crossover
		$crossover_value = $calc->MACDWithSignal($this->closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD);
		//$macdr1 = $calc->MACDR1($this->closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD, MACD_WAIT_PERIOD);
		//$macdr2 = $calc->MACDR2($this->closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD, MACD_MIN_AMPLITUDE*$this->closes[0]);
		$macdr1 = $macdr2 = true;
		echo "MACD divergence: " . $crossover_value . "\n";
		//echo "MACDR1 value: " . $macdr1 . "\n";
		//echo "MACDR2 value: " . $macdr2 . "\n";

		//Safeguards in times of stability
		if(!$macdr1 && !$macdr2){
			echo "Exiting - MACDR1 and MACDR2 are false\n";
			return Strategy::DO_NOTHING;
		}

		if($crossover_value >= 0.0){
			echo "MACD > Signal: Buying procedure triggered\n";
			return Strategy::BUY_STRONG;
		}
		else{
			echo "MACD < Signal: Selling procedure triggered\n";
			return Strategy::SELL_STRONG;
		}
	}

}
?>