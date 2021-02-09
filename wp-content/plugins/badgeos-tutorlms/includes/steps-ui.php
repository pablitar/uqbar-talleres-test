<?php
/**
 * Custom Achievement Steps UI.
 *
 * @package BadgeOS TutorLMS
 * @subpackage Achievements
 * @author LearningTimes, LLC
 * @license http://www.gnu.org/licenses/agpl.txt GNU AGPL v3.0
 * @link https://badgeos.org/downloads/tutorlms-add-on/
 */

/**
 * Update badgeos_get_step_requirements to include our custom requirements.
 *
 * @param $requirements
 * @param $step_id
 * @return mixed
 */
function badgeos_tutor_step_requirements( $requirements, $step_id ) {

    /**
     * Add our new requirements to the list
     */
    $requirements[ 'tutor_trigger' ] = get_post_meta( $step_id, '_badgeos_tutor_trigger', true );
    $requirements[ 'tutor_object_id' ] = (int) get_post_meta( $step_id, '_badgeos_tutor_object_id', true );
    $requirements[ 'tutor_object_arg1' ] = (int) get_post_meta( $step_id, '_badgeos_tutor_object_arg1', true );

    return $requirements;
}
add_filter( 'badgeos_get_deduct_step_requirements', 'badgeos_tutor_step_requirements', 10, 2 );
add_filter( 'badgeos_get_rank_req_step_requirements', 'badgeos_tutor_step_requirements', 10, 2 );
add_filter( 'badgeos_get_award_step_requirements', 'badgeos_tutor_step_requirements', 10, 2 );
add_filter( 'badgeos_get_step_requirements', 'badgeos_tutor_step_requirements', 10, 2 );

/**
 * Filter the BadgeOS Triggers selector with our own options.
 *
 * @param $triggers
 * @return mixed
 */
function badgeos_tutor_activity_triggers( $triggers ) {

    $triggers[ 'tutor_trigger' ] = __( 'TutorLMS Activity', 'badgeos-tutorlms' );
    return $triggers;
}
add_filter( 'badgeos_activity_triggers', 'badgeos_tutor_activity_triggers' );
add_filter( 'badgeos_award_points_activity_triggers', 'badgeos_tutor_activity_triggers' );
add_filter( 'badgeos_deduct_points_activity_triggers', 'badgeos_tutor_activity_triggers' );
add_filter( 'badgeos_ranks_req_activity_triggers', 'badgeos_tutor_activity_triggers' );

/**
 * Add TutorLMS Triggers selector to the Steps UI.
 *
 * @param $step_id
 * @param $post_id
 */
function badgeos_tutor_step_tutor_trigger_select( $step_id, $post_id ) {

    /**
     * Setup our select input
     */
    echo '<select name="tutor_trigger" class="select-tutor-trigger">';
    echo '<option value="">' . __( 'Select a TutorLMS Trigger', 'badgeos-tutorlms' ) . '</option>';

    /**
     * Loop through all of our TutorLMS trigger groups
     */
    $current_trigger = get_post_meta( $step_id, '_badgeos_tutor_trigger', true );

    $tutor_triggers = $GLOBALS[ 'badgeos_tutor' ]->triggers;

    if ( !empty( $tutor_triggers ) ) {
        foreach ( $tutor_triggers as $trigger => $trigger_label ) {
            if ( is_array( $trigger_label ) ) {
                $optgroup_name = $trigger;
                $triggers = $trigger_label;

                echo '<optgroup label="' . esc_attr( $optgroup_name ) . '">';

                /**
                 * Loop through each trigger in the group
                 */
                foreach ( $triggers as $trigger_hook => $trigger_name ) {
                    echo '<option' . selected( $current_trigger, $trigger_hook, false ) . ' value="' . esc_attr( $trigger_hook ) . '">' . esc_html( $trigger_name ) . '</option>';
                }
                echo '</optgroup>';
            } else {
                echo '<option' . selected( $current_trigger, $trigger, false ) . ' value="' . esc_attr( $trigger ) . '">' . esc_html( $trigger_label ) . '</option>';
            }
        }
    }

    echo '</select>';

}
add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_tutor_step_tutor_trigger_select', 10, 2 );
add_action( 'badgeos_award_steps_ui_html_after_achievement_type', 'badgeos_tutor_step_tutor_trigger_select', 10, 2 );
add_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', 'badgeos_tutor_step_tutor_trigger_select', 10, 2 );
add_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', 'badgeos_tutor_step_tutor_trigger_select', 10, 2 );

/**
 * Add a BuddyPress group selector to the Steps UI.
 *
 * @param $step_id
 * @param $post_id
 */
