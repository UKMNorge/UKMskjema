<?php
/* 
Plugin Name: UKM Skjemaer 2026
Plugin URI: http://www.ukm.no
Description: Skjemaer for UKM Norge
Author: UKM Norge / Kushtrim Aliu
Version: 1.0
Author URI: http://www.ukm.no
*/

// use UKMNorge\Arrangement\Arrangement;
// use UKMNorge\Arrangement\Videresending\Mottaker;
// use UKMNorge\Innslag\Personer\Person;
// use UKMNorge\Meta\Write as WriteMeta;
// use UKMNorge\Sensitivt\Intoleranse;

require_once('UKM/Autoloader.php');


// Function for the custom shortcode
function my_custom_shortcode_function() {
    return '<div>Your custom content here</div>';
}

// Register the shortcode
function register_my_custom_shortcode() {
    add_shortcode('my_custom_shortcode-kushal', 'my_custom_shortcode_function');
}

// Hook into WordPress initialization
add_action('init', 'register_my_custom_shortcode');

class UKMskjema extends UKMNorge\Wordpress\Modul
{
    public static $action = 'skjemaPlaceholder';
    public static $path_plugin = null;

    /**
     * Initier Videresending-objektet
     *
     **/
    public static function init($plugin_path)
    {
        parent::init($plugin_path);
    }

    /**
     * Hooker modulen inn i Wordpress
     *
     * @return void
     */
    public static function hook()
    {
        add_action('admin_menu', ['UKMskjema', 'meny'], 101);
        add_action('wp_ajax_UKMskjema_ajax', ['UKMskjema', 'ajax']);
    }

    /**
     * Håndterer alle ajax-kall
     *
     * @return void
     */
    public static function ajax()
    {
        if (is_array($_POST)) {
            static::addResponseData('POST', $_POST);
        }

        try {
            $supported_actions = [
                'filmerAjax',
                'avmeld',
                'videresend',
                'kontroll',
                'kontrollSave',
                'avmeldPerson',
                'videresendPerson',
                'bilderShow',
                'bildeSet',
                'filmerShow',
                'playbackShow',
                'nominasjon',
                'lederDelete',
                'lederSave',
                'lederCreate',
                'lederSaveNatt',
                'kommentarOvernatting',
                'lederSaveHoved',
                'tilrettelegging',
                'createSamtykkeskjema',
                'saveSamtykkeskjema',
                'getAlleSamtykkeskjemaer',
            ];

            if (in_array($_POST['subaction'], $supported_actions)) {
                static::setupLogger();
                require_once('ajax/' . $_POST['subaction'] . '.ajax.php');
            } else {
                throw new Exception('Beklager, støtter ikke denne handlingen!');
            }
        } catch (Exception $e) {
            static::addResponseData('success', false);
            static::addResponseData('message', $e->getMessage());
            static::addResponseData('code', $e->getCode());
        }

        $data = json_encode(static::getResponseData());
        echo $data;
        die();
    }

    /**
     * Legg til alle scripts som videresendingen bruker
     * 
     * (og ja, det er en del!)
     *
     * @return void
     */
    public static function script()
    {
        wp_enqueue_script('WPbootstrap3_js');
        wp_enqueue_style('WPbootstrap3_css');
        wp_enqueue_script('TwigJS');

        wp_enqueue_style('UKMskjemaVueStyle', plugin_dir_url(__FILE__) . '/client/dist/assets/build.css');
        wp_enqueue_script('UKMskjemaVueJs', plugin_dir_url(__FILE__) . '/client/dist/assets/build.js','','',true);
    }

    /**
     * Registrer menyer
     *
     **/
    public static function meny()
    {
        add_action(
            'admin_print_styles-' .
                add_menu_page(

                    'UKM Skjemaer 2026',
                    'UKM Skjemaer 2026',
                    'editor',
                    'UKMskjema',
                    ['UKMskjema', 'renderAdmin'],
                    'dashicons-editor-removeformatting', #'//ico.ukm.no/paper-airplane-20.png',
                    90
                ),
            ['UKMskjema', 'script']
        );
    }
}


UKMskjema::init(__DIR__);
UKMskjema::hook();
