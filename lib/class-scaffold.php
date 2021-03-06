<?php
/**
 * Scaffold for Styles and Scripts classes
 * Uses theme customization API
 *
 * @author usabilitydynamics@UD
 * @see https://codex.wordpress.org/Theme_Customization_API
 * @version 0.1
 * @module UsabilityDynamics\AMD
 */
namespace UsabilityDynamics\AMD {

  if( !class_exists( '\UsabilityDynamics\AMD\Scaffold' ) ) {

    abstract class Scaffold {
      
      public $args = NULL;
      
      public static $query_vars = array(
        'amd_asset_type',
        'amd_is_asset',
      );
      
      /**
       * Constructor
       * Must be called in child constructor firstly!
       *
       */
      public function __construct( $args = array(), $context = null ) {
      
        $this->args = wp_parse_args( $args, array(
          'version' => null,
          'type' => '', // style, script
          'minify' => false,
          'load_in_head' => true,
          'permalink' => '', // (assets/wp-amd.js|assets/wp-amd.css)
          'dependencies' => array(),
          'admin_menu' => true,
          'post_type' => false,
        ));

        // If context is provided (e.g. instance of wp-amd bootstrap), get settings from it.
        if( is_object( $context ) && method_exists( $context, 'get' ) ) {
          $this->args[ 'version' ] = $context->get( 'version' );
        }

        // $this->args['permalink'] = '/' . $this->args['permalink'];

        //** Add hooks only if type is allowed */
        if( in_array( $this->get( 'type' ), array( 'script', 'style' ) ) ) {
        
          if( !$this->get( 'post_type' ) ) {
            $this->args[ 'post_type' ] = self::get_post_type( $this->get( 'type' ) );
          }
          
          //** rewrite and respond */
          add_action( 'query_vars', array( __CLASS__, 'query_vars' ) );
          add_filter( 'update_option_rewrite_rules', array( &$this, 'update_option_rewrite_rules' ), 1 );
          add_filter( 'template_include', array( __CLASS__, 'return_asset' ), 1, 1 );
          
          //** Register assets and post_type */
          add_action( 'init', array( &$this, 'register_post_type' ) );
          add_action( 'admin_init', array( &$this, 'admin_init' ) );
          add_filter( 'redirect_canonical', array( $this, 'redirect_canonical') );

          switch( $this->get( 'type' ) ) {
            case 'style':
              add_action( 'wp_print_styles', array( &$this, 'register_asset' ), 999 );
            break;
            case 'script':
            default:
              add_action( 'wp_enqueue_scripts', array( &$this, 'register_asset' ), 999 );
            break;
          }
          
          //** Determine if Admin Menu is enabled */
          if( $this->get( 'admin_menu' ) ) {
            add_action( 'admin_menu', array( &$this, 'add_admin_menu' ) );
            // Override the edit link, the default link causes a redirect loop
            add_filter( 'get_edit_post_link', array( __CLASS__, 'revision_post_link' ) );
          }
        
        }
        
      }

      /**
       * Admin Settings
       *
       * @todo Does not actually save anything, for now only configurable via apply filters.
       * @author potanin@UD
       */
      public function admin_init() {

        add_settings_section( 'wp-amd-rewrites', __( 'Asset Rewrites', get_wp_amd( 'domain' ) ), function() {
          echo '<p>' . __( 'Assets generated by wp-amd may have custom rewrite paths.', get_wp_amd( 'domain' ) ) . '</p>';
        }, 'permalink' );

        add_settings_field( 'wp-amd.rewrites.script', __( 'Script URL', get_wp_amd( 'domain' ) ), function() {
          echo '<code>' . untrailingslashit( get_option( 'home' ) ) . "</code>";
			    echo '<input name="wp-amd[rewrite][script]" type="text" value="/' . ( get_wp_amd( 'assets.script.permalink' ) ) .'" class="regular-text code" readonly="true" />';
        }, 'permalink', 'wp-amd-rewrites' );

        add_settings_field( 'wp-amd.rewrites.style', __( 'Style URL', get_wp_amd( 'domain' ) ), function() {
          echo '<code>' . untrailingslashit( get_option( 'home' ) ) . "</code>";
			    echo '<input name="wp-amd[rewrite][style]" type="text" value="/' . ( get_wp_amd( 'assets.style.permalink' ) ) .'" class="regular-text code" readonly="true" />';
        }, 'permalink', 'wp-amd-rewrites' );

      }

      /**
       * Register AMD Post Types
       *
       *
       */
      public function register_post_type() {

        if( $this->get( 'post_type' ) === 'amd_style' ) {

          $labels = array(
            'name'               => _x( 'Styles', 'post type general name', get_wp_amd( 'domain' ) ),
            'singular_name'      => _x( 'Style', 'post type singular name', get_wp_amd( 'domain' ) ),
            'menu_name'          => _x( 'Styles', 'admin menu', get_wp_amd( 'domain' ) ),
            'name_admin_bar'     => _x( 'Style', 'add new on admin bar', get_wp_amd( 'domain' ) ),
            'add_new'            => _x( 'Add New', 'book', get_wp_amd( 'domain' ) ),
            'add_new_item'       => __( 'Add New Style', get_wp_amd( 'domain' ) ),
            'new_item'           => __( 'New Style', get_wp_amd( 'domain' ) ),
            'edit_item'          => __( 'Edit Style', get_wp_amd( 'domain' ) ),
            'view_item'          => __( 'View Style', get_wp_amd( 'domain' ) ),
            'all_items'          => __( 'All Styles', get_wp_amd( 'domain' ) ),
            'search_items'       => __( 'Search Styles', get_wp_amd( 'domain' ) ),
            'parent_item_colon'  => __( 'Parent Styles:', get_wp_amd( 'domain' ) ),
            'not_found'          => __( 'No books found.', get_wp_amd( 'domain' ) ),
            'not_found_in_trash' => __( 'No books found in Trash.', get_wp_amd( 'domain' ) ),
          );

        }

        if( $this->get( 'post_type' ) === 'amd_script' ) {

          $labels = array(
            'name'               => _x( 'Scripts', 'post type general name', get_wp_amd( 'domain' ) ),
            'singular_name'      => _x( 'Script', 'post type singular name', get_wp_amd( 'domain' ) ),
            'menu_name'          => _x( 'Scripts', 'admin menu', get_wp_amd( 'domain' ) ),
            'name_admin_bar'     => _x( 'Script', 'add new on admin bar', get_wp_amd( 'domain' ) ),
            'add_new'            => _x( 'Add New', 'book', get_wp_amd( 'domain' ) ),
            'add_new_item'       => __( 'Add New Script', get_wp_amd( 'domain' ) ),
            'new_item'           => __( 'New Script', get_wp_amd( 'domain' ) ),
            'edit_item'          => __( 'Edit Script', get_wp_amd( 'domain' ) ),
            'view_item'          => __( 'View Script', get_wp_amd( 'domain' ) ),
            'all_items'          => __( 'All Scripts', get_wp_amd( 'domain' ) ),
            'search_items'       => __( 'Search Scripts', get_wp_amd( 'domain' ) ),
            'parent_item_colon'  => __( 'Parent Scripts:', get_wp_amd( 'domain' ) ),
            'not_found'          => __( 'No books found.', get_wp_amd( 'domain' ) ),
            'not_found_in_trash' => __( 'No books found in Trash.', get_wp_amd( 'domain' ) ),
          );

        }

        register_post_type( $this->get( 'post_type' ), array(
          'labels'              => $labels,
          'can_export'          => true,
          'public'              => false,
          'publicly_queryable'  => false,
          'show_ui'             => false,
          'show_in_menu'        => false,
          'capability_type'     => 'post',
          'supports' =>         array( 'revisions' )
        ));

      }

      /**
       * Prevent Trailing Slash Redirects on Assets
       *
       * @author potanin@UD
       * @param $url
       * @return bool
       */
      public function redirect_canonical( $url ) {
        global $wp_query;

        if( $wp_query->get( 'amd_is_asset' ) ) {
          return false;
        }

        return $url;

      }

      /**
       * Add Administrative Menus
       *
       * @return array
       */
      public function add_admin_menu() {

        // @The pluralization is ghetto, I know.. - potanin@UD
        $name = ucfirst( $this->get( 'type' ) ) . 's';

        $id = add_theme_page( $name, $name, 'edit_theme_options', 'amd-page-' . $this->get( 'type' ), array( $this, 'admin_edit_page' ) );

        add_action( 'admin_print_scripts-' . $id, array( $this, 'admin_scripts' ) );
        add_action( "load-$id", array( $this, 'screen_options' ) );

        add_meta_box( 'amd-publish',        __( 'Publish',    get_wp_amd( 'domain' ) ), array( $this, 'render_metabox_publish' ),      $id, 'side', 'core' );

        if( $this->get( 'dependencies' ) ) {
          add_meta_box( 'amd-dependencies', __( 'Dependency', get_wp_amd( 'domain' ) ), array( $this, 'render_metabox_dependencies' ), $id, 'side', 'core' );
        }

        add_meta_box( 'amd-revisions',    __( 'Revisions',  get_wp_amd( 'domain' ) ), array( $this, 'render_metabox_revisions' ),    $id, 'side', 'core' );

      }

      /**
       *
       * @todo Implement...
       */
      static public function render_metabox_publish() {

        ?> <input class="button-primary" type="submit" name="publish" value="<?php _e( 'Save Asset', get_wp_amd( 'domain' ) ); ?>"/>

        <?php if( $_GET[ 'page' ] === 'amd-page-style' ) { ?>
          <ul>
            <li><a href="<?php echo admin_url( 'customize.php' ); ?>"><?php _e( 'Edit in Customizer', get_wp_amd( 'domain' ) ); ?></a></li>
          </ul>
        <?php } ?>

        <?php
      }

      /**
       *
       * @todo Implement...
       */
      static public function screen_options() {

        add_screen_option( 'layout_columns', array(
          'max' => 2,
          'default' => 2
        ));

        get_current_screen()->add_help_tab( array(
          'id'      => 'overview',
          'title'   => __('Overview', 'wp-amd'),
          'content' =>
            '<p>' . __( 'Coming soon.', 'wp-amd' ) . '</p>'
        ) );

        get_current_screen()->set_help_sidebar(
          '<p><strong>' . __( 'For more information:' ) . '</strong></p>' .
          '<p>' . __( '<a href="https://github.com/UsabilityDynamics/wp-amd" target="_blank">GitHub Page</a>' ) . '</p>' .
          '<p>' . __( '<a href="https://github.com/UsabilityDynamics/wp-amd/wiki" target="_blank">GitHub Wiki</a>' ) . '</p>' .
          '<p>' . __( '<a href="http://UsabilityDynamics.com" target="_blank">UsabilityDynamics.com</a>' ) . '</p>'
        );

      }
      
      /**
       * register_admin_styles function.
       * adds styles to the admin page
       *
       * Add action to denqueue or deregister scripts which cause conflicts and errors.
       *
       * @access public
       * @return void
       */
      public function admin_scripts() {
        do_action( 'amd::admin_scripts::edit_page' );
        
        wp_register_script( 'wp-amd-ace', plugins_url( '/static/scripts/src/ace/ace.js', dirname( __FILE__ ) ), array(), $this->get( 'version' ), true );
        wp_enqueue_style( 'wp-amd-admin-styles', plugins_url( '/static/styles/wp-amd.css', dirname( __FILE__ ) ) );
        wp_enqueue_script( 'wp-amd-admin-scripts', plugins_url( '/static/scripts/wp-amd.js',  dirname( __FILE__ ) ), array( 'wp-amd-ace', 'jquery-ui-resizable' ), $this->get( 'version' ), true );
        wp_enqueue_script( 'wp-amd-admin-scripts' );

      }
      
      /**
       * 
       */
      public function admin_edit_page() {
        $msg = 0;

        // the form has been submited save the options
        if( !empty( $_POST ) && check_admin_referer( 'update_amd_' . $this->get( 'type' ), 'update_amd_' . $this->get( 'type' ) . '_nonce' ) ) {

          $data = stripslashes( $_POST [ 'content' ] );
          $post_id = $this->save_asset( $data );
          $updated = true;
          $msg = 1;

          if( isset( $_POST[ 'dependency' ] ) ) {
            add_post_meta( $post_id, 'dependency', $_POST[ 'dependency' ], true ) or update_post_meta( $post_id, 'dependency', $_POST[ 'dependency' ] );
          }

          // Redirect back to self.
          die( wp_redirect( admin_url( 'themes.php?page=amd-page-' . $this->get( 'type' ) . '&updated=true' ) . '&message=' . $msg ) );

        }

        if( isset( $_GET[ 'message' ] ) ) {
          $msg = (int) $_GET[ 'message' ];
        }

        $messages = array(
          0 => false,
          1 => sprintf( __( 'Global %s saved. <a href="%s" target="_blank">View in browser</a>.', get_wp_amd( 'domain' ) ), $this->get( 'type' ), home_url( apply_filters( 'wp-amd:' . $this->get( 'type' ) . ':path', 'assets/wp-amd.' .  $this->get( 'extension' ), $this ) ) ),
          5 => isset( $_GET[ 'revision' ] ) ? sprintf( __( '%s restored to revision from %s, <em>Save changes for the revision to take effect</em>', get_wp_amd( 'domain' ) ), ucfirst( $this->get( 'type' ) ), wp_post_revision_title( (int) $_GET[ 'revision' ], false ) ) : false
        );
        
        $data = self::get_asset( $this->get( 'type' ) );
        $data = $data ? $data : array();
        $data[ 'msg' ] = $messages[ $msg ];
        $data[ 'post_content' ] = isset( $data[ 'post_content' ] ) ? $data[ 'post_content' ] : '';
        

        $template = WP_AMD_DIR . 'static/templates/' . $this->get( 'type' ) . '-edit-page.php';
        
        if( file_exists( $template ) ) {
          include( $template );
        }
      }

      /**
       * Saves/updates asset.
       *
       *
       * @todo After POST save, do wp_redirect() back to self to avoid re-posting data accidentally on page reload. - potanin@UD
       *
       * @access public
       *
       * @param $value
       *
       * @internal param mixed $js
       * @return void
       */
      public function save_asset( $value = null ) {

        if( !$post = self::get_asset( $this->get( 'type' )  ) ) {

          $data = array(
            'post_title' => ( 'Global AMD ' . ucfirst( $this->get( 'type' ) ) ),
            'post_content' => $value,
            'post_status' => 'publish',
            'post_type' => $this->get( 'post_type' ),
          );

          $post_id = wp_insert_post( $data );

          // @todo Handle is_wp_error;
          if( $post_id && !is_wp_error( $post_id ) ) {
            add_post_meta( $post_id, 'theme_relation', sanitize_key( wp_get_theme()->get( 'Name' ) ), true );
          }

        } else {
          $post[ 'post_content' ] = $value;
          $post_id = wp_update_post( $post );
        }

        $this->cache_asset( $value );

        return $post_id;

      }

      /**
       * Cache Static Asset.
       *
       * @param $value
       */
      public function cache_asset( $value ) {

        $_cache_path = trailingslashit( $this->get( 'disk_cache' ) ) . 'wp-amd-' . $this->get( 'type' ) . '.' . $this->get( 'extension' );

        if( $this->get( 'disk_cache' ) && is_dir( $this->get( 'disk_cache' ) ) && ( !is_file( $_cache_path ) || is_writable( $_cache_path ) ) ) {

          if( $value ) {
            file_put_contents( $_cache_path, $value );
          } else {
            unlink( $_cache_path );
          }

        };

      }
      
      /**
       * revision_post_link function.
       * Override the edit link, the default link causes a redirect loop
       *
       * @access public
       * @param mixed $post_link
       * @return void
       */
      public static function revision_post_link( $post_link ) {
        global $post;
        if( isset( $post ) && strstr( $post_link, 'action=edit' ) && !strstr( $post_link, 'revision=' ) ) {
          switch( true ) {
            case ( self::get_post_type( 'script' ) == $post->post_type ):
              $post_link = 'themes.php?page=amd-page-script';
              break;
            case ( self::get_post_type( 'style' ) == $post->post_type ):
              $post_link = 'themes.php?page=amd-page-style';
              break;
          }
        }
        return $post_link;
      }

      /**
       * add_metabox function.
       *
       * @access public
       *
       * @param $post_id
       *
       * @internal param mixed $js
       * @return void
       */
      public function add_metaboxes( $post_id ) {


      }

      /**
       * @param $post
       *
       * @internal param $_post
       */
      public function render_metabox_dependencies( $post ) {
        $dependency = array();
        if( !empty( $post[ 'ID' ] ) ) {
          $dependency = get_post_meta( $post[ 'ID' ], 'dependency', true );
          $dependency = !is_array( $dependency ) ? array() : $dependency;
        }
        ?>
        <ul>
        <?php foreach( (array)$this->get( 'dependencies' ) as $key => $data ) : ?>
          <?php if( !$this->is_wp_dependency( $key ) && empty( $data[ 'url' ] ) ) continue; ?>
          <li>
            <label>
              <input data-amd-dependency="<?php echo $data[ 'id' ]; ?>" <?php if( current_theme_supports( $data[ 'id' ] ) ) { ?>disabled<?php } ?> type="checkbox" name="dependency[]" value="<?php echo $key; ?>" <?php checked( current_theme_supports( $data[ 'id' ] ) || in_array( $key, $dependency ), true ); ?> />
              <a href="<?php echo $data[ 'infourl' ]; ?>" target="_blank"> <?php echo $data[ 'name' ]; ?> </a>
            </label>
          </li>
        <?php endforeach; ?>
        </ul>
        <?php
      }

      /**
       * @param $post
       *
       * @internal param $_post
       */
      public function render_metabox_revisions( $post ) {
        if( isset( $post[ 'ID' ] ) ) {
          wp_list_post_revisions( $post[ 'ID' ], 'revision' );
        }
      }

      /**
       * Determine if dependency belongs to Wordpress ( already registered by WordPress )
       *
       * @param $dep
       *
       * @internal param $dependency
       *
       * @return bool
       */
      public function is_wp_dependency( $dep ) {
        switch( $this->get( 'type' ) ) {
          case 'script':
            if( wp_script_is( $dep, 'registered' ) ) {
              return true;
            }
            break;
          case 'style':
            if( wp_style_is( $dep, 'registered' ) ) {
              return true;
            }
            break;
        }        
        return false;
      }

      /**
       * Register dependencies
       *
       * @param $dependencies
       *
       * @return array
       */
      public function register_dependencies( $dependencies ) {
        $all_deps = $this->get( 'dependencies' );
        $registered = array();

        foreach( (array) $dependencies as $dependency ) {
          if( isset( $all_deps[ $dependency ] ) ) {
            if( $this->is_wp_dependency( $dependency ) ) {
              array_push( $registered, $dependency );
            } else if( !empty( $all_deps[ $dependency ][ 'url' ] ) ) {

              $_deps = isset( $all_deps[ $dependency ] ) && isset( $all_deps[ $dependency ][ 'dependencies' ] ) ? $all_deps[ $dependency ][ 'dependencies' ] : array();

              switch( $this->get( 'type' ) ) {
                case 'script':
                  wp_register_script( $dependency, $all_deps[ $dependency ][ 'url' ], $_deps, !empty( $all_deps[ $dependency ][ 'version' ] ) ? $all_deps[ $dependency ][ 'version' ] : $this->get( 'version' ), true );
                  array_push( $registered, $dependency );
                break;
                case 'style':
                  wp_register_style( $dependency, $all_deps[ $dependency ][ 'url' ], $_deps, !empty( $all_deps[ $dependency ][ 'version' ] ) ? $all_deps[ $dependency ][ 'version' ] : $this->get( 'version' ) );
                  array_push( $registered, $dependency );
                break;
                default: break;
              }
            }
          }
        }

        return $registered;
      }
      
      /**
       * New query vars
       *
       * @param type $query_vars
       * @return string
       */
      public static function query_vars( $query_vars ) {
        return array_unique( array_merge( $query_vars, self::$query_vars ) );
      }

      /**
       * Dynamic Rules
       *
       * @param $rules
       *
       * @internal param \UsabilityDynamics\AMD\type $current
       * @return type
       */
      public function update_option_rewrite_rules( $rules = array() ) {
        $new_rules = array( '' . $this->get( 'permalink' ) . '$' => 'index.php?' . self::$query_vars[0] . '=' . $this->get( 'type' ) . '&' . self::$query_vars[1] . '=1' );
        $rules =  array_merge_recursive( (array) $new_rules, (array) $rules );
        return $rules;
      }
      
      /**
       *
       * @global type $wp_query
       * @param type $template
       * @return type
       */
      public static function return_asset( $template ) {
        global $wp_query;
        
        if ( ( $type = get_query_var( self::$query_vars[0] ) ) && in_array( $type, array( 'script', 'style' ) ) ) {

          $headers = apply_filters( 'amd:' . $type . ':headers', array(
            'Cache-Control'   => 'public,max-age=31536000,no-transform',
            'Pragma'          => 'cache',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Vary'            => 'Accept-Encoding'
          ));

          switch( $type ) {
            case 'script':
              $headers[ 'Content-Type' ] = isset( $headers[ 'Content-Type' ] ) && $headers[ 'Content-Type' ] ? $headers[ 'Content-Type' ] : 'application/javascript; charset=' . strtolower( get_bloginfo( 'charset' ) );
            break;
            case 'style':
              $headers[ 'Content-Type' ] = isset( $headers[ 'Content-Type' ] ) && $headers[ 'Content-Type' ] ? $headers[ 'Content-Type' ] : 'text/css; charset=' . strtolower( get_bloginfo( 'charset' ) );
            break;
          }

          foreach( (array) $headers as $_key => $field_value ) {
            @header( "{$_key}: {$field_value}", true );
          }
          
          $data = self::get_asset( $type );

          if ( $data && !empty( $data[ 'post_content' ] ) ) {
            die( $data[ 'post_content' ] );
          } else {
            die( '/** Global asset is empty */' );
          }
        }

        return $template;
      }
      
      /**
       * Registers asset with all selected dependencies
       *
       */
      public function register_asset() {
        $url = $this->get_asset_url();
        $dependencies = array();
        $post = self::get_asset( $this->get( 'type' ) );
        if( !empty( $post ) ) {
          $dependencies = $this->get_saved_dependencies( $post[ 'ID' ] );
          $dependencies = $this->register_dependencies( $dependencies );
        }
        switch( $this->get( 'type' ) ) {
          case 'script':
            wp_enqueue_script( 'wp-amd-script', $url, $dependencies, $this->get_latest_version_id( $post[ 'ID' ] ), !$this->get( 'load_in_head' ) );
          break;
          case 'style':
            wp_enqueue_style( 'wp-amd-style', $url, $dependencies, $this->get_latest_version_id( $post[ 'ID' ] ) );
            break;
        }
      }

      /**
       * Get latest revision ID
       *
       * @param $post_id
       *
       * @return string
       */
      public function get_latest_version_id( $post_id ) {
        $posts = get_posts( array( 'numberposts' => 1, 'post_type' => 'revision', 'post_status' => 'any', 'post_parent' => $post_id ) );
        $post = !empty( $posts ) ? array_shift( $posts ) : false;
        if( $post ) {
          return $post->ID;
        }
        return '1';
      }
      
      /**
       * get_saved_dependencies function
       *
       * @access public
       *
       * @param $post_id
       *
       * @return array|mixed $dependency_arr
       */
      function get_saved_dependencies( $post_id ) {
        $dependency_arr = get_post_meta( $post_id, 'dependency', true );
        if( !is_array( $dependency_arr ) )
          $dependency_arr = array();

        return $dependency_arr;
      }
      
      /**
       * Global JS URL
       * @return bool|string
       *
       * @todo Make flush_rewrite_rules intelligently based on actual permalink changes.
       *
       */
      public function get_asset_url() {
        global $wp_rewrite;
        
        $url = home_url() . '?' . self::$query_vars[0] . '=' . $this->get( 'type' ) . '&' . self::$query_vars[1] . '=1';

        switch( true ) {
          case ( empty( $wp_rewrite->permalink_structure ) ):
            // Do nothing.
          break;
          case ( !array_key_exists( '^' . $this->get( 'permalink' ), $wp_rewrite->rules ) ):
            // Looks like permalink structure is set, but our rules are not.
            // Flush rewrite rules to have correct permalink next time.
            add_action( 'admin_init' , "flush_rewrite_rules" );
          break;
          default:
            $url = home_url( $this->get( 'permalink' ) );
          break;
        }
        
        return $url;
      }

      /**
       * Returns asset by type ( script, style )
       *
       * @access public
       *
       * @param $type
       *
       * @return void
       */
      public static function get_asset( $type ) {

        $posts = get_posts( array(
          'numberposts' => 1, 
          'post_type' => self::get_post_type( $type ), 
          'post_status' => 'publish',
          'meta_key' => 'theme_relation',
          'meta_value' => sanitize_key( wp_get_theme()->get( 'Name' ) ),
        ));

        $post = is_array( $posts ) ? array_shift( $posts ) : false;

        if( !$post ) {
          return false;
        }

        return $post ? get_object_vars( $post ) : false;

      }

      /**
       * @param $type
       * @return string
       */
      public static function get_post_type( $type ) {
        return 'amd_' . $type;
      }
      
      /**
       * Returns required argument
       */
      public function get( $arg = null ) {

        if( !$arg ) {
          return $this->args;
        }

        return isset( $this->args[ $arg ] ) ? $this->args[ $arg ] : NULL;
      }
      
    }

  }

}


      
