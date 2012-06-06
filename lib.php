<?php

/**
 * Defines the editing form for the shortanswer question type.
 *
 * @package   qtype_vhdl
 * @copyright 2011 Binghamton University
 * @author 	  Kyle Temkin <ktemkin@binghamton.edu>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Serve question type files
 *
 * @since 2.0
 * @package qtype
 * @subpackage qtype_vhdlf
 * @copyright The Open Unviersity
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function qtype_vhdl_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) 
{
    global $CFG;
    require_once($CFG->libdir . '/questionlib.php');
    question_pluginfile($course, $context, 'qtype_vhdl', $filearea, $args, $forcedownload);
}
