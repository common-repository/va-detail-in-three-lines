<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/*
Plugin Name: VA Detail In Three Lines
Plugin URI: https://github.com/VisuAlive/va-detail-in-three-lines
Description: This plugin is a plugin for explaining in detail the contents of the article in a sentence of three lines.
Author: KUCKLU
Version: 1.0.1
Author URI: http://visualive.jp/
Text Domain: va-detail-in-three-lines
Domain Path: /languages
GitHub Plugin URI: https://github.com/VisuAlive/va-detail-in-three-lines
GitHub Branch: master
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

VisuAlive WordPress Plugin, Copyright (C) 2014 VisuAlive and KUCKLU.

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
/**
 * VA DETAIL IN THREE LINES
 *
 * @package WordPress
 * @subpackage VA Detail In Three Lines
 * @since VA Detail In Three Lines 0.0.1
 * @author KUCKLU <kuck1u@visualive.jp>
 * @copyright Copyright (c) 2014 KUCKLU, VisuAlive.
 * @license http://opensource.org/licenses/gpl-2.0.php GPLv2
 * @link http://visualive.jp/
 */

if ( ! class_exists( 'VA_DETAIL_IN_THREE_LINES' ) ) :
$vaditl_plugin_data = get_file_data( __FILE__, array('ver' => 'Version', 'langs' => 'Domain Path', 'mo' => 'Text Domain' ) );
define( 'VA_DETAIL_IN_THREE_LINES_PLUGIN_URL', plugin_dir_url(__FILE__) );
define( 'VA_DETAIL_IN_THREE_LINES_PLUGIN_PATH', plugin_dir_path(__FILE__) );
define( 'VA_DETAIL_IN_THREE_LINES_DOMAIN', dirname( plugin_basename(__FILE__) ) );
define( 'VA_DETAIL_IN_THREE_LINES_NONCE', 'vaditl-nonce' );
define( 'VA_DETAIL_IN_THREE_LINES_VERSION', $vaditl_plugin_data['ver'] );
define( 'VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN', $vaditl_plugin_data['mo'] );

class VA_DETAIL_IN_THREE_LINES {
    function __construct() {
        add_action( 'plugins_loaded', array( &$this, '_plugins_loaded') );
    }

    /**
     * [Plugin loaded]
     * @param  [void]
     * @return [type] [description]
     */
    function _plugins_loaded() {
        global $vaditl_plugin_data;
        load_plugin_textdomain( VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN, false, VA_DETAIL_IN_THREE_LINES_DOMAIN . $vaditl_plugin_data[ 'langs' ] );
        $priority_excerpt = apply_filters( 'vaditl/priority/excerpt', 10 );
        $priority_content = apply_filters( 'vaditl/priority/content', 10 );

        add_action( 'add_meta_boxes', array( &$this, 'add_metabox' ) );
        add_action( 'save_post', array( &$this, 'save_metabox' ) );
        add_action( 'wp_enqueue_scripts', array( &$this, 'vaditl_enqueue_script' ) );
        add_filter( 'the_excerpt', array( &$this, 'changed_excerpt' ), (int)$priority_excerpt );
        add_filter( 'the_content', array( &$this, 'changed_content' ), (int)$priority_content );
    }

    /**
     * [WP enqueue script]
     * @param  [void]
     * @return [string] [description]
     */
    function vaditl_enqueue_script() {
        wp_enqueue_style( 'vaditl-style', VA_DETAIL_IN_THREE_LINES_PLUGIN_URL . 'assets/css/vaditl-style.css', array(), null );
    }

    /**
     * [Add contents]
     * @param [string] $content [description]
     */
    function add_contents( $content ) {
        $id       = get_the_ID();
        $postmeta = unserialize( get_post_meta( $id, 'vaditl-date', true ) );
        if ( is_array( $postmeta ) ) {
            $postmeta = array_values( array_filter( $postmeta, 'strlen' ) );
        } else {
            $postmeta = array();
        }

        if ( isset( $postmeta ) && ! empty( $postmeta ) && count( $postmeta ) === 3 ) {
            $content  = '<!-- VisuAlive Detail In Three Lines WordPress Plugin START -->' . PHP_EOL;
            $content .= '<div class="vaditl-postdetails">' . PHP_EOL;
            $content .= '<dl class="vaditl-postdetails--details">' . PHP_EOL;
            $content .= '<dt class="vaditl-postdetails--head">' . esc_html( __( 'Roughly say....', VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN ) ) . '</dt>' . PHP_EOL;
            foreach ( $postmeta as $key => $value ) {
                $content .= '<dd class="vaditl-postdetails--item"><span>' . esc_attr( $value ) . '</span></dd>' . PHP_EOL;
            }
            $content .= '</dl>' . PHP_EOL;
            $content .= '</div>' . PHP_EOL;
            $content .= '<div class="vaditl-postmore">' . PHP_EOL;
            $content .= '<a class="vaditl-postmore--anchor" href="' . esc_url( get_permalink() ) . '">' . __( 'Read post', VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN ) . '</a>' . PHP_EOL;
            $content .= '</div>' . PHP_EOL;
            $content .= '<!-- VisuAlive Detail In Three Lines WordPress Plugin END -->' . PHP_EOL;
        }

        return $content;
    }

