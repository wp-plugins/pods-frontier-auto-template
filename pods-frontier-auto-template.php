<?php
/*
Plugin Name: Pods Frontier Auto Template
Plugin URI: http://pods.io/?p=182830
Description: Automatic front-end output of Pods Templates.
Version: 0.1.0
Author: Pods Framework Team
Author URI: http://pods.io/
Text Domain: pods-pfat
License: GPL v2 or later
*/

/**
 * Copyright (c) 2014 Josh Pollock (email: Josh@JoshPress.net). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

//add a dev mode for this
if ( !defined( 'PODS_PFAT_DEV_MODE' ) ) {
	define( 'PODS_PFAT_DEV_MODE', false );
}

//constant for the transient expiration time
if ( !defined( 'PODS_PFAT_TRANSIENT_EXPIRE' ) ) {
	define( 'PODS_PFAT_TRANSIENT_EXPIRE', DAY_IN_SECONDS );
}

/**
 * Pods_PFAT class
 *
 * @class Pods_PFAT The class that holds the entire pods-frontier-auto-templates plugin
 *
 * @since 0.0.1
 */
class Pods_PFAT {

	/**
	 * Constructor for the Pods_PFAT class
	 *
	 * Sets up all the appropriate hooks and actions
	 * within the plugin.
	 *
	 * @since 0.0.1
	 */
	public function __construct() {

		/**
		 * Plugin Setup
		 */
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		// Localize our plugin
		add_action( 'init', array( $this, 'localization_setup' ) );

		/**
		 * The next three hooks make the Auto Template Magic Happen
		 */
		//Add option tab for post types
		add_filter( 'pods_admin_setup_edit_tabs_post_type', array( $this, 'tab' ), 11, 3 );

		//add the same tab for taxonomies
		add_filter( 'pods_admin_setup_edit_tabs_taxonomy', array( $this, 'tab' ), 11, 3 );

		//Add options to the new tab
		add_filter( 'pods_admin_setup_edit_options', array( $this, 'options' ), 12, 2 );

		//end the magic//

		//Include and init front-end class
		add_action( 'plugins_loaded', array( $this, 'front_end' ), 25 );

		//Delete transients when Pods settings are updated.
		add_action( 'update_option', array( $this, 'reset' ), 21, 3 );

	}

	/**
	 * Initializes the Pods_PFAT() class
	 *
	 * Checks for an existing Pods_PFAT() instance
	 * and if it doesn't find one, creates it.
	 *
	 * @since 0.0.1
	 */
	public static function init() {

		static $instance = false;

		if ( !$instance ) {
			$instance = new Pods_PFAT();
		}

		return $instance;

	}

	/**
	 * Placeholder for activation function
	 *
	 * @since 0.0.1
	 */
	public function activate() {

	}

	/**
	 * Placeholder for deactivation function
	 *
	 * @since 0.0.1
	 */
	public function deactivate() {

	}

