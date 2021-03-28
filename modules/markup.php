<?php

function setupMarkup($settings){
    define('MARKUP_ENABLED_TAGS', $settings['markup_enabled_tags']);
}

function processMarkup($post){
    $post = processBold($post);
    $post = processStrike($post);
    $post = processItalic($post);
    $post = processUnderline($post);
    $post = processSpoiler($post);
    $post = processMonotype($post);
    $post['message'] = processHeader($post['message']);
    return $post;
}

function processHeader($post){
    if(MARKUP_ENABLED_TAGS['header'])
        $post = preg_replace("#\[h\](.*?)\[/h\]#","<h3>\\1</h3>",$post);
        $post = preg_replace("#\#\#\#(.*?)\#\#\##","<h3>\\1</h3>",$post);
    return $post;
}

function processMonotype($post){
    if(MARKUP_ENABLED_TAGS['monotype'])
        $post = preg_replace("#\'\'(.*?)\'\'#","<span class=\"monotype\">\\1</span>",$post);
        $post = preg_replace("#\`(.*?)\`#","<span class=\"monotype\">\\1</span>",$post);
        $post = preg_replace("#\[code\](.*?)\[/code\]#","<span class=\"monotype\">\\1</span>",$post);
    return $post;
}

function processSpoiler($post){
    if(MARKUP_ENABLED_TAGS['spoiler'])
        $post = preg_replace("#\%\%(.*?)\%\%#","<span class=\"spoiler\">\\1</span>",$post);
        $post = preg_replace("#\[spoiler\](.*?)\[/spoiler\]#","<span class=\"spoiler\">\\1</span>",$post);
    return $post;
}

function processUnderline($post){
    if(MARKUP_ENABLED_TAGS['underline'])
        $post = preg_replace("#\[u\](.*?)\[/u\]#","<span class=\"underline\">\\1</span>",$post);
        $post = preg_replace("#\_\_(.*?)\_\_#","<span class=\"underline\">\\1</span>",$post);
    return $post;
}

function processItalic($post){
    if(MARKUP_ENABLED_TAGS['italic'])
        $post = preg_replace("#\*(.*?)\*#","<span class=\"italic\">\\1</span>",$post);
        $post = preg_replace("#\[i\](.*?)\[/i\]#","<span class=\"italic\">\\1</span>",$post);
    return $post;
}

function processStrike($post){
    if(MARKUP_ENABLED_TAGS['strike'])
        $post = preg_replace("#\[s\](.*?)\[/s\]#","<span class=\"strike\">\\1</span>",$post);
        $post = preg_replace("#\~\~(.*?)\~\~#","<span class=\"strike\">\\1</span>",$post);
    return $post;
}

function processBold($post){
    if(MARKUP_ENABLED_TAGS['bold'])
        $post = preg_replace("#\*\*(.*?)\*\*#",'<span class="bold">\\1</span>',$post);
        $post = preg_replace("#\[b\](.*?)\[/b\]#",'<span class="bold">\\1</span>',$post);
    return $post;
}

?>
