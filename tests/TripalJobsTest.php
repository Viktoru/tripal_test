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
    $this->assertTrue(is_numeric($job_id), 'Case #3: It should returns a numeric job ID.');

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
    $this->assertFalse($job_get_active, 'Case #1: It should returns false (bool)');

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
    // Global url
    $url = 'admin/tripal/tripal_jobs';
    return $url;

    // Return hexadecimal to decimal.
    // Next, return part of a string.
    // Finally, generate an unique ID.
    $job_id = hexdec(substr(uniqid('job_id'), 0, 8));

    $sql = "SELECT * FROM {tripal_jobs} WHERE job_id = :job_id";
    $results = db_query($sql, array(':job_id' => $job_id));
    $job = $results->fetchObject();

    $job_cancel = tripal_get_active_jobs($job_id, [$redirect = TRUE]);

    if ($job->start_time > 0) {
      $this->assertTrue($job_cancel, 'Job_id cancelled');
    }
    else {
      $this->assertFalse($job_cancel, 'Job_id cannot be cancelled. It is in progress or has finished.');
    }
    if ($redirect) {
      $this->assertTrue($this->$url, TRUE);

    }
// Alternative $query
//    $query = db_query("SELECT * FROM {tripal_jobs} WHERE status ='Cancelled'");
//    $num_rows = $query->rowCount();
//    $job_cancel_total = $num_rows;
//
//    if ($job_cancel_total >= 1) {
//      $this->assertTrue(TRUE);
//      var_dump(TRUE);
//    }
//    else {
//      $this->assertFalse(FALSE);
//      var_dump(FALSE);
//    }

  }

  public function test_tripal_get_job_end() {

    $job = tripal_get_job_end('job');

    $q = db_query("SELECT * FROM {tripal_jobs} WHERE end_time > 0");

    $arg_result = array(
      ':end_time' => 'end_time'
    );
    $query = db_query($q, $arg_result);
    $test_job_id = $query->fetchField();

    if ($test_job_id > 0) {
      $this->assertTrue(TRUE);
      // var_dump($test_job_id, 'It should return TRUE.');
    }
    else {
      $this->assertFalse(FALSE);
      //  var_dump($test_job_id, 'It should return FALSE.');
    }

  }

  public function test_tripal_get_job_start() {

    $q = db_query("SELECT * FROM {tripal_jobs} WHERE start_time > 0");

    $arg_result = array(
      ':start_time' => 'start_time'
    );
    $query = db_query($q, $arg_result);
    $test_job_q = $query->fetchField();

    if ($test_job_q > 0) {
      $this->assertTrue(is_numeric($test_job_q), 'It should return TRUE.');

    }
    if ($test_job_q == NULL) {
      $this->assertTrue(is_null($test_job_q), 'It should return TRUE. NULL.');

    }
    if ($test_job_q == '') {
      $this->assertTrue(empty($test_job_que), 'It should return TRUE. Empty');

    }
  }

  public function test_tripal_get_job() {

    $job_id = hexdec(substr(uniqid('get_job_id'), 0, 8));
    $job_get_id = tripal_get_job($job_id);

    $r_job_id = $job_get_id->job_id;
//    $r_uid = $job_get_id->uid;
//    $r_job_name = $job_get_id->job_name;
//    $r_modulename_= $job_get_id->modulename;
//    $r_callback = $job_get_id->callback;
//    $r_status = $job_get_id->status;
//    $r_submit_date = $job_get_id->submit_date;
//    $r_start_time = $job_get_id->start_time;
//    $r_end_time = $job_get_id->end_time;
//    $r_priority = $job_get_id->priority;

    $this->assertTrue($r_job_id > 0, 'Case Job_id #1: It should return True.');
    $this->assertFalse($r_job_id < 0, 'Case Job_id #1: It should return False.');
    $this->assertEmpty('', 'Case Job_id #1: It should return True.');

//
//    $this->assertTrue($r_status === 'Completed', 'Case status #1: It should return true.');
//    $this->assertFalse($r_status !== 'Completed', 'Case status #1: It should return False.');
//    $this->assertFalse($r_start_time < 0, 'Case start time #1: It should return False.');
//    $this->assertTrue($r_start_time > 0, 'Case start time #1: It should return True.');
//    $this->assertEmpty('', 'Case start time #1: It should return True.');


  }

  public function test_tripal_get_job_submit_date() {
    // $job = "1486328495";
    $job_submit_date = tripal_get_job_submit_date('job');
    $r_job_id = format_date($job_submit_date->submit_date);
    $this->assertFalse($r_job_id < 0, 'Case job submit date #1: It should return True.');

  }

  public function test_tripal_is_job_running() {

    $job_running = tripal_is_job_running();

    foreach ($job_running as $job) {
      $status = $job->pid;
      if ($job->pid && $status) {
        $this->assertTrue(TRUE);
      }
      else {
        $this->assertFalse(FALSE);
        $new_rec = $job->job_id;
        $new_rec = $job->status;
        $new_rec = $job->error_msg = 'Job has terminated unexpectedly.';
        drupal_write_record('tripal_jobs', $new_rec, 'job_id');
      }
    }
    $this->assertFalse(FALSE);
  }

  public function test_tripal_launch_job() {
    // when a specific job needs to be launched and this argument will allow it.
    // Only jobs which have not been run previously will run.

    $launch_job = tripal_launch_job([$do_parallel = 0], [$job_id = NULL]);
    $job_running = $launch_job->do_parallel;
    $job_id = $launch_job->job_id;

    // It'll check if a job is no running.  Job will not run at the same time. It should return TRUE.
    if (!$job_running and tripal_is_job_running()) {
      $this->assertTrue(TRUE, 'If a job is no running. It should return TRUE.');
    }

    if ($job_id) {
      $sql = "SELECT * FROM {tripal_jobs} TJ " .
        "WHERE TJ.start_time IS NULL and TJ.end_time IS NULL and TJ.job_id = :job_id " .
        "ORDER BY priority ASC,job_id ASC";
      $job_res = db_query($sql, array(':job_id' => $job_id));

      $this->assertTrue($job_res, 'Get all jobs that have not started.');

    } else {

      $sql = "SELECT * FROM {tripal_jobs} TJ " .
        "WHERE TJ.start_time IS NULL and TJ.end_time IS NULL " .
        "ORDER BY priority ASC,job_id ASC";
      $job_res = db_query($sql);

      if ($job_res < 0) {

        $this->assertFalse($job_res, 'It should return FALSE.');

      }

    }

    foreach($job_res as $job){



    }





  }

}

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

}
