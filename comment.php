<?php namespace comment2memobird;
/*
* Plugin Name: Comment to Memobird
* Description: Print comments on your blog to your Memobird!
* Version: 1.0.0
* Author: 0xBBC
* Author URI: https://blog.0xbbc.com/
* License: GPLv3
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
* Acknowledgement: This plugin uses INTRETECH©'s offical 
*      PHP SDK with slightly modifications on class name.
*      https://github.com/memobird/gugu-php
*/

$admin_page = dirname( __FILE__ ) . '/comment-admin.php';
if ( validate_file($admin_page) === 0) {
    require_once( $admin_page );
    
    // it has to be in the same namespace as this class
    // and its class name should only be 'Comment2Memobird_Admin'
    if ( !class_exists('\\'.__NAMESPACE__.'\Comment2Memobird_Admin') ) {
        die( 'There is something wrong with the critical file, comment-admin.php. Please reinstall this plugin.' );
    }
} else {
    die( 'Missing critical file: comment-admin.php. Please reinstall this plugin.' );
}

// php-mbstring check
if ( function_exists( 'mb_convert_encoding' ) === false ) {
    die( 'This plugin requires php-mbstring extension.' );
}

// for compatibility before WordPress version 4.0.0
function compatible_wp_validate_boolean($var) {
    // wp_validate_boolean was introduced at 4.0.0
    if ( function_exists( 'wp_validate_boolean' ) ) {
        return wp_validate_boolean($var);
    }
    
    // copy and paste from the implementation of wp_validate_boolean
    if ( is_bool( $var ) ) {
        return $var;
    }
    
    if ( is_string( $var ) && 'false' === strtolower( $var ) ) {
        return false;
    }
    
    return (bool) $var;
}

class Comment2Memobird {
    public static function init() {
        register_activation_hook( __FILE__, array( __CLASS__, 'memobird_install' ) );
        register_deactivation_hook( __FILE__, array( __CLASS__, 'memobird_uninstall' ) );
        add_filter( 'plugin_action_links', array( __CLASS__, 'memobird_settings_link' ), '99', 2 );
        add_filter( 'pre_comment_approved' , array( __CLASS__, 'memobird_print' ), '99', 2 );
    }
    
    // registers default options
    public static function memobird_install() {
        add_option( 'kcomment_to_memobird_ak', 'Please fill in your access key' );
        add_option( 'kcomment_to_memobird_udid', 'Please fill in your memobird udid' );
        add_option( 'kcomment_to_memobird_enable_pingback', true );
        add_option( 'kcomment_to_memobird_enable_trackback', true );
        add_option( 'kcomment_to_memobird_enable_regular', true );
        add_option( 'kcomment_to_memobird_text_only', true );
        add_option( 'kcomment_to_memobird_userid', '' );
    }
    
    public static function memobird_uninstall() {
        delete_option( 'kcomment_to_memobird_ak' );
        delete_option( 'kcomment_to_memobird_udid' );
        delete_option( 'kcomment_to_memobird_enable_pingback' );
        delete_option( 'kcomment_to_memobird_enable_trackback' );
        delete_option( 'kcomment_to_memobird_enable_regular' );
        delete_option( 'kcomment_to_memobird_text_only' );
        delete_option( 'kcomment_to_memobird_userid' );
    }
    
    public static function memobird_print( $approved , $commentdata ) {
        // maybe we need to check for WordPress functions' existance
        //   wp_validate_boolean, get_option, update_option
        //   get_the_title, wp_specialchars_decode
        
        $akismet_result = false;
        if (array_key_exists('akismet_result', $commentdata) ) {
            $akismet_result = compatible_wp_validate_boolean( $commentdata['akismet_result'] );
        }
        if ($akismet_result == true) return 'spam';
        
        $enable_pingback = compatible_wp_validate_boolean( get_option( 'kcomment_to_memobird_enable_pingback' ) );
        $enable_trackback = compatible_wp_validate_boolean( get_option( 'kcomment_to_memobird_enable_trackback' ) );
        $enable_regular = compatible_wp_validate_boolean( get_option( 'kcomment_to_memobird_enable_trackback' ) );
        $text_only = compatible_wp_validate_boolean( get_option( 'kcomment_to_memobird_text_only' ) );
        
        function comment2memobird_handling($type, $commentdata) {
            $author = $commentdata['comment_author'];
            $author_email = $commentdata['comment_author_email'];
            
            if (strlen($author) == 0) {
                if (strlen($author_email) == 0) {
                    $author = '有人悄悄';
                } else {
                    $author = "有人&lt;$author_email&gt;悄悄";
                }            
            } else {
                if (strlen($author_email) != 0) {
                    $author = "$author&lt;$author_email&gt;";
                }
            }

            $access_key = get_option( 'kcomment_to_memobird_ak', '' );
            $memobird_udid = get_option( 'kcomment_to_memobird_udid', '' );
            $userid = get_option( 'kcomment_to_memobird_userid', '' );
            
            $memobird_library = dirname( __FILE__ ) . '/memobird.php';
            if ( validate_file( $memobird_library ) === 0) {
                require_once( $memobird_library );
                
                // it has to be in the same namespace as this class
                // and its class name should only be 'memobird'
                if ( class_exists( '\\'.__NAMESPACE__.'\memobird' ) ) {
                    $memobird = new memobird();
                    $memobird->ak = $access_key;
                    
                    if ( strlen($userid) == 0 ) {
                        $result = json_decode( $memobird->getUserId( $memobird_udid, 'comment_to_memobird' ), true );
                        $result_code = (int)$result['showapi_res_code'];
                        if ( $result_code == 1 ) {
                            update_option( 'kcomment_to_memobird_userid', (int)$result['showapi_userid'] );
                        } else {
                            return;
                        }
                    }
                    
                    $userid = get_option( 'kcomment_to_memobird_userid', '' );
                    $comment_content = $author.'在「'.get_the_title($commentdata['comment_post_ID']).'」上给你发送了一条'."$type: \n".$commentdata['comment_content'];
                    
                    // replace '<br>' with '\n'
                    $comment_content = str_ireplace( '<br>', "\n", $comment_content );
                    
                    // strip all html tags, since the content is going to be printed
                    $comment_content = strip_tags( $comment_content );
                    
                    // decode special chars
                    $comment_content = wp_specialchars_decode( $comment_content, ENT_QUOTES );

                    // $comment_content will be processed into is a base64 encoded string, as INTRETECH© required
                    $content = $memobird->contentSet( 'T', $comment_content );
                    
                    $memobird->printPaper( $content, $memobird_udid, $userid );   
                }
            }
        }
        
        $type = $commentdata['comment_type'];
        if ( $type === 'pingback' && $enable_pingback ) comment2memobird_handling( 'pingback', $commentdata );
        elseif ( $type === 'trackback' && $enable_trackback ) comment2memobird_handling( 'trackback', $commentdata );
        elseif ( $enable_regular ) comment2memobird_handling( '评论', $commentdata );

        return 0;
    }
    
    public static function memobird_settings_link( $links, $file ) {
        if ( plugins_url('comment-admin.php', __FILE__ ) === $file && function_exists( 'admin_url' ) ) {
            $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=kcomment-to-memobird' ) ) . '">' . esc_html__( 'Settings' ) . '</a>';
            array_unshift( $links, $settings_link );
        }
        return $links;
    }
}; // class

Comment2Memobird::init();
