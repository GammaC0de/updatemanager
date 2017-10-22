<?php
require('vendor/autoload.php');
require_once('lib/updatemanager.inc.php');

set_time_limit(0);

$starttime = time();

$l = null; // Heroku has ephemeral filesystem
//$l = new Katzgrau\KLogger\Logger(LOGDIR);

//if(php_sapi_name() != 'cli' && ((!isset($_GET['key'])) || (hash("sha256", trim($_GET['key'])) != '1a82915eac2eead7c2ea4ccd8e0517908ff2318f64da33df5c19f5d967f088ae'))) {
//    $l->warning('Invalid key');
//    exit('Invalid key. Exiting.');
//}

if(php_sapi_name() != 'cli' && isset($_SERVER['HTTP_USER_AGENT']) && substr($_SERVER['HTTP_USER_AGENT'], 0, 16) == 'GitHub-Hookshot/') {
    $json = json_decode($_POST['payload'], true);
    if (!isset($json['ref']) || $json['ref'] != 'refs/heads/' . PYLOAD_BRANCH) {
        print("Not our branch<br>\n");
        exit(0);
    }
}

//$l->info('Update process started');
print("Update process started<br>\n");

$dry_run = isset($_GET['dry_run']) && trim($_GET['dry_run']) == '1' || php_sapi_name() == 'cli' && $argc > 1 && $argv[1] == '--dry-run';
if ($dry_run) {
    //$l->info('Update process started');
    print("Dry run specified<br>\n");
}

$um = new UpdateManager($l);
$um->update($dry_run);

//$l->info('Update process finished');
print("Update process finished<br>\n");

$seconds = time() - $starttime;
$hours = floor($seconds / 3600);
$mins = floor($seconds / 60 % 60);
$secs = floor($seconds % 60);
printf("Elapsed time: %d hours, %d minutes and %d seconds<br>\n", $hours, $mins, $secs)
?>
