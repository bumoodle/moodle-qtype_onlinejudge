<?php
/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_vhdl
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) 
    die('Direct access to this script is forbidden.');    ///  It must be included from a Moodle page

require_once($CFG->dirroot.'/question/type/edit_question_form.php');


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
class question_edit_vhdl_form extends question_edit_form 
{
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
    function definition_inner(&$mform) 
    {

       
        //user HDL input settings
        $mform->addelement('header', 'userinput', get_string('inputoptions', 'qtype_vhdl'));
        //accepted HDLs            
        $mform->addelement('select', 'hdltype', get_string('hdltype', 'qtype_vhdl'), array(
        	'any' => get_string('anytype', 'qtype_vhdl'),
	        'true' => get_string('anytruehdl', 'qtype_vhdl'),
    		'vhdl' => get_string('vhdlonly', 'qtype_vhdl'),
        	'verilog' => get_string('verilogonly', 'qtype_vhdl'),
		'fsm' => get_string('fsmonly', 'qtype_vhdl'),
        	'sch' => get_string('schonly', 'qtype_vhdl')));
        //allow multiple files 
        $mform->addelement('advcheckbox', 'allowmulti', '', ' '.get_string('allowmultifiles', 'qtype_vhdl'), array("group" => ""), array('0', '1'));
        
        //grading testbench information
        $mform->addelement('header', 'gradingbench', get_string('gradingbench', 'qtype_vhdl'));
        //file upload
        $mform->addelement('filemanager', 'testbench', get_string('gradingbenchfiles', 'qtype_vhdl'), null, self::$hdl_file_options);
        $mform->addRule('testbench', get_string('notestbench', 'qtype_vhdl'), 'required');
        //allow user feedback
        $mform->addelement('advcheckbox', 'autofeedback', '', ' '.get_string('autofeedback', 'qtype_vhdl'), array("group" => ""), array('0', '1'));
        
        
    }


    function validation($fromform, $files)
    {
    		
			$errors = parent::validation($fromform, $files);
			
			if(!isset($fromform['testbench']))
				$errors['testbench'] = get_string('notestbench', 'qtype_vhdl');
			
    		return $errors;
    	
    }
    
    function qtype() 
    {
        return 'vhdl';
    }
}
