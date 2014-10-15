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
 * @package		ecommerce
 */

class Hook_ocf_cpf_filter_ecommerce
{
    /**
	 * Find which special CPFs to enable.
	 *
	 * @return array			A list of CPFs to enable
	 */
    public function to_enable()
    {
        $cpf = array();

        // General payment details
// Not configurable per-member yet
//		$cpf=array_merge($cpf,array('currency'=>1,));

        // Local payment
        if (get_option('use_local_payment') == '1') {
            $cpf = array_merge($cpf,array('payment_type' => 1,'payment_cardholder_name' => 1,'payment_card_type' => 1,'payment_card_number' => 1,'payment_card_start_date' => 1,'payment_card_expiry_date' => 1,'payment_card_issue_number' => 1,'payment_card_cv2' => 1,));
        }

        return $cpf;
    }
}
