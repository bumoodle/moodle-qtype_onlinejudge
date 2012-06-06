<?php

require_once 'Debug.class.php';

//emulate moodle
global $CFG;
$CFG = new stdClass;
$CFG->debug = true;
$CFG->dirroot = '/home/ktemkin/www/bu/moodle/moodle/';


//require the SSH classes
require_once 'SSHConfig.class.php';
require_once 'RemoteConnection.class.php';

$ssh = new RemoteConnection();

echo '<pre>';
$ssh->upload_file('/tmp/testfile', '/tmp/testfile');
print_r($ssh->execute('cat /tmp/testfile'));
echo '</pre>';

?>