<?php
/**
 * Shipping Methods Display
 *
 * In 2.1 we show methods per package. This allows for multiple methods per order if so desired.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/cart/cart-shipping.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.0
 */

defined('ABSPATH') || exit;

$formatted_destination = isset($formatted_destination) ? $formatted_destination : WC()->countries->get_formatted_address($package['destination'], ', ');
$has_calculated_shipping = !empty($has_calculated_shipping);
$show_shipping_calculator = !empty($show_shipping_calculator);
$calculator_text = '';
?>

<tr class="woocommerce-shipping-totals shipping
	<?php if (get_theme_mod('cart_boxed_shipping_labels', 0) && 1 < count($available_methods)) echo 'shipping--boxed'; ?>">
    <td class="shipping__inner" colspan="2">
        <table class="shipping__table <?php if (1 < count($available_methods)) : ?>shipping__table--multiple<?php endif; ?>">
            <tbody>
            <tr>
                <th <?php if (1 < count($available_methods)) : ?> colspan="2" <?php endif; ?>><?php echo wp_kses_post($package_name); ?></th>
                <td data-title="<?php echo esc_attr($package_name); ?>">
                    <?php if ($available_methods) : ?>
                        <ul id="shipping_method" class="shipping__list woocommerce-shipping-methods">
                            <?php foreach ($available_methods as $method) : ?>
                                <li class="shipping__list_item">
                                    <?php
                                    if (1 < count($available_methods)) {
                                        printf('<input type="radio" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" %4$s />', $index, esc_attr(sanitize_title($method->id)), esc_attr($method->id), checked($method->id, $chosen_method, false)); // WPCS: XSS ok.
                                    } else {
                                        printf('<input type="hidden" name="shipping_method[%1$d]" data-index="%1$d" id="shipping_method_%1$d_%2$s" value="%3$s" class="shipping_method" />', $index, esc_attr(sanitize_title($method->id)), esc_attr($method->id)); // WPCS: XSS ok.
                                    }
                                    printf('<label class="shipping__list_label" for="shipping_method_%1$s_%2$s">%3$s</label>', $index, esc_attr(sanitize_title($method->id)), wc_cart_totals_shipping_method_label($method)); // WPCS: XSS ok.
                                    do_action('woocommerce_after_shipping_rate', $method, $index);
                                    ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (is_cart()) :
                            $quantity = 0;
                            foreach (WC()->cart->get_cart() as $cart_item) {
                                $quantity += $cart_item['quantity'];
                            }
                            if ($quantity < 3) : ?>
                                <p class="font-small"><a class="tooltip discount-shipping-district"
                                                         title="Quận 1, 3, 5, 6, 10, 11, Tân Bình, Tân Phú, Bình Tân">Khu
                                        vực gần shop:</a> Giảm thêm <strong>10.000đ</strong> phí ship sau khi shop xác
                                    nhận đơn hàng qua điện thoại.</p>
                                <h6>- hoặc -</h6>
                                <p class="font-small"><?php printf(__('Buy %s more products to get a free shipping.', 'flatsome'), '<strong>' . esc_html(3 - $quantity) . '</strong>'); ?></p>
                            <?php endif;
                        endif; ?>
                    <?php
                    elseif (!$has_calculated_shipping || !$formatted_destination) :
                        esc_html_e('Enter your address to view shipping options.', 'woocommerce');
                    elseif (!is_cart()) :
                        echo wp_kses_post(apply_filters('woocommerce_no_shipping_available_html', __('There are no shipping methods available. Please ensure that your address has been entered correctly, or contact us if you need any help.', 'woocommerce')));
                    else :
                        // Translators: $s shipping destination.
                        echo wp_kses_post(apply_filters('woocommerce_cart_no_shipping_available_html', sprintf(esc_html__('No shipping options were found for %s.', 'woocommerce') . ' ', '<strong>' . esc_html($formatted_destination) . '</strong>')));
                        $calculator_text = __('Enter a different address', 'woocommerce');
                    endif;
                    ?>

                    <?php if ($show_package_details) : ?>
                        <?php echo '<p class="woocommerce-shipping-contents"><small>' . esc_html($package_details) . '</small></p>'; ?>
                    <?php endif; ?>

                    <?php if ($show_shipping_calculator) : ?>
                        <?php woocommerce_shipping_calculator($calculator_text); ?>
                    <?php endif; ?>
                </td>
            </tbody>
            </tr>
        </table>
    </td>
</tr>