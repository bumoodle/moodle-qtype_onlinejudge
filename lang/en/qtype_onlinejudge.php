<?php
/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_onlinejudge
 * @copyright 2011 Binghamton University
 * @author    Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginnameadding'] = 'Adding a Coding question';
$string['pluginnameediting'] = 'Editing a Coding Simulation question';

$string['pluginname'] = $string['qtype_onlinejudge'] = 'Coding';
$string['pluginname_help'] = 'In response to a question (that may include a image) the respondent chooses from multiple answers. There are two types of multiple choice questions - one answer and multiple answer.';
$string['pluginname_help'] = 'In response to a question (that may include a image) the respondent uploads a HDL circuit description, which is tested for accuracy using Xilinx simulation tools.';
$string['pluginname_link'] = 'question/type/onlinejudge';
$string['pluginname_summary'] = 'An advanced HDL question, which automatically grades student designs.';
$string['pluginnamesummary'] = 'An advanced coding question, which automatically grades student designs.';

$string['estimated_wait'] = 'Estimated wait:';
$string['estimated_wait_message'] = 'There are {$a->length} program(s) waiting to be graded ahead of this one; your program should be graded in less than <b>{$a->estimated_time}</b> seconds.';

$string['result'] = 'Result';
$string['status'] = 'Status';
$string['status_pending_help'] = 'Questions which require you to submit code take a little bit longer for the computer to grade, especially at peak times, such as just before the due dates for quizzes. <br/><br/>. You can work on other problems while you wait for this question to be graded.';
$string['status_grading_help'] = 'Questions which require you to submit code take slightly longer to grade. Your code is currently being graded in the background; you can click the Refresh button to continue.';

$string['in_line'] = 'Waiting in line to be graded...';

$string['grading'] = 'Your submission is being graded.';
$string['grading_in_progress'] = 'The system is currently grading your submission in the background; grading should be completed momentarily. <br /><i>You can click Refresh to view an updated status.</i>';

$string['details'] = 'Details';
$string['compiler_output'] = 'Compiler output:';
$string['comments'] = 'Comments';
$string['grading_details'] = 'Grading details:';

$string['submitted_files'] = 'Submitted';

$string['filesspecified'] = '{$a} files';

$string['inputoptions'] = 'User Input Options';
$string['allowmultifiles'] = 'Allow the user to submit multiple files, for a multi-level design.';
$string['allowzipfiles'] = 'Allow the user to submit one or more ZIP archives, which will be automatically unzipped.';

$string['language'] = 'Language';
$string['memlimit'] = 'Maximum Memory Usage';
$string['cpulimit'] = 'Maximum CPU Time';



$string['gradingbench'] = 'Grading Testbench';
$string['gradingbenchfiles'] = 'Testbench File(s)';
$string['autofeedback'] = 'Display point breakdown to the student as feedback. (Generated automatically from testbench comments.)';
$string['notestbench'] = 'You must provide at least one valid testbench file!';

$string['uploaddesign'] = 'Submit your design:';
$string['userdesign'] = 'Your Design';
$string['erroroccurred'] = '<br/><b><font color="#7A0707">There was a problem with your submission, and it was unable to be automatically graded.</font></b><br /> The system wasn\'t able to figure out a more detailed description. Check your design, and try again in a moment.';
$string['toolerror'] = '<br/><b><font color="#7A0707">The lab tools weren\'t able to interpret your submission. This could be a problem with your submission or with the lab tools.</font></b><br />They provided the following error message:<br /><br /><div style="font-family: monospace;">{$a}</div></font>';
$string['notcompatible'] = '<br/><b><font color="#7A0707">The system couldn\'t find your design within the files submitted. </font></b><br />This could be a naming issue; or one or more files could be missing or malformed.';

$string['catchall'] = 'An unknown error occurred.';

$string['pleasesubmit'] = 'Please attach the file to be submitted.';

$string['remote_issue'] = 'An error occurred contacting the HDL Simulation server. You will not be penalized for this submission.';

$string['runtestscript'] = 'Path to RunTest';
$string['configruntestscript'] = 'The absolute path to the RunTest executable used to simualte HDL files.';

$string['runtimemax'] = 'Maximum Runtime';
$string['configruntimemax'] = 'Maximum runtime for the simulation in seconds.';
