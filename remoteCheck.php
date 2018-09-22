<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
header("Content-Type: text/plain");

exec('sudo chmod -f 777 /var/NXBoost/data/debug.log');

echo file_get_contents("/var/NXBoost/services/data/getinfo");

echo "

";

$debugLog = "/var/NXBoost/data/debug.log";
echo file_get_contents($debugLog);
?>
