<?php
require_once __DIR__ . '/includes/duplicate_content.php';
require_once __DIR__ . '/includes/product_cat_handler.php';
require_once __DIR__ . '/includes/sender_email.php';

// load post_meta dependend scripts
add_action('the_post', 'load_includes');
function load_includes()
{
    global $post;

    $is_sales_checker = !empty(get_post_meta($post->ID, 'sales_checker'));
    if ($is_sales_checker) {
        require_once __DIR__ . '/includes/sales_checker.php';
        add_action('the_post', 'fix_sales_handler_from_post', 10);
        add_action('the_post', 'add_shortcode_sales_checker', 20);
    }
}

add_theme_support('editor-styles');
add_editor_style('style-editor.css');

function theme_setup_theme_supported_features()
{
    add_theme_support('editor-color-palette', array(
        array(
            'name' => __('lehmann red', 'envo-storefront-child'),
            'slug' => 'lehmann-red',
            'color' => '#de0303',
        ),
        array(
            'name' => __('light grayish magenta', 'envo-storefront-child'),
            'slug' => 'light-gray',
            'color' => '#d0a5db',
        ),
        array(
            'name' => __('very light gray', 'envo-storefront-child'),
            'slug' => 'very-light-gray',
            'color' => '#eee',
        ),
        array(
            'name' => __('very dark gray', 'envo-storefront-child'),
            'slug' => 'very-dark-gray',
            'color' => '#444',
        ),
        array(
            'name' => __('light white', 'envo-storefront-child'),
            'slug' => 'light-white',
            'color' => '#fbf8f9',
        ),
    ));
}
add_action('after_setup_theme', 'theme_setup_theme_supported_features');

if (!function_exists('wbp_show_product_loop_adult_flash')) {

    /**
     * Output the product adult flash.
     */
    function wbp_show_product_loop_adult_flash()
    {
        wc_get_template('loop/adult-flash.php');
    }
}
// add_action('woocommerce_before_shop_loop_item', 'wbp_show_product_loop_adult_flash', 20);

if (!function_exists('wbp_show_single_product_adult_flash')) {

    /**
     * Output the product adult flash.
     */
    function wbp_show_product_adult_flash()
    {
        wc_get_template('single-product/adult-flash.php');
    }
}
if (!function_exists('is_adult_product')) {

    /**
     * Output the product adult flash.
     */
    function is_adult_product()
    {
        global $post;

        $terms = get_the_terms(get_the_ID(), 'product_tag');
        foreach ($terms as $term) {
            if ($term->slug === "ab-18") {
                return true;
            }
        }
        return false;
    }
}
// add_action('wbp_adult_flash', 'wbp_show_single_product_adult_flash', 10);

/**
 * check for sales attribute and if true add SALES Category to it
 *
 */
function check_product_for_sale($post_id, $post, $is_update)
{
    global $woocommerce;

    $product_id = $post_id;
    $product = wc_get_product($product_id);

    if (!$product) {
        return 0;
    }

    if ($product->is_type('variation')) {
        $variation = new WC_Product_Variation($product);
        $product_id = $variation->get_parent_id();
        $product = wc_get_product($product_id);
    }
    if (defined('SALES_CAT_ID')) {
        process_sales_cat($product_id, SALES_CAT_ID);
    }

}
add_action("save_post", "check_product_for_sale", 99, 3);

/** check for featured product attribute and if true add FEATURED Category to it */
function check_product_cat_before_save($product, $data_store)
{
    if (defined('FEATURED_CAT_ID')) {
        $is_featured = $product->is_featured();
        set_product_cat($product, FEATURED_CAT_ID, $is_featured);
    }
}
add_action("woocommerce_before_product_object_save", "check_product_cat_before_save", 99, 2);

