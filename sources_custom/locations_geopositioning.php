<?php

function init__locations_geopositioning()
{
	define('MIN_LAT',deg2rad(-90.0));
	define('MAX_LAT',deg2rad(90.0));
	define('MIN_LON',deg2rad(-180.0));
	define('MAX_LON',deg2rad(180.0));

	define('EARTH_RADIUS',6371010.0); // In m

	define('GEO_SEARCH_EXPANSION_FACTOR',1.3);
}

function fix_geoposition($lstring,$category_id)
{
	$type='yahoo';

	$lstring=preg_replace('#, (Africa|Americas|Asia|Europe|Oceania)$#','',$lstring); // Confuses Bing

	// Web service to get remaining latitude/longitude
	if ($type=='bing')
	{
		$url='http://dev.virtualearth.net/REST/v1/Locations?query='.urlencode($lstring).'&o=xml&key=AvmgsVWtIoJeCnZXdDnu3dQ7izV9oOowHCNDwbN4R1RPA9OXjfsQX1Cr9HSrsY4j';
	} elseif ($type=='yahoo')
	{
		$url='http://where.yahooapis.com/geocode?q='.urlencode($lstring).'&appid=dj0yJmk9N0x3TTdPaDNvdElCJmQ9WVdrOWFGWjVOa3hzTldFbWNHbzlNVFU0TXpBMU9EWTJNZy0tJnM9Y29uc3VtZXJzZWNyZXQmeD1mNg--';
	} elseif ($type=='google')
	{
		$url='http://maps.googleapis.com/maps/api/geocode/xml?address='.urlencode($lstring).'&sensor=false';
	} else exit('unknown type');
	$result=http_download_file($url);
	$matches=array();
	if ((($type=='bing') && (preg_match('#<Latitude>([\-\d\.]+)</Latitude>\s*<Longitude>([\-\d\.]+)</Longitude>#',$result,$matches)!=0)) || (($type=='google') && (preg_match('#<lat>([\-\d\.]+)</lat>\s*<lng>([\-\d\.]+)</lng>#',$result,$matches)!=0)) || (($type=='yahoo') && (preg_match('#<latitude>([\-\d\.]+)</latitude>\s*<longitude>([\-\d\.]+)</longitude>#',$result,$matches)!=0)))
	{
		$latitude=floatval($matches[1]);
		$longitude=floatval($matches[2]);

		$fields=$GLOBALS['SITE_DB']->query_select('catalogue_fields',array('*'),array('c_name'=>'_catalogue_category'),'ORDER BY cf_order');
		require_code('content');
		require_code('fields');
		$assocated_catalogue_entry_id=get_bound_content_entry('catalogue_category',strval($category_id));

		$GLOBALS['SITE_DB']->query_update('catalogue_efv_float',array('cv_value'=>$latitude),array('ce_id'=>$assocated_catalogue_entry_id,'cf_id'=>$fields[0]['id']),'',1);
		$GLOBALS['SITE_DB']->query_update('catalogue_efv_float',array('cv_value'=>$longitude),array('ce_id'=>$assocated_catalogue_entry_id,'cf_id'=>$fields[1]['id']),'',1);

		return '1';
	}
	return '0';
}

