<?php
session_start();if (!file_exists('db')){mkdir('db', 0777, true);}
error_reporting(E_ALL);

require 'board_config.php';
require MODULES_PATH.'modules.php'; setupModules(['modules_path' => MODULES_PATH]);

/// --Imports Start--
importModule('build_post'); setupBuildPost([
                                        'markup_enabled' => MARKUP_ENABLED,
                                        'markup_enabled_tags' => MARKUP_ENABLED_TAGS,
                                        ]);
importModule('build_post_block');
importModule('additional_pages');
importModule('helpers');
importModule('manage');
importModule('redirect_table');
importModule('templates');; setupTemplates(TEMPLATES_PATH);
importModule('view_page');
/// --Imports End--

function newPost() {
        return array(
                'parent' => '0',
                'timestamp' => '0',
                'bumped' => '0',
                'ip' => '',
                'name' => '',
                'tripcode' => '',
                'email' => '',
                'nameblock' => '',
                'subject' => '',
                'message' => '',
                'password' => '',
                'file' => '',
                'file_hex' => '',
                'file_original' => '',
                'file_size' => '0',
                'file_size_formatted' => '',
                'image_width' => '0',
                'image_height' => '0',
                'thumb' => '',
                'thumb_width' => '0',
                'thumb_height' => '0'
        );
}

