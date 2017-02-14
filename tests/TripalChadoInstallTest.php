<?php

/**
 * Created by PhpStorm.
 * User: vunda
 * Date: 2/10/17
 * Time: 8:45 AM
 */

// Bootstrap Drupal.
define('DRUPAL_ROOT', '/var/www/html');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

class TripalChadoInstallTest extends PHPUnit_Framework_TestCase
{
    public function testAddition(){
        $this->assertEquals(2, 1 + 1);
    }
    public function testSubtraction(){
        $this->assertEquals(0.17, (1- 0.83));
    }

    public function testMultiplication(){
        $this->assertEquals(10, 2 * 5);
    }
    public function testDivision(){
        $this->assertTrue(2 == (10 / 5));
    }


}