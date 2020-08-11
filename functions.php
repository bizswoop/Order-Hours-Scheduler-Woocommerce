<?php
namespace Zhours;

defined('ABSPATH') or die('No script kiddies please!');

use Zhours\Aspect\InstanceStorage, Zhours\Aspect\Page, Zhours\Aspect\TabPage, Zhours\Aspect\Box;

/**
 * Retrieves the current store status.

 * @return bool The store status
 */
function get_current_status() {
    if (!plugin_enabled()) return true;
    list($rewrite, $status) = get_force_override_status();

    if($rewrite) { // return force status if enabled
        return (bool) $status;
    }
    $periods = get_day_periods();
    if (!$periods)
        return false;

    $holidays_calendar = get_holidays();

    $holidays = explode(', ', $holidays_calendar);
    if (is_holiday($holidays)) return false;

    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    $current_index = \date_i18n('N') - 1;
    $current_index = $days[$current_index];

    $today = isset($periods[$current_index]) ? $periods[$current_index] : null;

		$time = \date_i18n('H:i');

    if (!$today || !isset($today['periods'])) {
        return false;
    }

    $matches = array_filter($today['periods'], function ($element) use ($time) {
        return $time >= $element['start'] && $time <= $element['end'];
    });
    return count($matches) !== 0;
}

function is_plugin_settings_page() {
		$page = Page::get('order hours');
		return isset($_GET['page']) && $_GET['page'] === Page::getName($page);
}

function get_status_on_special_date($date) {
    $time = $date[1];
    $date = $date[0];
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    $day_of_week = date('N', strtotime($date));
    $periods = get_day_periods();
    $day = $days[$day_of_week - 1];
    if (isset($periods[$day])) {
        $matches = array_filter($periods[$day]['periods'], function ($element) use ($time) {
            return $time >= $element['start'] && $time <= $element['end'];
        });
        return count($matches) !== 0;

    }
    return false;
}

function is_holiday($dates) {
    if ($dates) {
        $date_format = get_date_format();

        $today = date($date_format, current_time( 'timestamp' ));
        foreach ($dates as $date){
            if ($today === $date){
                return true;
            }
        }
    }
    return false;
}

function get_date_format() {
    $standardFormat = 'd/m/Y';
    $wpFormat = get_option('date_format');
    if (preg_match('/^(?=.*\bd\b)(?=.*\bm\b)(?=.*\by\b).*$/i', $wpFormat)) {
        return $wpFormat;
    } else return $standardFormat;
}

function plugin_enabled() {
		return Settings::getValue('schedule', 'status', 'order hours status') === '1';
}

function get_alertbutton() {
    list ($text, $size, $color, $bg_color) = InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
        return Page::get('order hours')->scope(function () {
            return TabPage::get('actions')->scope(function (TabPage $alertbutton) {
                $options = Box::get('options');
                $text = Input::get('text');
                $size = Input::get('font size');
                $color = Input::get('color');
                $bg_color = Input::get('background color');
                $values = [$text, $size, $color, $bg_color];

                return array_map(function (Input $value) use ($options, $alertbutton) {
                    return $value->getValue($options, null, $alertbutton);
                }, $values);
            });
        });
    });

    $color = ($color) ? $color : 'black';
    $bg_color = ($bg_color) ? $bg_color : 'transparent';
    if (!$text || get_current_status()) {
        return;
    }
    ?>
    <style>
        .zhours_alertbutton {
            color: <?= $color; ?>;
            background-color: <?= $bg_color; ?>;
            padding: <?= $size; ?>px;
            font-size: <?= $size; ?>px;
        }
    </style>
    <div class="zhours_alertbutton">
        <?= $text; ?>
    </div>
    <?php
}

function is_enable_cache_clearing() {
		return Settings::getValue('schedule', 'cache management', 'enable cache clearing');
}

function is_hide_add_to_cart() {
		return Settings::getValue('actions', 'cart functionality', 'hide');
}

function get_day_periods() {
		return Settings::getValue('schedule', 'days schedule', 'period');
}

function get_holidays() {
		return Settings::getValue('schedule', 'holidays schedule', 'holidays calendar');
}

