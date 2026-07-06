<?php

require('../../config.php');

require_login();

$context = context_system::instance();

require_capability('moodle/site:config', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/dashboard/index.php'));
$PAGE->set_pagelayout('admin');

$PAGE->set_title('Dashboard Statistic');
$PAGE->set_heading('Dashboard Statistic');

echo $OUTPUT->header();

echo html_writer::tag('h2', 'Dashboard Statistic');

echo html_writer::div(
    'Selamat! Plugin Local Dashboard berhasil dibuat.',
    'alert alert-success'
);

echo $OUTPUT->footer();