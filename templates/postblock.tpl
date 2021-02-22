<div id="postarea">
    <form name="postform" id="postform" action="?do=post" method="post" enctype="multipart/form-data">
    <input type="hidden" name="parent" value="<?htmlspecialchars_parent?>">
    <table class="postform">
            <tbody>
                    <tr>
                            <td class="postblock" title="Optional [#password]">Name</td>
                            <td>
                                    <input type="text" name="name" size="28" maxlength="75">
                            </td>
                    </tr>
                    <?postblock_subject?>
                    <tr>
                            <td class="postblock">Markup:</td>
                            <td ><a href="?do=markup">[Справка по разметке]</a></td>
                            </tr>
                    <tr>
                            <td class="postblock">Message</td>
                            <td>
                                    <textarea name="message" cols="48" rows="4" placeholder=""></textarea>
                            </td>
                    </tr>
                    <?postblock_image?>
                    <?postblock_captcha?>
                    <tr>
                            <td class="postblock"></td>
                            <td>
                                    <input type="submit" value="<?post_button_name?>">
                                    <?opt_bump_thread?>
                                    <?opt_modpost?>
                                    <?opt_rawhtml?>
                            </td>
                    </tr>
                </tbody>
        </table>
    </form>
</div>
<hr>