function nameAndTripcode($name) {
        if (preg_match("/(#|!)(.*)/", $name, $regs)) {
                $cap = $regs[2];
                $cap_full = '#' . $regs[2];
                if (function_exists('mb_convert_encoding')) {
                        $recoded_cap = mb_convert_encoding($cap, 'SJIS', 'UTF-8');
                        if ($recoded_cap != '') {
                                $cap = $recoded_cap;
                        }
                }
                if (strpos($name, '#') === false) {
                        $cap_delimiter = '!';
                } elseif (strpos($name, '!') === false) {
                        $cap_delimiter = '#';
                } else {
                        $cap_delimiter = (strpos($name, '#') < strpos($name, '!')) ? '#' : '!';
                }
                if (preg_match("/(.*)(" . $cap_delimiter . ")(.*)/", $cap, $regs_secure)) {
                        $cap = $regs_secure[1];
                        $cap_secure = $regs_secure[3];
                        $is_secure_trip = true;
                } else {
                        $is_secure_trip = false;
                }
                $tripcode = "";
                if ($cap != "") { // Copied from Futabally
                        $cap = strtr($cap, "&amp;", "&");
                        $cap = strtr($cap, "&#44;", ", ");
                        $salt = substr($cap."H.", 1, 2);
                        $salt = preg_replace("/[^\.-z]/", ".", $salt);
                        $salt = strtr($salt, ":;<=>?@[\\]^_`", "ABCDEFGabcdef");
                        $tripcode = substr(crypt($cap, $salt), -10);
                }
                if ($is_secure_trip) {
                        if ($cap != "") {
                                $tripcode .= "!";
                        }
                        $tripcode .= "!" . substr(md5($cap_secure . TINYIB_TRIPSEED), 2, 10);
                }
                return array(preg_replace("/(" . $cap_delimiter . ")(.*)/", "", $name), $tripcode);
        }
        return array($name, "");
}
function nameBlock($name, $tripcode, $email, $timestamp, $modposttext) {
        $output = '<span class="postername">';
        $output .= ($name == "" && $tripcode == "") ? "" : $name;
        if ($tripcode != "") {
                $output .= '</span><span class="postertrip">!' . $tripcode;
        }
        $output .= '</span>';
        if ($email != "") {
                $output = '<a href="mailto:' . $email . '">' . $output . '</a>';
        }
        return $output . $modposttext . ' ' . date(TINYIB_DATEFORMAT, $timestamp);
}
function _postLink($matches) {
        $post = postByID($matches[1]);
        if ($post) {
                return
                        '<a href="?do=thread&id=' .
                        ($post['parent'] == 0 ? $post['id'] : $post['parent']) .
                        '#' . $matches[1] . '">' . $matches[0] . '</a>'
                ;
        }
        return $matches[0];
}
function postLink($message) {
        return preg_replace_callback('/&gt;&gt;([0-9]+)/', '_postLink', $message);
}
function colorQuote($message) {
        if (substr($message, -1, 1) != "\n") { $message .= "\n"; }
        return preg_replace('/^(&gt;[^\>](.*))\n/m', '<span class="unkfunc">\\1</span>' . "\n", $message);
}
function deletePostImages($post) {
        if ($post['file'] != '') { @unlink('db/' . $post['file']); }
        if ($post['thumb'] != '') { @unlink('db/' . $post['thumb']); }
}
function checkBanned() {
        $ban = banByIP($_SERVER['REMOTE_ADDR']);
        if ($ban) {
                if ($ban['expire'] == 0 || $ban['expire'] > time()) {
                        $expire = ($ban['expire'] > 0) ?
                                ('Your ban will expire ' . date(TINYIB_DATEFORMAT, $ban['expire'])) :
                                'The ban on your IP address is permanent and will not expire.'
                        ;
                        $reason = ($ban['reason'] == '') ?
                                '' :
                                ('<br>The reason provided was: ' . $ban['reason'])
                        ;
                        fancyDie('Sorry, it appears that you have been banned from posting on this image board.  ' . $expire . $reason);
                } else {
                        clearExpiredBans();
                }
        }
}
function checkFlood() {
        $lastpost = lastPostByIP();
        if ($lastpost) {
                if ((time() - $lastpost['timestamp']) < TINYIB_RATELIMIT) {
                        fancyDie(
                                'Please wait a moment before posting again. '.
                                ' You will be able to make another post in ' .
                                (TINYIB_RATELIMIT - (time() - $lastpost['timestamp'])) .
                                " second(s)."
                        );
                }
        }
}
function checkMessageSize() {
        if (strlen($_POST["message"]) > TINYIB_MAXPOSTSIZE) {
                fancyDie(
                        'Your message is ' . strlen($_POST["message"]) .
                        ' characters long, but the maximum allowed is '.TINYIB_MAXPOSTSIZE.
                        '.<br>Please shorten your message, or post it in multiple parts.'
                );
        }
}
function manageCheckLogIn() {
        $loggedin = false; $isadmin = false;
        if (isset($_POST['password'])) {
                if ($_POST['password'] == TINYIB_ADMINPASS) {
                        $_SESSION['tinyib'] = TINYIB_ADMINPASS;
                } elseif (TINYIB_MODPASS != '' && $_POST['password'] == TINYIB_MODPASS) {
                        $_SESSION['tinyib'] = TINYIB_MODPASS;
                }
        }
        if (isset($_SESSION['tinyib'])) {
                if ($_SESSION['tinyib'] == TINYIB_ADMINPASS) {
                        $loggedin = true;
                        $isadmin = true;
                } elseif (TINYIB_MODPASS != '' && $_SESSION['tinyib'] == TINYIB_MODPASS) {
                        $loggedin = true;
                }
        }
        return array($loggedin, $isadmin);
}
function setParent() {
        if (isset($_POST["parent"])) {
                if ($_POST["parent"] != "0") {
                        if (!threadExistsByID($_POST['parent'])) {
                                fancyDie("Invalid parent thread ID - unable to create post.");
                        }                      
                        return $_POST["parent"];
                }
        }      
        return "0";
}
function validateFileUpload() {
        switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_OK:
                        break;
                case UPLOAD_ERR_FORM_SIZE:
                        fancyDie("That file is larger than 2 MB.");
                        break;
                case UPLOAD_ERR_INI_SIZE:
                        fancyDie("The uploaded file exceeds the upload_max_filesize directive (" . ini_get('upload_max_filesize') . ") in php.ini.");
                        break;
                case UPLOAD_ERR_PARTIAL:
                        fancyDie("The uploaded file was only partially uploaded.");
                        break;
                case UPLOAD_ERR_NO_FILE:
                        fancyDie("No file was uploaded.");
                        break;
                case UPLOAD_ERR_NO_TMP_DIR:
                        fancyDie("Missing a temporary folder.");
                        break;
                case UPLOAD_ERR_CANT_WRITE:
                        fancyDie("Failed to write file to disk");
                        break;
                default:
                        fancyDie("Unable to save the uploaded file.");
        }
}
function checkDuplicateImage($hex) {
        $hexmatches = postsByHex($hex);
        if (count($hexmatches) > 0) {
                foreach ($hexmatches as $hexmatch) {
                        $location = ($hexmatch['parent']=='0') ? $hexmatch['id'] : $hexmatch['parent'];
                        fancyDie(
                                'TIME PARADOX! That file has already been posted '.
                                '<a href="?do=thread&id='.$location.'#'.$hexmatch['id'].'">here</a>.
                                <br>'
                        );
                }
        }
}
function thumbnailDimensions($width, $height, $is_reply) {
        if ($is_reply) {
                $max_h = TINYIB_REPLYHEIGHT;
                $max_w = TINYIB_REPLYWIDTH;
        } else {
                $max_h = TINYIB_THUMBHEIGHT;
                $max_w = TINYIB_THUMBWIDTH;
        }
        return ($width > $max_w || $height > $max_h) ? array($max_w, $max_h) : array($width, $height);
}
function createThumbnail($name, $filename, $new_w, $new_h) {
	$system = explode(".", $filename);
	$system = array_reverse($system);
	if (preg_match("/jpg|jpeg/", $system[0])) {
		$src_img = imagecreatefromjpeg($name);
	} else if (preg_match("/png/", $system[0])) {
		$src_img = imagecreatefrompng($name);
	} else if (preg_match("/gif/", $system[0])) {
		$src_img = imagecreatefromgif($name);
	} else {
		return false;
	}

	if (!$src_img) {
		fancyDie("Unable to read uploaded file during thumbnailing. A common cause for this is an incorrect extension when the file is actually of a different type.");
	}
	$old_x = imageSX($src_img);
	$old_y = imageSY($src_img);
	$percent = ($old_x > $old_y) ? ($new_w / $old_x) : ($new_h / $old_y);
	$thumb_w = round($old_x * $percent);
	$thumb_h = round($old_y * $percent);

	$dst_img = imagecreatetruecolor($thumb_w, $thumb_h);
	if (preg_match("/png/", $system[0]) && imagepng($src_img, $filename)) {
		imagealphablending($dst_img, false);
		imagesavealpha($dst_img, true);

		$color = imagecolorallocatealpha($dst_img, 0, 0, 0, 0);
		imagefilledrectangle($dst_img, 0, 0, $thumb_w, $thumb_h, $color);
		imagecolortransparent($dst_img, $color);

		imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
	} else {
		fastimagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
	}

	if (preg_match("/png/", $system[0])) {
		if (!imagepng($dst_img, $filename)) {
			return false;
		}
	} else if (preg_match("/jpg|jpeg/", $system[0])) {
		if (!imagejpeg($dst_img, $filename, 70)) {
			return false;
		}
	} else if (preg_match("/gif/", $system[0])) {
		if (!imagegif($dst_img, $filename)) {
			return false;
		}
	}

	imagedestroy($dst_img);
	imagedestroy($src_img);

	return true;
}

