<?php
/**
 * @package merlinObjectBrowser

 * Plugin Name: Merlin Object Browser
 * Description: Use Merlin Archive for selecting images for WordPress, requires Merlin X 5.4.024 or later and Merlin SODA utility
 * Version:	1.7
 * Author:		MerlinOne Development Team
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * License:	GPL2

 * Copyright 2018	MerlinOne Inc. (email : cforber@merlinone.com)

 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License along with this program.	If not, see http://www.gnu.org/licenses/.
 */

/**
 * Main Class - MOB stands for Merlin Object Browser.
 */
class MOB {
	/**
	 * --------------------------------------------*
	 * Attributes
	 * --------------------------------------------
	 */

	/**
	 * @var object|null $_instance	Refers to a single instance of this class
	 */
	private static $_instance = null;

	/**
	 * @var object $options	Saved options
	 */
	public $options;

	/**
	 * --------------------------------------------*
	 * Constructor
	 * --------------------------------------------
	 */


	/**
	 * Creates or returns an instance of this class.
	 *
	 * @return MOB_Theme_Options A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$_instance ) {
			self::$_instance = new self;
		}

		return self::$_instance;

	}//end get_instance()


	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 */
	private function __construct() {
		// Add strings for buttons.
		add_filter( 'media_view_strings', array( $this, 'custom_media_strings' ), 10, 2 );
		// Enqueue merlinObjectBrowser_extend.js and merlinObjectBrowser.css.
		add_action( 'admin_enqueue_scripts', array( $this, 'merlin_object_browser_scripts' ) );
		// Add js to parent with necessary js functions and global variables.
		add_action( 'admin_enqueue_scripts', array( $this, 'plugin_load_plugin_vars' ) );
		// Add function as html POST action, to process upload metadata.
		add_action( 'admin_post_merlin_object_upload', array( $this, 'upload_merlin_object' ) );
		// Add to admin settings.
		add_action( 'admin_menu', array( $this, 'merlin_object_browser_add_admin_menu' ) );
		// Initial settings for admin.
		add_action( 'admin_init', array( $this, 'merlin_object_browser_settings_init' ) );

	}//end __construct()


	/**
	 * --------------------------------------------*
	 * Functions
	 * --------------------------------------------
	 */


	/**
	 * Labels for titles and buttons
	 *
	 * @param array	 $strings List of media view strings.
	 * @param WP_Post $post Post object.
	 *
	 * @return array $strings Updated $strings array
	 */
	public function custom_media_strings( $strings, $post ) {
		$strings['MOB_custom_menu_title'] = __( 'Insert from Merlin', 'MOB_custom' );
		$strings['MOB_custom_button']	= __( 'Go to Library', 'MOB_custom' );
		return $strings;

	}//end custom_media_strings()


	/**
	 * Enqueue merlinObjectBrowser_extend.js and merlinObjectBrowser.css
	 *
	 * @return void
	 */
	public function merlin_object_browser_scripts() {
		wp_enqueue_script( 'objectBrowser-extend', plugins_url( '/merlinObjectBrowser_extend.js', __FILE__ ), array( 'media-views' ), null );
		wp_enqueue_style( 'objectBrowser-css', plugins_url( '/merlinObjectBrowser.css', __FILE__ ), null, null );

	}//end merlin_object_browser_scripts()


	/**
	 * Add js to parent with necessary js functions and global variables.
	 *
	 * @return void
	 */
	public static function plugin_load_plugin_vars() {
		$homeurl	= admin_url();
		$pluginurl	= plugins_url( '', __FILE__ );
		$options	= get_option( 'merlin_object_browser_settings' );
		$archiveurl = '';
		$enturl	= '';
		if ( true === isset( $options['archiveURL'] ) ) {
			$archiveurl = $options['archiveURL'];
			$enturl	= untrailingslashit( $archiveurl ) . '?altprofile=cms&cmstype=wordpress&cmspluginurl=' . rawurlencode( $pluginurl ) . '&cmsurl=' . rawurlencode( $homeurl );
		}
		?>
<script type="text/javascript">
	var merlinObjectBrowser_merlinUrl = <?php echo wp_json_encode( $enturl ) ?>;
	var MOB_latestUploadId = '';

	function mOB_simulateClick(el, etype) {
		var theButton = document.getElementById(el);
		if ( theButton )
		{
			if ( document.all )
			{
				theButton.click();
			} else {
				var evObj = document.createEvent('MouseEvents');
				evObj.initMouseEvent('click', true, true, window, 1, 12, 345, 7, 220, false, false, true, false, 0, null );
				theButton.dispatchEvent(evObj);
			}
		}
	}
</script>
		<?php

	}//end plugin_load_plugin_vars()


