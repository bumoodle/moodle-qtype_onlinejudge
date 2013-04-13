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
        	'accepted_types' => array('*.vhd', '*.v', '*.sch', '*.fsm', '*.zip'));
	
	
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
}