function get_position_styles() {
    list($position, $custom_position) = InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
        return Page::get('order hours')->scope(function () {
            return TabPage::get('notification')->scope(function (TabPage $alertbar) {
                $options = Box::get('options');
                $alert_position = Input::get('alert bar position');
                $custom_position = Input::get('custom position');
                $values = [$alert_position, $custom_position];
                return array_map(function (Input $value) use ($options, $alertbar) {
                    return $value->getValue($options, null, $alertbar);
                }, $values);
            });
        });
    });
    if ($position === __('Top', 'order-hours-scheduler-for-woocommerce')) {
        echo 'top: 0;';
    } elseif ($position === __('Custom', 'order-hours-scheduler-for-woocommerce') && $custom_position) {
        echo $custom_position . ';';
    } else {
        echo 'bottom: 0;';
    }
}

function get_alertbar() {
    $hide_alert_bar = apply_filters( 'zhw_hide_alert_bar', false);
    if ($hide_alert_bar || isset($_COOKIE['not_show_alert_bar'])) {
        return;
    }

    list($hide_alert_bar, $message, $size, $color, $bg_color, $alert_position, $custom_position) = InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
        return Page::get('order hours')->scope(function () {
            return TabPage::get('notification')->scope(function (TabPage $alertbar) {
                $options = Box::get('options');
                $hide_alert_bar = Box::get('hide alert bar');
                $message = Input::get('message');
                $size = Input::get('font size');
                $color = Input::get('color');
                $bg_color = Input::get('background color');
                $alert_position = Input::get('alert bar position');
                $custom_position = Input::get('custom position');
                $values = [$hide_alert_bar, $message, $size, $color, $bg_color, $alert_position, $custom_position];
                return array_map(function (Input $value) use ($options, $alertbar) {
                    return $value->getValue($options, null, $alertbar);
                }, $values);
            });
        });
    });
    $override_message = apply_filters('zhd_get_override_alert_bar_message', null);
    if ($override_message !== null) {
        if (isset($override_message['checkbox']) && $override_message['checkbox']) {
            return;
        }
        $message = $override_message['edit'];
    }

    $color = ($color) ? $color : 'black';
    $bg_color = ($bg_color) ? $bg_color : 'white';
//    $is_active_branding_plugin = has_action('hours_branding_is_active');
    $is_active_branding_plugin = true;
    ?>
    <style>
        .zhours_alertbar {
        <?php
        get_position_styles();
        ?>
            z-index: 1000;
            position: fixed;
            width: 100%;
            color: <?= $color; ?>;
            background-color: <?= $bg_color; ?>;
            padding: <?= $size; ?>px;
            font-size: <?= $size; ?>px;
            line-height: 1;
            text-align: center;
        }

        .zhours_alertbar-space {
            height: <?=$size*3?>px;
        }
    </style>

    <div class="zhours_alertbar-space"></div>
    <div class="zhours_alertbar">
        <?php
        if (!$is_active_branding_plugin) { ?>
            <div class="zhours_alertbar-branding">
                <a href="https://www.bizswoop.com" rel="nofollow">
                    <span class="zhours_alertbar-branding-label"><?php _e('POWERED BY:', 'order-hours-scheduler-for-woocommerce'); ?> </span>
                    <img src="<?= esc_url( plugins_url( 'assets/ZS_Logo.svg', __FILE__ ) ) ?>" alt="BIZSWOOP">
                    <span class="zhours_alertbar-branding-label">BIZSWOOP</span>
                </a>
            </div>
        <?php } ?>
        <div class="zhours_alertbar-message">
            <?= $message; ?>
        </div>
        <?php
        if ($hide_alert_bar && isset($hide_alert_bar['checkbox'])) : ?>
            <div class="zhours_alertbar-close-box">
                <?php
                if (isset($hide_alert_bar['edit']) && $hide_alert_bar['edit']) : ?>
                    <span class="close-box-icon"> <?= $hide_alert_bar['edit'] ?> </span>
                <?php endif; ?>
                <img id="zhours_alertbar-close" src="<?= esc_url( plugins_url( 'assets/close_icon.png', __FILE__ ) ) ?>" alt="Close" >
            </div>
        <?php endif; ?>
    </div>
    <style>
        .zhours_alertbar {
            display: flex;
        }
        .zhours_alertbar-close-box {
            display: inline-block;
            float: right;
        }
        .close-box-icon {
            position: relative;
            right: 5px;
        }
        .zhours_alertbar-close-box {
            flex-grow: 1;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
        }
        .zhours_alertbar-branding {
            display: flex;
        }
        .zhours_alertbar-branding a {
            display: flex;
            align-items: center;
            color: <?= $color; ?>;
        }
        .zhours_alertbar-close-box img{
            cursor: pointer;
            width: 20px;
            display: inline-block !important;
        }
        .zhours_alertbar-message {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-grow: 300;
            padding: 0 10px;
        }
        .zhours_alertbar-branding img {
            margin: 0 0.3rem;
            display: inline-block;
            width: <?= 2 * $size; ?>px;
            background-color: #ffffff;
            padding: 2px;
            border-radius: 50%;
        }
        @media (max-width: 600px) {
            .zhours_alertbar-branding-label {
               display: none;
            }
        }
    </style>
    <script>
        jQuery(document).ready(function ($) {
            $('#zhours_alertbar-close').on('click', function () {
                $('.zhours_alertbar').fadeOut();
                $('.zhours_alertbar-space').fadeOut();
                var now = new Date();
                now.setTime(now.getTime() + 7 * 24 * 3600 * 1000);
                document.cookie = "not_show_alert_bar=true; expires=" + now.toUTCString() + "; domain=<?= get_formatted_site_url() ?>;path=/";
            });
        });
    </script>
    </script>
    <?php
}

