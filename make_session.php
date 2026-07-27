<?php
// Creates a temporary login session and prints its id, so the real HTML can be fetched with curl.
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');

$user = $DB->get_record('user', ['id' => 2], '*', MUST_EXIST);
\core\session\manager::login_user($user);

echo session_id() . "\n";
