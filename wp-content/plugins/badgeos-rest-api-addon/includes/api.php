<?php
/**
 * BadgeOS REST API
 *
 * @author   BadgeOS
 * @category Admin
 * @package  BadgeOS_REST_API/Points
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BadgeOS_REST_Points_API
 */
class BadgeOS_REST_API_Main {

    private $_api_key = '';

    private $_user_id = 0;

    /**
     * Hook in tabs.
     */
    public function __construct () {
        $this->_api_key = isset( $_REQUEST['apikey'] ) ? sanitize_text_field( $_REQUEST['apikey'] ): '';
    }

    /**
     * Hook in tabs.
     */
    public function get_apikey_owner( ) {

        // Grab our hidden achievements
        global $wpdb;
        $recs = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.post_author FROM {$wpdb->posts} AS p
                                    JOIN {$wpdb->postmeta} AS pm
                                    ON p.ID = pm.post_id
                                    WHERE p.post_status='publish' and pm.meta_key = '_badgeos_restapi_apikey'
                                    AND pm.meta_value = %s
                                    ",
            $this->_api_key));
        
        if( count( $recs ) > 0 ) {
            $this->_user_id = $recs[0]->post_author;
        } else {
            $this->_user_id = 0;
        }
        return $this->_user_id;
    }

    /**
     * Checks if apikey is enabled.
     * 
     * @param $apikey
     * 
     * @return $is_route_allowed;
     */
    public function verify_api_key( $apikey ) {

        // Grab our hidden achievements
        $this->_api_key = isset( $apikey ) ? sanitize_text_field( $apikey ): '';
        $is_route_allowed = true;
        $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        if ( isset( $settings['badgeos_settings_rest_enable_api_keys'] ) && $settings['badgeos_settings_rest_enable_api_keys']=='yes') {
            if( $this->get_apikey_owner( ) < 1 ) {
                $is_route_allowed = false;
            } else {
                $GLOBALS['BadgeOS_REST_API_Addon']->_api_key = $this->_api_key;
            }
        }

        return $is_route_allowed;
    }

    /**
     * Checks apikey access level.
     * 
     * @param $apikey
     * 
     * @return $access;
     */
    public function apikey_access( $apikey, $access = 'Read' ) { 
        
        // Grab our hidden achievements
        global $wpdb;
        $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        if ( isset( $settings['badgeos_settings_rest_enable_api_keys'] ) && $settings['badgeos_settings_rest_enable_api_keys']=='yes') {

            // Grab our hidden achievements
            $this->_api_key = isset( $apikey ) ? sanitize_text_field( $apikey ): '';
            
            $recs = $wpdb->get_results( $wpdb->prepare(
                "SELECT p.ID FROM {$wpdb->posts} AS p
                                        JOIN {$wpdb->postmeta} AS pm
                                        ON p.ID = pm.post_id
                                        WHERE p.post_status='publish' and pm.meta_key = '_badgeos_restapi_apikey'
                                        AND pm.meta_value = %s
                                        ",
                $this->_api_key));
            $permission = false;
            if( count( $recs ) > 0 ) {
                $apikey_id = $recs[0]->ID;
                
                $permission = get_post_meta( $apikey_id, '_badgeos_restapi_permission', true );
                if( $access == 'Read' ) {
                    if( in_array(  $permission, [ 'Read', 'Read_Write' ] )  ) {
                        $permission = true;
                    }
                } else if( $access == 'Write' ) {
                    if( trim( $permission ) == 'Read_Write' ) {
                        $permission = true;
                    }
                }
                
            }
        } else {
            $permission = true;
        }

        return $permission;
    }

    /**
     * Checks apikey access level.
     * 
     * @param $apikey
     * 
     * @return $access;
     */
    public function apikey_domain( $apikey ) {
        
        // Grab our hidden achievements
        global $wpdb;

        // Grab our hidden achievements
        $this->_api_key = isset( $apikey ) ? sanitize_text_field( $apikey ): '';
        
        $recs = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} AS p
                                    JOIN {$wpdb->postmeta} AS pm
                                    ON p.ID = pm.post_id
                                    WHERE p.post_status='publish' and pm.meta_key = '_badgeos_restapi_apikey'
                                    AND pm.meta_value = %s
                                    ",
            $this->_api_key));
        $domain = true;
        if( count( $recs ) > 0 ) {
            $apikey_id = $recs[0]->ID;
            $domain = get_post_meta( $apikey_id, '_badgeos_restapi_domain', true );
            if( !empty( $domain ) ) {
                
                $allowedDomains = explode( ',', $domain );
                if( isset( $_SERVER['HTTP_REFERER'] ) ) {
                    $referer = $_SERVER['HTTP_REFERER'];

                    $domain = parse_url( $referer ); //If yes, parse referrer
                    foreach( $allowedDomains as $allowed ) {
                        if( $allowed == $domain['host'] ) {
                            return true;
                        }
                    }
                }
                
                return false;
            }
        }

        return true;
    }
    
}