<?php
/**
 * Plugin Name: BadgeOS REST API Addon
 * Description: The BadgeOS REST API addon provides the BadgeOS API End-Points.
 * Version:     1.0
 * Author:      BadgeOS
 * Author URI:  https://badgeos.org/
 * Text Domain: bos-api
 */
if ( !defined ( 'ABSPATH' ) ) exit;

register_activation_hook( __FILE__, ['BadgeOS_REST_API_Addon', 'activation' ] );
register_deactivation_hook( __FILE__, ['BadgeOS_REST_API_Addon', 'deactivation' ] );

/**
 * Class BadgeOS_REST_API_Addon
 */ 
final class BadgeOS_REST_API_Addon {
    const VERSION = '1.0';

    /**
     * @var self
     */
    private static $instance = null;

    /**
     * @var self
     */
    public $_api_key = '';

    /**
     * @return $this
     */
    public static function instance() {

        if ( is_null( self::$instance ) && ! ( self::$instance instanceof BadgeOS_REST_API_Addon ) ) {
            self::$instance = new self;

            self::$instance->setup_constants();
            self::$instance->includes();
            self::$instance->hooks();
        }

        return self::$instance;
    }

    /**
     * Activation function hook
     *
     * @return void
     */
    public static function activation() {

        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        update_option( 'bos_api_version', self::VERSION );
    }

    /**
     * Deactivation function hook
     *
     * @return void
     */
    public static function deactivation() {
    }

    /**
     * Upgrade function hook
     *
     * @return void
     */
    public function upgrade() {
        
        if ( get_option ( 'bos_api_version' ) != self::VERSION ) { }

    }

    /**
     * Setup Constants
     */
    private function setup_constants() {

        /**
         * Directory
         */
        define( 'BOS_API_DIR', plugin_dir_path ( __FILE__ ) );
        define( 'BOS_API_DIR_FILE', BOS_API_DIR . basename ( __FILE__ ) );
        define( 'BOS_API_INCLUDES_DIR', trailingslashit ( BOS_API_DIR . 'includes' ) );
        define( 'BOS_API_ASSETS_DIR', trailingslashit ( BOS_API_DIR . 'assets' ) );
        define( 'BOS_API_TEMPLATES_DIR', trailingslashit ( BOS_API_DIR . 'templates' ) );
        define( 'BOS_API_BASE_DIR', plugin_basename(__FILE__));

        /**
         * URLS
         */
        define( 'BOS_API_URL', trailingslashit ( plugins_url ( '', __FILE__ ) ) );
        define( 'BOS_API_ASSETS_URL', trailingslashit ( BOS_API_URL . 'assets' ) );
    }

    /**
     * Include Required Files
     */
    private function includes() {

        if( file_exists( BOS_API_INCLUDES_DIR . 'settings.php' ) ) {
            require_once ( BOS_API_INCLUDES_DIR . 'settings.php' );
        }

        if( file_exists( BOS_API_INCLUDES_DIR . 'api-keys.php' ) ) {
            require_once ( BOS_API_INCLUDES_DIR . 'api-keys.php' );
        }
        
        if( file_exists( BOS_API_INCLUDES_DIR . 'api.php' ) ) {
            require_once ( BOS_API_INCLUDES_DIR . 'api.php' );
        }

        if( file_exists( BOS_API_INCLUDES_DIR . 'points-api.php' ) ) {
            require_once ( BOS_API_INCLUDES_DIR . 'points-api.php' );
        }

        if( file_exists( BOS_API_INCLUDES_DIR . 'achievements-api.php' ) ) {
            require_once ( BOS_API_INCLUDES_DIR . 'achievements-api.php' );
        }

        if( file_exists( BOS_API_INCLUDES_DIR . 'ranks-api.php' ) ) {
            require_once ( BOS_API_INCLUDES_DIR . 'ranks-api.php' );
        }

        if( file_exists( BOS_API_INCLUDES_DIR . 'api-widget.php' ) ) {
            require_once ( BOS_API_INCLUDES_DIR . 'api-widget.php' );
        }
        
    }

