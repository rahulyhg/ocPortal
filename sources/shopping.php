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
 * @package		shopping
 */

/**
 * Get product details array, according to the hook specified in the 'hook' GET parameter
 *
 * @return array	Product details
 */
function get_product_details()
{
    $_hook = get_param('hook');

    require_code('hooks/systems/ecommerce/' . filter_naughty_harsh($_hook));

    $object = object_factory('Hook_' . filter_naughty_harsh($_hook));

    $products = $object->get_product_details();

    return $products;
}

/**
 * Function to add new item to cart.
 *
 * @param  array	Product details
 */
function add_to_cart($product_det)
{
    $_hook = get_param('hook');

    require_code('hooks/systems/ecommerce/' . filter_naughty_harsh($_hook));

    $object = object_factory('Hook_' . filter_naughty_harsh($_hook));

    $object->add_order($product_det);
}

/**
 * Update cart
 *
 * @param  array	Product details
 */
function update_cart($product_det)
{
    foreach ($product_det as $product_row) {
        $where = array('product_id' => $product_row['product_id'],'is_deleted' => 0);
        if (is_guest()) {
            $where['session_id'] = get_session_id();
        } else {
            $where['ordered_by'] = get_member();
        }

        if ($product_row['quantity']>0) {
            $GLOBALS['SITE_DB']->query_update(
                'shopping_cart',
                array('quantity' => $product_row['quantity']),
                $where
            );
        } else {
            $GLOBALS['SITE_DB']->query_delete(
                'shopping_cart',
                $where
            );
        }
    }

    // Update tax opt out status to the current order
    if (get_option('allow_opting_out_of_tax') == '1') {
        $order_id = get_current_order_id();
        $tax_opted_out = post_param_integer('tax_opted_out',0);
        $GLOBALS['SITE_DB']->query_update('shopping_order',array('tax_opted_out' => $tax_opted_out),array('id' => $order_id),'',1);
    }
}

/**
 * Remove from cart.
 *
 * @param  array	Products to remove
 */
function remove_from_cart($product_to_remove)
{
    foreach ($product_to_remove as $product_id) {
        $where = array('product_id' => $product_id);
        if (is_guest()) {
            $where['session_id'] = get_session_id();
        } else {
            $where['ordered_by'] = get_member();
        }

        $GLOBALS['SITE_DB']->query_update(
            'shopping_cart',
            array('is_deleted' => 1),
            $where
        );
    }
}

/**
 * Log cart actions
 *
 * @param  ID_TEXT	The data
 */
function log_cart_actions($action)
{
    $where = array();
    if (is_guest()) {
        $where['session_id'] = get_session_id();
    } else {
        $where['e_member_id'] = get_member();
    }

    $id = $GLOBALS['SITE_DB']->query_select_value_if_there('shopping_logging','id',$where);

    if (is_null($id)) {
        $GLOBALS['SITE_DB']->query_insert(
            'shopping_logging',
            array(
                'e_member_id' => get_member(),
                'session_id' => get_session_id(),
                'ip' => get_ip_address(),
                'last_action' => $action,
                'date_and_time' => time()
            )
        );
    } else {
        $GLOBALS['SITE_DB']->query_update(
            'shopping_logging',
            array(
                'last_action' => $action,
                'date_and_time' => time()
            )
        );
    }
}

/**
 * Delete incomplete orders from ages ago.
 */
function delete_incomplete_orders()
{
    // Delete any 2-week+ old orders
    $GLOBALS['SITE_DB']->query("DELETE t1,t2 FROM " . get_table_prefix() . "shopping_order t1, " . get_table_prefix() . "shopping_order_details t2 WHERE t1.id=t2.order_id AND t1.order_status='ORDER_STATUS_awaiting_payment' AND add_date<" . strval(time()-60*60*24*14));
}

/**
 * Show cart link
 *
 * @return tempcode
 */
