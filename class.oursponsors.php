<?php

class OurSponsors {
	private static $initiated = false;
	private static $sponsors_table_name = '';
	private static $sponsor_levels_table_name = '';

	public static function init() {
		self::init_table_names();
		if ( ! self::$initiated ) {
			if(is_admin()) {
				self::init_admin_hooks();
			} else {
				self::init_user_hooks();
			}
		}
	}

	private static function init_table_names() {
		global $wpdb;
		self::$sponsors_table_name = $wpdb->prefix . "oursponsors_sponsors";
		self::$sponsor_levels_table_name = $wpdb->prefix . "oursponsors_levels";
	}

	/**
	 * Initializes WordPress hooks
	 */
	private static function init_admin_hooks() {
		self::$initiated = true;

		wp_enqueue_style( 'our-sponsors-styles', '/wp-content/plugins/oursponsors/_inc/oursponsors.css' );

		add_action( 'admin_menu', ['OurSponsors', 'create_plugin_menu'] );
		add_action( 'admin_enqueue_scripts', ['OurSponsors', 'enqueue_ajax_scripts'] );
		add_action( 'wp_ajax_manage_sponsors', ['OurSponsors', 'manage_sponsors_ajax_callback'] );
	}

	private static function init_user_hooks() {
		add_shortcode( 'oursponsors', ['OurSponsors', 'get_shortcode_content'] );

		wp_enqueue_style('our-sponsors-user-styles', '/wp-content/plugins/oursponsors/_inc/oursponsors-user.css');
	}

	public static function get_shortcode_content( $atts ) {
		global $wpdb;

		$args = shortcode_atts( array(
	        'year' => '',
	        'all' => false
	    ), $atts );

		$sponsors_table_name = self::$sponsors_table_name;
		$sponsor_levels_table_name = self::$sponsor_levels_table_name;

		$sponsors_raw = $wpdb->get_results("SELECT * FROM $sponsors_table_name ORDER BY sponsor_level, name", ARRAY_A);
		$levels = $wpdb->get_results("SELECT * FROM $sponsor_levels_table_name ORDER BY size DESC", ARRAY_A);

		$sponsors = [];

		foreach($sponsors_raw as $s) {
			if(!array_key_exists($s['sponsor_level'], $sponsors)) {
				$sponsors[$s['sponsor_level']] = [];
			}
			if ( $args['all'] || strpos($s['years'], $args['year']) !== false ) {
				$sponsors[$s['sponsor_level']][] = $s;
			}
		}

		foreach($levels as $v) {
			if ( !array_key_exists($v['id'], $sponsors)
				|| count( $sponsors[$v['id']] ) === 0
			) {
				continue;
			}
			echo ( '<h2 class="oursponsors-level-name">' . $v['name'] . '</h2>' );
			echo ( '<p class="oursponsors-level-text">' . $v['text'] . '</p>' );
			echo ( '<div class="oursponsors-level oursponsors-row row">');
			foreach($sponsors[$v['id']] as $s) {
				echo ( '<div class="oursponsors-sponsor col-md-' . $v['size'] . ' oursponsors-col-' . $v['size'] . '">');
				$image_url = wp_get_attachment_url($s['image_id']);
				echo ( '<h3 class="oursponsors-sponsor-name">' );
				echo ( self::oursponsors_linkwrap( $s['name'], $s['url'] ) );
				echo ( '</h3>' );
				if ($s['image_id']) {
					echo ( self::oursponsors_linkwrap( '<img class="oursponsors-sponsor-img" src="' . $image_url . '" alt="Logo for ' . $s['name'] . '">', $s['url'], 'oursponsors-sponsor-img-link' ) );
				}
				if ($s['text']) {
					echo ( '<div class="oursponsors-sponsor-text">' . $s['text'] . '</div>' );
				}
				echo ( '</div>' ); //close .oursponsors_sponsor
			}
			echo ( '</div>' ); //close .oursponsors_level
		}
	}

	private static function oursponsors_linkwrap($str, $link, $class="") {
		if (!$link) {
			return $str;
		}
		return "<a href='$link' class='$class'>$str</a>";
	}

	public static function plugin_activation() {
		self::init_table_names();
		self::create_tables();
		self::fill_if_empty();
	}

