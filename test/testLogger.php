<?php
require_once "private/defines.php";

require_once PROJ_ROOT . "/classes/class.Eddie.php";
require_once PROJ_ROOT . "/classes/class.Logger.php";

date_default_timezone_set('America/New_York');

$eddie = new Eddie("GDAX");


$log = new Logger("log/log.csv");
$log->logTransaction("buy", 2.0, 100.0, false);
$log->logTransaction("sell", 1.0, 105.0, true);

?>