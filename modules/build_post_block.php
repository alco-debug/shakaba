<?php
function buildPostBlockCaptcha(){
    $captcha_key = md5(mt_rand());
    $captcha_expect = md5(TINYIB_CAPTCHASALT.substr(md5($captcha_key),0,4));
    $data = [
        'captcha_key' => $captcha_key,
        'captcha_expect' => $captcha_expect,
        ];
    return renderTemplate('postblock_captcha', $data);
}
function buildPostBlockImage(){
    $return = renderTemplate('postblock_image', []);
    return $return;
}
function buildPostBlockSubject($parent){
    $return = renderTemplate('postblock_subject', []);
    return $return;
}
function buildPostBlock($parent) {
    $htmlspecialchars_parent = htmlspecialchars($parent);
    $postblock_subject = '';
    $postblock_image = '';
    $postblock_captcha = '';
    if (! $parent) {
        $postblock_subject = buildPostBlockSubject($parent);
        $body .= $postblock_subject;
    }
    if (TINYIB_USECAPTCHA && !LOGGED_IN) {
        $postblock_captcha = buildPostBlockCaptcha();
        $body .= $postblock_captcha;
    } 
    if (!CLAIRE_TEXTMODE) {
        $postblock_image = buildPostBlockImage();
        $body .= buildPostBlockImage();
    }
    $post_button_name = ($parent) ? 'Post Reply' : 'Create Thread';
    $opt_bump_thread = ($parent) ? '<label><input type="checkbox" name="bump" id="bump" checked>Обнять тредик</label>' : '';
    $opt_modpost = LOGGED_IN ? '<label><input type="checkbox" name="modpost" id="modpost">Modpost</label>' : '';
    $opt_rawhtml = IS_ADMIN ? '<label><input type="checkbox" name="rawhtml" id="rawhtml">RawHTML</label>' : '';
    $data = [
        'htmlspecialchars_parent' => $htmlspecialchars_parent,
        'postblock_subject' => $postblock_subject,
        'postblock_image' => $postblock_image,
        'postblock_captcha' => $postblock_captcha,
        'post_button_name' => $post_button_name,
        'opt_bump_thread' => $opt_bump_thread,
        'opt_modpost' => $opt_modpost,
        'opt_rawhtml' => $opt_rawhtml,
        ];
    return renderTemplate('postblock', $data);
}
?>