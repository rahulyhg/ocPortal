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
 * Cache driver class.
 */
class Persistent_cacheing_eacceleratorcache
{
    public $objects_list = null;

    /**
     * Instruction to load up the objects list.
     *
     * @return array                    The list of objects
     */
    public function load_objects_list()
    {
        if (is_null($this->objects_list)) {
            if (function_exists('eaccelerator_get')) {
                $this->objects_list = eaccelerator_get(get_file_base() . 'PERSISTENT_CACHE_OBJECTS');
            }
            if (function_exists('mmcache_get')) {
                $this->objects_list = mmcache_get(get_file_base() . 'PERSISTENT_CACHE_OBJECTS');
            }
            if ($this->objects_list === null) {
                $this->objects_list = array();
            }
        }
        return $this->objects_list;
    }

    /**
     * Get data from the persistent cache.
     *
     * @param  string                   $key Key
     * @param  ?TIME                    $min_cache_date Minimum timestamp that entries from the cache may hold (null: don't care)
     * @return ?mixed                   The data (null: not found / NULL entry)
     */
    public function get($key, $min_cache_date = null)
    {
        if (function_exists('eaccelerator_get')) {
            $data = eaccelerator_get($key);
        } elseif (function_exists('mmcache_get')) {
            $data = mmcache_get($key);
        } else {
            $data = null;
        }
        if (is_null($data)) {
            return null;
        }
        if ((!is_null($min_cache_date)) && ($data[0] < $min_cache_date)) {
            return null;
        }
        return unserialize($data[1]);
    }

    /**
     * Put data into the persistent cache.
     *
     * @param  string                   $key Key
     * @param  mixed                    $data The data
     * @param  integer                  $flags Various flags (parameter not used)
     * @param  ?integer                 $expire_secs The expiration time in seconds (null: no expiry)
     */
    public function set($key, $data, $flags = 0, $expire_secs = null)
    {
        // Update list of persistent-objects
        $objects_list = $this->load_objects_list();
        if (!array_key_exists($key, $objects_list)) {
            $objects_list[$key] = true;
            if (function_exists('eaccelerator_put')) {
                eaccelerator_put(get_file_base() . 'PERSISTENT_CACHE_OBJECTS', $objects_list, 0);
            } elseif (function_exists('mmcache_put')) {
                mmcache_put(get_file_base() . 'PERSISTENT_CACHE_OBJECTS', $objects_list, 0);
            }
        }

        if (function_exists('eaccelerator_put')) {
            eaccelerator_put($key, array(time(), serialize($data)), $expire_secs);
        } elseif (function_exists('mmcache_put')) {
            mmcache_put($key, array(time(), serialize($data)), $expire_secs);
        }
    }

    /**
     * Delete data from the persistent cache.
     *
     * @param  string                   $key Key
     */
    public function delete($key)
    {
        // Update list of persistent-objects
        $objects_list = $this->load_objects_list();
        unset($objects_list[$key]);
        if (function_exists('eaccelerator_put')) {
            eaccelerator_put(get_file_base() . 'PERSISTENT_CACHE_OBJECTS', $objects_list, 0);
        } elseif (function_exists('mmcache_put')) {
            mmcache_put(get_file_base() . 'PERSISTENT_CACHE_OBJECTS', $objects_list, 0);
        }

        if (function_exists('eaccelerator_rm')) {
            eaccelerator_rm($key);
        } elseif (function_exists('mmcache_rm')) {
            mmcache_rm($key);
        }
    }

    /**
     * Remove all data from the persistent cache.
     */
    public function flush()
    {
        $objects_list = $this->load_objects_list();
        if (function_exists('eaccelerator_rm')) {
            foreach (array_keys($objects_list) as $obkey) {
                eaccelerator_rm($obkey);
            }
        } elseif (function_exists('mmcache_rm')) {
            foreach (array_keys($objects_list) as $obkey) {
                mmcache_rm($obkey);
            }
        }

        $objects_list = array();
        if (function_exists('eaccelerator_put')) {
            eaccelerator_put(get_file_base() . 'PERSISTENT_CACHE_OBJECTS', $objects_list, 0);
        } elseif (function_exists('mmcache_put')) {
            mmcache_put(get_file_base() . 'PERSISTENT_CACHE_OBJECTS', $objects_list, 0);
        }
    }
}
