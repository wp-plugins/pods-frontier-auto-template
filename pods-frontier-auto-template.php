<?php
/*
Plugin Name: Pods Frontier Auto Template
Plugin URI: http://pods.io/?p=182830
Description: Automatic front-end output of Pods Templates.
Version: 1.2.1
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



/**
 * Initialize class, if Pods is active and minimum version is met.
 *
 * @since 0.0.1
 */
add_action( 'plugins_loaded', 'pfat_safe_activate');
function pfat_safe_activate() {
	if ( defined( 'PODS_VERSION' ) ) {
		if ( version_compare( PODS_VERSION, '2.5.5', '>=' ) ) {
			return;

		}



		//add a dev mode for this
		if ( ! defined( 'PODS_PFAT_DEV_MODE' ) ) {
			define( 'PODS_PFAT_DEV_MODE', false );
		}

//constant for the transient expiration time
		if ( ! defined( 'PODS_PFAT_TRANSIENT_EXPIRE' ) ) {
			define( 'PODS_PFAT_TRANSIENT_EXPIRE', DAY_IN_SECONDS );
		}


		if ( version_compare( PODS_VERSION, '2.3.18' ) >= 0 ) {
			include_once( dirname(__FILE__ ) . '/classes/main.php' );
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

	if ( defined( 'PODS_PFAT_TRANSIENT_EXPIRE' ) && ! defined( 'PODS_VERSION' ) ) {

		//use the global pagenow so we can tell if we are on plugins admin page
		global $pagenow;
		if ( $pagenow == 'plugins.php' ) {
			?>
				<div class="error">
					<p><?php _e( 'You have activated Pods Frontier Auto Template, but not the core Pods plugin.', 'pfat' ); ?></p>
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

	if ( defined( 'PODS_PFAT_TRANSIENT_EXPIRE' ) && defined( 'PODS_VERSION' ) ) {

		//set minimum supported version of Pods.
		$minimum_version = '2.3.18';

		//check if Pods version is greater than or equal to minimum supported version for this plugin
		if ( version_compare(  $minimum_version, PODS_VERSION ) >= 0) {

			//create $page variable to check if we are on pods admin page
			$page = pods_v('page','get', false, true );

			//check if we are on Pods Admin page
			if ( $page === 'pods' ) {
				?>
				<div class="error">
					<p><?php _e( 'Pods Frontier Auto Template, requires Pods version '.$minimum_version.' or later. Current version of Pods is '.PODS_VERSION, 'pfat' ); ?></p>
				</div>
			<?php

			} //endif on the right page

		} //endif version compare

	} //endif Pods is not active

}
