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
 * @package		galleries
 */

class Hook_content_meta_aware_gallery
{

	/**
	 * Standard modular info function for content_meta_aware hooks. Allows progmattic identification of ocPortal entity model (along with db_meta table contents).
	 *
	 * @return ?array	Map of award content-type info (NULL: disabled).
	 */
	function info()
	{
		return array(
			'content_type_label'=>'galleries:GALLERY',

			'table'=>'galleries',
			'id_field'=>'name',
			'id_field_numeric'=>false,
			'parent_category_field'=>'parent_id',
			'parent_category_meta_aware_type'=>'gallery',
			'title_field'=>'fullname',
			'title_field_dereference'=>true,

			'is_category'=>true,
			'is_entry'=>false,
			'seo_type_code'=>'gallery',
			'feedback_type_code'=>'galleries',
			'permissions_type_code'=>'galleries', // NULL if has no permissions
			'view_pagelink_pattern'=>'_SEARCH:galleries:misc:_WILD',
			'edit_pagelink_pattern'=>'_SEARCH:cms_galleries:_ec:_WILD',
			'view_category_pagelink_pattern'=>'_SEARCH:galleries:misc:_WILD',
			'support_url_monikers'=>false,
			'search_hook'=>'galleries',
			'submitter_field'=>NULL,
			'add_time_field'=>'add_date',
			'edit_time_field'=>NULL,
			'validated_field'=>NULL,
			
			'addon_name'=>'galleries',
			
			'module'=>'galleries',
		);
	}
	
}
