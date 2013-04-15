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

//TODO: When Moodle eventually supports this, genericize this away.
require_once($CFG->dirroot.'/mod/quiz/attemptlib.php');
require_once($CFG->dirroot.'/mod/quiz/accessmanager.php');

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


/**
 * Event handler which is triggered then the judging of an Online Judge program 
 * is complete. Used to handle the grading of questions whose grading occurs after
 * a quiz is closed (say, past the deadline.)
 *
 * @param object $task Information describing the task which was just completed.
 */
function onlinejudge_task_judged($task) {
    global $DB;

    //If we've intercepted an event from another source, ignore it.
    if($task->component != 'qtype_onlinejudge') {
      return;
    }

    //Get the relevant question_attempt object..
    $quba = question_engine::load_questions_usage_by_activity($task->instanceid);
    $qa = $quba->get_question_attempt($task->slot);

    //Only run this if the quiz is finished (and thus we're the only one capable
    //of modifying the attempt. We don't want to modify this if the user can still
    //move through the quiz, as we'll wind up in an inconsistent state.)
    if (!$qa->get_state()->is_finished()) {
        return;
    }

    //Update the question attempt in the relevant quiz.
    $qa->process_action(array('-update' => 1), null, $task->userid);
   
    //And save the modified QUBA.
    question_engine::save_questions_usage_by_activity($quba);

    //If this QUBA is being used by a quiz attempt, update the quiz's grade.
    //TODO: Update the Moodle core so this doesn't require a special case?
    if($quba->get_owning_component() == 'mod_quiz') {
        $quiz_attempt = quiz_attempt::create_from_usage_id($quba->get_id()); 
        $quiz_attempt->process_finish(time(), null);
    }

}
