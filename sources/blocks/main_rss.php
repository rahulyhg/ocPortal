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
 * @package    syndication_blocks
 */

/**
 * Block class.
 */
class Block_main_rss
{
    /**
     * Find details of the block.
     *
     * @return ?array                   Map of block info (null: block is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 3;
        $info['locked'] = false;
        $info['update_require_upgrade'] = 1;
        $info['parameters'] = array('param', 'max_entries', 'title', 'copyright');
        return $info;
    }

    /**
     * Find cacheing details for the block.
     *
     * @return ?array                   Map of cache details (cache_on and ttl) (null: block is disabled).
     */
    public function cacheing_environment()
    {
        $info = array();
        $info['cache_on'] = array('block_main_rss__cache_on');
        $info['ttl'] = intval(get_option('rss_update_time'));
        return $info;
    }

    /**
     * Execute the block.
     *
     * @param  array                    $map A map of parameters.
     * @return tempcode                 The result of execution.
     */
    public function run($map)
    {
        require_lang('news');
        require_css('news');
        require_code('obfuscate');

        $url = array_key_exists('param', $map) ? $map['param'] : (get_brand_base_url() . '/backend.php?type=rss&mode=news&filter=1,2,17,18,29,30'); // http://news.google.co.uk/news?hs=UTT&tab=wn&topic=w&output=atom

        require_code('rss');
        $rss_feeds = array();
        $urls = preg_split('#\s+#', $url);
        $error = null;
        foreach (array_reverse($urls) as $url) { // Reversed so that $rss stays as the first feed, and hence the title etc comes from the first, not the last
            $url = trim($url);
            if ($url == '') {
                continue;
            }

            $rss = new OCP_RSS($url);
            if (!is_null($rss->error)) {
                $error = $rss->error;
                continue;
            }
            $rss_feeds[] = $rss;
        }

        if ((!is_null($error)) && (count($rss_feeds) == 0)) {
            $GLOBALS['DO_NOT_CACHE_THIS'] = true;
            require_code('failure');
            relay_error_notification(do_lang('ERROR_HANDLING_RSS_FEED', $url, $error), false, 'error_occurred_rss');
            if (cron_installed()) {
                if (!$GLOBALS['FORUM_DRIVER']->is_staff(get_member())) {
                    return new Tempcode();
                }
            }
            return do_template('INLINE_WIP_MESSAGE', array('_GUID' => 'c2a067db18cd5f14392fa922b06967e4', 'MESSAGE' => htmlentities($error)));
        }

        global $NEWS_CATS_CACHE;
        $NEWS_CATS_CACHE = $GLOBALS['SITE_DB']->query_select('news_categories', array('*'), array('nc_owner' => null));
        $NEWS_CATS_CACHE = list_to_map('id', $NEWS_CATS_CACHE);

        if (!array_key_exists('title', $rss->gleamed_feed)) {
            $rss->gleamed_feed['title'] = do_lang_tempcode('RSS_STREAM');
        }
        if (array_key_exists('title', $map)) {
            $rss->gleamed_feed['title'] = $map['title'];
        }

        // Reduce what we collected about the feed to a minimum. This is very awkward, as we don't know what's here.
        if (!array_key_exists('copyright', $rss->gleamed_feed)) {
            $rss->gleamed_feed['copyright'] = '';
        }
        if (array_key_exists('copyright', $map)) {
            $rss->gleamed_feed['copyright'] = $map['copyright'];
        }

        // Now for the actual stream contents
        $max = array_key_exists('max_entries', $map) ? intval($map['max_entries']) : 10;
        $content = new Tempcode();
        $items = array();
        foreach ($rss_feeds as $_rss) {
            foreach ($_rss->gleamed_items as $item) {
                if (!array_key_exists('clean_add_date', $item)) {
                    $item['clean_add_date'] = time();
                }
                $items[] = $item;
            }
        }
        sort_maps_by($items, 'clean_add_date');
        $items = array_reverse($items);
        foreach ($items as $i => $item) {
            if ($i >= $max) {
                break;
            }

            if (array_key_exists('full_url', $item)) {
                $_full_url = $item['full_url'];
            } elseif (array_key_exists('guid', $item)) {
                $_full_url = $item['guid'];
            } elseif (array_key_exists('comment_url', $item)) {
                $_full_url = $item['comment_url'];
            } else {
                $_full_url = '';
            }

            $_title = $item['title'];
            $_title = array_key_exists('title', $item) ? $item['title'] : '';

            $full_url = ($_full_url != '') ? hyperlink($_full_url, do_lang_tempcode('VIEW'), true, false, $_title) : new Tempcode();

            if (array_key_exists('category', $rss->gleamed_items)) {
                $_title = do_template('BLOCK_MAIN_RSS_TITLE', array('_GUID' => 'd962c1165564f080329decffeab88ba7', 'CATEGORY' => $rss->gleamed_items['category'], 'TITLE' => $_title));
            }

            if (!array_key_exists('news', $item)) {
                $news = (array_key_exists('news_article', $item)) ? $item['news_article'] : '';
                $news_full = new Tempcode();
            } else {
                $news = $item['news'];
                if (array_key_exists('news_article', $item)) {
                    $news_full = do_template('BLOCK_MAIN_RSS_FULL', array('_GUID' => 'adcd82c64966f54fb0173b8edc626bd7', 'NEWS_FULL' => $item['news_article']));
                } else {
                    $news_full = new Tempcode();
                }
            }

            if (array_key_exists('author', $item)) {
                $_author = $item['author'];
                if (array_key_exists('author_url', $item)) {
                    $__author = hyperlink($item['author_url'], escape_html($_author));
                } elseif (array_key_exists('author_email', $item)) {
                    $__author = hyperlink(protect_from_escaping(mailto_obfuscated() . ((strpos($item['author_email'], '&') !== false) ? $item['author_email'] : obfuscate_email_address($item['author_email']))), escape_html($_author));
                } else {
                    $__author = make_string_tempcode($_author);
                }
                $author = do_lang_tempcode('SUBMITTED_BY', $__author->evaluate());
            } else {
                $author = new Tempcode();
            }

            // If we want to show in a tails arrangement (by default, we won't)
            if (!$author->is_empty()) {
                $tails = do_template('BLOCK_MAIN_RSS_LIST_FIRST', array('_GUID' => '5ce8a5f1fd8a9487c01b63e791618589', 'X' => $author));
                $tails->attach(do_template('BLOCK_MAIN_RSS_LIST_LAST', array('_GUID' => 'f199850d1b76cc4a6774731e1f89762e', 'X' => $full_url)));
            } else {
                $tails = new Tempcode();
            }

            if (array_key_exists('category', $item)) {
                global $THEME_IMAGES_CACHE;
                $cat = null;
                foreach ($NEWS_CATS_CACHE as $_cat => $news_cat) {
                    if (get_translated_text($news_cat['nc_title']) == $item['category']) {
                        $cat = $_cat;
                    }
                }
                if (!is_null($cat)) {
                    $img = ($NEWS_CATS_CACHE[$cat]['nc_img'] == '') ? '' : find_theme_image($NEWS_CATS_CACHE[$cat]['nc_img']);
                    if (is_null($img)) {
                        $img = '';
                    }
                    if (($img != '') && (url_is_local($img))) {
                        $img = get_base_url() . '/' . $img;
                    }
                    $category = do_template('BLOCK_MAIN_RSS_CATEGORY', array('_GUID' => '9b70a0d7524b62ea74bdb8071f4e88b5', 'IMG' => $img, 'CATEGORY' => $item['category']));
                } else {
                    $category = do_template('BLOCK_MAIN_RSS_CATEGORY_NO_IMG', array('_GUID' => '772e44215bd2682e51a96b7480753ded', 'CATEGORY' => $item['category']));
                }
            } else {
                $category = new Tempcode();
            }

            if (array_key_exists('add_date', $item)) {
                $__title = do_template('BLOCK_MAIN_RSS_FROM_TITLE', array('_GUID' => 'ba9d262682d2e7d74c393508c8d49dd6', 'FEED_URL' => $url, 'NEWS_TITLE' => $_title, 'DATE' => $item['add_date']));
            } else {
                $__title = $_title;
            }

            $content->attach(do_template('BLOCK_MAIN_RSS_SUMMARY', array(
                '_GUID' => '9ca64090348263449ea1fcea75c8ed5f',
                'FEED_URL' => $url,
                'NEWS_FULL' => $news_full,
                'DATE' => array_key_exists('add_date', $item) ? $item['add_date'] : '',
                'DATE_RAW' => array_key_exists('clean_add_date', $item) ? strval($item['clean_add_date']) : '',
                'TAILS' => $tails,
                'AUTHOR' => $author,
                'CATEGORY' => $category,
                'FULL_URL' => $full_url,
                'FULL_URL_RAW' => $_full_url,
                'NEWS_TITLE' => $__title,
                'NEWS' => $news,
            )));
        }

        if (array_key_exists('author', $rss->gleamed_feed)) {
            $__author = null;
            $_author_string = $rss->gleamed_feed['author'];
            if (array_key_exists('url', $rss->gleamed_feed)) {
                $__author = hyperlink($rss->gleamed_feed['url'], escape_html($_author_string), true);
            } elseif (array_key_exists('author_url', $rss->gleamed_feed)) {
                $__author = hyperlink($rss->gleamed_feed['author_url'], escape_html($_author_string), true);
            } elseif (array_key_exists('author_email', $rss->gleamed_feed)) {
                $__author = hyperlink(mailto_obfuscated() . obfuscate_email_address($rss->gleamed_feed['author_email']), escape_html($_author_string), true);
            }
            if (!is_null($__author)) {
                $_author_string = $__author->evaluate();
            }
            $author = do_lang_tempcode('RSS_SOURCE_FROM', $_author_string);
        } else {
            $author = new Tempcode();
        }

        return do_template('BLOCK_MAIN_RSS', array('_GUID' => '6c9c1287abff88fda881e3e25ef7b296', 'FEED_URL' => $url, 'TITLE' => $rss->gleamed_feed['title'], 'COPYRIGHT' => $rss->gleamed_feed['copyright'], 'AUTHOR' => $author, 'CONTENT' => $content));
    }
}

/**
 * Find the cache signature for the block.
 *
 * @param  array                        $map The block parameters.
 * @return array                        The cache signature.
 */
function block_main_rss__cache_on($map)
{
    return array(cron_installed() ? null : $GLOBALS['FORUM_DRIVER']->is_staff(get_member()), array_key_exists('max_entries', $map) ? intval($map['max_entries']) : 10, array_key_exists('title', $map) ? $map['title'] : '', array_key_exists('copyright', $map) ? $map['copyright'] : '', array_key_exists('param', $map) ? $map['param'] : '');
}
