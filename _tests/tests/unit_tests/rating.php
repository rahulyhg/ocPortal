<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2011

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		unit_testing
 */

/**
 * ocPortal test case class (unit testing).
 */
class rating_test_set extends ocp_test_case
{
	var $event_id;

	function setUp()
	{
		parent::setUp();
		require_code('calendar2');
		require_code('feedback');
		$this->event_id=add_calendar_event(8,'1',NULL,0,'test_event','',3,1,2010,1,10,10,15,2010,NULL,1,1,19,NULL,1,1,1,1,1,'',NULL,0,NULL,NULL,NULL);
		if('test_event'==get_translated_text($GLOBALS['SITE_DB']->query_value('calendar_events','e_title ',array('id'=>$this->event_id))))
		{
		   $GLOBALS['SITE_DB']->query_insert('rating',array('rating_for_type'=>'events','rating_for_id'=>$this->event_id,'rating_member'=>get_member(),'rating_ip'=>get_ip_address(),'rating_time'=>time(),'rating'=>4));		
		}
		$data = $GLOBALS['SITE_DB']->query_select('rating',array('rating '),array('rating_for_id'=>$this->event_id,'rating_member'=>get_member()));
		$rating = $data[0]['rating'];
		// Test the forum was actually created
		$this->assertTrue(4==$rating);
	}

	function testEditNewscategory()
	{
		// Test the forum edits
		edit_calendar_event($this->event_id,8,'',NULL,0,'test_event1','',3,1,2010,1,10,10,15,2010,1,19,0,0,NULL,1,'','',1,1,1,1,'');
		// Test the forum was actually created
		$this->assertTrue('test_event1'==get_translated_text($GLOBALS['SITE_DB']->query_value('calendar_events','e_title ',array('id'=>$this->event_id))));
	}
	
	
	function tearDown()
	{
		delete_calendar_event($this->event_id);
		$GLOBALS['SITE_DB']->query_delete('rating',array('rating_for_id'=>$this->event_id,'rating_member'=>get_member()));
		parent::tearDown();
	}
}