function get_formatted_site_url() {
    $url = get_site_url();
    $host = parse_url($url, PHP_URL_HOST);
    $names = explode(".", $host);

    if(count($names) == 1) {
        return $names[0];
    }

    $names = array_reverse($names);
    return $names[1] . '.' . $names[0];
}

function get_add_on_plugins() {
    return InstanceStorage::getGLobalStorage()->asCurrentStorage(function () {
        return Page::get('order hours')->scope(function () {
            return TabPage::get('add-ons')->scope(function (TabPage $alertbutton) {
                $box = Box::get('plugins');
                return $box->attaches[0]->attaches;
            });
        });
    });
}

function check_if_holiday($date) {
    $holidays = get_holidays();
    $holidays = explode(', ', $holidays);
    foreach ($holidays as $holiday) {
        if ($date === $holiday)
            return true;
    }
    return false;
}

function get_date_from_day_of_the_week($day, $is_cycled = false) {
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $date = new \DateTime();
    if (in_array(strtolower($day), $days)) {
        if ($is_cycled)
            $date->modify('+1 day');
        $day_of_week = strtolower(date('l', $date->getTimestamp()));
        while ($day_of_week !== $day) {
            $date->modify('+1 day');
            $day_of_week = strtolower(date('l', $date->getTimestamp()));
        }
    }
    return $date;
}

