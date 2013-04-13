<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * True-false question definition class.
 *
 * @package    qtype
 * @subpackage truefalse
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once($CFG->dirroot.'/question/behaviour/adaptiveweighted_queued/behaviour.php');
require_once($CFG->dirroot.'/local/onlinejudge/judgelib.php');

defined('MOODLE_INTERNAL') || die();


/**
 * Represents a true-false question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_onlinejudge_question extends question_graded_automatically 
{

    /**
     * Creates the question behavior that should handle this question.
     *
     * This question type takes a while to grade, and thus requires a queued
     * grading type.
     */ 
    public function make_behaviour(question_attempt $qa, $preferredbehavior) {
        //TODO: Allow manual grading (allow a base queued behavior to decide based on preferredbehavior)?.
        return new qbehaviour_adaptiveweighted_queued($qa, $preferredbehavior); 
    }

    /**
     * Returns an array containing the data expected in a valid submission of a VHDL question.
     */
    public function get_expected_data() {
        //Expect the user to submit one or more files.
        //TODO: Allow the user to enter code directly.
        return array('answer' => question_attempt::PARAM_FILES, 'answerraw' => PARAM_RAW, 'attemptid' => PARAM_INT);
    }

    /**
     * Indicates that no sample "correct response" is available.
     */
    public function get_correct_response() {
        return null;
    }

    
    /**
     * Queues grading of the provided response.
     * Used by the question behavior to submit this question for grading.
     * 
     * @param array $response The repsonse to be graded.
     * @param int $quba_id The ID of the QUBA that owns this question.
     * @param int $slot The slot that identifies the given question.
     * @return int A unique ID identifing the task in the relevant work queue.
     */ 
    public function queue_grading(array $response, $quba_id, $slot) {

        //Extract each of the files for grading...
        $files = $this->get_files_for_grading($response);
        $userid  = reset($files)->get_userid();

        //TODO: Set up options here.
        $options = new stdClass;

        //Submit the code for evaluation.
        return onlinejudge_submit_task($quba_id, $userid, $this->judge, $files, 'qtype_onlinejudge', $options, $slot);
    }

    /**
     * Returs true iff the given queued grading task is complete.
     *
     * @param int $task_id The unique ID for the task, as returned by queue_grading.
     * @return bool True iff the given task is graded.
     */
    public function queued_grading_is_complete($task_id) {

      //Fetch the given task's status...
      $task = online_judge::get_task_record($task_id);

      //Return true (indicating that grading is compelte) if we're neither waiting in line nor currently judging the task.
      return ($task->status != ONLINEJUDGE_STATUS_PENDING) && ($task->status != ONLINEJUDGE_STATUS_JUDGING);
    }

    /**
     * Returns the result of a queued grading task.
     *
     * @param int $task_id The unique ID for this task, as returned by queue_grading.
     * @return array An associative array containing each of the results of the queued
     *    grading, in a format easily converted to QT vars. Each key represents a variable
     *    name; and each value the QT var's value.
     */
    public function get_queued_grading_result($task_id) {

        //Fetch the given task's database record...
        $task = online_judge::get_task_record($task_id);

        //And return it, converted to an array.
        return (array)$task;
    
    }


    /**
     * @return bool True iff the given response should be queued for grading.
     */
    public function is_complete_response(array $response)
    {
        //if the response does not specify any files, it must be incomplete
        //if(!$this->files_specified($response))
        //  return false;

        //TODO

        return true;
    }

    /**
     * Returns true iff the given response is gradeable.
     * TODO: Rename, as not to appear to implement the standard API.
     */
    public function is_gradable_response(array $response)
    {
        //any complete response is gradeable
        return $this->is_complete_response($response);
    }

    /**
     * Determines if the given response is gradable _after_ parsing by the Online Judge
     * worker daemon.
     */
    public function post_process_response_is_gradable(array $response) {
        return array_key_exists('_status', $response) && array_key_exists('_output', $response) 
            && $response['_status'] == ONLINEJUDGE_STATUS_ACCEPTED;
    }

    /**
     * Determines the grades for a known-valid response.
     */
    function grade_response(array $response) {

        //Get the grade according to the testbench, out of 100.
        list($grade, ) = $this->parse_testbench_output($response);

        //Divide it by 100 in order to normalize the to a fraction.
        $grade /= 100.0;

        //And return it.
        return array($grade, question_state::graded_state_for_fraction($grade));
    }


    /**
     * Determines the amount of files specified by the given response.
     */ 
    protected function files_specified(array $response)
    {
        global $USER;

        //If no user was specified, use the current user.
        $user_id = empty($response['userid']) ? $USER->id : $response['userid'];

        //Get a reference to the file storage singleton.
        $file_storage = get_file_storage();

        //attempt to get the uploaded file, if possible
        $user_design = $file_storage->get_area_files($this->contextid, 'question', 'response_answer', $response['attemptid'], 'sortorder', false);

        //If we can't find the files associated with the attempt, check the user's draft area.
        if(!count($user_design) && !empty($response['answerraw'])) {
          $user_context = context_user::instance($user_id);
          $user_design = $file_storage->get_area_files($user_context->id, 'user', 'draft', $response['answerraw']);
        }

        //and return the amount of files in the user design
        return (count($user_design));
    }

    /**
     * Attempts to extract all of the submitted files from the given response.
     * @param array $response The response (qt data) for the given question submission.
     * @return array An array of stored_file objects containing each file uploaded by
     *     the user.
     */ 
    protected function get_files_from_response(array $response) {
        global $USER;

        //If the user didn't submit any files, return.
        //TODO: Perhaps this doesn't need to be here-- e.g. for regrading?
        if(!array_key_exists('answerraw', $response)) {
            return;
        }

        //first, get a reference to Moodle's file storage controller
        $file_storage = get_file_storage();

        //and get a reference to the current user's draft files (which house the newly uploaded file)
        $user_context = context_user::instance($USER->id);

        //attempt to get the uploaded file, if possible
        $user_design = $file_storage->get_area_files($this->contextid, 'question', 'response_answer', $response['attemptid'], 'sortorder', false);

        //if this fails, retrieve the local copy from the draft area
        if(!count($user_design)) {
            $user_design = $file_storage->get_area_files($user_context->id, 'user', 'draft', $response['answerraw']);
        }

        return $user_design;
    }

    /**
     * Attempts to gather all of the files needed to grade a given response.
     * @param array $response The response (qt data) for the given question submission.
     * @return array An array of stored_file objects containing each file uploaded by
     *     the user.
     */ 
    protected function get_files_for_grading(array $response) {

        //first, get a reference to Moodle's file storage controller
        $file_storage = get_file_storage();

        //Get the user's design.
        $user_design = $this->get_files_from_response($response);

        //And get all of the stored files included in the testbench.
        $testbench = $file_storage->get_area_files($this->contextid, 'qtype_onlinejudge', 'testbench', $this->testbench);

        //Get an array that contains each of the filenames reserved for testbench use.
        $testbench_filenames = array_map(function($file) { return $file->get_filename(); }, $testbench);

        //Remove any user files whose filenames conflict with the instructor's testbench files.
        $user_design = array_filter($user_design, function($file) use($testbench_filenames) { return !in_array($file->get_filename(), $testbench_filenames); } );

        //Return an array containing all files used for grading.
        return array_merge($user_design, $testbench);

    }

    /**
     * Summates all of the comments extracted from the testbench.
     * @param array $response The response returned from the queued grading system.
     * @return array An array containing [$grade, $remarks]-- the grade out of 100, and an array containing each
     *    of the contined remarks in [grade, remark] format.
     */
    public function parse_testbench_output($response) {

        //A test-case that fails no assertions should be
        //silent; and thus have the maximum possible grade.
        $grade = 100;

        //Create an array to store each of the relevant instructor comments.
        $remarks = [];

        //Parse the test-case output.
        $marks = explode("\n", $response['_output']);

        //For each of the marks in the test-case output,
        //adjust the grade.
        foreach($marks as $mark) {

            //Skip empty marks.
            if(empty($mark)) {
              continue;
            }

            //Break the grade into its components.
            $remark = explode('|', $mark);
            $remarks[] = $remark;

            //Add the mark to the assignment's grade.
            $grade += intval($remark[0]);
        }

        //Return the final grade.
        return array($grade, $remarks);
    }

    /**
     * Returns each of the remarks contained in the testbench.
     * @param array $response The response returned from the queued grading system.
     * @return array An array of arrays, which each contain [$grade, $remark]-- the total point adjustment
     *    associated with the comment, and the explanatory remark.
     */
    public function get_testbench_remarks($response) {
        list(,$remarks) = $this->parse_testbench_output($response);
        return $remarks;
    }

    /**
     * Converts from the database shorthand for a language to an array of valid file extensions
     * for that language (as determined by ISIM). 
     */
    private static function elaborate_types($types)
    {
    	switch($types)
    	{
        case 'fsm':
            return array('fsm');
            case 'sch':
            return array('sch', 'sym');
            case 'vhdl':
            return array('vhd', 'vhdl');
          case 'verilog':
            return array('v');
          case 'true':
            return array('vhd', 'vhdl', 'v');
          case 'all':
          default:
            return array('sch', 'vhd', 'vhdl', 'v');	
    	
    	}
    } 


    /**
     * Returns true iff $a and $b both refer to the same response.
     * This is used to prevent duplicate submissions from being graded. 
     */
    public function is_same_response(array $a, array $b)
    {
      //FIXME FIXME FIXME 
      return false;
    }


    /**
     * Returns a short-but-compelte summary of the given response.
     */
    public function summarise_response(array $response)
    {
        //TODO: fixme
        return 'waveform';
    }

     /**
     * Returns an error message if the given response doesn't validate (isn't complete),
     * or null if the response is gradeable.
     */
    public function get_validation_error(array $response)
    {
        //FIXME: todo
        return null;

    }

    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) 
    {
        if ($component == 'question' && $filearea == 'response_answer') 
        {
            return true;
        } 
        else if ($component == 'question' && $filearea == 'hint') 
        {
            return $this->check_hint_file_access($qa, $options, $args);
        }
        else 
        {
            return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload);
        }
    }
   
    /**
     * Summates all of the task comments in the testbench.
     */
    public static function extract_task_comments($task, $max_grade = 100) {

        //A test-case that fails no assertions should be
        //silent; and thus have the maximum possible grade.
        $grade = $max_grade;

        //Create an array to store each of the relevant instructor comments.
        $remarks = [];

        //Parse the test-case output.
        $marks = explode("\n", $task->output);

        //For each of the marks in the test-case output,
        //adjust the grade.
        foreach($marks as $mark) {

            //Skip empty marks.
            if(empty($mark)) {
              continue;
            }

            //Break the grade into its components.
            $remark = explode('|', $mark);
            $remarks[] = $remark;

            //Add the mark to the assignment's grade.
            $grade += intval($remark[0]);
        }

        //Return the final grade.
        return array($grade, $remarks);;
    }




}
