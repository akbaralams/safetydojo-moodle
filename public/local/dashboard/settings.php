<?php

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_dashboard',
            get_string('pluginname', 'local_dashboard'),
            new moodle_url('/local/dashboard/index.php')
        )
    );

}