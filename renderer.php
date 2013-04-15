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
require_once($CFG->dirroot . '/question/type/onlinejudge/lib.php');

/**
 * Generates the output for true-false questions.
 *
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_onlinejudge_renderer extends qtype_renderer
{
    
    /**
     * Generates the question's text status information, entry area, and controls for the given questino.
     *
     * @param question_attempt $qa The question to print.
     * @param question_display_options $options The options which specify how the question should be formatted.
     */ 
    public function formulation_and_controls(question_attempt $qa, question_display_options $options) 
    {
        $result = '';

        //Get the core question definition and the active question behavior object.
        $question = $qa->get_question();
        $behaviour = $qa->get_behaviour();

        //get the most recent step with a provided answer
        $step = $qa->get_last_step_with_qt_var('answer');

        //Output the question's text.
        $result .= html_writer::tag('div', $question->format_questiontext($qa), array('class' => 'qtext'));
        global $PAGE;

        //If the question is currently being graded, render the input form read-only.
        $read_only = ($qa->get_state() == question_state::$complete);

        //Output the answer form.
        if(empty($options->readonly) && !$read_only) {
            $result .= html_writer::start_tag('div', array('class' => 'ablock'));
            $result .= html_writer::tag('div', $this->render_file_picker($qa, $options), array('class' => 'answer'));
            $result .= html_writer::end_tag('div');
        }
        
        //get the last submitted step 
        $feedback_step = $behaviour->get_feedback_step();
        $feedback_answer = $feedback_step->get_qt_data();

        //Get the ID 
        $active_task_id = $behaviour->get_active_task_id();
        
        //If the quesiton has an active task, display its status.
        if($active_task_id !== null) {
            $result .= $this->queued_grade_status($active_task_id);
        } 
        //If the feedback step has been graded, display its status.
        else if($feedback_step->has_qt_var('_status')) {
            $result .= $this->grading_results($qa, $feedback_answer, $options);
        }
        else if(empty($options->readonly) && !$read_only) {
            $result .= $this->files_read_only($qa, $options);
        }

        return $result;
    }

    /**
     * Prints information about a graded coding question,
     * including the testbench comments / marks.
     */ 
    protected function grading_results($qa, $response, $options) {
    
        //Start a buffer to store the generated status blurb,
        //and create a table to show status information.
        $table = $this->generate_results_table();

        //Notify the user of their grading status.
        $table->data[] = $this->generate_status_row($qa, $response, $options->correctness);

        //If a compilation error occurred, report the compiler output.
        if($response['_status'] == online_judge::STATUS_COMPILATION_ERROR) {
            $table->data[] = $this->generate_compilation_error_row($response); 
        }

        //If the query was accepted, report information regarding it.
        if($response['_status'] == online_judge::STATUS_ACCEPTED) {
            $table->data[] = $this->generate_comment_row($qa, $response);
        }

        //Add the list of submitted files.
        $table->data[] = array(get_string('submitted_files', 'qtype_onlinejudge').':', $this->files_read_only($qa, $options));

        return html_writer::table($table); 

    }

    /**
     * Generates a html_table row which describes each of the testbench comments / marks.
     * Intended for use by generate_results_table.
     */  
    protected function generate_comment_row($qa, $response) {

        //Determine the maximum mark possible for the given question,
        //and extract each of the remarks that compose the grade.
        $max_mark = $qa->get_max_mark();
        $comments = $qa->get_question()->get_testbench_remarks($response);

        //If there's no comment 
        if(!count($comments)) {
            return array();
        }

        //Display each of the relevant comments.
        $item_name = get_string('comments', 'qtype_onlinejudge').':';
        $item = $this->summarize_comments($comments, $max_mark);
        return array($item_name, $item);
    }

    /**
     * Summarizes the comments for a given test-case in an easy-to-read table.
     */
    function summarize_comments($comments, $max_grade) {
    
      //And generate a new table containing each of the comments and demerits
      //generated by the unit tests.
      $table = new html_table();
      $table->attributes['class'] = 'codefeedback';
      $table->align = array('center', 'left');
      $table->width = '100%';
      $table->size = array('4em', '');

      //Display each of the grading comments for the given row.
      foreach($comments as $comment) {

        //If points have been added/subtracted for this, render a number of poitns...
        if($comment[0] != 0)  {
          $comment[0] = ($comment[0] / 100.0) * $max_grade; 
        } 
        //Otherwise, leave this field blank.
        else {
          $comment[0] = '';
        }

        //Add the testbench comment to the table.
        $table->data[] = $comment;

      }

      //Convert the comments into a HTML table.
      return html_writer::table($table);
    
    }

    /**
     * Generates an html_table row which describes a task's compilation error.
     * For use by generate_results_table.
     */ 
    protected function generate_compilation_error_row($response) {
        $item_name = get_string('compiler_output', 'qtype_onlinejudge');
        $item = html_writer::tag('pre', $response['_compileroutput']);
        return array($item_name, $item);
    }

    /**
     * Generates an html_table row which describes the status of the given response.
     * For use by generate_results_table.
     */ 
    protected function generate_status_row($qa, $response, $show_correctness) {

        //If an error occurred, display the status in red.
        $result_class = ($response['_status'] == online_judge::STATUS_ACCEPTED) ? 'bold' : 'notifyproblem';

        //TODO: String.
        //Report the raw status of the grading.
        $item_name = get_string('result', 'qtype_onlinejudge').$this->help_icon('status', 'assignment_onlinejudge').':';
        $item  = html_writer::start_tag('span', array('class' => $result_class));
        $item .= get_string('status'.$response['_status'], 'local_onlinejudge');
        $item .= html_writer::end_taG('span');

        //If the response was accepted, indicate whether it was correct or incorrect.
        if($response['_status'] == online_judge::STATUS_ACCEPTED) {
           $item .= ' ('.strtolower($qa->get_state_string($show_correctness)).')';
        }

        //And return it as a table row.
        return array($item_name, $item);
    
    }

    /**
     * Displays the wait time for an assignment which is queued for grading.
     */
    protected function queued_grade_status($task_id) {

        //Start a buffer to store the generated status blurb,
        //and create a table to show status information.
        $result = $this->spacer();
        $table = $this->generate_results_table();

        //Get the task's information.
        $task = online_judge::get_task_record($task_id);


        //If the task has not yet been judged, display information about the wait.
        if($task->status == online_judge::STATUS_PENDING) {

            //Notify the user that their grade is pending...
            $item_name = get_string('status', 'qtype_onlinejudge').$this->help_icon('status_pending', 'qtype_onlinejudge').':';
            $item = get_string('in_line', 'qtype_onlinejudge');
            $table->data[] = array($item_name, $item);

            //Generate information relating to the wait.
            $wait_info = new stdClass;
            $wait_info->length = online_judge::position_in_queue($task_id);

            //Determine the maximum amount of time that the student should have to wait by assuming each task takes the full CPU time limit.
            //This won't be exactly accurate (as other things are using the CPU), but it should be pretty close, as many assignments should be
            //well under the limit.
            $wait_info->estimated_time = $wait_info->length * $task->cpulimit;
            $table->data[] = array(get_string('estimated_wait', 'qtype_onlinejudge'), get_string('estimated_wait_message', 'qtype_onlinejudge', $wait_info)); 

        } else {

            //Notify the user that their submission is being graded.
            $item_name = get_string('status', 'qtype_onlinejudge').$this->help_icon('status_grading', 'qtype_onlinejudge').':';
            $item = get_string('grading', 'qtype_onlinejudge');
            $table->data[] = array($item_name, $item);

            //And provide additional instructions.
            $table->data[] = array(get_string('details', 'qtype_onlinejudge'), get_string('grading_in_progress', 'qtype_onlinejudge'));
        }

        return $result.html_writer::table($table);

    }

    /**
     * Generates a new HTML table formatted to display compilation results.
     */
    private function generate_results_table($id = 'coderesults') {
        $table = new html_table();
        $table->id = $id;
        $table->attributes['class'] = 'generaltable';
        $table->align = array ('right', 'left');
        $table->size = array('20%', '');
        $table->width = '100%';
        return $table;
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

            $output[] = html_writer::tag('div', 
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
