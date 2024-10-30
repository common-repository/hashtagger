<?php

/**
 * The Smart Hashtags Plugin
 *
 * Smart Hashtags allows usage of #hashtags, @usernames and $cashtags in posts
 *
 * @wordpress-plugin
 * Plugin Name: Smart Hashtags [#hashtagger]
 * Description: Use #hashtags, @usernames and $cashtags in your posts
 * Version: 7.2.3
 * Author: Peter Raschendorfer
 * Author URI: https://profiles.wordpress.org/petersplugins/
 * Text Domain: hashtagger
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 */

// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Loader
 */
require_once( plugin_dir_path( __FILE__ ) . '/loader.php' );


/**
 * Theme function
 */
function do_hashtagger( $content ) {
  
  return pp_hashtagger()->work( $content );
  
}

?>