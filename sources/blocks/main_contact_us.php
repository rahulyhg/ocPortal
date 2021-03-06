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
 * @package		staff_messaging
 */

class Block_main_contact_us
{

	/**
	 * Standard modular info function.
	 *
	 * @return ?array	Map of module info (NULL: module is disabled).
	 */
	function info()
	{
		$info=array();
		$info['author']='Chris Graham';
		$info['organisation']='ocProducts';
		$info['hacked_by']=NULL;
		$info['hack_version']=NULL;
		$info['version']=2;
		$info['locked']=false;
		$info['parameters']=array('param','title','email_optional');
		return $info;
	}

	/**
	 * Standard modular run function.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
	function run($map)
	{
		require_lang('messaging');
		require_code('feedback');

		$type=array_key_exists('param',$map)?$map['param']:do_lang('GENERAL');

		$id=uniqid('',true);
		$_self_url=build_url(array('page'=>'admin_messaging','type'=>'view','id'=>$id,'message_type'=>$type),get_module_zone('admin_messaging'));
		$self_url=$_self_url->evaluate();
		$self_title=post_param('title',do_lang('CONTACT_US_MESSAGING'));
		$post=post_param('post','');
		$title=post_param('title','');

		$box_title=array_key_exists('title',$map)?$map['title']:do_lang('CONTACT_US');

		if ((post_param_integer('_comment_form_post',0)==1) && ($post!=''))
		{
			$message=new ocp_tempcode();/*Used to be written out here*/ attach_message(do_lang_tempcode('MESSAGE_SENT'),'inform');

			// Check CAPTCHA
			if ((addon_installed('captcha')) && (get_option('captcha_on_feedback')=='1'))
			{
				require_code('captcha');
				enforce_captcha();
			}

			// Handle notifications
			require_code('notifications');
			$notification_subject=do_lang('CONTACT_US_NOTIFICATION_SUBJECT',$title,NULL,NULL,get_site_default_lang());
			$notification_message=do_lang('CONTACT_US_NOTIFICATION_MESSAGE',comcode_escape(get_site_name()),comcode_escape($GLOBALS['FORUM_DRIVER']->get_username(get_member())),array($post,comcode_escape($type)),get_site_default_lang());
			dispatch_notification('messaging',$type.'_'.$id,$notification_subject,$notification_message,NULL,NULL,3,true);

			// Send standard confirmation email to current user
			$email_from=trim(post_param('email',$GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member())));
			if ($email_from!='')
			{
				require_code('mail');
				mail_wrap(do_lang('YOUR_MESSAGE_WAS_SENT_SUBJECT',$title),do_lang('YOUR_MESSAGE_WAS_SENT_BODY',$post),array($email_from),$GLOBALS['FORUM_DRIVER']->get_username(get_member()),'','',3,NULL,false,get_member());
			}

			decache('main_staff_checklist');
		} else
		{
			$message=new ocp_tempcode();
		}

		if (!has_no_forum())
		{
			// Comment posts
			$forum=get_option('messaging_forum_name');
			$count=0;
			$_comments=$GLOBALS['FORUM_DRIVER']->get_forum_topic_posts($GLOBALS['FORUM_DRIVER']->find_topic_id_for_topic_identifier($forum,$type.'_'.$id),$count);

			if ($_comments!==-1)
			{
				$em=$GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();
				require_javascript('javascript_editing');
				$comcode_help=build_url(array('page'=>'userguide_comcode'),get_comcode_zone('userguide_comcode',false));
				require_javascript('javascript_validation');
				$comment_url=get_self_url();
				$email_optional=array_key_exists('email_optional',$map)?(intval($map['email_optional'])==1):true;

				if (addon_installed('captcha'))
				{
					require_code('captcha');
					$use_captcha=((get_option('captcha_on_feedback')=='1') && (use_captcha()));
					if ($use_captcha)
					{
						generate_captcha();
					}
				} else $use_captcha=false;

				$comment_details=do_template('COMMENTS_POSTING_FORM',array('JOIN_BITS'=>'','FIRST_POST_URL'=>'','FIRST_POST'=>'','USE_CAPTCHA'=>$use_captcha,'EMAIL_OPTIONAL'=>$email_optional,'POST_WARNING'=>'','COMMENT_TEXT'=>'','GET_EMAIL'=>true,'GET_TITLE'=>true,'EM'=>$em,'DISPLAY'=>'block','COMMENT_URL'=>$comment_url,'TITLE'=>$box_title));

				$notifications_enabled=NULL;
				$notification_change_url=NULL;
				if (has_actual_page_access(get_member(),'admin_messaging'))
				{
					require_code('notifications');
					$notifications_enabled=notifications_enabled('messaging','type',get_member());
				}

				$out=do_template('BLOCK_MAIN_CONTACT_US',array('_GUID'=>'fd269dce5ff984ee558e9052fa0150b0','COMMENT_DETAILS'=>$comment_details,'MESSAGE'=>$message,'NOTIFICATIONS_ENABLED'=>$notifications_enabled,'TYPE'=>$type));
			} else $out=new ocp_tempcode();
		} else $out=new ocp_tempcode();

		return $out;
	}

}


