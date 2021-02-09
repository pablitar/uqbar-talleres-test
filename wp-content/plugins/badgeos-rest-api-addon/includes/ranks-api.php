<?php
/**
 * BadgeOS REST API for Ranks
 *
 * @author   BadgeOS
 * @category Admin
 * @package  BadgeOS_REST_API/Ranks
 * @version  1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class BadgeOS_REST_Ranks_API
 */
class BadgeOS_REST_Ranks_API extends BadgeOS_REST_API_Main {

    /**
     * Hook in tabs.
     */
    public function __construct () {

        add_action( 'rest_api_init',  [ $this, 'badgeos_ranks_register_api_end_points' ] );
        add_action( 'badgeos_after_award_rank', [ $this, 'badgeos_update_rank_apikey_field' ], 10, 8 );
    }

    /**
     * Returns the achievement types
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_update_rank_apikey_field( $user_id, $rank_id, $rank_type, $credit_id, $credit_amount, $admin_id, $trigger, $rank_entry_id=0 ){
        
        global $wpdb;
        $api_key = $GLOBALS['BadgeOS_REST_API_Addon']->_api_key;
        if( ! empty( $api_key ) ) {
            
            $table_name = $wpdb->prefix . 'badgeos_ranks';
            $data_array = array( 'api_key' => $api_key );
            $where = array( 'id' => absint(  $rank_entry_id ) );
            $wpdb->update( $table_name , $data_array, $where );
        }
    }

    /**
     * Returns the rank_types types
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_rank_types( $request ){
        
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    
                    // Grab all of our rank_types type posts
                    $rank_types = get_posts( array(
                        'post_type'      =>	$badgeos_settings['ranks_main_post_type'],
                        'posts_per_page' =>	-1,
                    ) );
            
                    wp_send_json([ 'type' => 'success', 'data' => $rank_types ], 200);
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
     * Returns the rank record
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_rank_type_by_id( $request ){
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our achievement type posts
                    $rank = get_post( sanitize_text_field( $request['type_id'] ) );
                    if( $rank ) {
                        if( $rank->post_type == $badgeos_settings['ranks_main_post_type'] ) {
                            wp_send_json( [ 'type' => 'success', 'data' => $rank ], 200);
                        } else {
                            wp_send_json(['type' => 'error', 'message' => __( 'No rank type found.', 'bos-api' ) ], 200);
                        }
                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'No rank type found.', 'bos-api' ) ], 200);
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
     * Returns the rank record
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_get_rank_by_id( $request ){
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our rank type posts
                    $rank = get_post( sanitize_text_field( $request['rank_id'] ) );
                    if( $rank ) {
                        $types = array_keys( badgeos_get_rank_types_slugs_detailed() );
                        if( in_array( $rank->post_type, $types ) ) {
                            wp_send_json( [ 'type' => 'success', 'data' => $rank ], 200);
                        } else {
                            wp_send_json(['type' => 'error', 'message' => __( 'No rank found.', 'bos-api' ) ], 200);
                        }
                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'No rank found.', 'bos-api' ) ], 200);
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
    function badgeos_get_all_ranks( $request ) {

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) { 
                    
                    $ranks_per_page = sanitize_text_field( $request->get_param( 'ranks_per_page' ) ) ;
                    $ranks_page = sanitize_text_field( $request->get_param( 'ranks_page' ) ) ;
                    $ranks_search = sanitize_text_field( $request->get_param( 'ranks_search' ) ) ;
                    $ranks_order_by = sanitize_text_field( $request->get_param( 'ranks_order_by' ) ) ;
                    $ranks_order = sanitize_text_field( $request->get_param( 'ranks_order' ) ) ;
                    $ranks_status = sanitize_text_field( $request->get_param( 'ranks_status' ) ) ;
                    $ranks_types = sanitize_text_field( $request->get_param( 'ranks_types' ) ) ;

                    // You can get the combined, merged set of parameters:
                    // $parameters = $request->get_params();
                
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    $types = array_keys( badgeos_get_rank_types_slugs_detailed() );
                    $step_key = array_search( trim( $badgeos_settings['ranks_step_post_type'] ), $types );
                    if ( $step_key )
                        unset( $types[$step_key] );
                    
                    if( !empty( $ranks_types ) ) {
                        $types = $ranks_types;
                    }
                    
                    $ranks = get_posts( array(
                        'post_type'      =>	$types,
                        'posts_per_page' =>	( $ranks_per_page > 0 ? $ranks_per_page : -1 ),
                        'paged' =>	( $ranks_page > 0 ? $ranks_page : 1 ),
                        's' => $ranks_search,
                        'orderby' => $ranks_order_by,
                        'order' => $ranks_order,
                        'post_status' => $ranks_status,
                    ) );

                    wp_send_json(['type' => 'success', 'data' => $ranks ], 200);
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
     * Returns the awarded ranks json array
     * 
     * @param $user_id  Pass via Get/Post
     * @param $rank_id  Pass via Get/Post
     * @param $site_id  Pass via Get/Post
     * @param $rank_type  Pass via Get/Post
     * @param $start_date  Pass via Get/Post
     * @param $end_date  Pass via Get/Post
     * @param $no_steps  Pass via Get/Post
     * @param $since  Pass via Get/Post
     * @param $pagination  Pass via Get/Post
     * @param $limit  Pass via Get/Post
     * @param $page  Pass via Get/Post
     *
     * @return array
     */
    function badgeos_awarded_ranks( $request ) {
        
        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {

                    $user_id = sanitize_text_field( $request->get_param( 'user_id' ) ) ;
                    $rank_id = sanitize_text_field( $request->get_param( 'rank_id' ) ) ;
                    $site_id = sanitize_text_field( $request->get_param( 'site_id' ) ) ;
                    $rank_type = sanitize_text_field( $request->get_param( 'rank_type' ) ) ;
                    $start_date = sanitize_text_field( $request->get_param( 'start_date' ) ) ;
                    $end_date = sanitize_text_field( $request->get_param( 'end_date' ) ) ;
                    $no_steps = sanitize_text_field( $request->get_param( 'no_steps' ) ) ;
                    $since = sanitize_text_field( $request->get_param( 'since' ) ) ;
                    $pagination = rest_sanitize_boolean( $request->get_param( 'pagination' ) ) ;
                    $limit = sanitize_text_field( $request->get_param( 'limit' ) ) ;
                    $page = sanitize_text_field( $request->get_param( 'page' ) ) ;
                    
                    $args = [
                        'user_id'           => $user_id,     // The given user's ID
                        'site_id'           => $site_id, // The given site's ID
                        'rank_id'           => $rank_id, // A specific achievement's post ID
                        'rank_type'         => $rank_type, // A specific achievement type
                        'start_date'        => $start_date, // A specific achievement type
                        'end_date'          => $end_date, // A specific achievement type
                        'no_steps'          => $no_steps,    // A specific achievement type
                        'since'             => $since,     // A specific timestamp to use in place of $limit_in_days
                        'pagination'        => $pagination, 
                        'limit'             => $limit, 
                        'page'              => $page,    
                    ];

                    $args['pagination'] = $pagination;
                    $args['limit'] = $limit;
                    $args['page'] = $page;
                    $ranks = badgeos_get_user_ranks( $args );
                    
                    $response = ['type' => 'success', 'data' => $ranks ];
                    if( $pagination == true ) {
                        
                        $args['total_only'] = true;
                        $response[ 'page' ] = $page;
                        $response[ 'limit' ] = $limit;
                        $response[ 'total' ] = badgeos_get_user_ranks( $args );
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
     * Returns list of steps by trigger
     * 
     * @param $trigger_name  Pass via Get/Post
     *
     * @return array
     */
    function badgeos_steps_by_trigger( $request ) {
        
        // Setup all our globals
        global $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'] ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
           
                    $trigger_name = sanitize_text_field( $request->get_param( 'trigger_name' ) ) ;
                    
                    // Now determine if any badges are earned based on this trigger event
                    $steps = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_rank_trigger_type' AND pm.meta_value = %s", $trigger_name) );
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
     * Returns the achievement record
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_revoke_rank( $request ){
        
        // Setup all our globals
        global $blog_id, $wpdb;

        if( $this->verify_api_key( $request['apikey'] ) ) { 
            if( $this->apikey_access( $request['apikey'], 'Write' ) ) { 
                if( $this->apikey_domain( $request['apikey'] ) ) {
            
                    $badgeos_settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
                    // Grab all of our rank type posts
                    $rank_id    = sanitize_text_field( $request->get_param( 'rank_id' ) );
                    $user_id    = sanitize_text_field( $request['user_id'] );

                    if( intval( $rank_id ) > 0 && intval( $user_id ) > 0 ) {

                        badgeos_revoke_rank_from_user_account( $user_id, $rank_id );
                
                        wp_send_json( [ 'type' => 'success', 'message' => __( 'Rank is removed from user profile.', 'bos-api' )], 200);

                    } else {
                        wp_send_json(['type' => 'error', 'message' => __( 'User Id and Rank Id are mandatory fields.', 'bos-api' ) ], 200);
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
     * Award the rank steps based on trigger name
     *
     * @param $request
     *
     * @return json
     */
    function badgeos_award_rank( $request ){
        
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

                        /**
                         * Grab the user ID
                         */
                        $user_data = get_user_by( 'id', $user_id );

                        /**
                         * Sanity check, if we don't have a user object, bail here
                         */
                        if ( ! is_object( $user_data ) ) {
                            wp_send_json(['type' => 'error', 'message' => __( 'No such user found.', 'bos-api' ) ], 200);
                        }
                        

                        /**
                         * If the user doesn't satisfy the trigger requirements, bail here
                         */
                        if ( ! apply_filters( 'badgeos_user_rank_deserves_trigger', true, $user_id, $this_trigger, $site_id, $args ) ) {
                            wp_send_json(['type' => 'error', 'message' => __( "User don't deserve this trigger.", 'bos-api' ) ], 200);
                        }
                            

                        /**
                         * Now determine if any Achievements are earned based on this trigger event
                         */
                        $triggered_ranks = $wpdb->get_results( $wpdb->prepare(
                                                "SELECT p.ID as post_id 
                                                FROM $wpdb->postmeta AS pm
                                                INNER JOIN $wpdb->posts AS p ON ( p.ID = pm.post_id AND pm.meta_key = '_rank_trigger_type' ) where p.post_status = 'publish' AND pm.meta_value = %s
                                                ",
                                                $this_trigger
                                            ) );
                        
                        if( !empty( $triggered_ranks ) ) {
                            foreach ( $triggered_ranks as $rank ) { 
                                $parent_id = badgeos_get_parent_id( $rank->post_id );
                                if( absint($parent_id) > 0) { 
                                    $new_count = badgeos_ranks_update_user_trigger_count( $rank->post_id, $parent_id,$user_id, $this_trigger, $site_id, $args );
                                    badgeos_maybe_award_rank( $rank->post_id,$parent_id,$user_id, $this_trigger, $site_id, $args );
                                } 
                            }
                        }

                        wp_send_json( [ 'type' => 'success', 'message' => __( 'Trigger is executed successfully.', 'bos-api' ), 'total_steps_found'=> count( $triggered_ranks )], 200);

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
     * Registers the rest api routes
     *
     * @param $categories
     * @param $post
     *
     * @return none
     */
    function badgeos_ranks_register_api_end_points() {

        $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
        $rest_api_endpoint_base      = isset( $settings['badgeos_settings_rest_api_endpoint'] ) && !empty( $settings['badgeos_settings_rest_api_endpoint'] ) ? sanitize_text_field( $settings['badgeos_settings_rest_api_endpoint'] ): 'badgeos-api';
        
        if( ( isset( $settings['badgeos_settings_rest_api_enable'] ) && $settings['badgeos_settings_rest_api_enable']=='yes') ) {
            
            register_rest_route( $rest_api_endpoint_base, '/get-rank-types', array(
                'methods' => 'GET', 
                'callback' => [ $this, 'badgeos_get_rank_types' ] ,
            ) ); 
            
            register_rest_route( $rest_api_endpoint_base, '/get-rank-type-by-id/(?P<type_id>[a-zA-Z0-9_-]+)', array(
                'methods' => 'GET',
                'callback' => [ $this, 'badgeos_get_rank_type_by_id' ] ,
            ) );

            register_rest_route( $rest_api_endpoint_base, '/get-rank-by-id/(?P<rank_id>[a-zA-Z0-9_-]+)', array(
                'methods' => 'GET',
                'callback' => [ $this, 'badgeos_get_rank_by_id' ] ,
            ) );
            register_rest_route( $rest_api_endpoint_base, '/get-all-ranks', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_get_all_ranks' ] , 
                'args'     => array(
                    'ranks_per_page' => array( 
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    ),
                    'ranks_page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'ranks_search' => array(
                        'default' => false,
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'ranks_order_by' => array(
                        'default' => false,
                        'type' => 'string',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'ranks_order' => array(
                        'default' => 'ASC',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'ranks_status' => array(
                        'default' => 'publish',
                        'sanitize_callback' => 'sanitize_title',
                    ),
                    'ranks_types' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_title',
                    )
                ),
            ) );

            register_rest_route( $rest_api_endpoint_base, '/awarded-ranks', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_awarded_ranks' ] , 
                'args'     => array(
                    'user_id' => array( 
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'rank_id' => array(
                        'default' => 0,
                        'required' => true,
                        'sanitize_callback' => 'absint',
                    ),
                    'site_id' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'rank_type' => array(
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
                    'no_steps' => array(
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
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'limit' => array(
                        'default' => 10,
                        'sanitize_callback' => 'absint',
                    )
                ),
            ) );
            
            register_rest_route( $rest_api_endpoint_base, '/award-rank', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_award_rank' ] , 
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

            register_rest_route( $rest_api_endpoint_base, '/revoke-rank', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_revoke_rank' ] , 
                'args'     => array(
                    'rank_id' => array( 
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    ),
                    'user_id' => array(
                        'default' => 0,
                        'sanitize_callback' => 'absint',
                    )
                ),
            ) );

            register_rest_route( $rest_api_endpoint_base, '/rank-steps-by-trigger/(?P<trigger_name>[a-zA-Z0-9_-]+)', array(
                'methods'  => 'GET, POST',
                'callback' => [ $this, 'badgeos_steps_by_trigger' ]
            ) );
        }
    }
}

new BadgeOS_REST_Ranks_API();