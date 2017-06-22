<?php
require_once PROJ_ROOT . "/private/defines.php";

require_once PROJ_ROOT . "/strategies/StrategyInterface.php";

require_once PROJ_ROOT . "/classes/class.Calculator.php";
require_once PROJ_ROOT . "/classes/class.Eddie.php";

class EMAStrategy implements Strategy{

	public function __construct(&$exchange){
		$this->ex = $exchange;

		$candle_count = LONG_TERM_EMA_PERIOD * 2;
		$now = date(DATE_ATOM, time());
		$limit_time = date(DATE_ATOM, time() - (EMA_CROSSOVER_CANDLE_WIDTH*$candle_count));

		$candles = $this->ex->getCandles($limit_time, $now, EMA_CROSSOVER_CANDLE_WIDTH);

		//Extract closing prices
		//Omit current candle from these calculations as it is too volatile
		$this->closes1 = array();
		for($i=1;$i<$candle_count;$i+=1){
			$this->closes1[] = $candles[$i][4];
		}
		$this->closes2 = array();
		for($i=1;$i<$candle_count;$i+=1){
			$this->closes2[] = $candles[$i][4];
		}
	}

	public function evaluate(){
		$calc = new Calculator;

		//Calculate SMA and EMA
		$ema_s = $calc->EMA($this->closes1, SHORT_TERM_EMA_PERIOD);
		$ema_l = $calc->EMA($this->closes2, LONG_TERM_EMA_PERIOD);
		if($ema_s < 0.01 || $ema_l < 0.01){ //They would never actually be this low unless ETH completely tanked
			echo "Exiting - Error in EMA calculation\n";
			return Strategy::DO_NOTHING;
		}
		else{
			echo "Short term EMA: " . $ema_s . "\n";
			echo "Long term EMA: " . $ema_l . "\n";
		}

		//Only swap if there is enough separation between short and long term EMA
		if(abs($ema_s - $ema_l) < MIN_SEPARATION){
			echo "Exiting - Not enough separation between short and long term EMA.\n
					Required: " . MIN_SEPARATION . "\n
					Actual: ". abs($ema_s - $ema_l) . "\n";
			return Strategy::DO_NOTHING;
		}

		if($ema_s > $ema_l){
			echo "EMA-S > EMA-L: Buying procedure triggered\n";
			Strategy::BUY_STRONG;
		}
		else{
			echo "EMA-S < EMA-L: Selling procedure triggered\n";
			Strategy::SELL_STRONG;
		}
	}

}
?>