<?php

/**
 * The hashtagger core plugin class
 */


 // If this file is called directly, abort
 if ( ! defined( 'WPINC' ) ) {
	die;
}


/**
 * The core plugin class
 */
if ( !class_exists( 'PP_Hashtagger' ) ) { 

  class PP_Hashtagger {
    
    /**
     * Instance
     *
     * @since  6
     * @var    singleton
     * @access protected
     */
    protected static $_instance = null;
    
    
    /**
     * Plugin Main File Path and Name
     * was $_file before
     * removed in v6
     */
     
    
    /**
     * Plugin Name
     *
     * @since  1
     * @var    string
     * @access private
     */
    private $plugin_name;
    
    
    /**
     * Plugin Slug
     *
     * @since  1
     * @var    string
     * @access private
     */
    private $plugin_slug;
    
    
    /**
     * Plugin Version
     *
     * @since  6
     * @var    int
     * @access private
     * was $version before
     */
    private $plugin_version;
    
    private $settings;
    
    
    /**
     * Ignore List
     *
     * @since  7
     * @var    array
     * @access private
     */
    private $ignore_list;
    
    
    /**
     * Ignore Blocks Backup
     *
     * @since  7.1
     * @var    array
     * @access private
     */
    private $ignore_block_bak;
    
    
    /**
     * Ignore Blocks Replacement
     *
     * @since  7.1
     * @var    string
     * @access private
     */
    private $ignore_block_replace;
    
		private $admin_handle;
    protected $regex_general;
    protected $regex_notag;
    protected $regex_users;
    protected $regex_cash;
    protected $regnonce;
    
    
    /**
     * Init the Class 
     *
     * @since 1
     * @see getInstance
     */
    public function __construct( $settings ) {
      
      $this->plugin_file    = $settings['file'];
      $this->plugin_slug    = $settings['slug'];
      $this->plugin_name    = $settings['name'];
      $this->plugin_version = $settings['version'];
      
      $this->get_settings();
      $this->init();
      
    } 
    
    /**
     * Prevent Cloning
     *
     * @since 4
     */
    protected function __clone() {}
    
    
    /**
	   * Get the Instance
     *
     * @since 6
     * @param array $settings {
     *   @type string $file    Plugin Main File Path and Name
     *   @type string $slug    Plugin Slug
     *   @type string $name    Plugin Name
     *   @type int    $version Plugin Verion
     * }
     * @return singleton
     */
    public static function getInstance( $settings ) {
     
      if ( null === self::$_instance ) {

        self::$_instance = new self( $settings );
        
      }
      
      return self::$_instance;
      
    }
    
    
    /**
	   * get plugin file
     *
     * @since 6
     * @access public
     */
    public function get_plugin_file() {
      
      return $this->plugin_file;
      
    }
    
    
    /**
	   * get plugin slug
     *
     * @since 6
     * @access public
     */
    public function get_plugin_slug() {
      
      return $this->plugin_slug;
      
    }
    
    
    /**
	   * get plugin name
     *
     * @since 6
     * @access public
     */
    public function get_plugin_name() {
      
      return $this->plugin_name;
      
    }
    
    
    /**
	   * get plugin version
     *
     * @since 6
     * @access public
     */
    public function get_plugin_version() {
      
      return $this->plugin_version;
      
    }
    
    
    /**
		 * do plugin init
		 */
    private function init() {
      
      // since 7.1
      $this->ignore_block_replace = '_____(*hashtagger*IGNOREBLOCK*hashtagger*)_____';
      
      $this->regex_general = '/(^|[\s!\.:;\?(>])#([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
      $this->regex_notag = '/(^|[\s!\.:;\?(>])\+#([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
      
      if ( true === $this->settings['tags_allow_numeric'] ) {
        
        // Allow Tags to start with numbers
        $this->regex_general = '/(^|[\s!\.:;\?(>])#([\p{L}0-9][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
        $this->regex_notag = '/(^|[\s!\.:;\?(>])\+#([\p{L}0-9][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
        
      }
      
      $this->regex_users = '/(^|[\s!\.:;\?(>])\@([\p{L}][\p{L}0-9_]+)(?=[^<>]*(?:<|$))/u';
      $this->regex_cash = '/(^|[\s!\.:;\?(>])\$([A-Z][A-Z\-]+)(?=[^<>]*(?:<|$))/u';
      
      $this->regnonce = 'hashtagger_regenerate';
      
      // create ignore list - since v 7
      $this->ignore_list = array( 'hashtags' => array(), 'usernames' => array(), 'cashtags' => array() );
      foreach( $this->settings['ignorelist'] as $ignore ) {
        
        if ( strpos( $ignore, '#' ) === 0 ) {
          
          $this->ignore_list['hashtags'][] = trim( substr( $ignore, 1) );
          
        } elseif ( strpos( $ignore, '@' ) === 0 ) {
          
          $this->ignore_list['usernames'][] = trim( substr( $ignore, 1) );
          
        } elseif ( strpos( $ignore, '$' ) === 0 ) {
          
          $this->ignore_list['cashtags'][] = trim( substr( $ignore, 1) );
          
        }
        
      }
      
      add_action( 'init', array( $this, 'add_text_domain' ) );

      add_action( 'save_post', array( $this, 'generate_tags' ), 19 );
      
      // *** For Plugin User Submitted Posts https://wordpress.org/plugins/user-submitted-posts/ (since v 3.2)
      //     had to use filter usp_new_post insetad of action usp_insert_after because tags are created AFTER usp_insert_after
      add_filter( 'usp_new_post', array( $this, 'process_content_for_user_submitted_posts' ), 9999 );
      
      // *** For Barley - Inline Editing Plugin for WordPress (since v 3.2)
      //     had to override their save function...
      add_action( 'wp_ajax_barley_update_post',  array( $this, 'process_content_for_barely' ), 0 );
    
      
      if ( ! is_admin() ) {
        
        add_filter( 'the_content', array( $this, 'process_content' ), 9999 );
        
        if ( $this->settings['sectiontype_title'] ) {
          
          add_filter( 'the_title', array( $this, 'process_title' ), 9999 );
          
        }
        
        if ( $this->settings['sectiontype_excerpt'] ) {
          
          add_filter( 'the_excerpt', array( $this, 'process_excerpt' ), 9999 );
          
        }
        
        if ( $this->settings['display_add_symbol_to_tag'] ) {
        
          add_filter( 'get_term', array( $this, 'add_hash_to_single_tag' ), 9999, 2 );
          
        }
        
      } else {
        
        add_action( 'admin_head', array( $this, 'admin_css' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_style' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );
        add_action( 'wp_ajax_hashtagger_regenerate', array( &$this, 'admin_hashtagger_regenerate' ) );
        
        add_action( 'wp_ajax_pp_hashtagger_dismiss_admin_notice', array( $this, 'dismiss_admin_notice' ) );
        
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_js' ) );
      
      }
      
    }
    
    
    /**
		 * add text domain
		 */
    function add_text_domain() {  
    
      load_plugin_textdomain( 'hashtagger' );
      
    }
    
    
    /**
		 * get all settings
		 */
    private function get_settings() {
      
      $this->settings = array();
	  
	  // since v 7.2.1
	  // check != 1 instead of == 0
	  
      $this->settings['posttype_page'] = ( get_option( 'swcc_htg_posttype_page', '1' ) == 1 ) ?  true : false;
      $this->settings['posttype_custom'] = ( get_option( 'swcc_htg_posttype_custom', '1' ) == 1 ) ?  true : false;
      $this->settings['sectiontype_title'] = ( get_option( 'swcc_htg_sectiontype_title', '0' ) == 1 ) ?  true : false;
      $this->settings['sectiontype_excerpt'] = ( get_option( 'swcc_htg_sectiontype_excerpt', '0' ) == 1 ) ?  true : false;
      $this->settings['advanced_nodelete'] = ( get_option( 'swcc_htg_advanced_nodelete', '0' ) != 1 ) ?  false : true;
      $this->settings['usernames'] = get_option( 'swcc_htg_usernames', 'NONE' );
      $this->settings['advanced_single'] = ( get_option( 'swcc_htg_advanced_single', '0' ) != 1 ) ?  false : true;
      
      if ( ! in_array( $this->settings['usernames'], array( 'NONE', 'PROFILE', 'WEBSITE-SAME', 'WEBSITE-NEW' ) ) ) {
        
        $this->settings['usernames'] = 'NONE';
        
      }
      
      $this->settings['cashtags'] = get_option( 'swcc_htg_cashtags', 'NONE' );
      
      if ( ! in_array( $this->settings['cashtags'], array( 'NONE', 'MARKETWATCH-SAME', 'MARKETWATCH-NEW', 'GOOGLE-SAME', 'GOOGLE-NEW', 'YAHOO-SAME', 'YAHOO-NEW', 'STOCKTWITS-SAME', 'STOCKTWITS-NEW' ) ) ) {
        
        $this->settings['cashtags'] = 'NONE';
        
      }
      
      $this->settings['usernamesnick'] = ( get_option( 'swcc_htg_usernamesnick', '0' ) !=1 ) ?  false : true;
      $this->settings['cssclass'] = get_option( 'swcc_htg_cssclass', '' ); 
      $this->settings['cssclass_notag'] = get_option( 'swcc_htg_cssclass_notag', '' );
      $this->settings['usernamescssclass'] = get_option( 'swcc_htg_usernamescssclass', '' );
      $this->settings['cashtagcssclass'] = get_option( 'swcc_htg_cashtagcssclass', '' );
      $this->settings['display_nosymbols'] = ( get_option( 'swcc_htg_display_nosymbols', '0' ) !=1 ) ?  false : true;
      $this->settings['display_add_symbol_to_tag'] = ( get_option( 'swcc_htg_display_add_symbol_to_tag', '0' ) != 1 ) ?  false : true;
      $this->settings['tags_allow_numeric'] = ( get_option( 'swcc_htg_tags_allow_numeric', '0' ) != 1 ) ?  false : true;
      $this->settings['tags_no_links'] = ( get_option( 'swcc_htg_tags_no_links', '0' ) != 1 ) ?  false : true;
      
      // ignore list
      // since v 7
      // v  7.2 - unserialize removed, convert to array if necessary
      $this->settings['ignorelist'] = get_option( 'swcc_htg_ignorelist', '' );
      if ( ! is_array( $this->settings['ignorelist'] ) ) {
        
        $this->settings['ignorelist'] = array();
        
      }

      
      // ignore blocks
      // since v 7.1
      $this->settings['ignoreblock_code'] = ( get_option( 'swcc_htg_ignoreblock_code', '0' ) != 1 ) ?  false : true;
      $this->settings['ignoreblock_pre'] = ( get_option( 'swcc_htg_ignoreblock_pre', '0' ) != 1 ) ?  false : true;
      
    }
    
    
    /**
	 * helper function to extract the hashtags from content and to add them as tags to the post
     * since v 3.0 option to not delete unused tags
	*/
    function generate_tags( $postid ) {
      
      $post_type = get_post_type( $postid );
      $custom = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
      
      if ( ( 'post' == $post_type ) || ( 'page' == $post_type && $this->settings['posttype_page'] ) || ( in_array( $post_type, $custom ) && $this->settings['posttype_custom'] ) ) {
        
        $post = get_post( $postid );
        
        $content = $post->post_content;
        
        if ( $this->settings['sectiontype_title'] ) {
          
          $content = $content . ' ' . $post->post_title;
          
        }
        
        if ( $this->settings['sectiontype_excerpt'] ) {
          
          $content = $content . ' ' . $post->post_excerpt;
          
        }
        
        wp_set_post_tags( $postid, $this->get_hashtags_from_content( $content ), $this->settings['advanced_nodelete'] );
        
      }
      
    }
    
    
    /**
		 * helper function to get an array of hashtags from a given content - used by generate_tags()
		 */
    function get_hashtags_from_content( $content ) {
      
      // strip and backup blocks to ignore
      // since 7.1
      $content = $this->backup_blocks_to_ignore( $content );
      
      preg_match_all( $this->regex_general, strip_tags( $content ), $matches );
      // remove tags to ignore - since v 7
      return implode( ', ', array_diff( $matches[2], $this->ignore_list['hashtags']) );
      
    }
    
    
    /**
		 * process content
     *
     * @uses make_link_tag()
     * @uses make_link_notag()
     * @uses make_link_usernames()
     * @uses make_link_cashtags()
		 */
    function work( $content ) { 
    
      // strip and backup blocks to ignore
      // since 7.1
      $content = $this->backup_blocks_to_ignore( $content );
    
      if ( ! $this->settings['tags_no_links'] ) {
        
        $content = str_replace( '##', '#', preg_replace_callback( $this->regex_notag, array( $this, 'make_link_notag' ), preg_replace_callback( $this->regex_general, array( $this, 'make_link_tag' ), $content ) ) );
        
      }
      
      if ( $this->settings['usernames'] != 'NONE' ) {
        
        $content = str_replace( '@@', '@', preg_replace_callback( $this->regex_users, array( $this, 'make_link_usernames' ), $content ) );
        
      }
      
      if ( $this->settings['cashtags'] != 'NONE' ) {
        
        $content = str_replace( '$$', '$', preg_replace_callback( $this->regex_cash, array( $this, 'make_link_cashtags' ), $content ) );
        
      }
      
      // restore ignored blocks
      // since 7.1
      $content = $this->restore_ignored_blocks( $content );
      
      return $content;
      
    }
    
    
    /**
		 * replace hashtags with links when displaying content
     * post type depending since v 3.0
		 */
    function process_content( $content ) {
      
      global $post;
      
      $post_type = get_post_type();
      $custom = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
      
      if ( ( 'post' == $post_type ) || ( 'page' == $post_type && $this->settings['posttype_page'] ) || ( in_array( $post_type, $custom ) && $this->settings['posttype_custom'] ) ) {
        
        // do we have to process only singular pages?
        // @since 4
        if ( false === $this->settings['advanced_single'] || ( true === $this->settings['advanced_single'] && is_singular() ) ) { 
        
          $content = $this->work( $content );
          
        }
        
      }
      
      return $content;
      
    }
    
    
    /**
		 * process title 
     *
     * @uses process_content()
     * @since 3.0
		 */
    function process_title( $title, $id = null ) {
      return $this->process_content( $title );
    }
    
    
    /**
		 * process excerpt
     *
     * @uses process_content()
     * @since 3.0
		 */
    function process_excerpt( $excerpt ) {
      
      return $this->process_content( $excerpt );
      
    }
    
    
    /**
     * create link for a #hashtag
		 * callback function for preg_replace_callback
     *
     * @uses make_link()
     */
    function make_link_tag( $match ) {
      
      return $this->make_link( $match, true );
      
    }
    
    
    /**
     * create link for a +#hashtag
		 * callback function for preg_replace_callback
     *
     * @uses make_link()
     */
    function make_link_notag( $match ) {
      
      return $this->make_link( $match, false );
      
    }
    
    
    /**
     * create link for a @username
		 * callback function for preg_replace_callback
     *
     * @uses make_link_users()
     */
    function make_link_usernames( $match ) {
      
      return $this->make_link_users( $match, $this->settings['usernames'] );
      
    }
    
    
    /**
     * create link for a $cashtag
		 * callback function for preg_replace_callback
     *
     * @uses make_link_cash()
     */
    function make_link_cashtags( $match ) {
      
      return $this->make_link_cash( $match, $this->settings['cashtags'] );
      
    }
    
    
    /**
     * helper function to generate tag link
     * used by make_link_tag and make_link_notag
     */
    private function make_link( $match, $mktag ) {
      
      if ( $match[2] != strip_tags( $match[2] )  ) {
        
        $content = $match[0];
        
      } elseif ( in_array( $match[2], $this->ignore_list['hashtags'] ) ) {
      
        // ignore tag if found in ignore list - since v 7
        $content = $match[0];
      
      } else {
        
        $terms = get_terms( array( 'taxonomy' => 'post_tag', 'name' => $match[2], 'number' => 1 ) );
        
        if ( ! $terms ) {
          
          $content = $match[0];
          
        } else {
          
          $term = $terms[0];
          $termid = $term->term_id;
          $slug = $term->slug;
          
          if ( $mktag ) {
            
            $css = $this->settings['cssclass'];
            
          } else {
            
            $css = $this->settings['cssclass_notag'];
            
          }
          if ( $css != '' ) {
            
            $css = ' class="' . $css . '"';
            
          }
          
          if ( ! $this->settings['display_nosymbols'] ) {
            
            $symbol = '#';
            
          } else {
            
            $symbol = '';
            
          }
          
          $content = $match[1] . '<a' . $css . ' href="' . get_tag_link( $termid ) . '">' . $symbol . $match[2] . '</a>';
          
        }
        
      }
      
      return $content;
      
    }

    
    /**
     * helper function to generate user link
     * used by make_link_usernames
     */
    private function make_link_users( $match, $link ) {
      
      if ( in_array( $match[2], $this->ignore_list['usernames'] ) ) {
      
        // ignore user if found in ignore list - since v 7
        // exit immediately
        return $match[0];
        
      }
      
      $user = false;
      $username = $match[2];
      
      if ( ! $this->settings['usernamesnick'] ) {
        
        // get by login name - default
        $user = get_user_by( 'login', $username );
        
      } else {
        
        // get by nickname
        $users = get_users( array( 'meta_key' => 'nickname', 'meta_value' => $username ) );
        
        if ( count( $users ) == 1 ) {
          
          // should result in one user
          $user = $users[0];
          
        }
      }
      
      if ( !$user ) {
        
        $content = $match[0];
        
      } else {
        
        if ( $link != 'PROFILE' ) {
          
          $linkto = $user->user_url;
          
        } else {
          
          $linkto = '';
          
        }
        
        if ( $linkto == '' ) {
          
          $linkto = get_author_posts_url( $user->ID );
          
        }
        
        if ( $link == 'WEBSITE-NEW' ) {
          
          $target = ' target="_blank"';
          
        } else {
          
          $target = '';
          
        }
        
        $css = $this->settings['usernamescssclass'];
        
        if ( $css != '' ) {
          
          $css = ' class="' . $css . '"';
          
        }
        
        if ( ! $this->settings['display_nosymbols'] ) {
          
          $symbol = '@';
          
        } else {
          
          $symbol = '';
          
        }
        
        $content = $match[1] . '<a' . $css . ' href="' . $linkto . '"'. $target . '>' . $symbol . $match[2] . '</a>';
        
      }
      
      return $content;
      
    }
    
    
    /**
     * helper function to generate cashtag link
     * used by make_link_cashtags
     */
    private function make_link_cash( $match, $link ) {
           
      if ( in_array( $match[2], $this->ignore_list['cashtags'] ) ) {
      
        // ignore cashtag if found in ignore list - since v 7
        // exit immediately
        return $match[0];
        
      }
      
      // preventively
      $content = $match[0];
      
      if ( $link == 'MARKETWATCH-SAME' || $link == 'MARKETWATCH-NEW' ) {
        
        $linkto = 'https://www.marketwatch.com/investing/Stock/' . $match[2];
        
      } elseif ( $link == 'GOOGLE-SAME' || $link == 'GOOGLE-NEW' ) {
        
        $linkto = 'https://www.google.com/finance?q=' . $match[2];
        
      } elseif ( $link == 'YAHOO-SAME' || $link == 'YAHOO-NEW' ) {
        
        $linkto = 'https://finance.yahoo.com/q?s=' . $match[2];
        
      } elseif ( $link == 'STOCKTWITS-SAME' || $link == 'STOCKTWITS-NEW' ) {
        
        $linkto = 'https://stocktwits.com/symbol/' . $match[2];
        
      }
      
      if ( $link == 'MARKETWATCH-NEW' || $link == 'GOOGLE-NEW' || $link == 'YAHOO-NEW' || $link == 'STOCKTWITS-NEW' ) {
        
        $target = ' target="_blank"';
        
      } else {
        
        $target = '';
        
      }
      
      $css = $this->settings['cashtagcssclass'];
      
      if ( $css != '' ) {
        
        $css = ' class="' . $css . '"';
        
      }
      
      if ( ! $this->settings['display_nosymbols'] ) {
      
        $symbol = '$';
        
      } else {
        
        $symbol = '';
        
      }
        
      $content = $match[1] . '<a' . $css . ' href="' . $linkto . '"'. $target . '>' . $symbol . $match[2] . '</a>';

      return $content;

    }
    
    
    /**
		 * process content for Plugin User Submitted Posts https://wordpress.org/plugins/user-submitted-posts/
     *
     * @since 3.2 
     */
    function process_content_for_user_submitted_posts( $new_user_post ) {
      
      $this->generate_tags( $new_user_post['id'] );
      return $new_user_post;
      
    }
    
    
    /**
		 * process content for Barley - Inline Editing Plugin for WordPress
     *
     * @since 3.2 
     */
    function process_content_for_barely() {
      // this function overrides barley_update_post in functions_posts.php of the Barely plugin
      
      // -- Taken from Barely
      $json            = array();
      $json['success'] = false;
      $columns         = array(
                        'the_title'   => 'post_title',
                        'the_content' => 'post_content');

      // Only proceed if we have a post_id
      if ( isset($_POST['p']) && ! empty($_POST['p']) ) {
        
        $k               = trim(urldecode($_POST['k']));
        $v               = trim(urldecode($_POST['v']));
        $pid             = trim(urldecode($_POST['p']));

        // Strip trailing BR tag in FireFox
        if ( $k === 'the_title' ) {
          
          $v = preg_replace('/(.*)<br[^>]*>/i', '$1', $v);
          
        }

        // For the_title and the_content only
        if (array_key_exists($k, $columns)) {
          
            $res = wp_update_post( array(
                'ID'         => $pid,
                $columns[$k] => $v
            ));
        }

        // Save an Advanced Custom Field
        if ( strpos($k, 'field_') !== false ) {
          
          $res = update_field($k,$v,$pid);
          
        }

        // Save a WordPress Custom Field
        if ( strpos($k, 'field_') === false && !array_key_exists($k, $columns) ) {
          
          $res = update_post_meta($pid,$k,$v);
          
        }
        
        // -- ** added for hashtagger **
        if ( $k === 'the_content' ) {
          
          $this->generate_tags( $pid );
          
        }
        // -- ** added for hashtagger **

        // Good? No? Yes?
        $json['success'] = ($res > 0) ? true : false;

      } 

      header('Content-Type: application/json');
      print json_encode($json);
      exit();
      // -- Taken from Barely
      
    }
    
    
    /**
		 * add hash symbol to WP tags
     *
     * @since 3.7
     */
    function add_hash_to_single_tag( $term, $taxonomy ) {
      
      if ( 'post_tag' == $term->taxonomy ) {
          
		// since v 7.2.1
		// only add if not existing
		if ( substr( $term->name, 0, 1 ) != '#' ) {
        
			$term->name = '#' . $term->name;
			
		}
        
      }
      
      return $term;
        
    }
    
    
    /**
     * uninstall plugin
     */
    function uninstall() {
      
      if( is_multisite() ) {
        
        $this->uninstall_network();
        
      } else {
        
        $this->uninstall_single();
        
      }
      
    }
    
    
    /**
     * uninstall network wide
     */
    function uninstall_network() {
      
      global $wpdb;
      
      $activeblog = $wpdb->blogid;
      $blogids = $wpdb->get_col( esc_sql( 'SELECT blog_id FROM ' . $wpdb->blogs ) );
      
      foreach ($blogids as $blogid) {
        
        switch_to_blog( $blogid );
        $this->uninstall_single();
        
      }
      
      switch_to_blog( $activeblog );
      
    }
    
    /**
     * uninstall single blog
     */
    function uninstall_single() {
      
      foreach ( $this->settings as $key => $value) {
          
        delete_option( 'swcc_htg_' . $key );
        
      }
      
    }
   
    
	/**
		 * init backend
		 */
    function admin_menu() {
      
      $this->admin_handle = add_options_page( 'hashtagger ' . __( 'Settings' ), '#hashtagger', 'manage_options', 'hashtaggersettings', array( $this, 'admin_page' ) );
      
    }
    
    
    /**
     * add admin css file
     * @since 4
     */
    function admin_style() {
      
      if ( get_current_screen()->id == $this->admin_handle ) {
        
        wp_enqueue_style( 'pp-admin-page', plugins_url( 'assets/css/pp-admin-page-v2.css', $this->get_plugin_file() ) );
        wp_enqueue_style( 'hashtagger-ui', plugins_url( 'assets/css/hashtagger-ui.css', $this->get_plugin_file() ) );
        
      }
      
    }
    
    
		/**
		 * add admin css
		 */
		function admin_css() {

			if ( get_current_screen()->id == $this->admin_handle ) {
        
        ?>
        <style type="text/css">
          .hashtagger #sumbit_regnerate {
            display: block;
            width: 200px;
            height: 40px;
            line-height: 40px;
            margin-top: 40px;
            padding: 0;
            text-align: center;
            opacity: 0;
            transition: opacity .3s;
          }
          .hashtagger #hashtagger_regenerate_confirmation:checked ~ .checktitle #sumbit_regnerate {
            opacity: 1;
          }
        </style>
        <?php
      }
    }
    
    
    /**
     * show admin page
     */
    function admin_page() {
      
      $url = admin_url( 'options-general.php?page=' . $_GET['page'] . '&tab=' );
      $current_tab = 'general';
      
      if ( isset( $_GET['tab'] ) ) {
        
        $current_tab = $_GET['tab'];
        
      }
      
      if ( ! in_array( $current_tab, array('general', 'tags', 'usernames', 'cashtags', 'advanced', 'ignoreblocks', 'ignorelist', 'posttype', 'sectiontype', 'css', 'display', 'regenerate') ) ) {
        
        $current_tab = 'general';
        
      }
	  
      ?>
      <div class="wrap pp-admin-page-wrapper hashtagger">
		<div class="pp-admin-notice-area"><div class="wp-header-end"></div></div>
		<div class="pp-admin-page-header">
			<div class="pp-admin-page-title">
				<h1><?php echo $this->get_plugin_name(); ?></h1>
				<p><strong>PLEASE NOTE</strong><br />Development, maintenance and support of this plugin has been retired. You can use this plugin as long as is works for you. Thanks for your understanding.<br />Regards, Peter</p>
			</div>
		</div>
      
        <h2 class="nav-tab-wrapper">
          <a href="<?php echo $url . 'general'; ?>" class="nav-tab<?php if ( 'general' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Information', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'tags'; ?>" class="nav-tab<?php if ( 'tags' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Tags', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'usernames'; ?>" class="nav-tab<?php if ( 'usernames' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Usernames', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'cashtags'; ?>" class="nav-tab<?php if ( 'cashtags' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Cashtags', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'advanced'; ?>" class="nav-tab<?php if ( 'advanced' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Advanced', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'ignoreblocks'; ?>" class="nav-tab<?php if ( 'ignoreblocks' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Ignore Blocks', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'ignorelist'; ?>" class="nav-tab<?php if ( 'ignorelist' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Ignore List', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'posttype'; ?>" class="nav-tab<?php if ( 'posttype' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Post Types', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'sectiontype'; ?>" class="nav-tab<?php if ( 'sectiontype' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Section Types', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'css'; ?>" class="nav-tab<?php if ( 'css' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'CSS Style', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'display'; ?>" class="nav-tab<?php if ( 'display' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Display', 'hashtagger' ); ?></a>
          <a href="<?php echo $url . 'regenerate'; ?>" class="nav-tab<?php if ( 'regenerate' == $current_tab ) { echo ' nav-tab-active'; } ?>"><?php _e( 'Regnerate', 'hashtagger' ); ?></a>
        </h2>
        <div class="postbox">
          <div class="inside">
            <?php if ( 'regenerate' == $current_tab ) { ?>
              <?php $objects = $this->get_objects(); ?>
                <?php 
                  if ( count( $objects ) == 0 ) {
                    echo '<p>' . __( 'No objects to process!', 'hashtagger' ) . '</p>';
                  } else {
                    echo '<div id="hashtagger_ajax_area"><p><span class="form-invalid">' . __( 'JavaScript must be enabled to use this feature.' ) . '</span></p></div>';
                    add_action( 'admin_print_footer_scripts', array( $this, 'add_regenerate_js' ) );
                  }
                ?>
            <?php } else { ?>
              <form method="post" action="options.php" id="pp-plugin-settings-hashtagger">
                <?php if ( 'general' == $current_tab ) { ?>
                  <p>#hashtags <?php _e( 'currently link to', 'hashtagger'); ?> <code style="white-space: nowrap"><?php echo $this->tag_base_url(); ?></code>.</p>
                  <p><?php printf( __( 'The <b>Tag base</b> for the Archive URL can be changed on %s page', 'hashtagger' ), '<a href="'. admin_url( 'options-permalink.php' ) .'">' . __( 'Permalink Settings' ) . '</a>' ); ?>.</p>
                <?php } else {
                  settings_fields( 'hashtagger_settings_' . $current_tab );   
                  do_settings_sections( 'hashtagger_settings_section_' . $current_tab );
                  submit_button(); 
                } ?>
              </form>
            <?php } ?>
          </div>
        </div>
      </div>
      <?php
    }
    
    
    /**
     * get tag base URL
     */
    private function tag_base_url() {
      
      global $wp_rewrite;
      
      return get_home_url( null, str_replace( '%post_tag%', '[hashtag]', $wp_rewrite->get_extra_permastruct( 'post_tag' ) ) );
      
    }
    
    
    /**
     * init admin
     */
    function admin_init() {
      
      $this->add_setting_sections( array (
      
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-tags',
            'page'  => 'hashtagger_settings_section_tags',
            'name'  => 'hashtagger_settings_tags'
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_tags_allow_numeric',
              'callback' => 'admin_tags_allow_numeric',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_tags_no_links',
              'callback' => 'admin_tags_no_links',
              'register' => true
              
            )
          )
        ),
      
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-usernames',
            'page'  => 'hashtagger_settings_section_usernames',
            'name'  => 'hashtagger_settings_usernames',
            'register' => true
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_usernames',
              'callback' => 'admin_usernames',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_usernamesnick',
              'callback' => 'admin_usernamesnick',
              'register' => true
              
            )
          )
        ),
      
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-cashtags',
            'page'  => 'hashtagger_settings_section_cashtags',
            'name'  => 'hashtagger_settings_cashtags'
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_cashtags',
              'callback' => 'admin_cashtags',
              'register' => true
            )
          )
        ),
      
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-advanced',
            'page'  => 'hashtagger_settings_section_advanced',
            'name'  => 'hashtagger_settings_advanced'
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_advanced_nodelete',
              'callback' => 'admin_advanced_nodelete',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_advanced_single',
              'callback' => 'admin_advanced_single',
              'register' => true
            )
          )
        ),
        
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-ignoreblocks',
            'page'  => 'hashtagger_settings_section_ignoreblocks',
            'name'  => 'hashtagger_settings_ignoreblocks'
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_ignoreblock_code',
              'callback' => 'admin_ignoreblock_code',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_ignoreblock_pre',
              'callback' => 'admin_ignoreblock_pre',
              'register' => true
            )
          )
        ),
        
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-ignorelist',
            'page'  => 'hashtagger_settings_section_ignorelist',
            'name'  => 'hashtagger_settings_ignorelist'
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_ignorelist',
              'callback' => 'admin_ignorelist',
              'sanitize' => 'admin_ignorelist_validate',
              'register' => true
            )
          )
        ),
      
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-posttype',
            'page'  => 'hashtagger_settings_section_posttype',
            'name'  => 'hashtagger_settings_posttype'
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_posttype_page',
              'callback' => 'admin_posttype_page',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_posttype_custom',
              'callback' => 'admin_posttype_custom',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_posttype_post',
              'callback' => 'admin_posttype_post',
              'register' => false
            )
          )
        ),
      
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-sectiontype',
            'page'  => 'hashtagger_settings_section_sectiontype',
            'name'  => 'hashtagger_settings_sectiontype'
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_sectiontype_title',
              'callback' => 'admin_sectiontype_title',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_sectiontype_excerpt',
              'callback' => 'admin_sectiontype_excerpt',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_sectiontype_content',
              'callback' => 'admin_sectiontype_content',
              'register' => false
            )
          )
        ),
      
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-css',
            'page'  => 'hashtagger_settings_section_css',
            'name'  => 'hashtagger_settings_css'
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_cssclass',
              'callback' => 'admin_cssclass',
              'register' => true,
              'sanitize' => 'admin_cssclass_validate'
            ),
            array(
              'id'       => 'swcc_htg_cssclass_notag',
              'callback' => 'admin_cssclass_notag',
              'register' => true,
              'sanitize' => 'admin_cssclass_validate'
            ),
            array(
              'id'       => 'swcc_htg_usernamescssclass',
              'callback' => 'admin_usernamescssclass',
              'register' => true,
              'sanitize' => 'admin_cssclass_validate'
            ),
            array(
              'id'       => 'swcc_htg_cashtagcssclass',
              'callback' => 'admin_cashtagcssclass',
              'register' => true,
              'sanitize' => 'admin_cssclass_validate'
            )
          )
        ),
      
        array(
          'section' => array(
            'id'    => 'hashtagger-settings-display',
            'page'  => 'hashtagger_settings_section_display',
            'name'  => 'hashtagger_settings_display',
            'register' => true
          ),
          'fields' => array(
            array(
              'id'       => 'swcc_htg_display_nosymbols',
              'callback' => 'admin_display_nosymbols',
              'register' => true
            ),
            array(
              'id'       => 'swcc_htg_display_add_symbol_to_tag',
              'callback' => 'admin_display_add_symbol_to_tag',
              'register' => true
              
            )
          )
        )
        
      ) );

    }
    
    
    /**
     * handle the settings field : allow numeric
     */
    function admin_tags_allow_numeric() {
      
      $this->print_slider_check( 
        'swcc_htg_tags_allow_numeric', 
        ( $this->settings['tags_allow_numeric'] == true ), 
        false, 
        __( 'Allow tags starting with numbers', 'hashtagger' ), 
        'settings_tags_allow_numeric', 
        '<span class="dashicons dashicons-warning"></span>' . __( 'Please note that this is not commonly used. Twitter, Instagram, YouTube, Pinterest for instance do not support hashtags starting with or using only numbers.', 'hashtagger')
      );
      
    }
    
    
    /**
     * handle the settings field : no link creation
     */
    function admin_tags_no_links() {
      
      $this->print_slider_check( 
        'swcc_htg_tags_no_links', 
        ( $this->settings['tags_no_links'] == true ), 
        false, 
        __( 'Only create tags from #hashtags, but do not show links', 'hashtagger' ),
        'settings_tags_no_links', 
        ''
      );
            
    }
    
    
    /**
     * handle the settings field : user names
     */
    function admin_usernames() {
      
      $curvalue = $this->settings['usernames'];
      
      $this->print_select_box(
        'swcc_htg_usernames',
        __( 'Handling of @usernames', 'hashtagger' ),
        'settings_usernames_usernames',
        array(
          array(
            'value'    => 'NONE',
            'title'    => __('Ignore @usernames', 'hashtagger' ),
            'selected' => ( $curvalue == 'NONE' )
          ),
          array(
            'value'    => 'PROFILE',
            'title'    => __( 'Link @usernames to users profile page', 'hashtagger' ),
            'selected' => ( $curvalue == 'PROFILE' )
          ),
          array(
            'value'    => 'WEBSITE-SAME',
            'title'    => __( 'Link @usernames to users website in same browser tab', 'hashtagger'),
            'selected' => ( $curvalue == 'WEBSITE-SAME' )
          ),
          array(
            'value'    => 'WEBSITE-NEW',
            'title'    => __( 'Link @usernames to users website in new browser tab', 'hashtagger'),
            'selected' => ( $curvalue == 'WEBSITE-NEW' )
          )
        )
      );
      
    }
    
    
    /**
     * handle the settings field : use nicknames instead of usernames
     */
    function admin_usernamesnick() {
      
      $this->print_slider_check( 
        'swcc_htg_usernamesnick', 
        ( $this->settings['usernamesnick'] == true ), 
        false, 
        __( 'Use @nicknames instead of @usernames', 'hashtagger' ),
        'settings_usernames_nicknames', 
        '<span class="dashicons dashicons-shield"></span>' . __( 'Highly recommended to enhance WordPress security!', 'hashtagger')
      );

    }
    
    
    /**
     * handle the settings field : cashtags
     */
    function admin_cashtags() {
      
      $curvalue = $this->settings['cashtags'];
      
      $this->print_select_box(
        'swcc_htg_cashtags',
        __( 'Handling of $cashtags', 'hashtagger' ),
        'settings_cashtags',
        array(
          array(
            'value'    => 'NONE',
            'title'    =>  __('Ignore $cashtags', 'hashtagger' ),
            'selected' => ( $curvalue == 'NONE' )
          ),
          array(
            'value'    => 'MARKETWATCH-SAME',
            'title'    => __( 'Link $cashtags to MarketWatch in same browser tab', 'hashtagger' ),
            'selected' => ( $curvalue == 'MARKETWATCH-SAME' )
          ),
          array(
            'value'    => 'MARKETWATCH-NEW',
            'title'    => __( 'Link $cashtags to MarketWatch in new browser tab', 'hashtagger' ),
            'selected' => ( $curvalue == 'MARKETWATCH-NEW' )
          ),
          array(
            'value'    => 'GOOGLE-SAME',
            'title'    => __( 'Link $cashtags to Google Finance in same browser tab', 'hashtagger'),
            'selected' => ( $curvalue == 'GOOGLE-SAME' )
          ),
          array(
            'value'    => 'GOOGLE-NEW',
            'title'    => __( 'Link $cashtags to Google Finance in new browser tab', 'hashtagger'),
            'selected' => ( $curvalue == 'GOOGLE-NEW' )
          ),
          array(
            'value'    => 'YAHOO-SAME',
            'title'    => __( 'Link $cashtags to Yahoo Finance in same browser tab', 'hashtagger'),
            'selected' => ( $curvalue == 'YAHOO-SAME' )
          ),
          array(
            'value'    => 'YAHOO-NEW',
            'title'    => __( 'Link $cashtags to Yahoo Finance in new browser tab', 'hashtagger'),
            'selected' => ( $curvalue == 'YAHOO-NEW' )
          ),
          array(
            'value'    => 'STOCKTWITS-SAME',
            'title'    => __( 'Link $cashtags to StockTwits in same browser tab', 'hashtagger'),
            'selected' => ( $curvalue == 'STOCKTWITS-SAME' )
          ),
          array(
            'value'    => 'STOCKTWITS-NEW',
            'title'    => __( 'Link $cashtags to StockTwits in new browser tab', 'hashtagger'),
            'selected' => ( $curvalue == 'STOCKTWITS-NEW' )
          )
        )
      );
      
    }

        
    /**
     * handle the settings field : no delete
     */
    function admin_advanced_nodelete() {
      
      $this->print_slider_check( 
        'swcc_htg_advanced_nodelete', 
        ( $this->settings['advanced_nodelete'] == true ), 
        false, 
         __( 'Do not delete unused Tags', 'hashtagger'),
        'settings_advanced_nodelete', 
        ''
      );
      
    }
    
    
    /**
     * handle the settings field : process only single pages
     * @since 4
     */
    function admin_advanced_single() {
      
      $this->print_slider_check( 
        'swcc_htg_advanced_single', 
        ( $this->settings['advanced_single'] == true ), 
        false, 
         __( 'Process only pages containing a single post', 'hashtagger'),
        'settings_advanced_single', 
        ''
      );
      
    }
    
    
    /**
     * handle the settings field : ignore <code> block
     * @since 7.1
     */
    function admin_ignoreblock_code() {
      
      $this->print_slider_check( 
        'swcc_htg_ignoreblock_code', 
        ( $this->settings['ignoreblock_code'] == true ), 
        false, 
         __( 'Ignore text in &lt;code&gt; blocks', 'hashtagger'),
        'settings_ignoreblock_code', 
        ''
      );
      
    }
    
    
    /**
     * handle the settings field : ignore <pre> block
     * @since 7.1
     */
    function admin_ignoreblock_pre() {
      
      $this->print_slider_check( 
        'swcc_htg_ignoreblock_pre', 
        ( $this->settings['ignoreblock_pre'] == true ), 
        false, 
         __( 'Ignore text in &lt;pre&gt; blocks', 'hashtagger'),
        'settings_ignoreblock_pre', 
        ''
      );
      
    }
    
    
    /**
     * handle the settings field : ignore list
     * @since 7
     */
    function admin_ignorelist() {
      
      $this->print_textarea_field(
        'swcc_htg_ignorelist', 
         __( 'Words to ignore (can be a #hashtag, a @username or a $cashtag) - one word per line', 'hashtagger' ),
        'settings_ignorelist', 
        $this->settings['ignorelist']
      );
    
    }
    
    
    /**
     * validate input : ignore list
     * @since 7
     */
    function admin_ignorelist_validate( $input ) {
            
      // v 7.2 - serialize removed
      return array_filter( array_map( 'trim', explode( "\n", sanitize_textarea_field( $input ) ) ) );
      
    }
    
      
    /**
     * handle the settings field : posttype 'post' - this is only a dummy, maybe for future use, currently posts are always on
     */
    function admin_posttype_post() {
      
      $this->print_slider_check( 
        'swcc_htg_posttype_post', 
        true, 
        true, 
         __( 'Posts' ),
        'settings_posttypes_posts', 
        ''
      );
    
    }
    
    
    /**
     * handle the settings field : posttype 'page'
     */
    function admin_posttype_page() {
      
      $this->print_slider_check( 
        'swcc_htg_posttype_page', 
        ( $this->settings['posttype_page'] == true ), 
        false, 
         __( 'Pages' ),
        'settings_posttypes_pages', 
        ''
      );
      
    }
    
    
    /**
     * handle the settings field : posttype 'custom post types'
     */
    function admin_posttype_custom() {
      
      $this->print_slider_check( 
        'swcc_htg_posttype_custom', 
        ( $this->settings['posttype_custom'] == true ), 
        false, 
         __( 'Custom Post Types', 'hashtagger' ),
        'settings_posttypes_custom', 
        ''
      );
     
    }

    
    /**
     * handle the settings field : sectiontype 'title'
     */
    function admin_sectiontype_title() {
      
       $this->print_slider_check( 
        'swcc_htg_sectiontype_title', 
        ( $this->settings['sectiontype_title'] == true ), 
        false, 
         __( 'Title' ),
        'settings_sectiontypes_title', 
        ''
      );
      
    }
    
    
    /**
     * handle the settings field : sectiontype 'excerpt'
     */
    function admin_sectiontype_excerpt() {
      
       $this->print_slider_check( 
        'swcc_htg_sectiontype_excerpt', 
        ( $this->settings['sectiontype_excerpt'] == true ), 
        false, 
         __( 'Excerpt' ),
        'settings_sectiontypes_excerpt', 
        ''
      );
      
    }
    
    
    /**
     * handle the settings field : sectiontype 'content' ' - this is only a dummy, maybe for future use, currently content is always on
     */
    function admin_sectiontype_content() {
      
      $this->print_slider_check( 
        'swcc_htg_sectiontype_content', 
        true,
        true, 
         __( 'Content' ),
        'settings_sectiontypes_content', 
        ''
      );
      
    }
    
    
    /**
     * handle the settings field : css class
     */
    function admin_cssclass() {
      
      $this->print_text_field(
        'swcc_htg_cssclass', 
         __( 'CSS class name(s) for #hashtags', 'hashtagger' ),
        'settings_css_hashtags', 
        $this->settings['cssclass']
      );
    
    }

    
    /**
     * handle the settings field : css class notag
     */
    function admin_cssclass_notag() {
      
      $this->print_text_field(
        'swcc_htg_cssclass_notag', 
         __( 'CSS class name(s) for +#hashtag links', 'hashtagger' ),
        'settings_css_hashtaglinks', 
        $this->settings['cssclass_notag']
      );
    
    }
    
    
    /**
     * handle the settings field : css class for usernames
     */
    function admin_usernamescssclass() {
      
      $this->print_text_field(
        'swcc_htg_usernamescssclass', 
         __( 'CSS class name(s) for @usernames', 'hashtagger' ),
        'settings_css_usernames', 
        $this->settings['usernamescssclass']
      );
      
    }
    
    
    /**
     * handle the settings field : css class for cashtags
     */
    function admin_cashtagcssclass() {
      
      $this->print_text_field(
        'swcc_htg_cashtagcssclass', 
         __( 'CSS class name(s) for $cashtags', 'hashtagger' ),
        'settings_css_cashtags', 
        $this->settings['cashtagcssclass']
      );
    
    }
    
    
    /**
     * validate input : css class
     */
    function admin_cssclass_validate( $input ) {
      
      $classes = explode(' ', $input);
      $css = '';
      
      foreach( $classes as $class ) {
        
        $css = $css . sanitize_html_class( $class ) . ' ';
        
      }
      
      return rtrim( $css );
      
    }


    /**
     * handle the settings field : do not display symbols
     */
    function admin_display_nosymbols() {
      
      $this->print_slider_check( 
        'swcc_htg_display_nosymbols', 
        ( $this->settings['display_nosymbols'] == true ), 
        false, 
         __( 'Remove symbols from links', 'hashtagger'),
        'settings_display', 
        ''
      );
     
    }
    
    
    /**
     * handle the settings field : add hash to tag list
     */
    function admin_display_add_symbol_to_tag() {
      
      $this->print_slider_check( 
        'swcc_htg_display_add_symbol_to_tag', 
        ( $this->settings['display_add_symbol_to_tag'] == true ), 
        false, 
        __( 'Add hash symbol to WordPress Tags', 'hashtagger'),
        'settings_display', 
        ''
      );

    }
    
    
    /**
     * helper function to print out a slider styled checkbox
     * @since  4
     * @param  string $name    field name
     * @param  bool   $checked true/false
     * @param  bool   $disabled true/false
     * @param  string $title   title
     * @param  string $help    anchor to link to in manual
     * @param  string $note    second line
     */
    function print_slider_check( $name, $checked, $disabled, $title, $help, $note ) {
      
      $chk = '';
      if ( $checked ) {
        $chk = ' checked="checked"';
      }
      
      $dis = '';
      if ( $disabled ) {
        $dis = ' disabled="disabled"';
      }
      
      $add = '';
      if ( ! empty( $note ) ) {
        $add = '<br />' . $note;
      }
       
      echo '<p class="toggle"><input type="checkbox" name="' . $name . '" id="' . $name . '" value="1"' . $chk . $dis . ' /><label for="' . $name . '" class="check"></label>' . $title . $add . '</p>';
       
    }
    
    
    /**
     * helper function to print out a select box
     * @since  4
     * @param  string $name    field namee
     * @param  string $title   title
     * @param  string $help    anchor to link to in manual
     * @param  array  $options array of options
     *                         string $value    => option value
     *                         string $title    => option title
     *                         bool   $selected => true/false
     */
    function print_select_box( $name, $title, $help, $options ) {
      
      echo $title . '<br />';
      
      echo '<select name="' . $name . '" id="' . $name . '">';
      
      foreach ( $options as $option ) {
        
        $sel = '';
        if ( $option['selected'] ) {
          $sel = ' selected="selected"';
        }
        
        echo '<option value="' . $option['value'] . '"' . $sel. '>' . $option['title'] . '</option>';
        
      }
      
      echo '</select>';
       
    }
    
    
    /**
     * helper function to print out a text input field
     * @since  4
     * @param  string $name  field namee
     * @param  string $title title
     * @param  string $help  anchor to link to in manual
     * @param  string $value initial value
     */
    function print_textarea_field( $name, $title, $help, $value ) {
      
      echo $title . '<br />';
      
      echo '<textarea class="regular-textarea" name="' . $name . '" id="' . $name , '">' . implode( "\n", $value ) . '</textarea>';
       
    }
    
    
    /**
     * helper function to print out a textarea field
     * @since  4
     * @param  string $name  field namee
     * @param  string $title title
     * @param  string $help  anchor to link to in manual
     * @param  string $value initial value
     */
    function print_text_field( $name, $title, $help, $value ) {
      
      echo $title . '<br />';
      
      echo '<input class="regular-text" type="text" name="' . $name . '" id="' . $name , '" value="' . $value . '" />';
       
    } 
    

    /**
     * helper function to add a complete setting section
     * @since  4
     * @param  array $settings array of settings to add
     *                         array $section =>  array for the setting section
     *                                            string $id    => ID of the section
     *                                            string $page  => section page name
     *                                            string $name  => name of the section
     *                         array $fields   => multidimensional array of fields to add
     *                                            string $id       => ID of the field (option name)
     *                                            string $callback => function to call
     *                                            bool   $register => register as settings option true/false
     *                                            string $sanitize => name of the sanitize function to call
     */
    function add_settings( $settings ) {
     
     
      add_settings_section( $settings['section']['id'], '', null, $settings['section']['page'] );
      
      foreach ( $settings['fields'] as $field ) {
        $args = array();
        if ( !empty ( $field['sanitize'] ) ) {
          $args['sanitize_callback'] = array( $this, $field['sanitize'] );
        }
        if ( $field['register'] ) {
          register_setting( $settings['section']['name'], $field['id'], $args );
        }
        add_settings_field( $field['id'], '' , array( $this, $field['callback'] ), $settings['section']['page'], $settings['section']['id'] );
      }
      
      return;
      
    }
    
    
    /**
     * helper function to add multiple setting sections
     * @since  4
     * @param  array $sections array of setting sections to add
     * @see    add_settings()
     */
    function add_setting_sections( $sections ) {
      
      foreach( $sections as $section ) {
        
        $this->add_settings( $section );
        
      }
      
    }
    
    
    /**
     * get an array of all objects to process depending on settings
     */
    function get_objects() {
      
      $post_types = array();
      
      if ( $this->settings['posttype_custom'] ) {
        
        $post_types = get_post_types( array( 'public' => true, '_builtin' => false ), 'names', 'and' );
        
      }
      
      if ( $this->settings['posttype_page'] ) {
        
        $post_types[] = 'page';
        
      }
      
      $post_types[] = 'post';
      
      return get_posts( array( 'post_type' => $post_types, 'posts_per_page' => -1 ) );
      
    }
    
    
    /**
	   * Strip all block that should be ignoread and save them
     *
     * @since  7.1
     * @param  string $content original content
     * @return string stripped content
     * @access private
     */
    private function backup_blocks_to_ignore( $content ) {
      
      $this->ignore_block_bak = array();
        
      if ( $this->settings['ignoreblock_code'] ) {
                           
        $content = preg_replace_callback( '/<code[^>]*>(.*?)<\/code>/is', array( $this, 'do_backup_block' ), $content );
        
      }
      
      if ( $this->settings['ignoreblock_pre'] ) {
                           
        $content = preg_replace_callback( '/<pre[^>]*>(.*?)<\/pre>/is', array( $this, 'do_backup_block' ), $content );
        
      }
      
      return $content;
      
    }
    
    
    /**
	   * Strip a single block to ignore and back it up
     * used as allback for preg_replace_callback() in function backup_blocks_to_ignore()
     *
     * @since  7.1
     * @param  string $match found match
     * @return string replacement
     * @access private
     */
    private function do_backup_block( $match ) {
      
      $i = count( $this->ignore_block_bak );
      
      // backup
      $this->ignore_block_bak[$i] = $match[0];
      
      return $this->ignore_block_replace . sprintf( '%08d', $i );
     
    }


    /**
	   * Restore all saved blocks
     *
     * @since  7.1
     * @param  string $content stripped content
     * @return string content containing restored ignored blocks
     * @access private
     */
    private function restore_ignored_blocks( $content ) {
      
      if( ! empty( $this->ignore_block_bak ) ) {
        
        //we have backup content
        
        while ( strpos( $content, $this->ignore_block_replace ) !== false ) {
          
          // we have do to this n times to catch nested blocks
          $content = preg_replace_callback( '/' . preg_quote( $this->ignore_block_replace ) . '([0-9]{8})/', array( $this, 'do_restore_block' ), $content );
          
        }
        
      }
      
      return $content;
      
    }
    
    
    /**
	   * Restore a single backed up block
     * used as allback for preg_replace_callback() in function restore_ignored_blocks()
     *
     * @since  7.1
     * @param  string $match found match
     * @return string replacement
     * @access private
     */
    private function do_restore_block( $match ) {
      
      $i = (int)$match[1];
      return $this->ignore_block_bak[$i];
     
    }


    /**
     * add JavaScript to regenerate all objets to footer
     */
    function add_regenerate_js() {
      
      $objects = $this->get_objects();
      $ids = array();
      
      foreach( $objects as $object ) {
        
        $ids[] = $object->ID;
        
      }
      
      $ids = implode( ',', $ids );
      
      ?>
        <script type='text/javascript'>
          var object_ids = [<?php echo $ids; ?>];
          var objects = <?php echo count( $objects ); ?>;
          var counter = 0;
          var abort = false;
          jQuery( '#hashtagger_ajax_area' ).html( '<table class="form-table"><tbody><tr><td class="toggle autoheight"><input type="checkbox" name="hashtagger_regenerate_confirmation" id="hashtagger_regenerate_confirmation" value="ok" /><label for="hashtagger_regenerate_confirmation" class="check"></label><div class="checktitle"><?php _e( 'Regenerate all existing objects using the current settings', 'hashtagger' ); ?><input type="button" name="sumbit_regnerate" id="sumbit_regnerate" class="button button-primary button-large" value="<?php _e( 'Process all objects', 'hashtagger' ); ?> (<?php echo count( $objects ); ?>)"  /></div></td></tr></tbody></table>' );
          jQuery( '#sumbit_regnerate' ).click( function() {             
            jQuery( '#hashtagger_ajax_area' ).html( '<p><?php _e( 'Please be patient while objects are processed. Do not close or leave this page.', 'hashtagger' ); ?></p><p><div style="width: 100%; height: 40px; border: 2px solid #222; border-radius: 5px; background-color: #FFF"><div id="hashtagger_regnerate_progressbar" style="width: 0; height: 100%; background-image: url(<?php echo plugins_url( 'assets/img/progress.png', $this->get_plugin_file() ); ?>); background-repeat: repeat-x" ></div></div></p><p id="hashtagger_abort_area"><input type="button" name="cancel_regnerate" id="cancel_regnerate" class="button button-secondary button-large" value="<?php _e( 'Abort regeneration', 'hashtagger' ); ?>" /></p>' );
            jQuery( '#cancel_regnerate' ).click( function() { 
              abort = true;
              jQuery( '#hashtagger_abort_area' ).html( '<strong><?php _e( 'Aborting process...', 'hashtagger' ); ?></strong>' );
            });
            regenerate_object();
          });
          function regenerate_object() {
            var object_id = object_ids[0];
            jQuery.ajax( { 
              type: 'POST', 
              url: ajaxurl, 
              data: { 'action': 'hashtagger_regenerate', 'id': object_id }, 
              success: function(response) {  
                counter++;
                jQuery( '#hashtagger_regnerate_progressbar' ).width( ( counter * 100 / objects ) + '%' );
                if ( abort ) {
                  abortstring = '<?php _e( 'Process aborted. {COUNTER} of {OBJECTS} objects have been processed.', 'hashtagger'); ?>';
                  abortstring = abortstring.replace( '{COUNTER}', counter );
                  abortstring = abortstring.replace( '{OBJECTS}', objects );
                  jQuery( '#hashtagger_abort_area' ).html( abortstring );
                } else {
                  object_ids.shift();
                  if ( object_ids.length > 0 ) {
                    regenerate_object();
                  } else {
                    donestring = '<?php _e( 'All done. {OBJECTS} objects have been processed.', 'hashtagger' ); ?>';
                    donestring = donestring.replace( '{OBJECTS}', objects );
                    jQuery( '#hashtagger_abort_area' ).html( donestring );
                  }
                }
              }
            } );
          }
        </script>
      <?php
      
    }

    
    /**
     * handle ajax call for one object to regenerate
     *
     * @uses generate_tags()
     */
    function admin_hashtagger_regenerate() {
      
      $id = (int)$_REQUEST['id'];
      $this->generate_tags( $id );
      echo $id;
      die();
      
    }
    
    
    /**
     * create nonce
     *
     * @since  7
     * @access private
     * @return string Nonce
     */
    private function get_nonce() {
      
      return wp_create_nonce( 'pp-hashtagger_dismiss_admin_notice' );
      
    }
    
    
    /**
     * check nonce
     *
     * @since  7
     * @access private
     * @return boolean
     */
    private function check_nonce() {
      
      return check_ajax_referer( 'pp-hashtagger_dismiss_admin_notice', 'securekey', false );
      
    }
   
    
    /**
     * add admin js files
     */
    function admin_js() {
    
      wp_enqueue_script( 'hashtagger-js', plugins_url( 'assets/js/hashtagger.js', $this->get_plugin_file() ), 'jquery', $this->get_plugin_version(), true );
      
      wp_localize_script( 'hashtagger-js', 'pp_hashtagger_security', array( 'securekey' => $this->get_nonce() ) );
      
    }
    
    
    /**
     * show the nav icons
     *
     * @since 5
     * @access private
     */
    private function show_nav_icons( $icons ) {
       
      foreach ( $icons as $icon ) {
         
        echo '<a href="' . $icon['link'] . '" title="' . $icon['title'] . '"><span class="dashicons ' . $icon['icon'] . '"></span><span class="text">' . $icon['title'] . '</span></a>';
         
      }
      
    }

  }
  
}