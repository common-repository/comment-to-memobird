=== Plugin Name ===
Contributors: 0xbbc
Tags: comment, print
Requires at least: 3.0.1
Tested up to: 4.8.1
Stable tag: 1.0.0
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

🐦Print comments on your blog to your Memobird! 

Memobird is the name of a thermal printer manufactured by INTRETECH©. And Memobird can receive messages from the Internet with your permission. This plugin requires the service provided by INTRETECH©, all data (to be specific, the comments on your site) are directly sent to INTRETECH© (to be specific, they provided web API for developers at http://open.memobird.cn, that's where the comments are sent to). 

This plugin uses INTRETECH©'s offical PHP SDK with slightly modifications on class name. https://github.com/memobird/gugu-php. 
```
gugu-php-LICENSE
memobird.php  (with class name changed from `memobird' 
               to `comment2memobird_memobird', and 
               expose instance variable named `ak')
```

You can request an access key via their website. And for details, they provided a manual at http://open.memobird.cn/upload/webapi.pdf.

NOTICE, this plugin needs `php-mbstring` extension to be installed for converting string encoding (INTRETECH© requires GBK encoding). 

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/comment-to-memobird` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Enter your access key and memobird UDID.
4. Wait for comments!

== Screenshots ==

1. Edit your access key, memobird UDID, and other available settings.

== Changelog ==

= 1.0.0 =
* Initial version

== Upgrade Notice ==

