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
 * @package		core_validation
 */

/*
NB: This is for Web Standards validation, not Content validation.
*/

class Hook_addon_registry_core_validation
{
    /**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
    public function get_chmod_array()
    {
        return array();
    }

    /**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
    public function get_version()
    {
        return ocp_version_number();
    }

    /**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
    public function get_description()
    {
        return 'Web Standards validation tools.';
    }

    /**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_accessibility',
            'tut_markup',
        );
    }

    /**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
    public function get_dependencies()
    {
        return array(
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array(),
        );
    }

    /**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
    public function get_default_icon()
    {
        return 'themes/default/images/icons/48x48/menu/_generic_admin/component.png';
    }

    /**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
    public function get_file_list()
    {
        return array(
            'themes/default/css/validation.css',
            'sources/hooks/systems/addon_registry/core_validation.php',
            'themes/default/templates/VALIDATE_ATTRIBUTE_END.tpl',
            'themes/default/templates/VALIDATE_ATTRIBUTE_START.tpl',
            'themes/default/templates/VALIDATE_ERROR.tpl',
            'themes/default/templates/VALIDATE_ERROR_SCREEN.tpl',
            'themes/default/templates/VALIDATE_LINE.tpl',
            'themes/default/templates/VALIDATE_LINE_END.tpl',
            'themes/default/templates/VALIDATE_LINE_ERROR.tpl',
            'themes/default/templates/VALIDATE_MARKER_END.tpl',
            'themes/default/templates/VALIDATE_MARKER_START.tpl',
            'themes/default/templates/VALIDATE_SCREEN.tpl',
            'themes/default/templates/VALIDATE_SCREEN_END.tpl',
            'themes/default/templates/VALIDATE_TAG_END.tpl',
            'themes/default/templates/VALIDATE_TAG_NAME_END.tpl',
            'themes/default/templates/VALIDATE_TAG_NAME_START.tpl',
            'themes/default/templates/VALIDATE_TAG_START.tpl',
            'themes/default/templates/VALIDATE_MARKER.tpl',
            'sources/js_lex.php',
            'sources/js_parse.php',
            'sources/js_validator.php',
            'lang/EN/validation.ini',
            'sources/validation.php',
            'sources/validation2.php',
            'sources/hooks/systems/config/validation.php',
            'sources/hooks/systems/config/validation_compat.php',
            'sources/hooks/systems/config/validation_css.php',
            'sources/hooks/systems/config/validation_ext_files.php',
            'sources/hooks/systems/config/validation_javascript.php',
            'sources/hooks/systems/config/validation_wcag.php',
            'sources/hooks/systems/config/validation_xhtml.php',
        );
    }


    /**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array			The mapping
	 */
    public function tpl_previews()
    {
        return array(
            'VALIDATE_SCREEN.tpl' => 'administrative__validate',
            'VALIDATE_TAG_START.tpl' => 'administrative__validate',
            'VALIDATE_TAG_NAME_START.tpl' => 'administrative__validate',
            'VALIDATE_LINE_END.tpl' => 'administrative__validate',
            'VALIDATE_LINE.tpl' => 'administrative__validate',
            'VALIDATE_TAG_END.tpl' => 'administrative__validate',
            'VALIDATE_TAG_NAME_END.tpl' => 'administrative__validate',
            'VALIDATE_SCREEN_END.tpl' => 'administrative__validate',
            'VALIDATE_ERROR.tpl' => 'administrative__validate_error_screen',
            'VALIDATE_ERROR_SCREEN.tpl' => 'administrative__validate_error_screen',
            'VALIDATE_ATTRIBUTE_START.tpl' => 'administrative__validate_error_screen',
            'VALIDATE_ATTRIBUTE_END.tpl' => 'administrative__validate_error_screen',
            'VALIDATE_MARKER.tpl' => 'administrative__validate_error_screen',
            'VALIDATE_LINE_ERROR.tpl' => 'administrative__validate_error_screen',
            'VALIDATE_MARKER_START.tpl' => 'administrative__validate',
            'VALIDATE_MARKER_END.tpl' => 'administrative__validate'
        );
    }

    /**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
    public function tpl_preview__administrative__validate()
    {
        $display = new ocp_tempcode();
        $display->attach(do_lorem_template('VALIDATE_SCREEN',array(
            'MSG' => lorem_phrase(),
            'RETURN_URL' => placeholder_url(),
            'TITLE' => lorem_title(),
            'MESSY_URL' => placeholder_url(),
            'RET' => lorem_phrase(),
        )));

        $display->attach(do_lorem_template('VALIDATE_LINE',array(
            'NUMBER' => placeholder_number(),
        )));

        $display->attach(do_lorem_template('VALIDATE_TAG_START',array(
            'COLOUR' => '#b7b7b7',
        )));
        $display->attach(lorem_word());
        $display->attach(do_lorem_template('VALIDATE_TAG_END',array()));

        $display->attach(do_lorem_template('VALIDATE_TAG_NAME_START',array()));
        $display->attach(lorem_word());
        $display->attach(do_lorem_template('VALIDATE_TAG_NAME_END',array()));

        $display->attach(do_lorem_template('VALIDATE_MARKER_START',array()));
        $display->attach(lorem_word());
        $display->attach(do_lorem_template('VALIDATE_MARKER_END',array()));

        $display->attach(do_lorem_template('VALIDATE_LINE_END',array()));

        $display->attach(do_lorem_template('VALIDATE_SCREEN_END',array()));

        return array(
            lorem_globalise($display,null,'',true)
        );
    }

    /**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
	 *
	 * @return array			Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
    public function tpl_preview__administrative__validate_error_screen()
    {
        $errors = new ocp_tempcode();
        $display = new ocp_tempcode();
        foreach (placeholder_array() as $key => $_error) {
            $errors->attach(do_lorem_template('VALIDATE_ERROR',array(
                'I' => lorem_word() . strval($key),
                'LINE' => placeholder_number(),
                'POS' => placeholder_number(),
                'ERROR' => $_error,
            )));
        }

        $display->attach(do_lorem_template('VALIDATE_ERROR_SCREEN',array(
            'MSG' => lorem_phrase(),
            'RETURN_URL' => placeholder_url(),
            'TITLE' => lorem_title(),
            'IGNORE_URL_2' => placeholder_url(),
            'IGNORE_URL' => placeholder_url(),
            'MESSY_URL' => placeholder_url(),
            'ERRORS' => $errors,
            'RET' => lorem_phrase(),
        )));

        $markers = new ocp_tempcode();
        foreach (placeholder_array() as $key => $_error) {
            $markers->attach(do_lorem_template('VALIDATE_MARKER',array(
                'I' => lorem_word() . strval($key),
                'ERROR' => $_error,
            )));
        }
        $display->attach(do_lorem_template('VALIDATE_LINE_ERROR',array(
            'MARKERS' => $markers,
            'NUMBER' => placeholder_number(),
        )));
        $display->attach(do_lorem_template('VALIDATE_ATTRIBUTE_START',array()));
        $display->attach(lorem_word());
        $display->attach(do_lorem_template('VALIDATE_ATTRIBUTE_END',array()));
        $display->attach(do_lorem_template('VALIDATE_LINE_END',array()));
        $display->attach(lorem_phrase());

        $display->attach(do_lorem_template('VALIDATE_LINE',array(
            'NUMBER' => placeholder_number(),
        )));
        $display->attach(do_lorem_template('VALIDATE_LINE_END',array()));

        $display->attach(do_lorem_template('VALIDATE_SCREEN_END',array()));

        return array(
            lorem_globalise($display,null,'',true)
        );
    }
}
