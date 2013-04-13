<?php
/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_onlinejudge
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if(!defined('MOODLE_INTERNAL'))
	die();
	
require_once($CFG->libdir.'/form/filemanager.php');

/////////////////
/// HDL Sim.  ///
/////////////////

/// QUESTION TYPE CLASS //////////////////
/**
 * @package questionbank
 * @subpackage questiontypes
 */
class qtype_onlinejudge extends question_type 
{

	/**
	 * Short name for the question type.
	 */
    function name() {
        return 'onlinejudge';
    }
    
    /**
     * Specifies the database table and columns used to store the question options.
     */
	function extra_question_fields() {
    	return array('question_onlinejudge', 'judge', 'allowmulti', 'testbench', 'autofeedback');
    }
    
    /**
     * The name of the 'id' row in the custom database table.
     */
   	function questionid_column_name() {	
		return 'question';
    }

    /**
     * Indicates which "file areas" this question uses for its user responsese.
     */
    public function response_file_areas() {
        //indicate that this question only expects files in its answers
        return array('answer');
    }

    public function get_question_options($question) {
        global $DB;
        $question->options = $DB->get_record('question_onlinejudge', array('question' => $question->id), '*', MUST_EXIST);
        parent::get_question_options($question);
    }

    public function save_question_options($formdata) 
    {
        $draftitemid = file_get_submitted_draft_itemid('testbench');
        file_save_draft_area_files($formdata->testbench, $formdata->context->id, 'qtype_onlinejudge', 'testbench', $draftitemid, $this->fileoptions);
        $this->save_hints($formdata);
        parent::save_question_options($formdata);
    }

    
    /**
     * Deletes ancillary information along with the question.
     */ 
    function delete_question($questionid, $contextid) 
    {
        global $DB;
        $DB->delete_records('question_onlinejudge', array('question' => $questionid));

        //TODO: Consider deleting the submitted testbench along with the file.
        
        parent::delete_question($questionid, $contextid);
    }

    /**
     * 
     * Grades a given response using the ISIM simulator.
     * @param $question 	The question information, including instructor configuration.
     * @param $state 		The student's response to the question.
     */
    function grade_responses(&$question, &$state, $cmoptions) 
    {
    	
    	//run the simulation (which might take a while; hopefully won't be noticible with the page load latency)
    	try
    	{
			//create a new HDL Simulation object
    		$sim = new HDLSimulation($question->options->testbench, $state->responses[''], self::elaborate_types($question->options->hdltype));
		
    	
    		//start sending to the user as we run the simulation	
	    	flush();
	    	$sim->run_simulation();
	    	$sim->cleanup();
	    	
			//get the grade and comments from the reference testbench    	
	    	$state->raw_grade = $sim->get_grade() * $question->maxgrade;
	
	    	//if the automatic feedback option is enabled, provide feedback
	    	if($question->options->autofeedback)
				$state->manualcomment = $sim->get_marks_str($question->maxgrade);
	
    	}
    	//if a SimulationException occurred (almost always due to user error, though server load might play a factor)
    	catch(SimulationException $e)
    	{
    		//display the error message provided by the SimulationException (user-safe; reveals no technical detail about the server)
			$state->manualcomment = $e->getMessage();
			
			//do not award any points for this reponse 
	    	$state->raw_grade = 0;
	    	
	    	//but do no assess any penalty 
	    	
	    	//mark the state as graded, so the user can see the result
	        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
	        	
	        return true;
    	}
    	
    	catch(RemoteException $e)
    	{
			$state->manualcomment = get_string('remote_issue');    
			
			//do not award any points for this reponse 
	    	$state->raw_grade = 0;
	    	
	    	//but do no assess any penalty 
	    	
	    	//mark the state as graded, so the user can see the result
	        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;
	        	
	        return true;
    	}
    	
    	
      	// Update the penalty.
        $state->penalty = $question->penalty * $question->maxgrade;

        // mark the state as graded
        $state->event = ($state->event ==  QUESTION_EVENTCLOSE) ? QUESTION_EVENTCLOSEANDGRADE : QUESTION_EVENTGRADE;

        return true;
    }

    /**
     * @param object $question
     * @return mixed either a integer score out of 1 that the average random
     * guess by a student might give or an empty string which means will not
     * calculate.
     */
    function get_random_guess_score($question) 
    {
    	//about as likely as monkeys typing hamlet
        return 0;
    }
    
    
    /*
     * BEGIN UNMODIFIED MOODLE CORE CODE
     */
    
    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $fs = get_file_storage();
        $fs->move_area_files_to_new_context($oldcontextid, $newcontextid, 'qtype_onlinejudge', 'testbench', $questionid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $fs = get_file_storage();
        $fs->delete_area_files($contextid, 'qtype_onlinejudge', 'testbench', $questionid);
    }


    

    function check_file_access($question, $state, $options, $contextid, $component, $filearea, $args) 
    {
        if ($component == 'question' && $filearea == 'response_answer') 
        {
            return true;
        }

        elseif ($component == 'question' && $filearea == 'hint')
        {
            return true;
        }
        elseif ($component == 'question' && $filearea == 'answerfeedback') 
        {

            $answerid = reset($args); // itemid is answer id.
            $answers = &$question->options->answers;
            if (isset($state->responses[''])) 
            {
                $response = $state->responses[''];
            } 
            else 
            {
                $response = '';
            }

            return $options->feedback && isset($answers[$response]) && $answerid == $response;

        } 
        else 
        {
            return parent::check_file_access($question, $state, $options, $contextid, $component, $filearea, $args);
        }
    }
   
}

