<?php
require('vendor/autoload.php');
require_once('lib/updatemanager.inc.php');

set_time_limit(0);

$starttime = time();

$l = null; // Heroku has ephemeral filesystem
//$l = new Katzgrau\KLogger\Logger(LOGDIR);

if (php_sapi_name() != 'cli') {
    if (isset($_SERVER['HTTP_USER_AGENT']) && substr($_SERVER['HTTP_USER_AGENT'], 0, 16) == 'GitHub-Hookshot/') {
        if (!isset($_SERVER['HTTP_X_GITHUB_EVENT']) || $_SERVER['HTTP_X_GITHUB_EVENT'] != 'push') {
            //    $l->info('Not a push event');
            exit("Not a push event<br>\n");
        }

        $payload = $_POST['payload'];
    }
    else
        $payload = null;

    if (isset($_GET['key'])) {
        if (hash("sha256", trim($_GET['key'])) != '1a82915eac2eead7c2ea4ccd8e0517908ff2318f64da33df5c19f5d967f088ae') {
            //    $l->warning('Invalid Signature');
            header('HTTP/1.0 403 Forbidden');
            exit('Invalid Signature. Exiting.');
        }
    }
    elseif ($payload != null) {
        list($algo, $hmac) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
        if (!in_array($algo, hash_algos(), TRUE)) {
            //    $l->warning("Hash algorithm '$algo' is not supported.);
            header('HTTP/1.0 500 Internal server error');
            exit("Hash algorithm '$algo' is not supported. Exiting.");
        }
        if (hash_hmac($algo, $payload, getenv('GITHUB_SECRET')) != $hmac) {
            print("Calcultated HMAC: ". hash_hmac($algo, $payload, getenv('GITHUB_SECRET') . "Got: $hmac\n")
            //    $l->warning('Invalid Signature');
            header('HTTP/1.0 403 Forbidden');
            exit('Invalid Signature. Exiting.');
        }
    }
    else {
        //    $l->warning('Missing Signature');
        header('HTTP/1.0 403 Forbidden');
        exit('Missing Signature. Exiting.');
    }

    if ($payload != null) {
        $json = json_decode($_POST['payload'], true);
        if (!isset($json['ref']) || $json['ref'] != 'refs/heads/' . PYLOAD_BRANCH) {
            //    $l->info('Not our branch');
            exit("Not our branch<br>\n");
        }
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
$mins = floor($seconds / 60 % 60);
$secs = floor($seconds % 60);
print("Elapsed time: $mins minutes and %d seconds<br>\n");
?>
