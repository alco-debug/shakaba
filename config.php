<?php
// --Config Start-- 
define('CLAIRE_TEXTMODE', false); //true disallow images.
define('CLAIRE_BLOGMODE', false); //true allow creating thread only by admin & mod.
define('TINYIB_PAGETITLE', 'Shakaba');
define('TINYIB_ADMINPASS',  "");
define('TINYIB_MODPASS',    ""); // Leave blank to disable
define('TINYIB_THREADSPERPAGE', 10);
define('TINYIB_REPLIESTOSHOW',  3);
define('TINYIB_MAXTHREADS',     0);    // 0 disables deleting old threads
define('TINYIB_DELETE_TIMEOUT', 1200);  // Seconds for deleting own posts
define('TINYIB_MAXPOSTSIZE',    15000); // Characters
define('TINYIB_RATELIMIT',      15);   // Delay between posts from same IP
define('TINYIB_TRIPSEED',   "1231");
define('TINYIB_USECAPTCHA',   true); // just use it.
define('TINYIB_CAPTCHASALT',  '');
define('TINYIB_THUMBWIDTH',  250);
define('TINYIB_THUMBHEIGHT', 250);
define('TINYIB_REPLYWIDTH',  250);
define('TINYIB_REPLYHEIGHT', 250);
define('TINYIB_TIMEZONE',   ''); // Leave blank to use server default timezone
define('TINYIB_DATEFORMAT', 'D Y-m-d g:ia');
define('TINYIB_DBPOSTS','posts');
define('TINYIB_DBBANS', 'bans');
define('TINYIB_DBLOCKS','locked_threads');
define('TINYIB_DBPATH', '../db');
define('MAINTENANCE_MODE', false);
define('MODULES_PATH', 'modules/');
define('TEMPLATES_PATH', 'templates/');
define('BUMPLIMIT', 1000);
define('MARKUP_ENABLED', true);
define('MARKUP_ENABLED_TAGS', [
    'bold' => true,
    'italic' => true,
    'strike' => true,
    'underline' => true,
    'spoiler' => true,
    'monotype' => true,
    'header' => true,
]);
define('MIGRATED', false);
define('NEW_DOMAIN', 'http://new-example.com');
define('ROOT_URI', '');

$board_name = ROOT_URI;
$board_desc = TINYIB_PAGETITLE;
// --Config End--
?>