<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocportalcom
 */

class Hook_addon_registry_ocportalcom
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
		return 'Development';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Chris Graham';
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
		return 'The ocPortal deployment platform.';
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
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'sources_custom/hooks/systems/addon_registry/ocportalcom.php',
			'adminzone/pages/modules_custom/admin_ocpusers.php',
			'adminzone/pages/minimodules_custom/make_ocportal_release.php',
			'sources_custom/hooks/systems/do_next_menus/ocportalcom.php',
			'data_custom/ocpcom_web_service.php',
			'lang_custom/EN/sites.ini',
			'lang_custom/EN/ocpcom.ini',
			'pages/minimodules_custom/licence.php',
			'site/pages/modules_custom/sites.php',
			'sources_custom/ocpcom.php',
			'sources_custom/hooks/systems/cron/site_cleanup.php',
			'uploads/website_specific/ocportal.com/myocp/template.sql',
			'uploads/website_specific/ocportal.com/myocp/template.tar',
			'data_custom/myocp_upgrade.php',
			'sources_custom/errorservice.php',
			'sources_custom/miniblocks/fp_animation.php',
			'sources_custom/miniblocks/ocpcom_featuretray.php',
			'sources_custom/miniblocks/ocpcom_make_upgrader.php',
			'sources_custom/miniblocks/ocpcom_new_tutorials.php',
			'themes/default/templates_custom/MO_NEW_WEBSITE.tpl',
			'themes/default/templates_custom/OC_DOWNLOAD_RELEASES.tpl',
			'themes/default/templates_custom/OC_DOWNLOAD_SCREEN.tpl',
			'themes/default/templates_custom/OC_HOSTING_COPY_SUCCESS_SCREEN.tpl',
			'themes/default/templates_custom/OC_SITE.tpl',
			'themes/default/templates_custom/OC_SITES_SCREEN.tpl',
			'uploads/website_specific/ocportal.com/.htaccess',
			'uploads/website_specific/ocportal.com/logos',
			'uploads/website_specific/ocportal.com/logos/a.png',
			'uploads/website_specific/ocportal.com/logos/b.png',
			'uploads/website_specific/ocportal.com/logos/choice.php',
			'uploads/website_specific/ocportal.com/logos/default.png',
			'uploads/website_specific/ocportal.com/logos/index.html',
			'uploads/website_specific/ocportal.com/scripts/addon_manifest.php',
			'uploads/website_specific/ocportal.com/scripts/errorservice.php',
			'uploads/website_specific/ocportal.com/scripts/fetch_release_details.php',
			'uploads/website_specific/ocportal.com/scripts/newsletter_join.php',
			'uploads/website_specific/ocportal.com/scripts/user.php',
			'uploads/website_specific/ocportal.com/scripts/version.php',
			'uploads/website_specific/ocportal.com/upgrades/make_upgrader.php',
			'uploads/website_specific/ocportal.com/upgrades/tarring.log',
		);
	}
}