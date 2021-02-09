<?php 
/**
 * Custom Achievement Rules
 *
 * @package BadgeOS TutorLMS
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://badgeos.org/downloads/tutorlms-add-on/
 */

/**
 * Load up our tutor triggers so we can add actions to them
 */
function badgeos_tutor_load_triggers() {

    /**
     * Grab our Tutor triggers
     */
    $tutor_triggers = $GLOBALS[ 'badgeos_tutor' ]->triggers;

    if ( !empty( $tutor_triggers ) ) {
        foreach ( $tutor_triggers as $trigger => $trigger_label ) {

            if ( is_array( $trigger_label ) ) {
                $triggers = $trigger_label;

                foreach ( $triggers as $trigger_hook => $trigger_name ) {
                    add_action( $trigger_hook, 'badgeos_tutor_trigger_event', 0, 20 );
                    add_action( $trigger_hook, 'badgeos_tutor_trigger_award_points_event', 0, 20 );
                    add_action( $trigger_hook, 'badgeos_tutor_trigger_deduct_points_event', 0, 20 );
                    add_action( $trigger_hook, 'badgeos_tutor_trigger_ranks_event', 0, 20 );
                }
            } else {
                add_action( $trigger, 'badgeos_tutor_trigger_event', 0, 20 );
                add_action( $trigger, 'badgeos_tutor_trigger_award_points_event', 0, 20 );
                add_action( $trigger, 'badgeos_tutor_trigger_deduct_points_event', 0, 20 );
                add_action( $trigger, 'badgeos_tutor_trigger_ranks_event', 0, 20 );
            }
        }
    }
}
add_action( 'init', 'badgeos_tutor_load_triggers', 0 );


/**
 * Handle each of our TutorLMS triggers
 */
function badgeos_tutor_trigger_event() {

    /**
     * Setup all our important variables
     */
    global $blog_id, $wpdb;

    /**
     * Setup args
     */
    $args = func_get_args();
    
    /**
     * Grab the current trigger
     */
    $this_trigger = current_filter();

    /**
     * Object-specific triggers
     */
    $tutor_quiz_triggers = array(
        'badgeos_tutor_quiz_finished',
        'badgeos_tutor_quiz_finished_specific',
        'badgeos_tutor_quiz_finished_course_specific',
        'badgeos_tutor_quiz_finished_fail',
        'badgeos_tutor_quiz_finished_fail_specific',
        'badgeos_tutor_quiz_finished_fail_course_specific',
        'badgeos_tutor_quiz_finished_completed_specific',
    );

    $tutor_lesson_triggrs = array(
        'badgeos_tutor_lesson_completed_after',
        'badgeos_tutor_lesson_completed_after_specific',
        'badgeos_tutor_lesson_completed_after_course_specific',
    );

    $tutor_course_triggers = array(
        'badgeos_tutor_course_complete_after',
        'badgeos_tutor_course_complete_after_specific',
        'badgeos_tutor_course_complete_after_tag',
    );

    $tutor_subscribe_triggers = array(
        'badgeos_tutor_after_enroll',
        'badgeos_tutor_after_enroll_specific',
    );

    $userID = get_current_user_id();

    if( in_array( $this_trigger, $tutor_quiz_triggers ) ){
        $attempt_id = (int) $args[0];
        $attempt = tutor_utils()->get_attempt($attempt_id);
        $userID = $attempt->user_id;
    }
    
    if ( empty( $userID ) ) {
        return;
    }

    $user_data = get_user_by( 'id', $userID );

    if ( empty( $user_data ) ) {
        return;
    }

    /**
     * Now determine if any badges are earned based on this trigger event
     */
    $triggered_achievements = $wpdb->get_results( $wpdb->prepare( "SELECT pm.post_id FROM $wpdb->postmeta as pm inner join $wpdb->posts as p on( pm.post_id = p.ID ) WHERE p.post_status = 'publish' and pm.meta_key = '_badgeos_tutor_trigger' AND pm.meta_value = %s", $this_trigger) );


    if( count( $triggered_achievements ) > 0 ) {
        /**
         * Update hook count for this user
         */
        $new_count = badgeos_update_user_trigger_count( $userID, $this_trigger, $blog_id );

        /**
         * Mark the count in the log entry
         */
        badgeos_post_log_entry( null, $userID, null, sprintf( __( '%1$s triggered %2$s (%3$dx)', 'badgeos-tutorlms' ), $user_data->user_login, $this_trigger, $new_count ) );
    }
    foreach ( $triggered_achievements as $achievement ) {
        $parents = badgeos_get_achievements( array( 'parent_of' => $achievement->post_id ) );
        if( count( $parents ) > 0 ) {
            if( $parents[0]->post_status == 'publish' ) {
                badgeos_maybe_award_achievement_to_user( $achievement->post_id, $userID, $this_trigger, $blog_id, $args );
            }
        }
    }
}

/**
 * Handle community triggers for award points
 */
