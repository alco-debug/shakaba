<?php
function cleanString($string) {
    return str_replace(array("<", ">", '"'), array("&lt;", "&gt;", "&quot;"), $string);
}

function convertBytes($number) {
    $len = strlen($number);
    if ($len <= 3) return sprintf("%dB",     $number);
    if ($len <= 6) return sprintf("%0.2fKB", $number/1024);
    if ($len <= 9) return sprintf("%0.2fMB", $number/1024/1024);
    return sprintf("%0.2fGB", $number/1024/1024/1024);                                             
}

function processAllLinks($string){
    $ret = str_replace('href="/', 'href="/'.ROOT_URI, $string);
    $ret = str_replace('href="?', 'href="'.ROOT_URI.'?', $ret);
    $ret = str_replace('href="?', 'href="'.ROOT_URI.'?', $ret);
    $ret = str_replace('action="', 'action="'.ROOT_URI, $ret);
    $ret = str_replace('href=<?root?>', 'href="/"', $ret);
    return $ret;
}

function fancyDie($message, $depth=1) {
    $ret = pageHeader();
    $ret .= renderTemplate('service_message', ['message' => str_replace("\n", '<br>', $message)]);
    $ret .= pageFooter();
    $ret = processAllLinks($ret);
    return die($ret);
}
function uberDie($string){
    if($string == ''){
        fancyDie('ШАЙТАН ШАТАТЬ ШАКАБУ. STAY TUNA');
    }else{
        if(MAINTENANCE_MODE){
            die(maintenanceMessage().processAllLinks($string));
        }
        else{
            die(processAllLinks($string));
        }
        
    }
}
?>