<?php
require_once "private/defines.php";
$TAKER_FEE = 0.003;
$SLIPPAGE = 0.001;
$REALIZED_AMOUNT = 1.0 - ($TAKER_FEE + $SLIPPAGE);
//Init with values from July 23, 2018
$eth_total = 0.0;
$usd_total = 100;
$target_1_amount = 0.0;
$target_2_amount = 0.0;
$previous_action = "sell";

$eth_total_print = $eth_total;
$usd_total_print = $usd_total;
$last_usd_total = 100;
$last_usd_total_print = 0;

//Input structure: {datetime},{buy/sell},{amount}|{second target amount}:{second target price}
$log = file(PROJ_ROOT . "/log/log.csv", FILE_IGNORE_NEW_LINES);
foreach($log as $line){
	$line_parts = explode(',', $line);
	$buy_or_sell = $line_parts[1];
	$amount = $line_parts[2]; //This is the faked amount, but a profit-take will = 0 here
	$price = doubleval($line_parts[3]);
	echo "\n{$buy_or_sell} at {$price}";

	if($amount == "0"){
		if($target_1_amount > 0.0){
			if($buy_or_sell == "buy"){
				$eth_total += $target_1_amount / $price;
			}
			else if($buy_or_sell == "sell"){
				$usd_total += $target_1_amount * $price;
			}
			$target_1_amount = 0.0;
		}
		else if($target_2_amount > 0.0){
			if($buy_or_sell == "buy"){
				$eth_total += $target_2_amount / $price;
			}
			else if($buy_or_sell == "sell"){
				$usd_total += $target_2_amount * $price;
			}
			$target_1_amount = 0.0;
		}
	}

	else if($buy_or_sell == "buy"){
		if($usd_total > 0){
			$eth_total += (($usd_total + $target_1_amount + $target_2_amount) / $price) * $REALIZED_AMOUNT;
			$usd_total = 0;

			$eth_total_print = $eth_total;
			$usd_total_print = 0;

			$target_1_amount = $eth_total * TAKE_PROFIT_PERCENTAGE_LONG;
			$target_2_amount = $eth_total * TAKE_PROFIT_PERCENTAGE_2;
			$eth_total -= ($target_1_amount + $target_2_amount);
		}
		else{
			echo "\nOUT OF CASH OH NO OH NO";
		}
	}

	else if($buy_or_sell == "sell"){
		if($eth_total > 0){
			$usd_total += ($price * ($eth_total + $target_1_amount + $target_2_amount)) * $REALIZED_AMOUNT;
			$eth_total = 0;

			$usd_total_print = $usd_total;
			$last_usd_total_print = $usd_total - $last_usd_total;
			$last_usd_total = $usd_total;
			$eth_total_print = 0;

			$target_1_amount = $usd_total * TAKE_PROFIT_PERCENTAGE_SHORT;
			$target_2_amount = $usd_total * TAKE_PROFIT_PERCENTAGE_2;
			$usd_total -= ($target_1_amount + $target_2_amount);
		}
		else{
			echo "\nOUT OF ETH OH NO OH NO";
		}
	}
	
	echo "\nRemaining ETH: {$eth_total_print}";
	echo "\nRemaining USD: {$usd_total_print} ({$last_usd_total_print})";
}
?>

