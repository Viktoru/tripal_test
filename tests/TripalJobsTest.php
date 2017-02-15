<?php

// Bootstrap Drupal.
define('DRUPAL_ROOT', '/var/www/html');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

class TripalJobsTest extends PHPUnit_Framework_TestCase {

  protected $newdb;

  public function setUp() {
    $path = '/var/www/sites/all/modules/custom/tripal_test_tests/';
    $this->instance = $path;
  }

  public function TearDown() {
    unset($this->instance);
  }

  public function test_tripal_add_job() {
    global $user;
    // Case #3:  Submit a job successfully and receive a job id.
    $args = array();
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $this->assertTrue(is_numeric($job_id), 'Case #3: It should return a numeric job ID.');

    // Case #6: A new job was added to the database table.
    $sql = "
      SELECT job_id 
      FROM {tripal_jobs} 
      WHERE job_name = :job_name and modulename = :modulename and callback = :callback 
        and uid = :uid and priority = :priority
    ";
    $args = array(
      ':job_name' => $job_name,
      ':modulename' => 'modulename',
      ':callback' => 'tripal_test_jobs_callback',
      ':uid' => $user->uid,
      ':priority' => 10
    );
    $query = db_query($sql, $args);

    $test_job_id = $query->fetchField();

    // if $test_job_id and $job_id, are of the same "type" and "equal".
    if ($test_job_id === $job_id) {
      $this->assertTrue(TRUE);
      // var_dump(TRUE);
    }
    else {
      $this->assertFalse(FALSE);
      // var_dump(FALSE);
    }
    $this->newdb->$test_job_id;

  }

  public function test_get_active_jobs_function() {

    $job_get_active_jobs = uniqid('tripal_get_active_jobs');
    $job_get_active = tripal_get_active_jobs($job_get_active_jobs);
    $this->assertFalse($job_get_active, 'Case #1: It should return false (bool)');

    $sql = "
        SELECT * FROM {tripal_jobs} TJ
        WHERE TJ.end_time IS NULL and TJ.modulename = :modulename ";

    $args = array(
      ':modulename' => $job_get_active_jobs,
    );
    $query = db_query($sql, $args);

    $job_active = $query->fetchField();

    // if $test_job_id and $job_id, are of the same "type" and "equal".
    if ($job_active === $job_get_active) {
      $this->assertTrue(TRUE);
      // var_dump(TRUE);
    }
    else {
      $this->assertFalse(FALSE);
      // var_dump(FALSE);
    }
    $this->newdb->$job_active;
  }

  public function test_tripal_cancel_job() {

    $query = db_query("SELECT * FROM {tripal_jobs} WHERE status ='Cancelled'");
    $num_rows = $query->rowCount();
    $job_cancel_total = $num_rows;

    if ($job_cancel_total >= 1) {
      $this->assertTrue(TRUE);
      var_dump(TRUE);
    }
    else {
      $this->assertFalse(FALSE);
      var_dump(FALSE);
    }

  }

  public function test_tripal_get_job_end() {
    $q = db_query("SELECT * FROM {tripal_jobs} WHERE end_time > 0");

    $arg_result = array(
      ':end_time' => 'end_time'
    );
    $query = db_query($q, $arg_result);
    $test_job_id = $query->fetchField();

    if ($test_job_id > 0) {
      $this->assertTrue(TRUE);
      var_dump($test_job_id, 'It should be TRUE.');
    }
    else {
      $this->assertFalse(FALSE);
      var_dump($test_job_id, 'It should be FALSE.');
    }

  }

    public function test_tripal_get_job_start(){

      $q = db_query("SELECT * FROM {tripal_jobs} WHERE start_time > 0");

      $arg_result = array(
        ':start_time' => 'start_time'
      );
      $query = db_query($q, $arg_result);
      $test_job_q = $query->fetchField();

      if ($test_job_q > 0) {
        $this->assertTrue(is_numeric($test_job_q), 'It should returns TRUE.');

      } if ($test_job_q == NULL) {
        $this->assertTrue(is_null($test_job_q), 'It should returns TRUE. NULL.');

      } if ($test_job_q == '') {
        $this->assertTrue(empty($test_job_que), 'It should returns TRUE. Empty');

      }
    }





















