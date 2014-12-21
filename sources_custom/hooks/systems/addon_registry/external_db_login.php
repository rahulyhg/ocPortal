<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    external_db_login
 */

/**
 * Hook class.
 */
class Hook_addon_registry_external_db_login
{
    /**
     * Get a list of file permissions to set
     *
     * @return array                    File permissions to set
     */
    public function get_chmod_array()
    {
        return array();
    }

    /**
     * Get the version of ocPortal this addon is for
     *
     * @return float                    Version number
     */
    public function get_version()
    {
        return ocp_version_number();
    }

    /**
     * Get the addon category
     *
     * @return string                   The category
     */
    public function get_category()
    {
        return 'Development';
    }

    /**
     * Get the addon author
     *
     * @return string                   The author
     */
    public function get_author()
    {
        return 'Chris Graham';
    }

    /**
     * Find other authors
     *
     * @return array                    A list of co-authors that should be attributed
     */
    public function get_copyright_attribution()
    {
        return array();
    }

    /**
     * Get the addon licence (one-line summary only)
     *
     * @return string                   The licence
     */
    public function get_licence()
    {
        return 'Licensed on the same terms as ocPortal';
    }

    /**
     * Get the description of the addon
     *
     * @return string                   Description of the addon
     */
    public function get_description()
    {
        return 'A customisable login_provider hook, to help allowing login with logins defined in another database/table. See comments in sources_custom/external_db.php for usage documentation. Requires programming. Unlike other ocPortal user-sync addons, it runs only on ocPortal\'s end and interactively, no changes to other systems or batch importing required.';
    }

    /**
     * Get a list of tutorials that apply to this addon
     *
     * @return array                    List of tutorials
     */
    public function get_applicable_tutorials()
    {
        return array();
    }

    /**
     * Get a mapping of dependency types
     *
     * @return array                    File permissions to set
     */
    public function get_dependencies()
    {
        return array(
            'requires' => array(),
            'recommends' => array(),
            'conflicts_with' => array()
        );
    }

    /**
     * Get a list of files that belong to this addon
     *
     * @return array                    List of files
     */
    public function get_file_list()
    {
        return array(
            'sources_custom/hooks/systems/addon_registry/external_db_login.php',
            'sources_custom/hooks/systems/login_providers/external_db.php',
            'sources_custom/hooks/systems/login_providers_direct_auth/external_db.php',
            'sources_custom/hooks/systems/login_providers_direct_auth/index.html',
            'sources_custom/hooks/systems/login_providers_direct_auth/.htaccess',
            'sources_custom/hooks/systems/upon_login/external_db.php',
            'sources_custom/hooks/systems/upon_login/index.html',
            'sources_custom/hooks/systems/upon_login/.htaccess',
            'sources_custom/external_db.php',
            'pages/modules_custom/join.php',
            'pages/modules_custom/lost_password.php',
            'pages/modules_custom/login.php',
        );
    }
}