function badgeos_tutor_step_etc_select( $step_id, $post_id ) {

    $current_trigger = get_post_meta( $step_id, '_badgeos_tutor_trigger', true );
    $current_object_id = (int) get_post_meta( $step_id, '_badgeos_tutor_object_id', true );
    $current_object_arg1 = (int) get_post_meta( $step_id, '_badgeos_tutor_object_arg1', true );

    /**
     * Quizes
     */
    echo '<select name="badgeos_tutor_quiz_id" class="select-quiz-id">';
    echo '<option value="">' . __( 'Select a Quiz', 'badgeos-tutorlms' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = get_posts( array(
        'post_type' => 'tutor_quiz',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ) );

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_tutor_quiz_finished_specific', 'badgeos_tutor_quiz_finished_fail_specific','badgeos_tutor_quiz_finished_completed_specific' ) ) )
                $selected = selected( $current_object_id, $object->ID, false );

            echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Grade input
     */
    $grade = 100;

    if ( in_array( $current_trigger, array( 'badgeos_tutor_quiz_finished_completed_specific' ) ) )
        $grade = (int) $current_object_arg1;

    if ( empty( $grade ) )
        $grade = 100;

    echo '<span><input name="badgeos_tutor_quiz_grade" class="input-quiz-grade" type="text" value="' . $grade . '" size="3" maxlength="3" placeholder="100" />%</span>';

    /**
     * Lessons
     */
    echo '<select name="badgeos_tutor_lesson_id" class="select-lesson-id">';
    echo '<option value="">' . __( 'Select a Lesson', 'badgeos-tutorlms' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = get_posts( array(
        'post_type' => 'lesson',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ) );

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_tutor_lesson_completed_after_specific' ) ) )
                $selected = selected( $current_object_id, $object->ID, false );

            echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Courses
     */
    echo '<select name="badgeos_tutor_course_id" class="select-course-id">';
    echo '<option value="">' . __( 'Select a Course', 'badgeos-tutorlms' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = get_posts( array(
        'post_type' => 'courses',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ) );

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_tutor_course_complete_after_specific','badgeos_tutor_quiz_finished_course_specific','badgeos_tutor_quiz_finished_fail_course_specific','badgeos_tutor_lesson_completed_after_course_specific' ) ) )
                $selected = selected( $current_object_id, $object->ID, false );

            echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Course Category
     */

    echo '<select name="badgeos_tutor_course_category_id" class="select-course-category-id">';
    echo '<option value="">' . __( 'Select a Tag', 'badgeos-tutorlms' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = '';

    if( taxonomy_exists( 'course-tag' ) ) {
        $objects = get_terms( 'course-tag', array(
            'hide_empty' => false
        ) );
    }

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_tutor_course_complete_after_tag' ) ) )
                $selected = selected( $current_object_id, $object->term_id, false );

            echo '<option' . $selected . ' value="' . $object->term_id . '">' . esc_html( $object->name ) . '</option>';
        }
    }

    echo '</select>';

    /**
     * Purchased Courses
     */
    echo '<select name="badgeos_tutor_purchased_course_id" class="select-purchased-course-id">';
    echo '<option value="">' . __( 'Any Course', 'badgeos-tutorlms' ) . '</option>';

    /**
     * Loop through all objects
     */
    $objects = get_posts( array(
        'post_type' => 'courses',
        'post_status' => 'publish',
        'posts_per_page' => -1
    ) );

    if ( !empty( $objects ) ) {
        foreach ( $objects as $object ) {
            $selected = '';

            if ( in_array( $current_trigger, array( 'badgeos_tutor_after_enroll_specific' ) ) )
                $selected = selected( $current_object_id, $object->ID, false );

            echo '<option' . $selected . ' value="' . $object->ID . '">' . esc_html( get_the_title( $object->ID ) ) . '</option>';
        }
    }

    echo '</select>';
}
add_action( 'badgeos_steps_ui_html_after_trigger_type', 'badgeos_tutor_step_etc_select', 10, 2 );
add_action( 'badgeos_award_steps_ui_html_after_achievement_type', 'badgeos_tutor_step_etc_select', 10, 2 );
add_action( 'badgeos_deduct_steps_ui_html_after_trigger_type', 'badgeos_tutor_step_etc_select', 10, 2 );
add_action( 'badgeos_rank_req_steps_ui_html_after_trigger_type', 'badgeos_tutor_step_etc_select', 10, 2 );

/**
 * AJAX Handler for saving all steps.
 *
 * @param $title
 * @param $step_id
 * @param $step_data
 * @return string|void
 */
function badgeos_tutor_save_step( $title, $step_id, $step_data ) {

    /**
     * If we're working on a TutorLMS trigger
     */
    if ( 'tutor_trigger' == $step_data[ 'trigger_type' ] ) {

        /**
         * Update our TutorLMS trigger post meta
         */
        update_post_meta( $step_id, '_badgeos_tutor_trigger', $step_data[ 'tutor_trigger' ] );

        /**
         * Rewrite the step title
         */
        $title = $step_data[ 'tutor_trigger_label' ];

        $object_id = 0;
        $object_arg1 = 0;

        /**
         * pass any Quiz
         */
        if ( 'badgeos_tutor_quiz_finished' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            // $object_id = isset($step_data[ 'tutor_quiz_id' ]) ? (int) $step_data[ 'tutor_quiz_id' ] : 0;
            $object_id = 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Passed any quiz', 'badgeos-tutorlms' );
            } else {
                $title = sprintf( __( 'passed quiz "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        } elseif( 'badgeos_tutor_quiz_finished_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset($step_data[ 'tutor_quiz_id' ]) ? (int) $step_data[ 'tutor_quiz_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Passed any quiz', 'badgeos-tutorlms' );
            } else {
                $title = sprintf( __( 'passed quiz "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        } elseif( 'badgeos_tutor_quiz_finished_course_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset($step_data[ 'tutor_course_id' ]) ? (int) $step_data[ 'tutor_course_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Passed any quiz of a course', 'badgeos-tutorlms' );
            } else {
                $title = sprintf( __( 'passed any quiz of "%s" course', 'badgeos-tutorlms' ),get_the_title( $object_id ) );
            }
        } elseif( 'badgeos_tutor_quiz_finished_completed_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset( $step_data[ 'tutor_quiz_id' ] ) ? (int) $step_data[ 'tutor_quiz_id' ] : 0;
            $object_arg1 = isset( $step_data[ 'tutor_quiz_grade' ] ) ? (int) $step_data[ 'tutor_quiz_grade' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = sprintf( __( 'Completed any quiz with a score of %d or higher', 'badgeos-tutorlms' ), $object_arg1 );
            } else {
                $title = sprintf( __( 'Completed quiz "%s" with a score of %d or higher', 'badgeos-tutorlms' ), get_the_title( $object_id ), $object_arg1 );
            }
        } elseif ( 'badgeos_tutor_quiz_finished_fail' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            // $object_id = isset($step_data[ 'tutor_quiz_id' ]) ? (int) $step_data[ 'tutor_quiz_id' ] : 0;
            $object_id = 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = sprintf( __( 'Failed any quiz', 'badgeos-tutorlms' ), $object_arg1 );
            }  else {
                $title = sprintf( __( 'Failed quiz "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ), $object_arg1 );
            }
        } elseif ( 'badgeos_tutor_quiz_finished_fail_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset($step_data[ 'tutor_quiz_id' ]) ? (int) $step_data[ 'tutor_quiz_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = sprintf( __( 'Failed any quiz', 'badgeos-tutorlms' ), $object_arg1 );
            }  else {
                $title = sprintf( __( 'Failed quiz "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ), $object_arg1 );
            }
        } elseif ( 'badgeos_tutor_quiz_finished_fail_course_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset($step_data[ 'tutor_course_id' ]) ? (int) $step_data[ 'tutor_course_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = sprintf( __( 'Failed any quiz of a course', 'badgeos-tutorlms' ), $object_arg1 );
            }  else {
                $title = sprintf( __( 'Failed any quiz of "%s" course', 'badgeos-tutorlms' ), get_the_title( $object_id ), $object_arg1 );
            }
        } elseif ( 'badgeos_tutor_lesson_completed_after' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            // $object_id = isset( $step_data[ 'tutor_lesson_id' ] ) ? (int) $step_data[ 'tutor_lesson_id' ] : 0;
            $object_id = 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Completed any lesson', 'badgeos-tutorlms' );
            } else {
                $title = sprintf( __( 'Completed lesson "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_tutor_lesson_completed_after_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset( $step_data[ 'tutor_lesson_id' ] ) ? (int) $step_data[ 'tutor_lesson_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Completed any lesson', 'badgeos-tutorlms' );
            } else {
                $title = sprintf( __( 'Completed lesson "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_tutor_lesson_completed_after_course_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset( $step_data[ 'tutor_course_id' ] ) ? (int) $step_data[ 'tutor_course_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Completed any lesson of a course', 'badgeos-tutorlms' );
            } else {
                $title = sprintf( __( 'Completed any lesson of "%s" course', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_tutor_course_complete_after' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            // $object_id = isset($step_data[ 'tutor_course_id' ]) ? (int) $step_data[ 'tutor_course_id' ] : 0;
            $object_id = 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Completed any course', 'badgeos-tutorlms' );
            }  else {
                $title = sprintf( __( 'Completed course "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_tutor_course_complete_after_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset($step_data[ 'tutor_course_id' ]) ? (int) $step_data[ 'tutor_course_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Completed a course', 'badgeos-tutorlms' );
            }  else {
                $title = sprintf( __( 'Completed "%s" course', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_tutor_course_complete_after_tag' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset( $step_data[ 'tutor_course_category_id' ] ) ? (int) $step_data[ 'tutor_course_category_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Completed course in any tag', 'badgeos-tutorlms' );
            } else {
                     if( get_term( $object_id, 'course-tag' ) && taxonomy_exists( 'course-tag' ) ) {
                    $title = sprintf( __( 'Completed course in tag "%s"', 'badgeos-tutorlms' ), get_term( $object_id, 'course-tag' )->name );
                }
            }
        } elseif ( 'badgeos_tutor_after_enroll' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            // $object_id = isset( $step_data[ 'tutor_purchased_course_id' ] ) ? (int) $step_data[ 'tutor_purchased_course_id' ] : 0;
            $object_id = 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Subscribe any course', 'badgeos-tutorlms' );
            } else {
                $title = sprintf( __( 'Subscribe course "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        } elseif ( 'badgeos_tutor_after_enroll_specific' == $step_data[ 'tutor_trigger' ] ) {

            /**
             * Get Object ID
             */
            $object_id = isset( $step_data[ 'tutor_purchased_course_id' ] ) ? (int) $step_data[ 'tutor_purchased_course_id' ] : 0;

            /**
             * Set new step title
             */
            if ( $object_id == 0 ) {
                $title = __( 'Subscribe any course', 'badgeos-tutorlms' );
            } else {
                $title = sprintf( __( 'Subscribe course "%s"', 'badgeos-tutorlms' ), get_the_title( $object_id ) );
            }
        }

        /**
         * Store our Object ID in meta
         */
        update_post_meta( $step_id, '_badgeos_tutor_object_id', $object_id );
        update_post_meta( $step_id, '_badgeos_tutor_object_arg1', $object_arg1 );
    }

    return $title;
}
add_filter( 'badgeos_save_step', 'badgeos_tutor_save_step', 10, 3 );

/**
 * Include custom JS for the BadgeOS Steps UI.
 */
function badgeos_tutor_step_js() {
    ?>
    <script type="text/javascript">
        jQuery( document ).ready( function ( $ ) {

            var times = $( '.required-count' ).val();

            /**
             * Listen for our change to our trigger type selector
             */
            $( document ).on( 'change', '.select-trigger-type', function () {

                var trigger_type = $( this );
                var trigger_parent = trigger_type.parent();
                /**
                 * Show our group selector if we're awarding based on a specific group
                 */
                if ( 'tutor_trigger' == trigger_type.val() ) {
                    trigger_type.siblings( '.select-tutor-trigger' ).show().change();
                    var trigger = trigger_parent.find('.select-tutor-trigger').val();
                    if ( 'badgeos_tutor_quiz_finished_completed_specific'  == trigger ) {
                        trigger_parent.find('.input-quiz-grade').parent().show();
                    }
                    if( parseInt( times ) < 1 )
                        trigger_parent.find('.required-count').val('1');//.prop('disabled', true);
                }  else {
                    trigger_type.siblings( '.select-tutor-trigger' ).val('').hide().change();
                    trigger_parent.find( '.input-quiz-grade' ).parent().hide();
                    var fields = ['quiz','lesson','course','course-category','purchased-course'];
                    $( fields ).each( function( i,field ) {
                        trigger_parent.find('.select-' + field + '-id').hide();
                    });
                    trigger_parent.find( '.required-count' ).val( times );//.prop( 'disabled', false );
                }
            } );

            /**
             * Listen for our change to our trigger type selector
             */
            $( document ).on( 'change', '.select-tutor-trigger,' +
                '.select-quiz-id,' +
                '.select-lesson-id,' +
                '.select-course-id,' +
                '.select-course-category-id' +
                '.select-purchased-course-id', function () {
                badgeos_tutor_step_change( $( this ) , times);
            } );

            /**
             * Trigger a change so we properly show/hide our TutorLMS menues
             */
            $( '.select-trigger-type' ).change();

            /**
             * Inject our custom step details into the update step action
             */
            $( document ).on( 'update_step_data', function ( event, step_details, step ) {
                step_details.tutor_trigger = $( '.select-tutor-trigger', step ).val();
                step_details.tutor_trigger_label = $( '.select-tutor-trigger option', step ).filter( ':selected' ).text();

                step_details.tutor_quiz_id = $( '.select-quiz-id', step ).val();
                step_details.tutor_quiz_grade = $( '.input-quiz-grade', step ).val();
                step_details.tutor_lesson_id = $( '.select-lesson-id', step ).val();
                step_details.tutor_course_id = $( '.select-course-id', step ).val();
                step_details.tutor_course_category_id = $( '.select-course-category-id', step ).val();
                step_details.tutor_purchased_course_id = $( '.select-purchased-course-id', step ).val();
            } );

        } );

        function badgeos_tutor_step_change( $this , times) {

            var trigger_parent = $this.parent(),
                trigger_value = trigger_parent.find( '.select-tutor-trigger' ).val();
            var trigger_parent_value = trigger_parent.find( '.select-trigger-type' ).val();

            /**
             * Quiz specific
             */
            trigger_parent.find( '.select-quiz-id' )
                .toggle(
                    ( 'badgeos_tutor_quiz_finished_specific' == trigger_value
                        || 'badgeos_tutor_quiz_finished_fail_specific' == trigger_value
                        || 'badgeos_tutor_quiz_finished_completed_specific' == trigger_value )
                );

            /**
             * Lesson specific
             */
            trigger_parent.find( '.select-lesson-id' )
                .toggle( 'badgeos_tutor_lesson_completed_after_specific' == trigger_value );

            /**
             * Course specific
             */
            trigger_parent.find( '.select-course-id' )
                .toggle( 
                    ( 'badgeos_tutor_course_complete_after_specific' == trigger_value
                        || 'badgeos_tutor_lesson_completed_after_course_specific' == trigger_value
                        || 'badgeos_tutor_quiz_finished_course_specific' == trigger_value
                        || 'badgeos_tutor_quiz_finished_fail_course_specific' == trigger_value )
                );

            /**
             * Course Category specific
             */
            trigger_parent.find( '.select-course-category-id' )
                .toggle( 'badgeos_tutor_course_complete_after_tag' == trigger_value );

            /**
             * Quiz Grade specific
             */
            trigger_parent.find( '.input-quiz-grade' ).parent() // target parent span
                .toggle( 'badgeos_tutor_quiz_finished_completed_specific' == trigger_value );

            /**
             * Purchased Course
             */
            trigger_parent.find( '.select-purchased-course-id' )
                .toggle( 'badgeos_tutor_after_enroll_specific' == trigger_value );


            if ( ( 'badgeos_tutor_quiz_finished_specific' == trigger_value && '' != trigger_parent.find( '.select-quiz-id' ).val() )
                || ( 'badgeos_tutor_quiz_finished_course_specific' == trigger_value && '' != trigger_parent.find( '.select-course-id' ).val() )
                || ( 'badgeos_tutor_quiz_finished_completed_specific' == trigger_value && '' != trigger_parent.find( '.select-quiz-id' ).val() )
                || ( 'badgeos_tutor_quiz_finished_fail_specific' == trigger_value && '' != trigger_parent.find( '.select-quiz-id' ).val() )
                || ( 'badgeos_tutor_quiz_finished_fail_course_specific' == trigger_value && '' != trigger_parent.find( '.select-course-id' ).val() )
                || ( 'badgeos_tutor_lesson_completed_after_specific' == trigger_value && '' != trigger_parent.find( '.select-lesson-id' ).val() )
                || ( 'badgeos_tutor_lesson_completed_after_course_specific' == trigger_value && '' != trigger_parent.find( '.select-course-id' ).val() )
                || ( 'badgeos_tutor_course_complete_after_specific' == trigger_value && '' != trigger_parent.find( '.select-course-id' ).val() )
                || ( 'badgeos_tutor_course_complete_after_tag' == trigger_value && '' != trigger_parent.find( '.select-course-category-id' ).val()
                || ( 'badgeos_tutor_after_enroll_specific' == trigger_value && '' != trigger_parent.find( '.select-purchased-course-id' ).val())
                    ) ) {
                trigger_parent.find( '.required-count' )
                    .val( '1' );//.prop( 'disabled', true );
            } else {

                if(trigger_parent_value != 'tutor_trigger') {

                    trigger_parent.find('.required-count')
                        .val(times);//.prop('disabled', false);
                }
            }
        }
    </script>
    <?php
}
add_action( 'admin_footer', 'badgeos_tutor_step_js' );
