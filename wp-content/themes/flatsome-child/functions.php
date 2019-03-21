<?php
/**
 * Enqueue scripts and styles.
 */
add_action('wp_enqueue_scripts', 'flatsome_qeoqeo_scripts');
function flatsome_qeoqeo_scripts()
{
    // FontAwesome
    wp_enqueue_style('fontawesome', 'https://use.fontawesome.com/releases/v5.6.3/css/all.css');
}

// Mail configuration
add_action('phpmailer_init', 'qeoqeo_send_smtp_email');
function qeoqeo_send_smtp_email($phpmailer)
{
    $phpmailer->isSMTP();
    $phpmailer->Host = SMTP_HOST;
    $phpmailer->SMTPAuth = SMTP_AUTH;
    $phpmailer->Port = SMTP_PORT;
    $phpmailer->SMTPSecure = SMTP_SECURE;
    $phpmailer->Username = SMTP_USERNAME;
    $phpmailer->Password = SMTP_PASSWORD;
    $phpmailer->From = SMTP_FROM;
    $phpmailer->FromName = SMTP_FROMNAME;
}

// Format free shipping on order detail items
add_filter('woocommerce_order_shipping_to_display', 'qeoqeo_format_free_shipping');
add_filter('woocommerce_cart_shipping_method_full_label', 'qeoqeo_format_free_shipping');
function qeoqeo_format_free_shipping($shipping)
{
    if ($shipping === 'Phí ship') {
        return __('Free ship 🤩', 'woocommerce');
    }
    return $shipping;
}

// Remove dashboard myaccount and redirect to order
add_action('parse_request', 'qeoqeo_redirect_to_my_account_orders');
function qeoqeo_redirect_to_my_account_orders($wp)
{
    // All other endpoints such as change-password will redirect to
    // my-account/orders
    $allowed_endpoints = ['orders', 'edit-account', 'lost-password', 'customer-logout'];

    if (
        preg_match('%^my\-account(?:/([^/]+)|)/?$%', $wp->request, $m) &&
        (empty($m[1]) || !in_array($m[1], $allowed_endpoints))
    ) {
        wp_redirect('/my-account/orders/');
        exit;
    }
}

add_filter('woocommerce_account_menu_items', 'qeoqeo_account_menu_items');
function qeoqeo_account_menu_items($items)
{
    unset($items['dashboard']);
    return $items;
}

// Logout without confirmation
add_action('template_redirect', 'qeoqeo_wc_bypass_logout_confirmation');
function qeoqeo_wc_bypass_logout_confirmation()
{
    global $wp;

    if (isset($wp->query_vars['customer-logout'])) {
        wp_redirect(str_replace('&amp;', '&', wp_logout_url(wc_get_page_permalink('myaccount'))));
        exit;
    }
}

// Check and validate customer account field
add_action('woocommerce_save_account_details_errors', 'qeoqeo_billing_field_validation', 20, 1);
function qeoqeo_billing_field_validation($args)
{
    if (isset($_POST['billing_phone']) && empty($_POST['billing_phone'])) {
        $args->add('error', __('<strong>Mobile phone</strong> is a required field.', 'flatsome'), '');
    }

    if (isset($_POST['billing_address_1']) && empty($_POST['billing_address_1'])) {
        $args->add('error', __('<strong>Address</strong> is a required field.', 'flatsome'), '');
    }
}

// Save value to user data
add_action('woocommerce_save_account_details', 'qeoqeo_my_account_saving_billing_info', 20, 1);
function qeoqeo_my_account_saving_billing_info($user_id)
{
    if (isset($_POST['billing_phone']) && !empty($_POST['billing_phone'])) {
        update_user_meta($user_id, 'billing_phone', sanitize_text_field($_POST['billing_phone']));
    }

    if (isset($_POST['billing_address_1']) && !empty($_POST['billing_address_1'])) {
        update_user_meta($user_id, 'billing_address_1', sanitize_text_field($_POST['billing_address_1']));
    }

}

// Remove required field requirement for first/last name in My Account Edit form
add_filter('woocommerce_save_account_details_required_fields', 'qeoqeo_remove_required_fields');
function qeoqeo_remove_required_fields($required_fields)
{
    unset($required_fields['account_first_name']);
    return $required_fields;
}

// Checkout / Account Fields
add_filter('woocommerce_checkout_fields', 'qeoqeo_order_fields');
function qeoqeo_order_fields($fields)
{
    //Shipping
    $order_billing = array(
        "billing_last_name",
        "billing_phone",
        "billing_email",
        "billing_country"
    );
    foreach ($order_billing as $field_billing) {
        $ordered_fields2[$field_billing] = $fields["billing"][$field_billing];
    }
    $fields["billing"] = $ordered_fields2;
    return $fields;
}

add_filter('woocommerce_checkout_fields', 'qeoqeo_custom_override_checkout_fields', 99);
function qeoqeo_custom_override_checkout_fields($fields)
{
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_first_name']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_state']);
    unset($fields['billing']['billing_address_2']);

    $fields['billing']['billing_last_name'] = array(
        'label' => __('Họ và tên', 'flatsome'),
        'placeholder' => _x('Nhập đầy đủ họ và tên của bạn', 'placeholder', 'flatsome'),
        'required' => true,
        'class' => array('form-row-wide'),
        'clear' => true
    );

    $fields['billing']['billing_phone']['placeholder'] = 'Nhập số điện thoại của bạn';
    $fields['billing']['billing_email']['placeholder'] = 'Nhập email của bạn';

    $fields['billing']['billing_address_1']['label'] = "Địa chỉ";
    $fields['billing']['billing_address_1']['required'] = true;
    $fields['billing']['billing_address_1']['placeholder'] = 'Ví dụ: Số 69 đường XYZ, phường 6, quận 9, Tp.HCM';

    return $fields;
}

add_filter('woocommerce_order_formatted_billing_address', 'qeoqeo_order_formatted_billing_address');
function qeoqeo_order_formatted_billing_address($billing)
{
    if (!is_admin()) {
        $billing['last_name'] = '';
    }
    return $billing;
}

// New order status: Shipping
add_action('init', 'qeoqeo_register_my_new_order_statuses');
function qeoqeo_register_my_new_order_statuses()
{
    register_post_status('wc-shipping', array(
        'label' => _x('Shipping', 'Order status', 'flatsome'),
        'public' => true,
        'exclude_from_search' => false,
        'show_in_admin_all_list' => true,
        'show_in_admin_status_list' => true,
        'label_count' => _n_noop('Shipping <span class="count">(%s)</span>', 'Shipping<span class="count">(%s)</span>', 'flatsome')
    ));
}

// Register in wc_order_statuses.
add_filter('wc_order_statuses', 'qeoqeo_my_new_wc_order_statuses');
function qeoqeo_my_new_wc_order_statuses($order_statuses)
{
    $order_statuses['wc-shipping'] = _x('Shipping', 'Order status', 'flatsome');
    return $order_statuses;
}

// Apply coupon to all cart by coupon code
// see https://businessbloomer.com/woocommerce-apply-coupon-programmatically-product-cart/
add_action('woocommerce_before_cart', 'qeoqeo_apply_coupon');
function qeoqeo_apply_coupon()
{
    $coupon_code = 'OPENINGWEEK';
    if (WC()->cart->has_discount($coupon_code)) return;
    WC()->cart->add_discount($coupon_code);
    wc_print_notices();
}