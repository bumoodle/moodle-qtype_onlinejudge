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
 * True-false question renderer class.
 *
 * @package    qtype
 * @subpackage truefalse
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/lib/form/filemanager.php');

/**
 * Generates the output for true-false questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_vhdl_renderer extends qtype_renderer
{


    public function formulation_and_controls(question_attempt $qa, question_display_options $options) 
    {

        $question = $qa->get_question();

        //get the most recent step with a provided answer
        $step = $qa->get_last_step_with_qt_var('answer');

        //and output the answer
        $result = '';
        $result .= html_writer::tag('div', $question->format_questiontext($qa), array('class' => 'qtext'));

        $result .= html_writer::start_tag('div', array('class' => 'ablock'));

        if(empty($options->readonly))
            $result .= html_writer::tag('div', $this->render_file_picker($qa, $options), array('class' => 'answer'));
        else
            $result .= html_writer::tag('div', $this->files_read_only($qa, $options), array('class' => 'answer'));
        
        
        
        $result .= html_writer::end_tag('div');

        
        //get the last submitted step 
        $last_submitted_step = $qa->get_last_step_with_qt_var('-submit');
        $last_submitted_answer = $last_submitted_step->get_qt_data();

        //if a raw answer was provided for the last submission, provide feedback
        if(array_key_exists('answerraw', $last_submitted_answer))
        {
            //retrieve any feedback from the question object
            $feedback = $question->get_autograde_feedback($last_submitted_answer);

            //if feedback was provided, display it
            if(!empty($feedback))
                $result .= html_writer::tag('div', $feedback, array('class' => 'feedback'));
        }
         
        return $result;
    }

    /**
     * Displays any attached files when the question is in read-only mode.
     * @param question_attempt $qa the question attempt to display.
     * @param question_display_options $options controls what should and should
     *      not be displayed. Used to get the context.
     */
    public function files_read_only(question_attempt $qa, question_display_options $options) 
    {
        $files = $qa->get_last_qt_files('answer', $options->context->id);
        $output = array();

        //list each file as a download-only icon 
        foreach ($files as $file) 
        {
            $mimetype = $file->get_mimetype();

            $output[] = html_writer::tag('p', 
                html_writer::link($qa->get_response_file_url($file), 
                $this->output->pix_icon(file_mimetype_icon($mimetype), $mimetype,
                    'moodle', array('class' => 'icon')) . ' ' . s($file->get_filename())));
        }
        return implode($output);
    }



    /**
     * Render a file picker, which is used for specifying the HDL files when not in read-only mode.
     */
    function render_file_picker(question_attempt $qa, question_display_options $options)
    {
        //specify the options for the file picker
        $pickeroptions = new stdClass();
        $pickeroptions->mainfile = null;
        $pickeroptions->maxfiles = $qa->get_question()->allowmulti ? -1 : 1;
        $pickeroptions->context = $options->context;
        $pickeroptions->itemid = $qa->prepare_response_files_draft_itemid('answer', $options->context->id);
        //$pickeroptions->itemid = $qa->prepare_response_files_draft_itemid('answer', $options->context->id);
        
        //get the proper field name for the picker
        $name = $qa->get_qt_field_name('answer');
        $name_raw = $qa->get_qt_field_name('answerraw');
        $context_raw = $qa->get_qt_field_name('attemptid');
       
        // Create a file manager and file manager renderer:
        $file_manager = new form_filemanager($pickeroptions);
        $file_renderer = $this->page->get_renderer('core', 'files');

        // And render the file picker: 
        $retval =  $file_renderer->render($file_manager);
        $retval .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $name, 'value' => $pickeroptions->itemid));
        $retval .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $name_raw, 'value' => $pickeroptions->itemid));
        $retval .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $context_raw, 'value' => $qa->get_database_id()));

        //return the rendered value
        return $retval;
    }


}
