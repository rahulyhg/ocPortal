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
 * @package		staff_messaging
 */

class Block_main_contact_simple
{
    /**
	 * Find details of the block.
	 *
	 * @return ?array	Map of block info (NULL: block is disabled).
	 */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        $info['parameters'] = array('param','title','private','email_optional','body_prefix','body_suffix','subject_prefix','subject_suffix','redirect');
        return $info;
    }

    /**
	 * Execute the block.
	 *
	 * @param  array		A map of parameters.
	 * @return tempcode	The result of execution.
	 */
    public function run($map)
    {
        require_lang('messaging');
        require_code('feedback');

        $to = array_key_exists('param',$map)?$map['param']:get_option('staff_address');

        $body_prefix = array_key_exists('body_prefix',$map)?$map['body_prefix']:'';
        $body_suffix = array_key_exists('body_suffix',$map)?$map['body_suffix']:'';
        $subject_prefix = array_key_exists('subject_prefix',$map)?$map['subject_prefix']:'';
        $subject_suffix = array_key_exists('subject_suffix',$map)?$map['subject_suffix']:'';

        $block_id = md5(serialize($map));

        $post = post_param('post','');
        if ((post_param_integer('_comment_form_post',0) == 1) && (post_param('_block_id','') == $block_id) && ($post != '')) {
            if (addon_installed('captcha')) {
                if (get_option('captcha_on_feedback') == '1') {
                    require_code('captcha');
                    enforce_captcha();
                }
            }

            $message = new ocp_tempcode();/*Used to be written out here*/

            require_code('mail');

            $email_from = trim(post_param('email',$GLOBALS['FORUM_DRIVER']->get_member_email_address(get_member())));
            $title = post_param('title');

            mail_wrap($subject_prefix . $title . $subject_suffix,$body_prefix . $post . $body_suffix,array($to),null,$email_from,$GLOBALS['FORUM_DRIVER']->get_username(get_member()),3,null,false,get_member());

            if ($email_from != '') {
                mail_wrap(do_lang('YOUR_MESSAGE_WAS_SENT_SUBJECT',post_param('title')),do_lang('YOUR_MESSAGE_WAS_SENT_BODY',$post),array($email_from),null,'','',3,null,false,get_member());
            }

            attach_message(do_lang_tempcode('MESSAGE_SENT'),'inform');

            $redirect = array_key_exists('redirect',$map)?$map['redirect']:'';
            if ($redirect != '') {
                require_code('urls2');
                $redirect = page_link_as_url($redirect);
                require_code('site2');
                assign_refresh($redirect,0.0);
            }
        } else {
            $message = new ocp_tempcode();
        }

        $box_title = array_key_exists('title',$map)?$map['title']:do_lang('CONTACT_US');
        $private = (array_key_exists('private',$map)) && ($map['private'] == '1');

        $em = $GLOBALS['FORUM_DRIVER']->get_emoticon_chooser();

        require_javascript('javascript_editing');
        require_javascript('javascript_validation');

        $comment_url = get_self_url();
        $email_optional = array_key_exists('email_optional',$map)?(intval($map['email_optional']) == 1):true;

        if (addon_installed('captcha')) {
            require_code('captcha');
            $use_captcha = ((get_option('captcha_on_feedback') == '1') && (use_captcha()));
            if ($use_captcha) {
                generate_captcha();
            }
        } else {
            $use_captcha = false;
        }

        $hidden = new ocp_tempcode();
        $hidden->attach(form_input_hidden('_block_id',$block_id));

        $comment_details = do_template('COMMENTS_POSTING_FORM',array(
            '_GUID' => 'd35227903b5f786331f6532bce1765e4',
            'JOIN_BITS' => '',
            'FIRST_POST_URL' => '',
            'FIRST_POST' => '',
            'USE_CAPTCHA' => $use_captcha,
            'EMAIL_OPTIONAL' => $email_optional,
            'POST_WARNING' => '',
            'COMMENT_TEXT' => '',
            'GET_EMAIL' => !$private,
            'GET_TITLE' => !$private,
            'EM' => $em,
            'DISPLAY' => 'block',
            'TITLE' => $box_title,
            'COMMENT_URL' => $comment_url,
            'HIDDEN' => $hidden,
        ));

        $out = do_template('BLOCK_MAIN_CONTACT_SIMPLE',array('_GUID' => '298a357f442f440c6b42e58d6717e57c','EMAIL_OPTIONAL' => true,'COMMENT_DETAILS' => $comment_details,'MESSAGE' => $message));

        return $out;
    }
}