    /**
     * [Changed excerpt]
     * @param  [string] $excerpt [description]
     * @return [string]          [description]
     */
    function changed_excerpt( $excerpt ) {
        return $this->add_contents( $excerpt );
    }

    /**
     * [Changed content]
     * @param  [string] $content [description]
     * @return [string]          [description]
     */
    function changed_content( $content ) {
        if ( is_home()
            || is_search()
            || is_archive()
            || is_author()
            || ( is_feed() && get_option( 'rss_use_excerpt' ) )
        ) {
            return $this->add_contents( $content );
        }

        return $content;
    }

    /**
     * [Add metabox]
     * @param  [void]
     * @return [void]
     */
    function add_metabox() {
        $types_default = array('posts' => 'post', 'pages' => 'page');
        $types_custom  = get_post_types( array( 'public' => true, '_builtin' => false ), 'names' );
        $types         = apply_filters( 'vaditl/posttypes', array_merge( $types_default, $types_custom ) );

        foreach ( $types as $type => $value ) {
            add_meta_box( 'vaditl_metabox', __( 'Detail in three lines', VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN ), array( &$this, 'cd_metabox' ), $value, 'normal', 'high' );
        }
    }

    /**
     * [Add Metabox callback]
     * @param  [Object] $post [description]
     * @return [void]         [description]
     */
    function cd_metabox( $post ) {
        wp_nonce_field( VA_DETAIL_IN_THREE_LINES_DOMAIN, VA_DETAIL_IN_THREE_LINES_NONCE );
        $postmeta = unserialize( get_post_meta( $post->ID, 'vaditl-date', true ) );
        $count    = array( __( 'First line', VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN ), __( 'Second line', VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN ), __( 'Third line', VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN ) );

        print( '<p>' . __('Please explain the contents of this post attractively clearly in three lines.', VA_DETAIL_IN_THREE_LINES_TEXTDOMAIN ) . '</p>' );
        for ( $i = 0; $i < 3; $i++ ) {
            printf( '<p><label for="%1$s-%2$d" style="display: block; font-weight: bold;">%3$s</label>%4$s', esc_attr( 'vaditl-date' ), esc_attr( $i ), esc_html( $count[$i] ), PHP_EOL );
            printf( '<input type="text" name="%1$s[]" id="%1$s-%2$d" value="%3$s" style="width: %4$s;" />%5$s', esc_attr( 'vaditl-date' ), esc_attr( $i ), esc_attr( $postmeta[$i] ), esc_attr( "100%" ), PHP_EOL );
            print( '</p>' );
        }
    }

    /**
     * [Save post meta]
     * @param  [intval] $id [description]
     * @return [void]       [description]
     */
    function save_metabox( $id ) {
        $input_args   = array(
                            VA_DETAIL_IN_THREE_LINES_NONCE => FILTER_SANITIZE_STRING,
                            'post_type'                    => FILTER_SANITIZE_STRING,
                            'vaditl-date'                  => array( 'filter' => FILTER_SANITIZE_STRING, 'flags'  => FILTER_REQUIRE_ARRAY )
                        );
        $input        = filter_input_array( INPUT_POST, $input_args );
        $verify_nonce = wp_verify_nonce( $input[VA_DETAIL_IN_THREE_LINES_NONCE], VA_DETAIL_IN_THREE_LINES_DOMAIN );

        if ( ! $verify_nonce || ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) ) { return (int)$id; }

        if ( 'page' === $input['post_type'] ) {
            if ( !current_user_can( 'edit_page', (int)$id ) )
                return (int)$id;
        } else {
            if ( !current_user_can( 'edit_post', (int)$id ) )
                return (int)$id;
        }

        update_post_meta( (int)$id, 'vaditl-date', serialize( $input['vaditl-date'] ) );
    }
}
new VA_DETAIL_IN_THREE_LINES;
endif;
