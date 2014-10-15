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
 * @package		occle
 */

/**
 * Make the functions.dat file
 */
function make_functions_dat()
{
    if (!file_exists(get_custom_file_base() . '/data_custom')) {
        require_code('files2');
        make_missing_directory(get_custom_file_base() . '/data_custom');
    }

    $files = make_functions_dat_do_dir(get_custom_file_base());
    $classes = array();
    $global = array();
    foreach ($files as $filename) {
        if (strpos($filename,'_custom') !== false) {
            continue;
        }

        $_filename = substr($filename,strlen(get_custom_file_base())+1);
        if ($_filename == 'sources/minikernel.php') {
            continue;
        }
        $result = get_php_file_api($_filename,false);
        foreach ($result as $i => $r) {
            if ($r['name'] == '__global') {
                $global = array_merge($global,$r['functions']);
                unset($result[$i]);
            }
        }
        $classes = array_merge($classes,$result);
    }

    $classes['__global'] = array('functions' => $global);
    $myfile = @fopen(get_custom_file_base() . '/data_custom/functions.dat',GOOGLE_APPENGINE?'wb':'wt') or intelligent_write_error(get_custom_file_base() . '/data_custom/functions.dat');
    if (fwrite($myfile,serialize($classes)) == 0) {
        warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
    }
    fclose($myfile);
}

/**
 * Scan a directory for PHP files.
 *
 * @param  PATH		The directory
 * @param  boolean	Whether to skip custom files
 * @return array		Found files
 */
function make_functions_dat_do_dir($dir,$no_custom = false)
{
    $out = array();
    $_dir = ($dir == '')?'.':$dir;
    $dh = opendir($_dir);
    while (($file = readdir($dh)) !== false) {
        if ((strpos($file,'_custom') !== false) && ($no_custom)) {
            continue;
        }

        if ($file[0] != '.') {
            if (is_file($_dir . '/' . $file)) {
                if (substr($file,-4,4) == '.php') {
                    $path = $dir . (($dir != '')?'/':'') . $file;
                    $alt = str_replace('modules/','modules_custom/',str_replace('sources/','sources_custom/',$path));
                    if (($alt == $path) || (!file_exists($alt))) {
                        $out[] = $path;
                    }
                }
            } elseif (is_dir($_dir . '/' . $file)) {
                $out = array_merge($out,make_functions_dat_do_dir($dir . (($dir != '')?'/':'') . $file));
            }
        }
    }
    return $out;
}

/**
 * OcCLE command hook.
 */
class Hook_occle_command_find_function
{
    /**
	 * Run function for OcCLE hooks.
	 *
	 * @param  array	The options with which the command was called
	 * @param  array	The parameters with which the command was called
	 * @param  object	A reference to the OcCLE filesystem object
	 * @return array	Array of stdcommand, stdhtml, stdout, and stderr responses
	 */
    public function run($options,$parameters,&$occle_fs)
    {
        if ((array_key_exists('h',$options)) || (array_key_exists('help',$options))) {
            return array('',do_command_help('find_function',array('h'),array(true)),'','');
        } else {
            if (!array_key_exists(0,$parameters)) {
                return array('','','',do_lang('MISSING_PARAM','1','find_function'));
            }

            require_code('php');
            require_lang('phpdoc');
            $tpl = new ocp_tempcode();

            $contents = file_get_contents(get_custom_file_base() . '/data_custom/functions.dat');
            if ($contents == '') {
                make_functions_dat();
                $contents = file_get_contents(get_custom_file_base() . '/data_custom/functions.dat');
            }
            $_classes = unserialize($contents);
            foreach ($_classes as $class) {
                foreach ($class['functions'] as $function) {
                    if (strpos($function['name'],$parameters[0]) !== false) {
                        $ret = render_php_function($function,$class,true);
                        $tpl->attach($ret[0]);
                    }
                }
            }

            return array('',occle_make_normal_html_visible($tpl),'','');
        }
    }
}
