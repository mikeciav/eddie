<?php
//Calculator class computes the SMA and EMA of a set of closing prices

class Calculator{

	public function SMA($closes)
	{
		return array_sum($closes) / count($closes);
	}

	//Calculate EMA using the SMA of the previous $close_cnt closes as the seed
	public function EMA($closes, $n){
		$sma_closes = array();
		for($i = ($n-1)*2; $i>=$n; $i-=1){
			$sma_closes[] = $closes[$i];
		}
		$previous = $this->SMA($sma_closes);

		$multiplier = (2.0 / ($n + 1.0) );
		$EMA = 0.0;
		for($i = $n-1; $i>-1; $i-=1){
			$close = $closes[$i];
			$EMA = ($close * $multiplier) + ($previous * (1.0 - $multiplier));
			$previous = $EMA;
		}
		return $EMA;
	}

	//Calculate EMA using the oldest closing price as the seed
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
	public function MACD($short_closes, $long_closes, $short_cnt, $long_cnt){
		return $this->EMA($short_closes, $short_cnt) - $this->EMA($long_closes, $long_cnt);
	}

	//Calculate the difference between the MACD of 2 histories of closing prices and the MACD's {$signal_cnt} length signal
	public function MACDWithSignal($closes, $short_cnt, $long_cnt, $signal_cnt){
		$short = $long = $signal = [];
		$macd = 0.0;
		for($i=($signal_cnt-1)*2; $i>-1; $i-=1){
			$short = array_slice($closes, $i, $short_cnt*2);
			$long = array_slice($closes, $i, $long_cnt*2);

			$macd = $this->MACD($short, $long, $short_cnt, $long_cnt);
			array_unshift($signal,$macd);
		}
		$cur_signal = $this->EMA($signal, $signal_cnt);
		return $macd - $cur_signal;
	}

	//Calcuate the MACDR1 of 2 histories of closing prices
	//Return true if the signal - MACD metric has been on trend for {$min_wait_period} candles; otherwise return false
	public function MACDR1($closes, $short_cnt, $long_cnt, $signal_cnt, $min_wait_period){
		$trend = $macd = $this->MACDWithSignal($closes, $short_cnt, $long_cnt, $signal_cnt);
		for($i=0; $i<$min_wait_period-1; $i+=1){
			array_shift($closes);
			$macd = $this->MACDWithSignal($closes, $short_cnt, $long_cnt, $signal_cnt);
			if($trend > 0){
				if($macd < 0.0) return false;
			}
			else{
				if($macd >= 0.0) return false;
			}
		}
		return true;
	}

	//Calculate the MACDR2 of 2 histories of closing prices
	//Return true if the amplitude of the MACD exceeds a threshold of {$min_ampl}; otherwise return false
	public function MACDR2($closes, $short_cnt, $long_cnt, $signal_cnt, $min_ampl){
		$short = array_slice($closes, 0, $short_cnt*2);
		$long = array_slice($closes, 0, $long_cnt*2);
		return (abs($this->MACD($short, $long, $short_cnt, $long_cnt)) > $min_ampl);

	}
}
?>