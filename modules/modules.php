<?php

$GLOBALS['modules_path'] = '';

function setupModules($settings){
    $GLOBALS['modules_path'] = $settings['modules_path'];
}

function importModule($module_name){
    require $GLOBALS['modules_path'].$module_name.'.php';
}

?>