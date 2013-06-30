<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2013

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		core_ocf
 */

/* This file is designed to be overwritten by addons that implement external user sync schemes. */

/**
 * Find is a field is editable.
 * Called for fields that have a fair chance of being set to auto-sync, and hence be locked to local edits.
 *
 * @param  ID_TEXT		Field name
 * @param  ID_TEXT		The special type of the user (built-in types are: <blank>, ldap, httpauth, <name of import source>)
 * @return boolean		Whether the field is editable
 */
function ocf_field_editable($field_name,$special_type)
{
	switch ($field_name)
	{
		case 'username':
			switch ($special_type)
			{
				case 'ldap':
					return false;
			}
			break;

		case 'password':
			switch ($special_type)
			{
				case 'ldap':
				case 'httpauth':
					return false;
			}
			break;

		case 'primary_group':
			switch ($special_type)
			{
				case 'ldap':
					return false;
			}
			break;

		case 'secondary_groups':
			switch ($special_type)
			{
				case 'ldap':
					return false;
			}
			break;
	}

	return true;
}