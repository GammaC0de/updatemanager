<?php
define('AUTH_FILE', getenv('HOME') . '/.netrc');

//define('PYLOAD_REPO_URL', 'https://github.com/pyload/pyload.git');
define('PYLOAD_REPO_URL', 'https://github.com/GammaC0de/pyload.git');
define('PYLOAD_REPO_PATH', 'data/pyload-repo/');
define('PYLOAD_BRANCH', 'stable');

//define('SERVER_REPO_URL', 'https://github.com/pyload/updates.git');
define('SERVER_REPO_URL', 'https://github.com/GammaC0de/GammaC0de.github.io.git');
define('SERVER_REPO_PATH', 'data/server-repo/');

define('PLUGINS_PATH', 'module/plugins/');
define('REPO_PLUGINS_PATH', PYLOAD_REPO_PATH . PLUGINS_PATH);

define('SQLITEDB_FILE', 'plugins.sqlite'); // Temporary location
define('PLUGINLIST_FILE', SERVER_REPO_PATH . 'plugins.txt');
define('BLACKLIST_FILE', SERVER_REPO_PATH . 'blacklist.txt');
define('VERSION_FILE', SERVER_REPO_PATH . 'VERSION');

define('LOGDIR', 'logs');

/* Constants for local test env
define('AUTH_FILE', getenv('HOME') . '/.netrc');

define('PYLOAD_REPO_URL', 'https://github.com/pyload/pyload.git');
define('PYLOAD_REPO_PATH', 'data/pyload-repo/');
define('PYLOAD_BRANCH', 'stable');

define('SERVER_REPO_URL', 'https://github.com/pyload/updates.git');
define('SERVER_REPO_PATH', 'data/server-repo/');

define('PLUGINS_PATH', 'module/plugins/');
define('REPO_PLUGINS_PATH', PYLOAD_REPO_PATH . PLUGINS_PATH);

define('SQLITEDB_FILE', 'plugins.sqlite'); // Temporary location
define('PLUGINLIST_FILE', SERVER_REPO_PATH . 'plugins.txt');
define('BLACKLIST_FILE', SERVER_REPO_PATH . 'blacklist.txt');
define('VERSION_FILE', SERVER_REPO_PATH . 'VERSION');
*/

require('vendor/autoload.php');
require_once('lib/database.inc.php');
require_once('lib/git.inc.php');

class UpdateManager {

    private $git_pyload;
    private $git_updserver;
    private $db;
    private $l;

    public $prev_commit;
    public $last_commit;

    function __construct($l) {
        $this->l = $l;

        // Create '.netrc' auth file.
        file_put_contents(AUTH_FILE, "machine github.com\n  password " .getenv('GIT_TOKEN') . " \n  login " .getenv('GIT_USER') . " ");

        $this->git_updserver=new GitCMD($l, SERVER_REPO_URL, SERVER_REPO_PATH, 'master');

        if (file_exists(SERVER_REPO_PATH . SQLITEDB_FILE))
            copy(SERVER_REPO_PATH . SQLITEDB_FILE, SQLITEDB_FILE);
        $this->db = new umSQLite3(SQLITEDB_FILE);
        $this->prev_commit = $this->db->get_prev_commit();
        //$this->l->info("Prev commit: $this->prev_commit");
        printf("Prev commit: %s\n", is_null($this->prev_commit) ? "None" : $this->prev_commit);

        $this->git_pyload = new GitCMD($l, PYLOAD_REPO_URL, PYLOAD_REPO_PATH, PYLOAD_BRANCH, !is_null($this->prev_commit));
        $this->last_commit = $this->git_pyload->last_commit();

        //$this->l->info("Last commit: $this->last_commit");
        print("Last commit: $this->last_commit\n");
    }

    function __destruct() {
        if (!is_null($this->db)) {
            $this->db->close();
        }
    }

    private function get_plugin_version($type, $name) {
        $path = REPO_PLUGINS_PATH . $type . '/' . $name;
        if (!file_exists($path))
            $path = "https://raw.githubusercontent.com/pyload/pyload/$this->last_commit/module/plugins/$type/$name";
        $content = file_get_contents($path);
        $status = preg_match('/__version__\s*=\s*[\'"]([^\'"]+)[\'"]/i', $content, $m);
        if(!$status || !isset($m[1]) || $content==false) {
            //$this->l->error("Unable to detect version for $type/$name");
            print("Unable to detect version for $type/$name\n");
            return null;
        }
        else {
            return $m[1];
        }
    }

    private function get_nametype($module) {
        if (preg_match('~' . PLUGINS_PATH . '(.+?)/(.+)~', $module, $m)  == 0) {
            //$this->l->error(Unable to detect type or name for mosule $module\n");
            print("Unable to detect type or name for mosule $module\n");
            return array(null, null);
        }
        else
            return array_slice($m, 1, 2);
    }


