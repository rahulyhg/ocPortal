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
 * @package		ecommerce
 */

/**
 * Module page class.
 */
class Module_subscriptions
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
        $info['version'] = 5;
        $info['locked'] = false;
        $info['update_require_upgrade'] = 1;
        return $info;
    }

    /**
	 * Uninstall the module.
	 */
    public function uninstall()
    {
        $GLOBALS['SITE_DB']->drop_table_if_exists('subscriptions');

        $dbs_bak = $GLOBALS['NO_DB_SCOPE_CHECK'];
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;
        $GLOBALS['SITE_DB']->drop_table_if_exists('f_usergroup_subs');
        $GLOBALS['SITE_DB']->drop_table_if_exists('f_usergroup_sub_mails');
        $GLOBALS['NO_DB_SCOPE_CHECK'] = $dbs_bak;
    }

    /**
	 * Install the module.
	 *
	 * @param  ?integer	What version we're upgrading from (NULL: new install)
	 * @param  ?integer	What hack version we're upgrading from (NULL: new-install/not-upgrading-from-a-hacked-version)
	 */
    public function install($upgrade_from = null,$upgrade_from_hack = null)
    {
        $dbs_bak = $GLOBALS['NO_DB_SCOPE_CHECK'];
        $GLOBALS['NO_DB_SCOPE_CHECK'] = true;

        if (is_null($upgrade_from)) {
            $GLOBALS['SITE_DB']->create_table('subscriptions',array(
                'id' => '*AUTO', // linked to IPN with this
                's_type_code' => 'ID_TEXT',
                's_member_id' => 'MEMBER',
                's_state' => 'ID_TEXT', // pending|new|active|cancelled (pending means payment has been requested)
                's_amount' => 'SHORT_TEXT', // can't always find this from s_type_code
                's_purchase_id' => 'ID_TEXT',
                's_time' => 'TIME',
                's_auto_fund_source' => 'ID_TEXT', // The payment gateway
                's_auto_fund_key' => 'SHORT_TEXT', // Used by PayPal for nothing much, but is of real use if we need to schedule our own subscription transactions
                's_via' => 'ID_TEXT', // An eCommerce hook or 'manual'

                // Copied through from what the hook says at setup, in case the hook later changes
                's_length' => 'INTEGER',
                's_length_units' => 'SHORT_TEXT',
            ));

            $GLOBALS['SITE_DB']->create_table('f_usergroup_subs',array(
                'id' => '*AUTO',
                's_title' => 'SHORT_TRANS',
                's_description' => 'LONG_TRANS__COMCODE',
                's_cost' => 'SHORT_TEXT',
                's_length' => 'INTEGER',
                's_length_units' => 'SHORT_TEXT',
                's_auto_recur' => 'BINARY',
                's_group_id' => 'GROUP',
                's_enabled' => 'BINARY',
                's_mail_start' => 'LONG_TRANS',
                's_mail_end' => 'LONG_TRANS',
                's_mail_uhoh' => 'LONG_TRANS',
                's_uses_primary' => 'BINARY',
            ));
        }

        if ((is_null($upgrade_from)) || ($upgrade_from<5)) {
            $GLOBALS['SITE_DB']->create_table('f_usergroup_sub_mails',array(
                'id' => '*AUTO',
                'm_usergroup_sub_id' => 'AUTO_LINK',
                'm_ref_point' => 'ID_TEXT', // start|term_start|term_end|expiry
                'm_ref_point_offset' => 'INTEGER',
                'm_subject' => 'SHORT_TRANS',
                'm_body' => 'LONG_TRANS',
            ));
        }

        if ((!is_null($upgrade_from)) && ($upgrade_from<5)) {
            $GLOBALS['SITE_DB']->alter_table_field('subscriptions','s_special','ID_TEXT','s_purchase_id');
            $GLOBALS['SITE_DB']->add_table_field('subscriptions','s_length','INTEGER',1);
            $GLOBALS['SITE_DB']->add_table_field('subscriptions','s_length_units','SHORT_TEXT','m');
            $subscriptions = $GLOBALS['SITE_DB']->query_select('subscriptions',array('*'));
            foreach ($subscriptions as $sub) {
                if (substr($sub['s_type_code'],0,9) != 'USERGROUP') {
                    continue;
                }

                $usergroup_subscription_id = intval(substr($sub['s_type_code'],9));
                $usergroup_subscription_rows = $GLOBALS['FORUM_DB']->query_select('f_usergroup_subs',array('*'),array('id' => $usergroup_subscription_id),'',1);
                if (!array_key_exists(0,$usergroup_subscription_rows)) {
                    continue;
                }
                $usergroup_subscription_row = $usergroup_subscription_rows[0];

                $update_map = array(
                    's_length' => $usergroup_subscription_row['s_length'],
                    's_length_units' => $usergroup_subscription_row['s_length_units'],
                );
                $GLOBALS['SITE_DB']->query_update('subscriptions',$update_map,array('id' => $sub['id']),'',1);
            }

            $GLOBALS['SITE_DB']->add_table_field('f_usergroup_subs','s_auto_recur','BINARY',1);
        }

        $GLOBALS['NO_DB_SCOPE_CHECK'] = $dbs_bak;
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
        if ((!$check_perms || !is_guest($member_id)) && ($GLOBALS['SITE_DB']->query_select_value('subscriptions','COUNT(*)')>0)) {
            return array(
                'misc' => array('MY_SUBSCRIPTIONS','menu/adminzone/audit/ecommerce/subscriptions'),
            );
        }
        return array();
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

        require_lang('ecommerce');

        if ($type == 'misc') {
            $this->title = get_screen_title('MY_SUBSCRIPTIONS');
        }

        if ($type == 'cancel') {
            breadcrumb_set_parents(array(array('_SELF:_SELF:misc',do_lang_tempcode('MY_SUBSCRIPTIONS'))));

            $this->title = get_screen_title('SUBSCRIPTION_CANCEL');
        }

        return NULL;
    }

    /**
	 * Execute the module.
	 *
	 * @return tempcode	The result of execution.
	 */
    public function run()
    {
        require_code('ecommerce');
        require_css('ecommerce');

        // Kill switch
        if ((ecommerce_test_mode()) && (!$GLOBALS['IS_ACTUALLY_ADMIN']) && (!has_privilege(get_member(),'access_ecommerce_in_test_mode'))) {
            warn_exit(do_lang_tempcode('PURCHASE_DISABLED'));
        }

        if (is_guest()) {
            access_denied('NOT_AS_GUEST');
        }

        $type = get_param('type','misc');

        if ($type == 'misc') {
            return $this->my();
        }
        if ($type == 'cancel') {
            return $this->cancel();
        }
        return new ocp_tempcode();
    }

    /**
	 * Show my subscriptions.
	 *
	 * @return tempcode	The interface.
	 */
    public function my()
    {
        $member_id = get_member();
        if (has_privilege(get_member(),'assume_any_member')) {
            $member_id = get_param_integer('id',$member_id);
        }

        require_code('ecommerce_subscriptions');
        $_subscriptions = find_member_subscriptions($member_id);

        $subscriptions = array();
        foreach ($_subscriptions as $_subscription) {
            $subscriptions[] = prepare_templated_subscription($_subscription);
        }

        return do_template('ECOM_SUBSCRIPTIONS_SCREEN',array('_GUID' => 'e39cd1883ba7b87599314c1f8b67902d','TITLE' => $this->title,'SUBSCRIPTIONS' => $subscriptions));
    }

    /**
	 * Cancel a subscription.
	 *
	 * @return tempcode	The interface.
	 */
    public function cancel()
    {
        $id = get_param_integer('id');
        $via = $GLOBALS['SITE_DB']->query_select_value_if_there('subscriptions','s_via',array('id' => $id));
        if (is_null($via)) {
            warn_exit(do_lang_tempcode('MISSING_RESOURCE'));
        }

        if (($via != 'manual') && ($via != '')) {
            require_code('hooks/systems/ecommerce_via/' . filter_naughty($via));
            $hook = object_factory($via);
            if ($hook->auto_cancel($id) !== true) {
                // Because we cannot TRIGGER a REMOTE cancellation, we have it so the local user action triggers that notification, informing the staff to manually do a remote cancellation
                require_code('notifications');
                $trans_id = $GLOBALS['SITE_DB']->query_select_value('transactions','id',array('t_purchase_id' => strval($id)));
                $username = $GLOBALS['FORUM_DRIVER']->get_username(get_member());
                dispatch_notification('subscription_cancelled_staff',null,do_lang('SUBSCRIPTION_CANCELLED_SUBJECT',null,null,null,get_site_default_lang()),do_lang('SUBSCRIPTION_CANCELLED_BODY',$trans_id,$username,null,get_site_default_lang()));
            }
        }

        $GLOBALS['SITE_DB']->query_delete('subscriptions',array('id' => $id,'s_member_id' => get_member()),'',1);

        $url = build_url(array('page' => '_SELF'),'_SELF');
        return redirect_screen($this->title,$url,do_lang_tempcode('SUCCESS'));
    }
}
