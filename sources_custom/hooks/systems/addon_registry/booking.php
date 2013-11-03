<?php /*

 ocPortal
 Copyright (c) ocProducts, 2004-2014

 See text/EN/licence.txt for full licencing information.

*/

/**
 * @license		http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright	ocProducts Ltd
 * @package		booking
 */

class Hook_addon_registry_booking
{
	/**
	 * Get a list of file permissions to set
	 *
	 * @return array			File permissions to set
	 */
	function get_chmod_array()
	{
		return array();
	}

	/**
	 * Get the version of ocPortal this addon is for
	 *
	 * @return float			Version number
	 */
	function get_version()
	{
		return ocp_version_number();
	}

	/**
	 * Get the addon category
	 *
	 * @return string			The category
	 */
	function get_category()
	{
		return 'Development';
	}

	/**
	 * Get the addon author
	 *
	 * @return string			The author
	 */
	function get_author()
	{
		return 'Chris Graham';
	}

	/**
	 * Find other authors
	 *
	 * @return array			A list of co-authors that should be attributed
	 */
	function get_copyright_attribution()
	{
		return array();
	}

	/**
	 * Get the addon licence (one-line summary only)
	 *
	 * @return string			The licence
	 */
	function get_licence()
	{
		return 'Licensed on the same terms as ocPortal';
	}

	/**
	 * Get the description of the addon
	 *
	 * @return string			Description of the addon
	 */
	function get_description()
	{
		return 'Sophisticated booking system. Not yet fully finished for public use, but fully cohesive and suitable for some use cases.

You may wish to deny access to the usergroup and member directories when using this addon, to prevent leakage of customer lists.';
	}

	/**
	 * Get a list of tutorials that apply to this addon
	 *
	 * @return array			List of tutorials
	 */
	function get_applicable_tutorials()
	{
		return array(
		);
	}

	/**
	 * Get a mapping of dependency types
	 *
	 * @return array			File permissions to set
	 */
	function get_dependencies()
	{
		return array(
			'requires'=>array(
			),
			'recommends'=>array(
			),
			'conflicts_with'=>array(
			)
		);
	}

	/**
	 * Get a list of files that belong to this addon
	 *
	 * @return array			List of files
	 */
	function get_file_list()
	{
		return array(
			'themes/default/images_custom/icons/24x24/menu/blacked.png',
			'themes/default/images_custom/icons/24x24/menu/bookable.png',
			'themes/default/images_custom/icons/24x24/menu/booking.png',
			'themes/default/images_custom/icons/24x24/menu/supplement.png',
			'themes/default/images_custom/icons/48x48/menu/blacked.png',
			'themes/default/images_custom/icons/48x48/menu/bookable.png',
			'themes/default/images_custom/icons/48x48/menu/booking.png',
			'themes/default/images_custom/icons/48x48/menu/supplement.png',
			'sources_custom/hooks/systems/addon_registry/booking.php',
			'sources_custom/hooks/systems/notifications/booking_customer.php',
			'sources_custom/hooks/systems/notifications/booking_inform_staff.php',
			'cms/pages/modules_custom/cms_booking.php',
			'sources_custom/booking.php',
			'sources_custom/booking2.php',
			'sources_custom/bookings_ical.php',
			'site/pages/modules_custom/booking.php',
			'sources_custom/blocks/side_book_date_range.php',
			'sources_custom/blocks/side_choose_showing.php',
			'sources_custom/blocks/main_choose_to_book.php',
			'sources_custom/hooks/modules/members/booking.php',
			'themes/default/templates_custom/BLOCK_SIDE_BOOK_DATE_RANGE.tpl',
			'themes/default/templates_custom/BLOCK_SIDE_CHOOSE_SHOWING.tpl',
			'themes/default/templates_custom/BLOCK_MAIN_CHOOSE_TO_BOOK.tpl',
			'themes/default/templates_custom/JAVASCRIPT_BOOKING.tpl',
			'data_custom/bookings_ical.php',
			'data_custom/bookables_ical.php',
			'lang_custom/EN/booking.ini',
			'data_custom/booking_price_ajax.php',
			'themes/default/templates_custom/BOOKING_CONFIRM_FCOMCODE.tpl',
			'themes/default/templates_custom/BOOKING_NOTICE_FCOMCODE.tpl',
			'themes/default/templates_custom/BOOKING_DISPLAY.tpl',
			'themes/default/templates_custom/BOOKING_START_SCREEN.tpl',
			'themes/default/templates_custom/BOOKING_FLESH_OUT_SCREEN.tpl',
			'themes/default/templates_custom/BOOKING_JOIN_OR_LOGIN_SCREEN.tpl',
			'themes/default/templates_custom/BOOK_DATE_CHOOSE.tpl',
			'themes/default/templates_custom/BOOKABLE_NOTES.tpl',
			'sources_custom/hooks/systems/do_next_menus/booking.php',
			'themes/default/images_custom/calendar/booking.png',
			'themes/default/images_custom/calendar/index.html',
			'sources_custom/hooks/systems/config/bookings_max_ahead_months.php',
			'sources_custom/hooks/systems/config/bookings_show_warnings_for_months.php',
			'sources_custom/hooks/systems/config/member_booking_only.php',
		);
	}
}