<?php
require_once "private/defines.php";

require_once "/strategies/StrategyInterface.php";

require_once "/classes/class.Calculator.php";
require_once "/classes/class.Eddie.php";

class MACDStrategy implements Strategy{

	public function __construct(&$exchange, $mid_candle){
		$this->ex = $exchange;
		$inc = $mid_candle ? 0 : 1;

		$now = date(DATE_ATOM, time());
		$limit_time = date(DATE_ATOM, time() - (MACD_CROSSOVER_CANDLE_WIDTH*(LONG_TERM_MACD_PERIOD + MACD_SIGNAL_PERIOD + 3)));

		$candles = $this->ex->getCandles($limit_time, $now, MACD_CROSSOVER_CANDLE_WIDTH);

		//Extract closing prices
		//Omit current candle from these calculations as it is too volatile
		$this->closes = array();
		for($i=$inc;$i<LONG_TERM_MACD_PERIOD+MACD_SIGNAL_PERIOD+($inc-1);$i+=1){
			$this->closes[] = $candles[$i][4];
		}
	}

	public function evaluate(){
		$calc = new Calculator;

		//Calculate MACD Crossover
		$crossover_value = $calc->MACDWithSignal($this->closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD);
		$macdr1 = $calc->MACDR1($this->closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD, MACD_WAIT_PERIOD);
		$macdr2 = $calc->MACDR2($this->closes, SHORT_TERM_MACD_PERIOD, LONG_TERM_MACD_PERIOD, MACD_SIGNAL_PERIOD, MACD_MIN_AMPLITUDE*$this->closes[0]);
		echo "MACD crossover value: " . $crossover_value . "\n";
		echo "MACDR1 value: " . $macdr1 . "\n";
		echo "MACDR2 value: " . $macdr2 . "\n";

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