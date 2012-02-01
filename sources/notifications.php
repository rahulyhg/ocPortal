<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2012

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_notifications
 */

/*
Background details, to set the context and how we have structured things for consistency...

Notifications are one-off messages sent out in response to something happening on the site. They may get delivered via e-mail, etc.
Notifications may optionally create a message that staff might discuss, in which case a discussion link will be auto-appended to anyone having access to the admin_messaging module. This should be used sparingly - remember that any staff may raise such a notification by reporting some content, so it should only be particularly eventful stuff that spawns this.
People may get an RSS feed of notifications if they enable notifications via PT, as PTs have an RSS feed - that may then be connected to Growl, IM, or whatever service they may enjoy using (kind of quirky, but some power users enjoy this for the cool factor). It's good that we support the standards, without too much complexity.

There is a separate ocPortal action log, called via log_it. This is not related to the notifications system, although staff may choose a notification when anything is added to the action log.
Similarly, there is the ocPortal activities syndication system. This is not related either, but again notifications may be generated through this.
The Admin Zone front page shows tasks. These are not the same thing as notifications, although notifications may have been sent when they were set up (specifically there is a notification for when custom tasks have been added).

There are RSS feeds in ocPortal. These are completely unrelated to notifications, although can be used in a similar way (in that they'll change when the website content changes, so a polling RSS reader can detect new content).
Similarly, there is "realtime rain".
There is "what's new" and the newsletter, where again are separate.

Any notifications are CC'd to the configured CC email address (if there is one). This is like having that address get notifications for everything, even if they shouldn't normally be able to receive that notification (i.e. was targeted to a specific member(s)). But it's not really considered parts of the notifications system.
*/

/**
 * Standard code module initialisation function.
 */
function init__notifications()
{
	// Notifications will be sent from one of the following if not a specific member ID
	define('A_FROM_SYSTEM_UNPRIVILEGED',-3); // Sent from system (website itself) *without* dangerous Comcode permission
	define('A_FROM_SYSTEM_PRIVILEGED',-2); // Sent from system (website itself) *with* dangerous Comcode permission

	// Notifications will be sent to one of the following if not to a specific list of member IDs
	define('A_TO_ANYONE_ENABLED',NULL);
	
	define('A_NA',0x0); // Not applicable
	//
	define('A_INSTANT_EMAIL',0x2);
	define('A_DAILY_EMAIL_DIGEST',0x4);
	define('A_WEEKLY_EMAIL_DIGEST',0x8);
	define('A_MONTHLY_EMAIL_DIGEST',0x10);
	define('A_INSTANT_SMS',0x20);
	define('A_INSTANT_PT',0x40); // Private topic
	// And...
	define('A__ALL',0xFFFFFF);
	// And...
	define('A__STATISTICAL',-1); // This is magic, it will choose whatever the user probably wants, based on their existing settings
}

/**
 * Find the notification object for a particular notification code.
 *
 * @param  ID_TEXT		The notification code to use
 * @return ?object		Notification object (NULL: could not find)
 */
function _get_notification_ob_for_code($notification_code)
{
	$path='hooks/systems/notifications/'.filter_naughty($notification_code);
	if ((!is_file(get_file_base().'/sources/'.$path.'.php')) && (!is_file(get_file_base().'/sources_custom/'.$path.'.php')))
	{
		$hooks=find_all_hooks('systems','notifications');
		foreach (array_keys($hooks) as $hook)
		{
			$path='hooks/systems/notifications/'.filter_naughty($hook);
			require_code($path);
			$ob=object_factory('Hook_Notification_'.filter_naughty($hook));
			if (method_exists($ob,'list_handled_codes'))
			{
				if (array_key_exists($notification_code,$ob->list_handled_codes()))
				{
					return $ob;
				}
			}
		}
	} else // Ah, we know already (file exists directly) - so quick route
	{
		require_code($path);
		return object_factory('Hook_Notification_'.filter_naughty($notification_code));
	}

	return object_factory('Hook_Notification'); // default
}

/**
 * Send out a notification to members enabled.
 *
 * @param  ID_TEXT		The notification code to use
 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
 * @param  SHORT_TEXT	Message subject (in Comcode)
 * @param  LONG_TEXT		Message body (in Comcode)
 * @param  ?array			List of enabled members to limit sending to (NULL: everyone)
 * @param  ?integer		The member ID doing the sending. Either a USER or a negative number (e.g. A_FROM_SYSTEM_UNPRIVILEGED) (NULL: current member)
 * @param  integer		The message priority (1=urgent, 3=normal, 5=low)
 * @range  1 5
 * @param  boolean		Whether to create a topic for discussion (ignored if the staff_messaging addon not installed)
 * @param  boolean		Whether to NOT CC to the CC address
 */
