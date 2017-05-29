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

	public function EMA($closes){
		$previous = $this->SMA($closes);
		$multiplier = (2 / (count($closes) + 1) );
		$EMA = 0.0;
		foreach($closes as $close){
			$EMA = (($close - $previous) * $multiplier) + $previous;
			$previous = $EMA;
		}
		return $EMA;
	}
}
?>