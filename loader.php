<?php

/**
 * The hashtagger Plugin Loader
 *
 * @since 6
 *
 **/
 
// If this file is called directly, abort
if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * Load files
 */
require_once( plugin_dir_path( __FILE__ ) . '/inc/class-hashtagger.php' );


/**
 * Main Function
 */
function pp_hashtagger() {

  return PP_Hashtagger::getInstance( array(
    'file'    => dirname( __FILE__ ) . '/hashtagger.php',
    'slug'    => pathinfo( dirname( __FILE__ ) . '/hashtagger.php', PATHINFO_FILENAME ),
    'name'    => '#hashtagger',
    'version' => '7.2.3'
  ) );
    
}



/**
 * Run the plugin
 */
pp_hashtagger();


?>