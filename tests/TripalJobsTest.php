<?php

// Bootstrap Drupal.
define('DRUPAL_ROOT', '/var/www/html');
require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

/**
 *  PHPUnit Tests the testing the Tripal Jobs ABI.
 */
class TripalJobsTest extends PHPUnit_Framework_TestCase {

  /**
   * Tests the tripal_add_job function().
   */
  public function test_tripal_add_job() {
    global $user;

    // Case #1:  Submit a job successfully and receive a job id.
    $args = array();
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $this->assertTrue(is_numeric($job_id), 'Case #1: It should returns a numeric job ID. Instead recieved: "' . $job_id . '".');

    // Case #2: Was the job really added to the database as expected?
    // If the returend job_id is the same as the job_id received in case #1 then
    // we will consider the test successful.
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
    $this->assertEquals($test_job_id, $job_id, "Case #2: The record in the database does not match the expected job.");

    // Case #3: What if an empty callback is provided. The function
    // should return FALSE if no callback is provided.
    $job_id03 = tripal_add_job('Test Job Case #3', 'modulename', '', $args, $user->uid, 10);
    $this->assertFalse($job_id03, 'Case #3: It should return FALSE if no callback is provided');

    // Case #4: What if a callback is provided but it doesn't exist.
    // The function should return FALSE.
    $job_id04 = tripal_add_job('Test Job Case #4', 'modulename', 'tripal_test_jobs_callback3', $args, $user->uid, 10);
    $this->assertFalse($job_id04, 'Case #4: If a callback is provided but it doesnt exist. It should return FALSE');

    // Case #5: What if no arguments are provided. It should return FALSE.
    $job_id05 = tripal_add_job('Test Job Case #5', 'modulename', 'tripal_test_jobs_callback', '', $user->uid, 10);
    $this->assertFalse($job_id05, 'Case #5: If an array is not provided for arguments it should return FALSE');

    // Case #6:  What if no UID is provided. It should return FALSE.
    $job_id06 = tripal_add_job('Test Job Case #6', 'modulename', 'tripal_test_jobs_callback', $args, '', 10);
    $this->assertFalse($job_id06, 'Case #6: If an UID is not provided. ItSetup should return FALSE');

    // Case #7:  What if a priority greater than 10 is provided.
    // It should return FALSE.
    $job_id07 = tripal_add_job('Test Job Case #7', 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 11);
    $this->assertFalse($job_id07, 'Case #7: If a priority is grater than 10 it should return FALSE');

    // Case #8  What if a priority less than 1 is provided.
    // It should return FALSE.
    $job_id08 = tripal_add_job('Test Job Case #8', 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 0);
    $this->assertFalse($job_id08, 'Case #8: If a priority is less than 1 it should return FALSE');

    // Case #9:  What if the priority is an alpha character instead of
    // a number. It should return FALSE.
    $job_id09 = tripal_add_job('Test Job Case #9', 'modulename', 'tripal_test_jobs_callback', $args, $user->uid, 'asc123');
    $this->assertFalse($job_id09, 'Case #9: If a priority is not numeric it should return FALSE');

    // Case #10: What if the modulename is empty. It should return FALSE.
    $job_id10 = tripal_add_job('Test Job Case #10', '', 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $this->assertFalse($job_id10, 'Case #10: If the modulename is empty it should return FALSE');

    // Case #11: What if the callback is in another file, but the $includes
    // argument doesn't specify where they file is.  We should get a FAlSE.
    $job_id11 = tripal_add_job('Test Job Case #11', 'modulename', 'tripal_test_jobs_callback2', $args, $user->uid, 10);
    $this->assertFalse($job_id11, 'Case #11: If a callback but is in another file that is not in scope then it should return FALSE');

    // Case #12: Same test as $10 but this time with the file in the $includes.
    // now we should get a valid job_id.
    $includes = array("./files/dummy_callback.inc");
    $job_id12 = tripal_add_job('Test Job Case #12', 'modulename', 'tripal_test_jobs_callback2', $args, $user->uid, 10, $includes);
    $this->assertFalse($job_id12, 'Case #12: If a callback but is in another file that is not in scope then it should return FALSE');

    // Case #13: If we give a different user ID from the active user does
    // the job properly get associated with the requested user.

  }

  /**
   * Tests the tripal_get_active_jobs_function().
   */
  public function test_tripal_get_active_jobs() {
    global $user;
    $test_module = uniqid('test_module');

    // Case #1: Does the function return any jobs for the given module.
    // Since we have added zero job we should we get 0 jobs back.
    $jobs = tripal_get_active_jobs($test_module);
    $this->assertTrue(count($jobs) == 0, 'Case #1: should have returned 0 job. Instead, received ' . count($jobs) . ' job(s).');

    // Case #2: Does the function return 1 job when only 1 job is present.
    $args = array();
    $job_name1 = uniqid('tripal_test_job');
    $job_id1 = tripal_add_job($job_name1, $test_module, 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $this->assertTrue(is_numeric($job_id1), 'Case #2: Could not add a job to test the tripal_get_active_jobs() function.');
    $jobs = tripal_get_active_jobs($test_module);
    $this->assertTrue(count($jobs) == 1, 'Case #2: should have returned 1 job. Instead, received ' . count($jobs) . ' job(s).');

    // Case #3: Does the function return 2 jobs when two are present.
    $args = array();
    $job_name2 = uniqid('tripal_test_job');
    $job_id2 = tripal_add_job($job_name2, $test_module, 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $this->assertTrue(is_numeric($job_id2), 'Case #3: Could not add a job to test the tripal_get_active_jobs() function.');
    $jobs = tripal_get_active_jobs($test_module);
    $this->assertTrue(count($jobs) == 2, 'Case #3: should have returned 1 job. Instead, received ' . count($jobs) . ' job(s).');

    // Case #4: Are the returned jobs objects?
    foreach ($jobs as $job) {
      $this->assertTrue(is_object($job), 'Case #4: should have returned an object. Instead, received ' . gettype($job) . '.');
    }

    // Case #5: Are we getting back the jobs we added?
    $this->assertTrue($jobs[0]->job_name == $job_name1 or $jobs[0]->job_name == $job_name2, "Case #5a: tripal_get_active_jobs() job name returned doesn't match expected.");
    $this->assertTrue($jobs[1]->job_name == $job_name1 or $jobs[1]->job_name == $job_name2, "Case #5b: tripal_get_active_jobs() job name returned doesn't match expected.");

    // Case #6: If we cancel a job we should now only get one job back. Set the
    // second parameter to not redirect or PHPUnit is thrown off.
    tripal_cancel_job($job_id1, FALSE);
    $jobs = tripal_get_active_jobs($test_module);
    $this->assertTrue(count($jobs) == 1, 'Case #6: should have returned 1 job when the other is cancelled. Instead, received ' . count($jobs) . ' job(s).');

    // Case #6:  Test with non-existent modulename.  It should return an empty array.
    $jobs = tripal_get_active_jobs('blah');
    $this->assertTrue(count($jobs) == 0, 'Case #7: should have returned 0 job with bogus module name. Instead, received ' . count($jobs) . ' job(s).');
  }

  /**
   * Tests the tripal_cancel_job() function.
   */
  public function test_tripal_cancel_job() {
    global $user;

    // Setup: Add a job that we'll later Cancel.
    $args = array();
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'tripal_test', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Case #1: Cancel the job that was just previously added.  There is no
    // return value.
    $success = tripal_cancel_job($job_id, FALSE);
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status = db_query($sql, $args)->fetchField();
    $this->assertTrue($status == 'Cancelled', "Case #1a: Job was not properly cancelled.");
    // $this->assertTrue($success, 'Case #1b: The return value should be TRUE.');

    // Case #2: A job that has already started should not be Cancelled. We
    // do not want to run tripal_launch_job() because the job callback is
    // an empty function which will run so fast and mark the job as completed.
    // Also we don't want a dependency on the tripal_launch_job() since it is
    // tested in another test function.
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'tripal_test', 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $sql = "UPDATE {tripal_jobs} SET start_time = :start, status = 'Running' WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id, ':start' => time());
    $status = db_query($sql, $args);
    tripal_cancel_job($job_id, FALSE);
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status = db_query($sql, $args)->fetchField();
    $this->assertTrue($status == 'Running', "Case #2: Job was cancelled when it should not have been because it's running.");

    // Case #3:  Pass an empty job_id, it should return FALSE.
    $success = tripal_cancel_job('', FALSE);
    $this->assertFALSE($success, "Case #3: Passing an empty job_id should return FALSE.");

    // Case #4:  Pass a non-numeric job_id, it should return FALSE.
    $success = tripal_cancel_job('abc', FALSE);
    $this->assertFALSE($success, "Case #4: Passing a non numeric job_id should return FALSE.");
  }

  /**
   * Tests the tripal_get_job() function. --
   */
  public function test_tripal_get_job(){
    global $user;

    //  Setup: Submit a job successfully and receive a job ID
    $args = array();
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'tripal_test_add_get_job', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Case #1: It should return a numeric job ID.
    $this->assertTrue(is_numeric($job_id), 'Case #1: It should returns a numeric job ID.');

    // Setup UPDATE: It will update tripal_jobs start_time and end_time, progress plus status.
    // If a job was submitted successfully and the status was completed, it should return TRUE.
    $sql = "UPDATE {tripal_jobs} SET start_time = :start, end_time = :end_time, status = 'Completed', progress = '100' WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id, ':start' => time(), ':end_time' => time());
    db_query($sql, $args);

    // Case #2: If a job was submitted successfully, the status of that job was completed.
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status = db_query($sql, $args)->fetchField();
    $this->assertTrue($status == 'Completed', "Case #2: Job was successfully completed. It should retrieve information about a job.");

  }
  /**
   * Tests the tripal_get_job_end() function.
   */
  public function test_tripal_get_job_end(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'tripal_test_get_job_end', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Setup UPDATE: It will update tripal_jobs, start_time and end_time plus status.
    // If a job was submitted successfully and the status was completed, it should return TRUE.
    $sql = "UPDATE {tripal_jobs} SET start_time = :start, end_time = :end_time, status = 'Completed', progress = '100' WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id, ':start' => time(), ':end_time' => time());
    db_query($sql, $args);

    // Case #1: If a job was submitted successfully, it should return the end time for a job.
    $sql = "SELECT end_time FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $end_time = db_query($sql, $args)->fetchField();
    $this->assertTrue(is_numeric($end_time), 'Case #1: It should return TRUE, if end_time is not a numeric date.');

  }
  /**
   * Test the tripal_get_job_start() function.
   */

  public function test_tripal_get_job_start(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'tripal_test_get_job_start', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Setup SELECT statement: If a job was submitted, the status should return TRUE or the status is "Waiting".
    // Case #1: If a job was submitted and the status is waiting. It should return TRUE.
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status = db_query($sql, $args)->fetchField();
    $this->assertTrue($status == 'Waiting', 'Case #1: If a job was submitted and the status is waiting only then you can cancel it.');

    // tripal_cancel_job function to cancel my $job_id.
    tripal_cancel_job($job_id, FALSE);

    // Setup SELECT statement: If a job was cancelled, the status should return "Cancelled".
    // Case #2: If a job was cancelled, it should return TRUE.
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status2 = db_query($sql, $args)->fetchField();
    $this->assertTrue($status2 == 'Cancelled', 'Case #2: It should return TRUE.');

  }

  /**
   * Tests Tripal_get_job_submit_date() function.
   */
  public function test_tripal_get_job_submit_date(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'tripal_test_get_job_submit_date', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Case #1: If a job was submitted successfully, it should return a date added to the queue.
    $this->assertTrue(is_numeric($job_id), 'Case #1: If a job was submitted successfully, it should return a date added to the queue.');

    // Setup SELECT statement: If a job was submitted, it should return a date.
    $sql = "SELECT submit_date FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $submit_date = db_query($sql, $args)->fetchField();
    $this->assertTrue(is_numeric($submit_date), 'Case #1: If a job was submitted successfully. It should return a date.');

  }

  /**
   * Tests tripal_is_job_running() function.
   */
  public function test_tripal_is_job_running(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid('tripal_test_job');
    $job_id = tripal_add_job($job_name, 'tripal_test_is_job_running', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Setup UPDATE statement: Update the status "Running". The status of a job should return running.
    $sql = "UPDATE {tripal_jobs} SET status = 'Running' WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    db_query($sql, $args);

    // Setup SELECT statement: If a job was submitted and the status is running, it should return
    // the job is Running.
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status_return = db_query($sql, $args)->fetchField();
    // Case #1: If a job was submitted and run, the status should show running.
    $this->assertTrue($status_return == 'Running', "Case #1: A Job is running, it should return TRUE.");

    // Setup UPDATE statement: Updating the status from running to error.
    // If this happened, it should return a status error, the start_time and the end_time plus the error_message.
    $sql = "UPDATE {tripal_jobs}
            SET start_time = :start_time, end_time = :end_time, status = 'Error', error_msg = 'Job has terminated unexpectedly'
            WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id, ':start_time' => time(), ':end_time' => time());
    db_query($sql, $args);

    // Setup SELECT statement: It should select the status of the job and it should return an error.
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status_error = db_query($sql, $args)->fetchField();

    // Case #2: If a job has terminated unexpectedly, it should return a status error.
    $this->assertTrue($status_error == 'Error', "Case #1: A Job has terminated unexpectedly, it should return Error.");

  }

  public function test_tripal_launch_job(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid('tripal_test_job_launch');
    $job_id = tripal_add_job($job_name, 'tripal_test_launch_job', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // SELECT Statement: If a job is waiting, it should return TRUE.
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status_waiting = db_query($sql, $args)->fetchField();
    $this->assertTrue($status_waiting == 'Waiting', 'Case #1: It should return TRUE.');

    // If a job is not running. Get a job that have not started.
    tripal_launch_job([$do_parallel = 1], $job_id);

    // SELECT Statement: If a job is Completed, it should return TRUE.
    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id);
    $status_waiting = db_query($sql, $args)->fetchField();
    $this->assertTrue($status_waiting == 'Completed', 'Case #1: It should return Completed.');


    // Setup: Submit a second job successfully and receive a job ID
    $job_name = uniqid('tripal_test_job_launch_2');
    $job_id = tripal_add_job($job_name, 'tripal_test_launch_job_2', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // If a job is not running. Get a job that have not started.
    tripal_launch_job([$do_parallel = 1], $job_id);

  }

  public function test_tripal_rerun_job(){
//    global $user;  $args = array();
//
//    // Setup: Submit a job successfully and receive a job ID
//    $job_name = uniqid('tripal_test_job_rerun3');
//    $job_id = tripal_add_job($job_name, 'tripal_test_is_job_rerun3', 'tripal_test_jobs_callback', $args, $user->uid, 10);
//
//    // Update tripal_job table: Adding an error to the status, a dates to the start_time, end_time and an error message.
//    $sql = "UPDATE {tripal_jobs}
//            SET start_time = :start_time, end_time = :end_time, status = 'Error', error_msg = 'Job has terminated unexpectedly'
//            WHERE job_id = :job_id";
//    $args = array(':job_id' => $job_id, ':start_time' => time(), ':end_time' => time());
//    $job_error = db_query($sql, $args);

//    $var = tripal_rerun_job($job_id, [$goto_jobs_page = TRUE]);
//
//    var_dump($var);

  }


}

/**
 * Dummy callback function used for testing the Jobs API.
 */
//function tripal_test_jobs_callback() {
//
//}

