<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Self enrols the current user into a course picked from the catalogue.
 *
 * The eligibility decision is fully delegated to the core enrol_self plugin, so enrolment
 * keys, date windows, capacity limits and cohort restrictions are all still enforced.
 *
 * @package   koperedashboard_allcourses
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use koperedashboard_allcourses\service\catalog_service;
use local_kopere_dashboard\audit\manager as audit_manager;

require_once(__DIR__ . "/../../../../config.php");

$courseid = required_param("courseid", PARAM_INT);

$context = context_system::instance();
require_login();
require_capability("koperedashboard/allcourses:view", $context);

// State changing request: must be a POST carrying a valid session key.
if (!(defined("AJAX_SCRIPT") && AJAX_SCRIPT) && $_SERVER["REQUEST_METHOD"] !== "POST") {
    throw new moodle_exception("invalidrequest", "error");
}
require_sesskey();

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url("/local/kopere_dashboard/plugins/allcourses/enrol.php"));

$returnurl = new moodle_url("/local/kopere_dashboard/plugins/allcourses/");

$course = get_course($courseid);
if ($course->id == SITEID || !core_course_category::can_view_course_info($course)) {
    redirect(
        $returnurl,
        get_string("error_unavailable", "koperedashboard_allcourses"),
        null,
        notification::NOTIFY_ERROR
    );
}

$plugin = catalog_service::get_self_plugin();
if ($plugin === null) {
    redirect(
        $returnurl,
        get_string("error_selfdisabled", "koperedashboard_allcourses"),
        null,
        notification::NOTIFY_ERROR
    );
}

["instance" => $instance, "reason" => $reason] = catalog_service::get_usable_self_instance($course->id);
if ($instance === null) {
    redirect(
        $returnurl,
        $reason !== "" ? $reason : get_string("error_notenrollable", "koperedashboard_allcourses"),
        null,
        notification::NOTIFY_ERROR
    );
}

// Never bypass an enrolment key: let the core enrolment screen collect and verify it.
if (!empty($instance->password)) {
    redirect(new moodle_url("/enrol/index.php", ["id" => $course->id]));
}

// enrol_self() raises its own success notification.
$plugin->enrol_self($instance);

audit_manager::log(
    "koperedashboard_allcourses",
    "self_enrolled",
    "course",
    $course->id,
    get_string("action_enrol", "koperedashboard_allcourses") . ": " . format_string($course->fullname),
    $context->id,
    [
        "courseid" => $course->id,
        "shortname" => $course->shortname,
        "enrolinstanceid" => $instance->id,
    ]
);

redirect(new moodle_url("/course/view.php", ["id" => $course->id]));