	/**
	 * Initialize plugin for localization
	 *
	 * @since 0.0.1
	 */
	public function localization_setup() {

		load_plugin_textdomain( 'pods-pfat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	}

	/**
	 * The Frontier Auto Display option tab.
	 *
	 * @param array $tabs
	 * @param array $pod
	 * @param array $addtl_args
	 *
	 * @return array
	 *
	 * @since 0.0.1
	 */
	function tab( $tabs, $pod, $addtl_args ) {

		$tabs[ 'pods-pfat' ] = __( 'Frontier Auto Display Options', 'pods-pfat' );

		return $tabs;

	}

	/**
	 * Adds options for this plugin under the Frontier Auto Template tab.
	 *
	 * @param array $options
	 * @param array $pod
	 *
	 * @return array
	 *
	 * @since 0.0.1
	 */
	function options( $options, $pod ) {
		//check if it's a post type pod and add fields for that.
		if ( $pod['type'] === 'post_type' )  {
			$options[ 'pods-pfat' ] = array (
				'pfat_enable'  => array (
					'label'             => __( 'Enable Automatic Pods Templates for this Pod?', 'pods-pfat' ),
					'help'              => __( 'When enabled you can specify the names of Pods Templates to be used to display items in this Pod in the front-end.', 'pods-pfat' ),
					'type'              => 'boolean',
					'default'           => FALSE,
					'dependency'        => TRUE,
					'boolean_yes_label' => ''
				),
				'pfat_single'  => array (
					'label'      => __( 'Single item view template', 'pods-pfat' ),
					'help'       => __( 'Name of Pods template to use for single item view.', 'pods-pfat' ),
					'type'       => 'text',
					'default'    => FALSE,
					'depends-on' => array ( 'pfat_enable' => TRUE )
				),
				'pfat_archive' => array (
					'label'      => __( 'Archive view template', 'pods-pfat' ),
					'help'       => __( 'Name of Pods template to use for use in this Pods archive pages.', 'pods-pfat' ),
					'type'       => 'text',
					'default'    => FALSE,
					'depends-on' => array ( 'pfat_enable' => TRUE )
				),
			);
		}

		//check if it's a taxonomy Pod, if so add fields for that
		if ( $pod['type'] === 'taxonomy' ) {
			$options[ 'pods-pfat' ] = array (
				'pfat_enable'  => array (
					'label'             => __( 'Enable Automatic Pods Templates for this Pod?', 'pods-pfat' ),
					'help'              => __( 'When enabled you can specify the names of a Pods Template to be used to display items in this Pod in the front-end.', 'pods-pfat' ),
					'type'              => 'boolean',
					'default'           => FALSE,
					'dependency'        => TRUE,
					'boolean_yes_label' => ''
				),
				'pfat_archive'  => array (
					'label'      => __( 'Taxonomy Template', 'pods-pfat' ),
					'help'       => __( 'Name of Pods template to use for this taxonomy.', 'pods-pfat' ),
					'type'       => 'text',
					'default'    => FALSE,
					'depends-on' => array ( 'pfat_enable' => TRUE )
				),
			);
		}

		//BTW if it's neither, no options added, Tab wouldn't be there anyway.

		return $options;

	}

	/**
	 * Include/ init the front end class on the front end only
	 *
	 * @return Pods_PFAT_Frontend
	 *
	 * @since 0.0.1
	 */
	function front_end() {

		if ( PODS_PFAT_DEV_MODE ) {
			$this->delete_transients();
		}

		if ( !is_admin() ) {
			include_once( 'classes/front-end.php' );

			$GLOBALS[ 'Pods_PFAT_Frontend' ] = new Pods_PFAT_Frontend();
		}

	}

	/**
	 * Reset the transients for front-end class when Pods are saved.
	 *
	 * @uses update_option hook
	 *
	 * @param string $option
	 * @param mixed $old_value
	 * @param mixed $value
	 *
	 * @since 0.0.1
	 */
	function reset( $option, $old_value, $value ) {

		if ( $option === '_transient_pods_flush_rewrites' ) {
			$this->delete_transients();
		}

	}

	/**
	 * Delete the transients set by this plugin
	 *
	 * @since 0.0.1
	 */
	function delete_transients() {

		delete_transient( 'pods_pfat_the_pods' );
		delete_transient( 'pods_pfat_auto_pods' );

	}

} // Pods_PFAT

/**
 * Initialize class, if Pods is active and minimum version is met.
 *
 * @since 0.0.1
 */
add_action( 'plugins_loaded', 'pfat_safe_activate');
function pfat_safe_activate() {
	if ( defined( 'PODS_VERSION' ) ) {
		if (version_compare( PODS_VERSION, '2.3.18' ) >= 0) {
			$GLOBALS[ 'Pods_PFAT' ] = Pods_PFAT::init();
		}
	}
}


/**
 * Throw admin nag if Pods isn't activated.
 *
 * Will only show on the plugins page.
 *
 * @since 0.0.1
 */
add_action( 'admin_notices', 'pfat_admin_notice_pods_not_active' );
function pfat_admin_notice_pods_not_active() {

	if ( ! defined( 'PODS_VERSION' ) ) {

		//use the global pagenow so we can tell if we are on plugins admin page
		global $pagenow;
		if ( $pagenow == 'plugins.php' ) {
			?>
				<div class="updated">
					<p><?php _e( 'You have activated Pods Frontier Auto Templates, but not the core Pods plugin.', 'pfat' ); ?></p>
				</div>
			<?php

		} //endif on the right page
	} //endif Pods is not active
}

/**
 * Throw admin nag if Pods minimum version is not met
 *
 * Will only show on the Pods admin page
 *
 * @since 0.0.1
 */
add_action( 'admin_notices', 'pfat_admin_notice_pods_min_version_fail' );
function pfat_admin_notice_pods_min_version_fail() {

	if ( defined( 'PODS_VERSION' ) ) {

		//set minimum supported version of Pods.
		$minimum_version = '2.3.18';

		//check if Pods version is greater than or equal to minimum supported version for this plugin
		if ( version_compare(  $minimum_version, PODS_VERSION ) >= 0) {

			//create $page variable to check if we are on pods admin page
			$page = pods_v('page','get', false, true );

			//check if we are on Pods Admin page
			if ( $page === 'pods' ) {
				?>
				<div class="updated">
					<p><?php _e( 'Pods Frontier Auto Templates, requires Pods version '.$minimum_version.' or later. Current version of Pods is '.PODS_VERSION, 'pfat' ); ?></p>
				</div>
			<?php

			} //endif on the right page
		} //endif version compare
	} //endif Pods is not active
}