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
 * @package		captcha
 */

class Hook_addon_registry_captcha
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
		return 'Stop spam-bots from performing actions on the website.';
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
			'previously_in_addon'=>array('core_captcha'),
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

			'sources/hooks/systems/snippets/captcha_wrong.php',
			'sources/hooks/systems/addon_registry/captcha.php',
			'FORM_SCREEN_INPUT_CAPTCHA.tpl',
			'data/securityimage.php',
			'sources/captcha.php',
			'lang/EN/captcha.ini',
			'data/sounds/0.wav',
			'data/sounds/1.wav',
			'data/sounds/2.wav',
			'data/sounds/3.wav',
			'data/sounds/4.wav',
			'data/sounds/5.wav',
			'data/sounds/6.wav',
			'data/sounds/7.wav',
			'data/sounds/8.wav',
			'data/sounds/9.wav',
			'data/sounds/a.wav',
			'data/sounds/b.wav',
			'data/sounds/c.wav',
			'data/sounds/d.wav',
			'data/sounds/e.wav',
			'data/sounds/f.wav',
			'data/sounds/g.wav',
			'data/sounds/h.wav',
			'data/sounds/i.wav',
			'data/sounds/j.wav',
			'data/sounds/k.wav',
			'data/sounds/l.wav',
			'data/sounds/m.wav',
			'data/sounds/n.wav',
			'data/sounds/o.wav',
			'data/sounds/p.wav',
			'data/sounds/q.wav',
			'data/sounds/r.wav',
			'data/sounds/s.wav',
			'data/sounds/t.wav',
			'data/sounds/u.wav',
			'data/sounds/v.wav',
			'data/sounds/w.wav',
			'data/sounds/x.wav',
			'data/sounds/y.wav',
			'data/sounds/z.wav',
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
				'FORM_SCREEN_INPUT_CAPTCHA.tpl'=>'form_screen_input_captcha',
				);
	}

	/**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/Javascript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
	function tpl_preview__form_screen_input_captcha()
	{
		require_code('captcha');
		generate_captcha();

		$input = do_lorem_template('FORM_SCREEN_INPUT_CAPTCHA',array('TABINDEX'=>placeholder_number()));
		$captcha = do_lorem_template('FORM_SCREEN_FIELD',array('REQUIRED'=>true,'SKIP_LABEL'=>false,'BORING_NAME'=>'security_image','NAME'=>lorem_phrase(),'DESCRIPTION'=>lorem_sentence_html(),'DESCRIPTION_SIDE'=>'','INPUT'=>$input,'COMCODE'=>''));

		return array(
			lorem_globalise(
				do_lorem_template('FORM_SCREEN',array(
					'SKIP_VALIDATION'=>true,
					'HIDDEN'=>'',
					'TITLE'=>lorem_title(),
					'URL'=>placeholder_url(),
					'FIELDS'=>$captcha,
					'SUBMIT_NAME'=>lorem_word(),
					'TEXT'=>lorem_sentence_html(),
						)
			),NULL,'',true),
		);
	}
}
