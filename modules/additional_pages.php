<?php
function maintenanceMessage(){
    $message = 'ШАЙТАН ШАТАТЬ ШАКАБУ. STAY TUNA';
    return renderTemplate('maintenance', ['message' => $message]);
}
function homePageLinks(){
$tpl_name = 'homepage_link';
$data = [
        [
            'href' => 'https://uberchan.ru',
            'text' => 'Тот Самый Уберчан',
            ],
        [
            'href' => '?do=boards',
            'text' => 'Посетить Убер-чан (<i>убежище временное</i>)',
            ],
        [
            'href' => 'mailto:uberchan@tutanota.com',
            'text' => 'uberchan@tutanota.com',
            ],
    ];
$return = '';
foreach($data as $link_data){
    $return = $return.renderTemplate($tpl_name, $link_data)."\n";
}
return $return;
}
function homePageMascott(){
    $tpl_fname = 'mascott';
    $img_path = 'mascott.png';
    $img_alt = 'Убертян';
    $data = ['img_path' => $img_path, 'img_alt' => $img_alt];
    return renderTemplate($tpl_fname, $data);
}
function homePage() {
    $tpl_fname = 'homepage';
    $title = 'Welcome to Uber-chan!';
    $mascott = homePageMascott();
    $links = homePageLinks();
    $data = [
        'title' => $title,
        'mascott' => $mascott,
        'links' => $links,
        ];
    return renderTemplate($tpl_fname, $data);
}
function markupGuide(){
    $tpl_fname = 'markup';
    return pageHeader().renderTemplate($tpl_fname, []).pageFooter();
}
?>