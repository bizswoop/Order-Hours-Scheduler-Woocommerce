<?php
namespace Zhours;

use Zhours\Model\ServiceBox;

defined('ABSPATH') or die('No script kiddies please!');

class Input extends \Zhours\Aspect\Input
{
    const TYPE_DAYS_PERIODS = 'DaysPeriod';
    const TYPE_HOLIDAYS_SCHEDULE = 'HolidaysSchedule';
    const TYPE_DELIVERY_CHECKBOX = 'DeliveryCheckbox';
    const TYPE_CHECKBOX_EDIT_ONE_ROW = 'CheckBoxEditOneRow';
    const TYPE_CARD_PLUGIN = 'CardPlugin';

    public function htmlDaysPeriod($post, $parent)
    {
        $base_name = $this->nameInput($post, $parent);
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $value = $this->getValue($parent, null, $post);
        $value = (array)maybe_unserialize($value);

        foreach ($days as $day) {
            if (!isset($value[$day]) || !isset($value[$day]['periods'])) {
                $value[$day]['periods'] = [];
            }
        }
        ?>
        <div class="aspect_days_periods">
            <div class="aspect_days_tabs">
                <?php foreach ($days as $day) { ?>
                    <a href="#" data-day="<?= esc_attr($day); ?>"><?php _e(ucwords($day)); ?></a>
                <?php } ?>
            </div>

            <?php foreach ($days as $day) {
                $day_value = $value[$day];
                $day_period = $day_value['periods'];
                $input_name = $base_name . '[' . esc_attr($day) . ']';
                if (!isset($day_value['all_day'])) {
                    $day_value['all_day'] = '0';
                }
                $is_all_day = $day_value['all_day'] === '1';
                ?>
                <div class="aspect_day_period" data-day="<?= esc_attr($day); ?>" data-base=<?= $base_name; ?>>
                    <table>
                        <thead>
                        <tr>
                            <th><?php _e('Opening', 'order-hours-scheduler-for-woocommerce'); ?></th>
                            <th><?php _e('Closing', 'order-hours-scheduler-for-woocommerce'); ?></th>
                            <td>
                                <input class="aspect_all_day_value" type="hidden" name="<?= $input_name ?>[all_day]" value="<?= $day_value['all_day'] ?>"/>
                                <button class="aspect_all_day button <?= $is_all_day ? 'active' : '' ?>">
                                <?php _e('All Day', 'order-hours-scheduler-for-woocommerce'); ?>
                                </button>
                             </td>
                            <td>
                                <button class="aspect_day_add button <?= $is_all_day ? 'hidden' : '' ?>">+</button>
                            </td>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (count($day_period) === 0) {
                            $day_period = [['start' => null, 'end' => null]];
                        }
                        foreach ($day_period as $id => $period) {
                            $name = $input_name . '[periods][' . $id . ']';
                            ?>
                            <tr class="aspect_period" data-id="<?= $id; ?>">
                                <td><input type="time" name="<?= $name; ?>[start]"
                                           class="aspect_day_start"
                                           value="<?= $period['start'] ?>"></td>
                                <td><input type="time" name="<?= $name; ?>[end]"
                                           class="aspect_day_end"
                                           value="<?= $period['end'] ?>"></td>
                               <td></td>
                                <td>
                                    <button class="aspect_day_delete button <?= $is_all_day ? 'hidden' : '' ?>">&times;</button>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            <?php } ?>
        </div>
        <?php
    }

    public function htmlHolidaysSchedule($post, $parent)
    {
        $base_name = $this->nameInput($post, $parent);
        $value = $this->getValue($parent, null, $post);
        $value = (array)maybe_unserialize($value);

        if (!isset($value[0])) {
            $value[0] = [];
        }
        $date_format = get_option('date_format');
        $input_name = $base_name;
        ?>

        <div class="aspect_holidays_calendar">
        <div class="aspect_holidays_tab">
            <table>
                <td class="relative-column">
                    <textarea readonly id="date_picker_values" cols="30" rows="4"><?= $value[0]; ?></textarea>
                    <input name="<?= $input_name ?>" value="<?= $value[0] ?>" type="text" id="date_picker" readonly="readonly" >
                </td>
                <td>
                    <p><?php _e('Click on Text Box to Open Calendar and Select Your Holidays', 'order-hours-scheduler-for-woocommerce'); ?></p>
                </td>
            </table>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                function getDateFormat(){
                    const standardFormat = 'dd/mm/yy';
                    let wpFormat = '<?= $date_format ?>';
                    wpFormat = wpFormat.replace('Y', 'y').replace('M', 'm').replace('D', 'd');

                    wpFormat = replaceFormat(wpFormat, 'm');
                    wpFormat = replaceFormat(wpFormat, 'd');
                    wpFormat = replaceFormat(wpFormat, 'y');

                    if (wpFormat.includes('y') && wpFormat.includes('m') && wpFormat.includes('d')){
                        return wpFormat;
                    } else return standardFormat;
                }

                function replaceFormat(format, match) {
                    const regExp = new RegExp(match, 'ig');
                    if ((format.match(regExp) || []).length === 1) {
                        return format.replace(match, `${match}${match}`);
                    }
                    return format;
                }

                $('#date_picker_values').on('click', function(event) {
                  const datePicker = $('#date_picker');
                  const textareaHeight = $(this).height();
                  $('#date_picker').height(textareaHeight);
                  datePicker.select();
                });

                $('#date_picker').multiDatesPicker({
                    dateFormat: getDateFormat(),
                    showButtonPanel: true,
                     onSelect: function() {
                        const textStr = $('#date_picker').val();
                        $('#date_picker_values').val(textStr);
                    }
                });
            });
        </script>
        <?php
    }

    public function htmlCheckboxEditOneRow($post, $parent) {
        $base_name = $this->nameInput($post, $parent);
        $value = $this->getValue($parent, null, $post);
        $classes = $this->getClass();

        if (!isset($value['checkbox']))
            $value['checkbox'] = false;
        ?>
                <label class="<?= $classes ?>"><input type="checkbox" <?php self::isChecked($value['checkbox']); ?>
                          name="<?= $base_name ?>[checkbox]"
                          value="1">&nbsp;<?= $this->getLabelText() ?></label>

               <input class="large-text code zh-text-input <?= $classes ?>" type="text"
               name="<?= $base_name ?>[edit]"
               id="<?= $base_name ?>"
               value="<?= isset($value['edit']) ? $value['edit'] : '' ?>"/>
        <?php
            if (!empty($this->getDescription())) { ?>
                <p class="right-description-highlight description"> <?= $this->getDescription() ?></p>
                <?php
            }
        ?>
        <?php
    }

    public function htmlCardPlugin($post, $parent) {
        $plugins = get_add_on_plugins();
        $services = self::getServices();
        ?>
        <div class="zh-plugins-area">
        	<h2 class="zh-section-header">
        		<i class="fal fa-plus-circle zh-section-header-icon"></i>
        		<?php _e('Add more functionality', 'order-hours-scheduler-for-woocommerce'); ?>
        	</h2>
        	<div class="zh-services-wrapper">
						 <?php
						foreach ($plugins as $key => $plugin) {
								$is_active = Addons::is_active_add_on($plugin->getNamespace());
						?>
								<div class="zh-card-box-plugin" id="<?= $key ?>">
										<div class="zh-card-box-header">
												<?= $plugin->getLabelText() ?>
										</div>
										<div class="zh-card-box-description">
												<?= $plugin->getDescription() ?>
										</div>
										<div class="zh-card-box-footer">
												<div class="zh-card-box-left-footer">
												<?php
														if (!$is_active) {
																?>
																<span class="zh-dot zh-dot-enable"></span> <span><a href="<?= admin_url('plugins.php') ?>">
																	<?php _e('Enable', 'order-hours-scheduler-for-woocommerce'); ?>
																</a></span>
																<?php
														} else {
																?>
																<span class="zh-dot zh-dot-active"></span><span><?php _e('Active', 'order-hours-scheduler-for-woocommerce'); ?></span>
																<?php
														}
												 ?>
												</div>
												<?php
														if (!$is_active && $plugin->linkToBuy) {
																?>
																<div class="zh-card-box-center-footer">
																		<a class="zh-buy-button" href="<?= $plugin->linkToBuy ?>">
																			<?php _e('Buy', 'order-hours-scheduler-for-woocommerce'); ?>
																		</a>
																</div>
																<?php
														}
												?>
												<div class="zh-card-box-right-footer">
														<a href="<?= $plugin->getLink() ?>">
														<?php _e('More info', 'order-hours-scheduler-for-woocommerce'); ?>
														</a>
												</div>
										</div>

								</div>
								<?php } ?>
					</div>
        </div>
       <hr>
						<h2 class="zh-section-header"><i class="fal fa-compass zh-section-header-icon"></i><?php _e('Explore more products, platforms and services', 'order-hours-scheduler-for-woocommerce'); ?></h2>
						<div class="zh-services-wrapper">
						<?php foreach ($services as $service) : ?>
								<div class="zh-card-box-plugin">
										<div class="zh-card-box-service-header">
												<i class="<?= $service->getIconClass(); ?> zh-card-box-header-icon"></i>
												<br/>
												<?= $service->getHeader(); ?>
										</div>
										<div class="zh-card-box-description"><?= $service->getDescription(); ?></div>
										<div class="zh-card-box-footer-center">
												<a href="<?= $service->getLink(); ?>"><?= $service->getLinkText(); ?></a>
										</div>
								</div>
						<?php endforeach; ?>
								<div class="zh-card-box-plugin">
										<div class="zh-bzswp-box">
												<img class="zh-bzswp-box-logo" src="<?= Plugin::getUrl('assets/bizswoop.png'); ?>" alt="bizswoop">
												<h2>BizSwoop</h2>
												<p class="zh-bzswp-description"><?php _e('Your life`s work, our technology', 'order-hours-scheduler-for-woocommerce'); ?></p>
										</div>
										<div class="zh-card-box-footer-center">
												<a href="https://www.bizswoop.com"><?php _e('Visit Us', 'order-hours-scheduler-for-woocommerce'); ?></a>
										</div>
								</div>
						</div>
        <style>
        html th{
            width: 0 !important;
        }
        </style>
        <?php
    }

    protected static function getServices() {
    		return [
    			new ServiceBox(
    				__('WordPress.org', 'order-hours-scheduler-for-woocommerce'),
    				__('Free plugin apps for the open source community', 'order-hours-scheduler-for-woocommerce'),
    				'fab fa-wordpress-simple',
    				'https://wordpress.org/plugins/search/bizswoop',
    				__('Explore Free Apps', 'order-hours-scheduler-for-woocommerce')
    			),
    			new ServiceBox(
    				__('Premium Plugins', 'order-hours-scheduler-for-woocommerce'),
    				__('Smart plugin apps for your advanced business requirements', 'order-hours-scheduler-for-woocommerce'),
    				'fal fa-cubes',
    				'https://www.bizswoop.com/wp',
    				__('Explore All Apps', 'order-hours-scheduler-for-woocommerce')
    			),
    			new ServiceBox(
    				__('Powerful Platforms', 'order-hours-scheduler-for-woocommerce'),
    				__('Advanced platforms for agencies, developers and businesses', 'order-hours-scheduler-for-woocommerce'),
    				'fal fa-window',
    				'https://www.bizswoop.com/platforms',
    				__('Explore Platforms', 'order-hours-scheduler-for-woocommerce')
    			),
    			new ServiceBox(
    				__('Super Services', 'order-hours-scheduler-for-woocommerce'),
    				__('High-touch services to boost your business technology solutions', 'order-hours-scheduler-for-woocommerce'),
    				'fal fa-feather',
    				'https://www.bizswoop.com/services/',
    				__('Explore Services', 'order-hours-scheduler-for-woocommerce')
    			),
    		];
    }
}
