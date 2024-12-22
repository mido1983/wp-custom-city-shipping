<?php
/**
 * Plugin Name: Custom City Shipping
 * Description: Adds city-based shipping costs to WooCommerce
 * Version: 1.0
 * Author: WebRainbow
 */

if (!defined('ABSPATH')) {
    exit;
}
define('CATEGORY_ID', 235);  // Определение константы здесь

include_once "ClassPluginLogger.php";

function enqueue_update_checkout_script() {
    wp_enqueue_script('update-checkout', plugins_url('/js/update-checkout.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('update-checkout', 'custom_shipping_params', array(
        'ajax_url' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_update_checkout_script');




function custom_city_shipping_init()
{
    if (!class_exists('WC_Custom_City_Shipping_Method')) {

        class WC_Custom_City_Shipping_Method extends WC_Shipping_Method
        {

            public function __construct()
            {
                $this->id = 'custom_city_shipping';
                $this->method_title = __('Custom City Shipping', 'woocommerce');

                $this->init();

                $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
                $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Custom City Shipping', 'woocommerce');
            }

            function init()
            {
                $this->init_form_fields();
                $this->init_settings();
                add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));

            }

            function init_form_fields()
            {

                $categories = get_terms('product_cat');
                $categories_options = array();
                foreach ($categories as $category) {
                    $categories_options[$category->slug] = $category->name;
                }

                // массив городов
                $city_args = array(
                    'Азур',
                    'Ариель',
                    'Ашдод',
                    'Ашкелон',
                    'Бат-Ям',
                    'Бней-Брак',
                    'Беер-Яков',
                    'Бейт-Нехамия',
                    'Бейт-Арие',
                    'Бейт-Шемеш',
                    'Ган-Явне',
                    'Герцлия',
                    'Гиват-Шмуэль',
                    'Гадера',
                    'Гиватаим',
                    'Кирьят-Оно',
                    'Кибуц-Эйнат',
                    'Кфар-Саба',
                    'Кирьят-Экрон',
                    'Кфар-Йона',
                    'Кирьят-Тивон',
                    'Кирьят-Ата',
                    'Кармиель',
                    'Кадима',
                    'Кейсария',
                    'Тсоран',
                    'Лод',
                    'Модиин',
                    'Натания',
                    'Нес-Циона',
                    'Нахшоним',
                    'Нэшер',
                    'Ор-Ехуда',
                    'Пардес-Хана',
                    'Петах-Тиква',
                    'Раанана',
                    'Рош-Айн',
                    'Рамат-а-шарон',
                    'Рамат-Ган',
                    'Ришон-ле-Цион',
                    'Рамле',
                    'Реховот',
                    'Тель-Авив',
                    'Тель-Монд',
                    'Шоам',
                    'Цур-Ицхак',
                    'Ход-а-шарон',
                    'Холон',
                    'Хадера',
                    'Хайфа',
                    'Йехуд',
                    'Йокнеам',
                    'Явне'
                );

                // создание массива полей формы
                $this->form_fields = array(
                    'enabled' => array(
                        'title' => __('Enable', 'woocommerce'),
                        'type' => 'checkbox',
                        'default' => 'yes'
                    ),
                    'title' => array(
                        'title' => __('Method Title', 'woocommerce'),
                        'type' => 'text',
                        'default' => __('Custom City Shipping', 'woocommerce')
                    ),
                );

                // создание полей для каждого города
                foreach ($city_args as $city) {
                    $this->form_fields[$city] = array(
                        'title' => __($city, 'woocommerce'),
                        'type' => 'text',
                        'default' => '100'
                    );
                }

                // добавление полей для выбора бесплатной доставки
                $this->form_fields['free_shipping_categories'] = array(
                    'title' => __('Free Shipping Categories', 'woocommerce'),
                    'type' => 'multiselect',
                    'options' => $categories_options,
                    'default' => '',
                    'class' => 'wc-enhanced-select'
                );
                $this->form_fields['free_shipping_cities'] = array(
                    'title' => __('Free Shipping Cities', 'woocommerce'),
                    'type' => 'multiselect',
                    'options' => $city_args,
                    'default' => '',
                    'class' => 'wc-enhanced-select'
                );

                $this->form_fields['second_free_shipping_category'] = array(
                    'title' => __('Second Free Shipping Category', 'woocommerce'),
                    'type' => 'select',
                    'options' => $categories_options,
                    'description' => __('Select a product category for which shipping will be free in certain cities.', 'woocommerce'),
                    'desc_tip' => true,
                    'default' => '',
                );

                $this->form_fields['second_free_shipping_cities'] = array(
                    'title' => __('Cities for Second Free Shipping Category', 'woocommerce'),
                    'type' => 'multiselect',
                    'options' => array_combine($city_args, $city_args),
                    'description' => __('Select cities where the second category will have free shipping.', 'woocommerce'),
                    'desc_tip' => true,
                    'class' => 'wc-enhanced-select',
                    'default' => '',
                );

            }

            public function calculate_shipping($package = []) {
                $city = WC()->customer->get_shipping_city();

                $free_shipping_categories = isset($this->settings['free_shipping_categories']) ? $this->settings['free_shipping_categories'] : [];
                $free_shipping_cities = isset($this->settings['free_shipping_cities']) ? $this->settings['free_shipping_cities'] : [];

                // Приведение $free_shipping_cities к массиву, если оно строка
                if (!is_array($free_shipping_cities)) {
                    $free_shipping_cities = explode(',', $free_shipping_cities);
                }

                $free_shipping = false;
                if (is_array($free_shipping_cities) && in_array($city, $free_shipping_cities)) {
                    $free_shipping = true;
                }

                foreach (WC()->cart->get_cart_contents() as $item) {
                    if (has_term(CATEGORY_ID, 'product_cat', $item['product_id'])) {
                        $free_shipping = true;
                        break;
                    }
                }

                // Проверка для второй категории с хардкодом
                $second_category_id = 271; // ID второй категории для бесплатной доставки
                $second_free_shipping_cities = [
                    'Ариель', 'Ашдод', 'Ашкелон', 'Азур', 'Бат-Ям', 'Бней-Брак', 'Бейт-Нехамия', 'Бейт-Арие',
                    'Ган-Явне', 'Герцлия', 'Гиват-Шмуэль', 'Гадера', 'Гиватаим', 'Кирьят-Оно', 'Кибуц-Эйнат',
                    'Кфар-Саба', 'Кирьят-Экрон', 'Кфар-Йона', 'Кадима', 'Кейсария', 'Тсоран', 'Лод', 'Модиин',
                    'Натания', 'Нес-Циона', 'Нахшоним', 'Ор-Ехуда', 'Пардес-Хана', 'Петах-Тиква', 'Раанана',
                    'Рош-Айн', 'Рамат-а-шарон', 'Рамат-Ган', 'Ришон-ле-Цион', 'Рамле', 'Реховот', 'Тель-Авив',
                    'Тель-Монд', 'Шоам', 'Цур-Ицхак', 'Ход-а-шарон', 'Холон', 'Хадера', 'Йехуд', 'Явне'
                ];

                if (!$free_shipping && in_array($city, $second_free_shipping_cities)) {
                    foreach (WC()->cart->get_cart_contents() as $item) {
                        if (has_term($second_category_id, 'product_cat', $item['product_id'])) {
                            $free_shipping = true;
                            break;
                        }
                    }
                }

                $cost = $free_shipping ? 0 : ($this->settings[strtolower($city)] ?? 0);

                $rate = array(
                    'id' => $this->id,
                    'label' => $this->title,
                    'cost' => $cost,
                    'calc_tax' => 'per_order'
                );
                $this->add_rate($rate);
            }

        }
    }
}

add_action('woocommerce_shipping_init', 'custom_city_shipping_init');


function custom_validate_city_field() {
    if (isset($_POST['billing_city']) && ($_POST['billing_city'] == '' || $_POST['billing_city'] == '--Выбрать город доставки--')) {
        wc_add_notice(__('Пожалуйста, выберите город доставки.'), 'error');
    }
}
add_action('woocommerce_checkout_process', 'custom_validate_city_field');

// Фильтр для изменения класса обязательного поля города
function custom_override_default_address_fields( $address_fields ) {
    $address_fields['city']['required'] = true;
    return $address_fields;
}
add_filter('woocommerce_default_address_fields', 'custom_override_default_address_fields');


function override_default_address_fields($fields)
{
    // Ваши города
    $city_args = array(
        'type' => 'select',
        'options' => array(
            '--Выбрать город доставки--' => __('Город  Доставки  не выбран', 'woocommerce'),
            'Азур' => __('Азур', 'woocommerce'),
            'Ариель' => __('Ариель', 'woocommerce'),
            'Ашдод' => __('Ашдод', 'woocommerce'),
            'Ашкелон' => __('Ашкелон', 'woocommerce'),
            'Бат-Ям' => __('Бат-Ям', 'woocommerce'),
            'Бней-Брак' => __('Бней-Брак', 'woocommerce'),
            'Беер-Яков' => __('Беер-Яков', 'woocommerce'),
            'Бейт-Нехамия' => __('Бейт-Нехамия', 'woocommerce'),
            'Бейт-Арие' => __('Бейт-Арие', 'woocommerce'),
            'Бейт-Шемеш' => __('Бейт-Шемеш', 'woocommerce'),
            'Ган-Явне'=> __('Ган-Явне', 'woocommerce'),
            'Герцлия'=> __('Герцлия', 'woocommerce'),
            'Гиват-Шмуэль' => __('Гиват-Шмуэль', 'woocommerce'),
            'Гадера' => __('Гадера', 'woocommerce'),
            'Гиватаим' => __('Гиватаим', 'woocommerce'),
            'Кирьят-Оно' => __('Кирьят-Оно', 'woocommerce'),
            'Кибуц-Эйнат' => __('Кибуц-Эйнат', 'woocommerce'),
            'Кфар-Саба' => __('Кфар-Саба', 'woocommerce'),
            'Кирьят-Экрон' => __('Кирьят-Экрон', 'woocommerce'),
            'Кфар-Йона' => __('Кфар-Йона', 'woocommerce'),
            'Кирьят-Тивон' => __('Кирьят-Тивон', 'woocommerce'),
            'Кирьят-Ата' => __('Кирьят-Ата', 'woocommerce'),
            'Кармиель' => __('Кармиель', 'woocommerce'),
            'Кадима' => __('Кадима', 'woocommerce'),
            'Кейсария' => __('Кейсария', 'woocommerce'),
            'Тсоран' => __('Тсоран', 'woocommerce'),
            'Лод' => __('Лод', 'woocommerce'),
            'Модиин' => __('Модиин', 'woocommerce'),
            'Натания' => __('Натания', 'woocommerce'),
            'Нес-Циона' => __('Нес-Циона', 'woocommerce'),
            'Нахшоним' => __('Нахшоним', 'woocommerce'),
            'Нэшер' => __('Нэшер', 'woocommerce'),
            'Ор-Ехуда' => __('Ор-Ехуда', 'woocommerce'),
            'Пардес-Хана' => __('Пардес-Хана', 'woocommerce'),
            'Петах-Тиква' => __('Петах-Тиква', 'woocommerce'),
            'Раанана' => __('Раанана', 'woocommerce'),
            'Рош-Айн' => __('Рош-Айн', 'woocommerce'),
            'Рамат-а-шарон' => __('Рамат-а-шарон', 'woocommerce'),
            'Рамат-Ган' => __('Рамат-Ган', 'woocommerce'),
            'Ришон-ле-Цион' => __('Ришон-ле-Цион', 'woocommerce'),
            'Рамле' => __('Рамле', 'woocommerce'),
            'Реховот' => __('Реховот', 'woocommerce'),
            'Тель-Авив' => __('Тель-Авив', 'woocommerce'),
            'Тель-Монд' => __('Тель-Монд', 'woocommerce'),
            'Шоам' => __('Шоам', 'woocommerce'),
            'Цур-Ицхак' => __('Цур-Ицхак', 'woocommerce'),
            'Ход-а-шарон' => __('Ход-а-шарон', 'woocommerce'),
            'Холон' => __('Холон', 'woocommerce'),
            'Хадера' => __('Хадера', 'woocommerce'),
            'Хайфа' => __('Хайфа', 'woocommerce'),
            'Йехуд' => __('Йехуд', 'woocommerce'),
            'Йокнеам' => __('Йокнеам', 'woocommerce'),
            'Явне' => __('Явне', 'woocommerce'),
        ),
    );
    $fields['city'] = $city_args;
    $fields['shipping']['shipping_city'] = $city_args;
    $fields['billing']['billing_city'] = $city_args;

    return $fields;
}

add_filter('woocommerce_default_address_fields', 'override_default_address_fields');

function add_custom_city_shipping($methods)
{
    $methods['custom_city_shipping'] = 'WC_Custom_City_Shipping_Method';
    return $methods;
}

add_filter('woocommerce_shipping_methods', 'add_custom_city_shipping');

function custom_city_shipping_label($label, $method)
{
    $city = WC()->customer->get_shipping_city();
    if ($method->id == 'custom_city_shipping') {

        // Получаем стоимость доставки
        $cost = is_numeric($method->cost) ? wc_price($method->cost) : $method->cost;
        // Обновляем метку, добавляя город и стоимость
        $label = $method->label . " (" . $city . ") - " . $cost;
    }
    return $label;
}

add_filter('woocommerce_cart_shipping_method_full_label', 'custom_city_shipping_label', 10, 2);

function custom_city_shipping_rate($rates)
{

    if (isset($rates['custom_city_shipping'])) {
        $city = WC()->customer->get_shipping_city();

        // Получаем экземпляр вашего метода доставки
        $shipping_method_instance = WC_Shipping_Zones::get_shipping_method($rates['custom_city_shipping']->instance_id);

        // Проверяем, существует ли метод доставки и имеет ли он настройки
        if ($shipping_method_instance && isset($shipping_method_instance->settings)) {
            $city_cost = $shipping_method_instance->settings[strtolower($city)] ?? 0;
            $rates['custom_city_shipping']->cost = $city_cost;
        }
    }
    return $rates;
}

add_filter('woocommerce_package_rates', 'custom_city_shipping_rate', 10, 2);
add_filter('woocommerce_checkout_fields', 'override_default_address_fields');
function update_city_field() {
    $cities = array(

    );

    // Проверка, содержит ли корзина товары из категории 271
    $category_in_cart = false;
    foreach (WC()->cart->get_cart() as $cart_item) {
        if (has_term(271, 'product_cat', $cart_item['product_id'])) {
            $category_in_cart = true;
            break;
        }
    }

    // Если категория 271 в корзине, возвращаем города из $second_free_shipping_cities
    if ($category_in_cart) {
        $second_free_shipping_cities = array(
            'Ариель',
            'Ашдод',
            'Ашкелон',
            'Азур',
            'Бат-Ям',
            'Бней-Брак',
            'Беер-Яков',
            'Бейт-Нехамия',
            'Бейт-Арие',
            'Ган-Явне',
            'Герцлия',
            'Гиват-Шмуэль',
            'Гадера',
            'Гиватаим',
            'Кирьят-Оно',
            'Кибуц-Эйнат',
            'Кфар-Саба',
            'Кирьят-Экрон',
            'Кфар-Йона',
            'Кадима',
            'Кейсария',
            'Тсоран',
            'Лод',
            'Модиин',
            'Натания',
            'Нес-Циона',
            'Нахшоним',
            'Ор-Ехуда',
            'Ор-Аккива',
            'Пардес-Хана',
            'Петах-Тиква',
            'Раанана',
            'Рош-Айн',
            'Рамат-а-шарон',
            'Рамат-Ган',
            'Ришон-ле-Цион',
            'Рамле',
            'Реховот',
            'Тель-Авив',
            'Тель-Монд',
            'Шоам',
            'Цур-Ицхак',
            'Ход-а-шарон',
            'Холон',
            'Хадера',
            'Йехуд',
            'Явне'
        );
        $cities = array_merge($cities, $second_free_shipping_cities);
    } else {
        // Если в корзине нет товаров из категории 271, возвращаем все города
        $all_cities = array(
            'Азур',
            'Ариель',
            'Ашдод',
            'Ашкелон',
            'Бат-Ям',
            'Бней-Брак',
            'Беер-Яков',
            'Бейт-Нехамия',
            'Бейт-Арие',
            'Бейт-Шемеш',
            'Ган-Явне',
            'Герцлия',
            'Гиват-Шмуэль',
            'Гадера',
            'Гиватаим',
            'Кирьят-Оно',
            'Кибуц-Эйнат',
            'Кфар-Саба',
            'Кирьят-Экрон',
            'Кфар-Йона',
            'Кирьят-Тивон',
            'Кирьят-Ата',
            'Кармиель',
            'Кадима',
            'Кейсария',
            'Тсоран',
            'Лод',
            'Модиин',
            'Натания',
            'Нес-Циона',
            'Нахшоним',
            'Нэшер',
            'Ор-Ехуда',
            'Пардес-Хана',
            'Петах-Тиква',
            'Раанана',
            'Рош-Айн',
            'Рамат-а-шарон',
            'Рамат-Ган',
            'Ришон-ле-Цион',
            'Рамле',
            'Реховот',
            'Тель-Авив',
            'Тель-Монд',
            'Шоам',
            'Цур-Ицхак',
            'Ход-а-шарон',
            'Холон',
            'Хадера',
            'Хайфа',
            'Йехуд',
            'Йокнеам',
            'Явне'
        );
        $cities = array_merge($cities, $all_cities);
    }

    wp_send_json_success(array('cities' => $cities));
}
add_action('wp_ajax_update_city_field', 'update_city_field');
add_action('wp_ajax_nopriv_update_city_field', 'update_city_field');
