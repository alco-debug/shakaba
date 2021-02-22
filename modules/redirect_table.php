<?php
$GLOBALS['redirect_table'] = [
    '/b' => '?do=home',
    '/' => '?do=home',
];

function redirect_by_table($uri){
    $uri_copy = strtolower($uri);
    foreach ($GLOBALS['redirect_table'] as $key => $value){
        if($uri_copy == $key){
            return $value;
        }
    }
    return $uri_copy;
}

?>