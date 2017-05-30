<?php
require_once "classes/class.Eddie.php";
require_once "classes/class.Logger.php";

date_default_timezone_set('America/New_York');

$eddie = new Eddie("GDAX");


$log = new Logger("log/log.csv");
$log->logTransaction("buy", 2.0, 100.0);

?>