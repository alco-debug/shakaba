<?php
require "markup.php";

function setupBuildPost($settings){
    define('MARKUP_ENABLED', $settings['markup_enabled']);
    setupMarkup(['markup_enabled_tags' => $settings['markup_enabled_tags']]);
}

function buildPost($post, $isrespage, $number_in_thread="") {
    if(MARKUP_ENABLED)
        $post = processMarkup($post);
    $threadid = getThreadID($post);
    $ban_poster = getBanPosterLink($post);
    $post_id = $post['id'];
    $post_header = getPostHeader($post, $threadid, $isrespage);
    $post_footer = getPostFooter($post, $isrespage);
    $post_attachment = getPostAttachment($post);
    $post_nameblock = str_replace("Anonymous", "", $post['nameblock']);
    $post_message = $post['message'];
    $post_subject = getPostSubject($post);
    $post_link = '?do=thread&id='.$threadid.'#'.$post['id'];
    
    $data = [
        'post_id' => $post_id,
        'post_header' => $post_header,
        'post_footer' => $post_footer,
        'post_subject' => $post_subject,
        'post_attachment' => $post_attachment,
        'post_nameblock' => $post_nameblock,
        'post_message' => $post_message,
        'ban_poster' => $ban_poster,
        'post_link' => $post_link,
        'post_number_in_thread' => $number_in_thread,
        ];
    
    
    return renderTemplate('post', $data);
}

function getImageDescription($post){
        $image_desc =
            cleanString($post['file_original']) .' ('.$post["image_width"].'x'.
            $post["image_height"].', '.$post["file_size_formatted"].')';
        return $image_desc;
}
function getPostLink($post, $threadid){
    return '?do=thread&id='.$threadid.'#'.$post['id'];
}
function getThreadID($post){
    return ($post['parent'] == 0) ? $post['id'] : $post['parent'];
}
function isOpenPost($post){
    return $post['parent'] == 0;
}
function postHasFile($post){
    return $post['file'] != '';
}
function postHasSubject($post){
    return $post['subject'] != '';
}
function getPostHeader($post, $threadid, $isrespage){
    $return = '';
    if (isOpenPost($post) && !$isrespage) {
                $note = isLocked($threadid) ? '<em>(locked)</em>' : ''; //&#x1f512;
                $data = [
                    'note' => $note,
                    'post_id' => $post['id'],
                    ];
                $return .= renderTemplate('thread_header', $data);
        }
        if (!isOpenPost($post)) {
                $data = ['post_id' => $post['id']];
                $return .= renderTemplate('usual_post_header', $data);
        }
        return $return;
}
function getPostFooter($post, $isrespage){
    $return = '';
    if (isOpenPost($post)) {
                if (!$isrespage && $post["omitted"] > 0) {
                        $data = [
                            'post_id' => $post['id'],
                            'posts_omitted' => $post['omitted'],
                            ];
                        $return .= renderTemplate('thread_footer', $data);
                }
        } else {
                $return .= renderTemplate('usual_post_footer', []);
        }
    return $return;
}
function getPostAttachment($post){
    $return = '';
    if (postHasFile($post)) {
                $data = [
                    'post_id' => $post['id'],
                    'post_thumb' => $post['thumb'],
                    'post_file' => $post['file'],
                    'image_desc' => getImageDescription($post),
                    'thumb_width' => $post['thumb_width'],
                    'thumb_height' => $post['thumb_height'],
                    ];
                $return .= renderTemplate('post_attachment', $data);
        }
    return $return;
}
function getPostSubject($post){
    $return = '';
    if (postHasSubject($post)) {
                $return .= renderTemplate('post_subject', ['post_subject' => $post['subject']]);
        }
    return $return;
}
function getBanPosterLink($post){
    $return = '';
    if (IS_ADMIN) {
                $data = [
                    'urlencode_post_ip' => urlencode($post['ip']),
                    'htmlspecialchars_post_ip' => htmlspecialchars($post['ip']),
                    ];
                $ban_poster = renderTemplate('ban_poster', $data);
                $return .= $ban_poster;
        }
    return $return;
}
?>
