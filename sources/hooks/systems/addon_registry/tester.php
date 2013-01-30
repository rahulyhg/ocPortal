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
 * @package		tester
 */

class Hook_addon_registry_tester
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
		return 'Organise and assign functional test sets, and mark their completion.';
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
			'conflicts_with'=>array()
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
			'sources/hooks/systems/config_default/bug_report_text.php',
			'sources/hooks/systems/config_default/tester_forum_name.php',
			'tester.css',
			'sources/hooks/systems/addon_registry/tester.php',
			'TESTER_ADD_SECTION_SCREEN.tpl',
			'TESTER_GO_SCREEN.tpl',
			'TESTER_GO_SECTION.tpl',
			'TESTER_GO_TEST.tpl',
			'TESTER_REPORT.tpl',
			'TESTER_STATISTICS_MEMBER.tpl',
			'TESTER_STATISTICS_SCREEN.tpl',
			'TESTER_TEST_GROUP.tpl',
			'TESTER_TEST_GROUP_NEW.tpl',
			'TESTER_TEST_SET.tpl',
			'lang/EN/tester.ini',
			'site/pages/modules/tester.php',
			'sources/hooks/systems/content_meta_aware/tester.php'
		);
	}


	/**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array						The mapping
	 */
	function tpl_previews()
	{
		return array(
			'TESTER_STATISTICS_MEMBER.tpl'=>'administrative__tester_statistics_screen',
			'TESTER_STATISTICS_SCREEN.tpl'=>'administrative__tester_statistics_screen',
			'TESTER_GO_SECTION.tpl'=>'administrative__tester_go_section',
			'TESTER_TEST_SET.tpl'=>'administrative__tester_go_screen',
			'TESTER_GO_TEST.tpl'=>'administrative__tester_go_screen',
			'TESTER_GO_SCREEN.tpl'=>'administrative__tester_go_screen',
			'TESTER_REPORT.tpl'=>'administrative__tester_report',
			'TESTER_TEST_GROUP_NEW.tpl'=>'administrative__tester_add_section_screen',
			'TESTER_ADD_SECTION_SCREEN.tpl'=>'administrative__tester_add_section_screen',
			'TESTER_TEST_GROUP.tpl'=>'administrative__tester_add_section_screen'
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__tester_statistics_screen()
	{
		$testers=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$testers->attach(do_lorem_template('TESTER_STATISTICS_MEMBER', array(
				'TESTER'=>lorem_word(),
				'NUM_TESTS'=>placeholder_number(),
				'NUM_TESTS_SUCCESSFUL'=>placeholder_number(),
				'NUM_TESTS_FAILED'=>placeholder_number(),
				'NUM_TESTS_INCOMPLETE'=>placeholder_number()
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('TESTER_STATISTICS_SCREEN', array(
				'TITLE'=>lorem_title(),
				'TESTERS'=>$testers,
				'NUM_TESTS'=>placeholder_number(),
				'NUM_TESTS_SUCCESSFUL'=>placeholder_number(),
				'NUM_TESTS_FAILED'=>placeholder_number(),
				'NUM_TESTS_INCOMPLETE'=>placeholder_number()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__tester_go_section()
	{
		return array(
			lorem_globalise(do_lorem_template('TESTER_GO_SECTION', array(
				'ID'=>placeholder_id(),
				'EDIT_TEST_SECTION_URL'=>placeholder_url(),
				'NOTES'=>lorem_phrase(),
				'SECTION'=>lorem_phrase(),
				'TESTS'=>placeholder_fields()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__tester_go_screen()
	{
		$sections=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$a_test=do_lorem_template('TESTER_TEST_SET', array(
				'TESTS'=>placeholder_array(),
				'T_TEST'=>lorem_word()
			));
			$tests=do_lorem_template('TESTER_GO_TEST', array(
				'BUG_REPORT_URL'=>placeholder_url(),
				'TEST'=>$a_test,
				'ID'=>strval($k),
				'VALUE'=>strval($k)
			));

			$sections->attach(do_lorem_template('TESTER_GO_SECTION', array(
				'ID'=>placeholder_id(),
				'EDIT_TEST_SECTION_URL'=>placeholder_url(),
				'NOTES'=>lorem_phrase(),
				'SECTION'=>lorem_phrase(),
				'TESTS'=>$tests
			)));
		}

		return array(
			lorem_globalise(do_lorem_template('TESTER_GO_SCREEN', array(
				'ADD_TEST_SECTION_URL'=>placeholder_url(),
				'SHOW_SUCCESSFUL'=>'true',
				'SHOW_FOR_ALL'=>'true',
				'TITLE'=>lorem_title(),
				'SECTIONS'=>$sections,
				'URL'=>placeholder_url()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__tester_report()
	{
		return array(
			lorem_globalise(do_lorem_template('TESTER_REPORT', array(
				'TITLE'=>lorem_title(),
				'TEST'=>lorem_phrase(),
				'COMMENTS'=>lorem_phrase()
			)), NULL, '', true)
		);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__tester_add_section_screen()
	{
		$tests=new ocp_tempcode();
		foreach (placeholder_array() as $k=>$v)
		{
			$tests->attach(do_lorem_template('TESTER_TEST_GROUP', array(
				'ID'=>'edit_' . strval($k),
				'FIELDS'=>placeholder_fields()
			)));
		}

		$add_template=do_lorem_template('TESTER_TEST_GROUP_NEW', array(
			'ID'=>lorem_word(),
			'FIELDS'=>placeholder_fields()
		));

		return array(
			lorem_globalise(do_lorem_template('TESTER_ADD_SECTION_SCREEN', array(
				'TITLE'=>lorem_title(),
				'SUBMIT_NAME'=>lorem_phrase(),
				'TESTS'=>$tests,
				'URL'=>placeholder_url(),
				'FIELDS'=>placeholder_fields(),
				'ADD_TEMPLATE'=>$add_template
			)), NULL, '', true)
		);
	}
}