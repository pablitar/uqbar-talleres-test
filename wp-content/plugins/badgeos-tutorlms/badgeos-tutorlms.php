<?php
/**
 * Plugin Name: BadgeOS TutorLMS
 * Plugin URI: https://badgeos.org/downloads/tutorlms-add-on/
 * Description: This BadgeOS add-on integrates BadgeOS features with TutorLMS
 * Tags: tutorlms, badgeos, badgeos-tutorlms-integration, tutorlms-gamification
 * Author: LearningTimes, LLC
 * Version: 1.0
 * Author URI: http://www.learningtimes.com/
 * Text Domain: badgeos-tutorlms
 * Domain Path: /languages/
 * License: GNU AGPLv3
 * License URI: http://www.gnu.org/licenses/agpl-3.0.html
 */

/*
 * Copyright © 2020 Credly, LLC
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Affero General
 * Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>;.
*/

class BadgeOS_TutorLMS {

	/**
	 * Plugin Basename
	 *
	 * @var string
	 */
	public $basename = '';

	/**
	 * Plugin Directory Path
	 *
	 * @var string
	 */
	public $directory_path = '';

	/**
	 * Plugin Directory URL
	 *
	 * @var string
	 */
	public $directory_url = '';

	/**
	 * BadgeOS LearnDash Triggers
	 *
	 * @var array
	 */
	public $triggers = array();

	/**
	 * Actions to forward for splitting an action up
	 *
	 * @var array
	 */
	public $actions = array();

    /**
     * BadgeOS_LearnDash constructor.
     */
	function __construct() {

		// Define plugin constants
		$this->basename = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url = plugin_dir_url( __FILE__ );

		// If BadgeOS is unavailable, deactivate our plugin
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );

		// LearnDash Action Hooks
        $this->triggers = array(
            
            // Quizzes
            // 
            // pass
            'badgeos_tutor_quiz_finished' => __( 'Passed any Quiz', 'badgeos-tutorlms' ),
            'badgeos_tutor_quiz_finished_specific' => __( 'Passed a specific Quiz', 'badgeos-tutorlms' ),
            'badgeos_tutor_quiz_finished_course_specific' => __( 'Passed any Quiz of a course', 'badgeos-tutorlms' ),

            // fail
            'badgeos_tutor_quiz_finished_fail' => __( 'Failed any Quiz', 'badgeos-tutorlms' ),
            'badgeos_tutor_quiz_finished_fail_specific' => __( 'Failed a specific Quiz', 'badgeos-tutorlms' ),
            'badgeos_tutor_quiz_finished_fail_course_specific' => __( 'Failed any Quiz of a course', 'badgeos-tutorlms' ),
            
            // grad
            'badgeos_tutor_quiz_finished_completed_specific' => __( 'Minimum % Grade on a Quiz', 'badgeos-tutorlms' ),

            // Lessons
            'badgeos_tutor_lesson_completed_after' => __( 'Completed any Lesson', 'badgeos-tutorlms'),
            'badgeos_tutor_lesson_completed_after_specific' => __( 'Completed a specific Lesson', 'badgeos-tutorlms'),
            'badgeos_tutor_lesson_completed_after_course_specific' => __( 'Completed any Lesson of a course', 'badgeos-tutorlms'),

            // Courses
            'badgeos_tutor_course_complete_after' => __( 'Completed Course', 'badgeos-tutorlms'),
            'badgeos_tutor_course_complete_after_specific' => __( 'Completed a specific Course', 'badgeos-tutorlms'),
            'badgeos_tutor_course_complete_after_tag' => __( 'Completed Course from a Tag', 'badgeos-tutorlms' ),

            // enroll couse
            'badgeos_tutor_after_enroll' => __( 'Subscribe in a course', 'badgeos-tutorlms' ),
            'badgeos_tutor_after_enroll_specific' => __( 'subscribe in a specific course', 'badgeos-tutorlms' ),

        );

		// Actions that we need split up
		$this->actions = array(
			// course
            'tutor_course_complete_after' =>  array(
            	'actions' => array(
            		'badgeos_tutor_course_complete_after',
            		'badgeos_tutor_course_complete_after_specific',
            		'badgeos_tutor_course_complete_after_tag',
            	),
            ),
            // lesson
            'tutor_lesson_completed_after' => array(
            	'actions' => array(
            		'badgeos_tutor_lesson_completed_after',
            		'badgeos_tutor_lesson_completed_after_specific',
            		'badgeos_tutor_lesson_completed_after_course_specific',
            	),
            ),
            'tutor_after_enroll' => array(
            	'actions' => array(
            		'badgeos_tutor_after_enroll',
            		'badgeos_tutor_after_enroll_specific',
            	)
            ),
            'tutor_quiz/attempt_ended' => 'badgeos_tutor_quiz_finished',
            'tutor_quiz_finished' => 'badgeos_tutor_quiz_finished',
            'badgeos_tutor_quiz_finished' => array(
                'actions' => array(
                    'badgeos_tutor_quiz_finished_specific',
                    'badgeos_tutor_quiz_finished_course_specific',
                    'badgeos_tutor_quiz_finished_fail',
                    'badgeos_tutor_quiz_finished_fail_specific',
                    'badgeos_tutor_quiz_finished_fail_course_specific',
                    'badgeos_tutor_quiz_finished_completed_specific',
                ),
            ),
		);

