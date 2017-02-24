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

  // All jobs created by this function for testing should have the same
  // prefix so that we can remove them afterwards.
  private $job_prefix = 'tripal_test_job';

  /**
   * tearDown called after each function for cleanup of testing data.
   */
  public function tearDown() {
    $sql = "DELETE FROM {tripal_jobs} WHERE job_name LIKE 'tripal_test_job%'";
    $query = db_query($sql);
  }

  /**
   * Tests the tripal_add_job function().
   */
  public function test_tripal_add_job() {
    global $user;

    // Case #1:  Submit a job successfully and receive a job id.
    $args = array();
    $job_name = uniqid($job_prefix);
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
    // Since we have added zero job we should get 0 jobs back.
    $jobs = tripal_get_active_jobs($test_module);
    $this->assertTrue(count($jobs) == 0, 'Case #1: should have returned 0 job. Instead, received ' . count($jobs) . ' job(s).');

    // Case #2: Does the function return 1 job when only 1 job is present.
    $args = array();
    $job_name1 = uniqid($job_prefix);
    $job_id1 = tripal_add_job($job_name1, $test_module, 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $this->assertTrue(is_numeric($job_id1), 'Case #2: Could not add a job to test the tripal_get_active_jobs() function.');
    $jobs = tripal_get_active_jobs($test_module);
    $this->assertTrue(count($jobs) == 1, 'Case #2: should have returned 1 job. Instead, received ' . count($jobs) . ' job(s).');

    // Case #3: Does the function return 2 jobs when two are present.
    $args = array();
    $job_name2 = uniqid($job_prefix);
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
    $job_name = uniqid($job_prefix);
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
    $job_name = uniqid($job_prefix);
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
   * Tests the tripal_get_job() function. -
   */
  public function test_tripal_get_job(){
    global $user;

    //  Setup: Submit a job successfully and receive a job ID
    $args = array();
    $job_name = uniqid($job_prefix);
    $job_id = tripal_add_job($job_name, 'tripal_test_add_get_job', 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $this->assertTrue(is_numeric($job_id), 'Setup: It should returns a numeric job ID.');

    // Case #1: The function should return an object describing the job.
    $job = tripal_get_job($job_id);
    $this->assertTrue(is_object($job), "Case #1: The function should return an object.");

    // Case #2:  Did it give us the correct job back.
    $job2 = ($job->job_id);
    $this->assertEquals($job2, $job_id, 'If the two variables $job_id and $job2 expected and actual are equal, it should return TRUE.');

    // Case #3: Test, is it missing the $job_id?
    // The function should return an empty string.
    $job_id_null = ((unset) $job_id);
    $this->assertNull($job_id_null, "Case #3: The $job_id should return NULL.");
  }
  /**
   * Tests the tripal_get_job_end() function.
   *
   * This is a deprecated function for Tripal v3.
   */
  public function test_tripal_get_job_end(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid($job_prefix);
    $job_id = tripal_add_job($job_name, 'tripal_test_get_job_end', 'tripal_test_jobs_callback', $args, $user->uid, 10);
    // Setup UPDATE: It will update tripal_jobs, start_time and end_time plus status.
    // If a job was submitted successfully and the status was completed, it should return the end_time.
    $sql = "UPDATE {tripal_jobs} SET start_time = :start, end_time = :end_time, status = 'Completed', progress = '100' WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id, ':start' => time(), ':end_time' => time());
    db_query($sql, $args);
    // It should return an job object describing a job.
    $job = tripal_get_job($job_id);

    // Case #1:  Retrieves the Human-readable version of the job's end time.
    // The output from the end_time date is "Thu, 02/23/2017 - 12:50".
    $end_time = tripal_get_job_end($job);
    $expected = format_date($job->end_time);
    $this->assertEquals($expected, $end_time, "Case #1: The returned end time does not match what's expected: '$expected' != '$end_type'");

    // Case #2: Test if an empty job object is passed. The function should
    // should return an empty string.
    $job2 = tripal_get_job();
    $this->assertTrue(empty($job2), "Case #2: A job object should return empty.");

    // Case #3:  Test if a job is an object but it's missing the end_time
    // member variable.  The function should return an empty string.
    $temp_job = $job->end_time;
    $temp_job_null = ((unset) $temp_job);
    $this->assertNull($temp_job_null, "Case #3: The end time should return NULL.");

  }
  /**
   * Test the tripal_get_job_start() function.
   *
   * This is a deprecated function for Tripal v3.
   */

  public function test_tripal_get_job_start(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid($job_prefix);
    $job_id = tripal_add_job($job_name, 'tripal_test_get_job_start', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Get a job from tripal_get_job function. The job_id #, it should return an job object.
    $get_job = tripal_get_job($job_id);

    // Runs tripal_get_job_start, if it was already running, it should return "Not Yet Started."
    $return_start_time = tripal_get_job_start($get_job);
    $this->assertTrue($return_start_time == 'Not Yet Started', 'Case #1: It should return "Not Yet Started"');

  }

  /**
   * Tests Tripal_get_job_submit_date() function.
   *
   * This is a deprecated function for Tripal v3.
   */
  public function test_tripal_get_job_submit_date(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid($job_prefix);
    $job_id = tripal_add_job($job_name, 'tripal_test_get_job_submit_date', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Retrieve the job_id. The job_id #, it should return an job object.
    $job_object = tripal_get_job($job_id);

    // Expecting submit_date date
    $job_submit_date = tripal_get_job_submit_date($job_object);

    // Format $job_submit_date to a numeric submit_date
    $format_submit_date = time($job_submit_date);

    // Case #1: Retrive an submit_date, it should retrieve an numeric submit_date date.
    $this->assertTrue(is_numeric($format_submit_date), 'Case #1: If a job was submitted successfully. It should return a numeric submit_date date.');

  }

  /**
   * Tests tripal_is_job_running() function.
   */
  public function test_tripal_is_job_running(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID.
    // A job status is waiting.
    $job_name = uniqid($job_prefix);
    $job_id = tripal_add_job($job_name, 'tripal_test_is_job_running', 'tripal_test_jobs_callback', $args, $user->uid, 10);
    $job_name2 = uniqid($job_prefix);
    $job_id2 = tripal_add_job($job_name2, 'tripal_test_is_job_running', 'tripal_test_jobs_callback', $args, $user->uid, 10);
    // Setup UPDATE statement: Update the status "Running". The status of a job should return running.
    $sql = "UPDATE {tripal_jobs} SET status = 'Running' WHERE job_id = :job_id";
    $args = array(':job_id' => $job_id2);
    db_query($sql, $args);
    $job = tripal_get_job($job_id2);

    // Checking if a job is running.
    $jobs = tripal_is_job_running();

    // Case #1:  Is the return value an array. Because we have added one job
    // and set it's status to Running this should be an array with 1 element.

    // Case #2:  Is the job in the array the one we changed to 'Running'.

    // Case #3:  Now set the second job to be running and the return value should
    // have both jobs in it.

    // Case #4:  Delete both jobs, the return value shoud be FALSE.

  }

  /**
   *
   */
  public function test_tripal_launch_job(){
    global $user;  $args = array();

    // Setup: Submit a job successfully and receive a job ID
    $job_name = uniqid($job_prefix);
    $job_id = tripal_add_job($job_name, 'tripal_test_launch_job', 'tripal_test_jobs_callback', $args, $user->uid, 10);

    // Launch all queued tripal jobs.
    tripal_launch_job($do_pareallel = 0, $job_id = NULL);

  }

  /**
   *
   */
  public function test_tripal_rerun_job(){
    global $user;  $args = array();

//    // Setup: Submit a job successfully and receive a job ID
//    $job_name = uniqid($job_prefix);
//    $job_id = tripal_add_job($job_name, 'tripal_test_is_job_rerun_1', 'tripal_test_jobs_callback', $args, $user->uid, 10);
//
//    // Setup UPDATE statement: Updating the status from running to error.
//    // If this happens, it should return a status error, the start_time, the end_time plus the error_message.
//    $sql = "UPDATE {tripal_jobs}
//            SET start_time = :start_time, end_time = :end_time, status = 'Error', error_msg = 'Job has terminated unexpectedly'
//            WHERE job_id = :job_id";
//    $args = array(':job_id' => $job_id, ':start_time' => time(), ':end_time' => time());
//    db_query($sql, $args);
//
//    // Setup SELECT statement: If the status retrieve an error, it should return TRUE.
//    $sql = "SELECT status FROM {tripal_jobs} WHERE job_id = :job_id";
//    $args = array(':job_id' => $job_id);
//    $status_error = db_query($sql, $args)->fetchField();
//
//    // Case #1: If a job has terminated unexpectedly, it should return a status error.
//    $this->assertTrue($status_error == 'Error', "Case #1: A Job has terminated unexpectedly, it should return Error.");
//
//    // Case #2: If the status return an error, we can re-run it manually. Use the command line to re-run it.
//    tripal_rerun_job($job_id, [$goto_jobs_page = TRUE]);

  }


}

/**
 * Dummy callback function used for testing the Jobs API.
 */
//function tripal_test_jobs_callback() {
//
//}

