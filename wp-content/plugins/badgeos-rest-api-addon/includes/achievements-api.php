<?php
/**
 * BadgeOS REST API for Achievements
 *
 * @author   BadgeOS
 * @category Admin
 * @package  BadgeOS_REST_API/Achievements
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BadgeOS_REST_Achievements_API
 */
class BadgeOS_REST_Achievements_API extends BadgeOS_REST_API_Main {

    /**
     * Hook in tabs.
     */
    public function __construct () {

        add_action( 'rest_api_init', [ $this, 'badgeos_register_achivements_api_end_points' ] );

        add_action( 'badgeos_achievements_new_added', [ $this, 'badgeos_update_achivement_apikey_field' ], 10, 4 );
    }
    
    /**
     * Returns the achievement types
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_update_achivement_apikey_field( $rec_type='', $achievement_id, $user_id, $entry_id ){
        
        global $wpdb;
        $api_key = $GLOBALS['BadgeOS_REST_API_Addon']->_api_key;
        if( ! empty( $api_key ) ) {
            
            $table_name = $wpdb->prefix . 'badgeos_achievements';
            $data_array = array( 'api_key' => $api_key );
            $where = array( 'entry_id' => absint(  $entry_id ) );
            $wpdb->update( $table_name , $data_array, $where );
        }
    }

    /**
     * Returns the achievement types
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_achievements_types( $request ){
        
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our achievement type posts
                    $achievement_types = get_posts( array(
                        'post_type'      =>	$badgeos_settings['achievement_main_post_type'],
                        'posts_per_page' =>	-1,
                    ) );
            
                    wp_send_json([ 'type' => 'success', 'data' => $achievement_types ], 200);
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
     * Returns the achievement record
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_achievement_type_by_id( $request ){
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our achievement type posts
                    $achievement = get_post( sanitize_text_field( $request['type_id'] ) );
                    if( $achievement ) {
                        if( $achievement->post_type == $badgeos_settings['achievement_main_post_type'] ) {
                            wp_send_json( [ 'type' => 'success', 'data' => $achievement ], 200);
                        } else {
                            wp_send_json(['type' => 'error', 'message' => __( 'No achievement type found.', 'bos-api' ) ], 200);
                        }
                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'No achievement type found.', 'bos-api' ) ], 200);
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
     * @param $trigger_name  Pass via Get/Post
     *
     * @return array
     */
    function badgeos_restapi_steps_by_trigger( $request ) {
        
        // Setup all our globals
        global $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
           
                    $trigger_name = sanitize_text_field( $request->get_param( 'trigger_name' ) ) ;
                    
                    // Now determine if any badges are earned based on this trigger event
                    $steps = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_badgeos_trigger_type' AND pm.meta_value = %s", $trigger_name) );
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
     * Registers the rest api routes
     * 
     * @param $user_id  Pass via Get/Post
     * @param $site_id  Pass via Get/Post
     * @param $achievement_id  Pass via Get/Post
     * @param $achievement_type  Pass via Get/Post
     * @param $start_date  Pass via Get/Post
     * @param $end_date  Pass via Get/Post
     * @param $no_step  Pass via Get/Post
     * @param $since  Pass via Get/Post
     * @param $pagination  Pass via Get/Post
     * @param $limit  Pass via Get/Post
     * @param $page  Pass via Get/Post
     *
     * @return array
     */
    function badgeos_awarded_achievements( $request ) {
        
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
           
                    $user_id = sanitize_text_field( $request->get_param( 'user_id' ) ) ;
                    $site_id = sanitize_text_field( $request->get_param( 'site_id' ) ) ;
                    $achievement_id = sanitize_text_field( $request->get_param( 'achievement_id' ) ) ;
                    $achievement_type = sanitize_text_field( $request->get_param( 'achievement_type' ) ) ;
                    $start_date = sanitize_text_field( $request->get_param( 'start_date' ) ) ;
                    $end_date = sanitize_text_field( $request->get_param( 'end_date' ) ) ;
                    $no_step = sanitize_text_field( $request->get_param( 'no_step' ) ) ;
                    $since = sanitize_text_field( $request->get_param( 'since' ) ) ;
                    $pagination = rest_sanitize_boolean( $request->get_param( 'pagination' ) ) ;
                    $limit = sanitize_text_field( $request->get_param( 'limit' ) ) ;
                    $page = sanitize_text_field( $request->get_param( 'page' ) ) ;
                    
                    $args = [
                        'user_id'           => $user_id,     // The given user's ID
                        'site_id'           => $site_id, // The given site's ID
                        'achievement_id'    => $achievement_id, // A specific achievement's post ID
                        'achievement_type'  => $achievement_type, // A specific achievement type
                        'start_date'        => $start_date, // A specific achievement type
                        'end_date'          => $end_date, // A specific achievement type
                        'no_step'           => $no_step, // A specific achievement type
                        'since'             => $since,     // A specific timestamp to use in place of $limit_in_days
                    ];

                    $args['pagination'] = $pagination;
                    $args['limit'] = $limit;
                    $args['page'] = $page;
                    $achievements = badgeos_get_user_achievements( $args );
                    $response = ['type' => 'success', 'data' => $achievements ];
                    if( $pagination == true ) {
                        
                        $args['total_only'] = true;
                        $response[ 'page' ] = $page;
                        $response[ 'limit' ] = $limit;
                        $response[ 'total' ] = badgeos_get_user_achievements( $args );
                    }
                    
                    wp_send_json($response, 200);
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
     * @param $achievements_per_page  Pass via Get/Post
     * @param $achievements_page  Pass via Get/Post
     * @param $achievements_search  Pass via Get/Post
     * @param $achievements_order_by  Pass via Get/Post
     * @param $achievements_order  Pass via Get/Post
     * @param $achievements_status  Pass via Get/Post
     * @param $achievements_types  Pass via Get/Post
     *
     * @return array
     */
    function badgeos_get_all_achievements( $request ) {

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
                    $achievements_per_page = sanitize_text_field( $request->get_param( 'achievements_per_page' ) ) ;
                    $achievements_page = sanitize_text_field( $request->get_param( 'achievements_page' ) ) ;
                    $achievements_search = sanitize_text_field( $request->get_param( 'achievements_search' ) ) ;
                    $achievements_order_by = sanitize_text_field( $request->get_param( 'achievements_order_by' ) ) ;
                    $achievements_order = sanitize_text_field( $request->get_param( 'achievements_order' ) ) ;
                    $achievements_status = sanitize_text_field( $request->get_param( 'achievements_status' ) ) ;
                    $achievements_types = sanitize_text_field( $request->get_param( 'achievements_types' ) ) ;

                    // You can get the combined, merged set of parameters:
                    // $parameters = $request->get_params();
                
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    $types = array_keys( badgeos_get_achievement_types() );
                    $step_key = array_search( trim( $badgeos_settings['achievement_step_post_type'] ), $types );
                    if ( $step_key )
                        unset( $types[$step_key] );
                    
                    if( !empty( $achievements_types ) ) {
                        $types = $achievements_types;
                    }
                    
                    $achievements = get_posts( array(
                        'post_type'      =>	$types,
                        'posts_per_page' =>	( $achievements_per_page > 0 ? $achievements_per_page : -1 ),
                        'paged' =>	( $achievements_page > 0 ? $achievements_page : 1 ),
                        's' => $achievements_search,
                        'orderby' => $achievements_order_by,
                        'order' => $achievements_order,
                        'post_status' => $achievements_status,
                    ) );

                    wp_send_json(['type' => 'success', 'data' => $achievements ], 200);
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
     * Returns the achievement record
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_revoke_achievement( $request ){
        
        // Setup all our globals
        global $blog_id, $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'], 'Write' ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our achievement type posts
                    $achievements_id    = sanitize_text_field( $request->get_param( 'achievements_id' ) );
                    $user_id            = sanitize_text_field( $request['user_id'] );
                    $entry_id           = sanitize_text_field( $request['entry_id'] );
                    if( intval( $achievements_id ) > 0 && intval( $user_id ) > 0 && intval( $entry_id ) > 0 ) {

                        global $wpdb;

                        $achievements = array($achievements_id);
                        $entries = array($entry_id);
                        $indexes = array(0);
                        
                        $my_achievements = badgeos_get_user_achievements( array( 'user_id' => $user_id ) );
                
                        $index = 0;
                        $new_achievements = array();
                        $delete_achievement = array();
                        foreach( $my_achievements as $my_achs ) {
                            if( $my_achs->post_type != trim( $badgeos_settings['achievement_step_post_type'] ) ) {
                                if( in_array( $index, $indexes ) && in_array( $my_achs->ID, $achievements ) ) {
                                    $delete_achievement[] = $my_achs->ID;
                                } else {
                                    $new_achievements[] = $my_achs;
                                }
                                $index += 1;
                            } else {
                                $new_achievements[] = $my_achs;
                            }
                        }
                
                        foreach( $delete_achievement as $del_ach_id ) {
                            $children = badgeos_get_achievements( array( 'children_of' => $del_ach_id) );
                            foreach( $children as $child ) {
                                foreach( $new_achievements as $index => $item ) {
                
                                    if( $child->ID == $item->ID ) {
                                        unset( $new_achievements[ $index ] );
                                        $new_achievements = array_values( $new_achievements );
                                        $table_name = $wpdb->prefix . "badgeos_achievements";
                                        if($wpdb->get_var("show tables like '$table_name'") == $table_name) {
                                            $where = " where user_id='".intval($user_id)."' and entry_id = '".intval($item->entry_id)."'";
                                            $wpdb->get_results('delete from '.$wpdb->prefix.'badgeos_achievements '.$where.' limit 1' );
                                        }
                                        badgeos_decrement_user_trigger_count( $user_id, $child->ID, $del_ach_id );
                                        break;
                                    }
                                }
                            }
                        }
                        $new_achievements = array_values( $new_achievements );
                
                        // Update user's earned achievements
                        badgeos_update_user_achievements( array( 'user_id' => $user_id, 'all_achievements' => $new_achievements ) );
                        
                        foreach( $entries as $key => $entry ) {
                            $where = array( 'user_id' => $user_id );
                            
                            if( $entry != 0 ) {
                                $where['entry_id'] = $entry;
                            }
                            do_action( 'badgeos_before_revoke_achievement', $user_id, intval( $achievements[$key] ), $entry );
                            
                            $table_name = $wpdb->prefix . "badgeos_achievements";
                            if($wpdb->get_var("show tables like '$table_name'") == $table_name) {
                                $wpdb->delete( $table_name, $where );
                            }
                            do_action( 'badgeos_after_revoke_achievement', $user_id, intval( $achievements[$key] ), $entry );
                        }
                
                        wp_send_json( [ 'type' => 'success', 'message' => __( 'Achivement is removed from user profile.', 'bos-api' )], 200);

                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'User Id, Achievement Id and entry Id are mandatory fields.', 'bos-api' ) ], 200);
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
     * Returns the achievement record
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_award_achievement( $request ){
        
        // Setup all our globals
        global $blog_id, $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'], 'Write' ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our achievement type posts
                    $this_trigger   = sanitize_text_field( $request->get_param( 'trigger_name' ) );
                    $user_id        = sanitize_text_field( $request['user_id'] );
                    if( intval( $user_id ) > 0 && ! empty( $this_trigger ) ) {
                        
                        $site_id = $blog_id;

                        $args = [$user_id];

                        $user_data = get_user_by( 'id', $user_id );

                        // Sanity check, if we don't have a user object, bail here
                        if ( ! is_object( $user_data ) ) {
                            wp_send_json(['type' => 'error', 'message' => __( 'No such user found.', 'bos-api' ) ], 200);
                        }

                        // If the user doesn't satisfy the trigger requirements, bail here
                        if ( ! apply_filters( 'badgeos_user_deserves_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
                            wp_send_json(['type' => 'error', 'message' => __( "User don't deserve this trigger.", 'bos-api' ) ], 200);
                        }

                        // Now determine if any badges are earned based on this trigger event
                        $triggered_achievements = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_badgeos_trigger_type' AND pm.meta_value = %s", $this_trigger) );

                        if( count( $triggered_achievements ) > 0 ) {
                            // Update hook count for this user
                            $new_count = badgeos_update_user_trigger_count( $user_id, $this_trigger, $site_id, $args );

                            // Mark the count in the log entry
                            badgeos_post_log_entry( null, $user_id, null, sprintf( __( '%1$s triggered %2$s (%3$dx) from API', 'badgeos' ), $user_data->user_login, $this_trigger, $new_count ) );
                        }

                        foreach ( $triggered_achievements as $achievement ) {
                            $parents = badgeos_get_achievements( array( 'parent_of' => $achievement->post_id ) );
                            if( count( $parents ) > 0 ) {
                                if( $parents[0]->post_status == 'publish' ) {
                                    badgeos_maybe_award_achievement_to_user( $achievement->post_id, $user_id, $this_trigger, $site_id, $args );
                                }
                            }
                        }

                        wp_send_json( [ 'type' => 'success', 'message' => __( 'Trigger is executed successfully.', 'bos-api' ), 'total_steps_found'=> count( $triggered_achievements )], 200);

                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'User Id and trigger are mandatory fields.', 'bos-api' ) ], 200);
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
     * Returns the achievement record
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_achievement_by_id( $request ){
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our achievement type posts
                    $achievement = get_post( sanitize_text_field( $request['achievement_id'] ) );
                    if( $achievement ) {
                        $types = array_keys( badgeos_get_achievement_types() );
                        if( in_array( $achievement->post_type, $types ) ) {
                            wp_send_json( [ 'type' => 'success', 'data' => $achievement ], 200);
                        } else {
                            wp_send_json(['type' => 'error', 'message' => __( 'No achievement found.', 'bos-api' ) ], 200);
                        }
                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'No achievement found.', 'bos-api' ) ], 200);
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
     * @return none
     */
    function badgeos_register_achivements_api_end_points() {
        
        $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        $rest_api_endpoint_base      = isset( $settings['badgeos_settings_rest_api_endpoint'] ) && !empty( $settings['badgeos_settings_rest_api_endpoint'] ) ? sanitize_text_field( $settings['badgeos_settings_rest_api_endpoint'] ): 'badgeos-api';
        
        if( ( isset( $settings['badgeos_settings_rest_api_enable'] ) && $settings['badgeos_settings_rest_api_enable']=='yes') ) {
            
            register_rest_route( $rest_api_endpoint_base, '/get-achievements-types', array(
                'methods' => 'GET', 
                'callback' => [ $this, 'badgeos_get_achievements_types' ] ,
            ) ); 
            
            register_rest_route( $rest_api_endpoint_base, '/get-achievement-type-by-id/(?P<type_id>[a-zA-Z0-9_-]+)', array(
                'methods' => 'GET',
                'callback' => [ $this, 'badgeos_get_achievement_type_by_id' ] ,
            ) );

            register_rest_route( $rest_api_endpoint_base, '/get-achievement-by-id/(?P<achievement_id>[a-zA-Z0-9_-]+)', array(
                'methods' => 'GET',
                'callback' => [ $this, 'badgeos_get_achievement_by_id' ] ,
            ) );
            register_rest_route( $rest_api_endpoint_base, '/get-all-achievements', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_get_all_achievements' ] , 
                'args'     => array(
                    'achievements_per_page' => array( 
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    ),
                    'achievements_page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'achievements_search' => array(
                        'default' => false,
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'achievements_order_by' => array(
                        'default' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'achievements_order' => array(
                        'default' => 'ASC',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'achievements_status' => array(
                        'default' => 'publish',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'achievements_types' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_title',
                    )
                ),
            ) );

            register_rest_route( $rest_api_endpoint_base, '/awarded-achievements', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_awarded_achievements' ] , 
                'args'     => array(
                    'achievement_id' => array( 
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'user_id' => array(
                        'default' => 1,
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'site_id' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'achievement_type' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'start_date' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'end_date' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'no_step' => array(
                        'default' => true,
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'since' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'pagination' => array(
                        'default' => false,
                        'type'    => 'boolean'
                    ),
                    'page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'limit' => array(
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    )
                ),
            ) );
            
            register_rest_route( $rest_api_endpoint_base, '/award-achievement', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_award_achievement' ] , 
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

            register_rest_route( $rest_api_endpoint_base, '/revoke-achievement', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_revoke_achievement' ] , 
                'args'     => array(
                    'achievements_id' => array( 
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'user_id' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'entry_id' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    )
                ),
            ) );

            register_rest_route( $rest_api_endpoint_base, '/steps-by-trigger/(?P<trigger_name>[a-zA-Z0-9_-]+)', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_restapi_steps_by_trigger' ]
            ) );
        }
    }
}

new BadgeOS_REST_Achievements_API();