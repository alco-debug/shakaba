<?php
function pageHeader() {
    $header_tpl_fname = 'page_header';
    $style = renderTemplate('mainstyle.css', []);
    $data = [
        'page_title' => TINYIB_PAGETITLE,
        'style' => $style,
        ];
    $return = renderTemplate($header_tpl_fname, $data);
    return $return;
}
function pageFooter() {
    return <<<EOF
</div>
    </div>
    </body>
</html>
EOF;
}

function getPageLinks($pages=0, $thispage=0){
    $pages = max($pages, 0);
            $pagelinks =
                    ($thispage == 0) ?
                    "[ Previous ]" :
                    '[ <a href="?do=page&p=' .($thispage-1). '">Previous</a> ]'
            ;              
            for ($i = 0;$i <= $pages;$i++) {
                    $pagelinks .= ($thispage == $i) ? "[ $i ]" : "[ <a href=\"?do=page&p=$i\">$i</a> ]";
            }              
            $pagelinks .= ($pages <= $thispage) ?
                    "[ Next ]" :
                    '[ <a href="?do=page&p='.($thispage+1). '">Next</a> ]'
            ;
    return $pagelinks;
}

function getReturnLink($parent, $locked){
    $returnlink = '<span class="replylink">[<a href="?do=page">Return</a>';
            if (LOGGED_IN) {
                    if ($locked) {
                            $returnlink .= ' | <a href="?do=lock&id='.$parent.'">Unlock Thread</a>';
                    } else {
                            $returnlink .= ' | <a href="?do=lock&id='.$parent.'">Lock Thread</a>';                         
                    }
            }
            $returnlink .= ']</span>';
    return $returnlink;
}

function buildPage($htmlposts, $parent, $pages=0, $thispage=0) {
    $locked = $parent ? isLocked($parent) : false;
    $returnlink = ''; $pagelinks = '';
    $thread_is_locked = '';
    if ($parent == 0) {
            $pagelinks = getPageLinks($pages, $thispage);
            $thread_is_locked = '<hr>';
    } else {
            $returnlink = getReturnLink($parent, $locked);
    }
    if ($locked) {
            $thread_is_locked = '<div class="replymode">This thread is locked. You can\'t reply any more.</div>';
            $postblock = '';
    }
    if (!$locked) {
            $postblock = buildPostBlock($parent);
    }
    if(MAINTENANCE_MODE){
            $maintenance_message = maintenanceMessage();
    }else{
            $maintenance_message = '';
    }
    $data = [
            'header' => pageHeader(),
            'page_title' => TINYIB_PAGETITLE,
            'maintenance_message' => $maintenance_message,
            'postblock' => $postblock,
            'pagelinks' => $pagelinks,
            'thread_is_locked' => $thread_is_locked,
            'returnlink' => $returnlink,
            'htmlposts' => $htmlposts,
            'footer' => pageFooter(),
    ];
    return renderTemplate('page', $data);
}
function viewPage($pagenum) {
    $page = intval($pagenum);
    $pagecount = max(0, ceil(countThreads() / TINYIB_THREADSPERPAGE) - 1);
    if (!is_numeric($pagenum) || $page < 0 || $page > $pagecount) fancyDie('Invalid page number.');
    $htmlposts = array();
    $threads = getThreadRange(TINYIB_THREADSPERPAGE, $pagenum * TINYIB_THREADSPERPAGE );
    foreach ($threads as $thread) {
            $replies = latestRepliesInThreadByID($thread['id']);
            $htmlreplies = array();
            foreach ($replies as $reply) {
                    $htmlreplies[] = buildPost($reply, False);
            }
            $thread["omitted"] = (count($htmlreplies) == 3) ? (count(postsInThreadByID($thread['id'])) - 4) : 0;
            $htmlposts[] = buildPost($thread, false) . implode("", array_reverse($htmlreplies)) . "<br clear=\"left\">\n<hr>";
    }
    return buildPage(implode('', $htmlposts), 0, $pagecount, $page);
}
function viewThread($id) {
    $htmlposts = array();
    $posts = postsInThreadByID($id);
    foreach ($posts as $post) $htmlposts[] = buildPost($post, True);
    $htmlposts[] = "<br clear=\"left\">\n<hr>";
    return buildPage(implode('',$htmlposts), $id);
}

function viewBoards(){
    $boards_table = getBoardsTable();
    $table = '<table>';
    foreach($boards_table as $brd => $desc){
            $table .= '<tr><td><a href="'.$brd.'">'.$brd.'</a></td><td></td><td>'.$desc.'</td></tr>';
    }
    $table .= '</table>';
    return $table;
}

?>