    /**
	 * Register hooks
	 */
    private function hooks() {

        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'frontend_enqueue_scripts' ] );
        add_action( 'plugins_loaded', [ $this, 'upgrade' ] );
        add_filter( 'plugin_action_links_'.BOS_API_BASE_DIR, [ $this, 'settings_link' ], 10 ,1 );

        $this->db_upgrade();
    }

    /**
	 * This function will made the necessary changes on existing database to accomodate the points work
	 */
	function db_upgrade() {
		
		global $wpdb;
        
        $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix . "badgeos_achievements' AND column_name = 'api_key'"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE ".$wpdb->prefix . "badgeos_achievements ADD api_key varchar(50) DEFAULT ''");
        }

        $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix . "badgeos_ranks' AND column_name = 'api_key'"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE ".$wpdb->prefix . "badgeos_ranks ADD api_key varchar(50) DEFAULT ''");
        }

        $row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '".$wpdb->prefix . "badgeos_points' AND column_name = 'api_key'"  );
        if(empty($row)){
            $wpdb->query("ALTER TABLE ".$wpdb->prefix . "badgeos_points ADD api_key varchar(50) DEFAULT ''");
        }
    }
    /**
     * Enqueue scripts on admin
     *
     * @param string $hook
     */
    public function admin_enqueue_scripts( $hook ) {

        /**
         * Select2
         */
        wp_enqueue_style( 'bos-api-admin-select2-css', BOS_API_ASSETS_URL . 'css/select2.min.css', null, self::VERSION, null );
        wp_enqueue_script( 'bos-api-admin-select2-js', BOS_API_ASSETS_URL . 'js/select2.min.js', null, self::VERSION, true );
         /**
         * plugin's admin script
         */
        wp_enqueue_style( 'badgeos-jquery-ui-styles' );
        wp_enqueue_script( 'badgeos-jquery-ui-js' );

        wp_enqueue_script( 'bos-api-admin-script', BOS_API_ASSETS_URL . 'js/bos-api-admin-script.js', [ 'jquery' ], self::VERSION, true );
        
        $localize_array = [];
        $localize_array['ajax_url'] = admin_url( 'admin-ajax.php' );

        wp_localize_script( 'bos-api-admin-script', 'BosAPIVars', $localize_array );
        
        wp_enqueue_style( 'bos-api-admin-style', BOS_API_ASSETS_URL . 'css/bos-api-admin-style.css', [], self::VERSION, null );
    }

    public function frontend_enqueue_scripts( $hook ) {
        
        if( is_admin() ) {
            return false;
        }

        wp_enqueue_style( 'bos-api-frontend-style', BOS_API_ASSETS_URL . 'css/bos-api-frontend-style.css', [], self::VERSION, null );
        
        $localize_array = [];
        $localize_array['ajax_url'] = admin_url( 'admin-ajax.php' );

        wp_enqueue_script( 'bos-api-frontend-script', BOS_API_ASSETS_URL . 'js/bos-api-frontend-script.js', array( 'jquery' ), self::VERSION, null );
        wp_localize_script( 'bos-api-frontend-script', 'BosAPIVars', $localize_array );
    }

    /**
     * Add settings link on plugin page
     *
     * @return void
     */
    public function settings_link( $links ) {
        $settings_link = '<a href="admin.php?page=badgeos_settings&bos_s_tab=general&side_tab=bos_restapi_addon">'. __( 'Settings', 'bos-api' ). '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

/**
 * Display admin notifications if dependency not found.
 */
function badgeos_rest_api_addon_ready() {
    
    if( ! is_admin() ) {
        return;
    }

    if ( ! class_exists( 'BadgeOS' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        deactivate_plugins ( plugin_basename ( __FILE__ ), true );
        $class = 'notice is-dismissible error';
        $message = __( 'BadgeOS REST API requires <a href="https://wordpress.org/plugins/badgeos/" >BadgeOS</a> to be activated.', 'bos-api' );
        printf ( '<div id="message" class="%s"> <p>%s</p></div>', $class, $message );
    }
}

/**
 * @return badgeos_rest_api|bool
 */
function badgeos_rest_api_addon_main() {
    
    if ( ! class_exists( 'BadgeOS' ) ) {
        add_action( 'admin_notices', 'badgeos_rest_api_addon_ready' );
        
        return false;
    }
    
    $GLOBALS['BadgeOS_REST_API_Addon'] = BadgeOS_REST_API_Addon::instance();

    return $GLOBALS['BadgeOS_REST_API_Addon'];
}

add_action( 'plugins_loaded', 'badgeos_rest_api_addon_main' );