function dispatch_notification($notification_code,$code_category,$subject,$message,$to_member_ids=NULL,$from_member_id=NULL,$priority=3,$store_in_staff_messaging_system=false,$no_cc=false)
{
	$dispatcher=new Notification_dispatcher($notification_code,$code_category,$subject,$message,$to_member_ids,$from_member_id,$priority,$store_in_staff_messaging_system,$no_cc);
	if (get_param_integer('keep_debug_notifications',0)==1)
	{
		$dispatcher->dispatch();
	} else
	{
		register_shutdown_function(array($dispatcher,'dispatch'));
	}
}

/*
Dispatcher object. Used to create a closure for a notification dispatch, so we can then tell that to send in the background (register_shutdown_function), for performance reasons.
*/
class Notification_dispatcher
{
	var $notification_code=NULL;
	var $code_category=NULL;
	var $subject=NULL;
	var $message=NULL;
	var $to_member_ids=NULL;
	var $from_member_id=NULL;
	var $priority=NULL;
	var $store_in_staff_messaging_system=NULL;
	var $no_cc=NULL;

	/**
	 * Construct notification dispatcher.
	 *
	 * @param  ID_TEXT		The notification code to use
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  SHORT_TEXT	Message subject (in Comcode)
	 * @param  LONG_TEXT		Message body (in Comcode)
	 * @param  ?array			List of enabled members to limit sending to (NULL: everyone)
	 * @param  ?integer		The member ID doing the sending. Either a USER or a negative number (e.g. A_FROM_SYSTEM_UNPRIVILEGED) (NULL: current member)
	 * @param  integer		The message priority (1=urgent, 3=normal, 5=low)
	 * @range  1 5
	 * @param  boolean		Whether to create a topic for discussion (ignored if the staff_messaging addon not installed)
	 * @param  boolean		Whether to NOT CC to the CC address
	 */
	function Notification_dispatcher($notification_code,$code_category,$subject,$message,$to_member_ids,$from_member_id,$priority,$store_in_staff_messaging_system,$no_cc)
	{
		$this->notification_code=$notification_code;
		$this->code_category=$code_category;
		$this->subject=$subject;
		$this->message=$message;
		$this->to_member_ids=$to_member_ids;
		$this->from_member_id=$from_member_id;
		$this->priority=$priority;
		$this->store_in_staff_messaging_system=$store_in_staff_messaging_system;
		$this->no_cc=$no_cc;
	}

	/**
	 * Send out a notification to members enabled.
	 */
	function dispatch()
	{
		if (running_script('stress_test_loader')) return;
		if (get_page_name()=='admin_import') return;

		$subject=$this->subject;
		$message=$this->message;
		$no_cc=$this->no_cc;

		if ($GLOBALS['DEBUG_MODE'])
		{
			if ((strpos($this->message,'keep_devtest')!==false) && ((strpos(ocp_srv('HTTP_REFERER'),'keep_devtest')===false) || (strpos($this->message,ocp_srv('HTTP_REFERER'))===false))) // Bad URL - it has to be general, not session-specific
				fatal_exit(do_lang_tempcode('INTERNAL_ERROR'));
		}

		$ob=_get_notification_ob_for_code($this->notification_code);
		if (is_null($ob)) return;

		require_lang('notifications');
		require_code('mail');

		if (function_exists('set_time_limit')) @set_time_limit(0);

		if (($this->store_in_staff_messaging_system) && (addon_installed('staff_messaging')))
		{
			$id=uniqid('');
			$message_url=build_url(array('page'=>'admin_messaging','type'=>'view','id'=>$this->id,'message_type'=>$this->type),get_module_zone('admin_messaging'));

			$message=do_lang('MESSAGING_NOTIFICATION_WRAPPER',$message,$message_url->evaluate());

			do_comments(true,'notification',$this->id,$message_url,$subject,get_option('messaging_forum_name'),true,1,true,true,true);
		}

		$start=0;
		$max=300;
		do
		{
			list($members,$possibly_has_more)=$ob->list_members_who_have_enabled($this->notification_code,$this->code_category,$this->to_member_ids,$start,$max);

			foreach ($members as $to_member_id=>$setting)
			{
				$no_cc=_dispatch_notification_to_member($to_member_id,$setting,$this->notification_code,$this->code_category,$subject,$message,$this->from_member_id,$this->priority,$no_cc);
			}

			$start+=$max;
		}
		while ($possibly_has_more);
	}
}

/**
 * Find whether a particular kind of notification is available.
 *
 * @param  integer		The notification setting
 * @param  ?MEMBER		Member to check for (NULL: just check globally)
 * @return boolean		Whether it is available
 */