	public static function plugin_deactivation() {

	}

	private static function create_tables() {
		global $wpdb;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sponsors_table_name = self::$sponsors_table_name;
		$sponsor_levels_table_name = self::$sponsor_levels_table_name;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $sponsors_table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			years varchar(255) NOT NULL,
			name varchar(255) NOT NULL,
			text text NOT NULL,
			url varchar(255) DEFAULT '' NOT NULL,
			image_id mediumint(9) DEFAULT 0 NOT NULL,
			sponsor_level mediumint(9) DEFAULT 0 NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		dbDelta( $sql );

		$sql = "CREATE TABLE $sponsor_levels_table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			size mediumint(9) NOT NULL,
			text text NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";


		dbDelta( $sql );
	}

	private static function fill_if_empty() {
		global $wpdb;

		$sponsors_table_name = self::$sponsors_table_name;
		$sponsor_levels_table_name = self::$sponsor_levels_table_name;

		$sponsor_count = $wpdb->get_var( "SELECT COUNT(*) FROM $sponsors_table_name" );
		$levels_count = $wpdb->get_var( "SELECT COUNT(*) FROM $sponsor_levels_table_name" );

		if ( !$sponsor_count ) {
			$default_sponsors = [
				[
					'years' => date( 'Y' ),
					'name' => 'Example Gold Sponsor',
					'text' => 'This is an example "gold-level" sponsor.',
					'url' => 'http://purecode.com',
					'image_id' => 0,
					'sponsor_level' => 1
				],
				[
					'years' => date( 'Y' ),
					'name' => 'Example Silver Sponsor',
					'text' => 'This is an example "silver-level" sponsor.',
					'url' => 'http://purecode.com',
					'image_id' => 0,
					'sponsor_level' => 2
				],
				[
					'years' => date( 'Y' ),
					'name' => 'Example Silver Sponsor',
					'text' => 'This is another "silver-level" sponsor.',
					'url' => 'http://purecode.com',
					'image_id' => 0,
					'sponsor_level' => 2
				]
			];

			foreach( $default_sponsors as $s ) {
				$wpdb->insert( self::$sponsors_table_name,
					$s,
					[
						'years' => '%s',
						'name' => '%s',
						'text' => '%s',
						'url' => '%s',
						'image_id' => '%d',
						'sponsor_level' => '%d'
					]
				);
			}
		}

		if (!$levels_count) {
			$default_sponsors_levels = [
				[
					'name' => 'Gold',
					'text' => 'Our most valued sponsors',
					'size' => 6
				],
				[
					'name' => 'Silver',
					'text' => 'Our second most valued sponsors',
					'size' => 4
				]
			];

			foreach( $default_sponsors_levels as $s ) {
				$wpdb->insert( self::$sponsor_levels_table_name,
					$s,
					[
						'name' => '%s',
						'text' => '%s',
						'size' => '%d'
					]
				);
			}
		}
	}

	public static function create_plugin_menu() {
		add_menu_page( 'OurSponsors', 'OurSponsors', 'manage_options', 'our-sponsors', ['OurSponsors', 'manage_sponsors']);
	}

	public static function manage_sponsors() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		wp_enqueue_media();

		wp_enqueue_script('thb_media_selector', plugins_url( '/_inc/THB-WordPress-Media-Selector/thb.media_selector.js', __FILE__ ), array('jquery'));

