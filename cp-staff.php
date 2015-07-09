<?php
/*
Plugin Name: CP Staff
Version: 0.1.0
Plugin URI: http://chrisperish.com
Description: Adds Searchable Staff Page and Org Chart. | Steps: 1. Create Departments  2. Add Staff Members  3. Add shortcode [cp_staff] or [cp_staff_org] to your desired page. 4. Change settigns in CP Staff Settings menu. | When navigating to the page using ?department=yourdepartment at the end of the URL will auto search to that department.
Author: Chris Perish
Author URI: http://chrisperish.com
*/

/*Load scripts and CSS*/
function load_scripts(){
	wp_register_script('cp_staff_script',
	plugins_url('cp-staff_script.js',__FILE__)
	);
	wp_enqueue_script('cp_staff_script');
	wp_register_style('cp_staff_style',
	plugins_url('cp-staff_style.css',__FILE__)
	);
	wp_enqueue_style('cp_staff_style');
}
/*Add staff directory to page*/
function cp_add_staff_list(){
	load_scripts();
	
	$showImage = get_option('showstaffimage');
	
	//Print search box etc
	$html = "";
	$html .= '<h3>Search or Select: ';
	$html .= '<div><input type="text" class="input-medium search-query" placeholder="Search name, dept, or keyword" id="searchBox">';
	$html .= '<select id="searchDDL" class= "input-medium">';
	$html .= '<option value=" "></option>  ';
	$terms = get_terms( 'department' );
	if ( $terms ) {
		foreach ( $terms as $term ) {
			$html .='<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>';
		}
	}
	$html .= '</select></div><br/>';
	$html .= '</h3>';
	
	//Print all staff_members posts
	$args = array(
            'post_type' => 'staff_members',
            'post_status' => 'publish',
            'meta_key' => 'nameLast',
            'orderby' => 'meta_value',
            'order' => 'ASC',
            'posts_per_page' => -1,
            'meta_query' => array(
            	array(
				'key'	  	=> 'showStaffList',
				'value'	  	=> '1',
				'compare' 	=> '='
				)
				),
        );
        $query = new WP_Query( $args );
        if( $query->have_posts() ){
            while( $query->have_posts() ){
                $query->the_post();
				$html .= '<div class="staff-box" style="background-color: #' . get_option('stafflistbackgroundcolor') . '; color: #' . get_option('stafflisttextcolor') . ';">';
				if ($showImage == '1') $html .= '<div class="staff-image">' . get_the_post_thumbnail(get_the_ID(), 'thumbnail') . '</div>';
				$html .= '<div class="staff-upper">';
				$html .= '<div class="staff-name">' . get_post_meta(get_the_ID(),'nameFirst',true) . ' ' . get_post_meta(get_the_ID(),'nameLast',true) .' </div><br/>';
				$html .= '<div class="staff-title">' . get_post_meta(get_the_ID(),'jobTitle',true) . '</div><br/>';
				$html .= '<div class="staff-dept">' . strip_tags(get_the_term_list(get_the_ID(), 'department', '', ', ', '' )) . '</div><br/>';
				$html .= '<div class="staff-title">Phone: ' . get_post_meta(get_the_ID(),'phoneNumber',true);
				$html .= '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Email: <a href="mailto:' . get_post_meta(get_the_ID(),'email',true) . '">' . get_post_meta(get_the_ID(),'email',true) . '</a></div>';
				$html .= '</div>';
				$html .= '<div class="staff-lower">';
				$html .= '<div class="staff-duties">' . get_post_meta(get_the_ID(),'jobDescription',true) . '</div>';
				$html .= '</div>';
				$html .= '</div>';
            }
        }
    wp_reset_query();	
	$html .= '<div id="msgNoResults">No results found</div>';
	echo $html;
	
}

