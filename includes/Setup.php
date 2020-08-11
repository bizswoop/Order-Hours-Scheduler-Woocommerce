<?php

namespace Zhours;

use Zhours\Frontend\Shop;

class Setup
{
	public function __construct()
	{
			new Translation();
			add_action('plugins_loaded', [$this, 'init'], 1);
			add_action('after_setup_theme', [$this, 'themes_loaded'], 1);
    }

	public function init()
	{
			if (!class_exists('WooCommerce')) {
					add_action('admin_notices', function () { ?>
							<div class="notice notice-error is-dismissible">
									<p><?php _e('Order Hours Scheduler for WooCommerce require WooCommerce', 'order-hours-scheduler-for-woocommerce'); ?></p>
							</div>
							<?php
					});
					return;
			}

		require_once PLUGIN_ROOT . '/setting/setting.php';
		require_once PLUGIN_ROOT . '/functions.php';

		new Admin();
		new Frontend\Shop();
		new Template();
		/*
		 *
		 * do_action plugin is loaded;
		 */
		if (!get_current_status()) {
			\add_action('wp', function () {
					$allow_order_placing = apply_filterS('zhd_is_allowed_order_placing', false);
					if ($allow_order_placing) {
							return;
					}
					if (is_checkout()) {
							header('Location:' . wc_get_cart_url());
							exit;
					}

					if (is_hide_add_to_cart()){
					    \remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
					    \remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart');

                        add_filter( 'woocommerce_blocks_product_grid_item_html', [Shop::class, 'update_product_grid_item'], 10, 3 );
					}

					\remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
					\add_action('woocommerce_proceed_to_checkout', '\Zhours\get_alertbutton');

					\add_action('woocommerce_widget_shopping_cart_before_buttons', function () {
							\ob_start();
					});
					\add_action('woocommerce_after_mini_cart', function () {
							if (!WC()->cart->is_empty()) :
									\ob_end_clean();
							get_alertbutton();
							endif;
					});
			});

            \add_action('wp_footer', '\Zhours\get_alertbar');
        }
        cache_cleaner();

        /** Break html5 cart caching */
		\add_action('wp_enqueue_scripts', function () {
			\wp_enqueue_script('wc-cart-fragments', plugin_dir_url(PLUGIN_ROOT_FILE) . '/cart-fragments.js', array('jquery', 'jquery-cookie'), '1.0', true);
		}, 100);

        add_action( 'admin_enqueue_scripts', function (){
						if (is_plugin_settings_page()) {
								wp_enqueue_script( 'zh-multidatespicker', plugin_dir_url(PLUGIN_ROOT_FILE) . '/multidatespicker/jquery-ui.multidatespicker.js', ['jquery-ui-datepicker'] );

								wp_enqueue_style( 'zh-multidatepicker.css', plugin_dir_url(PLUGIN_ROOT_FILE) . '/multidatespicker/multidatespicker.css');
								wp_enqueue_style( 'zh-fa', plugin_dir_url(PLUGIN_ROOT_FILE) . '/assets/fa/app.css');
						}
        } );

    }

    public function themes_loaded()
    {
        if (function_exists(__NAMESPACE__ . '\get_current_status') && !get_current_status() && is_hide_add_to_cart()) {
            \remove_action('woocommerce_single_product_lightbox_summary', 'woocommerce_template_single_add_to_cart', 30);
        }
    }
}