function show_cart_link()
{
    $cart_url = build_url(array('page' => 'shopping','type' => 'misc'),get_module_zone('shopping'));

    $where = array('is_deleted' => 0);
    if (is_guest()) {
        $where['session_id'] = get_session_id();
    } else {
        $where['ordered_by'] = get_member();
    }
    $item_count = $GLOBALS['SITE_DB']->query_select_value_if_there('shopping_cart','count(*)',$where);

    if ($item_count>0) {
        $title = do_lang_tempcode('BUTTON_CART_ITEMS',strval($item_count));
    } else {
        $title = do_lang_tempcode('BUTTON_CART_EMPTY');
    }

    return do_template('ECOM_CART_LINK',array('_GUID' => '46ae00c8a605b84fee1b1c68fc57cd32','URL' => $cart_url,'ITEMS' => strval($item_count),'TITLE' => $title));
}

/**
 * Tell the staff the shopping order was placed
 *
 * @param  AUTO_LINK		Order ID
 */
function purchase_done_staff_mail($order_id)
{
    $member_id = $GLOBALS['SITE_DB']->query_select_value('shopping_order','c_member',array('id' => $order_id));
    $displayname = $GLOBALS['FORUM_DRIVER']->get_username($member_id,true);
    $username = $GLOBALS['FORUM_DRIVER']->get_username($member_id);
    $subject = do_lang('ORDER_PLACED_MAIL_SUBJECT',get_site_name(),strval($order_id),array($displayname,$username),get_site_default_lang());
    $message = do_lang('ORDER_PLACED_MAIL_MESSAGE',comcode_escape(get_site_name()),comcode_escape($displayname),array(strval($order_id),strval($member_id),comcode_escape($username)),get_site_default_lang());
    require_code('notifications');
    dispatch_notification('new_order',null,$subject,$message);
}

/**
 * Find products in cart
 *
 * @return array	Product details in cart
 */
function find_products_in_cart()
{
    $where = array('is_deleted' => 0);
    if (is_guest()) {
        $where['session_id'] = get_session_id();
    } else {
        $where['ordered_by'] = get_member();
    }
    $cart = $GLOBALS['SITE_DB']->query_select('shopping_cart',array('*'),$where);

    if (!array_key_exists(0,$cart)) {
        return array();
    }

    return $cart;
}

/**
 * Stock maintain warning mail
 *
 * @param  SHORT_TEXT	Product name
 * @param  AUTO_LINK		Product ID
 */
function stock_maintain_warn_mail($product_name,$product_id)
{
    $product_info_url = build_url(array('page' => 'catalogues','type' => 'entry','id' => $product_id),get_module_zone('catalogues'));

    $subject = do_lang('STOCK_LEVEL_MAIL_SUBJECT',get_site_name(),$product_name,null,get_site_default_lang());
    $message = do_lang('STOCK_MAINTENANCE_WARN_MAIL',comcode_escape(get_site_name()),comcode_escape($product_name),array($product_info_url->evaluate()),get_site_default_lang());

    require_code('notifications');
    dispatch_notification('low_stock',null,$subject,$message,null,null,A_FROM_SYSTEM_PRIVILEGED);
}

/**
 * Stock reduction
 *
 * @param  AUTO_LINK		The ID
 */
function update_stock($order_id)
{
    $row = $GLOBALS['SITE_DB']->query_select('shopping_order_details',array('*'),array('order_id' => $order_id),'',1);

    foreach ($row as $ordered_items) {
        $hook = $ordered_items['p_type'];

        require_code('hooks/systems/ecommerce/' . filter_naughty_harsh($hook),true);

        $object = object_factory('Hook_' . $hook,true);
        if (is_null($object)) {
            continue;
        }

        if (method_exists($object,'update_stock')) {
            $object->update_stock($ordered_items['p_id'],$ordered_items['p_quantity']);
        }
    }
}

/**
 * Delete cart contents for the current user.
 *
 * @param  boolean		Whether to just do a soft delete, i.e. mark as deleted.
 */
function empty_cart($soft_delete = false)
{
    $where = array();
    if (is_guest()) {
        $where['session_id'] = get_session_id();
    } else {
        $where['ordered_by'] = get_member();
    }
    if ($soft_delete) {
        $GLOBALS['SITE_DB']->query_update('shopping_cart',array('is_deleted' => 1),$where);
    } else {
        $GLOBALS['SITE_DB']->query_delete('shopping_cart',$where);
    }
}

