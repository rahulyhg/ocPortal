<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core
 */

/**
 * Standard code module initialisation function.
 */
function init__lang_compile()
{
    global $DECACHED_COMCODE_LANG_STRINGS;
    $DECACHED_COMCODE_LANG_STRINGS = false;
}

/**
 * Load up a language file, compiling it (it's not cached yet).
 *
 * @param  ID_TEXT                      The language file name
 * @param  ?LANGUAGE_NAME               The language (NULL: uses the current language)
 * @param  ?string                      The language type (lang_custom, or custom) (NULL: normal priorities are used)
 * @set    lang_custom custom
 * @param  PATH                         Where we are cacheing too
 * @param  boolean                      Whether to just return if there was a loading error
 * @return boolean                      Whether we FAILED to load
 */
function require_lang_compile($codename, $lang, $type, $cache_path, $ignore_errors = false)
{
    global $LANGUAGE_STRINGS_CACHE, $REQUIRE_LANG_LOOP, $LANG_LOADED_LANG;

    $desire_cache = (function_exists('get_option')) && ((get_option('is_on_lang_cache') == '1') || (get_param_integer('keep_cache', 0) == 1) || (get_param_integer('cache', 0) == 1)) && (get_param_integer('keep_cache', null) !== 0) && (get_param_integer('cache', null) !== 0);
    if ($desire_cache) {
        if (!$GLOBALS['IN_MINIKERNEL_VERSION']) {
            global $DECACHED_COMCODE_LANG_STRINGS;

            // Cleanup language strings
            if (!$DECACHED_COMCODE_LANG_STRINGS) {
                $comcode_lang_strings = $GLOBALS['SITE_DB']->query('SELECT string_index FROM ' . get_table_prefix() . 'cached_comcode_pages WHERE ' . db_string_equal_to('the_zone', '') . ' AND the_page LIKE \'' . db_encode_like($codename . ':') . '\'');
                if (!is_null($comcode_lang_strings)) {
                    foreach ($comcode_lang_strings as $comcode_lang_string) {
                        $GLOBALS['SITE_DB']->query_delete('cached_comcode_pages', $comcode_lang_string);
                        delete_lang($comcode_lang_string['string_index']);
                    }
                }
                $DECACHED_COMCODE_LANG_STRINGS = true;
            }
        }

        $load_target = array();
    } else {
        $load_target = &$LANGUAGE_STRINGS_CACHE[$lang];
    }

    global $FILE_ARRAY;
    if ((@is_array($FILE_ARRAY)) && (file_array_exists('lang/' . $lang . '/' . $codename . '.ini'))) {
        $lang_file = 'lang/' . $lang . '/' . $codename . '.ini';
        $file = file_array_get($lang_file);
        _get_lang_file_map($file, $load_target, null, true);
        $bad = true;
    } else {
        $bad = true;
        $dirty = false;

        // Load originals
        $lang_file = get_file_base() . '/lang/' . $lang . '/' . filter_naughty($codename) . '.ini';
        if (file_exists($lang_file)) { // Non-custom, Proper language
            _get_lang_file_map($lang_file, $load_target, null, false);
            $bad = false;
        }

        // Load overrides now if they are there
        if ($type != 'lang') {
            $lang_file = get_custom_file_base() . '/lang_custom/' . $lang . '/' . $codename . '.ini';
            if ((!file_exists($lang_file)) && (get_file_base() != get_custom_file_base())) {
                $lang_file = get_file_base() . '/lang_custom/' . $lang . '/' . $codename . '.ini';
            }
            if (!file_exists($lang_file)) {
                $lang_file = get_custom_file_base() . '/lang_custom/' . $lang . '/' . $codename . '.po';
                if (!file_exists($lang_file)) {
                    $lang_file = get_file_base() . '/lang_custom/' . $lang . '/' . $codename . '-' . strtolower($lang) . '.po';
                }
            }
        }
        if (($type != 'lang') && (file_exists($lang_file))) {
            _get_lang_file_map($lang_file, $load_target, null, false);
            $bad = false;
            $dirty = true; // Tainted from the official pack, so can't store server wide
        }

        // NB: Merge op doesn't happen in require_lang. It happens when do_lang fails and then decides it has to force a recursion to do_lang(xx,fallback_lang()) which triggers require_lang(xx,fallback_lang()) when it sees it's not loaded

        if (($bad) && ($lang != fallback_lang())) { // Still some hope
            require_lang($codename, fallback_lang(), $type, $ignore_errors);
            $REQUIRE_LANG_LOOP--;
            $fallback_cache_path = get_custom_file_base() . '/caches/lang/' . fallback_lang() . '/' . $codename . '.lcd';
            if (file_exists($fallback_cache_path)) {
                require_code('files');
                @copy($fallback_cache_path, $cache_path);
                fix_permissions($cache_path);
            }

            if (!array_key_exists($lang, $LANG_LOADED_LANG)) {
                $LANG_LOADED_LANG[$lang] = array();
            }
            $LANG_LOADED_LANG[$lang][$codename] = 1;

            return $bad;
        }

        if ($bad) { // Out of hope
            if ($ignore_errors) {
                return true;
            }

            if (($codename != 'critical_error') || ($lang != get_site_default_lang())) {
                fatal_exit(do_lang_tempcode('MISSING_LANG_FILE', escape_html($codename), escape_html($lang)));
            } else {
                critical_error('CRIT_LANG');
            }
        }
    }

    if (is_null($GLOBALS['PERSISTENT_CACHE'])) {
        // Cache
        if ($desire_cache) {
            if (!file_exists(dirname($cache_path))) {
                require_code('files2');
                make_missing_directory(dirname($cache_path));
            }

            $file = @fopen($cache_path, GOOGLE_APPENGINE ? 'wb' : 'ab'); // Will fail if cache dir missing .. e.g. in quick installer
            if ($file !== false) {
                @flock($file, LOCK_EX);
                if (!GOOGLE_APPENGINE) {
                    ftruncate($file, 0);
                }
                if (fwrite($file, serialize($load_target)) > 0) {
                    // Success
                    @flock($file, LOCK_UN);
                    fclose($file);
                    require_code('files');
                    fix_permissions($cache_path);
                } else {
                    // Failure
                    @flock($file, LOCK_UN);
                    fclose($file);
                    @unlink($cache_path);
                }
            }
        }
    } else {
        persistent_cache_set(array('LANG', $lang, $codename), $load_target, !$dirty);
    }

    if ($desire_cache) {
        $LANGUAGE_STRINGS_CACHE[$lang] += $load_target;
    }

    return $bad;
}

