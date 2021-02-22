<?php
function adminBar() {
        if (! LOGGED_IN) { return '[<a href="?do=page">Return</a>]'; }
        $text = IS_ADMIN ? '[<a href="?do=manage&p=bans">Bans</a>] ' : '';
        $text .= renderTemplate('admin_bar', []);
        return $text;
}
function managePage($text) {
        $admin_bar = adminBar();
        $data = [
            'admin_bar' => $admin_bar,
            'text' => $text,
            ];
        $body = renderTemplate('manage_page', $data);
        return pageHeader() . $body . pageFooter();
}
function manageLogInForm() {
        return renderTemplate('login_form', []);
}
function manageBanForm() {
        $banstr = isset($_GET['bans']) ? $_GET['bans'] : '';
        return renderTemplate('ban_form', ['banstr' => $banstr]);
}
function manageBansTable() {
        $text = '';
        $allbans = allBans();
        if (count($allbans) > 0) {
                $text .= '<table border="1"><tr><th>IP Address</th><th>Set At</th><th>Expires</th><th>Reason Provided</th><th>&nbsp;</th></tr>';
                foreach ($allbans as $ban) {
                        $expire = ($ban['expire'] > 0) ? date('y/m/d(D)H:i:s', $ban['expire']) : 'Never';
                        $reason = ($ban['reason'] == '') ? '&nbsp;' : htmlentities($ban['reason']);
                        $text .= '<tr><td>' . $ban['ip'] . '</td><td>' . date('y/m/d(D)H:i:s', $ban['timestamp']) . '</td><td>' . $expire . '</td><td>' . $reason . '</td><td><a href="?do=manage&p=bans&lift=' . $ban['id'] . '">lift</a></td></tr>';
                }
                $text .= '</table>';
        }
        return $text;
}
function manageModeratePostForm() {
        return renderTemplate('moderate_post_form', []);
}
function manageModeratePost($post) {
        $ban = banByIP($post['ip']);
        $ban_disabled = (!$ban && IS_ADMIN) ? '' : ' disabled';
        $ban_disabled_info = (!$ban) ? '' : (' A ban record already exists for ' . $post['ip']);
        $post_html = buildPost($post, true);
        $post_or_thread = ($post['parent'] == 0) ? 'Thread' : 'Post';
        $post_type = ($post['parent'] == 0) ? 'Thread' : 'Post';
        $post_id = $post['id'];
        $post_ip = $post['ip'];
        $data = [
            'ban_disabled' => $ban_disabled,
            'ban_disabled_info' => $ban_disabled_info,
            'post_id' => $post_id,
            'post_ip' => $post_ip,
            'post_type' => $post_type,
            'post_html' => $post_html,
            ];
        return renderTemplate('moderate_post', $data);
}
function manageAllThreads() {
        $threads = getThreadRange(10000, 0);
        $locks   = getAllLocks();
        $ret = renderTemplate('all_threads_header', []);
        foreach($threads as $thread) {
                $locked = in_array($thread['id'], $locks);
                // Workaround for incorrectly imported history
                $bump = ($thread['bumped'] > BUMPLIMIT ? date(TINYIB_DATEFORMAT,$thread['bumped']) : '-');
                $thread_id = $thread['id'];
                $thread_subject = $thread['subject'];
                $thread_message = htmlspecialchars(substr($thread['message'], 0, 60));
                $thread_created = date(TINYIB_DATEFORMAT, $thread['timestamp']);
                $thread_locked = $locked ? 'Locked' : '-';
                $data = [
                    'thread_id' => $thread_id,
                    'thread_subject' => $thread_subject,
                    'thread_message' => $thread_message,
                    'thread_created' => $thread_created,
                    'bump' => $bump,
                    'thread_locked' => $thread_locked,
                    ];
                $ret .= renderTemplate('all_threads_thread', $data);
        }
        $ret .= renderTemplate('all_threads_footer', []);
        return $ret;
}
?>