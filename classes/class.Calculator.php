<?php
//Calculator class computes the SMA and EMA of a set of closing prices

class Calculator{

	public function __construct($prices=array()){
		$this->prices = $prices;
	}
	
	public function setCandles($prices){
		$this->prices = $prices;
	}

	public function SMA($closes)
	{
		return array_sum($closes) / count($closes);
	}

	//This EMA calculation uses the SMA of the set of closes as the seed
	public function EMA($closes){
		$n = count($closes);
		$previous = $this->SMA($closes);
		$multiplier = (2.0 / ($n + 1.0) );
		$EMA = 0.0;
		for($i = $n-1; $i>-1; $i-=1){
			$close = $closes[$i];
			$EMA = ($close * $multiplier) + ($previous * (1.0 - $multiplier));
			$previous = $EMA;
		}
		return $EMA;
	}

	//This EMA calculation uses the oldest closing price as the seed
	public function EMA2($closes){
		$n = count($closes);
		$multiplier = (2.0 / ($n + 1.0) );
		$previous = $closes[$n-1];
		$EMA = 0.0;
		for($i = $n-1; $i>-1; $i-=1){
			$close = $closes[$i];
			$EMA = ($close * $multiplier) + ($previous * (1.0 - $multiplier));
			$previous = $EMA;
		}
		return $EMA;
	}

	//Calculate the MACD of 2 histories of closing prices
	public function MACD($short_closes, $long_closes){
		return $this->EMA($short_closes) - $this->EMA($long_closes);
	}

	//Calculate the difference between the MACD of 2 histories of closing prices and the MACD's {$signal_cnt} length signal
	public function MACDWithSignal($closes, $short_cnt, $long_cnt, $signal_cnt){
		$short = $long = $signal = [];
		$macd = 0.0;
		for($i=$signal_cnt-1; $i>-1; $i-=1){
			$short = array_slice($closes, $i, $short_cnt);
			$long = array_slice($closes, $i, $long_cnt);

			$macd = $this->MACD($short, $long);
			array_unshift($signal,$macd);
		}
		$cur_signal = $this->EMA($signal);
		return $macd - $cur_signal;
	}


	}
}
?>