/**
 * Get an array of all the INI language entries in the specified language.
 *
 * @param  LANGUAGE_NAME                The language
 * @param  ID_TEXT                      The language file
 * @param  boolean                      Force usage of original file
 * @return array                        The language entries
 */
function get_lang_file_map($lang, $file, $non_custom = false)
{
    $a = get_custom_file_base() . '/lang_custom/' . $lang . '/' . $file . '.ini';
    if (!file_exists($a)) {
        $a = get_custom_file_base() . '/lang_custom/' . $lang . '/' . $file . '.po';
        if (!file_exists($a)) {
            $a = get_custom_file_base() . '/lang_custom/' . $lang . '/' . $file . '-' . strtolower($lang) . '.po';
        }
    }
    if ((get_custom_file_base() != get_file_base()) && (!file_exists($a))) {
        $a = get_file_base() . '/lang_custom/' . $lang . '/' . $file . '.ini';
        if (!file_exists($a)) {
            $a = get_file_base() . '/lang_custom/' . $lang . '/' . $file . '.po';
            if (!file_exists($a)) {
                $a = get_file_base() . '/lang_custom/' . $lang . '/' . $file . '-' . strtolower($lang) . '.po';
            }
        }
    }

    if ((!file_exists($a)) || ($non_custom)) {
        $b = get_custom_file_base() . '/lang/' . $lang . '/' . $file . '.ini';
        if (!file_exists($b)) {
            $b = get_custom_file_base() . '/lang/' . $lang . '/' . $file . '.po';
            if (!file_exists($b)) {
                $b = get_custom_file_base() . '/lang/' . $lang . '/' . $file . '-' . strtolower($lang) . '.po';
            }
        }

        if (file_exists($b)) {
            $a = $b;
        } else {
            if ($non_custom) {
                return array();
            }
        }
    }

    $target = array();
    _get_lang_file_map($a, $target);
    return $target;
}

