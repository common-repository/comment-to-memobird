<?php namespace comment2memobird;
/*
 * The contents of this file are subject to the GPL License, Version 3.0.
 *
 * Copyright (C) 2017, 0xBBC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class Comment2Memobird_Admin {
    static $admin_tags = array(
        'input' => array(
            'type' => array(),
            'name' => array(),
            'id' => array(),
            'disabled' => array(),
            'value' => array(),
            'checked' => array(),
        ),
    );

    function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_page_init' ) );
    }

    function admin_page_init() {
        add_options_page( 'Memobird', 'Memobird', 'manage_options', 'kcomment-to-memobird', array( $this, 'plugin_options_menu' ) );
    }

    function plugin_options_menu() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) ); //xss ok
        }

        $this->table_head();

        // save options if this is a valid post
        if ( isset( $_POST['kcomment_to_memobird_save_field'] ) && // input var okay
            wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['kcomment_to_memobird_save_field'] ) ), 'kcomment_to_memobird_save_action' ) // input var okay
        ) {
            echo "<div class='updated settings-error' id='etting-error-settings_updated'><p><strong>Settings saved.</strong></p></div>\n";
            $this->admin_save();
        }

        $access_key          = "value='" . esc_attr( get_option( 'kcomment_to_memobird_ak', '' ) ) . "'";
        $this->admin_table_row( 'Access Key',
            'Memobird Developer Access Key (You can request one from http://open.memobird.cn)',
            "<input type='textbox' name='kcomment_to_memobird_ak' id='kcomment_to_memobird_ak' $access_key>",
            'kcomment_to_memobird_ak'
        );
        
        $memobird_udid        = "value='" . esc_attr( get_option( 'kcomment_to_memobird_udid', '' ) ) . "'";
        $this->admin_table_row( 'Memobird UDID',
            'Memobird UDID',
            "<input type='textbox' name='kcomment_to_memobird_udid' id='kcomment_to_memobird_udid' $memobird_udid>",
            'kcomment_to_memobird_udid'
        );
        
        $enable_pingback = '';
        if ( compatible_wp_validate_boolean( get_option( 'kcomment_to_memobird_enable_pingback' ) ) ) {
            $enable_pingback = 'checked="true"';
        }
        $this->admin_table_row( 'Pingback',
            'Print Pingbacks to Memobird',
            "<input type='checkbox' name='kcomment_to_memobird_enable_pingback' id='kcomment_to_memobird_enable_pingback' value='1' $enable_pingback />",
            'kcomment_to_memobird_enable_pingback'
        );
        
        $enable_trackback = '';
        if ( compatible_wp_validate_boolean( get_option( 'kcomment_to_memobird_enable_trackback' ) ) ) {
            $enable_trackback = 'checked="true"';
        }
        $this->admin_table_row( 'Trackback',
            'Print Trackbacks to Memobird',
            "<input type='checkbox' name='kcomment_to_memobird_enable_trackback' id='kcomment_to_memobird_enable_trackback' value='1' $enable_trackback />",
            'kcomment_to_memobird_enable_trackback'
        );
        
        $enable_regular = '';
        if ( compatible_wp_validate_boolean( get_option( 'kcomment_to_memobird_enable_regular' ) ) ) {
            $enable_regular = 'checked="true"';
        }
        $this->admin_table_row( 'Regular',
            'Print regular comments to Memobird',
            "<input type='checkbox' name='kcomment_to_memobird_enable_regular' id='kcomment_to_memobird_enable_regular' value='1' $enable_regular />",
            'kcomment_to_memobird_enable_regular'
        );
        
        $text_only = '';
        if ( compatible_wp_validate_boolean( get_option( 'kcomment_to_memobird_text_only' ) ) ) {
            $text_only = 'checked="true" readonly="readonly"';
        }
        $this->admin_table_row( 'Text Only',
            'Print only text part to Memobird (image support is under development)',
            "<input type='checkbox' name='kcomment_to_memobird_text_only' id='kcomment_to_memobird_text_only' value='1' $text_only />",
            'kcomment_to_memobird_text_only'
        );

        $this->table_foot();
    }

    function admin_save() {
        if ( array_key_exists( 'kcomment_to_memobird_ak', $_POST ) && isset( $_POST['kcomment_to_memobird_ak'] ) ) { // input var okay
            $possible_ak = strtolower( sanitize_text_field( wp_unslash( $_POST['kcomment_to_memobird_ak'] ) ) );
            
            // access key should be exactly composed by 32 chars in [a-z0-9]
            if ( preg_grep( '/^[a-z0-9]{32}$/', array( $possible_ak ) ) ) {
                update_option( 'kcomment_to_memobird_ak', $possible_ak ); // input var okay
            }
        }
        
        if ( array_key_exists( 'kcomment_to_memobird_udid', $_POST ) && isset( $_POST['kcomment_to_memobird_udid'] ) ) { // input var okay
            $possible_udid = strtolower( sanitize_text_field( wp_unslash( $_POST['kcomment_to_memobird_udid'] ) ) );
            
            // UDID should be exactly composed by 16 chars in [a-z0-9]
            if ( preg_grep( '/^[a-z0-9]{16}$/', array( $possible_udid ) ) ) {
                update_option( 'kcomment_to_memobird_udid', $possible_udid ); // input var okay
            }
        }
        
        // these options should only be boolean value
        update_option( 'kcomment_to_memobird_enable_pingback', compatible_wp_validate_boolean( array_key_exists( 'kcomment_to_memobird_enable_pingback', $_POST ) ) ); // input var okay
        update_option( 'kcomment_to_memobird_enable_trackback', compatible_wp_validate_boolean( array_key_exists( 'kcomment_to_memobird_enable_trackback', $_POST ) ) ); // input var okay
        update_option( 'kcomment_to_memobird_enable_regular', compatible_wp_validate_boolean( array_key_exists( 'kcomment_to_memobird_enable_regular', $_POST ) ) ); // input var okay
        update_option( 'kcomment_to_memobird_text_only', compatible_wp_validate_boolean( array_key_exists( 'kcomment_to_memobird_text_only', $_POST ) ) ); // input var okay
    }

    function table_head() {
        ?>
        <div class='wrap' id='memobird-options'>
            <h2>Comment to Memobird</h2>
            <form id='comment-to-memobird' name='comment-to-memobird' action='' method='POST'>
                <?php wp_nonce_field( 'kcomment_to_memobird_save_action', 'kcomment_to_memobird_save_field', true ); ?>
            <table class='form-table'>
            <caption class='screen-reader-text'>Comment to Memobird设置</caption>
        <?php
    }

    function table_foot() {
        ?>
        </table>
        <p class="submit"><input type="submit" class="button button-primary" value="Save Changes"/></p>
        </form>
        </div>
    <?php
    }

    function admin_table_row( $head, $comment, $input, $input_id ) {
        ?>
            <tr valign="top">
                    <th scope="row">
                        <label for="<?php echo esc_attr( $input_id ); ?>"><?php echo esc_html( $head ); ?></label>
                    </th>
                    <td>
                        <?php echo wp_kses( $input, self::$admin_tags ); ?>
                        <p class="description"><?php echo wp_kses_post( $comment ); ?></p>
                    </td>
                </tr>
<?php
    }
}; // class

function memobird_admin_init() {
    global $memobird_admin;
    $memobird_admin = new Comment2Memobird_Admin();
}

if ( function_exists( 'is_admin' ) && is_admin() ) {
    memobird_admin_init();
}
