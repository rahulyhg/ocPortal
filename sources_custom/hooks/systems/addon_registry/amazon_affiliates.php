<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		amazon_affiliates
 */

class Hook_addon_registry_amazon_affiliates
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
		return 'Third Party Integration';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Kamen Blaginov';
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
		return 'An affiliate Amazon block.

The block can be configured by the block parameters (associates id, product line, subject/keywords, and how many items to be displayed inside the block).

This addon allows you to create a banner link to a number of banners from your Amazon affiliate account. You will need to know your Amazon affiliate ID as well as the Amazon category (Books, Music, Software etc) and the search term you want it to display banners from. An example of the block usage:
[code=\"Comcode\"][block associates_id=\"prehistoricsi-20\" title=\"Amazon Affiliates\" product_line=\"books\" subject_keywords=\"lord of the rings\" items_number=\"1\"]side_amazon_affiliate_sales[/block][/code]';
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
			'sources_custom/hooks/systems/addon_registry/amazon_affiliates.php',
			'lang_custom/EN/amazon.ini',
			'sources_custom/blocks/side_amazon_affiliate_sales.php',
			'themes/default/templates_custom/BLOCK_SIDE_AMAZON_AFFILIATES.tpl',
		);
	}
}