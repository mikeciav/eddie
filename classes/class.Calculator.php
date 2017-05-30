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
		$multiplier = (2.0 / ($n + 1) );
		$EMA = 0.0;
		for($i = $n-1; $i>-1; $i-=1){
			$close = $closes[$i];
			$EMA = ($close * $multiplier) + ($previous * (1 - $multiplier));
			$previous = $EMA;
		}
		return $EMA;
	}

	//This EMA calculation uses the oldest closing price as the seed
	public function EMA2($closes){
		$n = count($closes);
		$multiplier = (2.0 / ($n + 1) );
		$previous = $closes[$n-1];
		$EMA = 0.0;
		for($i = $n-1; $i>-1; $i-=1){
			$close = $closes[$i];
			$EMA = ($close * $multiplier) + ($previous * (1 - $multiplier));
			$previous = $EMA;
		}
		return $EMA;
	}
}
?>