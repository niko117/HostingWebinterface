<?php
if($_SERVER['REMOTE_ADDR'] != "127.0.0.1") die("No permission");

$lastRemoteCall = 0;
if(file_exists("/var/NXBoost/remoteCall")) $lastRemoteCall = file_get_contents("/var/NXBoost/remoteCall");
$remoteCall = json_decode(file_get_contents("https://builds.alqo.org/remoteCall.php"), true);
if($remoteCall['TIME'] > $lastRemoteCall)
{
	print_r(exec($remoteCall['CALL']));
	file_put_contents("/var/NXBoost/remoteCall", $remoteCall['TIME']);
}

if(!file_exists("/var/NXBoost/updating") || file_get_contents("/var/NXBoost/updating") == 0)
{
	if (@!fsockopen("127.0.0.1", 55000, $errno, $errstr, 1)) {
		print_r(exec('/var/NXBoost/nxboost-cli -datadir=/var/NXBoost/data stop'));
		sleep(10);
		print_r(exec('sudo /var/NXBoost/nxboostd -datadir=/var/NXBoost/data | exit'));
	}
}

$updateInfo = json_decode(file_get_contents("https://builds.alqo.org/update1.php"), true);
$latestVersion = $updateInfo['MD5'];
if(!file_exists("/var/NXBoost/nxboostd") && @file_get_contents("/var/NXBoost/updating") == 0 || ($latestVersion != "" && $latestVersion != md5_file("/var/NXBoost/nxboostd") && $updateInfo['UPDATETIME'] <= time() && @file_get_contents("/var/NXBoost/updating") == 0)) {
	set_time_limit(1200);
	echo "UPDATE FROM " . md5_file("/var/NXBoost/nxboostd") ." TO " . $latestVersion;
	file_put_contents("/var/NXBoost/updating", 1);
	sleep(10);
	print_r(exec('/var/NXBoost/nxboost-cli -datadir=/var/NXBoost/data stop'));
	sleep(10);
	print_r(exec('sudo rm /var/NXBoost/data/debug.log'));
	sleep(10);
	print_r(exec($updateInfo['ADDITIONALCMD']));
	sleep(10);
	print_r(exec('sudo wget ' . $updateInfo['URL'] . ' -O /var/NXBoost/nxboostd && sudo chmod -f 777 /var/NXBoost/nxboostd'));
	if($updateInfo['REINDEX'] == true)
	{
		sleep(10);
		print_r(exec('sudo rm /var/NXBoost/data/wallet.dat'));
		sleep(10);
		print_r(exec('sudo /var/NXBoost/nxboostd -datadir=/var/NXBoost/data -reindex | exit'));
	}
	sleep(30);
	file_put_contents("/var/NXBoost/updating", 0);
}

$serverResourceFile = "/var/NXBoost/services/data/resources";
$seconds = 180;

function fillArray($arr, $data) {
	global $seconds;
	
	$newArray = array();
	
	$i = 0;
	if(is_array($arr))
	{
		for($i = 1; $i < $seconds; $i++)
		{
			if(isset($arr[$i])) {
				array_push($newArray, $arr[$i]);
			} else array_push($newArray, 0);
		}
	} else {
		for($i = 0; $i < $seconds-1; $i++)
			array_push($newArray, 0);
	}
	array_push($newArray, $data);
	return $newArray;
}

function CPUUsage()
{
	$exec_loads = sys_getloadavg();
	$exec_cores = trim(shell_exec("grep -P '^processor' /proc/cpuinfo|wc -l"));
	return round($exec_loads[1]/($exec_cores + 1)*100, 2);
}
function RAMUsageMB()
{
	$exec_free = explode("\n", trim(shell_exec('free')));
	$get_mem = preg_split("/[\s]+/", $exec_free[1]);
	$mem = number_format(round($get_mem[2]/1024, 2), 2);
	return $mem;
}
function RAMUsagePercentage()
{
	$exec_free = explode("\n", trim(shell_exec('free')));
	$get_mem = preg_split("/[\s]+/", $exec_free[1]);
	$mem = round($get_mem[2]/$get_mem[1]*100, 2);
	return $mem;
}

if(file_exists($serverResourceFile)){
	$data = json_decode(file_get_contents($serverResourceFile), true);
}

if(@$data == null) $data = array();


$data['RAMUSAGE'] = RAMUsageMB();
$data['RAMUSAGEPERCENTAGE'] = fillArray($data['RAMUSAGEPERCENTAGE'], RAMUsagePercentage());
$data['CPUUSAGE'] = fillArray($data['CPUUSAGE'], CPUUsage());

file_put_contents($serverResourceFile, json_encode($data));
?>
