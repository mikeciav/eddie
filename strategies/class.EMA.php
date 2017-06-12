<?php
require_once "private/defines.php";

require_once "/strategies/StrategyInterface.php";

require_once "/classes/class.Calculator.php";
require_once "/classes/class.Eddie.php";

class EMAStrategy implements Strategy{

	public function __construct(&$exchange){
		$this->ex = $exchange;

		$now = time();
		$limit_time1 = time() - (EMA_CROSSOVER_CANDLE_WIDTH*LONG_TERM_EMA_PERIOD);
		$limit_time2 = time() - (EMA_CROSSOVER_CANDLE_WIDTH*LONG_TERM_EMA_PERIOD);

		$this->closes1 = $this->ex->getCandles($limit_time1, $now, EMA_CROSSOVER_CANDLE_WIDTH);
		$this->closes2 = $this->ex->getCandles($limit_time2, $now, EMA_CROSSOVER_CANDLE_WIDTH);
	}

	public function evaluate(){
		$calc = new Calculator;

		//Calculate SMA and EMA
		$ema_s = $calc->EMA($this->closes1);
		$ema_l = $calc->EMA($this->closes2);
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