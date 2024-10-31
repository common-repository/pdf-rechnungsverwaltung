<?php

$invoice_id = sanitize_text_field($_GET['invoice_id']);

global $wpdb;
$wpdb->update("{$wpdb->prefix}invoice", ['paid' => date('Y-m-d')], ['id' => $invoice_id]);

wp_redirect(get_home_url() . '/pdf_invoices');
die;