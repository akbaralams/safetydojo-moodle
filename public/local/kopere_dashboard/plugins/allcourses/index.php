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
 * Catalogue of courses the current user can still enrol into.
 *
 * @package   koperedashboard_allcourses
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_course\external\course_summary_exporter;
use koperedashboard_allcourses\service\catalog_service;
use local_kopere_dashboard\output\layout;
use local_kopere_dashboard\util\userdate;

require_once(__DIR__ . "/../../../../config.php");

$context = context_system::instance();
require_login();
require_capability("koperedashboard/allcourses:view", $context);

$q = optional_param("q", "", PARAM_RAW_TRIMMED);

$PAGE->set_url(new moodle_url("/local/kopere_dashboard/plugins/allcourses/", ["q" => $q]));
$PAGE->add_body_class("local-kopere_dashboard");
$PAGE->set_context($context);
$PAGE->set_title(get_string("page_title", "koperedashboard_allcourses"));

$courses = catalog_service::get_catalogue_courses($USER->id, $q, 200);

$enrolledcourseids = [];
foreach ($courses as $course) {
    if (!empty($course->enrolled)) {
        $enrolledcourseids[] = (int) $course->id;
    }
}
$certificateurls = catalog_service::get_certificate_urls($USER->id, $enrolledcourseids);

$rows = [];
foreach ($courses as $course) {
    $enrolled = !empty($course->enrolled);

    // Enrolment eligibility only matters for courses the user has not joined yet.
    $canenrol = false;
    $needspassword = false;
    $reason = "";
    if (!$enrolled) {
        ["instance" => $instance, "reason" => $reason] = catalog_service::get_usable_self_instance($course->id);

        // A course with no usable instance stays listed, but with the core reason instead of a button,
        // so the learner understands why it cannot be joined yet.
        $canenrol = ($instance !== null);
        $needspassword = $canenrol && !empty($instance->password);
    }

    $summary = "";
    if (!empty($course->summary)) {
        $summary = shorten_text(
            content_to_text($course->summary, $course->summaryformat),
            180
        );
    }

    // Course image, falling back to the same generated pattern core uses on course cards.
    $imageurl = course_summary_exporter::get_course_image($course);
    if (!$imageurl) {
        $imageurl = $OUTPUT->get_generated_image_for_id($course->id);
    }

    $rows[] = [
        "id" => $course->id,
        "imageurl" => $imageurl,
        "fullname" => format_string($course->fullname),
        "shortname" => format_string($course->shortname),
        "category" => format_string($course->categoryname),
        "summary" => $summary !== "" ? $summary : get_string("summary_none", "koperedashboard_allcourses"),
        "startdate" => !empty($course->startdate) ? userdate::format($course->startdate) : "-",
        "enrolled" => $enrolled,
        "canenrol" => $canenrol,
        "needspassword" => $needspassword,
        "reason" => $canenrol ? "" : $reason,
        // Courses protected by an enrolment key are handed over to the core enrolment screen,
        // which knows how to prompt for and validate the key.
        "enrolkeyurl" => (new moodle_url("/enrol/index.php", ["id" => $course->id]))->out(false),
        "courseurl" => (new moodle_url("/course/view.php", ["id" => $course->id]))->out(false),
        "hascertificate" => isset($certificateurls[$course->id]),
        "certificateurl" => $certificateurls[$course->id] ?? "",
    ];
}

$templatecontext = [
    "q" => $q,
    "intro" => get_string("intro", "koperedashboard_allcourses"),
    "placeholder" => get_string("search_placeholder", "koperedashboard_allcourses"),
    "courses" => $rows,
    "hascourses" => !empty($rows),
    "emptymessage" => ($q !== "")
        ? get_string("no_results", "koperedashboard_allcourses")
        : get_string("no_courses", "koperedashboard_allcourses"),
    "formurl" => (new moodle_url("/local/kopere_dashboard/plugins/allcourses/"))->out(false),
    "enrolurl" => (new moodle_url("/local/kopere_dashboard/plugins/allcourses/enrol.php"))->out(false),
    "sesskey" => sesskey(),
];

$content = $OUTPUT->render_from_template("koperedashboard_allcourses/index", $templatecontext);
layout::page_render($context, $content, true);