function add_scripts()
{
    wp_enqueue_style("parent-style", get_parent_theme_file_uri('/style.css'));

    wp_enqueue_style('fancybox', get_stylesheet_directory_uri() . '/css/fancybox/jquery.fancybox.css', wp_get_theme()->get('Version'));

    wp_enqueue_style('fancybox', get_stylesheet_directory_uri() . '/css/fancybox/jquery.fancybox.css', wp_get_theme()->get('Version'));

    $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
    // Function to add analyticstracking.js to the site
    if (!IS_DEV_MODE) {
        $current_user = wp_get_current_user();
        $user_id = (0 == $current_user->ID) ? '' : $current_user->ID;
        wp_register_script('google-analytics', get_stylesheet_directory_uri() . '/js/analyticstracking.js', false, '1.0', true);
        wp_enqueue_script('google-analytics');
        // hand over the userID to the analytics script
        wp_localize_script('google-analytics', 'atts', array('user_id' => $user_id, 'ga_id' => GA_ID));
    }

    wp_register_script('fb', get_stylesheet_directory_uri() . '/js/fb.js', array('jquery'), '1.0', true);
    wp_enqueue_script('fb');
    wp_register_script('main', get_stylesheet_directory_uri() . '/js/main.js', array('jquery'), '1.0', true);
    wp_enqueue_script('main');

    /*
     *  Require Fancybox JS for Action Gallery Page only
     */
    if (is_page('action-galerie')) { // using the slug here
        add_theme_support('html5', array('gallery'));

        wp_enqueue_script('fancybox', get_stylesheet_directory_uri() . '/js/fancybox/jquery.fancybox' . $suffix . '.js', array('jquery'), '1.0', true);
        wp_enqueue_script('fancybox-helper', get_stylesheet_directory_uri() . '/js/fancybox-helper.js', array('jquery'), '1.0', true);
        wp_enqueue_style('fancybox', get_stylesheet_directory_uri() . '/css/fancybox/jquery.fancybox.css', wp_get_theme()->get('Version'));
    }
}
add_action('wp_enqueue_scripts', 'add_scripts');

/**
 * Register some extra Footer
 */
function wbp_register_subfooter_widget_area()
{
    register_sidebar(array(
        'name' => __('Sub Footer 1', 'envo-storefront-child'),
        'id' => 'wbp_sub_footer_1',
        'before_widget' => '<div class="column widget %2$s" id="%1$s">',
        'after_widget' => '</div>',
        'before_title' => '<span class="widgettitle">',
        'after_title' => '</span>',
        'description' => __('Choose which Widgets to display below the Footer', 'envo-storefront-child'),
    ));

    register_sidebar(array(
        'name' => __('Sub Footer 2', 'envo-storefront-child'),
        'id' => 'wbp_sub_footer_2',
        'before_widget' => '<div class="column widget %2$s" id="%1$s">',
        'after_widget' => '</div>',
        'before_title' => '<span class="widgettitle">',
        'after_title' => '</span>',
        'description' => __('Choose which Widgets to display below the Footer', 'envo-storefront-child'),
    ));
}
add_action('widgets_init', 'wbp_register_subfooter_widget_area', 11);

/**
 * Default sort for shop and specific categories
 */
function custom_default_orderby($sortby)
{

    if (is_shop()) {
        return 'date';
    }

    global $wp_query;

    // categories sorting table
    $orderby = array(
        'sale' => 'date',
    );

    $cat = $wp_query->get_queried_object();
    $slug = $cat->slug;

    if (array_key_exists($slug, $orderby)) {
        $sortby = $orderby[$slug];
    }

    return $sortby;
}
add_filter('woocommerce_default_catalog_orderby', 'custom_default_orderby');

/**
 * Unsupprted Browsers IE 11 and lower
 */
function detectTrident($current_theme)
{
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $browser = ['name' => '', 'version' => '', 'platform' => ''];
    if (preg_match('/Trident\/([0-9.]*)/u', $ua, $match)) {
        $match = (int)array_pop($match) + 4;
        // write_log( "Trident:" );
        // write_log( $match );
    } elseif (preg_match('/MSIE\s{1}([0-9.]*)/u', $ua, $match)) {
        $match = (int)array_pop($match);
        // write_log( "MSIE:" );
        // write_log( $match );
    }
    if (!empty($match) && ($match <= 11)) {
        $browser['name'] = 'ie';
        $browser['version'] = $match;
        add_action('wp_footer', 'unsupported_browsers_template', 100);

        wp_register_script('browser_sniffer', get_stylesheet_directory_uri() . '/js/browser_support.js', ['jquery'], '0.1', true);
        wp_localize_script('browser_sniffer', '__browser', array('name' => $browser['name'], 'version' => $browser['version'], 'platform' => $browser['platform']));
        wp_enqueue_script('browser_sniffer');
    }
}
function unsupported_browsers_template()
{
    get_template_part('custom-templates/custom', 'unsupported-browser');
}
add_action('wp_enqueue_scripts', 'detectTrident');
//1.0.0