/*Add org chart to page*/
function cp_add_staff_org(){
	load_scripts();
	$showImageOrg = get_option('showstaffimageorg');
	$staffchart_scroll = get_option('staffchart_scroll');
	$staffchart_width = get_option('staffchart_width');
	$staffchart_width_window = get_option('staffchart_width_window');
	//center scrolling window
	?>
	<script type="text/javascript">
	jQuery(window).load(function(){
		window.setTimeout( center_chart, 750 );
		
	});
	function center_chart(){
		jQuery('#scroller_div').animate({"scrollLeft":(((jQuery('.google-visualization-orgchart-table').width()) - jQuery('#scroller_div').width())/2)},500);
	}
	</script>
	<?php
	$html = '';
	$html .= '<script type="text/javascript" src="https://www.google.com/jsapi?autoload={\'modules\':[{\'name\':\'visualization\',\'version\':\'1.1\',\'packages\':[\'orgchart\']}]}"></script>';
	if ($staffchart_scroll == '1'){
		$html .= '<div id="scroller_div" style="width:' . $staffchart_width_window . ';overflow: auto; overflow-y: hidden; margin: 0 auto; white-space: nowrap;"><div id="chart_div" style="width:' . $staffchart_width . ';"></div></div>';	
	}
	else {
		$html .= '<div id="scroller_div" style="width:' . $staffchart_width . ';"><div id="chart_div" style="width:' . $staffchart_width . ';"></div></div>';
	}
	$html .='<script type="text/javascript">';
	$html .='google.setOnLoadCallback(drawChart);';
    $html .='function drawChart() {';
    $html .='var data = new google.visualization.DataTable();';
    $html .='data.addColumn(\'string\', \'Name\');';
    $html .='data.addColumn(\'string\', \'Manager\');';
    $html .='data.addColumn(\'string\', \'ToolTip\');';
    $html .='data.addRows([';
	$args = array(
            'post_type' => 'staff_members',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
            	array(
				'key'	  	=> 'showOrgChart',
				'value'	  	=> '1',
				'compare' 	=> '='
				)
				),
        );
	$query = new WP_Query( $args );

        if( $query->have_posts() ){
            while( $query->have_posts() ){
                $query->the_post();
				//set vars to null
				$sFirstName = '';
				$sLastName = '';
				$sJobDescription = '';
				$sSupervisorNickName = '';
				$sNickName = '';
				//fill in nulls
				$sFirstName = get_post_meta(get_the_ID(),'nameFirst',true);
				$sLastName = get_post_meta(get_the_ID(),'nameLast',true);
				$sJobTitle = get_post_meta(get_the_ID(),'jobTitle',true);
				$sSupervisorNickName = get_post_meta(get_the_ID(),'supervisorNickName',true);
				$sNickName = get_post_meta(get_the_ID(),'nickName',true);
				if ($showImageOrg == '1'){
				$html .=  '[{v:\'' . $sNickName . '\', f:\'' 
				. '<div class="org-chart-box"><div class="staff-image-org">' . get_the_post_thumbnail($post_id, 'thumbnail') . '</div>' . '<b>' . $sJobTitle . '</b><br/>' . $sFirstName . ' ' . $sLastName . '</div>\'}, \'' . $sSupervisorNickName . '\', \'' . $sJobDescription .'\'],';
				}
				else {
					$html .=  '[{v:\'' . $sNickName . '\', f:\'' 
				. '<b>' . $sJobTitle . '</b><br/>' . $sFirstName . ' ' . $sLastName . '\'}, \'' . $sSupervisorNickName . '\', \'' . $sJobDescription .'\'],';
				}
            }
        }
    wp_reset_query();
    
    $html .=']);';
    $html .='var chart = new google.visualization.OrgChart(document.getElementById(\'chart_div\'));';
    $html .='chart.draw(data, {allowHtml:true, allowCollapse:true, nodeClass:\'org-chart-item\'});';
    $html .='}';
	$html .='</script>';
	echo $html;
}

// Custom post type
function cp_create_posttype() {
	$labels = array (
		'name'               => 'Staff Members'
	,	'singular_name'      => 'Staff Member'
	,	'add_new'            => 'Add New Member'
	,	'add_new_item'       => 'Add New Staff Member'
	,	'edit_item'          => 'Edit Staff Member'
	,	'new_item'           => 'New Staff Member'
	,	'view_item'          => 'View Staff Member'
	,	'search_items'       => 'Search Staff'
	,	'not_found'          => 'No staff found'
	,	'not_found_in_trash' => 'No staff found in Trash'
	,	'parent_item_colon'  => 'Parent Staff Member'
    );
	
	register_post_type(
		'staff_members'
	,	array (
			'public'        => TRUE
		,	'publicly_queryable'	=> TRUE
		,	'rewrite'	=> array('slug' => 'staff_member')
		,	'capability_type'	=> 'post'
		,	'has_archive'	=> TRUE
		,	'Hierarchical'	=> FALSE
		,	'label'         => 'Staff Members'
		,	'labels'        => $labels
		,	'menu_position' => 20
		,	'supports'	=> array('thumbnail' )
		)
	);
}

