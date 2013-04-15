<?php
/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_vhdl
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/edit_question_form.php');
require_once($CFG->dirroot.'/local/onlinejudge/judgelib.php');


/**
 * Defines the editing form for the thruefalse question type.
 *
 * @copyright &copy; 2006 The Open University
 * @author T.J.Hunt@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package questionbank
 * @subpackage questiontypes
 */

/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_vhdl
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * vhdl editing form definition.
 */
class qtype_onlinejudge_edit_form extends question_edit_form 
{
    /**
     * A set of default options for the testbench upload form.
     */
	private static $hdl_file_options = 
			array(
        	'maxfiles' => 25,
        	'subdirs' => 0, 
          'maxbytes' => 5242880, //5 MiB (may be limited further by Moodle's built-in maximum upload size)
        );
	
	
    /**
     * Add question-type specific form fields.
     *
     * @param object $mform the form being built.
     */
    function definition_inner($mform) 
    {

        //user HDL input settings
        $mform->addelement('header', 'userinput', get_string('inputoptions', 'qtype_onlinejudge'));
        //accepted HDLs            
        $mform->addelement('select', 'judge', get_string('language', 'qtype_onlinejudge'), onlinejudge_get_languages());

        //memory limit
        $mform->addelement('select', 'memlimit', get_string('memlimit', 'qtype_onlinejudge'), self::get_max_memory_usages());

        //cpu time limit
        $mform->addelement('select', 'cpulimit', get_string('cpulimit', 'qtype_onlinejudge'), self::get_max_cpu_times());

        //allow multiple files 
        $mform->addelement('advcheckbox', 'allowmulti', '', ' '.get_string('allowmultifiles', 'qtype_onlinejudge'), array("group" => ""), array('0', '1'));

        
        //grading testbench information
        $mform->addelement('header', 'gradingbench', get_string('gradingbench', 'qtype_onlinejudge'));
        
        //file upload
        $mform->addelement('filemanager', 'testbench', get_string('gradingbenchfiles', 'qtype_onlinejudge'), null, self::$hdl_file_options);

        $mform->addRule('testbench', get_string('notestbench', 'qtype_onlinejudge'), 'required');
        //allow user feedback
        $mform->addelement('advcheckbox', 'autofeedback', '', ' '.get_string('autofeedback', 'qtype_onlinejudge'), array("group" => ""), array('0', '1'));
        $mform->setDefault('autofeedback', '1');
        //
        $this->add_interactive_settings(); 
        
    }

    /**
     * Preprocess the form data, creating any necessary file areas. 
     *
     * @param question_vhdl_question    The core question definition to be modified.
     * @return question_vhdl_question   The question, which was modified by preprocessing.
     */
    protected function data_preprocessing($question) 
    {
        //perform the base modifications to the question type
        $question = parent::data_preprocessing($question);
        
        //load the hints to be edited
        $question = $this->data_preprocessing_hints($question, true, true);

        //if we have an existing file itemID, use it to import the existing files
        $itemid = empty($question->options->testbench) ? null : $question->options->testbench;

        //if a draft area exists for the current form, get its ID
        $draftitemid = file_get_submitted_draft_itemid('testbench');

        //Prepare a draft area for the testbench file upload.
        //If we didn't get a valid draft area in the last step, then one will be automatically created.
        //If we _did_ get a valid file handle (itemid), permanent files already exist. Copy them to the draft area for modification.
        file_prepare_draft_area($draftitemid, $this->context->id, 'qtype_onlinejudge', 'testbench', $itemid, self::$hdl_file_options);

        //Replace the question's testbench with a reference to the draft file area.
        //If a testbench previously existed, copy it there.
        $question->testbench = $draftitemid;

        

        //return the newly updated question
        return $question;
    }

    /**
     * Performs basic form validation, ensuring that the input is valid.
     * 
     * @param $fromform     The partially santized post-data submitted by the user.
     * @param $file         An array of uploaded user-files, before they are saved to the draft area.
     *
     * @returns array       An array of errors which occurred during validation. If no errors occur, returns an empty array.
     */
    function validation($fromform, $files)
    {
        //perform the main validation
        $errors = parent::validation($fromform, $files);
       
        //if the user did not include a testbench, then throw an error
        if(!isset($fromform['testbench']))
            $errors['testbench'] = get_string('notestbench', 'qtype_onlinejudge');
		
        //return the completed list of errors
        return $errors;
    }
   
    /**
     * Returns the question type suffix, for identification.
     */
    function qtype() 
    {
        return 'onlinejudge';
    }


  /**
   * The following functions are from the Online Judge assignemnt type
   * by Sun Zhigang. They should be replaced with Moodle configuration options.
   * 
   * @copyright 2011 Sun Zhigang (http://sunner.cn)
   * @author    Sun Zhigang
   * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
   */


    /**
     * This function returns an
     * array of possible memory sizes in an array, translated to the
     * local language.
     *
     * @return array
     */
    static function get_max_memory_usages() {

        // Get max size
        $maxsize = 1024 * 1024 * get_config('local_onlinejudge', 'maxmemlimit');
        $memusage[$maxsize] = display_size($maxsize);

        $sizelist = array(1048576, 2097152, 4194304, 8388608, 16777216, 33554432,
                          67108864, 134217728, 268435456, 536870912);

        foreach ($sizelist as $sizebytes) {
           if ($sizebytes < $maxsize) {
               $memusage[$sizebytes] = display_size($sizebytes);
           }
        }

        ksort($memusage, SORT_NUMERIC);

        return $memusage;
    }

    /**
     * This function returns an
     * array of possible CPU time (in seconds) in an array
     *
     * @return array
     */
    static function get_max_cpu_times() {

        // Get max size
        $maxtime = get_config('local_onlinejudge', 'maxcpulimit');
        $cputime[$maxtime] = get_string('numseconds', 'moodle', $maxtime);
        $timelist = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 20, 25, 30, 40, 50, 60);
        foreach ($timelist as $timesecs) {
           if ($timesecs < $maxtime) {
               $cputime[$timesecs] = get_string('numseconds', 'moodle', $timesecs);
           }
        }

        ksort($cputime, SORT_NUMERIC);

        return $cputime;
    }
}
