=== MailWizz Newsletter Box ===
Contributors: twisted1919
Donate link: http://www.mailwizz.com/
Tags: email, newsletter box, newsletter, email marketing
Requires at least: 3.0
Tested up to: 3.7.1
Stable tag: 1.1
License: MIT
License URI: http://opensource.org/licenses/MIT

Allows you to create a custom newsletter box for your MailWizz EMA application.

== Description ==

Adds a subscription widget for your [MailWizz Email Marketing Application](http://www.mailwizz.com/ "MailWizz Email Marketing Application") based on the 
[PHP-SDK](https://github.com/twisted1919/mailwizz-php-sdk/ "PHP-SDK").  
Using the widget, you can generate a subscription form based on your mail list definition.  
You also have full control over the generated form, so you can continue changing it until it fits your needs.  


== Installation ==  

1. Upload the folder `mailwizz-newsletter-box` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Visit widgets area to setup the subscription widget


== Changelog ==

= 1.0 =
* Initial release.

= 1.1 = 
* Added nonce check on subscribe form submission
* Replaced `json_encode` with `MailWizzApi_Json::encode` to make it work on hosts without `json_encode`	