function badgeos_tutor_trigger_award_points_event() {

    /**
     * Setup all our globals
     */
    global $user_ID, $blog_id, $wpdb;

    $site_id = $blog_id;

    $args = func_get_args();

    /**
     * Grab our current trigger
     */
    $this_trigger = current_filter();

    /**
     * Object-specific triggers
     */
    $tutor_quiz_triggers = array(
        'badgeos_tutor_quiz_finished',
        'badgeos_tutor_quiz_finished_specific',
        'badgeos_tutor_quiz_finished_course_specific',
        'badgeos_tutor_quiz_finished_fail',
        'badgeos_tutor_quiz_finished_fail_specific',
        'badgeos_tutor_quiz_finished_fail_course_specific',
        'badgeos_tutor_quiz_finished_completed_specific',
    );

    $tutor_lesson_triggrs = array(
        'badgeos_tutor_lesson_completed_after',
        'badgeos_tutor_lesson_completed_after_specific',
        'badgeos_tutor_lesson_completed_after_course_specific',
    );

    $tutor_course_triggers = array(
        'badgeos_tutor_course_complete_after',
        'badgeos_tutor_course_complete_after_specific',
        'badgeos_tutor_course_complete_after_tag',
    );

    $tutor_subscribe_triggers = array(
        'badgeos_tutor_after_enroll',
        'badgeos_tutor_after_enroll_specific',
    );

    /**
     * Grab the user ID
     * 
     */
    $userID = get_current_user_id();
    
    if( in_array( $this_trigger, $tutor_quiz_triggers ) ){
        $attempt_id = (int) $args[0];
        $attempt = tutor_utils()->get_attempt($attempt_id);
        $userID = $attempt->user_id;
    }
    
    if ( empty( $userID ) ) {
        return;
    }

    $user_data = get_user_by( 'id', $userID );

    if ( empty( $user_data ) ) {
        return;
    }

    /**
     * If the user doesn't satisfy the trigger requirements, bail here\
     */
    if ( ! apply_filters( 'user_deserves_point_award_trigger', true, $userID, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    /**
     * Now determine if any badges are earned based on this trigger event
     */
    $triggered_points = $wpdb->get_results( $wpdb->prepare("
            SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON 
            ( p.ID = pm.post_id AND pm.meta_key = '_point_trigger_type' )INNER JOIN $wpdb->postmeta AS pmtrg 
            ON ( p.ID = pmtrg.post_id AND pmtrg.meta_key = '_badgeos_tutor_trigger' ) 
            where p.post_status = 'publish' AND pmtrg.meta_value =  %s 
            ",
        $this_trigger
    ) );

    if( !empty( $triggered_points ) ) {
        foreach ( $triggered_points as $point ) {

            $parent_point_id = badgeos_get_parent_id( $point->post_id );

            /**
             * Update hook count for this user
             */
            $new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $userID, $this_trigger, $site_id, 'Award', $args );

            badgeos_maybe_award_points_to_user( $point->post_id, $parent_point_id , $userID, $this_trigger, $site_id, $args );

        }
    }
}

/**
 * Handle community triggers for deduct points
 */
function badgeos_tutor_trigger_deduct_points_event( $args='' ) {

    /**
     * Setup all our globals
     */
    global $user_ID, $blog_id, $wpdb;

    $site_id = $blog_id;

    $args = func_get_args();

    /**
     * Grab our current trigger
     */
    $this_trigger = current_filter();

    /**
     * Object-specific triggers
     */
    $tutor_quiz_triggers = array(
        'badgeos_tutor_quiz_finished',
        'badgeos_tutor_quiz_finished_specific',
        'badgeos_tutor_quiz_finished_course_specific',
        'badgeos_tutor_quiz_finished_fail',
        'badgeos_tutor_quiz_finished_fail_specific',
        'badgeos_tutor_quiz_finished_fail_course_specific',
        'badgeos_tutor_quiz_finished_completed_specific',
    );

    $tutor_lesson_triggrs = array(
        'badgeos_tutor_lesson_completed_after',
        'badgeos_tutor_lesson_completed_after_specific',
        'badgeos_tutor_lesson_completed_after_course_specific',
    );

    $tutor_course_triggers = array(
        'badgeos_tutor_course_complete_after',
        'badgeos_tutor_course_complete_after_specific',
        'badgeos_tutor_course_complete_after_tag',
    );

    $tutor_subscribe_triggers = array(
        'badgeos_tutor_after_enroll',
        'badgeos_tutor_after_enroll_specific',
    );

    $userID = get_current_user_id();
    
    if( in_array( $this_trigger, $tutor_quiz_triggers ) ){
        $attempt_id = (int) $args[0];
        $attempt = tutor_utils()->get_attempt($attempt_id);
        $userID = $attempt->user_id;
    }
    
    if ( empty( $userID ) ) {
        return;
    }

    $user_data = get_user_by( 'id', $userID );

    if ( empty( $user_data ) ) {
        return;
    }

    /**
     * If the user doesn't satisfy the trigger requirements, bail here
     */
    if ( ! apply_filters( 'user_deserves_point_deduct_trigger', true, $userID, $this_trigger, $site_id, $args ) ) {
        return $args[ 0 ];
    }

    /**
     * Now determine if any Achievements are earned based on this trigger event
     */
    $triggered_deducts = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON 
        ( p.ID = pm.post_id AND pm.meta_key = '_deduct_trigger_type' )INNER JOIN $wpdb->postmeta AS pmtrg 
        ON ( p.ID = pmtrg.post_id AND pmtrg.meta_key = '_badgeos_tutor_trigger' ) 
        where p.post_status = 'publish' AND pmtrg.meta_value =  %s",
        $this_trigger
    ) );

    if( !empty( $triggered_deducts ) ) {
        foreach ( $triggered_deducts as $point ) {

            $parent_point_id = badgeos_get_parent_id( $point->post_id );

            /**
             * Update hook count for this user
             */
            $new_count = badgeos_points_update_user_trigger_count( $point->post_id, $parent_point_id, $userID, $this_trigger, $site_id, 'Deduct', $args );

            badgeos_maybe_deduct_points_to_user( $point->post_id, $parent_point_id , $userID, $this_trigger, $site_id, $args );

        }
    }
}

