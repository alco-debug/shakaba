<?php
function getBoardsTable(){
    $contents = file_get_contents(CURRENT_DOMAIN.'/boards.php');
    $boards = json_decode($contents, $assoc=true);
    return $boards;
}
?>