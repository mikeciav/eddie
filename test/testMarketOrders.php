<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";

date_default_timezone_set('America/New_York');

	$eddie = new Eddie("GDAX");

	$eddie->sellETH(.05);
	sleep(5);
	$eddie->buyETH(10);

?>