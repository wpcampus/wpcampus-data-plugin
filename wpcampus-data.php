<?php

/**
 * Plugin Name:       WPCampus: Data
 * Plugin URI:        https://wpcampus.org
 * Description:       Manages data for the WPCampus network of sites.
 * Version:           1.0.0
 * Author:            WPCampus
 * Author URI:        https://wpcampus.org
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpcampus
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) or die();

require_once wpcampus_data()->plugin_dir . 'inc/class-wpcampus-data-global.php';

final class WPCampus_Data {

	/**
	 * Holds the absolute URL and
	 * the directory path to the
	 * main plugin directory.
	 *
	 * @var string
	 */
	public $plugin_url;
	public $plugin_dir;

	/**
	 * Holds the class instance.
	 *
	 * @access	private
	 * @var		WPCampus_Data
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return	WPCampus_Data
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Magic method to output a string if
	 * trying to use the object as a string.
	 *
	 * @return string
	 */
	public function __toString() {
		return sprintf( __( '%s Data', 'wpcampus-data' ), 'WPCampus' );
	}

	/**
	 * Method to keep our instance
	 * from being cloned or unserialized
	 * and to prevent a fatal error when
	 * calling a method that doesn't exist.
	 *
	 * @return void
	 */
	public function __clone() {}
	public function __wakeup() {}
	public function __call( $method = '', $args = array() ) {}

	/**
	 * Warming up the engine.
	 */
	protected function __construct() {

		// Store the plugin URL and DIR.
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_path( __FILE__ );

	}

	/**
	 * Get the sessions from all of our events.
	 */
	public function get_event_sessions() {
		global $wpdb;

		// Will hold sessions.
		$sessions = array();

		// Do we have any filters?
		$filters = array();
		$allowed_filters = array( 'e' );
		if ( ! empty( $_GET ) ) {
			foreach ( $_GET as $get_filter_key => $get_filter_value ) {
				if ( ! in_array( $get_filter_key, $allowed_filters ) ) {
					continue;
				}
				$filters[ $get_filter_key ] = explode( ',', sanitize_text_field( $get_filter_value ) );
			}
		}

		// Store info for event sites.
		$event_sites = array(
			array(
				'site_id' => 6,
				'title'   => 'WPCampus Online 2018',
				'slug'    => 'wpcampus-online-2018',
				'date'    => '2018-01-30',
			),
			array(
				'site_id' => 7,
				'title'   => 'WPCampus 2017',
				'slug'    => 'wpcampus-2017',
				'date'    => "2017-07-15','2017-07-14",
			),
			array(
				'site_id' => 6,
				'title'   => 'WPCampus Online 2017',
				'slug'    => 'wpcampus-online-2017',
				'date'    => '2017-01-23',
			),
			array(
				'site_id' => 4,
				'title'   => 'WPCampus 2016',
				'slug'    => 'wpcampus-2016',
				'date'    => "2016-07-16','2016-07-16",
			),
		);

		$main_site_prefix = $wpdb->prefix;

		foreach ( $event_sites as $event ) {

			// If filtering by event, remove those not in the filter.
			if ( ! empty( $filters['e'] ) && ! in_array( $event['slug'], $filters['e'] ) ) {
				continue;
			}

			if ( empty( $event['slug'] ) ) {
				continue;
			}

			// Set the ID and title
			$event_site_id = $event['site_id'];

			// Get the site's DB prefix.
			$event_site_prefix = $wpdb->get_blog_prefix( $event_site_id );

			// Get the schedule URL for the site.
			$event_site_schedule_url = get_site_url( $event_site_id, '/schedule/' );

			$event_slug = $event['slug'];

			// Get the sessions.
			$site_sessions = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT proposal.meta_value AS ID,
					%d AS blog_id,
					%s AS event,
					%s AS event_slug,
					event_date.meta_value AS event_date,
					the_proposal.post_title,
					the_proposal.post_content,
					posts.post_parent,
					slides.meta_value AS slides_url,
					posts.post_name AS slug,
					CONCAT( %s, posts.post_name, '/') AS permalink,
					posts.guid
					FROM {$event_site_prefix}posts posts
					INNER JOIN {$event_site_prefix}postmeta event_type ON event_type.post_id = posts.ID AND event_type.meta_key = 'event_type' AND event_type.meta_value = 'session'
					INNER JOIN {$event_site_prefix}postmeta proposal ON proposal.post_id = posts.ID AND proposal.meta_key = 'proposal' AND proposal.meta_value != ''
					INNER JOIN {$event_site_prefix}postmeta event_date ON event_date.post_id = posts.ID AND event_date.meta_key = 'conf_sch_event_date' AND event_date.meta_value IN ('" . $event['date'] . "')
					INNER JOIN {$main_site_prefix}posts the_proposal ON the_proposal.ID = proposal.meta_value AND the_proposal.post_type = 'proposal' AND the_proposal.post_status = 'publish'
					LEFT JOIN {$main_site_prefix}postmeta slides ON slides.post_id = the_proposal.ID AND slides.meta_key = 'session_slides_url'
					WHERE posts.post_type = 'schedule' AND posts.post_status = 'publish'",
					$event_site_id, $event['title'], $event_slug, $event_site_schedule_url
				)
			);

			// Sort by title.
			usort( $site_sessions, function( $a, $b ) {
				if ( $a->post_title == $b->post_title ) {
					return 0;
				}
				return ( $a->post_title < $b->post_title ) ? -1 : 1;
			});

			// Add to complete list.
			$sessions[ $event_slug ] = array(
				'title'    => $event['title'],
				'slug'     => $event_slug,
				'sessions' => $site_sessions
			);

		}

		return $sessions;
		}

		// Sort by title.
		usort( $sessions, function( $a, $b ) {
			if ( $a->post_title == $b->post_title ) {
				return 0;
			}
			return ( $a->post_title < $b->post_title ) ? -1 : 1;
		});

		return $sessions;
	}
}

/**
 * Returns the instance of our main WPCampus_Data class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @access	public
 * @return	WPCampus_Data
 */
function wpcampus_data() {
	return WPCampus_Data::instance();
}

// Let's get this show on the road
wpcampus_data();
