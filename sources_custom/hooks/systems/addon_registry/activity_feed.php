<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		activity_feed
 */

class Hook_addon_registry_activity_feed
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
		return 'New Features';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Chris Warburton / Chris Graham / Paul / Naveen';
	}

	/**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
	function get_copyright_attribution()
	{
		return array(
			'JAVASCRIPT_BASE64.tpl is from http://www.webtoolkit.info'
		);
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
		return 'Displays a self-updating feed of logged site activity, with options to filter the contents. Also includes a block for entering new activities directly into the feed, allowing a \"status update\" functionality.

These blocks are put on the member profile tabs by default, but may also be called up on other areas of the site.

If the chat addon is installed, \"status\" posts can be restricted to only show for buddies.

If the Facebook of Twitter addons are installed then the system can syndicate out activities to the user\'s Twitter and Facebook followers.

The blocks provided are [tt]main_activities[/tt] and the status entry box is called [tt]main_activities_state[/tt].

[code=\"Comcode\"][block=\"Goings On\" max=\"20\" grow=\"0\" mode=\"all\"]main_activities[/block][/code]
...will show a feed with a title \"Goings On\" containing the last 20 activities, old activities will \"fall off the bottom\" (grow=\"0\") as new ones are loaded via AJAX and there is no filtering on what is shown. (mode=\"all\").

[code=\"Comcode\"][block=\"Say Something\"]main_activities_state[/block][/code]
...will show a status update box with the title \"Say Something\".';
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
			),
			'recommends'=>array(
			),
			'conflicts_with'=>array(
			)
		);
	}

	/**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
	function get_default_icon()
	{
		return 'themes/default/images_custom/icons/48x48/tabs/member_account/activity.png';
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images_custom/icons/24x24/tabs/member_account/activity.png',
			'themes/default/images_custom/icons/48x48/tabs/member_account/activity.png',
			'sources_custom/hooks/systems/addon_registry/activity_feed.php',
			'sources_custom/hooks/systems/activities/.htaccess',
			'sources_custom/hooks/systems/notifications/activity.php',
			'sources_custom/hooks/systems/rss/activities.php',
			'data_custom/activities_updater.php',
			'data_custom/activities_removal.php',
			'data_custom/activities_handler.php',
			'data_custom/latest_activity.txt',
			'lang_custom/EN/activities.ini',
			'sources_custom/blocks/main_activities_state.php',
			'sources_custom/blocks/main_activities.php',
			'sources_custom/activities_submission.php',
			'sources_custom/hooks/systems/activities/activities.php',
			'themes/default/templates_custom/JAVASCRIPT_BASE64.tpl',
			'themes/default/templates_custom/JAVASCRIPT_JQUERY.tpl',
			'themes/default/templates_custom/JAVASCRIPT_ACTIVITIES_STATE.tpl',
			'themes/default/templates_custom/BLOCK_MAIN_ACTIVITIES_STATE.tpl',
			'themes/default/templates_custom/BLOCK_MAIN_ACTIVITIES.tpl',
			'themes/default/templates_custom/BLOCK_MAIN_ACTIVITIES_XML.tpl',
			'themes/default/templates_custom/JAVASCRIPT_ACTIVITIES.tpl',
			'themes/default/templates_custom/ACTIVITY.tpl',
			'themes/default/templates_custom/OCF_MEMBER_PROFILE_ACTIVITIES.tpl',
			'themes/default/images_custom/stop12.png',
			'themes/default/images_custom/stop12_hover.png',
			'themes/default/images_custom/stop12_active.png',
			'themes/default/css_custom/activities.css',
			'sources_custom/hooks/systems/syndication/index.html',
			'sources_custom/hooks/systems/profiles_tabs/activities.php',
			'sources_custom/hooks/systems/profiles_tabs/.htaccess',
			'sources_custom/hooks/systems/profiles_tabs/index.html',
			'sources_custom/hooks/systems/profiles_tabs/posts.php',
			'uploads/addon_avatar_normalise/index.html',
			'uploads/addon_icon_normalise/index.html',
			'sources_custom/activities.php',
		);
	}
}