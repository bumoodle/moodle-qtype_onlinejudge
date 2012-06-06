<?php
/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_vhdl
 * @copyright 2011 Binghamton University
 * @author    Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['addingvhdl'] = 'Adding an HDL Simulation question';
$string['editingvhdl'] = 'Editing an HDL Simulation question';
$string['vhdl'] = 'HDL Simulation';
$string['vhdl_help'] = 'In response to a question (that may include a image) the respondent uploads a HDL circuit description, which is tested for accuracy using Xilinx simulation tools.';
$string['vhdl_link'] = 'question/type/vhdl';
$string['vhdlsummary'] = 'An advanced HDL question, which automatically grades student designs.';

$string['inputoptions'] = 'User HDL Input';
$string['allowmultifiles'] = 'Allow the user to submit multiple files, for a multi-level design.';
$string['allowzipfiles'] = 'Allow the user to submit one or more ZIP archives, which will be automatically unzipped.';

$string['hdltype'] = 'Accepted HDLs';
$string['anytype'] = 'Accept any valid HDL.';
$string['anytruehdl'] = 'Accept VHDL or Verilog, but not Schematic files.';
$string['vhdlonly'] = 'Accept only VHDL.';
$string['verilogonly'] = 'Accept only Verilog.';
$string['schonly'] = 'Accept only Xilinx Schematic files.';
$string['fsmonly'] = 'Accept only QFSM Finite State Machine descriptions.';

$string['gradingbench'] = 'Grading Testbench';
$string['gradingbenchfiles'] = 'Testbench File(s)';
$string['autofeedback'] = 'Display point breakdown to the student as feedback. (Generated automatically from testbench comments.)';
$string['notestbench'] = 'You must provide at least one valid testbench file!';

$string['uploaddesign'] = 'Submit your design:';
$string['userdesign'] = 'Your Design';
$string['erroroccurred'] = '<br/><b><font color="#7A0707">There was a problem with your submission, and it was unable to be automatically graded.</font></b><br /> Check your design, and try again in a moment. (This error also occurs if you submit the same design twice.).';

$string['catchall'] = 'An unknown error occurred.';

$string['remote_issue'] = 'An error occurred contacting the HDL Simulation server. You will not be penalized for this submission.';
