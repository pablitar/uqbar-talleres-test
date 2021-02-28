<?php
/**
 * @package Uqbar Workshops
 * @version 0.0.1
 */
/*
Plugin Name: Uqbar Workshops
Description: Plugin for extending wordpress for https://talleres.uqbar.org
Author: Pablitar
Version: 0.0.1
Author URI: https://github.com/pablitar
*/

// This just echoes the chosen line, we'll position it later.
function hello_dolly() {
	$chosen = hello_dolly_get_lyric();
	$lang   = '';
	if ( 'en_' !== substr( get_user_locale(), 0, 3 ) ) {
		$lang = ' lang="en"';
	}

	printf(
		'<p id="dolly"><span class="screen-reader-text">%s </span><span dir="ltr"%s>%s</span></p>',
		__( 'Quote from Hello Dolly song, by Jerry Herman:' ),
		$lang,
		$chosen
	);
}

// Now we set that function up to execute when the admin_notices action is called.
//add_action( 'admin_notices', 'hello_dolly' );

// We need some CSS to position the paragraph.

//add_action( 'admin_head', 'dolly_css' );


add_action('admin_menu', 'uw_register_menu');

function uw_register_menu() {
	add_submenu_page('tutor', 'Estudiantes por Curso', 'Estudiantes por curso', 'manage_tutor', 'tutor-students-per-course', 'uw_students_list_per_course' );
}

function uw_students_list_per_course() {
	?>
	<div class="wrap">
	<h2><?php _e('Students', 'tutor') ?></h2>

	<form id="students-filter" method="get">
		<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
		
		<?php uw_course_dropdown();?>
	</form>
		<div class="students-list-container">
		<table class="wp-list-table widefat fixed striped table-view-list">
			<thead>
			<tr>
				<th>Apodo</th>
				<th>Nombre</th>
				<th>Email</th>
			</tr>
			</thead>

			<tbody id="the-list">
				<?php foreach(uw_get_enrolled_users_by_course(array_key_exists('course', $_GET) ? $_GET['course'] : '0') as $student) {?>
					<tr>
						<td><?php echo $student->user_login?></td>
						<td><?php echo $student->display_name?></td>
						<td><?php echo $student->user_email?></td>
					</tr>
				<?php }?>
			</tbody>
		</table>
		</div>
	</div>
	<?php
}

function uw_course_dropdown($selected = ''){
	$courses = (current_user_can('administrator')) ? tutor_utils()->get_courses() : tutils()->get_courses_by_instructor();
	$markup = '
		<div>
			<label>'.__('Course', 'tutor-pro').'</label>
			<select id="course" name="course" class="tutor-assignment-course-sorting" onchange="this.form.submit()">
				<option value="0">'.__('All','tutor').'</option>
				OPTIONS_PLACEHOLDER
			</select>	
		</div>
		';
	$options = '';
	$selected = array_key_exists('course', $_GET) ? $_GET['course'] : '';
	foreach($courses as $course){
		$options .= '<option value="'.$course->ID.'" '.selected($selected,$course->ID,false).'> '.$course->post_title.' </option>';
	}
	
	$content = str_replace('OPTIONS_PLACEHOLDER', $options, $markup);
	echo $content;
}

function uw_get_enrolled_users_by_course($course_id) {
	global $wpdb;

	$meta_key = '_is_tutor_student';

	$course_search = 'AND %d = 0';

	if($course_id != 0) {
		$course_search = "AND {$wpdb->posts}.post_parent = %d";
	}

	$students = $wpdb->get_results($wpdb->prepare("SELECT {$wpdb->users}.* FROM {$wpdb->users} 
		INNER JOIN {$wpdb->usermeta}
		ON ( {$wpdb->users}.ID = {$wpdb->usermeta}.user_id ) 
		INNER JOIN {$wpdb->posts}
		ON ( {$wpdb->users}.ID = {$wpdb->posts}.post_author )
		WHERE 1=1 AND ( {$wpdb->usermeta}.meta_key = '{$meta_key}' 
				  AND {$wpdb->posts}.post_type = 'tutor_enrolled'
				  {$course_search}) 
		ORDER BY {$wpdb->usermeta}.meta_value DESC", $course_id));
	
	// $user_ids = join(",", array_map(
	// 	function($student) {
	// 		return $student->ID;
	// 	}, $students));

	// $metas = uw_group_metas($wpdb->get_results("SELECT {$wpdb->usermeta}.*
	// 	 FROM {$wpdb->usermeta}
	// 	 WHERE user_id in ({$user_ids}) AND meta_key in('first_name','last_name');"));
	
	// foreach($students as $student) {
	// 	$student->meta = $metas[$student->ID];
	// }

	return $students;
}

/**
 * Function that groups an array of associative arrays by some key.
 * 
 * @param {String} $key Property to sort by.
 * @param {Array} $data Array that stores multiple associative arrays.
 */
function uw_group_metas($metas) {
    $result = array();

    foreach($metas as $val) {
        if(property_exists($val, 'user_id')){
			$user_id = $val->user_id;
			if(array_key_exists($user_id, $result)){
				$result[$user_id][$val->meta_key] = $val->meta_value;
			} else {
				$result[$user_id] = array($val->meta_key => $val->meta_value);
			}
        }
    }

    return $result;
}