// Add Custom Fields
function cp_admin_init(){
		add_meta_box("staffMember-meta", "Staff Member Info", "meta_options", "staff_members", "normal", "high");
    }
function meta_options(){
        global $post;
        $custom = get_post_custom($post->ID);
        $nameFirst = $custom["nameFirst"][0];
		$nameLast = $custom["nameLast"][0];
		$jobTitle = $custom["jobTitle"][0];
		$jobDescription = $custom["jobDescription"][0];
		$email = $custom["email"][0];
		$phoneNumber = $custom["phoneNumber"][0];
		$nickName = $custom["nickName"][0];
		$supervisorNickName = $custom["supervisorNickName"][0];
		$showStaffList = $custom["showStaffList"][0];
		$showOrgChart = $custom["showOrgChart"][0];
?>
	<table>
    <tr><td><label>First Name: </label></td><td><input name="nameFirst" value="<?php echo $nameFirst; ?>" /></td></tr>
	<tr><td><label>Last Name: </label></td><td><input name="nameLast" value="<?php echo $nameLast; ?>" /></td></tr>
	<tr><td><label>Job Title: </label></td><td><input name="jobTitle" value="<?php echo $jobTitle; ?>" /></td></tr>
	<tr><td><label>Job Description: </label></td><td><textarea cols="40" rows="5" name="jobDescription" ><?php echo $jobDescription; ?></textarea></td></tr>
	<tr><td><label>Phone Number: </label></td><td><input name="phoneNumber" value="<?php echo $phoneNumber; ?>" /></td></tr>
	<tr><td><label>Email Address: </label></td><td><input name="email" value="<?php echo $email; ?>" /></td></tr>
	<tr><td><label>Network ID or Nickname(must have no spaces and be unique): </label></td><td><input name="nickName" value="<?php echo $nickName; ?>" /></td></tr>
	<tr><td><label>Network ID or Nickname of Supervisor: </label></td><td><input name="supervisorNickName" value="<?php echo $supervisorNickName; ?>" /></td></tr>
	<tr><td><label>Show in staff list</label></td><td><input name="showStaffList" type="checkbox" value="1" <?php checked( '1', $showStaffList, true ); ?> /></td></tr>
	<tr><td><label>Show in org chart: </label></td><td><input name="showOrgChart" type="checkbox" value="1" <?php checked( '1', $showOrgChart, true ); ?> /></td></tr>
	</table>
<?php
    }
//Save staff memeber fields
function cp_save_staff_member(){
    global $post;
    update_post_meta($post->ID, "nameFirst", $_POST["nameFirst"]);
	update_post_meta($post->ID, "nameLast", $_POST["nameLast"]);
	update_post_meta($post->ID, "jobTitle", $_POST["jobTitle"]);
	update_post_meta($post->ID, "jobDescription", $_POST["jobDescription"]);
	update_post_meta($post->ID, "email", $_POST["email"]);
	update_post_meta($post->ID, "phoneNumber", $_POST["phoneNumber"]);
	update_post_meta($post->ID, "nickName", $_POST["nickName"]);
	update_post_meta($post->ID, "supervisorNickName", $_POST["supervisorNickName"]);
	update_post_meta($post->ID, "showStaffList", $_POST["showStaffList"]);
	update_post_meta($post->ID, "showOrgChart", $_POST["showOrgChart"]);
}

// Add taxonomy Department
function cp_register_taxonomy() {
	// set up labels
	$labels = array(
		'name'              => 'Departments',
		'singular_name'     => 'Department',
		'search_items'      => 'Search Departments',
		'all_items'         => 'All Departments',
		'edit_item'         => 'Edit Department',
		'update_item'       => 'Update Department',
		'add_new_item'      => 'Add New Department',
		'new_item_name'     => 'New Department'
		,
		'menu_name'         => 'Departments'
	);
	// register taxonomy
	register_taxonomy( 'department', 'staff_members', array(
		'hierarchical' => true,
		'labels' => $labels,
		'query_var' => true,
		'show_admin_column' => true
	) );
}

//Change Auto Save Title
add_filter('title_save_pre', 'save_title');
function save_title($my_post_title) {
        if ($_POST['post_type'] == 'staff_members') :
          $new_title = $_POST['nameLast'];
		  $new_title .= ', '; 
		  $new_title .= $_POST['nameFirst'];
          $my_post_title = $new_title;
        endif;
        return $my_post_title;
}
add_filter('name_save_pre', 'save_name');
function save_name($my_post_name) {
        if ($_POST['post_type'] == 'staff_members') :
          $new_name = $_POST['nameLast'];
		  $new_name .= ', '; 
		  $new_name .= $_POST['nameFirst'];
          $my_post_name = $new_name;
        endif;
        return $my_post_name;
}