		?><div class="wrap">
		<h3>Sponsors</h3>
		<table class="wp-list-table oursponsors_table widefat fixed striped">
			<tbody id="oursponsors_sponsors">
				<!-- target for sponsors -->
			</tbody>
			<thead>
				<tr>
					<th>Sponsor</th>
					<th>Sponsor Level</th>
					<th>Years</th>
				</tr>
			</thead>
			<tfoot>
				<tr id="oursponsors_sponsors_row_template" class="oursponsors_row" data-sponsor-id="" style="display:none">
					<td><span class="oursponsors_sponsor_name"></span></td>
					<td><span class="oursponsors_sponsor_level"></span></td>
					<td><span class="oursponsors_sponsor_years"></span></td>
				</tr>
				<tr id="oursponsors_sponsors_edit_template" style="display:none">
					<td colspan="3" class="oursponsors_sponsor inline-edit inline-edit-row quick-edit-row inline-editor" data-sponsor-id="" >
						<p>
							<label><span>Name: </span><input type="text" class="regular-text oursponsors_sponsor_name"></label>
						</p>
						<p>
							<label><span>Text: </span><textarea type="text" class="large-text oursponsors_sponsor_text"></textarea></label>
						</p>
						<p>
							<label><span>URL: </span><input type="text" class="regular-text oursponsors_sponsor_url"></label>
						</p>
						<p>
							<div class='oursponsors_image_preview_wrapper oursponsors_image_preview_wrapper'>
								<h4>Image (670 x 180)</h4>
								<img class='oursponsors_image_preview' src='' >
								<input class="oursponsors_open_media_selector" type="button" class="button" value="Upload/Select Image" />
								<input type='hidden' class='oursponsors_sponsor_image_id' value=''>
							</div>
						</p>
						<p>
							<label><span>Sponsor Level: </span><select class="oursponsors_sponsor_level"></select></label>
						</p>
						<p>
							<label><span>Years: </span><input type="text" class="regular-text oursponsors_sponsor_years"></label>
							(Comma delineated)
						</p>
						<p>
							<button class="oursponsors_sponsor_cancel">Cancel</button>
							<button class="oursponsors_sponsor_delete">Delete</button>
							<button class="oursponsors_sponsor_save">Save</button>

