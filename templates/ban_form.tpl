<form id="tinyib" name="tinyib" method="post" action="?do=manage&p=bans">
                <fieldset>
                        <legend>Ban an IP address from posting</legend>
                        <label for="ip">IP Address:</label>
                        <input type="text" name="ip" id="ip" value="<?banstr?>" autofocus>
                        <input type="submit" value="Submit" class="managebutton">
                        <br/>
                        <label for="expire">Expire(sec):</label>
                        <input type="text" name="expire" id="expire" value="0">&nbsp;&nbsp;
                        <small>
                                <a href="#" onclick="document.tinyib.expire.value='3600';return false;">1hr</a>&nbsp;
                                <a href="#" onclick="document.tinyib.expire.value='86400';return false;">1d</a>&nbsp;
                                <a href="#" onclick="document.tinyib.expire.value='172800';return false;">2d</a>&nbsp;
                                <a href="#" onclick="document.tinyib.expire.value='604800';return false;">1w</a>&nbsp;
                                <a href="#" onclick="document.tinyib.expire.value='1209600';return false;">2w</a>&nbsp;
                                <a href="#" onclick="document.tinyib.expire.value='2592000';return false;">30d</a>&nbsp;
                                <a href="#" onclick="document.tinyib.expire.value='0';return false;">never</a>
                        </small>
                        <br/>
                        <label for="reason">Reason:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</label>
                        <input type="text" name="reason" id="reason">&nbsp;&nbsp;<small>(optional)</small>
                </fieldset>
        </form>
        <br/>