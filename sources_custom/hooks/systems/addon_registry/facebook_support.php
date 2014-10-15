<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		facebook_support
 */

class Hook_addon_registry_facebook_support
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
	 * Get the addon category
	 *
	 * @return string			The category
	 */
    public function get_category()
    {
        return 'Third Party Integration';
    }

    /**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
    public function get_author()
    {
        return 'Kamen / Naveen / Chris';
    }

    /**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
    public function get_copyright_attribution()
    {
        return array(
            'Icon by leslienayibe',
            'Class by Facebook Inc.',
        );
    }

    /**
	 * Get the addon licence (one-line summary only)
	 *
	 * @return string			The licence
	 */
    public function get_licence()
    {
        return 'Licensed on the same terms as ocPortal';
    }

    /**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
    public function get_description()
    {
        return 'Substantial Facebook integration for your ocPortal website.

Features:
 - User\'s can log in to your site using their Facebook profile (for OCF-sites only)
 - New Facebook Friends block (allows users to like your site and shows those that have)
 - New Facebook Site activity feed block
 - New Facebook Facepile block (shows people who like your site or have signed in using Facebook)
 - New Facebook \'Like button\' block (linked into the main_screen_actions block by default)
 - New Facebook Comments block

For this addon to work you need to configure ocPortal\'s Facebook configuration settings, which includes getting a Facebook app ID.

Please be aware that this addon overrides some common templates to add Facebook functionality to them, such as [tt]LOGIN_SCREEN.tpl[/tt] and [tt]BLOCK_SIDE_PERSONAL_STATS_NO.tpl[/tt].';
    }

    /**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
    public function get_applicable_tutorials()
    {
        return array(
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
            'requires' => array(
            ),
            'recommends' => array(
            ),
            'conflicts_with' => array(
            )
        );
    }

    /**
	 * Explicitly say which icon should be used
	 *
	 * @return URLPATH		Icon
	 */
    public function get_default_icon()
    {
        return 'themes/default/images_custom/icons/48x48/menu/facebook.png';
    }

    /**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
    public function get_file_list()
    {
        return array(
            'themes/default/images_custom/icons/24x24/menu/facebook.png',
            'themes/default/images_custom/icons/48x48/menu/facebook.png',
            'sources_custom/hooks/systems/addon_registry/facebook_support.php',
            'sources_custom/hooks/systems/login_providers/.htaccess',
            'sources_custom/facebook/index.html',
            'sources_custom/facebook/jsonwrapper/JSON/index.html',
            'sources_custom/facebook/jsonwrapper/index.html',
            'sources_custom/blocks/main_facebook_friends.php',
            'lang_custom/EN/facebook_friends.ini',
            'themes/default/templates_custom/BLOCK_MAIN_FACEBOOK_FRIENDS.tpl',
            'lang_custom/EN/facebook.ini',
            'themes/default/templates_custom/BLOCK_SIDE_FACEBOOK_ACTIVITY.tpl',
            'themes/default/templates_custom/BLOCK_MAIN_FACEBOOK_FACEPILE.tpl',
            'themes/default/templates_custom/BLOCK_MAIN_FACEBOOK_LIKE.tpl',
            'themes/default/templates_custom/BLOCK_MAIN_FACEBOOK_COMMENTS.tpl',
            'sources_custom/blocks/side_facebook_activity.php',
            'sources_custom/blocks/main_facebook_facepile.php',
            'sources_custom/blocks/main_facebook_like.php',
            'sources_custom/blocks/main_facebook_comments.php',
            'sources_custom/facebook_connect.php',
            'sources_custom/hooks/systems/symbols/FB_CONNECT_UID.php',
            'sources_custom/hooks/systems/symbols/FB_CONNECT_ACCESS_TOKEN.php',
            'sources_custom/hooks/systems/symbols/USER_FB_CONNECT.php',
            'sources_custom/hooks/systems/symbols/FB_CONNECT_LOGGED_OUT.php',
            'sources_custom/hooks/systems/symbols/FB_CONNECT_FINISHING_PROFILE.php',
            'sources_custom/users.php',
            'sources_custom/ocf_members.php',
            'themes/default/templates_custom/BLOCK_SIDE_PERSONAL_STATS_NO.tpl',
            'themes/default/templates_custom/BLOCK_SIDE_PERSONAL_STATS.tpl',
            'themes/default/templates_custom/FACEBOOK_FOOTER.tpl',
            'themes/default/templates_custom/MEMBER_FACEBOOK.tpl',
            'themes/default/templates_custom/LOGIN_SCREEN.tpl',
            'themes/default/templates_custom/OCF_GUEST_BAR.tpl',
            'themes/default/templates_custom/BLOCK_MAIN_SCREEN_ACTIONS.tpl',
            'themes/default/templates_custom/JAVASCRIPT_FACEBOOK.tpl',
            'themes/default/templates_custom/BLOCK_TOP_LOGIN.tpl',
            'sources_custom/users_active_actions.php',
            'sources_custom/hooks/systems/syndication/facebook.php',
            'sources_custom/hooks/systems/syndication/.htaccess',
            'sources_custom/hooks/systems/page_groupings/facebook.php',
            'sources_custom/hooks/systems/login_providers/facebook.php',
            'sources_custom/hooks/systems/login_providers/index.html',
            'adminzone/pages/minimodules_custom/facebook_oauth.php',
            'sources_custom/facebook/facebook.php',
            'sources_custom/facebook/jsonwrapper/JSON/JSON.php',
            'sources_custom/facebook/jsonwrapper/JSON/LICENSE',
            'facebook_connect.php',
            'sources_custom/hooks/modules/members/facebook.php',
            'sources_custom/hooks/systems/config/facebook_allow_signups.php',
            'sources_custom/hooks/systems/config/facebook_appid.php',
            'sources_custom/hooks/systems/config/facebook_secret_code.php',
            'sources_custom/hooks/systems/config/facebook_uid.php',
            'sources_custom/hooks/systems/config/facebook_auto_syndicate.php',
            'sources_custom/hooks/systems/config/facebook_member_syndicate_to_page.php',
            'sources_custom/hooks/systems/config/facebook_sync_avatar.php',
            'sources_custom/hooks/systems/config/facebook_sync_dob.php',
            'sources_custom/hooks/systems/config/facebook_sync_email.php',
            'sources_custom/hooks/systems/config/facebook_sync_username.php',
            'sources_custom/ocf_field_editability.php',
        );
    }
}