/**
 * Extend a language map from strings in a given language file.
 *
 * @param  PATH                         The path to the language file
 * @param  array                        The currently loaded language map
 * @param  ?boolean                     Whether to get descriptions rather than strings (NULL: no, but we might pick up some descriptions accidently)
 * @param  boolean                      Whether $b is infact not a path, but the actual file contents
 */
function _get_lang_file_map($b, &$entries, $descriptions = null, $given_whole_file = false)
{
    if (!$given_whole_file) {
        if (!file_exists($b)) {
            return;
        }

        $tmp = fopen($b, 'rb');
        @flock($tmp, LOCK_SH);
        $lines = file($b);
        @flock($tmp, LOCK_UN);
        fclose($tmp);
        if ($lines === null) {
            $lines = array();
        } // Workaround HHVM bug #1162
    } else {
        $lines = explode("\n", unixify_line_format($b));
    }

    if ((!$given_whole_file) && ($b[strlen($b) - 1] == 'o')) { // po file.
        // No description support btw (but shouldn't really be needed, once you save it will make a .ini and that does have description support)
        if ($descriptions === true) {
            return;
        }

        // Parse po file
        $matches = array();
        $doing = null;
        $value = '';
        $processing = false;
        foreach ($lines as $line) {
            if ($line == '') {
                continue;
            }

            if (($line[0] == '#') && (preg_match('/#: \[strings\](.*)/', $line, $matches) != 0)) {
                if ((!is_null($doing)) && ($value != '')) {
                    $entries[$doing] = $value;
                }
                $doing = $matches[1];
                $value = '';
                $processing = false;
            }
            if ($processing) {
                if ($line[0] == '"') {
                    $v = substr(rtrim($line, "\r\n"), 1);
                    if (substr($v, -1) == '"') {
                        $v = substr($v, 0, strlen($v) - 1);
                    }
                    $value .= stripslashes($v);
                } else {
                    $processing = false;
                    if ((!is_null($doing)) && ($value != '')) {
                        if (($doing == 'en_left') && ($value != 'left') && ($value != 'right')) {
                            $value = 'left';
                        }
                        if (($doing == 'en_right') && ($value != 'left') && ($value != 'right')) {
                            $value = 'right';
                        }
                        $entries[$doing] = $value;
                    }
                }
            }
            if (!$processing) {
                if (substr($line, 0, 8) == 'msgstr "') {
                    $processing = true;
                    $v = substr(rtrim($line, "\r\n"), 8);
                    if (substr($v, -1) == '"') {
                        $v = substr($v, 0, strlen($v) - 1);
                    }
                    $value .= stripslashes($v);
                }
            }
        }
        if ((!is_null($doing)) && ($value != '')) {
            $entries[$doing] = $value;
        }
        if (substr(basename($b), 0, 6) == 'global' || $given_whole_file) {
            $entries['charset'] = 'utf-8'; // Has to be
        }
        return;
    }

    // Parse ini file
    $in_lang = false;
    $nl = "\r\n";
    foreach ($lines as $line) {
        $line = rtrim($line, $nl);
        if ($line == '') {
            continue;
        }

        if ($line[0] == '[') {
            if ($line == '[strings]') {
                $in_lang = ($descriptions !== true);
            } elseif ($line == '[descriptions]') {
                $in_lang = ($descriptions === true);
            }
        }

        if ($in_lang) {
            $parts = explode('=', $line, 2);

            if (isset($parts[1])) {
                $entries[$parts[0]] = rtrim($parts[1], $nl);/*We do this at lookup-time now for performance reasons str_replace('\n',"\n",$parts[1]);*/
            }
        }
    }
}
