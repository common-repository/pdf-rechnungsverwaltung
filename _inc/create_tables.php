<?php
/**
 * Created by PhpStorm.
 * User: m.heine
 * Date: 11.06.20
 * Time: 14:06
 */

function create_pdf_invoice_tables() {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    $queries = create_pdf_invoice_queries();
    foreach ($queries as $query) {
        dbDelta($query);
    }
}

function create_pdf_invoice_queries() {
    global $wpdb;
    $queries[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}invoice` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(200) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(11,2) DEFAULT NULL,
  `payment_target` int(11) DEFAULT NULL,
  `start_text` text,
  `end_text` text,
  `customer_id` int(11) DEFAULT NULL,
  `paid` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;";

    $queries[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}invoice_customer` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `salutation` varchar(200) DEFAULT NULL,
  `first_name` varchar(200) DEFAULT NULL,
  `last_name` varchar(200) DEFAULT NULL,
  `company` varchar(200) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `zip` varchar(50) DEFAULT NULL,
  `city` varchar(200) DEFAULT NULL,
  `country` varchar(200) DEFAULT NULL,
  `mail` varchar(200) DEFAULT NULL,
  `iban` varchar(200) DEFAULT NULL,
  `bic` varchar(200) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    $queries[] = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}invoice_position` (
    `invoice_id` int(11) unsigned NOT NULL,
  `position` int(11) NOT NULL,
  `description` text,
  `price` decimal(11,2) DEFAULT NULL,
  `amount` decimal(11,2) DEFAULT NULL,
  `unit` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`invoice_id`,`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    return $queries;
}