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
 * @package		calendar
 */

/**
 * Escapes a string as per the ical format.
 *
 * @param  string				Input
 * @return string				Output
 */
function ical_escape($in)
{
	$ret=str_replace(chr(10),'\n',str_replace(',','\,',str_replace(';','\;',str_replace('\\','\\\\',$in))));
	if (strpos($ret,':')!==false)
		$ret='"'.str_replace('"','\"',$ret).'"';
	return $ret;
}

/**
 * Outputs the logged-in member's calendar view to ical.
 */
function output_ical()
{	
	@ini_set('ocproducts.xss_detect','0');

	header('Content-Type: text/calendar');
	header('Content-Disposition: filename="export.ics"');

	if (function_exists('set_time_limit')) @set_time_limit(0);

	$filter=get_param_integer('type_filter',NULL);
	if ($filter===0) $filter=NULL;
	$where='(e_submitter='.strval(get_member()).' OR e_is_public=1)';
	if (!is_null($filter)) $where.=' AND e_type='.strval($filter);
	$events=$GLOBALS['SITE_DB']->query('SELECT e_is_public,e_submitter,e_add_date,e_edit_date,e_title,e_content,e_type,validated,id,e_recurrence,e_recurrences,e_start_hour,e_start_minute,e_start_month,e_start_day,e_start_year,e_end_hour,e_end_minute,e_end_month,e_end_day,e_end_year FROM '.get_table_prefix().'calendar_events WHERE '.$where.' ORDER BY e_add_date DESC',10000/*reasonable limit*/);
	echo "BEGIN:VCALENDAR\n";
	echo "VERSION:2.0\n";
	echo "PRODID:-//ocProducts/ocPortal//NONSGML v1.0//EN\n";
	echo "CALSCALE:GREGORIAN\n";
	$categories=array();
	$_categories=$GLOBALS['SITE_DB']->query_select('calendar_types',array('*'));
	foreach ($_categories as $category)
	{
		$categories[$category['id']]=get_translated_text($category['t_title']);
	}
	if ((is_null($filter)) || (!array_key_exists($filter,$categories)))
	{
		echo "X-WR-CALNAME:".ical_escape(get_site_name())."\n";
	} else
	{
		echo "X-WR-CALNAME:".ical_escape(get_site_name().": ".$categories[$filter])."\n";
	}

	foreach ($events as $event)
	{
		if (!has_category_access(get_member(),'calendar',strval($event['e_type']))) continue;

		if (($event['e_is_public']==1) || ($event['e_submitter']==get_member()))
		{
			echo "BEGIN:VEVENT\n";

			echo "DTSTAMP:".date('Ymd',time())."T".date('His',$event['e_add_date'])."\n";
			echo "CREATED:".date('Ymd',time())."T".date('His',$event['e_add_date'])."\n";
			if (!is_null($event['e_edit_date'])) echo "LAST-MODIFIED:".date('Ymd',time())."T".date('His',$event['e_edit_date'])."\n";

			echo "SUMMARY:".ical_escape(get_translated_text($event['e_title']))."\n";
			$description=get_translated_text($event['e_content']);
			$matches=array();
			$num_matches=preg_match_all('#\[attachment[^\]]*\](\d+)\[/attachment\]#',$description,$matches);
			for ($i=0;$i<$num_matches;$i++)
			{
				$description=str_replace($matches[0],'',$description);
				$attachments=$GLOBALS['SITE_DB']->query_select('attachments',array('*'),array('id'=>intval($matches[1])));
				if (array_key_exists(0,$attachments))
				{
					$attachment=$attachments[0];
					require_code('mime_types');
					echo "ATTACH;FMTTYPE=".ical_escape(get_mime_type($attachment['a_original_filename'])).":".ical_escape(find_script('attachments').'?id='.strval($attachment['id']))."\n";
				}
			}
			echo "DESCRIPTION:".ical_escape($description)."\n";

			if (!is_guest($event['e_submitter']))
				echo "ORGANIZER;CN=".ical_escape($GLOBALS['FORUM_DRIVER']->get_username($event['e_submitter'])).";DIR=".ical_escape($GLOBALS['FORUM_DRIVER']->member_profile_link($event['e_submitter'])).":MAILTO:".ical_escape($GLOBALS['FORUM_DRIVER']->get_member_email_address($event['e_submitter']))."\n";
			echo "CATEGORIES:".ical_escape($categories[$event['e_type']])."\n";
			echo "CLASS:".(($event['e_is_public']==1)?'PUBLIC':'PRIVATE')."\n";
			echo "STATUS:".(($event['validated']==1)?'CONFIRMED':'TENTATIVE')."\n";
			echo "UID:".ical_escape(strval($event['id']).'@'.get_base_url())."\n";
			$_url=build_url(array('page'=>'calendar','type'=>'view','id'=>$event['id']),get_module_zone('calendar'),NULL,false,false,true);
			$url=$_url->evaluate();
			echo "URL:".ical_escape($url)."\n";

			$forum=get_value('comment_forum__calendar');
			if (is_null($forum)) $forum=get_option('comments_forum_name');
			$start=0;
			do
			{
				$count=0;
				$_comments=$GLOBALS['FORUM_DRIVER']->get_forum_topic_posts($forum,'events_'.strval($event['id']),'',$count,1000,$start);
				if (is_array($_comments))
				{
					foreach ($_comments as $comment)
					{
						if ($comment['title']!='') $comment['message']=$comment['title'].': '.$comment['message'];
						echo "COMMENT:".ical_escape($comment['message'].' - '.$GLOBALS['FORUM_DRIVER']->get_username($comment['user']).' ('.get_timezoned_date($comment['date']).')')."\n";
					}
				}
				$start+=1000;
			}
			while (count($_comments)==1000);

			$time=mktime(is_null($event['e_start_hour'])?12:$event['e_start_hour'],is_null($event['e_start_minute'])?0:$event['e_start_minute'],0,$event['e_start_month'],$event['e_start_day'],$event['e_start_year']);
			$time2=mixed();
			$time2=is_null($event['e_end_hour'])?NULL:mktime(is_null($event['e_end_hour'])?12:$event['e_end_hour'],is_null($event['e_end_minute'])?0:$event['e_end_minute'],0,$event['e_end_month'],$event['e_end_day'],$event['e_end_year']);
			if ($event['e_recurrence']!='none')
			{
				$parts=explode(' ',$event['e_recurrence']);
				if (count($parts)==1)
				{
					echo "DTSTART;TZ=".$event['e_timezone'].":".date('Ymd',$time).(is_null($event['e_start_hour'])?"":("T".date('His',$time)))."\n";
					if (!is_null($time2)) echo "DTEND:".date('Ymd',$time2)."T".(is_null($event['e_end_hour'])?"":("T".date('His',$time2)))."\n";
					$recurrence_code='FREQ='.strtoupper($parts[0]);
					echo "RRULE:".$recurrence_code.(is_null($event['e_recurrences'])?'':(";COUNT=".strval($event['e_recurrences'])))."\n";
				} else
				{
					for ($i=0;$i<strlen($parts[1]);$i++)
					{
						switch ($parts[0])
						{
							case 'daily':
								$time+=60*60*24;
								if (!is_null($time2)) $time2+=60*60*24;
								break;
							case 'weekly':
								$time+=60*60*24*7;
								if (!is_null($time2)) $time2+=60*60*24*7;
								break;
							case 'monthly':
								$days_in_month=intval(date('D',mktime(0,0,0,intval(date('m',$time))+1,0,intval(date('Y',$time)))));
								$time+=60*60*$days_in_month;
								if (!is_null($time2)) $time2+=60*60*$days_in_month;
								break;
							case 'yearly':
								$days_in_year=intval(date('Y',mktime(0,0,0,0,0,intval(date('Y',$time))+1)));
								$time+=60*60*24*$days_in_year;
								if (!is_null($time2)) $time2+=60*60*24*$days_in_year;
								break;
						}
						if ($parts[1][$i]!='0')
						{
							echo "DTSTART:".date('Ymd',$time)."T".date('His',$time)."\n";
							if (!is_null($time2)) echo "DTEND:".date('Ymd',$time2)."T".date('His',$time2)."\n";
							$recurrence_code='FREQ='.strtoupper($parts[0]);
							echo "RRULE:".$recurrence_code.";INTERVAL=".strval(strlen($parts[1])).";COUNT=1\n";
						}
					}
				}
			} else
			{
				echo "DTSTART:".date('Ymd',$time)."T".date('His',$time)."\n";
				if (!is_null($time2)) echo "DTEND:".date('Ymd',$time2)."T".date('His',$time2)."\n";
			}

			$attendees=$GLOBALS['SITE_DB']->query_select('calendar_reminders',array('*'),array('e_id'=>$event['id']),'',5000/*reasonable limit*/);
			if (count($attendees)==5000) $attendees=array();
			foreach ($attendees as $attendee)
			{
				if ($attendee['n_member_id']!=get_member())
				{
					if (!is_guest($event['n_member_id']))
						echo "ATTENDEE;CN=".ical_escape($GLOBALS['FORUM_DRIVER']->get_username($attendee['n_member_id'])).";DIR=".ical_escape($GLOBALS['FORUM_DRIVER']->member_profile_link($attendee['n_member_id'])).":MAILTO:".ical_escape($GLOBALS['FORUM_DRIVER']->get_member_email_address($attendee['n_member_id']))."\n";
				} else
				{
					echo "BEGIN:VALARM\n";
					echo "X-WR-ALARMUID:alarm".ical_escape(strval($event['id']).'@'.get_base_url())."\n";
					echo "ACTION:AUDIO\n";
					echo "TRIGGER:-PT".strval($attendee['n_seconds_before'])."S\n";
					echo "ATTACH;VALUE=URI:Basso\n";
					echo "END:VALARM\n";
				}
			}

			echo "END:VEVENT\n";
		}
	}
	echo "END:VCALENDAR\n";
	exit();
}

