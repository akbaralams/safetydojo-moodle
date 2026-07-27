<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mariadb';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'safety_db';
$CFG->dbuser    = 'root';
$CFG->dbpass    = '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => 3306,
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = 'http://safety.test';
$CFG->dataroot  = 'C:\laragon\www\Moodle\moodledata';
$CFG->admin     = 'admin';
$CFG->debug = E_ALL;
$CFG->debugdisplay = 1;
// Disable slasharguments for better Nginx compatibility
$CFG->slasharguments = false;

$CFG->directorypermissions = 0777;
@ini_set('display_errors', '1');
$CFG->defaulthomepage = '/local/kopere_dashboard/';

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