function find_nearest_location($latitude,$longitude,$latitude_field_id=NULL,$longitude_field_id=NULL,$error_tolerance=NULL)
{
	if (is_null($error_tolerance)) // Ah, pick a default
	{
		$error_tolerance=50.0/EARTH_RADIUS; // 50 metres radius
	}

	// ====
	// Much help from http://janmatuschek.de/LatitudeLongitudeBoundingCoordinates

	$radLat=deg2rad($latitude);
	$radLon=deg2rad($longitude);

	$minLat=$radLat-$error_tolerance;
	$maxLat=$radLat+$error_tolerance;

	if ($minLat>MIN_LAT && $maxLat<MAX_LAT)
	{
		$deltaLon=asin(sin($error_tolerance)/cos($radLat));
		$minLon=$radLon-$deltaLon;
		if ($minLon<MIN_LON) $minLon+=2.0*M_PI;
		$maxLon=$radLon+$deltaLon;
		if ($maxLon>MAX_LON) $maxLon-=2.0*M_PI;
	} else
	{
		// a pole is within the distance
		$minLat=max($minLat,MIN_LAT);
		$maxLat=min($maxLat,MAX_LAT);
		$minLon=MIN_LON;
		$maxLon=MAX_LON;
	}

	$meridian180WithinDistance=($minLon>$maxLon);

	$minLat=rad2deg($minLat);
	$maxLat=rad2deg($maxLat);
	$minLon=rad2deg($minLon);
	$maxLon=rad2deg($maxLon);

	$where='(l_latitude>='.float_to_raw_string($minLat,10).' AND l_latitude<='.float_to_raw_string($maxLat,10).') AND (l_longitude>='.float_to_raw_string($minLon,10).' '.
				($meridian180WithinDistance?'OR':'AND').' l_longitude<='.float_to_raw_string($maxLon,10).') AND '.
				'acos(sin('.float_to_raw_string($radLat,10).')*sin(radians(l_latitude))+cos('.float_to_raw_string($radLat,10).')*cos(radians(l_latitude))*cos(radians(l_longitude)-'.float_to_raw_string($radLon,10).'))<='.float_to_raw_string($error_tolerance,10);

	// ==== ^^^

	if ((is_null($latitude_field_id)) || (is_null($longitude_field_id))) // Just do a raw query on locations table
	{
		$query='SELECT * FROM '.get_table_prefix().'locations WHERE '.$where;
		$locations=$GLOBALS['SITE_DB']->query($query);
	} else // Catalogue query (works both for entries and categories that use custom fields)
	{
		$where=str_replace(array('l_latitude','l_longitude'),array('a.cv_value','b.cv_value'),$where);
		$query='SELECT a.ce_id,c.id,cc_title,a.cv_value AS l_latitude,b.cv_value AS l_longitude FROM '.get_table_prefix().'catalogue_efv_float a JOIN '.get_table_prefix().'catalogue_efv_float b ON a.ce_id=b.ce_id AND a.cf_id='.strval($latitude_field_id).' AND b.cf_id='.strval($longitude_field_id).' LEFT JOIN '.get_table_prefix().'catalogue_entry_linkage x ON a.ce_id=x.catalogue_entry_id AND '.db_string_equal_to('x.content_type','catalogue_category').' LEFT JOIN '.get_table_prefix().'catalogue_categories c ON c.id=x.content_id WHERE '.$where;
		$locations=$GLOBALS['SITE_DB']->query($query,NULL,NULL,false,false,array('cc_title'=>'SHORT_TRANS'));
	}

	if (count($locations)==0)
	{
		if ($error_tolerance>=1.0)
		{
			return NULL; // Nothing, in whole world
		}

		return find_nearest_location($latitude,$longitude,$latitude_field_id,$longitude_field_id,$error_tolerance*GEO_SEARCH_EXPANSION_FACTOR);
	}

	$best=mixed();
	$best_at=mixed();
	foreach ($locations as $l)
	{
		$dist=latlong_distance_miles($l['l_latitude'],$l['l_longitude'],$latitude,$longitude);

		if ((is_null($best)) || ($dist<$best))
		{
			$best=$dist;
			$best_at=$l;
		}
	}
	$locations=array($best_at);

	return $locations[0];
}

function latlong_distance_miles($lat1,$lng1,$lat2,$lng2,$miles=true)
{
	$pi80 = M_PI / 180;
	$lat1 *= $pi80;
	$lng1 *= $pi80;
	$lat2 *= $pi80;
	$lng2 *= $pi80;
 
	$r = 6372.797; // mean radius of Earth in km
	$dlat = $lat2 - $lat1;
	$dlng = $lng2 - $lng1;
	$a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	$km = $r * $c;
 
	return ($miles ? ($km * 0.621371192) : $km);
}
