<?php
/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_vhdl
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if(!defined('MOODLE_INTERNAL'))
	die();
	
require_once($CFG->libdir.'/form/filemanager.php');
require_once("$CFG->dirroot/question/type/vhdl/RemoteHDLSimulation.class.php");

/////////////////
/// HDL Sim.  ///
/////////////////

/// QUESTION TYPE CLASS //////////////////
/**
 * @package questionbank
 * @subpackage questiontypes
 */
class question_vhdl_qtype extends default_questiontype 
{

	/**
	 * Short name for the question type.
	 */
    function name() 
    {
        return 'vhdl';
    }
    
    /**
     * Each of the following question fields are stored in the database, and correspond to instructor preferences. 
     */
	function extra_question_fields() 
    {
    	return array('question_vhdl', 'hdltype', 'allowmulti', 'testbench', 'autofeedback');
    }
    
    /**
     * The name of the 'id' row in the custom database table.
     */
	function questionid_column_name() 
	{	
		return 'question';
	}

	
    /**
    * Loads the question type specific options for the question.
    */
    function get_question_options(&$question) 
    {
        global $DB, $OUTPUT;
        
        // Get additional information from database
        // and attach it to the question object
        if (!$question->options = $DB->get_record('question_vhdl', array('question' => $question->id))) 
        {
            echo $OUTPUT->notification('Error: Missing question options!');
            return false;
        }
        // Load the answers
        if (!$question->options->answers = $DB->get_records('question_vhdl', array('question' =>  $question->id), 'id ASC')) 
        {
           echo $OUTPUT->notification('Error: Missing question answers for vhdl question ' . $question->id . '!');
           return false;
        }

        return true;
    }

    
    /**
     * Deletes ancillary information along with the question.
     */ 
    function delete_question($questionid, $contextid) 
    {
        global $DB;
        $DB->delete_records('question_vhdl', array('question' => $questionid));

        //TODO: Consider deleting the submitted testbench along with the file.
        //(Without this, it remains in the "recent files" in the file manager, as per standard moodle behavior.)
        
        parent::delete_question($questionid, $contextid);
    }

    /**
     * Determines if the user's response remains unchanged since they hit submit the last time.
     */
    function compare_responses($question, $state, $teststate) 
    {
    	//always consider an update upon submissions (multiple submissions are handled in CSRF/security in the
    	//grading system)
    	
    	//this is (unfortunately) necessary due to the way moodle consider files
    	return false;
    }

    /**
     * Returns a sample response for the instructor's convenience.
     * Not implemented, as this would take a huge amount of reverse engineering the test-bench.
     */
    function get_correct_responses(&$question, &$state) 
    {
        return null;
    }

    /**
     * Prints the main content of the question, as displayed to the user.
     */
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) 
    {
        global $CFG;
        
        //configure the options for the file chooser according to the script
        $maxfiles = $question->options->allowmulti ? 25 : 1;
        
        //only allow the correct file type to be uploaded
        //(the rest will be handled by the backend PHP script, which
        //uses file extension to run the proper parsing)
        switch($question->options->hdltype)
        {
        	case 'any':
        		$types_allowed = array('*.vhd', '*.v', '*.sch');
        		break;
        	case 'sch':
        		$types_allowed = array('*.sch', '*.sym');
        		break;
        	case 'vhdl':
        		$types_allowed = array('*.vhd');
        		break;
        	case 'verilog':
        		$types_allowed = array('*.v');
        		break;
        	case 'true':
        		$types_allowed = array('*.vhd', '*.v');
        		break;
		case 'fsm':
			$types_allowed = array('*.fsm');
			break;
        	
        }
        
        //create the moodle upload handler, borrowing the file manager from Moodle's quickform library
        $upload_handler = new MoodleQuickForm_filemanager($question->name_prefix, get_string('userdesign', 'qtype_vhdl'), null, array('maxfiles' => $maxfiles, 'subdirs' => false, 'accepted_types' => $types_allowed));

        
        //if the user has provided a design, populate the upload handler
        if(isset($state->responses['']))
        	$upload_handler->setValue($state->responses['']);
        	
        //create the actual HTML form, which will be included in display.html
        $fileupload = @$upload_handler->toHtml();

        //below is unmodified moodle core code (ugh)
        $context = $this->get_context_by_category_id($question->category);

        $readonly = $options->readonly ? ' disabled="disabled"' : '';

        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;

        // Print question formulation
        $questiontext = format_text($question->questiontext,
                         $question->questiontextformat,
                         $formatoptions, $cmoptions->course);

                         
        include("$CFG->dirroot/question/type/vhdl/display.html");
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
    
    function move_files($questionid, $oldcontextid, $newcontextid) 
    {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_answers($questionid, $oldcontextid, $newcontextid);
    }
    
    protected function delete_files($questionid, $contextid) 
    {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_answers($questionid, $contextid);
    }

    function check_file_access($question, $state, $options, $contextid, $component, $filearea, $args) 
    {
        if ($component == 'question' && $filearea == 'answerfeedback') 
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
   
   

    


    /**
     * Runs all the code required to set up and save an essay question for testing purposes.
     * Alternate DB table prefix may be used to facilitate data deletion.
     */
    function generate_test($name, $courseid = null) 
    {
        global $DB;
        
        list($form, $question) = parent::generate_test($name, $courseid);
        $question->category = $form->category;

        $form->questiontext = "This question is really stupid";
        $form->penalty = 1;
        $form->defaultgrade = 1;
        $form->correctanswer = 0;
        $form->feedbacktrue = 'Can you justify such a hasty judgment?';
        $form->feedbackfalse = 'Wisdom has spoken!';

        if ($courseid) 
        {
            $course = $DB->get_record('course', array('id' => $courseid));
        }

        return $this->save_question($question, $form);

    }
}
//// END OF CLASS ////

//////////////////////////////////////////////////////////////////////////
//// INITIATION - Without this line the question type is not in use... ///
//////////////////////////////////////////////////////////////////////////
question_register_questiontype(new question_vhdl_qtype());
