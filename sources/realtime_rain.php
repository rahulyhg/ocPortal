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
 * @package		realtime_rain
 */

/**
 * AJAX script for returning realtime-rain data.
 */
function realtime_rain_script()
{
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	@ini_set('ocproducts.xss_detect','0');

	header('Content-Type: text/xml');
	echo '<?xml version="1.0" encoding="'.get_charset().'"?'.'>';
	echo '<request><result>';
	require_code('realtime_rain');
	require_lang('realtime_rain');

	$time_now=time();
	$from=get_param_integer('from',$time_now-10);
	$to=get_param_integer('to',$time_now);

	if (get_param_integer('keep_realtime_test',0)==1)
	{
		$types=array('post','news','recommend','polls','ecommerce','actionlog','security','chat','stats','join','calendar','search','point_charges','banners','point_gifts');
		shuffle($types);

		$events=array();
		$cnt=count($types);
		for ($i=0;$i<max($cnt,5);$i++)
		{
			$timestamp=mt_rand($from,$to);
			$type=array_pop($types);

			$event=rain_get_special_icons(get_ip_address(),$timestamp)+array(
				'TYPE'=>$type,
				'FROM_MEMBER_ID'=>NULL,
				'TO_MEMBER_ID'=>NULL,
				'TITLE'=>'Test',
				'IMAGE'=>rain_get_country_image(get_ip_address()),
				'TIMESTAMP'=>strval($timestamp),
				'RELATIVE_TIMESTAMP'=>strval($timestamp-$from),
				'TICKER_TEXT'=>NULL,
				'URL'=>NULL,
				'IS_POSITIVE'=>($type=='ecommerce' || $type=='join'),
				'IS_NEGATIVE'=>($type=='security' || $type=='point_charges'),

				// These are for showing connections between drops. They are not discriminated, it's just three slots to give an ID code that may be seen as a commonality with other drops.
				'FROM_ID'=>NULL,
				'TO_ID'=>NULL,
				'GROUP_ID'=>'example_'.strval(mt_rand(0,4)),
			);
			//if ($i==0)
			{
				$event['SPECIAL_ICON']='email-icon';
				$event['MULTIPLICITY']='10';
			}
			$events[]=$event;
		}
	} else
	{
		$events=get_realtime_events($from,$to);
	}

	shuffle($events);

	$out=new ocp_tempcode();
	foreach ($events as $event)
	{
		$out->attach(do_template('REALTIME_RAIN_BUBBLE',$event));
	}
	$out->evaluate_echo();
	echo '</result></request>';
}

/**
 * Get all the events within a timestamp range.
 *
 * @param  TIME			From time (inclusive).
 * @param  TIME			To time (inclusive).
 * @return array			List of template parameter sets (perfect for use in a Tempcode LOOP).
 */
function get_realtime_events($from,$to)
{
	//restrictify();

	$drops=array();

	$hooks=find_all_hooks('systems','realtime_rain');
	foreach (array_keys($hooks) as $hook)
	{
		require_code('hooks/systems/realtime_rain/'.filter_naughty($hook));
		$ob=object_factory('Hook_realtime_rain_'.$hook);
		$drops=array_merge($drops,$ob->run($from,$to));
	}

	return $drops;
}

/**
 * Make a realtime event bubble's title fit in the available space.
 *
 * @param  string			Idealised title.
 * @return tempcode		Cropped title, with tooltip for full title.
 */
function rain_truncate_for_title($text)
{
	return protect_from_escaping(symbol_truncator(array($text,'40','1'),'left'));
}

/**
 * Get a country flag image for an IP address.
 *
 * @param  IP				An IP address.
 * @return URLPATH		Country flag image (blank: could not find one).
 */
function rain_get_country_image($ip_address)
{
	if ($ip_address=='') return '';

	$country=geolocate_ip($ip_address);
	if (is_null($country)) return '';

	return 'http://ocportal.com/uploads/website_specific/flags/'.$country.'.gif';
}

/**
 * Returns a map with an icon and multiplicity parameter (that may be NULL).
 *
 * @param  ?IP				An IP address (used to check against bots) (NULL: no IP).
 * @param  TIME			A timestamp (used to check for logged sent emails).
 * @param  ?string		A user agent (used to check against phones) (NULL: no user agent).
 * @param  ?string		News ticker news (NULL: no news ticker news).
 * @return array			Map with an icon and multiplicity parameter.
 */
function rain_get_special_icons($ip_address,$timestamp,$user_agent=NULL,$news=NULL)
{
	$icon=NULL;
	$tooltip='';
	$multiplicity=1;
	$bot=get_bot_type();
	if (!is_null($bot))
	{
		$icon='searchengine-icon';
		$tooltip=do_lang('RTEV_BOT');
	} else
	{
		if ((!is_null($user_agent)) && (is_mobile($user_agent)))
		{
			$icon='phone-icon';
			$tooltip=do_lang('RTEV_PHONE');
		} else
		{
			$mails_sent=$GLOBALS['SITE_DB']->query_value('logged_mail_messages','COUNT(*)',array('m_date_and_time'=>$timestamp));
			if ($mails_sent>0)
			{
				$multiplicity=$mails_sent;
				$icon='email-icon';
				$tooltip=do_lang('RTEV_EMAILS',integer_format($multiplicity));
			} elseif (!is_null($news))
			{
				$icon='news-icon';
				$tooltip=do_lang('RTEV_NEWS');
			}
		}
	}

	return array('SPECIAL_ICON'=>$icon,'SPECIAL_TOOLTIP'=>$tooltip,'MULTIPLICITY'=>strval(min(20,$multiplicity)));
}