function _notification_setting_available($setting,$member_id=NULL)
{
	$system_wide=false;
	$for_member=false;
	switch ($setting)
	{
		case A_INSTANT_EMAIL:
			$system_wide=true;
			if ($system_wide && !is_null($member_id)) $for_member=($GLOBALS['FORUM_DRIVER']->get_member_email_address($member_id)!='');
			break;
		case A_DAILY_EMAIL_DIGEST:
		case A_WEEKLY_EMAIL_DIGEST:
		case A_MONTHLY_EMAIL_DIGEST:
			$system_wide=(cron_installed());
			if ($system_wide && !is_null($member_id)) $for_member=($GLOBALS['FORUM_DRIVER']->get_member_email_address($member_id)!='');
			break;
		case A_INSTANT_SMS:
			$system_wide=(addon_installed('sms')) && (get_option('sms_api_id')!='');
			if ($system_wide && !is_null($member_id))
			{
				if (has_specific_permission($member_id,'use_sms'))
				{
					$cpf_values=$GLOBALS['FORUM_DRIVER']->get_custom_fields($member_id);
					if (array_key_exists('mobile_phone_number',$cpf_values))
					{
						$for_member=(cleanup_mobile_number($cpf_values['mobile_phone_number'])!='');
					}
				}
			}
			break;
		case A_INSTANT_PT:
			$system_wide=(get_forum_type()=='ocf') && (addon_installed('ocf_forum'));
			if ($system_wide && !is_null($member_id)) $for_member=has_specific_permission($member_id,'use_pt');
			break;
	}
	return $system_wide && (is_null($member_id) || $for_member);
}

/**
 * Send out a notification to a member.
 *
 * @param  MEMBER			Member to send to
 * @param  integer		Listening setting
 * @param  ID_TEXT		The notification code to use
 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
 * @param  SHORT_TEXT	Message subject (in Comcode)
 * @param  LONG_TEXT		Message body (in Comcode)
 * @param  integer		The member ID doing the sending. Either a USER or a negative number (e.g. A_FROM_SYSTEM_UNPRIVILEGED)
 * @param  integer		The message priority (1=urgent, 3=normal, 5=low)
 * @range  1 5
 * @param  boolean		Whether to NOT CC to the CC address
 * @return boolean		New $no_cc setting
 */
