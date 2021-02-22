<fieldset>
                <legend>Moderating post #<?post_id?></legend>              
                <div class="floatpost">
                        <fieldset>
                                <legend><?post_type?></legend>       
                                <?post_html?>
                        </fieldset>
                </div>         
                <fieldset>
                        <legend>Action</legend>                                
                        <form method="get" action="?">
                                <input type="hidden" name="do" value="manage" />
                                <input type="hidden" name="p" value="delete" />
                                <input type="hidden" name="delete" value="<?post_id?>" />
                                <input type="submit" value="Delete <?post_type?>" class="managebutton" />
                        </form>
                        <br/>
                        <form method="get" action="?">
                                <input type="hidden" name="do" value="manage" />
                                <input type="hidden" name="p"  value="bans" />
                                <input type="hidden" name="bans" value="<?post_ip?>" />
                                <input type="submit" value="Ban Poster" class="managebutton"<?ban_disabled?> /><?ban_disabled_info?>
                        </form>
                </fieldset>    
        </fieldset>
        <br />