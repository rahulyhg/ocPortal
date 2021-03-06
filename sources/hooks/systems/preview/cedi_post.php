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
 * @package		cedi
 */

class Hook_Preview_cedi_post
{

	/**
	 * Find whether this preview hook applies.
	 *
	 * @return array			Triplet: Whether it applies, the attachment ID type, whether the forum DB is used [optional]
	 */
	function applies()
	{
		$applies=(get_param('page','')=='cedi');
		return array($applies,'seedy_post',false);
	}

	/**
	 * Standard modular run function for preview hooks.
	 *
	 * @return array			A pair: The preview, the updated post Comcode
	 */
	function run()
	{
		require_lang('ocf');
		require_css('ocf');

		$original_comcode=post_param('post');

		$posting_ref_id=post_param_integer('posting_ref_id',mt_rand(0,100000));
		$post_bits=do_comcode_attachments($original_comcode,'seedy_post',strval(-$posting_ref_id),true,$GLOBALS['SITE_DB']);
		$post_comcode=$post_bits['comcode'];
		$post_html=$post_bits['tempcode'];

		return array($post_html,$post_comcode);
	}

}


