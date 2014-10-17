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
function init__sitemap_xml()
{
    require_code('xml');

    global $SITEMAPS_OUT_FILE, $SITEMAPS_OUT_PATH, $SITEMAPS_OUT_TEMPPATH;
    $SITEMAPS_OUT_FILE = null;
    $SITEMAPS_OUT_PATH = null;
    $SITEMAPS_OUT_TEMPPATH = null;
}

/**
 * Top level function to (re)generate a Sitemap (xml file, Google-style).
 */
function sitemap_xml_build()
{
    $GLOBALS['NO_QUERY_LIMIT'] = true;

    if (!is_guest()) {
        warn_exit('Will not generate sitemap as non-Guest');
    }

    $path = get_custom_file_base() . '/ocp_sitemap.xml';
    if (!file_exists($path)) {
        if (!is_writable_wrap(dirname($path))) {
            warn_exit(do_lang_tempcode('WRITE_ERROR_CREATE', escape_html('/')));
        }
    } else {
        if (!is_writable_wrap($path)) {
            warn_exit(do_lang_tempcode('WRITE_ERROR', escape_html('ocp_sitemap.xml')));
        }
    }

    require_code('sitemap');

    // Runs via a callback mechanism, so we don't need to load an arbitrary complex structure into memory.
    _sitemap_xml_initialise($path);
    $callback = '_sitemap_xml_serialize_sitemap_node';
    $meta_gather = SITEMAP_GATHER_TIMES;
    retrieve_sitemap_node(
        '',
        $callback,
        /*$valid_node_types=*/
        null,
        /*$child_cutoff=*/
        null,
        /*$max_recurse_depth=*/
        null,
        /*$require_permission_support=*/
        false,
        /*$zone=*/
        '_SEARCH',
        /*$use_page_groupings=*/
        false,
        /*$consider_secondary_categories=*/
        false,
        /*$consider_validation=*/
        false,
        $meta_gather
    );
    _sitemap_xml_finished();

    ping_sitemap_xml(get_custom_base_url() . '/ocp_sitemap.xml');
}

/**
 * Ping search engines with an updated sitemap.
 *
 * @param  URLPATH                      Sitemap URL.
 * @return string                       HTTP result output
 */
function ping_sitemap_xml($url)
{
    // Ping search engines
    $out = '';
    if (get_option('auto_submit_sitemap') == '1') {
        $ping = true;
        $base_url = get_base_url();
        $not_local = (substr($base_url, 0, 16) != 'http://localhost') && (substr($base_url, 0, 16) != 'http://127.0.0.1') && (substr($base_url, 0, 15) != 'http://192.168.') && (substr($base_url, 0, 10) != 'http://10.');
        if (($ping) && (get_option('site_closed') == '0') && ($not_local)) {
            // Submit to search engines
            $services = array(
                'http://www.google.com/webmasters/tools/ping?sitemap=',
                'http://submissions.ask.com/ping?sitemap=',
                'http://www.bing.com/webmaster/ping.aspx?siteMap=',
                'http://search.yahooapis.com/SiteExplorerService/V1/updateNotification?appid=SitemapWriter&url=',
            );
            foreach ($services as $service) {
                $out .= http_download_file($service . urlencode($url), null, false);
            }
        }
    }
    return $out;
}

/**
 * Initialise the writing to a Sitemap XML file. You can only call one of these functions per time as it uses global variables for tracking.
 *
 * @param  PATH                         Where we will save to.
 */
function _sitemap_xml_initialise($file_path)
{
    global $SITEMAPS_OUT_FILE, $SITEMAPS_OUT_PATH, $SITEMAPS_OUT_TEMPPATH, $LOADED_MONIKERS_CACHE;
    $SITEMAPS_OUT_TEMPPATH = ocp_tempnam('ocpsmap'); // We write to temporary path first to minimise the time our target file is invalid (during generation)
    $SITEMAPS_OUT_FILE = fopen($SITEMAPS_OUT_TEMPPATH, 'wb');
    $SITEMAPS_OUT_PATH = $file_path;

    if (function_exists('set_time_limit')) {
        @set_time_limit(0);
    }

    $GLOBALS['MEMORY_OVER_SPEED'] = true;

    // Load ALL URL ID monikers (for efficiency)
    if ($GLOBALS['SITE_DB']->query_select_value('url_id_monikers', 'COUNT(*)', array('m_deprecated' => 0)) < 10000) {
        $results = $GLOBALS['SITE_DB']->query_select('url_id_monikers', array('m_moniker', 'm_resource_page', 'm_resource_type', 'm_resource_id'), array('m_deprecated' => 0));
        foreach ($results as $result) {
            $LOADED_MONIKERS_CACHE[$result['m_resource_page']][$result['m_resource_type']][$result['m_resource_id']] = $result['m_moniker'];
        }
    }

    // Load ALL guest permissions (for efficiency)
    load_up_all_self_page_permissions(get_member());
    load_up_all_module_category_permissions(get_member());

    // Start of file
    $blob = '<' . '?xml version="1.0" encoding="' . get_charset() . '"?' . '>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    ';
    fwrite($SITEMAPS_OUT_FILE, $blob);
}

/**
 * Finalise the writing to a Sitemap XML file.
 */
function _sitemap_xml_finished()
{
    global $SITEMAPS_OUT_FILE, $SITEMAPS_OUT_PATH, $SITEMAPS_OUT_TEMPPATH;

    // End of file
    $blob = '
</urlset>
    ';
    fwrite($SITEMAPS_OUT_FILE, $blob);

    // Copy to final path / tidy up
    fclose($SITEMAPS_OUT_FILE);
    @unlink($SITEMAPS_OUT_PATH);
    copy($SITEMAPS_OUT_TEMPPATH, $SITEMAPS_OUT_PATH);
    @unlink($SITEMAPS_OUT_TEMPPATH);
    sync_file($SITEMAPS_OUT_PATH);
}

/**
 * Callback for writing a Sitemap node into the Sitemap XML file.
 *
 * @param  array                        The Sitemap node.
 */
function _sitemap_xml_serialize_sitemap_node($node)
{
    global $SITEMAPS_OUT_FILE;

    $page_link = $node['page_link'];
    if ($page_link === null) {
        return;
    }
    list($zone, $attributes, $hash) = page_link_decode($page_link);

    $add_date = $node['extra_meta']['add_date'];
    $edit_date = $node['extra_meta']['edit_date'];
    $priority = $node['sitemap_priority'];

    $langs = find_all_langs();
    foreach (array_keys($langs) as $lang) {
        $url = _build_url($attributes + (($lang == get_site_default_lang()) ? array() : array('keep_lang' => $lang)), $zone, null, false, false, true, $hash);

        $_lastmod_date = is_null($edit_date) ? $add_date : $edit_date;
        if (!is_null($_lastmod_date)) {
            $lastmod_date = '<lastmod>' . xmlentities(date('Y-m-d\TH:i:s', $_lastmod_date) . substr_replace(date('O', $_lastmod_date), ':', 3, 0)) . '</lastmod>';
        }
        $lastmod_date = '<changefreq>' . xmlentities($node['sitemap_refreshfreq']) . '</changefreq>';

        $url_blob = '
   <url>
      <loc>' . xmlentities($url) . '</loc>
      ' . $lastmod_date . '
      <priority>' . float_to_raw_string($priority) . '</priority>
   </url>
        ';
        fwrite($SITEMAPS_OUT_FILE, $url_blob);
    }
}
