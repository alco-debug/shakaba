# shakaba
Shakaba is a simple PHP imageboard engine based on Claire

# Imageboards list
- [Uber-chan](http://uberchan.rf.gd)

# Deployment
First of all, edit `config.php` as you need. Then copy all files to your hosting root (usually directory along `.htaccess` or simply `public_html`). All done! Don't forget either to change the mascott and logo or to make translation suitable for your needs.
To create yet another board just copy `new_board`, and give it an appropriate name (`b` stands for `yoursite.net/b/`), then edit `board_config.php` and `board_info.php`: in the `board_info.php` write an appropriate board name (`/b/` stands for `yoursite.net/b`) and an appriate board description (if you write `Random`, then it will be `/b/ - Random` on the boards' homepage and will be used as browser tab title the same way; don't forget to change passwords, salts and optionally paths and timezones in `board_config.php`.
