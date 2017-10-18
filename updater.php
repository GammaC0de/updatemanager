<?php
require('vendor/autoload.php');
require_once('lib/updatemanager.inc.php');

set_time_limit(0);

$starttime = time();

$l = null;
//$l = new Katzgrau\KLogger\Logger(LOGDIR);

//if(php_sapi_name() != 'cli' && ((!isset($_GET['key'])) || (sha1(trim($_GET['key'])) != 'b7739cb242fe4e9506a6488d96de0b187fb170ae'))) {
//    $l->warning('Invalid key');
//    exit('Invalid key. Exiting.');
//}

//$l->info('Update process started');
print("Update process started<br>\n");

$um = new UpdateManager($l);
$um->update();

//$l->info('Update process finished');
print("Update process finished<br>\n");

$seconds = time() - $starttime;
$hours = floor($seconds / 3600);
$mins = floor($seconds / 60 % 60);
$secs = floor($seconds % 60);
printf("Elapsed time: %d hours, %d minutes and %d seconds<br>\n", $hours, $mins, $secs)
?>
