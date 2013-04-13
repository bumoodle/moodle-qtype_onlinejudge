<?php

/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_vhdl
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/local/onlinejudge/judgelib.php');
require_once($CFG->dirroot.'/question/engine/bank.php');
require_once($CFG->dirroot.'/question/engine/questionusage.php');
require_once($CFG->dirroot.'/question/engine/questionattempt.php');

/**
 * Serve question type files
 *
 * @since 2.0
 * @package qtype
 * @subpackage qtype_vhdl
 * @copyright The Open Unviersity
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function qtype_onlinejudge_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) 
{
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_onlinejudge', $filearea, $args, $forcedownload);
}



function onlinejudge_task_judged($task) {
    global $DB;

    //If we've intercepted an event from another source, ignore it.
    if($task->component != 'qtype_onlinejudge') {
      return;
    }

    //Get the relevant question_attempt object..
    $quba = question_engine::load_questions_usage_by_activity($task->instanceid);
    $attempt = $quba->get_question_attempt($task->slot);

    //TODO: return unless the question is _finished_
    return;

    $data = array_merge(array('-update' => true, 'attemptid' => $attempt->get_id()), (array)$task);

    //Update the question attempt in the relevant quiz.
    $attempt->process_action($data);
    
    //And save the modified QUBA.
    question_engine::save_questions_usage_by_activity($quba);

}
