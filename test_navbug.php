<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/config.php');

$path = $argv[1] ?? '/local/kopere_dashboard/';
$qs = $argv[2] ?? '';
$_SERVER['QUERY_STRING'] = $qs;

$user = $DB->get_record('user', ['id' => 2], '*', MUST_EXIST);
\core\session\manager::set_user($user);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url($path, $qs === 'q=' ? ['q' => ''] : []));

$PAGE->navigation->initialise();

echo "URL: {$path}" . ($qs !== '' ? "?{$qs}" : '') . "\n";
echo "custommenuitems runtime: [" . str_replace("\n", " || ", trim((string)$CFG->custommenuitems)) . "]\n";

$renderer = $PAGE->get_renderer('core');
$primary = new \core\navigation\output\primary($PAGE);
$data = $primary->export_for_template($renderer);

$more = (array)($data['moremenu'] ?? []);
$nodes = (array)($more['nodearray'] ?? $more['children'] ?? []);
$labels = [];
foreach ($nodes as $n) {
    $n = (array)$n;
    $labels[] = $n['text'] ?? '?';
}
echo "navbar: [" . implode(', ', $labels) . "] (jumlah: " . count($labels) . ")\n";