/**
 * Handle community triggers for ranks
 */
function badgeos_tutor_trigger_ranks_event( $args='' ) {

    /**
     * Setup all our globals
     */
    global $user_ID, $blog_id, $wpdb;

    $site_id = $blog_id;

    $args = func_get_args();

    /**
     * Grab our current trigger
     */
    $this_trigger = current_filter();


    /**
     * Object-specific triggers
     */
    $tutor_quiz_triggers = array(
        'badgeos_tutor_quiz_finished',
        'badgeos_tutor_quiz_finished_specific',
        'badgeos_tutor_quiz_finished_course_specific',
        'badgeos_tutor_quiz_finished_fail',
        'badgeos_tutor_quiz_finished_fail_specific',
        'badgeos_tutor_quiz_finished_fail_course_specific',
        'badgeos_tutor_quiz_finished_completed_specific',
    );

    $tutor_lesson_triggrs = array(
        'badgeos_tutor_lesson_completed_after',
        'badgeos_tutor_lesson_completed_after_specific',
        'badgeos_tutor_lesson_completed_after_course_specific',
    );

    $tutor_course_triggers = array(
        'badgeos_tutor_course_complete_after',
        'badgeos_tutor_course_complete_after_specific',
        'badgeos_tutor_course_complete_after_tag',
    );

    $tutor_subscribe_triggers = array(
        'badgeos_tutor_after_enroll',
        'badgeos_tutor_after_enroll_specific',
    );

    $userID = get_current_user_id();
    
    if( in_array( $this_trigger, $tutor_quiz_triggers ) ){
        $attempt_id = (int) $args[0];
        $attempt = tutor_utils()->get_attempt($attempt_id);
        $userID = $attempt->user_id;
    }
    
    if ( empty( $userID ) ) {
        return;
    }

    $user_data = get_user_by( 'id', $userID );

    if ( empty( $user_data ) ) {
        return;
    }

    /**
     * If the user doesn't satisfy the trigger requirements, bail here
     */
    if ( ! apply_filters( 'badgeos_user_rank_deserves_trigger', true, $userID, $this_trigger, $site_id, $args ) )
        return $args[ 0 ];

    /**
     * Now determine if any Achievements are earned based on this trigger event
     */
    $triggered_ranks = $wpdb->get_results( $wpdb->prepare(
        "SELECT p.ID as post_id FROM $wpdb->postmeta AS pm INNER JOIN $wpdb->posts AS p ON 
            ( p.ID = pm.post_id AND pm.meta_key = '_rank_trigger_type' )INNER JOIN $wpdb->postmeta AS pmtrg 
            ON ( p.ID = pmtrg.post_id AND pmtrg.meta_key = '_badgeos_tutor_trigger' ) 
            where p.post_status = 'publish' AND pmtrg.meta_value =  %s",
            $this_trigger
    ) );

    if( !empty( $triggered_ranks ) ) {
        foreach ( $triggered_ranks as $rank ) {
            $parent_id = badgeos_get_parent_id( $rank->post_id );
            if( absint($parent_id) > 0) {
                $new_count = badgeos_ranks_update_user_trigger_count( $rank->post_id, $parent_id,$userID, $this_trigger, $site_id, $args );
                badgeos_maybe_award_rank( $rank->post_id,$parent_id,$userID, $this_trigger, $site_id, $args );
            }
        }
    }
}

/**
 * Check if user deserves a tutor trigger step
 *
 * @param $return
 * @param $user_id
 * @param $achievement_id
 * @param string $this_trigger
 * @param int $site_id
 * @param array $args
 * @return bool
 */
