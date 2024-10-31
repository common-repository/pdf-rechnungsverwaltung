<?php
/**
 * @package PDF-Invoices
 */
/*
Plugin Name: PDF-Rechnungsverwaltung
Description: Erstellen und verwalten von PDF-Rechnungen
Version: 0.0.1
Author: Marco Heine
Author URI: https://www.mh-6.de/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

PDF-Rechnungsverwaltung is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

PDF-Rechnungsverwaltung is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with {Plugin Name}. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

define( 'PDF_INVOICES_PLUGIN', __FILE__ );
define( 'PDF_INVOICES_PLUGIN_BASENAME', plugin_basename( PDF_INVOICES_PLUGIN ) );
define( 'PDF_INVOICES_PLUGIN_NAME', trim( dirname( PDF_INVOICES_PLUGIN_BASENAME ), '/' ) );
define( 'PDF_INVOICES_PLUGIN_DIR', untrailingslashit( dirname( PDF_INVOICES_PLUGIN ) ) );

require_once PDF_INVOICES_PLUGIN_DIR . '/_inc/create_tables.php';


class Pdf_invoices
{

    public function __construct() {
        register_activation_hook(__FILE__, [$this, 'pdf_invoices_activation']);
        register_deactivation_hook(__FILE__, [$this, 'pdf_invoices_deactivation']);
        add_action('init', [$this, 'customop_custom_output']);
        add_action('template_redirect', [$this, 'pdf_invoices_index_display']);
    }


    public function pdf_invoices_activation() {
        $this->customop_custom_output();
        flush_rewrite_rules(); // Update the permalink entries in the database, so the permalink structure needn't be redone every page load

        create_pdf_invoice_tables();
    }

    public function pdf_invoices_deactivation() {
        flush_rewrite_rules();
    }


    public function customop_custom_output() {
        if (is_user_logged_in()) {
            #add_rewrite_tag('%pdf_invoices%', '([^/]+)');
            #add_permastruct('pdf_invoices', '/pdf_invoices%');
            #$style = 'bootstrap';
            #if ((!wp_style_is($style, 'queue')) && (!wp_style_is($style, 'done'))) {
            #wp_enqueue_style($style, plugins_url() . '/pdf_invoices/css/bootstrap.min.css');
            #}
        }
    }

    public function pdf_invoices_index_display() {
        if (is_user_logged_in()) {
            global $wp;
            if (@$wp->query_vars['pagename'] == 'pdf_invoices' || @$wp->query_vars['name'] == 'pdf_invoices') {
                $view = (@$_GET['action']) ? sanitize_text_field($_GET['action']) : 'index';
                if ($view != 'pdf' && $view != 'paid') {
                    get_header();
                    get_sidebar();
                    include PDF_INVOICES_PLUGIN_DIR . "/views/container.php";
                    get_footer();
                    exit; // Don't forget the exit. If so, WordPress will continue executing the template rendering and will not fing anything, throwing the 'not found page'
                } else {
                    include PDF_INVOICES_PLUGIN_DIR . "/views/$view.php";
                    die;
                }
            }
        }
    }
}

$pdf_invoices = new Pdf_invoices();

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-settings-page-activator.php
 */
function activate_settings_page() {
    require PDF_INVOICES_PLUGIN_DIR . '/_inc/settings/class-settings-page-activator.php';
    Settings_Page_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-settings-page-deactivator.php
 */
function deactivate_settings_page() {
    require PDF_INVOICES_PLUGIN_DIR . '/_inc/settings/class-settings-page-deactivator.php';
    Settings_Page_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_settings_page');
register_deactivation_hook(__FILE__, 'deactivate_settings_page');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require PDF_INVOICES_PLUGIN_DIR . '/_inc/settings/class-settings-page.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_settings_page() {

    $plugin = new Settings_Page();
    $plugin->run();

}

run_settings_page();

function recursive_sanitize_text_field($array) {
    foreach ( $array as $key => &$value ) {
        if ( is_array( $value ) ) {
            $value = recursive_sanitize_text_field($value);
        }
        else {
            $value = sanitize_text_field( $value );
        }
    }

    return $array;
}