/**
 * Delete any pending orders for the current user. E.g. if cart purchase was cancelled, or cart was changed.
 */
function delete_pending_orders_for_current_user()
{
    $where = array('order_status' => 'ORDER_STATUS_awaiting_payment');
    if (is_guest()) {
        $where['session_id'] = get_session_id();
    } else {
        $where['c_member'] = get_member();
    }
    $orders = $GLOBALS['SITE_DB']->query_select('shopping_order',array('id'),$where);
    foreach ($orders as $order) {
        $GLOBALS['SITE_DB']->query_delete('shopping_order_details',array('order_id' => $order['id']));
        $GLOBALS['SITE_DB']->query_delete('shopping_order',array('id' => $order['id']),'',1);
    }
}

/**
 * Payment step.
 *
 * @return tempcode	The result of execution.
 */
function payment_form()
{
    require_code('ecommerce');

    $title = get_screen_title('PAYMENT_HEADING');

    $cart_items = find_products_in_cart();

    $purchase_id = null;

    $tax_opt_out = get_order_tax_opt_out_status();

    if (count($cart_items)>0) {
        $insert = array(
            'c_member' => get_member(),
            'session_id' => get_session_id(),
            'add_date' => time(),
            'tot_price' => 0,
            'order_status' => 'ORDER_STATUS_awaiting_payment',
            'notes' => '',
            'purchase_through' => 'cart',
            'transaction_id' => '',
            'tax_opted_out' => $tax_opt_out,
        );

        if (is_null($GLOBALS['SITE_DB']->query_select_value_if_there('shopping_order','id'))) {
            $insert['id'] = hexdec('1701D'); // Start offset
        }

        $order_id = $GLOBALS['SITE_DB']->query_insert('shopping_order',$insert,true);
    } else {
        $order_id = null;
    }

    $total_price = 0;

    foreach ($cart_items as $item) {
        $type_code = $item['product_id'];

        $hook = $item['product_type'];

        require_code('hooks/systems/ecommerce/' . filter_naughty_harsh($hook),true);

        $object = object_factory('Hook_' . filter_naughty_harsh($hook),true);
        if (is_null($object)) {
            continue;
        }

        $temp = $object->get_products(false,$type_code);

        if ($temp[$type_code][0] == PRODUCT_SUBSCRIPTION) {
            continue;
        }    //Subscription type skipped.

        $price = $temp[$type_code][1];

        $item_name = $temp[$type_code][4];

        if (method_exists($object,'set_needed_fields')) {
            $purchase_id = $object->set_needed_fields($type_code);
        } else {
            $purchase_id = strval(get_member());
        }

        $length = null;

        $length_units = '';

        if (method_exists($object,'calculate_product_price')) {
            $price = $object->calculate_product_price($item['price'],$item['price_pre_tax'],$item['product_weight']);
        } else {
            $price = $item['price'];
        }

        if (method_exists($object,'calculate_tax') && ($tax_opt_out == 0)) {
            $tax = round($object->calculate_tax($item['price'],$item['price_pre_tax']),2);
        } else {
            $tax = 0.0;
        }

        $GLOBALS['SITE_DB']->query_insert(
            'shopping_order_details',
            array(
                'p_id' => $item['product_id'],
                'p_name' => $item['product_name'],
                'p_code' => $item['product_code'],
                'p_type' => $item['product_type'],
                'p_quantity' => $item['quantity'],
                'p_price' => $price,
                'included_tax' => $tax,
                'order_id' => $order_id,
                'dispatch_status' => '',
            ),
            true
        );

        $total_price += $price*$item['quantity'];
    }

    $GLOBALS['SITE_DB']->query_update('shopping_order',array('tot_price' => $total_price),array('id' => $order_id),'',1);


    if (!perform_local_payment()) { // Pass through to the gateway's HTTP server
        $result = make_cart_payment_button($order_id,get_option('currency'));
    } else { // Handle the transaction internally
        if (((ocp_srv('HTTPS') == '') || (ocp_srv('HTTPS') == 'off')) && (!ecommerce_test_mode())) {
            warn_exit(do_lang_tempcode('NO_SSL_SETUP'));
        }

        $price = $GLOBALS['SITE_DB']->query_select_value('shopping_order','tot_price',array('id' => $order_id));
        $item_name = do_lang('CART_ORDER',strval($order_id));
        if (is_null($order_id)) {
            $fields = new ocp_tempcode();
            $hidden = new ocp_tempcode();
        } else {
            list($fields,$hidden) = get_transaction_form_fields(null,strval($order_id),$item_name,float_to_raw_string($price),null,'');
        }

        $finish_url = build_url(array('page' => 'purchase','type' => 'finish'),get_module_zone('purchase'));

        $result = do_template('PURCHASE_WIZARD_STAGE_TRANSACT',array('_GUID' => 'a70d6995baabb7e41e1af68409361f3c','FIELDS' => $fields,'HIDDEN' => $hidden));

        require_javascript('javascript_validation');

        return do_template('PURCHASE_WIZARD_SCREEN',array('_GUID' => 'dfc7b8460e81dfd6d083e5f5d2b606a4','TITLE' => $title,'CONTENT' => $result,'URL' => $finish_url));
    }

    return $result;
}

