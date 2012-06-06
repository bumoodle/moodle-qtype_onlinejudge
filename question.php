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


defined('MOODLE_INTERNAL') || die();


/**
 * Represents a true-false question.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_vhdl_question extends question_graded_automatically 
{

    /**
     * Returns an array containing the data expected in a valid submission of a VHDL question.
     */
    public function get_expected_data()
    {
        //expect the submission of one or more files
        //return array('answer' => question_attempt::PARAM_FILES);
        return array('answer' => question_attempt::PARAM_FILES, 'answerraw' => PARAM_RAW, 'attemptid' => PARAM_INT);
    }


    /**
     * Indicates that no sample "correct response" is available.
     */
    public function get_correct_response()
    {
        return null;
    }

    public function is_complete_response(array $response)
    {
        //if the response does not specify any files, it must be incomplete
        if(!$this->files_specified($response))
            return false;

        //otherwise, try processing it
        try
        {
            //process the user's response
            $this->process_response($response);
            return true;
        }
        catch(SimulationException $e)
        {
            return false; 
        }
    }

    /**
     * Returns true iff the given response is gradeable.
     */
    public function is_gradable_response(array $response)
    {
        //any complete response is gradeable
        return $this->is_complete_response($response);
    }

    /**
     * Determines the grades for a known-valid response.
     */
    function grade_response(array $response)
    {
        try
        {

        //process the user's response
        $grades = $this->process_response($response);

        //and return the earned grade
        return array($grades['fraction'], question_state::graded_state_for_fraction($grades['fraction']));
        }
        catch(SimulationException $e)
        {
            return false;
        }
    }

    public function get_autograde_feedback(array $response)
    {
        //if this response has no files, then never return feedback
        if(!$this->files_specified($response))
            return get_string('pleasesubmit', 'qtype_vhdl');

        try
        {
            //process the response, and extract feedback
            $processed = $this->process_response($response);
            return $processed['feedback'];
        }
        catch(SimulationException $e)
        {
            return $e->getMessage();
        }
    }

    protected function files_specified(array $response)
    {
        global $USER;

        $file_storage = get_file_storage();

        //and get a reference to the current user's draft files (which house the newly uploaded file)
        $user_context = context_user::instance($USER->id);

        //get a list of all area files
        $user_design = $file_storage->get_area_files($user_context->id, 'user', 'draft', $response['answerraw']);

        //and return the amount of files in the user design
        return (count($user_design));
    }

    /**
     * Processes the user's response, extracting comments and grades.
     * Throws a SimulationException if the response cannot be simulated.
     */
    protected function process_response(array $response)
    {
        global $USER;
    
        if(!array_key_exists('answerraw', $response))
            return;

        //first, get a reference to Moodle's file storage controller
        $file_storage = get_file_storage();

        //and get a reference to the current user's draft files (which house the newly uploaded file)
        $user_context = context_user::instance($USER->id);

        //get a reference to the stored testbench
        $testbench = $file_storage->get_area_files($this->contextid, 'qtype_vhdl', 'testbench', $this->testbench);

        //attempt to get the uploaded file, if possible
        $user_design = $file_storage->get_area_files($this->contextid, 'question', 'response_answer', $response['attemptid'], 'sortorder', false);

        //if this fails, retrieve the local copy from the draft area
        if(!count($user_design))
            $user_design = $file_storage->get_area_files($user_context->id, 'user', 'draft', $response['answerraw']);

        //create a new HDL Simulation object
        $sim = new HDLSimulation($testbench, $user_design, self::elaborate_types($this->hdltype));

        //run the simulation, caching as we go
        $sim->run_simulation();

        //and then clean up, afterwards
        $sim->cleanup();

        //return the marks and the afforded fraction
        return array('marks' => $sim->get_marks(), 'fraction' => $sim->get_grade(), 'hash' => $sim->get_hash(), 'feedback' => $sim->get_marks_str());
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
        try
        {
            //Process both responses.
            //This is a CPU-heavy operation the first time it is run, but is relatively light afterwards due to local caching.
            $a_resp = $this->process_response($a);
            $b_resp = $this->process_response($b);

            //return true iff the hashes match
            return ($a_resp['hash'] == $b_resp['hash']);
        }
        catch(SimulationException $e)
        {
            return false;
        }
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
        else 
        {
            return parent::check_file_access($question, $state, $options, $contextid, $component, $filearea);
        }
    }


}