/**
 * Import ical events to members's event calendar.
 *
 * @param  PATH		File path
*/
function ical_import($file_name)
{
	$data=file_get_contents($file_name);

	$whole=end(explode('BEGIN:VCALENDAR',$data));

	$events=explode('BEGIN:VEVENT',$whole);

	$calendar_nodes=array();
	
	$new_type=NULL;

	foreach($events as $key=>$items)
	{		
		$nodes=explode("\n",$items);

		foreach($nodes as $_child)
		{
			$child=explode(':',$_child,2);

			$matches=array();
			if (preg_match('#;TZID=(.*)#',$child[0],$matches))
				$calendar_nodes[$key]['TZID']=$matches[1];
			$child[0]=preg_replace('#;.*#','',$child[0]);

			if (array_key_exists(1,$child) && $child[0]!=='PRODID' &&  $child[0]!=='VERSION' && $child[0]!=='END')
				$calendar_nodes[$key][$child[0]]=trim($child[1]);
		}

		if ($key!=0)
		{
			list(,$type,$recurrence,$recurrences,$seg_recurrences,$title,$content,$priority,$is_public,$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,$timezone,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes)=get_event_data_ical($calendar_nodes[$key]);

			if (is_null($type))
			{
				if (is_null($new_type))
				{
					require_code('calendar2');
					$new_type=add_event_type(strval(ucfirst($type)),'calendar/general');
				}
				$type=$new_type;
			}

			$id=add_calendar_event($type,$recurrence,$recurrences,$seg_recurrences,$title,$content,$priority,$is_public,$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,$timezone,1,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes);
		}
	}
}

