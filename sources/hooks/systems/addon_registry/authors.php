<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		authors
 */

class Hook_addon_registry_authors
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
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Certain kinds of content can have authors instead of submitters (e.g. "ocProducts"). The authors may be independently described and searched under.';
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(),
			'recommends'=>array(),
			'conflicts_with'=>array(),
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

			'sources/hooks/systems/meta/authors.php',
			'sources/hooks/systems/addon_registry/authors.php',
			'AUTHOR_MANAGE_SCREEN.tpl',
			'AUTHOR_SCREEN.tpl',
			'AUTHOR_POPUP_WINDOW_DEFINED.tpl',
			'AUTHOR_POPUP_WINDOW_UNDEFINED.tpl',
			'AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY.tpl',
			'data/authors.php',
			'themes/default/images/bigicons/authors.png',
			'themes/default/images/pagepics/authors.png',
			'themes/default/images/pagepics/setauthorprofile.png',
			'cms/pages/modules/cms_authors.php',
			'lang/EN/authors.ini',
			'site/pages/modules/authors.php',
			'sources/authors.php',
			'sources/hooks/systems/do_next_menus/authors.php',
			'sources/hooks/systems/awards/author.php',
			'sources/hooks/systems/rss/authors.php',
			'themes/default/images/bigicons/set-own-profile.png',
		);
	}

	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array			The mapping
	 */
	function tpl_previews()
	{
		return array(
			'AUTHOR_MANAGE_SCREEN.tpl'=>'author_manage_screen',
			'AUTHOR_SCREEN.tpl'=>'author_screen',
			'AUTHOR_POPUP_WINDOW_DEFINED.tpl'=>'author_popup_window',
			'AUTHOR_POPUP_WINDOW_UNDEFINED.tpl'=>'author_popup_window',
			'AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY.tpl'=>'author_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__author_manage_screen()
	{
		require_lang('global');
		require_lang('authors');
		return array(
			lorem_globalise(
				do_lorem_template('AUTHOR_MANAGE_SCREEN',array(
					'TITLE'=>lorem_title(),
					'DEFINE_FORM'=>placeholder_form(),
					'MERGE_FORM'=>placeholder_form(),
				)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__author_screen()
	{
		require_lang('authors');

		$news_released = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$tpl = do_lorem_template('NEWS_BRIEF',array('DATE'=>placeholder_time(),'URL'=>placeholder_url(),'TITLE_PLAIN'=>lorem_word(),'ID'=>placeholder_id(),'TITLE'=>lorem_word()));
			$news_released->attach($tpl);
		}

		$downloads_released = new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			if ($k!=0) $downloads_released->attach(do_lorem_template('BLOCK_SEPARATOR',array()));

			$downloads_released->attach(lorem_sentence_html());
		}

		$staff_details = do_lorem_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink(placeholder_url(),do_lang_tempcode('DEFINE_AUTHOR'),false)));

		$point_details = do_lorem_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink(placeholder_url(),do_lang_tempcode('AUTHOR_POINTS'))));

		$url_details = do_lorem_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink(placeholder_url(),do_lang_tempcode('AUTHOR_HOMEPAGE'))));

		$search_details = do_lorem_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink(placeholder_url(),do_lang_tempcode('SEARCH'))));

		$forum_details = do_lorem_template('AUTHOR_SCREEN_POTENTIAL_ACTION_ENTRY',array('ACTION'=>hyperlink(placeholder_url(),do_lang_tempcode('AUTHOR_PROFILE'))));

		$skills = new ocp_tempcode();
		$description = new ocp_tempcode();

		return array(
			lorem_globalise(
				do_lorem_template('AUTHOR_SCREEN',array(
					'TAGS'=>lorem_word_html(),
					'TITLE'=>lorem_title(),
					'EDIT_URL'=>placeholder_url(),
					'AUTHOR'=>lorem_phrase(),
					'NEWS_RELEASED'=>$news_released,
					'DOWNLOADS_RELEASED'=>$downloads_released,
					'STAFF_DETAILS'=>$staff_details,
					'POINT_DETAILS'=>$point_details,
					'URL_DETAILS'=>$url_details,
					'SEARCH_DETAILS'=>$search_details,
					'FORUM_DETAILS'=>$forum_details,
					'SKILLS'=>$skills,
					'DESCRIPTION'=>$description
						)
			),NULL,'',true),
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__author_popup_window()
	{
		require_lang('authors');

		$out=new ocp_tempcode();
		$out->attach(do_lorem_template('AUTHOR_POPUP_WINDOW_DEFINED',array(
			'AUTHOR'=>lorem_phrase(),
			'FIELD_NAME'=>lorem_word(),
				)));
		$out->attach(do_lorem_template('AUTHOR_POPUP_WINDOW_UNDEFINED',array(
			'AUTHOR'=>lorem_phrase(),
			'FIELD_NAME'=>lorem_word(),
				)));

		return array(
			lorem_globalise(
				$out
			,NULL,'',true),
		);
	}
}