    public function update_db() {
        $filter = function($file) { return preg_match( '~^' . PLUGINS_PATH . ".+?/(?!__init__.py)~", $file) === 1;};

        if (is_null($this->prev_commit)) {
            $modules = array_filter($this->git_pyload->ls($this->last_commit, PLUGINS_PATH), $filter);
            foreach($modules as $module) {
                list($type, $name) = $this->get_nametype($module);
                if (is_null($type) || is_null($name))
                    continue;
                //$this->l->info("New plugin $type/$name! Adding to the database");
                print("New plugin $type/$name! Adding to the database\n");
                $file_version = $this->get_plugin_version($type, $name);
                $this->db->insert_plugin($type, $name, $this->last_commit, $file_version);
            }
        }
        else {
            $modules = array_filter($this->git_pyload->diff($this->prev_commit, $this->last_commit), $filter, ARRAY_FILTER_USE_KEY);
            foreach($modules as $module=>$status) {
                list($type, $name) = $this->get_nametype($module);
                if (is_null($type) || is_null($name))
                    continue;

                switch($status) {
                    case 'A':
                        //$this->l->info("New plugin $type/$name! Adding to the database");
                        print("New plugin $type/$name! Adding to the database\n");
                        $file_version = $this->get_plugin_version($type, $name);
                        $this->db->insert_plugin($type, $name, $this->last_commit, $file_version);
                        break;

                    case 'M':
                        $file_version = $this->get_plugin_version($type, $name);
                        if ($this->db->plugin_exists($type, $name, $file_version) == 1) {
                            //$this->l->info("$type/$name updated to $file_version");
                            print("$type/$name updated to $file_version\n");
                            $this->db->update_plugin($type, $name, $this->last_commit, $file_version);
                        }
                        break;

                    case 'D':
                        //$this->l->info("Deleted plugin $type/$name! Removing from the database");
                        print("Deleted plugin $type/$name! Removing from the database\n");
                        $this->db->remove_plugin($type, $name);
                        break;

                    default:
                        //$this->l->warning("Unknown file status '$status' for file $type/$name");
                        print("Unknown file status '$status' for file $type/$name\n");
                        break;
                }
            }
        }

        // Blacklist
        $content = file_get_contents(BLACKLIST_FILE);
        if ($content != false) {
            $modules = explode(PHP_EOL, $content);
            if (end($modules) == '')
                $modules = array_slice($modules, 0, -1);
            foreach($modules as $module) {
                list($type, $name) = explode('|', $module, 2) + array('', '');
                if ($type != '' && $name != '')
                    $this->db->remove_plugin($type, $name);
            }
        }

        $this->db->set_prev_commit($this->last_commit);
    }

    public function write_static() {
        $version = file_get_contents(VERSION_FILE);
        if ($version == false) {
            $version = '0.4.9';
            file_put_contents(VERSION_FILE, $version);
        }

        $f1 = fopen(PLUGINLIST_FILE, 'w');
        fwrite($f1, "$version\nhttps://raw.githubusercontent.com/pyload/pyload/%(changeset)s/module/plugins/%(type)s/%(name)s\ntype|name|changeset|version");
        $db_rows = $this->db->get_plugin_rows();
        while($row = $db_rows->fetchArray(SQLITE3_ASSOC)) {
            fwrite($f1, sprintf("\n%s|%s|%s|%s", $row['type'], $row['name'], $row['sha'], $row['version']));
        }

        fwrite($f1, "\nBLACKLIST\n");
        $f2 = fopen(BLACKLIST_FILE, 'w');
        $db_rows = $this->db->get_blacklist_rows();
        while($row = $db_rows->fetchArray(SQLITE3_ASSOC)) {
            fwrite($f1, sprintf("%s|%s\n", $row['type'], $row['name']));
            fwrite($f2, sprintf("%s|%s\n", $row['type'], $row['name']));
        }

        fclose($f2);
        fclose($f1);
    }

    public function push_server() {
        $this->git_updserver->set_ident("pyLoadUpdater", "pyLoadUpdater@users.noreply.github.com");
        if ($this->git_updserver->commit()) {
            $this->git_updserver->push();
            return true;
        }
        else
            return false;
    }

    public function update($dry_run=false) {
        $this->update_db();
        //$this->info('DB update completed');
        print("DB update completed\n");

        // The DB is now updated! Let's create the static file.
        $this->write_static();
        //$this->l->info('Plugin list created');
        print("Plugin list created\n");

        $this->db->close();
        if ($this->git_updserver->dirty() || !file_exists(SERVER_REPO_PATH . SQLITEDB_FILE)) {
            if ($dry_run) {
                //$this->l->info('Dry run, not pushing');
                print("There are pending changes, dry run - not pushing\n");
            } else {
                rename(SQLITEDB_FILE, SERVER_REPO_PATH . SQLITEDB_FILE);
                if ($this->push_server()) {
                    //$this->l->info('Server updated');
                    print("Server updated\n");
                } else {
                    //$this->l->info('No pending changes');
                    print("No pending changes\n");
                }
            }
        } else {
            //$this->l->info('No pending changes');
            print("No pending changes\n");
        }
    }
}
?>