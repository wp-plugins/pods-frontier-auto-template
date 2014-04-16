<?php
/**
 * Front-end functionality for this plugin.
 *
 * @package   @Pods_PFAT
 * @author    Josh Pollock <Josh@JoshPress.net>
 * @license   GPL-2.0+
 * @link	  http://Pods.io
 * @copyright 2014 Josh Pollock
 */

/**
 * Pods_PFAT_Frontend class
 *
 * @class Pods_PFAT_Frontend The class that handles the output in the front-end of the templates.
 *
 * @since 0.0.1
 */
class Pods_PFAT_Frontend {

	function __construct() {

		add_filter( 'the_content', array( $this, 'front' ) );

	}

	/**
	 * Get all post type and taxonomy Pods
	 *
	 * @return array Of Pod names.
	 * @since 0.0.1
	 */
	function the_pods() {

		//use the cached results
		$the_pods = get_transient( 'pods_pfat_the_pods' );

		//check if we already have the results cached & use it if we can.
		if ( false === $the_pods || PODS_PFAT_DEV_MODE ) {
			//get all post type pods
			$the_pods = pods_api()->load_pods( array(
				'type' => array(
					'taxonomy',
					'post_type'
				),
				'names' => true )
			);

			//cache the results
			set_transient( 'pods_pfat_the_pods', $the_pods, PODS_PFAT_TRANSIENT_EXPIRE );
		}

		return $the_pods;

	}

	/**
	 * Get all Pods with auto template enable and its settings
	 *
	 * @return array With info about auto template settings per post type
	 *
	 * @since 0.0.1
	 */
	function auto_pods() {
		//try to get cached results of this method
		$auto_pods = get_transient( 'pods_pfat_auto_pods' );

		//check if we already have the results cached & use it if we can.
		if ( $auto_pods === false || PODS_PFAT_DEV_MODE ) {
			//get possible pods
			$the_pods = $this->the_pods();

			//start output array empty
			$auto_pods = array();

			//loop through each to see if auto templates is enabled
			foreach ( $the_pods as $the_pod => $the_pod_label ) {
				$pods = pods_api( $the_pod );

				//if auto template is enabled add info about Pod to array
				if ( 1 == pods_v( 'pfat_enable', $pods->pod_data[ 'options' ] ) ) {
					//check if pfat_single and pfat_archive are set
					$single = pods_v( 'pfat_single', $pods->pod_data[ 'options' ], false, true );
					$archive = pods_v( 'pfat_archive', $pods->pod_data[ 'options' ], false, true );

					//build output array
					$auto_pods[ $the_pod ] = array(
						'name' => $the_pod,
						'single' => $single,
						'archive' => $archive
					);
				}
			} //endforeach

			//cache the results
			set_transient( 'pods_pfat_auto_pods', $auto_pods, PODS_PFAT_TRANSIENT_EXPIRE );
		}

		return $auto_pods;

	}

	/**
	 * Outputs templates after the content as needed.
	 *
	 * @param string $content Post content
	 *
	 * @uses 'the_content' filter
	 *
	 * @return string Post content with the template appended if appropriate.
	 *
	 * @since 0.0.1
	 */
	function front( $content ) {

		//get global post object
		global $post;

		//first use other methods in class to build array to search in/ use
		$possible_pods = $this->auto_pods();

		//check if on a taxonomy
		global $wp_query;
		if ( isset( $wp_query->query_vars[ 'taxonomy'] ) ) {
			//if so, but current taxonomy name in a variable
			$taxonomy = $wp_query->query_vars[ 'taxonomy'];

			//and set $current_post_type to the name of the current taxonomy
			$current_post_type = $taxonomy;
		}
		else {
			//get set to current post's post type
			$current_post_type = get_post_type( $post->ID );

			//if $current_post_type is false then set it to post
			if ( $current_post_type === false ) {
				$current_post_type = 'post';
			}

			//also set $taxonomy false
			$taxonomy = false;
		}

		//check if $current_post_type is the key of the array of possible pods
		if ( isset( $possible_pods[ $current_post_type ] ) ) {
			//build Pods object for current item
			$pods = pods( $current_post_type, $post->ID );

			//get array for the current post type
			$this_pod = $possible_pods[ $current_post_type ];


			if ( $this_pod[ 'single' ] && is_singular( $current_post_type ) ) {
					//append the template
					$content = $this->load_template( $this_pod[ 'single' ], $content , $pods );

			}
			//if pfat_archive was set try to use that template
			//check if we are on an archive of the post type
			elseif ( $this_pod[ 'archive' ] && is_post_type_archive( $current_post_type ) ) {
					//append the template
					$content = $this->load_template( $this_pod[ 'archive' ], $content , $pods );

			}
			//if pfat_archive was set and we're in the blog index, try to append template
			elseif ( is_home() && $this_pod[ 'archive' ] && $current_post_type === 'post'  ) {
				//append the template
				$content = $this->load_template( $this_pod[ 'archive' ], $content , $pods );

			}
			//if is taxonomy archive of the selected taxonomy
			elseif ( is_tax( $taxonomy )  ) {
				//if pfat_single was set try to use that template
				if ( $this_pod[ 'archive' ] ) {
					//append the template
					$content = $this->load_template( $this_pod[ 'archive' ], $content , $pods );
				}

			}

		}

		return $content;

	}

	/**
	 * Attach Pods Template to $content
	 *
	 * @param string 	$template_name 	The name of a Pods Template to load.
	 * @param string	$content		Post content
	 * @param object	$pods			Current Pods object.
	 *
	 * @return string $content with Pods Template appended if template exists
	 *
	 * @since 0.0.1
	 */
	function load_template( $template_name, $content, $pods  ) {
		//get the template
		$template = $pods->template( $template_name );

		//check if we got a valid template
		if ( !is_null( $template ) ) {
			$content = $content . $template;
		}

		return $content;
	}

} //Pods_PFAT_Frontend