        add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );

	}

	/**
	 * Check if BadgeOS is available
	 *
	 * @since  1.0.0
	 * @return bool True if BadgeOS is available, false otherwise
	 */
	public static function meets_requirements() {

		if ( !class_exists( 'BadgeOS' ) || !function_exists('tutor') ) {
			return false;
		}

		return true;

	}

	/**
	 * Generate a custom error message and deactivates the plugin if we don't meet requirements
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( !$this->meets_requirements() ) {

			$badgeos_activated = class_exists( 'BadgeOS' );
			$tutorlms_activated =  function_exists('tutor');

			if ( !$badgeos_activated || !$tutorlms_activated ) {

			    unset($_GET['activate']);
			    $message = __('<div id="message" class="error"><p><strong>BadgeOS TutorLMS Add-On</strong> requires both <a href="%s" target="_blank">%s</a> and <a href="%s" target="_blank">%s</a> add-ons to be activated.</p></div>', 'badgeos-learndash');
				echo sprintf($message,
                    'https://badgeos.org/',
                    'BadgeOS',
				    'https://wordpress.org/plugins/tutor//',
                    'TutorLMS'
                );
			}

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	}

	/**
	 * Load the plugin textdomain and include files if plugin meets requirements
	 *
	 * @since 1.0.0
	 */
	public function plugins_loaded() {
		// Load translations
		load_plugin_textdomain( 'badgeos-tutor', false, dirname( $this->basename ) . '/languages/' );

		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/rules-engine.php' );
			require_once( $this->directory_path . '/includes/steps-ui.php' );

			$this->action_forwarding();
		}
	}

	/**
	 * Forward WP actions into a new set of actions
	 *
	 * @since 1.0.0
	 */
	public function action_forwarding() {
		foreach ( $this->actions as $action => $args ) {
			$priority = 10;
			$accepted_args = 20;

			if ( is_array( $args ) ) {
				if ( isset( $args[ 'priority' ] ) ) {
					$priority = $args[ 'priority' ];
				}

				if ( isset( $args[ 'accepted_args' ] ) ) {
					$accepted_args = $args[ 'accepted_args' ];
				}
			}
			add_action( $action, array( $this, 'action_forward' ), $priority, $accepted_args );
		}
	}

	/**
	 * Forward a specific WP action into a new set of actions
	 *
	 * @return mixed Action return
	 *
	 * @since 1.0.0
	 */
	public function action_forward() {
		$action = current_filter();
		$args = func_get_args();

		if ( isset( $this->actions[ $action ] ) ) {
			if ( is_array( $this->actions[ $action ] )
				 && isset( $this->actions[ $action ][ 'actions' ] ) && is_array( $this->actions[ $action ][ 'actions' ] )
				 && !empty( $this->actions[ $action ][ 'actions' ] ) ) {
				foreach ( $this->actions[ $action ][ 'actions' ] as $new_action ) {
					if ( 0 !== strpos( $new_action, strtolower( __CLASS__ ) . '_' ) ) {
						// $new_action = strtolower( __CLASS__ ) . '_' . $new_action;
					}

					$action_args = $args;

					array_unshift( $action_args, $new_action );

					call_user_func_array( 'do_action', $action_args );
				}

				return null;
			}
			elseif ( is_string( $this->actions[ $action ] ) ) {
				$action =  $this->actions[ $action ];
			}
		}

		if ( 0 !== strpos( $action, strtolower( __CLASS__ ) . '_' ) ) {
			// $action = strtolower( __CLASS__ ) . '_' . $action;
		}

		array_unshift( $args, $action );

		return call_user_func_array( 'do_action', $args );
	}

}

if( !function_exists("dd") ) {
    function dd( $data, $exit_data = true) {
        echo '<pre>'.print_r($data, true).'</pre>';
        if($exit_data == false)
            echo '';
        else
            exit;
    }
}

$GLOBALS[ 'badgeos_tutor' ] = new BadgeOS_TutorLMS();