						</p>
					</td>
				</tr>
			</tfoot>
		</table>
		<button class="oursponsors_sponsor_add">Add Sponsor</button>
		<h3>Sponsor Levels</h3>
		<table class="wp-list-table oursponsors_table widefat fixed striped">
			<tbody id="oursponsors_sponsor_levels">
				<!-- target for sponsors -->
			</tbody>
			<thead>
				<tr>
					<th>Sponsor Level</th>
					<th>Display Size</th>
				</tr>
			</thead>
			<tfoot>
				<tr id="oursponsors_sponsor_levels_row_template" class="oursponsors_row" data-sponsor-id="" style="display:none">
					<td><span class="oursponsors_sponsor_level_name"></span></td>
					<td><span class="oursponsors_sponsor_level_size"></span></td>
				</tr>
				<tr id="oursponsors_sponsor_levels_edit_template" style="display:none">
					<td colspan="3" class="oursponsors_level inline-edit inline-edit-row quick-edit-row inline-editor" data-sponsor-id="" >
						<p>
							<label><span>Name: </span><input type="text" class="regular-text oursponsors_sponsor_level_name"></label>
						</p>
						<p>
							<label><span>Text: </span><textarea type="text" class="large-text oursponsors_sponsor_level_text"></textarea></label>
						</p>
						<p>
							<label><span>Size: </span><select class="large-text oursponsors_sponsor_level_size">
								<option value="12">Full Width (1 across)</option>
								<option value="6">Half Width (2 across)</option>
								<option value="4">Third Width (3 across)</option>
								<option value="3">Quarter Width (4 across)</option>
								<option value="2">Sixth Width (6 across)</option>
								<option value="1">Twelfth Width (12 across)</option>
							</select></label>
						</p>
						<p>
							<button class="oursponsors_sponsor_level_cancel">Cancel</button>
							<button class="oursponsors_sponsor_level_delete">Delete</button>
							<button class="oursponsors_sponsor_level_save">Save</button>
						</p>
					</td>
				</tr>
			</tfoot>
		</table>
		<button class="oursponsors_sponsor_level_add">Add Sponsor Level</button>
		</div>
		<?php
	}

	public static function enqueue_ajax_scripts($hook) {
		if( 'toplevel_page_our-sponsors' != $hook ) {
			return;
		}

		wp_enqueue_script( 'manage-sponsors', plugins_url( '/_inc/manage_sponsors.js', __FILE__ ), array('jquery') );

		wp_localize_script(
			'manage-sponsors',
			'ajax_object',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'plugin_url' => plugins_url('', __FILE__)
			)
		);
	}

	private static function echo_all_data() {
		global $wpdb;

		$sponsors_table_name = self::$sponsors_table_name;
		$sponsor_levels_table_name = self::$sponsor_levels_table_name;

		$sponsors = $wpdb->get_results("SELECT * FROM $sponsors_table_name", ARRAY_A);
		foreach($sponsors as $k => $s) {
			$sponsors[$k]['image_url'] = wp_get_attachment_url($s['image_id']);
		}

		echo (json_encode([
			'sponsors' => $sponsors,
			'levels' => $wpdb->get_results("SELECT * FROM $sponsor_levels_table_name", ARRAY_A)
		]));
	}

	// Same handler function...
	public static function manage_sponsors_ajax_callback() {
		global $wpdb;

		$subaction = $_POST['oursponsors_action'];
		$payload = $_POST['payload'];

		$sponsors_table_name = self::$sponsors_table_name;
		$sponsor_levels_table_name = self::$sponsor_levels_table_name;

		switch($subaction) {
			case 'get_all_data': {
				self::echo_all_data();
				break;
			}
			case 'update_sponsor': {
				if ($payload['id'] === 'x') {
					$result = $wpdb->insert( $sponsors_table_name,
						[
							'years' => stripslashes($payload['years']),
							'name' => stripslashes($payload['name']),
							'text' => stripslashes($payload['text']),
							'url' => stripslashes($payload['url']),
							'image_id' => intval($payload['image_id']),
							'sponsor_level' => intval($payload['sponsor_level'])
						],
						[
							'years' => '%s',
							'name' => '%s',
							'text' => '%s',
							'url' => '%s',
							'image_id' => '%d',
							'sponsor_level' => '%d'
						]
					);
				} else {
					$result = $wpdb->update(
						$sponsors_table_name,
						[
							'name' => stripslashes($payload['name']),
							'text' => stripslashes($payload['text']),
							'url' => stripslashes($payload['url']),
							'image_id' => intval($payload['image_id']),
							'sponsor_level' => intval($payload['sponsor_level']),
							'years' => stripslashes($payload['years'])
						],
						[
							'id' => intval($payload['id'])
						],
						[
							'%s', //name
							'%s', //text
							'%s', //url
							'%d', //image
							'%d', //sponsor level
							'%s' //years
						],
						[
							'%d'
						]
					);
				}
				if ($result === false) {
					// err
					echo( '"err"' );
				} elseif ($result === 0) {
					echo( '"none"' );
				} else {
					self::echo_all_data();
				}
				break;
			}
			case 'update_sponsor_level': {
				if ($payload['id'] === 'x') {
					$wpdb->insert( $sponsor_levels_table_name,
						[
							'name' => stripslashes($payload['name']),
							'text' => stripslashes($payload['text']),
							'size' => stripslashes($payload['size']),
						],
						[
							'name' => '%s',
							'text' => '%s',
							'size' => '%d'
						]
					);
				} else {
					$result = $wpdb->update(
						$sponsor_levels_table_name,
						[
							'name' => stripslashes($payload['name']),
							'text' => stripslashes($payload['text']),
							'size' => intval($payload['size']),
						],
						[
							'id' => intval($payload['id'])
						],
						[
							'%s', //name
							'%s', //text
							'%d', //size !!
						],
						[
							'%d'
						]
					);
				}
				if ($result === false) {
					// err
					echo( '"err"' );
				} elseif ($result === 0) {
					echo( '"none"' );
				} else {
					self::echo_all_data();
				}
				break;
			}
			case 'delete_sponsor' :{
				$result = $wpdb->delete($sponsors_table_name,
					[
						'id' => intval($payload['id'])
					],
					[
						'%d'
					]
				);
				if ($result === false) {
					// err
					echo( '"err"' );
				} elseif ($result === 0) {
					echo( '"none"' );
				} else {
					self::echo_all_data();
				}
				break;
			}
			case 'delete_level' :{
				$result = $wpdb->delete($sponsor_levels_table_name,
					[
						'id' => intval($payload['id'])
					],
					[
						'%d'
					]
				);
				if ($result === false) {
					// err
					echo( '"err"' );
				} elseif ($result === 0) {
					echo( '"none"' );
				} else {
					self::echo_all_data();
				}
				break;
			}
			default: {
				echo '"error"';
			}
		}

		wp_die();
	}
}