function badgeos_tutor_user_deserves_tutor_step( $return, $user_id, $achievement_id, $this_trigger = '', $site_id = 1, $args = array() ) {

    /**
     * If we're not dealing with a step, bail here
     */
    if ( 'step' != get_post_type( $achievement_id ) ) {
        return $return;
    }

    /**
     * Grab our step requirements
     */
    $requirements = badgeos_get_step_requirements( $achievement_id );
    /**
     * If the step is triggered by tutor actions...
     */
    if ( 'tutor_trigger' == $requirements[ 'trigger_type' ] ) {

        /**
         * Do not pass go until we say you can
         */
        $return = false;

        /**
         * Unsupported trigger
         */
        if ( ! isset( $GLOBALS[ 'badgeos_tutor' ]->triggers[ $this_trigger ] ) ) {
            return $return;
        }

        /**
         * tutor requirements not met yet
         */
        $tutor_triggered = false;

        /**
         * Set our main vars
         */
        $tutor_trigger = $requirements['tutor_trigger'];
        $object_id = $requirements['tutor_object_id'];

        /**
         * Extra arg handling for further expansion
         */
        $object_arg1 = null;

        if ( isset( $requirements['tutor_object_arg1'] ) ){
                $object_arg1 = $requirements['tutor_object_arg1'];
            }

        /**
         * Object-specific triggers
         */
        
        /**
         * quiz
         * 
         */
        $tutor_quiz_triggers = array(
            'badgeos_tutor_quiz_finished',
            'badgeos_tutor_quiz_finished_specific',
            'badgeos_tutor_quiz_finished_course_specific',
            'badgeos_tutor_quiz_finished_fail',
            'badgeos_tutor_quiz_finished_fail_specific',
            'badgeos_tutor_quiz_finished_fail_course_specific',
            'badgeos_tutor_quiz_finished_completed_specific',
        );

        /**
         * lesson
         */
        $tutor_lesson_triggrs = array(
            'badgeos_tutor_lesson_completed_after',
            'badgeos_tutor_lesson_completed_after_specific',
            'badgeos_tutor_lesson_completed_after_course_specific',
        );

        /**
         * course
         */
        $tutor_course_triggrs = array(
            'badgeos_tutor_course_complete_after',
            'badgeos_tutor_course_complete_after_specific',
            'badgeos_tutor_course_complete_after_tag',
        );

        /**
         * subscribe
         */
        $tutor_subscribe_triggers = array(
            'badgeos_tutor_after_enroll',
            'badgeos_tutor_after_enroll_specific',
        );

        /**
         * Triggered object ID (used in these hooks, generally 2nd arg)
         */
        $triggered_object_id = 0;
        
        /**
         * Grab user id
         * 
         */
        if( empty( $user_id ) ){
            $user_id = get_current_user_id();
        }

        /**
         * Use basic trigger logic if no object set
         */
        
        if( in_array( $tutor_trigger, $tutor_quiz_triggers ) ){

            $attempt_id = (int) $args[0];
            $attempt = tutor_utils()->get_attempt($attempt_id);
            $quiz_id = $attempt->quiz_id;
            $course_id = $attempt->course_id;
            $quiz_earned_marks = $attempt->earned_marks;
            $quiz_total_marks = $attempt->total_marks;
            $earned_percentage = $attempt->earned_marks > 0 ? ( number_format(($attempt->earned_marks * 100) / $attempt->total_marks)) : 0;
            $user_id = $attempt->user_id;
            $passing_grade = (int) tutor_utils()->get_quiz_option($attempt->quiz_id, 'passing_grade', 0);
           
           if( $tutor_trigger == 'badgeos_tutor_quiz_finished' ){
                if( $earned_percentage >= $passing_grade ){
                    $tutor_triggered = true;
                }else{
                    $tutor_triggered = false;
                }                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_course_specific'){
                $triggered_object_id = $course_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail' ){
                if( $earned_percentage < $passing_grade ){
                    $tutor_triggered = true;
                }else{
                    $tutor_triggered = false;
                }                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage < $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail_course_specific'){
                $triggered_object_id = $course_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage < $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_completed_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $object_arg1 ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } 
        } elseif( in_array( $tutor_trigger, $tutor_lesson_triggrs ) ){
            $lesson_id = (int) $args[0];
            $course_id = tutor_utils()->get_course_id_by_lesson( $lesson_id );
            
            if( $tutor_trigger == 'badgeos_tutor_lesson_completed_after' ){
                $tutor_triggered = true;                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_lesson_completed_after_specific' ){
                $triggered_object_id = $lesson_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_lesson_completed_after_course_specific'){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                    $tutor_triggered=true;
                } else{
                    $tutor_triggered=false;
                }
            }
        } elseif( in_array( $tutor_trigger, $tutor_course_triggrs ) ){
            $course_id = (int) $args[0];
            
            if( $tutor_trigger == 'badgeos_tutor_course_complete_after' ){
                $tutor_triggered = true;         
                
            } elseif( $tutor_trigger == 'badgeos_tutor_course_complete_after_specific' ){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_course_complete_after_tag'){
                $triggered_object_id = $course_id;
                if( has_term( $object_id, 'course-tag', $triggered_object_id ) ){
                    $tutor_triggered=true;
                } else{
                    $tutor_triggered=false;
                }
            }
        } elseif( in_array( $tutor_trigger, $tutor_subscribe_triggers ) ){
            $course_id = (int) $args[0];
            if( $tutor_trigger == 'badgeos_tutor_after_enroll' ){
                $tutor_triggered = true;
            } elseif( $tutor_trigger == 'badgeos_tutor_after_enroll_specific' ){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                    }
            }
        }

        /**
         * TutorLMS requirements met
         */
        if ( $tutor_triggered ) {

            $parent_achievement = badgeos_get_parent_of_achievement( $achievement_id );
            $parent_id = $parent_achievement->ID;

            $user_crossed_max_allowed_earnings = badgeos_achievement_user_exceeded_max_earnings( $user_id, $parent_id );
            if ( ! $user_crossed_max_allowed_earnings ) {
                $minimum_activity_count = absint( get_post_meta( $achievement_id, '_badgeos_count', true ) );
                if( ! isset( $minimum_activity_count ) || empty( $minimum_activity_count ) )
                    $minimum_activity_count = 1;

                $count_step_trigger = $requirements["tutor_trigger"];
                $activities = badgeos_get_user_trigger_count( $user_id, $count_step_trigger );
                $relevant_count = absint( $activities );

                $achievements = badgeos_get_user_achievements(
                    array(
                        'user_id' => absint( $user_id ),
                        'achievement_id' => $achievement_id
                    )
                );

                $total_achievments = count( $achievements );
                $used_points = intval( $minimum_activity_count ) * intval( $total_achievments );
                $remainder = intval( $relevant_count ) - $used_points;

                $return  = 0;
                if ( absint( $remainder ) >= $minimum_activity_count )
                    $return  = $remainder;

                return $return;
            } else {

                return 0;
            }
        }
    }

    return $return;
}
add_filter( 'user_deserves_achievement', 'badgeos_tutor_user_deserves_tutor_step', 15, 6 );

