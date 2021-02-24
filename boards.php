<?php
function getBoardsTable($path){
    $boards = [];
    $root_index = scandir($path);
    foreach($root_index as $fname){
        if(is_dir($fname)){
            $sub_index = scandir($fname);
            if(in_array('board_info.php', $sub_index)){
                include $fname.'/board_info.php';
                $board_name =  $GLOBALS['board_name'];
                if(substr($board_name, 0, 2) != '/_'){
                    $board_desc = $GLOBALS['board_desc'];
                    $boards[$board_name] = $board_desc;
                }
                
            }
        }
    }
    return $boards;
}

die(json_encode(getBoardsTable(getcwd())));

?>