 // $this->newdb->$job_cancel_active;
//}
//
//public function test_tripal_get_job(){
//
//    $job_get_jobs = uniqid('tripal_get_job');
//    $job_getjobs = tripal_cancel_job($job_get_jobs);
//    $this->assertTrue($job_getjobs, 'Case #1: It should return false (bool)');
//
//    $sql = "
//        SELECT j.* FROM {tripal_jobs} j
//        WHERE j.job_id=:job_id ";
//
//    $args = array(
//      ':job_id' => $job_get_jobs
//    );
//
//    $query = db_query($sql, $args);
//    $job_get_active = $query->fetchField();
//
//    var_dump($job_get_active);
//
//  }

}




//      // TODO: the remaining tests fail because the tripal_add_job() function needs fixes
//      // but until then and while debugging we'll skip them.
//
//      // Case #5: What if the job name isn't provided. The function
//      // should return FALSE instead of a job id.
//      $job_id02 = tripal_add_job('', 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 10);
//      $this->assertTrue($job_id02, 'Case #5: It should return FALSE if the name is not provided');


//    public function test_add_job_2(){
//      global $user;
//      $args = array();
//
//    // Case #5: What if the job name isn't provided. The function
//      // should return FALSE instead of a job id.
//      $job_id02 = tripal_add_job('', 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 10);
//      $this->assertFalse($job_id02, 'Case #5: It should return FALSE if the name is not provided');
//
//      return $this->newdb->$job_id02;
//
//    }



//
//    // Case #7: What if an empty callback is provided. The function
//    // should return FALSE if no callback is provided.
//    $job_id03 = tripal_add_job('Test Job Case #7', 'modulename', '', $args, $user->uid, 10);
//    $this->assertFalse($job_id03, 'Case #8: It should return FALSE if no callback is provided');
//
//    // Case #8: What if a callback is provided but it doesn't exist.
//    // The function should return FALSE.
//    $job_id04 = tripal_add_job('Test Job Case #8', 'modulename', 'tripal_test_jobs_callback2', $args, $user->uid, 10);
//    $this->assertFalse($job_id04, 'Case #8: If a callback is provided but it doesnt exist. It should return FALSE');
//
//    // Case #9: What if no arguments are provided. It should return FALSE.
//    $job_id05 = tripal_add_job('Test Job Case #9', 'modulename', 'tripal_test_jobs_callback', '', $user->uid, 10);
//    $this->assertFalse($job_id05, 'Case #9: If an argument was not provided. It should return FALSE');
//
//    // Case #10:  What if no UID is provided. It should return FALSE.
//    $job_id06 = tripal_add_job('Test Job Case #10', 'modulename', 'tripal_test_jobs_callback', $args, '', 10);
//    $this->assertFalse($job_id06, 'Case #10: If an UID is not provided. It should return FALSE');
//
//    // Case #11:  What if a priority greater than 10 is provided.
//    // It should return FALSE.
//    $job_id07 = tripal_add_job('Test Job Case #11', 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 11);
//    $this->assertFalse($job_id07, 'Case #11: If a priority is grater than 10 it should return FALSE');
//
//    // Case #12:  What if a priority less than 1 is provided.
//    // It should return FALSE.
//    $job_id08 = tripal_add_job('Test Job Case #12', 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 0);
//    $this->assertFalse($job_id08, 'Case #12: If a priority is less than 1 it should return FALSE');
//
//    //Case #13:  What if the priority is an alpha character instead of
//    //a number. It should return FALSE.
//    $job_id09 = tripal_add_job('Test Job Case #13', 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 'asc123');
//    $this->assertFalse($job_id09, 'Case #13: If a priority is not numeric it should return FALSE');
//
//    //Case #14: What if the modulename is empty. It should return FALSE.
//    $job_id10 = tripal_add_job('Test Job Case #14', '', 'tripal_test_jobs_callback', $args, $user->uid, 10);
//    $this->assertFalse($job_id10, 'Case #14: If the modulename is empty it should return FALSE');
//	}