	/**
	 * Add function as html POST action, to process upload metadata.
	 *
	 * @return void
	 */
	public function upload_merlin_object() {
		media_upload_header();
		if ( true === isset( $_POST['merlinObjectBrowser_urls'] ) ) {
			$mob_urljson = wp_unslash( $_POST['merlinObjectBrowser_urls'] );
			// Check that data returned is in valid json format.
			if ( false === self::_is_json( $mob_urljson ) ) {
				self::merlin_object_browser_form( '<div style="display:inline-block;" class="error form-invalid">Invalid data returned from Merlin archive, not a JSON object.' . $mob_urljson . '</div>' );
			} else {
				$options	= get_option( 'merlin_object_browser_settings' );
				$archive_maxDim	= '';
				if ( true === isset( $options['maximumDimension'] ) ) {
					$archive_maxDim = $options['maximumDimension'];
				}
				$mob_urlobjs	= json_decode( $mob_urljson, true );
				$attachments	= '';
				foreach ( $mob_urlobjs as $mob_urlobj ) {
					$attachmentid = self::merlin_object_browser_handle_upload( $mob_urlobj, $archive_maxDim );
					if ( true === is_wp_error( $attachmentid ) ) {
						$return_message = $attachmentid -> get_error_message();
						self::merlin_object_browser_form( '<div style="display:inline-block;" class="error form-invalid">' . sanitize_text_field( $return_message ) . '</div>' );
					} else {
						if ( '' !== $attachments ) {
							$attachments = $attachments . ',';
						}

						$attachments = $attachments . $attachmentid;
					}
				}

				self::_backtoparent( $attachments );
			}//end if
		} else {
			self::merlin_object_browser_form();
		}//end if

	}//end upload_merlin_object()


	/**
	 * The tab content
	 *
	 * @param string $header_message String to show in header, if not empty.
	 *
	 * @return void
	 */
	public function merlin_object_browser_form( $header_message = '' ) {
		media_upload_header();
		$homeurl	= home_url();
		$pluginurl	= plugins_url( '', __FILE__ );
		$options	= get_option( 'merlin_object_browser_settings' );
		$archiveurl	= '';
		if ( true === isset( $options['archiveURL'] ) ) {
			$archiveurl = $options['archiveURL'];
		}

		$enturl		= untrailingslashit( esc_url( $archiveurl ) ) . '?altprofile=wordpress&cmspluginurl=' . rawurlencode( $pluginurl ) . '&cmsurl=' . rawurlencode( $homeurl );
		$topform	= '<span id="merlin_object_browser_message">' . $header_message . '</span>';
		$iframe		= '<iframe src="' . esc_attr( $enturl ) . '" style="width:100%; height:calc(100% - 5px);" id="merlin_object_browser_int"></iframe>';
		echo wp_kses_post( $topform . $iframe );

	}//end merlin_object_browser_form()


