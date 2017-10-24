<?php
require('vendor/autoload.php');
require_once('lib/updatemanager.inc.php');

set_time_limit(0);

$starttime = time();

$l = null; // Heroku has ephemeral filesystem
//$l = new Katzgrau\KLogger\Logger(LOGDIR);

if (php_sapi_name() != 'cli') {
    header('Content-Type: text/plain');
    if (isset($_SERVER['HTTP_USER_AGENT']) && substr($_SERVER['HTTP_USER_AGENT'], 0, 16) == 'GitHub-Hookshot/') {
        if (!isset($_SERVER['HTTP_X_GITHUB_EVENT']) || $_SERVER['HTTP_X_GITHUB_EVENT'] != 'push') {
            //    $l->info('Not a push event');
            exit("Not a push event.");
        }

        $payload = $_POST['payload'];
    }
    else
        $payload = null;

    if (isset($_GET['key'])) {
        if (hash("sha256", trim($_GET['key'])) != '49e4b829958ce1a36af08a0ff03b9897be3653cbd979a03651f2dd1bb3d98733') {
            //    $l->warning('Invalid Signature');
            header('HTTP/1.0 403 Forbidden');
            exit('Invalid key. Exiting.');
        }
    }

    if ($payload != null) {
        if (!isset($_GET['key'])) {
            list($algo, $hmac) = explode('=', $_SERVER['HTTP_X_HUB_SIGNATURE'], 2) + array('', '');
            if (!in_array($algo, hash_algos(), TRUE)) {
                //    $l->warning("Hash algorithm '$algo' is not supported.);
                header('HTTP/1.0 500 Internal server error');
                exit("Hash algorithm '$algo' is not supported. Exiting.");
            }
            $raw_post_data = file_get_contents('php://input');
            if (hash_hmac($algo, $raw_post_data, getenv('GITHUB_SECRET')) != $hmac) {
                //    $l->warning('Webhook secret does not match. Exiting.');
                header('HTTP/1.0 403 Forbidden');
                exit('Webhook secret does not match. Exiting.');
            }
        }

        $json = json_decode($payload, true);
        if (!isset($json['ref']) || $json['ref'] != 'refs/heads/' . PYLOAD_BRANCH) {
            //    $l->info('Not our branch');
            exit('Not our branch.');
        }
    }
    else {
        //    $l->warning('Missing webhook secret. Exiting.');
        header('HTTP/1.0 403 Forbidden');
        exit('Missing webhook secret. Exiting.');
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
print("Elapsed time: $mins minutes and $secs seconds<br>\n");
?>