/**
 * Find current order tax opt out status
 *
 * @return  BINARY		Tax opt out status of current order
 */
function get_order_tax_opt_out_status()
{
    if (get_param('page','') == 'purchase') {
        return 0;
    }    //Purchase module creates separate orders for every product, so optout status depending only on current value of checkbox.

    $where = array();
    if (is_guest()) {
        $where['session_id'] = get_session_id();
    } else {
        $where['c_member'] = get_member();
    }
    $row = $GLOBALS['SITE_DB']->query_select('shopping_order',array('tax_opted_out'),$where,'ORDER BY add_date DESC',1);

    if (!array_key_exists(0,$row)) {
        return 0;
    } else {
        return $row[0]['tax_opted_out'];
    }
}

/**
 * Find current order ID
 *
 * @return  AUTO_LINK		Order ID
 */
function get_current_order_id()
{
    $where = array();
    if (is_guest()) {
        $where['session_id'] = get_session_id();
    } else {
        $where['c_member'] = get_member();
    }
    $row = $GLOBALS['SITE_DB']->query_select('shopping_order',array('id'),$where,'ORDER BY add_date DESC',1);

    if (!array_key_exists(0,$row)) {
        return 0;
    } else {
        return $row[0]['id'];
    }
}

/**
 * Return list entry of common order statuses of orders
 *
 * @return  tempcode		Order status list entries
 */
function get_order_status_list()
{
    $status_list = new ocp_tempcode();
    $status = array(
        'ORDER_STATUS_awaiting_payment' => do_lang_tempcode('ORDER_STATUS_awaiting_payment'),
        'ORDER_STATUS_payment_received' => do_lang_tempcode('ORDER_STATUS_payment_received'),
        'ORDER_STATUS_dispatched' => do_lang_tempcode('ORDER_STATUS_dispatched'),
        'ORDER_STATUS_onhold' => do_lang_tempcode('ORDER_STATUS_onhold'),
        'ORDER_STATUS_cancelled' => do_lang_tempcode('ORDER_STATUS_cancelled'),
        'ORDER_STATUS_returned' => do_lang_tempcode('ORDER_STATUS_returned'),
    );

    $status_list->attach(form_input_list_entry('all',false,do_lang_tempcode('NA')));

    foreach ($status as $key => $values) {
        $status_list->attach(form_input_list_entry($key,false,$values));
    }
    return $status_list;
}

/**
 * Return a string of order products to export as csv
 *
 * @param  AUTO_LINK		Order ID
 * @return LONG_TEXT		Products names and quantity
 */
function get_ordered_product_list_string($order_id)
{
    $product_det = array();

    $row = $GLOBALS['SITE_DB']->query_select('shopping_order_details',array('*'),array('order_id' => $order_id));

    foreach ($row as $key => $product) {
        $product_det[] = $product['p_name'] . ' x ' . integer_format($product['p_quantity']) . ' @ ' . do_lang('UNIT_PRICE') . '=' . float_format($product['p_price']);
    }

    return implode("\n",$product_det);
}