	/**
	 * Function that does the actual file upload based on the object from Merlin.
	 *
	 * @param object $mob_urlobj JSON object with metadata and upload url for a single Merlin object.
	 * @param string $archive_maxDim optional value set in admin form, maximum dimension for transferred image, in pixels.
	 *
	 * @return int $attachmentid The ID of the attachment or a WP_Error on failure.
	 */
	public function merlin_object_browser_handle_upload( $mob_urlobj, $archive_maxDim ) {
		$description 	= '';
		$merlinid	= sanitize_text_field( $mob_urlobj['cimageid'] );
		if ( false === self::_is_null_or_emptystring( $merlinid ) ) {
			$description = 'Merlin ID: ' . $merlinid . '. ';
		}

		// Caption through headline expected values are simple text strings.
		// Sanitize each and then concatenate non-empty values of caption, copyright and tags into description.
		// Headline and cobj get used elsewhere.
		$caption	= sanitize_text_field( $mob_urlobj['capt2120'] );
		$copyright	= sanitize_text_field( $mob_urlobj['copyright'] );
		$tags		= sanitize_text_field( $mob_urlobj['keywords'] );
		$headline	= sanitize_text_field( $mob_urlobj['headline'] );
		$cobj		= sanitize_text_field( $mob_urlobj['cobject205'] );
		$url_SODA	= $mob_urlobj['url'];
		// Need to check if the SODA url contains a cropinfo setting, if so, need to remove it
		if ( false != strpos($url_SODA, 'cropinfo') ) {
			$url_base	= substr( $url_SODA, 0, strpos($url_SODA, '&cropinfo') );
			$url_end	= substr( $url_SODA, strpos($url_SODA, 'cropinfo=') );
			if ( false != strpos( $url_end, "&" ) ) {
				// There are other settings in the url after &cropinfo, get those and reapply to base
				$url_end	= substr ( $url_end, strpos( $url_end, "&" ) );
				$url_base	= $url_base . $url_end;
			}
			$url_SODA	= $url_base;
		}
		// If a maximum dimension has been configured in the Merlin Object browser settings, need to apply it
		if ( true === self::_checkSODAversion() && 256 < intval( $archive_maxDim ) ) {
			$url_SODA = $url_SODA . "&cropinfo=d" . $archive_maxDim;
		}
		$tmp		 = download_url( $url_SODA );
		if ( true === is_wp_error( $tmp ) ) {
			return new WP_Error( 'merlin_object_browser', 'Could not download image from remote source' );
		}
		if ( false === self::_is_null_or_emptystring( $caption ) ) {
			$description = $description . $caption . ' ';
		}

		if ( false === self::_is_null_or_emptystring( $copyright ) ) {
			$description = $description . ' Copyright: ' . $copyright . '. ';
		}

		if ( false === self::_is_null_or_emptystring( $tags ) ) {
			$description = $description . $tags;
		}

		// Build up array like PHP file upload.
		$fileinfo		= array();
		$fileinfo['name'] 	= $cobj;
		$fileinfo['tmp_name'] 	= $tmp;

		$post_data = array(
						'post_excerpt' => $caption,
						'post_content' => $description,
					 );
		$postid = get_the_ID();
		$attachmentid = media_handle_sideload( $fileinfo, $postid, $cobj, $post_data );
		// Check for handle sideload errors.
		if ( true === is_wp_error( $attachmentid ) ) {
			return new WP_Error( 'merlin_object_browser', 'Could not download image from remote source' );
		}

		// Create the thumbnails and get the initial metadata.
		$attach_data = wp_generate_attachment_metadata( $attachmentid, get_attached_file( $attachmentid ) );
		wp_update_attachment_metadata( $attachmentid,	$attach_data );
		if ( false === self::_is_null_or_emptystring( $headline ) ) {
			update_post_meta( $attachmentid, '_wp_attachment_image_alt', $headline );
		}

		return $attachmentid;

	}//end merlin_object_browser_handle_upload()


	/**
	 * Function to add button at bottom of content area to return user to Media Library
	 *
	 * @param string $ids comma-delimited list of attachment ids.
	 *
	 * @return void
	 */
	private function _backtoparent( $ids ) {
		media_upload_header();
		?>
<!DOCTYPE html>
<html>
<head>
	<script>
		window.parent.parent.mOB_simulateClick('MOB_goToLibrary', 'click');
	</script>
</head>
<body>
</body>
</html>
		<?php

	}//end _backtoparent()


	/**
	 * Functions for settings admin.
	 *
	 * @return void
	 */
	public function merlin_object_browser_add_admin_menu() {
		add_options_page(
			'Merlin Object Browser Settings',
			'Merlin Object Browser',
			'manage_options',
			'merlin_object_browser',
			array(
			 $this,
			 'merlin_object_browser_options_page',
			)
		);

	}//end merlin_object_browser_add_admin_menu()


	/**
	 * Initial admin settings.
	 *
	 * @return void
	 */
	public function merlin_object_browser_settings_init() {
		register_setting( 'pluginPage', 'merlin_object_browser_settings' );
		add_settings_section(
			'merlin_object_browser_pluginPage_section',
			__( 'Merlin Archive', 'wordpress' ),
			array(
			 $this,
			 'merlin_object_browser_settings_section_callback',
			),
			'pluginPage'
		);
		add_settings_field(
			'archiveURL',
			__( 'Merlin Archive URL', 'wordpress' ),
			array(
			 $this,
			 'merlin_object_browser_archive_url_render',
			),
			'pluginPage',
			'merlin_object_browser_pluginPage_section'
		);
		add_settings_field(
			'maximumDimension',
			__( 'Maximum Image Dimension (pixels)', 'wordpress' ),
			array(
			 $this,
			 'merlin_object_browser_maxDim_render',
			),
			'pluginPage',
			'merlin_object_browser_pluginPage_section'
		);
		register_setting( 'pluginPage', 'archiveURL' );
		register_setting( 'pluginPage', 'maximumDimension' );

	}//end merlin_object_browser_settings_init()


