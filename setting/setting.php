<?php
namespace Zhours;

use \Zhours\Aspect\Addon, \Zhours\Aspect\Page, \Zhours\Aspect\TabPage, \Zhours\Aspect\Box;

defined('ABSPATH') or die('No script kiddies please!');

require_once __DIR__ . '/../functions.php';

$setting = new Page('order hours');

do_action('get_setting_page', $setting);

add_action('init', function (){
    $roles = ['shop_manager','administrator'];
    array_walk($roles, function ($role_name) {
        $role = get_role($role_name);
        $role->add_cap('zhours_manage_options', true);
    });
});

$setting
    ->setArgument('capability', 'zhours_manage_options')
    ->setArgument('parent_slug', 'woocommerce');

$setting->scope(function (Page $setting) {
    if ($setting->isRequested()) {
        add_action('admin_enqueue_scripts', function () {
            wp_enqueue_style('zhours-style', plugin_dir_url(__FILE__) . '/setting.css', [], '1.2');
            wp_enqueue_script('zhours-script', plugin_dir_url(__FILE__) . '/setting.js', ['jquery'], '1.1');
        });
    }

    $schedule = new TabPage('schedule');
    $schedule
			->setArgument('capability', 'zhours_manage_options')
			->setLabel('singular_name', __('Schedule', 'order-hours-scheduler-for-woocommerce'));

    $alertbar = new TabPage('notification');
    $alertbar
			->setArgument('capability', 'zhours_manage_options')
			->setLabel('singular_name', __('Notification', 'order-hours-scheduler-for-woocommerce'));


    $alertbutton = new TabPage('actions');
    $alertbutton
			->setArgument('capability', 'zhours_manage_options')
			->setLabel('singular_name', __('Actions', 'order-hours-scheduler-for-woocommerce'));

    $add_on = new TabPage('add-ons');
    $add_on
			->setArgument('capability', 'zhours_manage_options')
			->setLabel('singular_name', __('Add-ons', 'order-hours-scheduler-for-woocommerce'));

    $setting->attach($schedule, $alertbar, $alertbutton, $add_on);

    $schedule->scope(function (TabPage $schedule) {
        $status = new Box('status');
        $status->attachTo($schedule);

        $enable = new Input('order hours status');

        $force_status = new Box('force status');
        $force_status
            ->setLabel('singular_name', __('Force Override Store Schedule', 'order-hours-scheduler-for-woocommerce'))
            ->attachTo($schedule)
            ->scope(function ($box) {
                $rewrite = new Input('force rewrite status');
                $rewrite
                    ->setLabel('singular_name', __('Turn-on Force Override', 'order-hours-scheduler-for-woocommerce'))
                    ->setArgument('default', false)
                    ->attachTo($box)
                    ->attach([true, ''])
                    ->setType(Input::TYPE_CHECKBOX);

                $status = new Input('force status');
                $status
                    ->setLabel('singular_name', __('Ordering Status', 'order-hours-scheduler-for-woocommerce'))
                    ->setArgument('default', false)
                    ->attachTo($box)
                    ->setType(Input::TYPE_SELECT)
                    ->attach([false, __('Disabled', 'order-hours-scheduler-for-woocommerce')], [true, __('Enabled', 'order-hours-scheduler-for-woocommerce')]);
            });

        $days_schedule = new Box('days schedule');
        $days_schedule->attachTo($schedule);

        $period = new Input('period');
        $period
            ->attachTo($days_schedule)
            ->setType(Input::TYPE_DAYS_PERIODS);

        $holidays_schedule = new Box('holidays schedule');
        $holidays_schedule->attachTo($schedule);

        $holidays_calendar = new Input('holidays calendar');
        $holidays_calendar
            ->attachTo($holidays_schedule)
            ->setType(Input::TYPE_HOLIDAYS_SCHEDULE);

        $cache = new Box('cache management');
        $cache->attachTo($schedule);

        $enable_cache_clearing = new Input('enable cache clearing');
        $enable_cache_clearing
            ->setArgument('default', false)
            ->setLabel('singular_name',  __('Enable cache clearing', 'order-hours-scheduler-for-woocommerce'))
            ->setLabelText(__('Website cache will be cleared for each scheduled store open and close event', 'order-hours-scheduler-for-woocommerce'))
            ->setDescription(__('Important: Clearing the website cache may impact website loading speed and performance. Only locally stored cache is cleared, server side cache services are not cleared.', 'order-hours-scheduler-for-woocommerce'))
            ->attachTo($cache)
            ->attach([true, ''])
            ->setType(Input::TYPE_CHECKBOX);


        $description = call_user_func(function () {
            if (get_current_status()) {
                $color = 'green';
                $current_status = __('OPEN', 'order-hours-scheduler-for-woocommerce');
            } else {
                $color = 'red';
                $current_status = __('CLOSED', 'order-hours-scheduler-for-woocommerce');
            }
            $time = \date_i18n('H:i');
            return "<span style='background-color: $color; padding: 10px; display: inline-block; color: white; font-style: normal; line-height: 1;'> " .
							__('Current time:', 'order-hours-scheduler-for-woocommerce') . " $time . " . __('Status:', 'order-hours-scheduler-for-woocommerce') . " $current_status</span>";;
        });

        $enable
            ->setArgument('default', 0)
            ->setArgument('description', $description)
            ->attachTo($status)
            ->setType(Input::TYPE_SELECT)
            ->attach([0, __('Disabled', 'order-hours-scheduler-for-woocommerce')], [1, __('Enabled', 'order-hours-scheduler-for-woocommerce')]);
    });

    $alertbar->scope(function (TabPage $alertbar) {
        $options = new Box('options');
        $options
            ->attachTo($alertbar)
            ->setLabel('singular_name', __('Alert Bar Options for Sitewide Notice', 'order-hours-scheduler-for-woocommerce'));

        $hide_alert_bar = new Input('hide alert bar');
        $hide_alert_bar
            ->setLabel('singular_name', __('Hide Alert Bar', 'order-hours-scheduler-for-woocommerce'))
            ->setType(Input::TYPE_CHECKBOX_EDIT_ONE_ROW)
            ->setArgument('default', false)
            ->attach([true, ''])
            ->setLabelText(__('Allow Customer to Hide Alert Bar', 'order-hours-scheduler-for-woocommerce'))
            ->setClass('one-row-input')
            ->setDescription(__('Custom text for hide button, leave blank for icon only', 'order-hours-scheduler-for-woocommerce'));

        $message = new Input('message');
        $message->setLabel('singular_name', __('Alert Bar Message', 'order-hours-scheduler-for-woocommerce'));

        $size = new Input('font size');
        $size
            ->setArgument('default', 16)
            ->setArgument('min', 1)
            ->setType(Input::TYPE_NUMBER)
            ->setLabel('singular_name', __('Alert Bar Font Size', 'order-hours-scheduler-for-woocommerce'));

        $color = new Input('color');
        $color
            ->setType(Input::TYPE_COLOR)
            ->setLabel('singular_name', __('Alert Bar Color', 'order-hours-scheduler-for-woocommerce'));

        $bg_color = new Input('background color');
        $bg_color
            ->setType(Input::TYPE_COLOR)
            ->setLabel('singular_name', __('Alert Bar Background Color', 'order-hours-scheduler-for-woocommerce'));

        $alert_bar_position = new Input('alert bar position');
        $alert_bar_position
            ->setArgument('default', __('Bottom', 'order-hours-scheduler-for-woocommerce'))
            ->setLabel('singular_name', __('Alert Bar Position', 'order-hours-scheduler-for-woocommerce'))
            ->setType(Input::TYPE_RADIO)
            ->attachFew(array(
                'top'=> __('Top', 'order-hours-scheduler-for-woocommerce'),
                'bottom'=>  __('Bottom', 'order-hours-scheduler-for-woocommerce'),
                'custom' => __('Custom', 'order-hours-scheduler-for-woocommerce')
            ));
        $custom_position = new Input('custom position');
        $custom_position
            ->setType(Input::TYPE_TEXT)
            ->setLabel('singular_name', __('Custom Position (CSS)', 'order-hours-scheduler-for-woocommerce'))
            ->setArgument('description', __('Custom align position top or bottom eg.: top: 20px or bottom: 10px', 'order-hours-scheduler-for-woocommerce'))
            ->setClass('custom-position-input');

        $options->attach($hide_alert_bar, $message, $size, $color, $bg_color, $alert_bar_position, $custom_position);
    });

    $alertbutton->scope(function (TabPage $alertbutton) {

        $cart_functionality = new Box('cart functionality');
        $cart_functionality
            ->attachTo($alertbutton)
            ->setLabel('singular_name', __('Add to Cart Functionality', 'order-hours-scheduler-for-woocommerce'));

        $hide = new Input("hide");
        $hide
            ->setArgument('default', false)
            ->setLabel('singular_name', __('Hide Add to Cart button if Closed', 'order-hours-scheduler-for-woocommerce'))
            ->attachTo($cart_functionality)
            ->attach([true, ''])
            ->setType(Input::TYPE_CHECKBOX)
            ->setClass('reverse');

				$gateway_functionality = new Box('gateway functionality');
				$gateway_functionality
					->attachTo($alertbutton)
					->setLabel('singular_name', __('Payment Gateway Functionality', 'order-hours-scheduler-for-woocommerce'));

				$remove_gateways = new Input('remove gateways');
				$remove_gateways
					->setArgument('default', false)
					->setLabel('singular_name', __('Remove payment gateway options if closed to prevent checkout', 'order-hours-scheduler-for-woocommerce'))
					->attachTo($gateway_functionality)
					->attach([true, ''])
					->setType(Input::TYPE_CHECKBOX)
					->setClass('reverse');

        $options = new Box('options');
        $options
            ->attachTo($alertbutton)
            ->setLabel('singular_name', __('Alert Button Options for Checkout', 'order-hours-scheduler-for-woocommerce'));

        $text = new Input('text');
        $text->setLabel('singular_name', __('Alert Button Text', 'order-hours-scheduler-for-woocommerce'));

        $size = new Input('font size');
        $size
            ->setArgument('default', 16)
            ->setArgument('min', 1)
            ->setType(Input::TYPE_NUMBER)
            ->setLabel('singular_name', __('Alert Button Font Size', 'order-hours-scheduler-for-woocommerce'));

        $color = new Input('color');
        $color
            ->setType(Input::TYPE_COLOR)
            ->setLabel('singular_name', __('Alert Button Color', 'order-hours-scheduler-for-woocommerce'));

        $bg_color = new Input('background color');
        $bg_color
            ->setType(Input::TYPE_COLOR)
            ->setLabel('singular_name', __('Alert Button Background Color', 'order-hours-scheduler-for-woocommerce'));

        $options->attach($text, $size, $color, $bg_color);
    });

    $add_on->scope(function (TabPage $add) {
        $plugins = new Box('plugins');
        $plugins
            ->setLabel('singular_name', __('Plugins', 'order-hours-scheduler-for-woocommerce'))
            ->attachTo($add);

        $hours_widget = new Addon('hours_widget');
        $hours_widget
            ->setLabelText(__('Order Hours Widget', 'order-hours-scheduler-for-woocommerce'))
            ->setDescription(__('Add widget to show current store order status, countdown to order status change and display complete store schedule for Order Hours Scheduler', 'order-hours-scheduler-for-woocommerce'))
            ->setNamespace(Addons::ORDER_HOURS_WIDGET_NAMESPACE)
            ->setLinkToBuy('https://www.bizswoop.com/product/order-hours-widget')
            ->setLink('https://www.bizswoop.com/wp/orderhours/widget');

        $delivery_plugin = new Addon('delivery_plugin');
        $delivery_plugin
            ->setLabelText(__('Delivery and Pickup Functionality', 'order-hours-scheduler-for-woocommerce'))
            ->setDescription(__('Allow customers to select delivery time or pickup date time to checkout for orders', 'order-hours-scheduler-for-woocommerce'))
            ->setNamespace(Addons::ORDER_DELIVERY_NAMESPACE)
            ->setLink('https://www.bizswoop.com/wp/orderhours/delivery');

        $plugins_list = new Input('');
        $plugins_list
            ->setType(Input::TYPE_CARD_PLUGIN)
            ->attach($hours_widget)
            ->attach($delivery_plugin)
            ->attachTo($plugins);
    });
});