function cache_cleaner($is_cycled = false) {
    if (!is_enable_cache_clearing()) {
        if (wp_next_scheduled( 'zhours_cache_clear_open' )) {
            wp_clear_scheduled_hook( 'zhours_cache_clear_open' );
        }
        if (wp_next_scheduled( 'zhours_cache_clear_close' )) {
            wp_clear_scheduled_hook( 'zhours_cache_clear_close' );
        }
        return;
    }
    if (wp_next_scheduled( 'zhours_cache_clear_open' ) && wp_next_scheduled( 'zhours_cache_clear_close' ) ) {
        return;
    }
    $all_periods = get_day_periods();
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $current_index = \date_i18n('N') - 1;
    $current_date_time = date_i18n( 'Y-m-d H:i:s');
    $day_of_week = strtolower(date('l', strtotime($current_date_time)));
    foreach ($all_periods as $key => $day_periods) {
        if (!isset($day_periods['periods']) || (!$is_cycled && array_search($key, $days) < $current_index))
            continue;
        foreach ($day_periods['periods'] as $val => $period) {
            $start = get_date_from_day_of_the_week($key, $is_cycled);
            $start->setTime(explode(':', $period['start'])[0], explode(':', $period['start'])[1]);
            $start = $start->format('Y-m-d H:i:s');
            $end = get_date_from_day_of_the_week($key, $is_cycled);
            $end->setTime(explode(':', $period['end'])[0], explode(':', $period['end'])[1]);
            $end = $end->format('Y-m-d H:i:s');
            if ($current_date_time < $start) {
                if( !wp_next_scheduled( 'zhours_cache_clear_open' ) ) {
                    $time_offset = date('Y-m-d H:i:s', strtotime('-' . get_option('gmt_offset') . ' hours', strtotime($start)));
                    wp_schedule_event(strtotime($time_offset), 'daily', 'zhours_cache_clear_open');
                }
                if( !wp_next_scheduled( 'zhours_cache_clear_close' ) ) {
                    $time_offset = date('Y-m-d H:i:s', strtotime('-' . get_option('gmt_offset') . ' hours', strtotime($end)));
                    wp_schedule_event(strtotime($time_offset), 'daily', 'zhours_cache_clear_close');
                }
            }
            if ($current_date_time > $start && $current_date_time < $end) {
                if( !wp_next_scheduled( 'zhours_cache_clear_close' ) ) {
                    $time_offset = date('Y-m-d H:i:s', strtotime('-' . get_option('gmt_offset') . ' hours', strtotime($end)));
                    wp_schedule_event(strtotime($time_offset), 'daily', 'zhours_cache_clear_close');
                }
            }
            if (wp_next_scheduled( 'zhours_cache_clear_open' ) && wp_next_scheduled( 'zhours_cache_clear_close' ) ) {
                return;
            }
            if ($is_cycled && $day_of_week === $key) {
                return;
            }
            if (end($all_periods) === $day_periods) {
                cache_cleaner(true);
            }
        }
    }
}

add_filter('pre_option_zhours_current_status', function () {
    return get_current_status() ? "yes" : "no";
});

add_filter('check_if_store_hours_is_opened', function ($date) {
    return get_status_on_special_date($date);
});

add_filter('check_if_holiday', function ($date) {
    return check_if_holiday($date);
});

add_filter('get_period_schedule_by_day', function ($day) {
    $periods = get_day_periods();
    return isset($periods[$day]) ? $periods[$day] : [];
});

add_filter('body_class', function ($classes) {
    if (!get_current_status())
        $classes[] = 'zhours-closed-store';
    return $classes;
});

function get_force_override_status() {
    return InstanceStorage::getGlobalStorage()->asCurrentStorage(function () {
        return Page::get('order hours')->scope(function () {
            return TabPage::get('schedule')->scope(function (TabPage $schedule) {
                $force_status = Box::get('force status');
                return $force_status->scope(function ($box) use ($schedule) {
                    $rewrite = Input::get('force rewrite status');
                    $rewrite = $rewrite->getValue($box, null, $schedule);
                    $status = Input::get('force status');
                    $status = $status->getValue($box, null, $schedule);
                    return [$rewrite, $status];
                });
            });
        });
    });
}

function zhours_cache_clear($status){
    delete_directory(ABSPATH . 'wp-content/cache/');
    wp_clear_scheduled_hook( 'zhours_cache_clear_' . $status );
    cache_cleaner();
    do_action('zhours_on_cache_clearing');
}

function delete_directory($target) {
    if(is_dir($target)){
        $files = glob( $target . '*', GLOB_MARK );

        foreach( $files as $file ){
            delete_directory( $file );
        }
        rmdir( $target );
    } elseif(is_file($target)) {
        unlink( $target );
    }
}

function get_single_product_class() {
		return get_current_status() ? 'zh_single_add_to_cart_button_open' : 'zh_single_add_to_cart_button_close';
}

add_action('zhours_cache_clear_open', function () {
    zhours_cache_clear('open');
});

add_action('zhours_cache_clear_close', function () {
    zhours_cache_clear('close');
});