function _dispatch_notification_to_member($to_member_id,$setting,$notification_code,$code_category,$subject,$message,$from_member_id,$priority,$no_cc)
{
	// Fish out some general details of the sender
	$to_name=$GLOBALS['FORUM_DRIVER']->get_username($to_member_id);
	$from_email=mixed();
	$from_name=mixed();
	if (!is_null($from_member_id))
	{
		$from_email=$GLOBALS['FORUM_DRIVER']->get_member_email_address($from_member_id);
		if ($from_email=='') $from_email=NULL;
		$from_name=$GLOBALS['FORUM_DRIVER']->get_username($from_member_id);
	}

	$db=(substr($notification_code,0,4)=='ocf_')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];

	// If none-specified, we'll need to be clever now
	if ($setting==A__STATISTICAL)
	{
		$notifications_enabled=$db->query_select('notifications_enabled',array('l_setting'),array('l_member_id'=>$to_member_id),'',100/*within reason*/);
		if (count($notifications_enabled)==0) // Default to e-mail
		{
			$setting=A_INSTANT_EMAIL;
		} else
		{
			$possible_settings=array();
			foreach (array(A_INSTANT_SMS,A_INSTANT_EMAIL,A_DAILY_EMAIL_DIGEST,A_WEEKLY_EMAIL_DIGEST,A_MONTHLY_EMAIL_DIGEST,A_INSTANT_PT) as $possible_setting)
			{
				if (_notification_setting_available($possible_setting))
					$possible_settings[$possible_setting]=0;
			}
			foreach ($notifications_enabled as $ml)
			{
				foreach (array_keys($possible_settings) as $possible_setting)
				{
					if ($ml['l_setting'] & $possible_setting != 0)
					{
						$possible_settings[$possible_setting]++;
					}
				}
			}
			arsort($possible_settings);
			reset($possible_settings);
			$setting=key($possible_settings);
		}
	}

	$needs_manual_cc=true;

	$message_to_send=$message; // May get tweaked, if we have some kind of error to explain, etc

	// Send according to the listen setting...

	if (_notification_setting_available(A_INSTANT_SMS))
	{
		if ($setting & A_INSTANT_SMS !=0)
		{
			$wrapped_message=do_lang('NOTIFICATION_SMS_COMPLETE_WRAP',$subject,$message_to_send); // Lang string may be modified to include {2}, but would cost more. Default just has {1}.

			require_code('sms');
			$successes=sms_wrap($wrapped_message,array($to_member_id));
			if ($successes==0) // Could not send
			{
				$setting = $setting | A_INSTANT_EMAIL; // Make sure it also goes to email then
				$message_to_send=do_lang('INSTEAD_OF_SMS',$message);
			}
		}
	}

	if (_notification_setting_available(A_INSTANT_EMAIL))
	{
		if ($setting & A_INSTANT_EMAIL !=0)
		{
			$to_email=$GLOBALS['FORUM_DRIVER']->get_member_email_address($to_member_id);
			if ($to_email!='')
			{
				$wrapped_subject=do_lang('NOTIFICATION_EMAIL_SUBJECT_WRAP',$subject,comcode_escape(get_site_name()));
				$wrapped_message=do_lang('NOTIFICATION_EMAIL_MESSAGE_WRAP',$message_to_send,comcode_escape(get_site_name()));

				mail_wrap($wrapped_subject,$wrapped_message,$to_email,$to_name,$from_email,$from_name,$priority,NULL,$no_cc,($from_member_id<0)?$GLOBALS['FORUM_DRIVER']->get_guest_id():$from_member_id,($from_member_id==A_FROM_SYSTEM_PRIVILEGED),false);

				$needs_manual_cc=false;
				$no_cc=true; // Don't CC again
			}
		}
	}

	if (_notification_setting_available(A_DAILY_EMAIL_DIGEST))
	{
		if (($setting & A_DAILY_EMAIL_DIGEST !=0) || ($setting & A_WEEKLY_EMAIL_DIGEST !=0) || ($setting & A_MONTHLY_EMAIL_DIGEST !=0))
		{
			$GLOBALS['SITE_DB']->query_insert('digestives_tin',array(
				'd_subject'=>$subject,
				'd_message'=>$message,
				'd_from_member_id'=>$from_member_id,
				'd_to_member_id'=>$to_member_id,
				'd_priority'=>$priority,
				'd_no_cc'=>$no_cc?1:0,
				'd_date_and_time'=>time(),
				'd_notification_code'=>$notification_code,
				'd_code_category'=>is_null($code_category)?'':$code_category,
				'd_frequency'=>$setting,
			));

			$GLOBALS['SITE_DB']->query_insert('digestives_consumed',array(
				'c_member_id'=>$to_member_id,
				'c_frequency'=>$setting,
				'c_time'=>time(),
			),false,true/*If we've not set up first digest time, make it the digest period from now; if we have then silent error is supressed*/);

			$needs_manual_cc=false;
		}
	}

	if (_notification_setting_available(A_INSTANT_PT))
	{
		if ($setting & A_INSTANT_PT !=0)
		{
			require_code('ocf_topics_action');
			require_code('ocf_posts_action');

			$wrapped_subject=do_lang('NOTIFICATION_PT_SUBJECT_WRAP',$subject);
			$wrapped_message=do_lang('NOTIFICATION_PT_MESSAGE_WRAP',$message_to_send);

			// NB: These are posted by Guest (system) although the display name is set to the member triggering. This is intentional to stop said member getting unexpected replies.
			$topic_id=ocf_make_topic(NULL,$wrapped_subject,'ocf_topic_modifiers/announcement'/*HACKHACK: replace with proper topic emoticon*/,1,1,0,0,0,db_get_first_id(),$to_member_id,false,0,NULL,'');
			ocf_make_post($topic_id,$wrapped_subject,$wrapped_message,0,true,1,0,($from_member_id<0)?do_lang('SYSTEM'):$from_name,NULL,NULL,db_get_first_id(),NULL,NULL,NULL,false,true,NULL,true,$wrapped_subject,0,NULL,true,true,true,($from_member_id==A_FROM_SYSTEM_PRIVILEGED));
		}
	}

	// Send to staff CC address regardless
	if ((!$no_cc) && ($needs_manual_cc))
	{
		$no_cc=true; // Don't CC again

		$to_email=get_option('cc_address');
		if ($to_email!='')
		{
			mail_wrap($subject,$message,$to_email,$to_name,$from_email,$from_name,$priority,NULL,true,($from_member_id<0)?NULL:$from_member_id,($from_member_id==A_FROM_SYSTEM_PRIVILEGED),false);
		}
	}

	return $no_cc;
}

/**
 * Enable notifications for a member on a notification type+category.
 *
 * @param  ID_TEXT		The notification code to use
 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
 * @param  ?MEMBER		The member being signed up (NULL: current member)
 */
