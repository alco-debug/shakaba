<?php
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
?>