function badgeos_tutor_user_deserves_credit_deduct( $return, $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) {

    // Grab our step requirements
    $requirements      = badgeos_get_deduct_step_requirements( $credit_step_id );

    // If we're not dealing with a step, bail here
    $settings = get_option( 'badgeos_settings' );
    if ( trim( $settings['points_deduct_post_type'] ) != get_post_type( $credit_step_id ) ) {
        return $return;
    }

    // If the step is triggered by tutor actions...
    if ( 'tutor_trigger' == $requirements[ 'trigger_type' ] ) {
        // Do not pass go until we say you can
        $return = false;

        // Unsupported trigger
        if ( !isset( $GLOBALS[ 'badgeos_tutor' ]->triggers[ $this_trigger ] ) ) {
            return $return;
        }

        /**
         * tutor requirements not met yet
         */
        $tutor_triggered = false;

        /**
         * Set our main vars
         */
        $tutor_trigger = $requirements['tutor_trigger'];
        $object_id = $requirements['tutor_object_id'];

        /**
         * Extra arg handling for further expansion
         */
        $object_arg1 = null;

        if ( isset( $requirements['tutor_object_arg1'] ) ){
            $object_arg1 = $requirements['tutor_object_arg1'];
        }

        /**
         * Object-specific triggers
         */
        
        /**
         * quiz
         * 
         */
        $tutor_quiz_triggers = array(
            'badgeos_tutor_quiz_finished',
            'badgeos_tutor_quiz_finished_specific',
            'badgeos_tutor_quiz_finished_course_specific',
            'badgeos_tutor_quiz_finished_fail',
            'badgeos_tutor_quiz_finished_fail_specific',
            'badgeos_tutor_quiz_finished_fail_course_specific',
            'badgeos_tutor_quiz_finished_completed_specific',
        );

        /**
         * lesson
         */
        $tutor_lesson_triggrs = array(
            'badgeos_tutor_lesson_completed_after',
            'badgeos_tutor_lesson_completed_after_specific',
            'badgeos_tutor_lesson_completed_after_course_specific',
        );

        /**
         * course
         */
        $tutor_course_triggrs = array(
            'badgeos_tutor_course_complete_after',
            'badgeos_tutor_course_complete_after_specific',
            'badgeos_tutor_course_complete_after_tag',
        );

        /**
         * subscribe
         */
        $tutor_subscribe_triggers = array(
            'badgeos_tutor_after_enroll',
            'badgeos_tutor_after_enroll_specific',
        );

        /**
         * Triggered object ID (used in these hooks, generally 2nd arg)
         */
        $triggered_object_id = 0;
        
        /**
         * Grab user id
         * 
         */
        if( empty( $user_id ) ){
            $user_id = get_current_user_id();
        }

        /**
         * Use basic trigger logic if no object set
         */
        
        if( in_array( $tutor_trigger, $tutor_quiz_triggers ) ){

            $attempt_id = (int) $args[0];
            $attempt = tutor_utils()->get_attempt($attempt_id);
            $quiz_id = $attempt->quiz_id;
            $course_id = $attempt->course_id;
            $quiz_earned_marks = $attempt->earned_marks;
            $quiz_total_marks = $attempt->total_marks;
            $earned_percentage = $attempt->earned_marks > 0 ? ( number_format(($attempt->earned_marks * 100) / $attempt->total_marks)) : 0;
            $user_id = $attempt->user_id;
            $passing_grade = (int) tutor_utils()->get_quiz_option($attempt->quiz_id, 'passing_grade', 0);
           
           if( $tutor_trigger == 'badgeos_tutor_quiz_finished' ){
                if( $earned_percentage >= $passing_grade ){
                    $tutor_triggered = true;
                }else{
                    $tutor_triggered = false;
                }                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_course_specific'){
                $triggered_object_id = $course_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail' ){
                if( $earned_percentage < $passing_grade ){
                    $tutor_triggered = true;
                }else{
                    $tutor_triggered = false;
                }                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage < $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail_course_specific'){
                $triggered_object_id = $course_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage < $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_completed_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $object_arg1 ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } 
        } elseif( in_array( $tutor_trigger, $tutor_lesson_triggrs ) ){
            $lesson_id = (int) $args[0];
            $course_id = tutor_utils()->get_course_id_by_lesson( $lesson_id );
            
            if( $tutor_trigger == 'badgeos_tutor_lesson_completed_after' ){
                $tutor_triggered = true;                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_lesson_completed_after_specific' ){
                $triggered_object_id = $lesson_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_lesson_completed_after_course_specific'){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                    $tutor_triggered=true;
                } else{
                    $tutor_triggered=false;
                }
            }
        } elseif( in_array( $tutor_trigger, $tutor_course_triggrs ) ){
            $course_id = (int) $args[0];
            
            if( $tutor_trigger == 'badgeos_tutor_course_complete_after' ){
                $tutor_triggered = true;         
                
            } elseif( $tutor_trigger == 'badgeos_tutor_course_complete_after_specific' ){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_course_complete_after_tag'){
                $triggered_object_id = $course_id;
                if( has_term( $object_id, 'course-tag', $triggered_object_id ) ){
                    $tutor_triggered=true;
                } else{
                    $tutor_triggered=false;
                }
            }
        } elseif( in_array( $tutor_trigger, $tutor_subscribe_triggers ) ){
            $course_id = (int) $args[0];
            if( $tutor_trigger == 'badgeos_tutor_after_enroll' ){
                $tutor_triggered = true;
            } elseif( $tutor_trigger == 'badgeos_tutor_after_enroll_specific' ){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                    }
            }
        }

        // TutorLMS requirements met
        if ( $tutor_triggered ) {
            // Grab the trigger count
            $trigger_count = points_get_user_trigger_count( $credit_step_id, $user_id, $this_trigger, $site_id, 'Deduct', $args );

            // If we meet or exceed the required number of checkins, they deserve the step
            if ( 1 == $requirements[ 'count' ] || $requirements[ 'count' ] <= $trigger_count ) {
                // OK, you can pass go now
                $return = true;
            }
        }
    }
    return $return;
}
add_filter( 'badgeos_user_deserves_credit_deduct', 'badgeos_tutor_user_deserves_credit_deduct', 15, 7 );

