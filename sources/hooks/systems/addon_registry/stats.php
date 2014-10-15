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
 * @package		stats
 */

class Hook_addon_registry_stats
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
        return 'Show advanced graphs (analytics) and dumps of raw data relating to your website activity.';
    }

    /**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
    public function get_applicable_tutorials()
    {
        return array(
            'tut_statistics',
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
        return 'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/statistics.png';
    }

    /**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
    public function get_file_list()
    {
        return array(
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/statistics.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/statistics.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/clear_stats.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/geolocate.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/load_times.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/page_views.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/submits.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/top_keywords.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/top_referrers.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/users_online.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/clear_stats.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/geolocate.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/load_times.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/page_views.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/submits.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/top_keywords.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/top_referrers.png',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/users_online.png',
            'themes/default/images/icons/24x24/menu/adminzone/audit/statistics/index.html',
            'themes/default/images/icons/48x48/menu/adminzone/audit/statistics/index.html',
            'sources/hooks/modules/admin_setupwizard/stats.php',
            'sources/hooks/systems/config/stats_store_time.php',
            'sources/hooks/systems/config/super_logging.php',
            'sources/hooks/systems/realtime_rain/stats.php',
            'data/modules/admin_cleanup/page_stats.php.pre',
            'sources/hooks/systems/cleanup/page_stats.php',
            'sources/hooks/systems/cron/stats_clean.php',
            'sources/hooks/systems/page_groupings/stats.php',
            'sources/hooks/systems/non_active_urls/stats.php',
            'sources/hooks/systems/addon_registry/stats.php',
            'sources/hooks/modules/admin_import_types/stats.php',
            'sources/hooks/modules/admin_stats/.htaccess',
            'sources/hooks/modules/admin_stats/index.html',
            'themes/default/templates/STATS_GRAPH.tpl',
            'themes/default/templates/STATS_SCREEN.tpl',
            'themes/default/templates/STATS_SCREEN_ISCREEN.tpl',
            'themes/default/templates/STATS_OVERVIEW_SCREEN.tpl',
            'adminzone/pages/modules/admin_stats.php',
            'themes/default/css/stats.css',
            'themes/default/css/svg.css',
            'data/modules/admin_stats/.htaccess',
            'data/modules/admin_stats/index.html',
            'data/modules/admin_stats/IP_Country.txt', // http://geolite.maxmind.com/download/geoip/database/
            'data_custom/modules/admin_stats/index.html',
            'lang/EN/stats.ini',
            'sources/hooks/systems/cleanup/stats.php',
            'sources/svg.php',
            'sources/hooks/systems/config/bot_stats.php',
        );
    }


    /**
	 * Get mapping between template names and the method of this class that can render a preview of them
	 *
	 * @return array						The mapping
	 */
    public function tpl_previews()
    {
        return array(
            'STATS_GRAPH.tpl' => 'administrative__stats_screen',
            'STATS_SCREEN.tpl' => 'administrative__stats_screen',
            'STATS_OVERVIEW_SCREEN.tpl' => 'administrative__stats_screen_overview',
            'STATS_SCREEN_ISCREEN.tpl' => 'administrative__stats_screen_iscreen'
        );
    }


    /**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
    public function tpl_preview__administrative__stats_screen()
    {
        $graph = do_lorem_template('STATS_GRAPH',array(
            'GRAPH' => placeholder_url(),
            'TITLE' => lorem_phrase(),
            'TEXT' => lorem_sentence(),
            'KEYWORDS_SHARE' => lorem_word(),
            'DESCRIPTION_KEYWORDS_SHARE' => lorem_word(),
        ));

        return array(
            lorem_globalise(do_lorem_template('STATS_SCREEN',array(
                'TITLE' => lorem_title(),
                'GRAPH' => $graph,
                'STATS' => placeholder_table(),
            )),null,'',true)
        );
    }

    /**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
    public function tpl_preview__administrative__stats_screen_overview()
    {
        return array(
            lorem_globalise(do_lorem_template('STATS_OVERVIEW_SCREEN',array(
                'TITLE' => lorem_title(),
                'STATS_VIEWS' => placeholder_table(),
                'GRAPH_VIEWS_MONTHLY' => lorem_phrase(),
                'STATS_VIEWS_MONTHLY' => lorem_phrase(),
            )),null,'',true)
        );
    }

    /**
	 * Get a preview(s) of a (group of) template(s), as a full standalone piece of HTML in Tempcode format.
	 * Uses sources/lorem.php functions to place appropriate stock-text. Should not hard-code things, as the code is intended to be declaritive.
	 * Assumptions: You can assume all Lang/CSS/JavaScript files in this addon have been pre-required.
	 *
	 * @return array						Array of previews, each is Tempcode. Normally we have just one preview, but occasionally it is good to test templates are flexible (e.g. if they use IF_EMPTY, we can test with and without blank data).
	 */
    public function tpl_preview__administrative__stats_screen_iscreen()
    {
        $graph_regionality = do_lorem_template('STATS_GRAPH',array(
            'GRAPH' => placeholder_url(),
            'TITLE' => lorem_phrase(),
            'TEXT' => lorem_sentence(),
            'KEYWORDS_SHARE' => lorem_word(),
            'DESCRIPTION_KEYWORDS_SHARE' => lorem_word(),
        ));

        return array(
            lorem_globalise(do_lorem_template('STATS_SCREEN_ISCREEN',array(
                'TITLE' => lorem_title(),
                'GRAPH_REGIONALITY' => $graph_regionality,
                'STATS_REGIONALITY' => placeholder_table(),
                'STATS_VIEWS' => lorem_phrase(),
                'GRAPH_KEYWORDS' => lorem_phrase(),
                'STATS_KEYWORDS' => lorem_phrase(),
                'GRAPH_VIEWS_HOURLY' => lorem_phrase(),
                'STATS_VIEWS_HOURLY' => lorem_phrase(),
                'GRAPH_VIEWS_DAILY' => lorem_phrase(),
                'STATS_VIEWS_DAILY' => lorem_phrase(),
                'GRAPH_VIEWS_WEEKLY' => lorem_phrase(),
                'STATS_VIEWS_WEEKLY' => lorem_phrase(),
                'GRAPH_VIEWS_MONTHLY' => lorem_phrase(),
                'STATS_VIEWS_MONTHLY' => lorem_phrase(),
                'GRAPH_IP' => placeholder_ip(),
                'STATS_IP' => placeholder_ip(),
                'GRAPH_BROWSER' => lorem_phrase(),
                'STATS_BROWSER' => lorem_phrase(),
                'GRAPH_REFERRER' => lorem_phrase(),
                'STATS_REFERRER' => lorem_phrase(),
                'GRAPH_OS' => lorem_phrase(),
                'STATS_OS' => lorem_phrase(),
            )),null,'',true)
        );
    }
}