/**
 * Get array of an events from node of an imported ical file
 *
 * @param  array		Array of given event details
 * @return array		Returns array of event details for mapping
 */
function get_event_data_ical($calendar_nodes)
{
	$url='';
	$type=NULL; //default value
	$e_recurrence='none';
	$recurrences=NULL;
	$seg_recurrences=0;
	$title='';
	$content='';
	$priority=2;
	$is_public=1;
	$start_year=2000;
	$start_month=1;
	$start_day=1;
	$start_hour=0;
	$start_minute=0;
	$end_year=NULL;
	$end_month=NULL;
	$end_day=NULL;
	$end_hour=NULL;
	$end_minute=NULL;
	$timezone='Europe/London';
	$validated=NULL;
	$allow_rating=NULL;
	$allow_comments=NULL;
	$allow_trackbacks=NULL;
	$notes='';
	$validated=1;
	$allow_rating=1;
	$allow_comments=1;
	$allow_trackbacks=1;
	$matches=array();

	$rec_array=array('FREQ','BYDAY','INTERVAL','COUNT');
	$rec_by_day=array('MO','TU','WE','TH','FR','SA','SU');

//	if (array_key_exists('LOCATION',$calendar_nodes))
//		$geo_position=$calendar_nodes['LOCATION'];
	

	if (array_key_exists('RRULE',$calendar_nodes))
	{
		$byday='';
		foreach($rec_array as $value)
		{
			if (preg_match('/^((.)*('.$value.'=))([^;]+)/i',$calendar_nodes['RRULE'],$matches)!=0)
			{
				switch ($value)
				{
					case 'FREQ':
						$e_recurrence=strtolower(end($matches));
						break;

					case 'INTERVAL':
						$rec_patern=' 1';

						for ($i = 1; $i < intval(end($matches)); $i++) 
						{
							$rec_patern.='0';
						}

						$e_recurrence.=$rec_patern;
						break;

					case 'COUNT':
						$recurrences=end($matches);
						break;																				
				}				
			}
		}
	}

	if (array_key_exists('CATEGORIES',$calendar_nodes))
	{
		$type=strtolower($calendar_nodes['CATEGORIES']);		
	}
	
	// Check existancy of category	
	$typeid=NULL;

	$rows=$GLOBALS['SITE_DB']->query_select('calendar_types',array('id','t_title'));

	foreach ($rows as $row)
	{
		if (strtolower($type)==strtolower(get_translated_text($row['t_title'])))
			$typeid=$row['id'];
	}
	

	if (array_key_exists('SUMMARY',$calendar_nodes))
	{
		$title=$calendar_nodes['SUMMARY'];
		$content=$calendar_nodes['SUMMARY'];
	}

	if (array_key_exists('PRIORITY',$calendar_nodes))
		$priority=$calendar_nodes['PRIORITY'];

	if (array_key_exists('TZID',$calendar_nodes))
		$timezone=$calendar_nodes['TZID'];

	if (array_key_exists('URL',$calendar_nodes))
		$url=$calendar_nodes['URL'];

	if (array_key_exists('DTSTART',$calendar_nodes))
	{
		$all_day=false;
		if (strlen($calendar_nodes['DTSTART'])==8)
		{
			$calendar_nodes['DTSTART'].=' 00:00';
			$all_day=true;
		}
		$start=strtotime($calendar_nodes['DTSTART']);
		$start_year=intval(date('Y',$start));
		$start_month=intval(date('m',$start));
		$start_day=intval(date('d',$start));
		$start_hour=$all_day?NULL:intval(date('H',$start));
		$start_minute=$all_day?NULL:intval(date('i',$start));
		if ($all_day)
		{
			$timestamp=mktime(0,0,0,$start_month,$start_day,$start_year);
			$amount_forward=tz_time($timestamp,$timezone)-$timestamp;
			$timestamp=$timestamp-$amount_forward;
			list($start_year,$start_month,$start_day)=array_map('intval',explode('-',date('Y-m-d',$timestamp)));
		} else
		{
			$timestamp=mktime($start_hour,$start_minute,0,$start_month,$start_day,$start_year);
			$amount_forward=tz_time($timestamp,$timezone)-$timestamp;
			$timestamp=$timestamp-$amount_forward;
			list($start_hour,$start_minute,$start_year,$start_month,$start_day,$start_hour,$start_minute)=array_map('intval',explode('-',date('Y-m-d',$timestamp)));
		}
	}

	if (array_key_exists('DTEND',$calendar_nodes))
	{
		$all_day=false;
		if (strlen($calendar_nodes['DTEND'])==8)
		{
			$calendar_nodes['DTEND'].=' 00:00';
			$all_day=true;
		}
		$end=strtotime($calendar_nodes['DTEND']);
		$end_year=intval(date('Y',$end));
		$end_month=intval(date('m',$end));
		$end_day=intval(date('d',$end));
		$end_hour=mixed();
		$end_minute=mixed();
		$end_hour=$all_day?NULL:intval(date('H',$end));
		$end_minute=$all_day?NULL:intval(date('i',$end));
		if ($all_day)
		{
			$timestamp=mktime(0,0,0,$end_month,$end_day,$end_year);
			$amount_forward=tz_time($timestamp,$timezone)-$timestamp;
			$timestamp=$timestamp-$amount_forward;
			list($end_year,$end_month,$end_day)=array_map('intval',explode('-',date('Y-m-d',$timestamp-1)));
		} else
		{
			$timestamp=mktime($end_hour,$end_minute,0,$end_month,$end_day,$end_year);
			$amount_forward=tz_time($timestamp,$timezone)-$timestamp;
			$timestamp=$timestamp-$amount_forward;
			list($end_hour,$end_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute)=array_map('intval',explode('-',date('Y-m-d',$timestamp-1)));
		}
	}

	$ret=array($url,$typeid,$e_recurrence,$recurrences,$seg_recurrences,$title,$content,$priority,$is_public,$start_year,$start_month,$start_day,$start_hour,$start_minute,$end_year,$end_month,$end_day,$end_hour,$end_minute,$timezone,$validated,$allow_rating,$allow_comments,$allow_trackbacks,$notes);
	return $ret;
}

