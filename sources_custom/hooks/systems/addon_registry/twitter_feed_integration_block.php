<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		twitter_feed_integration_block
 */

class Hook_addon_registry_twitter_feed_integration_block
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
		return 'Jason Verhagen';
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
		return 'Common Public Attribution License';
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Integrate your Twitter feed into your web site, via a block. Full documentation at: http://ocportal.com/forum/topicview/misc/addons/twitter-feed_4.htm';
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
				'twitter_support',
				'PHP CuRL Extension',
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
			'sources_custom/hooks/systems/addon_registry/twitter_feed_integration_block.php',
			'lang_custom/EN/twitter_feed.ini',
			'sources_custom/blocks/twitter_feed.php',
			'themes/default/templates_custom/BLOCK_TWITTER_FEED.tpl',
			'themes/default/templates_custom/BLOCK_TWITTER_FEED_STYLE.tpl',
			'themes/default/images_custom/twitter_feed/bird_black_16.png',
			'themes/default/images_custom/twitter_feed/bird_black_32.png',
			'themes/default/images_custom/twitter_feed/bird_black_48.png',
			'themes/default/images_custom/twitter_feed/bird_blue_16.png',
			'themes/default/images_custom/twitter_feed/bird_blue_32.png',
			'themes/default/images_custom/twitter_feed/bird_blue_48.png',
			'themes/default/images_custom/twitter_feed/bird_gray_16.png',
			'themes/default/images_custom/twitter_feed/bird_gray_32.png',
			'themes/default/images_custom/twitter_feed/bird_gray_48.png',
			'themes/default/images_custom/twitter_feed/favorite.png',
			'themes/default/images_custom/twitter_feed/favorite_hover.png',
			'themes/default/images_custom/twitter_feed/favorite_on.png',
			'themes/default/images_custom/twitter_feed/index.html',
			'themes/default/images_custom/twitter_feed/reply.png',
			'themes/default/images_custom/twitter_feed/reply_hover.png',
			'themes/default/images_custom/twitter_feed/retweet.png',
			'themes/default/images_custom/twitter_feed/retweet_hover.png',
			'themes/default/images_custom/twitter_feed/retweet_on.png',
			'sources_custom/hooks/systems/config/twitterfeed_update_time.php',
			'sources_custom/hooks/systems/config/twitterfeed_use_twitter_support_config.php',
			'sources_custom/hooks/systems/config/channel_update_time.php',
		);
	}
}