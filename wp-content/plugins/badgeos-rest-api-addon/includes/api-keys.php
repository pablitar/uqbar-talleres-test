<?php
/**
 * BadgeOS REST API Settings
 *
 * @author   BadgeOS
 * @category Admin
 * @package  BadgeOS_REST_API/SETTINGS
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BadgeOS_REST_API_Keys
 */
class BadgeOS_REST_API_Keys {

    /**
     * Hook in tabs.
     */
    public function __construct () {
        $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        if( ( isset( $badgeos_settings['badgeos_settings_rest_api_enable'] ) && $badgeos_settings['badgeos_settings_rest_api_enable']=='yes') && ( isset( $badgeos_settings['badgeos_settings_rest_enable_api_keys'] ) && $badgeos_settings['badgeos_settings_rest_enable_api_keys']=='yes') ) {
            add_action( 'init',  array( $this, 'badgeos_register_post_types' ) );
            add_action( 'cmb2_admin_init', array( $this, 'badgeos_api_metaboxes' ) );
        }
        
    }
    function badgeos_restapi_sanitize_apikey( $value, $field_args, $field ) {
    
        // Don't keep anything that's less than 100!
        if ( empty($value) ) {
            $sanitized_value = md5( time().rand(0,1000000) );
        } else {
            $sanitized_value = $value;
        }
    
        return $sanitized_value;
    }

    /**
     * Register custom meta boxes for BadgeOS api keys
     *
     * @since  1.0.0
     * @param  none
     * @return none
     */
    function badgeos_api_metaboxes() {

        // Start with an underscore to hide fields from custom fields list
        $prefix = '_badgeos_restapi_';

        // Setup our $post_id, if available
        $post_id = isset( $_GET['post'] ) ? $_GET['post'] : 0;

        $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();

        // New Achievement Types
        $cmb_obj = new_cmb2_box( array(
            'id'            => 'badgeos_api_keys_data',
            'title'         => esc_html__( 'API Key Data', 'badgeos' ),
            'object_types'  => array( 'badgeos-api-keys' ),
            'context'    => 'normal',
            'priority'   => 'high',
            'show_names' => true, // Show field names on the left
        ) );

        $cmb_obj->add_field(array(
            'name' => __( 'User(Type 3 chars)', 'badgeos' ),
            'desc' => __( 'API key owner.', 'badgeos' ),
            'id'   => $prefix . 'user',
            'type' => 'text_medium',
        ));
        
        $cmb_obj->add_field(array(
            'name'    => __( 'Permission:', 'badgeos' ),
            'desc'    => __( 'User permission to access the api via api key.', 'badgeos' ),
            'id'      => $prefix . 'permission',
            'type'    => 'select', 
            'options' => apply_filters( 'badgeos_restapi_access_levels', array(
                ''=> __( 'None', 'badgeos' ),
                'Read' => __( 'Read', 'badgeos' ),
                'Read_Write' => __( 'Read/Write', 'badgeos' )
            ))
        ));

        $cmb_obj->add_field(array(
            'name'    => __( 'Allowed Domain:', 'badgeos' ),
            'desc'    => __( 'API allowed domain.', 'badgeos' ),
            'id'      => $prefix . 'domain',
            'type'    => 'text',
        ));

        $cmb_obj->add_field(array(
            'name'    => __( 'API Key:', 'badgeos' ),
            'desc'    => __( 'API key used to access the data.', 'badgeos' ),
            'id'      => $prefix . 'apikey',
            'type'    => 'text',
            'sanitization_cb' => [$this, 'badgeos_restapi_sanitize_apikey'], // function should return a sanitized value
            'attributes' => array (
                'readonly' => 'readonly',
            ),
        ));
    }


    /**
     * Register all of our BadgeOS CPTs
     *
     * @since  1.0.0
     * @return void
     */
    function badgeos_register_post_types() {
        global $badgeos;

        // Register our API Keys CPT
        $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        register_post_type( 'badgeos-api-keys', array(
            'labels'             => array(
                'name'               => __( 'API Keys', 'badgeos' ),
                'singular_name'      => __( 'API Key', 'badgeos' ),
                'add_new'            => __( 'Add New', 'badgeos' ),
                'add_new_item'       => __( 'Add New API Key', 'badgeos' ),
                'edit_item'          => __( 'Edit API Key', 'badgeos' ),
                'new_item'           => __( 'New API Key', 'badgeos' ),
                'all_items'          => __( 'API Keys', 'badgeos' ),
                'view_item'          => __( 'View API Key', 'badgeos' ),
                'search_items'       => __( 'Search API Keys', 'badgeos' ),
                'not_found'          => __( 'No API Keys found', 'badgeos' ),
                'not_found_in_trash' => __( 'No API Keys found in Trash', 'badgeos' ),
                'parent_item_colon'  => '',
                'menu_name'          => __( 'API Keys', 'badgeos' )
            ),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => current_user_can( badgeos_get_manager_capability() ),
            'show_in_menu'       => 'badgeos_badgeos',
            'query_var'          => false,
            'rewrite'            => false,
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array( 'title', 'page-attributes' ),

        ) );
    }
}

new BadgeOS_REST_API_Keys();