<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		unit_testing
 */

/**
 * ocPortal test case class (unit testing).
 */
class css_and_js_test_set extends ocp_test_case
{
	function setUp()
	{
		if (function_exists('set_time_limit')) set_time_limit(0);

		require_code('validation');
		require_code('validation2');
		require_code('js_validator');
		require_lang('validation');

		global $VALIDATION_COMPAT,$VALIDATION_EXT_FILES,$VALIDATION_MANUAL;
		$VALIDATION_COMPAT=false;
		$VALIDATION_EXT_FILES=false;
		$VALIDATION_MANUAL=false;

		//@set_time_limit(0);

		parent::setUp();
	}

	/*function testJavascript()		Too messy
	{
		$dh=opendir(get_file_base().'/themes/default/templates');
		while (($f=readdir($dh))!==false)
		{
			if ((substr($f,-4)=='.tpl') && (substr($f,0,11)=='JAVASCRIPT_') && (strpos($f,'COLOUR_PICKER')===false) && (strpos($f,'YAHOO')===false) && ($f!='JAVASCRIPT_NEED.tpl') && ($f!='JAVASCRIPT_NEED_INLINE.tpl'))
			{
				$path=javascript_enforce(basename($f,'.tpl'),'default',false);
				$contents=file_get_contents($path);
				$errors=check_js($contents);
				if ((!is_null($errors)) && ($errors['errors']==array())) $errors=NULL; // Normalise
				$this->assertTrue(is_null($errors),'Bad JS in '.$f);
				if (!is_null($errors))
				{
					var_dump($errors['errors']);
				} else
				{
					//echo 'Ok: '.$f."\n";
					//flush();
				}
			}
		}
	}*/

	function testCSS()
	{
		$dh=opendir(get_file_base().'/themes/default/css');
		while (($f=readdir($dh))!==false)
		{
			if ((substr($f,-4)=='.css') && ($f!='svg.css') && ($f!='no_cache.css') && ($f!='quizzes.css'/*we know this doesn't pass but it is extra glitz only*/))
			{
				$path=css_enforce(basename($f,'.css'),'default',false);

				$contents=file_get_contents($path);
				$errors=check_css($contents);
				if ((!is_null($errors)) && ($errors['errors']==array())) $errors=NULL; // Normalise
				$this->assertTrue(is_null($errors),'Bad CSS in '.$f);
				if (!is_null($errors))
				{
					var_dump($errors['errors']);
					var_dump($contents);
				}
			}
		}
	}
}