function enable_notifications($notification_code,$notification_category,$member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	$ob=_get_notification_ob_for_code($notification_code);
	$default_setting=$ob->get_default_auto_setting($notification_code,$notification_category);

	$db=(substr($notification_code,0,4)=='ocf_')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];

	$db->query_delete('notifications_enabled',array(
		'l_member_id'=>$member_id,
		'l_notification_code'=>$notification_code,
		'l_code_category'=>is_null($notification_category)?'':$notification_category,
	));
	$db->query_insert('notifications_enabled',array(
		'l_member_id'=>$member_id,
		'l_notification_code'=>$notification_code,
		'l_code_category'=>is_null($notification_category)?'':$notification_category,
		'l_setting'=>$default_setting,
	));
}

/**
 * Disable notifications for a member on a notification type+category.
 *
 * @param  ID_TEXT		The notification code to use
 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
 * @param  ?MEMBER		The member being de-signed up (NULL: current member)
 */
function disable_notifications($notification_code,$notification_category,$member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	$db=(substr($notification_code,0,4)=='ocf_')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];

	$db->query_delete('notifications_enabled',array(
		'l_member_id'=>$member_id,
		'l_notification_code'=>$notification_code,
		'l_code_category'=>is_null($notification_category)?'':$notification_category,
	));
}

/**
 * Find whether notifications are enabled for a member on a notification type+category.
 *
 * @param  ID_TEXT		The notification code to check
 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
 * @param  ?MEMBER		The member being de-signed up (NULL: current member)
 * @return boolean		Whether they are
 */
function notifications_enabled($notification_code,$notification_category,$member_id=NULL)
{
	if (is_null($member_id)) $member_id=get_member();

	$db=(substr($notification_code,0,4)=='ocf_')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];

	$test=$db->query_value_null_ok('notifications_enabled','l_setting',array(
		'l_member_id'=>$member_id,
		'l_notification_code'=>$notification_code,
		'l_code_category'=>is_null($notification_category)?'':$notification_category,
	));
	if ((is_null($test)) && (!is_null($notification_category)))
	{
		$test=$db->query_value_null_ok('notifications_enabled','l_setting',array(
			'l_member_id'=>$member_id,
			'l_notification_code'=>$notification_code,
			'l_code_category'=>'',
		));
	}
	return (!is_null($test)) && ($test!=A_NA);
}

/**
 * Disable notifications for all members on a certain notification type+category.
 *
 * @param  ID_TEXT		The notification code
 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
 */
function delete_all_notifications_on($notification_code,$notification_category)
{
	$db=(substr($notification_code,0,4)=='ocf_')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];

	$db->query_delete('notifications_enabled',array(
		'l_notification_code'=>$notification_code,
		'l_code_category'=>is_null($notification_category)?'':$notification_category,
	));
}

/**
 * Base class for notification hooks. Provides default implementations for all methods that provide full access to everyone, and interact with enabled table.
 */
class Hook_Notification
{
	/**
	 * Get a list of all the notification codes this hook can handle.
	 * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
	 *
	 * @return array			List of codes (mapping between code names, and a pair: section and labelling for those codes)
	 */
	function list_handled_codes()
	{
		$list=array();
		$codename=preg_replace('#^Hook\_Notification\_#','',strtolower(get_class($this)));
		$list[$codename]=array(do_lang('GENERAL'),do_lang('NOTIFICATION_TYPE_'.$codename));
		return $list;
	}

	/**
	 * Find whether a handled notification code supports categories.
	 * (Content types, for example, will define notifications on specific categories, not just in general. The categories are interpreted by the hook and may be complex. E.g. it might be like a regexp match, or like FORUM:3 or TOPIC:100)
	 *
	 * @param  ID_TEXT		Notification code
	 * @return boolean		Whether it does
	 */
	function supports_categories($notification_code)
	{
		return false;
	}

	/**
	 * Find a bitmask of settings (email, SMS, etc) a notification code supports for listening on.
	 *
	 * @param  ID_TEXT		Notification code
	 * @return integer		Allowed settings
	 */
	function allowed_settings($notification_code)
	{
		return A__ALL;
	}

	/**
	 * Find the initial setting that members have for a notification code (only applies to the member_could_potentially_enable members).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return integer		Initial setting
	 */
	function get_initial_setting($notification_code,$category=NULL)
	{
		return A__STATISTICAL;
	}

	/**
	 * Find the setting that members have for a notification code if they have done some action triggering automatic setting (e.g. posted within a topic).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return integer		Automatic setting
	 */
	function get_default_auto_setting($notification_code,$category=NULL)
	{
		return A__STATISTICAL;
	}