function badgeos_tutor_user_deserves_credit_award( $return, $credit_step_id, $credit_parent_id, $user_id, $this_trigger, $site_id, $args ) {

    // Grab our step requirements
    $requirements      = badgeos_get_award_step_requirements( $credit_step_id );

    // If we're not dealing with a step, bail here
    $settings = get_option( 'badgeos_settings' );
    if ( trim( $settings['points_award_post_type'] ) != get_post_type( $credit_step_id ) ) {
        return $return;
    }
    // If the step is triggered by tutor actions...
    if ( 'tutor_trigger' == $requirements[ 'trigger_type' ] ) {
        // Do not pass go until we say you can
        $return = false;

        // Unsupported trigger
        if ( !isset( $GLOBALS[ 'badgeos_tutor' ]->triggers[ $this_trigger ] ) ) {
            return $return;
        }

        /**
         * tutor requirements not met yet
         */
        $tutor_triggered = false;

        /**
         * Set our main vars
         */
        $tutor_trigger = $requirements['tutor_trigger'];
        $object_id = $requirements['tutor_object_id'];

        /**
         * Extra arg handling for further expansion
         */
        $object_arg1 = null;

        if ( isset( $requirements['tutor_object_arg1'] ) )
        {
            $object_arg1 = $requirements['tutor_object_arg1'];
        }

        /**
         * Object-specific triggers
         */
        
        /**
         * quiz
         * 
         */
        $tutor_quiz_triggers = array(
            'badgeos_tutor_quiz_finished',
            'badgeos_tutor_quiz_finished_specific',
            'badgeos_tutor_quiz_finished_course_specific',
            'badgeos_tutor_quiz_finished_fail',
            'badgeos_tutor_quiz_finished_fail_specific',
            'badgeos_tutor_quiz_finished_fail_course_specific',
            'badgeos_tutor_quiz_finished_completed_specific',
        );

        /**
         * lesson
         */
        $tutor_lesson_triggrs = array(
            'badgeos_tutor_lesson_completed_after',
            'badgeos_tutor_lesson_completed_after_specific',
            'badgeos_tutor_lesson_completed_after_course_specific',
        );

        /**
         * course
         */
        $tutor_course_triggrs = array(
            'badgeos_tutor_course_complete_after',
            'badgeos_tutor_course_complete_after_specific',
            'badgeos_tutor_course_complete_after_tag',
        );

        /**
         * subscribe
         */
        $tutor_subscribe_triggers = array(
            'badgeos_tutor_after_enroll',
            'badgeos_tutor_after_enroll_specific',
        );

        /**
         * Triggered object ID (used in these hooks, generally 2nd arg)
         */
        $triggered_object_id = 0;
        
        /**
         * Grab user id
         * 
         */
        if( empty( $user_id ) ){
            $user_id = get_current_user_id();
        }

        /**
         * Use basic trigger logic if no object set
         */
        
        if( in_array( $tutor_trigger, $tutor_quiz_triggers ) ){

            $attempt_id = (int) $args[0];
            $attempt = tutor_utils()->get_attempt($attempt_id);
            $quiz_id = $attempt->quiz_id;
            $course_id = $attempt->course_id;
            $quiz_earned_marks = $attempt->earned_marks;
            $quiz_total_marks = $attempt->total_marks;
            $earned_percentage = $attempt->earned_marks > 0 ? ( number_format(($attempt->earned_marks * 100) / $attempt->total_marks)) : 0;
            $user_id = $attempt->user_id;
            $passing_grade = (int) tutor_utils()->get_quiz_option($attempt->quiz_id, 'passing_grade', 0);
           
           if( $tutor_trigger == 'badgeos_tutor_quiz_finished' ){
                if( $earned_percentage >= $passing_grade ){
                    $tutor_triggered = true;
                }else{
                    $tutor_triggered = false;
                }                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_course_specific'){
                $triggered_object_id = $course_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail' ){
                if( $earned_percentage < $passing_grade ){
                    $tutor_triggered = true;
                }else{
                    $tutor_triggered = false;
                }                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage < $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail_course_specific'){
                $triggered_object_id = $course_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage < $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_completed_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $object_arg1 ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } 
        } elseif( in_array( $tutor_trigger, $tutor_lesson_triggrs ) ){
            $lesson_id = (int) $args[0];
            $course_id = tutor_utils()->get_course_id_by_lesson( $lesson_id );
            
            if( $tutor_trigger == 'badgeos_tutor_lesson_completed_after' ){
                $tutor_triggered = true;                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_lesson_completed_after_specific' ){
                $triggered_object_id = $lesson_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_lesson_completed_after_course_specific'){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                    $tutor_triggered=true;
                } else{
                    $tutor_triggered=false;
                }
            }
        } elseif( in_array( $tutor_trigger, $tutor_course_triggrs ) ){
            $course_id = (int) $args[0];
            
            if( $tutor_trigger == 'badgeos_tutor_course_complete_after' ){
                $tutor_triggered = true;         
                
            } elseif( $tutor_trigger == 'badgeos_tutor_course_complete_after_specific' ){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_course_complete_after_tag'){
                $triggered_object_id = $course_id;
                if( has_term( $object_id, 'course-tag', $triggered_object_id ) ){
                    $tutor_triggered=true;
                } else{
                    $tutor_triggered=false;
                }
            }
        } elseif( in_array( $tutor_trigger, $tutor_subscribe_triggers ) ){
            $course_id = (int) $args[0];
            if( $tutor_trigger == 'badgeos_tutor_after_enroll' ){
                $tutor_triggered = true;
            } elseif( $tutor_trigger == 'badgeos_tutor_after_enroll_specific' ){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                    }
            }
        }

        // TutorLMS requirements met
        if ( $tutor_triggered ) {
            // Grab the trigger count
            $trigger_count = points_get_user_trigger_count( $credit_step_id, $user_id, $this_trigger, $site_id, 'Award', $args );

            // If we meet or exceed the required number of checkins, they deserve the step
            if ( 1 == $requirements[ 'count' ] || $requirements[ 'count' ] <= $trigger_count ) {
                // OK, you can pass go now
                $return = true;
            }
        }
    }

    return $return;
}
add_filter( 'badgeos_user_deserves_credit_award', 'badgeos_tutor_user_deserves_credit_award', 15, 7 );

