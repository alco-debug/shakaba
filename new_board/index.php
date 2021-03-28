<?php
session_start();if (!file_exists('db')){mkdir('db', 0777, true);}
error_reporting(E_ALL);

require 'board_config.php';
require MODULES_PATH.'modules.php'; setupModules(['modules_path' => MODULES_PATH]);

/// --Imports Start--
importModule('action_handlers');
importModule('sqlite');
importModule('helpers');
importModule('boards');
importModule('build_post'); setupBuildPost([
                                        'markup_enabled' => MARKUP_ENABLED,
                                        'markup_enabled_tags' => MARKUP_ENABLED_TAGS,
                                        ]);
importModule('build_post_block');
importModule('additional_pages');
importModule('manage');
importModule('redirect_table');

importModule('templates');; setupTemplates(TEMPLATES_PATH);
importModule('view_page');
/// --Imports End--

try {
        $db = new PDO('sqlite:'.TINYIB_DBPATH);
        validateDatabaseSchema();
} catch (PDOException $e) {
    fancyDie('Could not connect to database: '.  $e->getMessage());
}
function validateDatabaseSchema() {
        global $db;
        $db->query('
        CREATE TABLE IF NOT EXISTS '.TINYIB_DBPOSTS.' (
                id INTEGER PRIMARY KEY,
                parent INTEGER NOT NULL,
                timestamp TIMESTAMP NOT NULL,
                bumped TIMESTAMP NOT NULL,
                ip TEXT NOT NULL,
                name TEXT NOT NULL,
                tripcode TEXT NOT NULL,
                email TEXT NOT NULL,
                nameblock TEXT NOT NULL,
                subject TEXT NOT NULL,
                message TEXT NOT NULL,
                password TEXT NOT NULL,
                file TEXT NOT NULL,
                file_hex TEXT NOT NULL,
                file_original TEXT NOT NULL,
                file_size INTEGER NOT NULL DEFAULT "0",
                file_size_formatted TEXT NOT NULL,
                image_width INTEGER NOT NULL DEFAULT "0",
                image_height INTEGER NOT NULL DEFAULT "0",
                thumb TEXT NOT NULL,
                thumb_width INTEGER NOT NULL DEFAULT "0",
                thumb_height INTEGER NOT NULL DEFAULT "0"
        )
        ');
        $db->query('
        CREATE TABLE IF NOT EXISTS '.TINYIB_DBBANS.' (
                id INTEGER PRIMARY KEY,
                ip TEXT NOT NULL,
                timestamp TIMESTAMP NOT NULL,
                expire TIMESTAMP NOT NULL,
                reason TEXT NOT NULL
        )
        ');
        $db->query('
        CREATE TABLE IF NOT EXISTS '.TINYIB_DBLOCKS.' (
                id INTEGER PRIMARY KEY,
                thread INTEGER NOT NULL        
        )
        ');
}

// Validate settings
if (TINYIB_TRIPSEED == '' || TINYIB_ADMINPASS == '') {
        fancyDie('Error: TINYIB_TRIPSEED and TINYIB_ADMINPASS must be configured.');
}
foreach (array('db') as $dir) {
        if (!is_writable($dir)) fancyDie("Error: Can't write to directory '$dir'.");
}
if (strlen(TINYIB_TIMEZONE)) date_default_timezone_set(TINYIB_TIMEZONE);
$redirect = true;
list($loggedin, $isadmin) = manageCheckLogIn();
define('LOGGED_IN', $loggedin);
define('IS_ADMIN', $isadmin);
////////////////////////////////////////////////////////////////////////////////
// Controller
if (MIGRATED){
    $redirect_url = NEW_DOMAIN.$_SERVER['REQUEST_URI'];
    redirect($redirect_url);
}

$redirect_uri = redirect_by_table($_SERVER['REQUEST_URI']);
if ($redirect_uri != $_SERVER['REQUEST_URI']){
        redirect($redirect_uri);
}

if(! isset($_GET['do'])){
        redirect(ROOT_URI.'?do=page');
}
        
switch($_GET['do']) {
        case 'markup': {
            uberDie(markupGuide());
        }break;
        case 'page': {
                if (! isset($_GET['p'])) redirect('?do=page&p=0');
                uberDie( viewPage($_GET['p']) );
        } break;
        case 'thread': {
                if (! isset($_GET['id'])) redirect('?do=page&p=0');
                uberDie( viewThread($_GET['id']));
        } break;
        case 'post': {
                handlePost();
                redirect($redirect);
        } break;
        case 'delpost': {
                handleDeletePost();
                redirect($redirect);
        } break;
        case 'lock': {
                if (! isset($_GET['id']) && $_GET['id'] > 0) redirect('?do=page&p=0');
                $thread_id = intval($_GET['id']);
                if (isLocked($thread_id)) {
                        unlockThread($thread_id);
                } else {
                        lockThread($thread_id);
                }
                redirect('?do=thread&id='.$thread_id);
        } break;
        case 'manage': {
                uberDie( handleManage() );
        } break;
        default: {
                fancyDie('Invalid request.');
        } break;
}