	/**
	 * Get a list of members who have enabled this notification (i.e. have permission to AND have chosen to or are defaulted to).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @param  integer		Start position (for pagination)
	 * @param  integer		Maximum (for pagination)
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function list_members_who_have_enabled($notification_code,$category=NULL,$to_member_ids=NULL,$start=0,$max=300)
	{
		return $this->_all_members_who_have_enabled($notification_code,$category,$to_member_ids,$start,$max);
	}

	/**
	 * Further filter results from _all_members_who_have_enabled.
	 *
	 * @param  array			Members from main query (we'll filter them)
	 * @param  ID_TEXT		The privilege
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @param  integer		Start position (for pagination)
	 * @param  integer		Maximum (for pagination)
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function _all_members_who_have_enabled_with_sp($to_filter,$sp,$only_if_enabled_on__notification_code,$only_if_enabled_on__category,$to_member_ids,$start,$max)
	{
		list($_members,$possibly_has_more)=$to_filter;
		$members=array();
		foreach ($_members as $member=>$setting)
		{
			if (has_specific_permission($member,$sp))
				$members[$member]=$setting;
		}
		return array($members,$possibly_has_more);
	}

	/**
	 * Further filter results from _all_members_who_have_enabled.
	 *
	 * @param  array			Members from main query (we'll filter them)
	 * @param  ID_TEXT		The zone
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @param  integer		Start position (for pagination)
	 * @param  integer		Maximum (for pagination)
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function _all_members_who_have_enabled_with_zone_access($to_filter,$zone,$only_if_enabled_on__notification_code,$only_if_enabled_on__category,$to_member_ids,$start,$max)
	{
		list($_members,$possibly_has_more)=$to_filter;
		$members=array();
		foreach ($_members as $member=>$setting)
		{
			if (has_zone_access($member,$zone))
				$members[$member]=$setting;
		}
		return array($members,$possibly_has_more);
	}

	/**
	 * Further filter results from _all_members_who_have_enabled.
	 *
	 * @param  array			Members from main query (we'll filter them)
	 * @param  ID_TEXT		The page
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @param  integer		Start position (for pagination)
	 * @param  integer		Maximum (for pagination)
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function _all_members_who_have_enabled_with_page_access($to_filter,$page,$only_if_enabled_on__notification_code,$only_if_enabled_on__category,$to_member_ids,$start,$max)
	{
		list($_members,$possibly_has_more)=$to_filter;
		$members=array();
		foreach ($_members as $member=>$setting)
		{
			if (has_actual_page_access($member,$page))
				$members[$member]=$setting;
		}
		return array($members,$possibly_has_more);
	}

	/**
	 * Further filter results from _all_members_who_have_enabled.
	 *
	 * @param  array			Members from main query (we'll filter them)
	 * @param  ID_TEXT		The category permission type
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @param  integer		Start position (for pagination)
	 * @param  integer		Maximum (for pagination)
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function _all_members_who_have_enabled_with_category_access($to_filter,$category,$only_if_enabled_on__notification_code,$only_if_enabled_on__category,$to_member_ids,$start,$max)
	{
		list($_members,$possibly_has_more)=$to_filter;
		$members=array();
		foreach ($_members as $member=>$setting)
		{
			if (has_category_access($member,$category,$only_if_enabled_on__category))
				$members[$member]=$setting;
		}
		return array($members,$possibly_has_more);
	}

	/**
	 * Find whether a member could enable this notification (i.e. have permission to).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  MEMBER			Member to check against
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return boolean		Whether they could
	 */
	function member_could_potentially_enable($notification_code,$member_id,$category=NULL)
	{
		return $this->_is_member(NULL,NULL,$member_id);
	}

	/**
	 * Find whether a member has enabled this notification (i.e. have permission to AND have chosen to or are defaulted to).
	 * (Separate implementation to list_members_who_have_enabled, for performance reasons.)
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  MEMBER			Member to check against
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return boolean		Whether they are
	 */
	function member_have_enabled($notification_code,$member_id,$category=NULL)
	{
		return $this->_is_member($notification_code,$category,$member_id);
	}