function badgeos_tutor_user_deserves_rank_step( $return, $step_id, $rank_id, $user_id, $this_trigger, $site_id, $args ) {
    // Grab our step requirements
    $requirements      = badgeos_get_rank_req_step_requirements( $step_id );

    // If we're not dealing with a step, bail here
    $settings = get_option( 'badgeos_settings' );
    if ( trim( $settings['ranks_step_post_type'] ) != get_post_type( $step_id ) ) {
        return $return;
    }

    // If the step is triggered by tutor actions...
    if ( 'tutor_trigger' == $requirements[ 'trigger_type' ] ) {
        // Do not pass go until we say you can
        $return = false;

        // Unsupported trigger
        if ( !isset( $GLOBALS[ 'badgeos_tutor' ]->triggers[ $this_trigger ] ) ) {
            return $return;
        }

        /**
         * tutor requirements not met yet
         */
        $tutor_triggered = false;

        /**
         * Set our main vars
         */
        $tutor_trigger = $requirements['tutor_trigger'];
        $object_id = $requirements['tutor_object_id'];

        /**
         * Extra arg handling for further expansion
         */
        $object_arg1 = null;

        if ( isset( $requirements['tutor_object_arg1'] ) )
        {
            $object_arg1 = $requirements['tutor_object_arg1'];
        }

        /**
         * Object-specific triggers
         */
        
        /**
         * quiz
         * 
         */
        $tutor_quiz_triggers = array(
            'badgeos_tutor_quiz_finished',
            'badgeos_tutor_quiz_finished_specific',
            'badgeos_tutor_quiz_finished_course_specific',
            'badgeos_tutor_quiz_finished_fail',
            'badgeos_tutor_quiz_finished_fail_specific',
            'badgeos_tutor_quiz_finished_fail_course_specific',
            'badgeos_tutor_quiz_finished_completed_specific',
        );

        /**
         * lesson
         */
        $tutor_lesson_triggrs = array(
            'badgeos_tutor_lesson_completed_after',
            'badgeos_tutor_lesson_completed_after_specific',
            'badgeos_tutor_lesson_completed_after_course_specific',
        );

        /**
         * course
         */
        $tutor_course_triggrs = array(
            'badgeos_tutor_course_complete_after',
            'badgeos_tutor_course_complete_after_specific',
            'badgeos_tutor_course_complete_after_tag',
        );

        /**
         * subscribe
         */
        $tutor_subscribe_triggers = array(
            'badgeos_tutor_after_enroll',
            'badgeos_tutor_after_enroll_specific',
        );

        /**
         * Triggered object ID (used in these hooks, generally 2nd arg)
         */
        $triggered_object_id = 0;
        
        /**
         * Grab user id
         * 
         */
        if( empty( $user_id ) ){
            $user_id = get_current_user_id();
        }

        /**
         * Use basic trigger logic if no object set
         */
        
        if( in_array( $tutor_trigger, $tutor_quiz_triggers ) ){

            $attempt_id = (int) $args[0];
            $attempt = tutor_utils()->get_attempt($attempt_id);
            $quiz_id = $attempt->quiz_id;
            $course_id = $attempt->course_id;
            $quiz_earned_marks = $attempt->earned_marks;
            $quiz_total_marks = $attempt->total_marks;
            $earned_percentage = $attempt->earned_marks > 0 ? ( number_format(($attempt->earned_marks * 100) / $attempt->total_marks)) : 0;
            $user_id = $attempt->user_id;
            $passing_grade = (int) tutor_utils()->get_quiz_option($attempt->quiz_id, 'passing_grade', 0);
           
           if( $tutor_trigger == 'badgeos_tutor_quiz_finished' ){
                if( $earned_percentage >= $passing_grade ){
                    $tutor_triggered = true;
                }else{
                    $tutor_triggered = false;
                }                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_course_specific'){
                $triggered_object_id = $course_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail' ){
                if( $earned_percentage < $passing_grade ){
                    $tutor_triggered = true;
                }else{
                    $tutor_triggered = false;
                }                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage < $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_fail_course_specific'){
                $triggered_object_id = $course_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage < $passing_grade ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_quiz_finished_completed_specific'){
                $triggered_object_id = $quiz_id;
                if($triggered_object_id == $object_id ){
                    if( $earned_percentage >= $object_arg1 ){
                        $tutor_triggered=true;
                    }
                    else{
                        $tutor_triggered=false;
                    }
                }
            } 
        } elseif( in_array( $tutor_trigger, $tutor_lesson_triggrs ) ){
            $lesson_id = (int) $args[0];
            $course_id = tutor_utils()->get_course_id_by_lesson( $lesson_id );
            
            if( $tutor_trigger == 'badgeos_tutor_lesson_completed_after' ){
                $tutor_triggered = true;                   
                
            } elseif( $tutor_trigger == 'badgeos_tutor_lesson_completed_after_specific' ){
                $triggered_object_id = $lesson_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_lesson_completed_after_course_specific'){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                    $tutor_triggered=true;
                } else{
                    $tutor_triggered=false;
                }
            }
        } elseif( in_array( $tutor_trigger, $tutor_course_triggrs ) ){
            $course_id = (int) $args[0];
            
            if( $tutor_trigger == 'badgeos_tutor_course_complete_after' ){
                $tutor_triggered = true;         
                
            } elseif( $tutor_trigger == 'badgeos_tutor_course_complete_after_specific' ){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                }
            } elseif( $tutor_trigger == 'badgeos_tutor_course_complete_after_tag'){
                $triggered_object_id = $course_id;
                if( has_term( $object_id, 'course-tag', $triggered_object_id ) ){
                    $tutor_triggered=true;
                } else{
                    $tutor_triggered=false;
                }
            }
        } elseif( in_array( $tutor_trigger, $tutor_subscribe_triggers ) ){
            $course_id = (int) $args[0];
            if( $tutor_trigger == 'badgeos_tutor_after_enroll' ){
                $tutor_triggered = true;
            } elseif( $tutor_trigger == 'badgeos_tutor_after_enroll_specific' ){
                $triggered_object_id = $course_id;
                if( $triggered_object_id == $object_id ){
                        $tutor_triggered=true;
                } else{
                        $tutor_triggered=false;
                    }
            }
        }

        // TutorLMS requirements met
        if ( $tutor_triggered ) {

            // Grab the trigger count
            $trigger_count = ranks_get_user_trigger_count( $step_id, $user_id, $this_trigger, $site_id, 'Award', $args );

            // If we meet or exceed the required number of checkins, they deserve the step
            if ( 1 == $requirements[ 'count' ] || $requirements[ 'count' ] <= $trigger_count ) {
                // OK, you can pass go now
                $return = true;
            }
        }
    }

    return $return;
}
add_filter( 'badgeos_user_deserves_rank_step', 'badgeos_tutor_user_deserves_rank_step', 15, 7 );

