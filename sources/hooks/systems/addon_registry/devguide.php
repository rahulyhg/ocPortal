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
 * @package		devguide
 */

class Hook_addon_registry_devguide
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
		return 'For programmers - blocks and infrastructure to help turn the phpdoc code comments ocPortal uses into HTML pages.';
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

			'sources/hooks/systems/addon_registry/devguide.php',
			'BLOCK_MAIN_BLOCK_HELP.tpl',
			'BLOCK_MAIN_BLOCK_HELP_PARAMETER.tpl',
			'sources/blocks/main_block_help.php',
			'PHP_CLASS.tpl',
			'PHP_FILE.tpl',
			'PHP_FUNCTION.tpl',
			'PHP_FUNCTION_SUMMARY.tpl',
			'PHP_PARAMETER.tpl',
			'PHP_PARAMETER_BIT.tpl',
			'PHP_PARAMETER_LIST.tpl',
			'lang/EN/phpdoc.ini',
			'sources/blocks/main_code_documentor.php',
			'sources/php.php',
			'sources/phpstub.php',
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
				'PHP_PARAMETER_LIST.tpl'=>'administrative__php_function',
				'PHP_PARAMETER.tpl'=>'administrative__php_function',
				'PHP_FUNCTION.tpl'=>'administrative__php_function',
				'PHP_FUNCTION_SUMMARY.tpl'=>'administrative__php_function',
				'PHP_PARAMETER_BIT.tpl'=>'administrative__php_function',
				'PHP_CLASS.tpl'=>'administrative__php_function',
				'PHP_FILE.tpl'=>'administrative__php_function',
				'BLOCK_MAIN_BLOCK_HELP_PARAMETER.tpl'=>'administrative__block_main_block_help',
				'BLOCK_MAIN_BLOCK_HELP.tpl'=>'administrative__block_main_block_help',
				);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__administrative__php_function()
	{
		$parameters			=	new ocp_tempcode();
		$full_parameters	=	new ocp_tempcode();

		foreach (placeholder_array() as $value)
		{
			$parameters->attach(do_lorem_template('PHP_PARAMETER_LIST',array(
									'TYPE'=>lorem_word_2(),
									'NAME'=>lorem_word(),
										)
							));


			$bits = do_lorem_template('PHP_PARAMETER_BIT',array('NAME'=>do_lang_tempcode('NAME'),'VALUE'=>$value));

			$full_parameters->attach(do_lorem_template('PHP_PARAMETER',array(
											'BITS'=>$bits,
												)
									));
		}

		$classes	=	new ocp_tempcode();
		foreach (placeholder_array() as $k=>$value)
		{
			$function = do_lorem_template('PHP_FUNCTION',array(
						'FILENAME'=>lorem_phrase(),
						'CODE'=>lorem_phrase(),
						'RETURN_TYPE'=>lorem_phrase(),
						'FUNCTION'=>strval($k),
						'CLASS'=>lorem_word(),
						'PARAMETERS'=>$parameters,
						'DESCRIPTION'=>lorem_paragraph_html(),
						'FULL_PARAMETERS'=>$full_parameters,
						'RETURN'=>lorem_phrase(),
							)
				);


			$summary = do_lorem_template('PHP_FUNCTION_SUMMARY',array(
						'FILENAME'=>lorem_word_html(),
						'RETURN_TYPE'=>lorem_phrase(),
						'CLASS'=>lorem_word_2(),
						'FUNCTION'=>strval($k),
						'PARAMETERS'=>$parameters,
					));

			$classes->attach(do_lorem_template('PHP_CLASS',array(
					'CLASS_NAME'=>lorem_word().strval($k),
					'FUNCTION_SUMMARIES'=>$summary,
					'FUNCTIONS'=>$function,
						)
			));
		}

		return array(
			lorem_globalise(
				do_lorem_template('PHP_FILE',array(
					'FILENAME'=>lorem_word(),
					'CLASSES'=>$classes,
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
	function tpl_preview__administrative__block_main_block_help()
	{
		$parameters=new ocp_tempcode();

		foreach (placeholder_array() as $value)
		{
			$parameters->attach(do_lorem_template('BLOCK_MAIN_BLOCK_HELP_PARAMETER',array(
					'NAME'=>lorem_word(),
					'DESCRIPTION'=>lorem_paragraph(),
						)
			));
		}

		return array(
			lorem_globalise(
				do_lorem_template('BLOCK_MAIN_BLOCK_HELP',array(
					'NAME'=>lorem_word(),
					'DESCRIPTION'=>lorem_paragraph(),
					'USE'=>lorem_phrase(),
					'PARAMETERS'=>$parameters,
						)
			),NULL,'',true),
		);
	}
}