	/**
	 * Get a list of members who have enabled this notification (i.e. have chosen to or are defaulted to).
	 * (No pagination supported, as assumed there are only a small set of members here.)
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @param  integer		Start position (for pagination)
	 * @param  integer		Maximum (for pagination)
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function _all_members_who_have_enabled($only_if_enabled_on__notification_code,$only_if_enabled_on__category,$to_member_ids,$start,$max)
	{
		global $NO_DB_SCOPE_CHECK;
		$bak=$NO_DB_SCOPE_CHECK;
		$NO_DB_SCOPE_CHECK=true;

		$initial_setting=$this->get_initial_setting($only_if_enabled_on__notification_code,$only_if_enabled_on__category);
		$has_by_default=($initial_setting!=A_NA);

		$clause_1=db_string_equal_to('l_notification_code',$only_if_enabled_on__notification_code);
		$clause_2=is_null($only_if_enabled_on__category)?db_string_equal_to('l_code_category',''):('('.db_string_equal_to('l_code_category','').' OR '.db_string_equal_to('l_code_category',$only_if_enabled_on__category).')');

		$clause_3='1=1';
		if (!is_null($to_member_ids))
		{
			$clause_3='(';
			foreach ($to_member_ids as $member_id)
			{
				if ($clause_3!='(') $clause_3.=' OR ';
				$clause_3.='l_member_id='.strval($member_id);
			}
			$clause_3.=')';
		}

		$db=(substr($only_if_enabled_on__notification_code,0,4)=='ocf_')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];

		if (($has_by_default) && (get_forum_type()=='ocf') && (db_has_subqueries($db->connection_read))) // Can only enumerate and join on a local OCF forum
		{
			$query_stub='SELECT m.id AS l_member_id,'.strval($initial_setting).' AS l_setting FROM '.$db->get_table_prefix().'f_members m WHERE '.str_replace('l_member_id','id',$clause_3).' AND ';
			$query_stem='NOT EXISTS(SELECT * FROM '.$db->get_table_prefix().'notifications_enabled l WHERE m.id=l.l_member_id AND '.$clause_1.' AND '.$clause_2.' AND '.$clause_3.' AND l_setting='.strval(A_NA).')';
		} else
		{
			$query_stub='SELECT l_member_id,l_setting FROM '.$db->get_table_prefix().'notifications_enabled WHERE ';
			$query_stem=$clause_1.' AND '.$clause_2.' AND l_setting<>'.strval(A_NA);
		}

		$results=$db->query($query_stub.$query_stem,$max,$start);

		$NO_DB_SCOPE_CHECK=$bak;

		$possibly_has_more=(count($results)==$max);

		return array(collapse_2d_complexity('l_member_id','l_setting',$results),$possibly_has_more);
	}

	/**
	 * Find whether someone has permisson to view any notifications (yes) and possibly if they actually are.
	 *
	 * @param  ?ID_TEXT		Notification code (NULL: don't check if they are)
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  MEMBER			Member to check against
	 * @return boolean		Whether they do
	 */
	function _is_member($only_if_enabled_on__notification_code,$only_if_enabled_on__category,$member_id)
	{
		if (is_null($only_if_enabled_on__notification_code)) return true;

		return notifications_enabled($only_if_enabled_on__notification_code,$only_if_enabled_on__category,$member_id);
	}
}

/**
 * Derived abstract base class of notification hooks that provides only staff access.
 */
class Hook_Notification__Staff extends Hook_Notification
{
	/**
	 * Get a list of all the notification codes this hook can handle.
	 * (Addons can define hooks that handle whole sets of codes, so hooks are written so they can take wide authority)
	 *
	 * @return array			List of codes (mapping between code names, and a pair: section and labelling for those codes)
	 */
	function list_handled_codes()
	{
		$list=array();
		$codename=preg_replace('#^Hook\_Notification\_#','',strtolower(get_class($this)));
		$list[$codename]=array(do_lang('STAFF'),do_lang('NOTIFICATION_TYPE_'.$codename));
		return $list;
	}

	/**
	 * Get a list of members who have enabled this notification (i.e. have permission to AND have chosen to or are defaulted to).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @param  integer		Start position (for pagination)
	 * @param  integer		Maximum (for pagination)
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function list_members_who_have_enabled($notification_code,$category=NULL,$to_member_ids=NULL,$start=0,$max=300)
	{
		return $this->_all_staff_who_have_enabled($notification_code,$category,$to_member_ids);
	}

	/**
	 * Find whether a member could enable this notification (i.e. have permission to).
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  MEMBER			Member to check against
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return boolean		Whether they could
	 */
	function member_could_potentially_enable($notification_code,$member_id,$category=NULL)
	{
		return $this->_is_staff(NULL,NULL,$member_id);
	}

	/**
	 * Find whether a member has enabled this notification (i.e. have permission to AND have chosen to or are defaulted to).
	 * (Separate implementation to list_members_who_have_enabled, for performance reasons.)
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  MEMBER			Member to check against
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @return boolean		Whether they are
	 */
	function member_have_enabled($notification_code,$member_id,$category=NULL)
	{
		return $this->_is_staff($notification_code,$category,$member_id);
	}

