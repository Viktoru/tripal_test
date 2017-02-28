<?php

// Bootstrap Drupal.
define('DRUPAL_ROOT', '/var/www/html');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

/**
 *  PHPUnit Tests the testing the Custom Chado Tables.
 *  Provides an API manage custom tables in Chado.
 *
 */
class CustomChadoTables extends PHPUnit_Framework_TestCase {

  public function test_chado_create_custom_table(){

    // Create a table name.
    $table_01 = 'table_1';
    db_create_table($table_01, 'chado');
    chado_create_custom_table($table_01, 'chado', $skip_if_exist = 1, $mview_id = NULL);

    var_dump($table_01);

  }

}