<?php

/***
 * Automatically-generated Database migration script.
 */ 
function xmldb_qtype_onlinejudge_upgrade($oldversion = 0) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2013041500) {

        // Define field memlimit to be added to question_onlinejudge
        $table = new xmldb_table('question_onlinejudge');
        $field = new xmldb_field('memlimit', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'autofeedback');

        // Conditionally launch add field memlimit
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field cpulimit to be added to question_onlinejudge
        $table = new xmldb_table('question_onlinejudge');
        $field = new xmldb_field('cpulimit', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'memlimit');

        // Conditionally launch add field cpulimit
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // vhdl savepoint reached
        upgrade_plugin_savepoint(true, 2013041500, 'qtype', 'onlinejudge');
    }

}