	/**
	 * Get a list of staff members who have enabled this notification (i.e. have permission to AND have chosen to or are defaulted to).
	 * (No pagination supported, as assumed there are only a small set of members here.)
	 *
	 * @param  ID_TEXT		Notification code
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  ?array			List of member IDs we are restricting to (NULL: no restriction). This effectively works as a intersection set operator against those who have enabled.
	 * @return array			A pair: Map of members to their notification setting, and whether there may be more
	 */
	function _all_staff_who_have_enabled($only_if_enabled_on__notification_code,$only_if_enabled_on__category,$to_member_ids)
	{
		$initial_setting=$this->get_initial_setting($only_if_enabled_on__notification_code,$only_if_enabled_on__category);

		$db=(substr($only_if_enabled_on__notification_code,0,4)=='ocf_')?$GLOBALS['FORUM_DB']:$GLOBALS['SITE_DB'];

		$admin_groups=array_merge($GLOBALS['FORUM_DRIVER']->get_super_admin_groups(),collapse_1d_complexity('group_id',$db->query_select('gsp',array('group_id'),array('specific_permission'=>'may_enable_staff_notifications'))));
		$rows=$GLOBALS['FORUM_DRIVER']->member_group_query($admin_groups);
		if (!is_null($to_member_ids))
		{
			$new_rows=array();
			foreach ($rows as $row)
			{
				if (in_array($GLOBALS['FORUM_DRIVER']->pname_id($row),$to_member_ids))
					$new_rows[]=$row;
			}
			$rows=$new_rows;
		}
		$new_rows=array();
		foreach ($rows as $row)
		{
			$test=notifications_enabled($only_if_enabled_on__notification_code,$only_if_enabled_on__category,$GLOBALS['FORUM_DRIVER']->pname_id($row));
			if (is_null($test)) $test=$initial_setting;

			if ($test!=A_NA)
				$new_rows[$GLOBALS['FORUM_DRIVER']->pname_id($row)]=$test;
		}

		$possibly_has_more=false;

		return array($new_rows,$possibly_has_more);
	}

	/**
	 * Find whether someone has permisson to view staff notifications and possibly if they actually are.
	 *
	 * @param  ?ID_TEXT		Notification code (NULL: don't check if they are)
	 * @param  ?SHORT_TEXT	The category within the notification code (NULL: none)
	 * @param  MEMBER			Member to check against
	 * @return boolean		Whether they do
	 */
	function _is_staff($only_if_enabled_on__notification_code,$only_if_enabled_on__category,$member_id)
	{
		$test=notifications_enabled($only_if_enabled_on__notification_code,$only_if_enabled_on__category,$member_id);

		return (($test) && (has_specific_permission($member_id,'may_enable_staff_notifications')));
	}
}

/*

TODO, Frontend...

Convert tracking addon to official notifications feature, and overhaul UI - will be a member setting notifications tab. messaging.ini / notifications.ini --> integrate
# # lang_custom/EN/notifications.ini
# # personalzone/pages/modules_custom/notifications.php
# # sources_custom/hooks/modules/notifications/catalogues.php
# # sources_custom/hooks/modules/notifications/cedi.php
# # sources_custom/hooks/modules/notifications/downloads.php
# # sources_custom/hooks/modules/notifications/galleries.php
# # sources_custom/hooks/systems/symbols/IS_TRACKED.php
# # sources_custom/hooks/systems/upon_query/notifications_catalogues.php
# # sources_custom/hooks/systems/upon_query/notifications_cedi.php
# # sources_custom/hooks/systems/upon_query/notifications_downloads.php
# # sources_custom/hooks/systems/upon_query/notifications_galleries.php
# # sources_custom/notifications.php
# # themes/default/css_custom/notifications.css
# # themes/default/templates_custom/JAVASCRIPT_NOTIFICATIONS.tpl
# # themes/default/templates_custom/NOTIFICATIONS.tpl
# # themes/default/templates_custom/NOTIFICATIONS_BUTTONS.tpl
# # themes/default/templates_custom/NOTIFICATIONS_SCREEN.tpl
# # themes/default/templates_custom/CEDI_PAGE_SCREEN.tpl
# # themes/default/templates_custom/GALLERY_REGULAR_MODE_SCREEN.tpl
# # themes/default/templates_custom/GALLERY_FLOW_MODE_SCREEN.tpl
# # themes/default/templates_custom/DOWNLOAD_CATEGORY_SCREEN.tpl
# # themes/default/templates_custom/CATALOGUE_DEFAULT_CATEGORY_SCREEN.tpl
# # cms/pages/modules_custom/cms_cedi.php
# # site/pages/modules_custom/cedi.php

Explain people want SMS and e-mail notifications, not just SMS notifications
UI must not show options unavailable to them.

Put "get notifications"/"stop notifications" buttons all over ocPortal where categories passed to notifications not null (used to be called track buttons)

Write tests

*/