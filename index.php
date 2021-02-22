<?php
session_start();if (!file_exists('db')){mkdir('db', 0777, true);}
error_reporting(E_ALL);

require 'config.php';
require MODULES_PATH.'modules.php'; setupModules(['modules_path' => MODULES_PATH]);

/// --Imports Start--
importModule('additional_pages');
importModule('boards');
importModule('helpers');
importModule('redirect_table');
importModule('templates');; setupTemplates(TEMPLATES_PATH);
importModule('view_page');
/// --Imports End--

function redirect($url='?do=page&p=0') {
        header('Location: '.$url);
        die();
}
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
        redirect(ROOT_URI.'?do=boards');
}
        
switch($_GET['do']) {
        case 'home': {
            uberDie(homePage());
        }break;
        case 'boards': {
            uberDie(fancyDie(viewBoards()));
        }break;
        default: {
            uberDie(fancyDie(viewBoards()));
        } break;
}
