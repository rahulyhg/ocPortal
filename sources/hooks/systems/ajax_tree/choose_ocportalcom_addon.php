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
 * @package		core_addon_management
 */

class Hook_choose_ocportalcom_addon
{
    /**
	 * This will get the XML file from ocportal.com.
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root)
	 * @return string			The XML file
	 */
    public function get_file($id)
    {
        $stub = (get_param_integer('localhost',0) == 1)?get_base_url():'http://ocportal.com';
        $v = 'Version ' . float_to_raw_string(ocp_version_number(),1);
        if (!is_null($id)) {
            $v = $id;
        }
        $url = $stub . '/data/ajax_tree.php?hook=choose_download&id=' . rawurlencode($v) . '&file_type=tar';
        require_code('character_sets');
        $contents = http_download_file($url);
        $utf = ($GLOBALS['HTTP_CHARSET'] == 'utf-8'); // We have to use 'U' in the regexp to work around a Chrome parser bug (we can't rely on convert_to_internal_encoding being 100% correct)
        require_code('character_sets');
        $contents = convert_to_internal_encoding($contents);
        $contents = preg_replace('#^\s*\<' . '\?xml version="1.0" encoding="[^"]*"\?' . '\>\<request\>#' . ($utf?'U':''),'',$contents);
        $contents = preg_replace('#</request>#' . ($utf?'U':''),'',$contents);
        $contents = preg_replace('#<category [^>]*has_children="false"[^>]*>[^>]*</category>#' . ($utf?'U':''),'',$contents);
        $contents = preg_replace('#<category [^>]*title="Manual install required"[^>]*>[^>]*</category>#' . ($utf?'U':''),'',$contents);
        return $contents;
    }

    /**
	 * Run function for ajax-tree hooks. Generates XML for a tree list, which is interpreted by JavaScript and expanded on-demand (via new calls).
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root)
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @return string			XML in the special category,entry format
	 */
    public function run($id,$options,$default = null)
    {
        return $this->get_file($id);
    }

    /**
	 * Generate a simple selection list for the ajax-tree hook. Returns a normal <select> style <option>-list, for fallback purposes
	 *
	 * @param  ?ID_TEXT		The ID to do under (NULL: root) - not always supported
	 * @param  array			Options being passed through
	 * @param  ?ID_TEXT		The ID to select by default (NULL: none)
	 * @param  string			Prefix titles with this
	 * @return tempcode		The nice list
	 */
    public function simple($id,$options,$it = null,$prefix = '')
    {
        $file = $this->get_file($id);

        $list = new ocp_tempcode();
        if (is_null($id)) {// Root, needs an NA option
            $list->attach(form_input_list_entry('',false,do_lang_tempcode('NA_EM')));
        }

        $matches = array();

        $num_matches = preg_match_all('#<entry id="(\d+)"[^<>]* title="([^"]+)"#',$file,$matches);
        for ($i = 0;$i<$num_matches;$i++) {
            $list->attach(form_input_list_entry('http://ocportal.com/dload.php?id=' . $matches[1][$i],false,$prefix . $matches[2][$i]));
        }

        $num_matches = preg_match_all('#<category id="(\d+)" title="([^"]+)"#',$file,$matches);
        for ($i = 0;$i<$num_matches;$i++) {
            $list2 = $this->simple($matches[1][$i],$options,$it,$matches[2][$i] . ' > ');
            $list->attach($list2);
        }
        return $list;
    }
}
