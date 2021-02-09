<?php
/**
 * BadgeOS REST API for Points
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
class BadgeOS_REST_Points_API extends BadgeOS_REST_API_Main {

    /**
     * Hook in tabs.
     */
    public function __construct () {

        add_action( 'rest_api_init',  [ $this, 'badgeos_register_points_rest_api_end_points' ] );
    }

    /**
     * Returns the point_points types
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_point_types( $request ){
        
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    
                    // Grab all of our rank_types type posts
                    $point_types = get_posts( array(
                        'post_type'      =>	$badgeos_settings['points_main_post_type'],
                        'posts_per_page' =>	-1,
                    ) );
            
                    wp_send_json([ 'type' => 'success', 'data' => $point_types ], 200);
                } else {
                    wp_send_json(['type'=>'invalid_domain', 'message'=>__( "Your domain is not whitelisted.", 'bos-api' ) ], 403);
                }
            } else {
                wp_send_json(['type'=>'access_denied', 'message'=>__( "You don't have permission to perform this action.", 'bos-api' ) ], 403);
            }
        } else {
            wp_send_json(['type'=>'auth_failed', 'message'=>__( 'Authorization failed.', 'bos-api' ) ], 403);
        }
    }

    /**
     * Returns the point record
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_point_type_by_id( $request ){
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    
                    // Grab all of our achievement type posts
                    $point = get_post( sanitize_text_field( $request['type_id'] ) );
                    if( $point ) {
                        if( $point->post_type == $badgeos_settings['points_main_post_type'] ) {
                            wp_send_json( [ 'type' => 'success', 'data' => $point ], 200);
                        } else {
                            wp_send_json(['type' => 'error', 'message' => __( 'No point type found.', 'bos-api' ) ], 200);
                        }
                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'No point type found.', 'bos-api' ) ], 200);
                    }
                } else {
                    wp_send_json(['type'=>'invalid_domain', 'message'=>__( "Your domain is not whitelisted.", 'bos-api' ) ], 403);
                }
            } else {
                wp_send_json(['type'=>'access_denied', 'message'=>__( "You don't have permission to perform this action.", 'bos-api' ) ], 403);
            }
        } else {
            wp_send_json(['type'=>'auth_failed', 'message'=>__( 'Authorization failed.', 'bos-api' ) ], 403);
        }
    }

    /**
     * Returns the point type balance
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_point_type_balance( $request ){
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    
                    // Grab all of our achievement type posts
                    $point_type_id = sanitize_text_field( $request->get_param('type_id' ) );
                    $point_object = get_post( $point_type_id );
                    $user_id = sanitize_text_field( $request->get_param('user_id') );
                    if( $point_object ) {
                        
                        if( $point_object->post_type == $badgeos_settings['points_main_post_type'] ) {
                            
                            $point_type_title = badgeos_points_type_display_title( $point_type_id );
                            $earned_points = badgeos_get_points_by_type( $point_type_id, $user_id );

                            wp_send_json( [ 'type' => 'success', 'display_title' => $earned_points.' '.$point_type_title, 'points'=> $earned_points, 'point_type'=> $point_type_title ], 200);
                        } else {
                            wp_send_json(['type' => 'error', 'message' => __( 'No point type found.', 'bos-api' ) ], 200);
                        }
                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'No point type found.', 'bos-api' ) ], 200);
                    }
                } else {
                    wp_send_json(['type'=>'invalid_domain', 'message'=>__( "Your domain is not whitelisted.", 'bos-api' ) ], 403);
                }
            } else {
                wp_send_json(['type'=>'access_denied', 'message'=>__( "You don't have permission to perform this action.", 'bos-api' ) ], 403);
            }
        } else {
            wp_send_json(['type'=>'auth_failed', 'message'=>__( 'Authorization failed.', 'bos-api' ) ], 403);
        }
    }
    

    /**
     * Registers the rest api routes
     *
     * @param $categories
     * @param $post
     *
     * @return none
     */
    function badgeos_register_points_rest_api_end_points() {

        $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        $rest_api_endpoint_base      = isset( $settings['badgeos_settings_rest_api_endpoint'] ) && !empty( $settings['badgeos_settings_rest_api_endpoint'] ) ? sanitize_text_field( $settings['badgeos_settings_rest_api_endpoint'] ): 'badgeos-api';
        
        if( ( isset( $settings['badgeos_settings_rest_api_enable'] ) && $settings['badgeos_settings_rest_api_enable']=='yes') ) {
            
            register_rest_route( $rest_api_endpoint_base, '/get-point-types', array(
                'methods' => 'GET', 
                'callback' => [ $this, 'badgeos_get_point_types' ] ,
            ) ); 
            
            register_rest_route( $rest_api_endpoint_base, '/get-point-type-by-id/(?P<type_id>[a-zA-Z0-9_-]+)', array(
                'methods' => 'GET',
                'callback' => [ $this, 'badgeos_get_point_type_by_id' ] ,
            ) );
            
            register_rest_route( $rest_api_endpoint_base, '/get-point-balance/(?P<type_id>[a-zA-Z0-9_-]+)/(?P<user_id>[a-zA-Z0-9_-]+)', array(
                'methods' => 'GET',
                'callback' => [ $this, 'badgeos_get_point_type_balance' ] ,
            ) );

            register_rest_route( $rest_api_endpoint_base, '/award-point', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_award_point' ] , 
                'args'     => array(
                   'trigger_name' => array( 
                        'default' => '',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'user_id' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    )
                ),
            ) );

            register_rest_route( $rest_api_endpoint_base, '/deduct-point', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_deduct_point' ] , 
                'args'     => array(
                    'trigger_name' => array( 
                        'default' => '',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'user_id' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    )
                ),
            ) );

            register_rest_route( $rest_api_endpoint_base, '/award-point-steps-by-trigger/(?P<trigger_name>[a-zA-Z0-9_-]+)', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_award_steps_by_trigger' ]
            ) );

            register_rest_route( $rest_api_endpoint_base, '/deduct-point-steps-by-trigger/(?P<trigger_name>[a-zA-Z0-9_-]+)', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_deduct_steps_by_trigger' ]
            ) );
        }
    }

    /**
     * Deduct the point steps based on trigger name
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_deduct_point( $request ){
        
        // Setup all our globals
        global $blog_id, $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'], 'Write' ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our achievement type posts
                    $this_trigger   = sanitize_text_field( $request->get_param( 'trigger_name' ) );
                    $user_id        = sanitize_text_field( $request->get_param( 'user_id' ) );
                    if( intval( $user_id ) > 0 && ! empty( $this_trigger ) ) {

                        $site_id = $blog_id;

                        $args = [$user_id];

                        $user_data = get_user_by( 'id', $user_id );

                        /**
                         * Sanity check, if we don't have a user object, bail here
                         */
                        if ( ! is_object( $user_data ) ) {
                            wp_send_json(['type' => 'error', 'message' => __( 'No such user found.', 'bos-api' ) ], 200);
                        }
                        
                        /**
                         * If the user doesn't satisfy the trigger requirements, bail here\
                         */
                        if ( ! apply_filters( 'user_deserves_point_deduct_trigger', true, $user_id, $this_trigger, $site_id, $args ) ){
                            wp_send_json(['type' => 'error', 'message' => __( "User don't deserve this trigger.", 'bos-api' ) ], 200);
                        }

                        $triggered_deducts = $wpdb->get_results( $wpdb->prepare(
                            "SELECT p.ID as post_id 
                                FROM $wpdb->postmeta AS pm
                                INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_deduct_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = %s
                            ",
                            $this_trigger
                        ) );

                        if( !empty( $triggered_deducts ) ) {
                            foreach ( $triggered_deducts as $point ) { 
                                
                                $parent_point_id = badgeos_get_parent_id( $point->post_id );
                                $new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $user_id, $this_trigger, $site_id, 'Deduct', $args );
                                badgeos_maybe_deduct_points_to_user( $point->post_id, $parent_point_id , $user_id, $this_trigger, $site_id, $args );
                    
                            }
                        }	

                        wp_send_json( [ 'type' => 'success', 'message' => __( 'Trigger is executed successfully.', 'bos-api' ), 'total_steps_found'=> count( $triggered_deducts )], 200);

                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'user_id and trigger_name are mandatory fields.', 'bos-api' ) ], 200);
                    }
                } else {
                    wp_send_json(['type'=>'invalid_domain', 'message'=>__( "Your domain is not whitelisted.", 'bos-api' ) ], 403);
                }
            } else {
                wp_send_json(['type'=>'access_denied', 'message'=>__( "You don't have permission to perform this action.", 'bos-api' ) ], 403);
            }
        } else {
            wp_send_json(['type'=>'auth_failed', 'message'=>__( 'Authorization failed.', 'bos-api' ) ], 403);
        }
    }

    /**
     * Award the point steps based on trigger name
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_award_point( $request ){
        
        // Setup all our globals
        global $blog_id, $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'], 'Write' ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our achievement type posts
                    $this_trigger   = sanitize_text_field( $request->get_param( 'trigger_name' ) );
                    $user_id        = sanitize_text_field( $request->get_param( 'user_id' ) );
                    if( intval( $user_id ) > 0 && ! empty( $this_trigger ) ) {

                        $site_id = $blog_id;

                        $args = [$user_id];

                        $user_data = get_user_by( 'id', $user_id );

                        /**
                         * Sanity check, if we don't have a user object, bail here
                         */
                        if ( ! is_object( $user_data ) ) {
                            wp_send_json(['type' => 'error', 'message' => __( 'No such user found.', 'bos-api' ) ], 200);
                        }
                        
                        /**
                         * If the user doesn't satisfy the trigger requirements, bail here\
                         */
                        if ( ! apply_filters( 'user_deserves_point_award_trigger', true, $user_id, $this_trigger, $site_id, $args ) ){
                            wp_send_json(['type' => 'error', 'message' => __( "User don't deserve this trigger.", 'bos-api' ) ], 200);
                        }

                        $triggered_points = $wpdb->get_results( $wpdb->prepare(
                            "SELECT p.ID as post_id 
                                FROM $wpdb->postmeta AS pm
                                INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_point_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = %s
                            ",
                            $this_trigger
                        ) );

                        if( !empty( $triggered_points ) ) {
                            foreach ( $triggered_points as $point ) { 

                                $parent_point_id = badgeos_get_parent_id( $point->post_id );

                                /**
                                 * Update hook count for this user
                                 */
                                $new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $user_id, $this_trigger, $site_id, 'Award', $args );
                                
                                badgeos_maybe_award_points_to_user( $point->post_id, $parent_point_id , $user_id, $this_trigger, $site_id, $args );
                            }
                        }

                        wp_send_json( [ 'type' => 'success', 'message' => __( 'Trigger is executed successfully.', 'bos-api' ), 'total_steps_found'=> count( $triggered_points )], 200);

                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'user_id and trigger_name are mandatory fields.', 'bos-api' ) ], 200);
                    }
                } else {
                    wp_send_json(['type'=>'invalid_domain', 'message'=>__( "Your domain is not whitelisted.", 'bos-api' ) ], 403);
                }
            } else {
                wp_send_json(['type'=>'access_denied', 'message'=>__( "You don't have permission to perform this action.", 'bos-api' ) ], 403);
            }
        } else {
            wp_send_json(['type'=>'auth_failed', 'message'=>__( 'Authorization failed.', 'bos-api' ) ], 403);
        }
    }

    /**
     * Returns list of award steps by trigger
     * 
     * @param $trigger_name  Pass via Get/Post
     *
     * @return array
     */
    function badgeos_award_steps_by_trigger( $request ) {
        
        // Setup all our globals
        global $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
           
                    $trigger_name = sanitize_text_field( $request->get_param( 'trigger_name' ) ) ;
                    
                    // Now determine if any badges are earned based on this trigger event
                    $steps = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_point_trigger_type' AND pm.meta_value = %s", $trigger_name) );
                    $step_ids = [];
                    foreach( $steps as $step ) {
                        $step_ids[] = $step->post_id;
                    }
                    wp_send_json(['type' => 'success', 'data' => $step_ids ], 200);
                } else {
                    wp_send_json(['type'=>'invalid_domain', 'message'=>__( "Your domain is not whitelisted.", 'bos-api' ) ], 403);
                }
            } else {
                wp_send_json(['type'=>'access_denied', 'message'=>__( "You don't have permission to perform this action.", 'bos-api' ) ], 403);
            }
        } else {
            wp_send_json(['type'=>'auth_failed', 'message'=>__( 'Authorization failed.', 'bos-api' ) ], 403);
        }
    }

    /**
     * Returns list of deduct steps by trigger
     * 
     * @param $trigger_name  Pass via Get/Post
     *
     * @return array
     */
    function badgeos_deduct_steps_by_trigger( $request ) {
        
        // Setup all our globals
        global $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
           
                    $trigger_name = sanitize_text_field( $request->get_param( 'trigger_name' ) ) ;
                    
                    // Now determine if any badges are earned based on this trigger event
                    $steps = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_deduct_trigger_type' AND pm.meta_value = %s", $trigger_name) );
                    $step_ids = [];
                    foreach( $steps as $step ) {
                        $step_ids[] = $step->post_id;
                    }
                    wp_send_json(['type' => 'success', 'data' => $step_ids ], 200);
                } else {
                    wp_send_json(['type'=>'invalid_domain', 'message'=>__( "Your domain is not whitelisted.", 'bos-api' ) ], 403);
                }
            } else {
                wp_send_json(['type'=>'access_denied', 'message'=>__( "You don't have permission to perform this action.", 'bos-api' ) ], 403);
            }
        } else {
            wp_send_json(['type'=>'auth_failed', 'message'=>__( 'Authorization failed.', 'bos-api' ) ], 403);
        }
    }
}

new BadgeOS_REST_Points_API();