<?php

define('TEMPLATE_EXT', '.tpl');
define('TEMPLATE_EXP_START', '<?');
define('TEMPLATE_EXP_END', '?>');

$GLOBALS['templates_path'] = '';


function setupTemplates($templates_path){
    $GLOBALS['templates_path'] = $templates_path;
}

function getTemplateFullName($template_fname){
    return $GLOBALS['templates_path'].$template_fname.TEMPLATE_EXT;
}

function loadTemplate($template_fname){
    $fname = getTemplateFullName($template_fname);
    $handle = fopen($fname, 'r');
    $contents = fread($handle, filesize($fname));
    fclose($handle);
    return $contents;
}

function renderTemplate($template_fname, $data_arr){
    $template = loadTemplate($template_fname);
    foreach ($data_arr as $key => $val){
        $exp = TEMPLATE_EXP_START.$key.TEMPLATE_EXP_END;
        $template = str_replace($exp, $val, $template);
    }
    return $template;
}
?>