<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		testing_platform
 */

/**
 * ocPortal test case class (unit testing).
 */
class catalogues_test_set extends ocp_test_case
{
	var $cms_catalogues;
	var $cms_catalogues_cat;
	var $cms_catalogues_alt;

	function setUp()
	{
		parent::setUp();

		$this->establish_admin_session();

		require_code('content_reviews2');
		require_code('permissions2');
		require_code('form_templates');

		// Creating cms catalogues object
		require_code('cms/pages/modules/cms_catalogues.php');
		$this->cms_catalogues=new Module_cms_catalogues();
		$this->cms_catalogues_cat=new Module_cms_catalogues_cat();
		$this->cms_catalogues_alt=new Module_cms_catalogues_alt();
		$this->cms_catalogues->pre_run();
		$this->cms_catalogues->run_start('misc');
	}

	function testAddCatalogueUI()
	{
		require_code('content2');
		$_GET['type']='ad';
		$this->cms_catalogues_alt->pre_run();
		$this->cms_catalogues_alt->ad();
	}

	function testAddCatalogueActulizer()
	{
		$_POST=array(
			'new_field_0_visible'=>'1',
			'new_field_0_required'=>'1',
			'title'=>'Biodata',
			'require__title'=>'1',
			'name'=>'biodata',
			'require__name'=>'1',
			'comcode__description'=>'1',
			'description'=>'Test-cat',		
			'description_parsed'=>'',
			'display_type'=>'1',
			'require__display_type'=>'1',
			'tick_on_form__ecommerce'=>'0',
			'require__ecommerce'=>'0',
			'is_tree'=>'1',
			'tick_on_form__is_tree'=>'0',
			'require__is_tree'=>'0',
			'auto_fill'=>'',
			'require__auto_fill'=>'0',
			'catalogue_owner'=>'',
			'require__catalogue_owner'=>'0',
			'notes'=>'test',
			'pre_f_notes'=>'1',
			'require__notes'=>'0',
			'send_view_reports'=>'never',
			'require__send_view_reports'=>'1',
			'access_1_presets'=>'-1',
			'access_1'=>'1',
			'access_9_presets'=>'-1',
			'access_9'=>'1',
			'access_12_presets'=>'-1',
			'access_12'=>'1',
			'access_11_presets'=>'-1',
			'access_11'=>'1',
			'access_13_presets'=>'-1',
			'access_13'=>'1',
			'access_15_presets'=>'-1',
			'access_15'=>'1',
			'access_14_presets'=>'-1',
			'access_14'=>'1',
			'access_10_presets'=>'-1',
			'access_10'=>'1',
			'access_16_presets'=>'-1',
			'access_16'=>'1',
			'new_field_0_name'=>'Name',
			'require__new_field_0_name'=>'1',
			'new_field_0_description'=>'Enter name',
			'require__new_field_0_description'=>'0',
			'new_field_0_default'=>'',
			'require__new_field_0_default'=>'0',
			'new_field_0_type'=>'short_text',
			'require__new_field_0_type'=>'1',
			'new_field_0_order'=>'0',
			'require__new_field_0_order'=>'1',
			'new_field_0_defines_order'=>'0',
			'require__'=>'0',
			'new_field_0_searchable'=>'1',
			'tick_on_form__new_field_0_searchable'=>'0',
			'require__new_field_0_searchable'=>'0',
			'new_field_0_put_in_category'=>'1',
			'tick_on_form__new_field_0_put_in_category'=>'0',
			'require__new_field_0_put_in_category'=>'0',
			'new_field_0_put_in_search'=>'1',
			'tick_on_form__new_field_0_put_in_search'=>'0',
			'require__new_field_0_put_in_search'=>'0',
			'new_field_1_name'=>'Address',
			'require__new_field_1_name'=>'0',
			'new_field_1_description'=>'Your address',
			'require__new_field_1_description'=>'0',
			'new_field_1_default'=>'',
			'require__new_field_1_default'=>'0',
			'new_field_1_type'=>'short_text',
			'require__new_field_1_type'=>'1',
			'new_field_1_order'=>'1',
			'require__new_field_1_order'=>'1',
			'new_field_1_defines_order'=>'0',
			'new_field_1_visible'=>'1',
			'tick_on_form__new_field_1_visible'=>'0',
			'require__new_field_1_visible'=>'0',
			'new_field_1_required'=>'1',
			'tick_on_form__new_field_1_required'=>'0',
			'require__new_field_1_required'=>'0',
			'new_field_1_searchable'=>'1',
			'tick_on_form__new_field_1_searchable'=>'0',
			'require__new_field_1_searchable'=>'0',
			'new_field_1_put_in_category'=>'1',
			'tick_on_form__new_field_1_put_in_category'=>'0',
			'require__new_field_1_put_in_category'=>'0',
			'new_field_1_put_in_search'=>'1',
			'tick_on_form__new_field_1_put_in_search'=>'0',
			'require__new_field_1_put_in_search'=>'0',
			'new_field_2_name'=>'Qualification',
			'require__new_field_2_name'=>'0',
			'new_field_2_description'=>'Qualification',
			'require__new_field_2_description'=>'0',
			'new_field_2_default'=>'',
			'require__new_field_2_default'=>'0',
			'new_field_2_type'=>'short_text',
			'require__new_field_2_type'=>'1',
			'new_field_2_order'=>2,
			'require__new_field_2_order'=>'1',
			'new_field_2_defines_order'=>'0',
			'new_field_2_visible'=>'1',
			'tick_on_form__new_field_2_visible'=>'0',
			'require__new_field_2_visible'=>'0',
			'new_field_2_required'=>'1',
			'tick_on_form__new_field_2_required'=>'0',
			'require__new_field_2_required'=>'0',
			'new_field_2_searchable'=>'1',
			'tick_on_form__new_field_2_searchable'=>'0',
			'require__new_field_2_searchable'=>'0',
			'new_field_2_put_in_category'=>'1',
			'tick_on_form__new_field_2_put_in_category'=>'0',
			'require__new_field_2_put_in_category'=>'0',
			'new_field_2_put_in_search'=>'1',
			'tick_on_form__new_field_2_put_in_search'=>'0',
			'require__new_field_2_put_in_search'=>'0',
			'new_field_3_name'=>'',
			'require__new_field_3_name'=>'0',
			'new_field_3_description'=>'',
			'require__new_field_3_description'=>'0',
			'new_field_3_default'=>'',
			'require__new_field_3_default'=>'0',
			'new_field_3_type'=>'short_text',
			'require__new_field_3_type'=>'1',
			'new_field_3_order'=>3,
			'require__new_field_3_order'=>'1',
			'new_field_3_defines_order'=>'0',
			'new_field_3_visible'=>'1',
			'tick_on_form__new_field_3_visible'=>'0',
			'require__new_field_3_visible'=>'0',
			'new_field_3_required'=>'1',
			'tick_on_form__new_field_3_required'=>'0',
			'require__new_field_3_required'=>'0',
			'new_field_3_searchable'=>'1',
			'tick_on_form__new_field_3_searchable'=>'0',
			'require__new_field_3_searchable'=>'0',
			'new_field_3_put_in_category'=>'1',
			'tick_on_form__new_field_3_put_in_category'=>'0',
			'require__new_field_3_put_in_category'=>'0',
			'new_field_3_put_in_search'=>'1',
			'tick_on_form__new_field_3_put_in_search'=>'0',
			'require__new_field_3_put_in_search'=>'0',
			'new_field_4_name'=>'',
			'require__new_field_4_name'=>'0',
			'new_field_4_description'=>'',
			'require__new_field_4_description'=>'0',
			'new_field_4_default'=>'',
			'require__new_field_4_default'=>'0',
			'new_field_4_type'=>'short_text',
			'require__new_field_4_type'=>'1',
			'new_field_4_order'=>4,
			'require__new_field_4_order'=>'1',
			'new_field_4_defines_order'=>'0',
			'new_field_4_visible'=>'1',
			'tick_on_form__new_field_4_visible'=>'0',
			'require__new_field_4_visible'=>'0',
			'new_field_4_required'=>'1',
			'tick_on_form__new_field_4_required'=>'0',
			'require__new_field_4_required'=>'0',
			'new_field_4_searchable'=>'1',
			'tick_on_form__new_field_4_searchable'=>'0',
			'require__new_field_4_searchable'=>'0',
			'new_field_4_put_in_category'=>'1',
			'tick_on_form__new_field_4_put_in_category'=>'0',
			'require__new_field_4_put_in_category'=>'0',
			'new_field_4_put_in_search'=>'1',
			'tick_on_form__new_field_4_put_in_search'=>'0',
			'require__new_field_4_put_in_search'=>'0',
			'new_field_5_name'=>'',
			'require__new_field_5_name'=>'0',
			'new_field_5_description'=>'',
			'require__new_field_5_description'=>'0',
			'new_field_5_default'=>'',
			'require__new_field_5_default'=>'0',
			'new_field_5_type'=>'short_text',
			'require__new_field_5_type'=>'1',
			'new_field_5_order'=>5,
			'require__new_field_5_order'=>'1',
			'new_field_5_defines_order'=>'0',
			'new_field_5_visible'=>'1',
			'tick_on_form__new_field_5_visible'=>'0',
			'require__new_field_5_visible'=>'0',
			'new_field_5_required'=>'1',
			'tick_on_form__new_field_5_required'=>'0',
			'require__new_field_5_required'=>'0',
			'new_field_5_searchable'=>'1',
			'tick_on_form__new_field_5_searchable'=>'0',
			'require__new_field_5_searchable'=>'0',
			'new_field_5_put_in_category'=>'1',
			'tick_on_form__new_field_5_put_in_category'=>'0',
			'require__new_field_5_put_in_category'=>'0',
			'new_field_5_put_in_search'=>'1',
			'tick_on_form__new_field_5_put_in_search'=>'0',
			'require__new_field_5_put_in_search'=>'0',
			'new_field_6_name'=>'',
			'require__new_field_6_name'=>'0',
			'new_field_6_description'=>'',
			'require__new_field_6_description'=>'0',
			'new_field_6_default'=>'',
			'require__new_field_6_default'=>'0',
			'new_field_6_type'=>'short_text',
			'require__new_field_6_type'=>'1',
			'new_field_6_order'=>6,
			'require__new_field_6_order'=>'1',
			'new_field_6_defines_order'=>'0',
			'new_field_6_visible'=>'1',
			'tick_on_form__new_field_6_visible'=>'0',
			'require__new_field_6_visible'=>'0',
			'new_field_6_required'=>'1',
			'tick_on_form__new_field_6_required'=>'0',
			'require__new_field_6_required'=>'0',
			'new_field_6_searchable'=>'1',
			'tick_on_form__new_field_6_searchable'=>'0',
			'require__new_field_6_searchable'=>'0',
			'new_field_6_put_in_category'=>'1',
			'tick_on_form__new_field_6_put_in_category'=>'0',
			'require__new_field_6_put_in_category'=>'0',
			'new_field_6_put_in_search'=>'1',
			'tick_on_form__new_field_6_put_in_search'=>'0',
			'require__new_field_6_put_in_search'=>'0',
			'new_field_7_name'=>'',
			'require__new_field_7_name'=>'0',
			'new_field_7_description'=>'',
			'require__new_field_7_description'=>'0',
			'new_field_7_default'=>'',
			'require__new_field_7_default'=>'0',
			'new_field_7_type'=>'short_text',
			'require__new_field_7_type'=>'1',
			'new_field_7_order'=>7,
			'require__new_field_7_order'=>'1',
			'new_field_7_defines_order'=>'0',
			'new_field_7_visible'=>'1',
			'tick_on_form__new_field_7_visible'=>'0',
			'require__new_field_7_visible'=>'0',
			'new_field_7_required'=>'1',
			'tick_on_form__new_field_7_required'=>'0',
			'require__new_field_7_required'=>'0',
			'new_field_7_searchable'=>'1',
			'tick_on_form__new_field_7_searchable'=>'0',
			'require__new_field_7_searchable'=>'0',
			'new_field_7_put_in_category'=>'1',
			'tick_on_form__new_field_7_put_in_category'=>'0',
			'require__new_field_7_put_in_category'=>'0',
			'new_field_7_put_in_search'=>'1',
			'tick_on_form__new_field_7_put_in_search'=>'0',
			'require__new_field_7_put_in_search'=>'0',
			'new_field_8_name'=>'',
			'require__new_field_8_name'=>'0',
			'new_field_8_description'=>'',
			'require__new_field_8_description'=>'0',
			'new_field_8_default'=>'',
			'require__new_field_8_default'=>'0',
			'new_field_8_type'=>'short_text',
			'require__new_field_8_type'=>'1',
			'new_field_8_order'=>8,
			'require__new_field_8_order'=>'1',
			'new_field_8_defines_order'=>'0',
			'new_field_8_visible'=>'1',
			'tick_on_form__new_field_8_visible'=>'0',
			'require__new_field_8_visible'=>'0',
			'new_field_8_required'=>'1',
			'tick_on_form__new_field_8_required'=>'0',
			'require__new_field_8_required'=>'0',
			'new_field_8_searchable'=>'1',
			'tick_on_form__new_field_8_searchable'=>'0',
			'require__new_field_8_searchable'=>'0',
			'new_field_8_put_in_category'=>'1',
			'tick_on_form__new_field_8_put_in_category'=>'0',
			'require__new_field_8_put_in_category'=>'0',
			'new_field_8_put_in_search'=>'1',
			'tick_on_form__new_field_8_put_in_search'=>'0',
			'require__new_field_8_put_in_search'=>'0',
			'new_field_9_name'=>'',
			'require__new_field_9_name'=>'0',
			'new_field_9_description'=>'',
			'require__new_field_9_description'=>'0',
			'new_field_9_default'=>'',
			'require__new_field_9_default'=>'0',
			'new_field_9_type'=>'short_text',
			'require__new_field_9_type'=>'1',
			'new_field_9_order'=>9,
			'require__new_field_9_order'=>'1',
			'new_field_9_defines_order'=>'0',
			'new_field_9_visible'=>'1',
			'tick_on_form__new_field_9_visible'=>'0',
			'require__new_field_9_visible'=>'0',
			'new_field_9_required'=>'1',
			'tick_on_form__new_field_9_required'=>'0',
			'require__new_field_9_required'=>'0',
			'new_field_9_searchable'=>'1',
			'tick_on_form__new_field_9_searchable'=>'0',
			'require__new_field_9_searchable'=>'0',
			'new_field_9_put_in_category'=>'1',
			'tick_on_form__new_field_9_put_in_category'=>'0',
			'require__new_field_9_put_in_category'=>'0',
			'new_field_9_put_in_search'=>'1',
			'tick_on_form__new_field_9_put_in_search'=>'0',
			'require__new_field_9_put_in_search'=>'0',
			'description__is_wysiwyg'=>'1',
		);

		require_code('autosave');
		$_GET['type']='_ad';
		$this->cms_catalogues_alt->pre_run();
		$this->cms_catalogues_alt->_ad();
	}

	function testDeleteCatalogue()
	{
		require_code('autosave');
		$this->cms_catalogues_alt->delete_actualisation('biodata');
	}
}