function fastimagecopyresampled(&$dst_image, &$src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
	// Author: Tim Eckel - Date: 12/17/04 - Project: FreeRingers.net - Freely distributable.
	if (empty($src_image) || empty($dst_image)) {
		return false;
	}

	if ($quality <= 1) {
		$temp = imagecreatetruecolor($dst_w + 1, $dst_h + 1);

		imagecopyresized($temp, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w + 1, $dst_h + 1, $src_w, $src_h);
		imagecopyresized($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $dst_w, $dst_h);
		imagedestroy($temp);
	} elseif ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
		$tmp_w = $dst_w * $quality;
		$tmp_h = $dst_h * $quality;
		$temp = imagecreatetruecolor($tmp_w + 1, $tmp_h + 1);

		imagecopyresized($temp, $src_image, $dst_x * $quality, $dst_y * $quality, $src_x, $src_y, $tmp_w + 1, $tmp_h + 1, $src_w, $src_h);
		imagecopyresampled($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $tmp_w, $tmp_h);
		imagedestroy($temp);
	} else {
		imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
	}

	return true;
}
function redirect($url='?do=page&p=0') {
        header('Location: '.$url);
        die();
}
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
// SQLite PDO Helper
function fetchAndExecute($sql, $parameters=array()) {
        $stmt = $GLOBALS['db']->prepare($sql);
        $stmt->execute($parameters);
        $results = $stmt->fetchAll();
        return $results;
}
function uniquePosts() {
        $result = fetchAndExecute(
                'SELECT COUNT(ip) c FROM (SELECT DISTINCT ip FROM '.TINYIB_DBPOSTS.')',
                array()
        );
        return $result[0]['c'];
}
function postByID($id) {
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE id=? LIMIT 1',
                array(intval($id))
        );
        if (count($result)) return $result[0];
}
function insertPost($post) {
        $result = fetchAndExecute('
                INSERT INTO '.TINYIB_DBPOSTS.' (
                        parent, timestamp, bumped, ip, name, tripcode, email, nameblock,
                        subject, message, password, file, file_hex, file_original,
                        file_size, file_size_formatted, image_width, image_height,
                        thumb, thumb_width, thumb_height
                ) VALUES (
                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )',
                array(
                        $post['parent'], time(), time(), $_SERVER['REMOTE_ADDR'],
                        $post['name'], $post['tripcode'], $post['email'], $post['nameblock'],
                        $post['subject'], $post['message'], $post['password'],
                        $post['file'], $post['file_hex'], $post['file_original'],
                        $post['file_size'], $post['file_size_formatted'],
                        $post['image_width'], $post['image_height'], $post['thumb'],
                        $post['thumb_width'], $post['thumb_height']
                )
        );
        return $GLOBALS['db']->lastInsertId();
}
function countPosts() {
        $result = fetchAndExecute(
                'SELECT COUNT(*) c FROM '.TINYIB_DBPOSTS.'',
                array()
        );
        return $result[0]['c'];
}
function latestPosts($count) {
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBPOSTS.' ORDER BY id DESC LIMIT '.intval($count),
                array()
        );
        return $result;
}
function postsByHex($hex) {    
        $result = fetchAndExecute(
                'SELECT id,parent FROM '.TINYIB_DBPOSTS.' WHERE file_hex=? LIMIT 1',
                array($hex)
        );
        return $result;
}
function deletePostByID($id) { 
        $posts = postsInThreadByID($id);
        foreach ($posts as $post) {
                if ($post['id'] != $id) {
                        deletePostImages($post);
                        fetchAndExecute('DELETE FROM '.TINYIB_DBPOSTS.' WHERE id = ?', array($post['id']));
                } else {
                        $thispost = $post;
                }
        }
        if (isset($thispost)) {
                /*if ($thispost['parent'] == 0) {
                        @unlink('res/' . $thispost['id'] . '.html');
                }*/
                deletePostImages($thispost);
                fetchAndExecute('DELETE FROM '.TINYIB_DBPOSTS.' WHERE id = ?', array($thispost['id']));
        }
}
function postsInThreadByID($id) {      
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE id=? OR parent=? ORDER BY id ASC',
                array($id, $id)
        );
        return $result;
}
function latestRepliesInThreadByID($id) {
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE parent = ? ORDER BY id DESC LIMIT '.TINYIB_REPLIESTOSHOW,
                array(intval($id))
        );
        return $result;
}
function lastPostByIP() {      
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE ip=? ORDER BY id DESC LIMIT 1',
                array($_SERVER['REMOTE_ADDR'])
        );
        if (count($result)) return $result[0];
}
function threadExistsByID($id) {
        $result = fetchAndExecute(
                'SELECT COUNT(id) c FROM '.TINYIB_DBPOSTS.' WHERE id=? AND parent=? LIMIT 1',
                array(intval($id), 0)
        );
        return $result[0]['c'];
}
function bumpThreadByID($id) {
        fetchAndExecute(
                'UPDATE '.TINYIB_DBPOSTS.' SET bumped = ? WHERE id = ?',
                array( time(), intval($id) )
        );
}
function countThreads() {
        $result = fetchAndExecute(
                'SELECT COUNT(id) c FROM '.TINYIB_DBPOSTS .' WHERE parent = ?',
                array(0)
        );
        return $result[0]['c'];
}
function getThreadRange($count, $offset) {
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBPOSTS.' WHERE parent = ? ORDER BY bumped DESC LIMIT '.intval($offset).','.intval($count),
                array(0)
        );
        return $result;
}
function trimThreads() {
        if (TINYIB_MAXTHREADS > 0) {
                $result = fetchAndExecute(
                        'SELECT id FROM '.TINYIB_DBPOSTS.' WHERE parent = ? ORDER BY bumped DESC LIMIT '.TINYIB_MAXTHREADS.',10',
                        array(0)
                );
                foreach ($result as $post) {
                        deletePostByID($post['id']);
                }
        }
}
function banByID($id) {
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBBANS.' WHERE id=? LIMIT 1',
                array($id)
        );
        if (count($result)) return $result[0];
}
function banByIP($ip) {
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBBANS.' WHERE ip=? LIMIT 1',
                array($ip)
        );
        if (count($result)) return $result[0];
}
function allBans() {
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBBANS.' ORDER BY timestamp DESC',
                array()
        );
        return $result;
}
function insertBan($ban) {
        $result = fetchAndExecute(
                'INSERT INTO '.TINYIB_DBBANS.' (ip, timestamp, expire, reason) VALUES (?, ?, ?, ?)',
                array($ban['ip'], time(), $ban['expire'], $ban['reason'])
        );
        return $GLOBALS['db']->lastInsertId();
}
function clearExpiredBans() {
        $result = fetchAndExecute(
                'SELECT * FROM '.TINYIB_DBBANS.' WHERE expire > 0 AND expire <= ?',
                array(time())
        );
        foreach ($result as $ban) deleteBanByID($ban['id']);
}
function deleteBanByID($id) {
        fetchAndExecute('DELETE FROM '.TINYIB_DBBANS.' WHERE id=?', array($id));
}
function isLocked($thread) {
        $result = fetchAndExecute(
                'SELECT COUNT(*) c FROM '.TINYIB_DBLOCKS.' WHERE thread=? LIMIT 1',
                array($thread)
        );
        return $result[0]['c'];
}
function lockThread($thread) {
        if (isLocked($thread)) return;
        fetchAndExecute('INSERT INTO '.TINYIB_DBLOCKS.' (thread) VALUES (?)', array($thread));
}
function unlockThread($thread) {
        if (! isLocked($thread)) return;
        fetchAndExecute('DELETE FROM '.TINYIB_DBLOCKS.' WHERE thread=?', array($thread));
}
function getAllLocks() {
        $result = fetchAndExecute(
                'SELECT thread FROM '.TINYIB_DBLOCKS.';',
                array()
        );
        $ret = array();
        foreach($result as $r) $ret[] = $r['thread'];
        return $ret;
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
////////////////////////////////////////////////////////////////////////////////
function handleManage() {
        global $redirect;  $redirect = false;
        //global $loggedin;  $loggedin = false;
        //global $isadmin;   $isadmin  = false;
        $text = "";
        //list($loggedin, $isadmin) = manageCheckLogIn();
        if (! isset($_GET['p']) ) {
                redirect('?do=manage&p=home');
        }
        if (! LOGGED_IN) {
                $text .= manageLogInForm();
                die( managePage($text) );
        }
        switch($_GET['p']) {
                case 'bans': {
                        if (! IS_ADMIN) redirect('?do=manage&p=home');
                        clearExpiredBans();
                        if (isset($_POST['ip'])) {
                                if ($_POST['ip'] != '') {
                                        $banexists = banByIP($_POST['ip']);
                                        if ($banexists) {
                                                fancyDie('There is already a ban on record for that IP address.');
                                        }
                                        $ban = array();
                                        $ban['ip'] = $_POST['ip'];
                                        $ban['expire'] = ($_POST['expire'] > 0) ? (time() + $_POST['expire']) : 0;
                                        $ban['reason'] = $_POST['reason'];
                                        insertBan($ban);
                                        $text .= '<b>Successfully added a ban record for ' . $ban['ip'] . '</b><br>';
                                }
                        } elseif (isset($_GET['lift'])) {
                                $ban = banByID($_GET['lift']);
                                if ($ban) {
                                        deleteBanByID($_GET['lift']);
                                        $text .= '<b>Successfully lifted ban on ' . $ban['ip'] . '</b><br>';
                                }
                        }
                        $text .= manageBanForm();
                        $text .= manageBansTable();                            
                } break;
                case 'delete': {
                        $post = postByID($_GET['delete']);
                        if ($post) {
                                deletePostByID($post['id']);
                                $text .= '<b>Post No.' . $post['id'] . ' successfully deleted.</b>';
                        } else {
                                fancyDie("Sorry, there doesn't appear to be a post with that ID.");
                        }
                } break;
                case 'moderate': {
                        if (isset($_GET['moderate']) && $_GET['moderate'] > 0) {
                                $post = postByID($_GET['moderate']);
                                if ($post) {
                                        $text .= manageModeratePost($post);
                                } else {
                                        fancyDie("Sorry, there doesn't appear to be a post with that ID.");
                                }
                        } else {
                                $text .= manageModeratePostForm();
                        }
                } break;
                case 'bump': {
                        if (! isset($_GET['id'])) fancyDie('Invalid request.');
                        bumpThreadByID( intval($_GET['id']) );
                        redirect('?do=manage&p=threads');
                } break;
                case 'logout': {
                        $_SESSION['tinyib'] = '';
                        session_destroy();
                        redirect('?do=manage&p=login');
                } break;
                case 'home': {
                        $text .=
                                'Currently '.countPosts().' posts in '.countThreads().
                                ' threads, made by '.uniquePosts().' users.<br>'.
                                'There are '. count(allBans()).' ban(s).'
                        ;
                } break;
                case 'threads': {
                        $text = manageAllThreads();
                } break;
                default: {
                        fancyDie('Invalid request.');
                } break;
        }
        return managePage($text);
}
////////////////////////////////////////////////////////////////////////////////
function handleDeletePost() {
        global $redirect;
        if (! isset($_GET['id']) || ! is_numeric($_GET['id'])) {
                fancyDie('No post was selected.');
        }
        $post = postByID($_GET['id']);
        //list($loggedin, $isadmin) = manageCheckLogIn();
        if (LOGGED_IN || (
                (time() - $post['timestamp'] < TINYIB_DELETE_TIMEOUT) &&
                ($post['ip'] == $_SERVER['REMOTE_ADDR'])
        )) {
                if (isset($_GET['force']) && $_GET['force'] == '1') {
                        deletePostByID($post['id']);
                        fancyDie('Post successfully deleted.', 2);
                } else {
                        fancyDie(
                                'Are you sure you want to delete post #'.$post['id']."?\n".
                                (($post['parent'])?'':"Deleting this post will delete the entire thread.\n").
                                'Click <a href="?do=delpost&id='.$post['id'].'&force=1">here</a> to confirm.'
                        );
                }
        } else {
                fancyDie('You have '.TINYIB_DELETE_TIMEOUT.' seconds to delete your own posts.');
        }
        $redirect = false;
}
////////////////////////////////////////////////////////////////////////////////
function handlePost() {
        global $redirect;// global $loggedin; global $isadmin;
        // Validate request
        if (!(isset($_POST["message"]) || isset($_POST["file"]))) {
                fancyDie('Invalid request');
        }
        // Validate user
        if (! LOGGED_IN) {
                checkBanned();
                checkMessageSize();
                checkFlood();
        }
        // Get options
        $modpost = (LOGGED_IN && isset($_POST['modpost']));
        $rawhtml = (LOGGED_IN && isset($_POST['rawhtml']));
        $bump    = (isset($_POST['bump']));    
        // Validate captcha if necessary
        if (TINYIB_USECAPTCHA && ! LOGGED_IN) {
                if (@$_POST['captcha_ex'] != md5(TINYIB_CAPTCHASALT . @$_POST['captcha_out'])) {
                        fancyDie('You appear to have mistyped the verification.');
                }
        }
        $post = newPost();
        $post['parent'] = setParent();
        $post['ip'] = $_SERVER['REMOTE_ADDR'];
        list($post['name'], $post['tripcode']) = nameAndTripcode($_POST["name"]);
        $post['name'] = cleanString(substr($post['name'], 0, 75));
        $post['email'] = ''; // Deprecated
        $post['subject'] = isset($_POST['subject']) ? cleanString(substr($_POST["subject"], 0, 75)) : '';
        $post['password'] = ''; // Deprecated
        // Options
        if ($modpost) {
                $modposttext = IS_ADMIN ? ' <span class="moderator">## Admin</span>' : ' <span class="moderator">## Mod</span>';               
        } else {
                $modposttext = '';             
        }
        if ($rawhtml) {
                $post['message'] = $_POST["message"];
        } else {
                $post['message'] = str_replace("\n", "<br>", colorQuote(postLink(cleanString(rtrim($_POST["message"])))));
        }
        $post['nameblock'] = nameBlock($post['name'], $post['tripcode'], $post['email'], time(), $modposttext);
        // Manage file uploads
        if (isset($_FILES['file'])) {
                if ($_FILES['file']['name'] != "") {
                        validateFileUpload();
                        if (!is_file($_FILES['file']['tmp_name']) || !is_readable($_FILES['file']['tmp_name'])) {
                                fancyDie("File transfer failure. Please retry the submission.");
                        }
                        $post['file_original'] = substr(htmlentities($_FILES['file']['name'], ENT_QUOTES), 0, 50);
                        $post['file_hex'] = md5_file($_FILES['file']['tmp_name']);
                        $post['file_size'] = $_FILES['file']['size'];
                        $post['file_size_formatted'] = convertBytes($post['file_size']);
                        $file_type = strtolower(preg_replace('/.*(\..+)/', '\1', $_FILES['file']['name']));
                        if ($file_type == '.jpeg') { $file_type = '.jpg'; }
                        $file_name = time() . mt_rand(1, 99);
                        $post['thumb'] =  "thumb_" .  $file_name .$file_type;
                        $post['file'] = $file_name . $file_type;
                        $thumb_location = "db/" . $post['thumb'];
                        $file_location = "db/" . $post['file'];
                        if (!($file_type == '.jpg' || $file_type == '.gif' || $file_type == '.png')) {
                                fancyDie("Only GIF, JPG, and PNG files are allowed.");
                        }
                        if (!@getimagesize($_FILES['file']['tmp_name'])) {
                                fancyDie("Failed to read the size of the uploaded file. Please retry the submission.");
                        }
                        $file_info = getimagesize($_FILES['file']['tmp_name']);
                        $file_mime = $file_info['mime'];
                        if (!($file_mime == "image/jpeg" || $file_mime == "image/gif" || $file_mime == "image/png")) {
                                fancyDie("Only GIF, JPG, and PNG files are allowed.");
                        }
                        checkDuplicateImage($post['file_hex']);
                        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_location)) {
                                fancyDie("Could not store uploaded file.");
                        }
                        if ($_FILES['file']['size'] != filesize($file_location)) {
                                fancyDie("File transfer failure. Please go back and try again.");
                        }
                        $post['image_width'] = $file_info[0]; $post['image_height'] = $file_info[1];
                        list($thumb_maxwidth, $thumb_maxheight) = thumbnailDimensions(
                                $post['image_width'], $post['image_height'], $post['parent'] != '0'
                        );
                        if (!createThumbnail($file_location, $thumb_location, $thumb_maxwidth, $thumb_maxheight)) {
                                fancyDie("Could not create thumbnail.");
                        }
                        $thumb_info = getimagesize($thumb_location);
                        $post['thumb_width'] = $thumb_info[0]; $post['thumb_height'] = $thumb_info[1];
                }
        }
if (!CLAIRE_TEXTMODE) {
        if ($post['file'] == '') { // No file uploaded
                if ($post['parent'] == '0') {
                        fancyDie("An image is required to start a thread.");}}}
                if (str_replace('<br>', '', $post['message']) == "") {
                        fancyDie("Please enter a message.");
                }
        $post['id'] = insertPost($post);
        $redirect = '?do=thread&id=' . ($post['parent']=='0' ? $post['id'] : $post['parent']) . '#'. $post['id'];
        trimThreads();
        if ($post['parent'] != '0' && $bump) bumpThreadByID($post['parent']);
}