	/**
	 * Admin form to set archiveURL
	 *
	 * @return void
	 */
	public function merlin_object_browser_archive_url_render() {
		$options		 = get_option( 'merlin_object_browser_settings' );
		$archive_url_set = '';
		if ( true === isset( $options['archiveURL'] ) ) {
			$archive_url_set = $options['archiveURL'];
		}
		?>
		<input type='text' name='merlin_object_browser_settings[archiveURL]' size="50" value='<?php echo esc_url( $archive_url_set ); ?>'>
		<?php

	}//end merlin_object_browser_archive_url_render()


	/**
	 * Admin form to set maximumDimension
	 *
	 * @return void
	 */
	public function merlin_object_browser_maxDim_render() {
		$options		 = get_option( 'merlin_object_browser_settings' );
		$archive_maxDim = '';
		if ( true === isset( $options['maximumDimension'] ) ) {
			$archive_maxDim = $options['maximumDimension'];
		}
		if ( true === self::_checkSODAversion() ) {
		?>
		<input type='text' name='merlin_object_browser_settings[maximumDimension]' size="10" value='<?php echo $archive_maxDim; ?>'>
		<?php
		}
		else
		{
		?>
		Maximum dimension not available. Merlin SODA version = <?php echo self::_getSODAversion(); ?>. SODA needs to be updated, contact Merlin support.
		<input type='hidden' name='merlin_object_browser_settings[maximumDimension]' value=''>
		<?php
		}

	}//end merlin_object_browser_maxDim_render()


	/**
	 * Callback function for header text for admin form to set Merlin Object Browser settings
	 *
	 * @return void
	 */
	public function merlin_object_browser_settings_section_callback() {
		echo __( 'Settings to access the Merlin Archive', 'wordpress' );

	}//end merlin_object_browser_settings_section_callback()


	/**
	 * Admin form to set Merlin Object Browser settings
	 *
	 * @return void
	 */
	public function merlin_object_browser_options_page() {
		?>
		<form action='options.php' method='post'>
			<h2>Merlin Object Browser Settings</h2>
			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>
		</form>
		<?php

	}//end merlin_object_browser_options_page()


	/**
	 * Function for basic JSON format validation.
	 *
	 * @param string $string String to check.
	 *
	 * @return boolean true if string is in JSON format.
	 */
	private function _is_json( $string ) {
		json_decode( $string );
		return ( json_last_error() === JSON_ERROR_NONE );

	}//end _is_json()


	/**
	 * Function for basic field validation (present and neither empty nor only white space.
	 *
	 * @param string $string String to check.
	 *
	 * @return boolean true if string is null or empty string.
	 */
	private function _is_null_or_emptystring( $string ) {
		return ( false === isset( $string ) || '' === trim( $string ) );

	}//end _is_null_or_emptystring()


	/**
	 * Function to get the version of SODA on the Merlin server.
	 *
	 * @return string with version number if found or empty string.
	 */
	private function _getSODAversion() {
		$versionSODA = "";
		$options		 = get_option( 'merlin_object_browser_settings' );
		$archive_url = '';
		if ( true === isset( $options['archiveURL'] ) ) {
			$archive_url = $options['archiveURL'];
		}
		if ( false === self::_is_null_or_emptystring($archive_url) ) {
			if ( false != strpos($archive_url, 'mx') ) {
				$archive_url	= substr( $archive_url, 0, strpos($archive_url, 'mx') );
			}
			$url_SODA 	= trailingslashit( $archive_url ) . "soda";
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url_SODA);
			// Set so curl_exec returns the result instead of outputting it.
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			// Get the response and close the channel.
			$response = curl_exec($ch);
			if ( false != strpos( $response, "SODA Version " ) ) {
				$versionSODA = substr( $response, strpos( $response, "SODA Version " ) + 13);
				if ( false != strpos( $versionSODA, "<br" ) ) {
					$versionSODA = substr( $versionSODA, 0, strpos( $versionSODA, "<br" ) );
				}
			}
			curl_close($ch);
		}
		return $versionSODA;

	}//end _getSODAversion()


	/**
	 * Function to check if the SODA version allows downsampling parameter rather than resampling (>1.0.26).
	 *
	 * @return boolean true if SODA version is 1.0.27 or greater.
	 */
	private function _checkSODAversion() {
		$okToResample = false;
		$versionSODA = self::_getSODAversion();
		if ( false === self::_is_null_or_emptystring( $versionSODA ) ) {
			$versionArray = explode( ".", $versionSODA );
			if ( 1 < $versionArray[0] ) {
				$okToResample = true;
			}
			else if ( 0 < $versionArray[1] ) {
				$okToResample = true;
			}
			else if ( 26 < $versionArray[2] ) {
				$okToResample = true;
			}
		}
		return $okToResample;

	}//end _checkSODAversion()


}//end class
MOB::get_instance();
