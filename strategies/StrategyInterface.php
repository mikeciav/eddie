<?php
//Logger logs a buy or sell transaction in a specified csv file
//It can also tweet out the transaction

require_once PROJ_ROOT . "/private/defines.php";

interface Strategy{

	const BUY_STRONG = 2;
	const BUY_WEAK = 1;
	const DO_NOTHING = 0;
	const SELL_WEAK = -1;
	const SELL_STRONG = -2;

	//Evaluates the current position using the strategy
	//Returns one of the constants from this interface
	public function evaluate();
}
?>