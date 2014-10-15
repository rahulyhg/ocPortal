<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		ocportalcom
 */

/**
 * Module page class.
 */
class Module_admin_ocpusers
{
    /**
	 * Find details of the module.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = true;
        return $info;
    }
    
    /**
	 * Uninstall the module.
	 */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('mayfeature');
        $GLOBALS['SITE_DB']->drop_table_if_exists('logged');
    }

    /**
	 * Install the module.
	 *
	 * @param  ?integer	 What version we're upgrading from (NULL: new install)
	 * @param  ?integer	 What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
    public function install($upgrade_from = null,$upgrade_from_hack = null)
    {
        $GLOBALS['SITE_DB']->create_table('mayfeature',array(
            'id' => '*AUTO',
            'url' => 'URLPATH'
        ));

        $GLOBALS['SITE_DB']->create_table('logged',array(
            'id' => '*AUTO',
            'website_url' => 'URLPATH',
            'website_name' => 'SHORT_TEXT',
            'is_registered' => 'BINARY',    // NOT CURRENTLY USED
            'log_key' => 'INTEGER',    // NOT CURRENTLY USED
            'expire' => 'INTEGER', // 0 means never	// NOT CURRENTLY USED
            'l_version' => 'ID_TEXT',
            'hittime' => 'TIME'
        ));
    }

    /**
	 * Find entry-points available within this module.
	 *
	 * @param  boolean	Whether to check permissions.
	 * @param  ?MEMBER	The member to check permissions as (NULL: current user).
	 * @param  boolean	Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
	 * @param  boolean	Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "misc" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
	 * @return ?array		A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (NULL: disabled).
	 */
    public function get_entry_points($check_perms = true,$member_id = null,$support_crosslinks = true,$be_deferential = false)
    {
        return array(
            'misc' => array('OC_SITES_INSTALLED','menu/_generic_admin/tool'),
        );
    }

    public $title;

    /**
	 * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
	 *
	 * @return ?tempcode		Tempcode indicating some kind of exceptional output (NULL: none).
	 */
    public function pre_run()
    {
        $type = get_param('type','misc');

        require_lang('ocportalcom');

        $this->title = get_screen_title('OC_SITES_INSTALLED');

        return NULL;
    }

    /**
	 * Execute the module.
	 *
	 * @return tempcode	 The result of execution.
	 */
    public function run()
    {
        require_code('ocportalcom');
        require_code('form_templates');

        $type = get_param('type','misc');
        if ($type == 'misc') {
            return $this->users();
        }
    }

    /**
	 * List of sites that have installed ocPortal.
	 *
	 * @return tempcode	 The result of execution.
	 */
    public function users()
    {
        $sortby = get_param('sortby','');

        $nameord = '';
        $acpord = '';
        $keyord = '';
        $versord = '';

        $orderby = get_param('orderby','desc');

        if ($orderby == 'desc') {
            if ($sortby == 'name') {
                $nameord = ':orderby=asc';
            } elseif ($sortby == 'acp') {
                $acpord = ':orderby=asc';
            } elseif ($sortby == 'vers') {
                $versord = ':orderby=asc';
            } elseif ($sortby == '') {
                $acpord = ':orderby=asc';
            }
        }

        $order_by = '';
        if ($sortby == 'name') {
            $order_by = 'ORDER BY website_name ';
        } elseif ($sortby == 'acp') {
            $order_by = 'ORDER BY hittime ';
        } elseif ($sortby == 'version') {
            $order_by = 'ORDER BY l_version ';
        } else {
            $order_by = 'ORDER BY hittime ';
        }

        $order_by .= ($orderby == 'desc')?'DESC':'ASC';

        $max = 500;
        if ($sortby != 'acp') {
            $order_by = 'GROUP BY website_url ' . $order_by;
        } else {
            $max = 1000;
        }

        $rows = $GLOBALS['SITE_DB']->query('SELECT * FROM ' . get_table_prefix() . 'logged WHERE website_url NOT LIKE \'%.myocp.com%\' AND website_name<>\'\' AND website_name<>\'(unnamed)\' ' . $order_by,$max);

        $seen_before = array();

        $_rows = new ocp_tempcode();
        foreach ($rows as $i => $r) {
            if (array_key_exists($r['website_url'],$seen_before)) {
                continue;
            }

            // Test that they give feature permission
            $url_parts = parse_url($r['website_url']);
            if (!array_key_exists('host',$url_parts)) {
                continue;
            }
            $perm = $GLOBALS['SITE_DB']->query_select_value_if_there('mayfeature','id',array('url' => $url_parts['scheme'] . '://' . $url_parts['host']));
            if ((is_null($perm)) && (get_param_integer('no_feature',0) == 1)) {
                continue;
            }

            $seen_before[$r['website_url']] = 1;

            $rt = array();
            $rt['VERSION'] = $r['l_version'];
            $rt['WEBSITE_URL'] = $r['website_url'];
            $rt['WEBSITE_NAME'] = $r['website_name'];
            $rt['LAST_ACP_ACCESS'] = integer_format(intval(round((time()-$r['hittime'])/60/60)));
            $rt['LAST_ACP_ACCESS_2'] = integer_format(intval(round((time()-$r['hittime'])/60/60/24)));
            if ($i<100) {
                $active = get_long_value_newer_than('testing__' . $r['website_url'] . '/_config.php',time()-60*60*10);
                if (is_null($active)) {
                    $test = http_download_file($r['website_url'] . '/_config.php',10,false,false,'Simple install stats',null,null,null,null,null,null,null,null,2.0);
                    if (!is_null($test)) {
                        $active = do_lang('YES');
                    } else {
                        $active = @strval($GLOBALS['HTTP_MESSAGE']);
                        if ($active == '') {
                            $active = do_lang('NO');
                        } else {
                            $active .= do_lang('OC_WHEN_CHECKING');
                        }
                    }
                    set_long_value('testing__' . $r['website_url'] . '/_config.php',$active);
                }
                $rt['OCP_ACTIVE'] = $active;
            } else {
                $rt['OCP_ACTIVE'] = do_lang('OC_CHECK_LIMIT');
            }

            $rt['NOTE'] = $perm?do_lang('OC_MAY_FEATURE'):do_lang('OC_KEEP_PRIVATE');

            $_rows->attach(do_template('OC_SITE',$rt));
        }

        return do_template('OC_SITES_SCREEN',array('_GUID' => '7f4b56c730f2b613994a3fe6f00ed525','TITLE' => $this->title,'ROWS' => $_rows,'NAMEORD' => $nameord,'ACPORD' => $acpord,'KEYORD' => $keyord,'VERORD' => $versord));
    }
}
