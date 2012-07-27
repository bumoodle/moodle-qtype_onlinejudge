<?php
/**
 * Settings for the HDL Simulation Question Type
 *
 * Authors:
 *    Kyle Temkin <ktemkin@binghamton.edu>
 * 
 * @package   qtype_vhdl
 * @copyright 2010-2012 Blindside Networks 
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v2 or later
 */

$settings->add( new admin_setting_configtext( 'runtestpath', get_string( 'runtestscript', 'qtype_vhdl' ), get_string( 'configruntestscript', 'qtype_vhdl' ), '/srv/autolab/runtest.sh' ) );
$settings->add( new admin_setting_configtext( 'runtimemax', get_string( 'runtimemax', 'qtype_vhdl' ), get_string( 'configruntimemax', 'qtype_vhdl' ), '60' ) );
?>