//Add settings page
function cp_admin_add_page() {
  add_options_page('CP Staff Settings', 'CP Staff Settings', 'manage_options', 'cp_staff_settings', 'plugin_options_page'); 
}

function plugin_options_page() {
    //HTML and PHP for Plugin Admin Page
    ?>
  	<h1>Staff Settings</h1>
  	<hr>
  	<form method="post" action="options.php">
    <?php 
    settings_fields( 'cp-staff-settings' ); 
    do_settings_sections( 'cp-staff-settings' ); 
    ?>
    <h2>Staff List Settings</h2>
    <table class="form-table">
      <tr>
      	<th>Show images in staff list: </th>
      	<td>
	  <input name="showstaffimage" type="checkbox" value="1" <?php checked( '1', get_option( 'showstaffimage' ), true ); ?> />
	  </td>
      </tr>
      <tr>
      <th>Staff list background color:</th>
      <td><input type="text" name="stafflistbackgroundcolor" value="<?php echo get_option( 'stafflistbackgroundcolor' ); ?>"/></td>
      </tr>
      <tr>
      <th>Staff list text color:</th>
      <td><input type="text" name="stafflisttextcolor" value="<?php echo get_option( 'stafflisttextcolor' ); ?>"/></td>
      </tr>
    </table>
    <hr>
    <h2>Org Chart Settings</h2>
    If your org chart looks squished horizontally adjust the org chart width and enable scrolling.<br/>
    For further customization modify the org-chart-item properties in the cp-staff_style.css file.
    <table class="form-table">
    <tr>
      	<th>Show images in staff org chart: </th>
      	<td>
	  <input name="showstaffimageorg" type="checkbox" value="1" <?php checked( '1', get_option( 'showstaffimageorg' ), true ); ?> />
	  </td>
      <tr>
      <th >Org chart width(e.g. 800px or 100%):</th>
      <td><input type="text" name="staffchart_width" value="<?php echo get_option( 'staffchart_width' ); ?>"/></td>
      </tr>
      <tr>
      	<th>Allow horizontal scrolling in chart : </th>
      	<td>
	  <input name="staffchart_scroll" type="checkbox" value="1" <?php checked( '1', get_option( 'staffchart_scroll' ), true ); ?> />
	  </td>
      </tr>
      <tr>
      <th>Scroll window width:</th>
      <td><input type="text" name="staffchart_width_window" value="<?php echo get_option( 'staffchart_width_window' ); ?>"/></td>
      </tr>
    </table>
    <?php submit_button(); ?>
  </form>

<?php
}
//Update settings page values
function cp_update_settings() {
  register_setting( 'cp-staff-settings', 'showstaffimage' );
  register_setting( 'cp-staff-settings', 'stafflistbackgroundcolor' );
  register_setting( 'cp-staff-settings', 'stafflisttextcolor' );
  register_setting( 'cp-staff-settings', 'staffchart_width' );
  register_setting( 'cp-staff-settings', 'staffchart_scroll' );
  register_setting( 'cp-staff-settings', 'staffchart_width_window' );
  register_setting( 'cp-staff-settings', 'showstaffimageorg' );
}

//Set default settings
function cp_set_defaults(){
	$o = array(
        'showstaffimage'            => '1',
        'stafflistbackgroundcolor'  => 'e6e6e6',
        'stafflisttextcolor'        => '002157',
        'staffchart_width'			=> '1200px',
        'staffchart_scroll'			=> '1',
        'staffchart_width_window'	=> '800px',
    );

    foreach ( $o as $k => $v )
    {
        update_option($k, $v);
    }
}

// Register hooks
register_activation_hook(__FILE__, 'cp_set_defaults');
add_action( 'init', 'cp_register_taxonomy' );
add_action( 'init', 'cp_create_posttype' );
add_action('save_post', 'cp_save_staff_member');
add_action('admin_menu', 'cp_admin_add_page');
add_action( 'admin_init', 'cp_update_settings' );
add_action( 'add_meta_boxes', 'cp_admin_init' );
add_shortcode('cp_staff', 'cp_add_staff_list');
add_shortcode('cp_staff_org','cp_add_staff_org');
?>