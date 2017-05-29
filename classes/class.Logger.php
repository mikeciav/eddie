<?php
//Calculator class computes the SMA and EMA of a set of closing prices

class Logger{

	public function __construct($path="log/log.csv"){
		$this->path = $path;
	}
	
	public function logTransaction($side, $size, $price){
		$total = ($side == "buy") ? $size / $price : $size * $price;
		$string = $side . "," . $size . "," . $price . "," . $total;
		return file_put_contents($this->path, $string.PHP_EOL , FILE_APPEND | LOCK_EX);		
	}

}
?>