/**
 * Check if user meets the rank requirement for a given rank
 *
 * @param  bool    $return          The current status of whether or not the user deserves this rank
 * @param  integer $step_id         The given rank's post ID
 * @param  integer $rank_id         The given rank's post ID
 * @param  integer $user_id         The given user's ID
 * @param  string  $this_trigger
 * @param  string  $site_id
 * @param  array   $args
 * @return bool                     Our possibly updated earning status
 */
function badgeos_tutor_user_deserves_rank_step_count_callback( $return, $step_id = 0, $rank_id = 0, $user_id = 0, $this_trigger = '', $site_id = 0, $args=array() ) {

    if( ! $return ) {
        return $return;
    }

    /**
     * Only override the $return data if we're working on a step
     */
    $settings = ( $exists = get_option( 'badgeos_settings' ) ) ? $exists : array();
    if ( trim( $settings['ranks_step_post_type'] ) == get_post_type( $step_id ) ) {

        if( ! empty( $this_trigger ) && array_key_exists( $this_trigger, $GLOBALS[ 'badgeos_tutor' ]->triggers ) ) {

            /**
             * Get the required number of checkins for the step.
             */
            $minimum_activity_count = absint( get_post_meta( $step_id, '_badgeos_count', true ) );

            /**
             * Grab the relevent activity for this step
             */
            $current_trigger = get_post_meta( $step_id, '_badgeos_tutor_trigger', true );
            $relevant_count = absint( ranks_get_user_trigger_count( $step_id, $user_id, $current_trigger, $site_id, $args ) );

            /**
             * If we meet or exceed the required number of checkins, they deserve the step
             */
            if ( $relevant_count >= $minimum_activity_count ) {
                $return = true;
            } else {
                $return = false;
            }
        }
    }

    return $return;
}
add_filter( 'badgeos_user_deserves_rank_step_count', 'badgeos_tutor_user_deserves_rank_step_count_callback', 10, 7 );