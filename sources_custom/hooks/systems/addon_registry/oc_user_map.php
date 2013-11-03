<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		oc_user_map
 */

class Hook_addon_registry_oc_user_map
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the addon category
	 *
	 * @return string			The category
	 */
	function get_category()
	{
		return 'Information Display';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'temp1024 / Chris Graham / Kamen Blaginov';
	}

	/**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
	function get_copyright_attribution()
	{
		return array();
	}

	/**
	 * Get the addon licence (one-line summary only)
	 *
	 * @return string			The licence
	 */
	function get_licence()
	{
		return 'Licensed on the same terms as ocPortal';
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'A google map with markers of users locations.

The addon adds extra custom profile fields to store members coordinates to store their latitude and logitude. The addon can automatically populate the members when members visit the block page (only supported by browsers that support the HTML 5 Location API, e.g. Firefox). Members can edit their locations in their profile.

Coordinates of the Google map centre point and zoom level are configurable. You can find the coordinates by using the option in Google Maps Labs or via http://itouchmap.com/latlong.html.

Parameters:
 - Title -- The Name of the block which will appear on screen for example Store Locater.
 - Description -- a Description of the block.
 - Width -- Defaults to 100% of the column.
 - Height -- Defaults to 300px but can be set to how ever many pixels (px) you need it to be.
 - Zoom -- A number between 1 and 17, the higher the number the more zoomed in the map will start at.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
		);
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(
				'OCF',
			),
			'recommends'=>array(
			),
			'conflicts_with'=>array(
			)
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources_custom/hooks/systems/addon_registry/oc_user_map.php',
			'lang_custom/EN/google_map_users.ini',
			'sources_custom/blocks/main_google_map_users.php',
			'themes/default/templates_custom/BLOCK_MAIN_GOOGLE_MAP_USERS.tpl',
			'data_custom/set_coordinates.php',
			'sources_custom/hooks/systems/ocf_cpf_filter/latitude.php',
			'data_custom/get_member_tooltip.php',
		);
	}
}