<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		themewizard
 */

class Hook_themewizard_equation
{
    /**
	 * Run function for snippet hooks. Generates XHTML to insert into a page using AJAX.
	 *
	 * @return tempcode  The snippet
	 */
    public function run()
    {
        $theme = get_param('theme');
        $equation = get_param('css_equation');

        require_code('themewizard');

        $css_path = get_custom_file_base() . '/themes/' . filter_naughty($theme) . '/css_custom/global.css';
        if (!file_exists($css_path)) {
            $css_path = get_file_base() . '/themes/default/css/global.css';
        }
        $css_file_contents = file_get_contents($css_path);

        $seed = find_theme_seed($theme);
        $dark = (strpos($css_file_contents,',#000000,WB,') !== false);

        $colours = calculate_theme($seed,$theme,'equations','colours',$dark);
        $parsed_equation = parse_css_colour_expression($equation);
        if (is_null($parsed_equation)) {
            return make_string_tempcode('');
        }
        $answer = execute_css_colour_expression($parsed_equation,$colours[0]);
        if (is_null($answer)) {
            return make_string_tempcode('');
        }

        return make_string_tempcode('#' . $answer);
    }
}
