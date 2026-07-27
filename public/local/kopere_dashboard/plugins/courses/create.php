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
 * create.php
 *
 * Reuses Moodle's real course_edit_form (same one powering /course/edit.php) so course creation
 * has full functionality, rendered inside the Kopere Dashboard shell/styling.
 *
 * @package   koperedashboard_courses
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_kopere_dashboard\output\layout;

require_once(__DIR__ . "/../../../../config.php");
require_once("{$CFG->dirroot}/course/lib.php");
require_once("{$CFG->dirroot}/course/edit_form.php");

$context = context_system::instance();
require_login();
require_capability("koperedashboard/courses:create", $context);

$categoryid = optional_param("category", 0, PARAM_INT);

if ($categoryid) {
    $category = $DB->get_record("course_categories", ["id" => $categoryid], "*", MUST_EXIST);
} else {
    $category = core_course_category::get_default();
}
$catcontext = context_coursecat::instance($category->id);
require_capability("moodle/course:create", $catcontext);

$PAGE->set_url(new moodle_url("/local/kopere_dashboard/plugins/courses/create.php", ["category" => $categoryid]));
$PAGE->add_body_class("local-kopere_dashboard");
$PAGE->set_context($context);
$PAGE->set_title(get_string("new_title", "koperedashboard_courses"));
$PAGE->requires->css("/local/kopere_dashboard/plugins/courses/styles.css");

$editoroptions = [
    "maxfiles" => EDITOR_UNLIMITED_FILES,
    "maxbytes" => $CFG->maxbytes,
    "trusttext" => false,
    "noclean" => true,
    "context" => $catcontext,
    "subdirs" => 0,
];

$newcourse = null;
$newcourse = file_prepare_standard_editor($newcourse, "summary", $editoroptions, null, "course", "summary", null);

$overviewfilesoptions = course_overviewfiles_options($newcourse);
if ($overviewfilesoptions) {
    file_prepare_standard_filemanager($newcourse, "overviewfiles", $overviewfilesoptions, null, "course", "overviewfiles", 0);
}

$returnurl = new moodle_url("/local/kopere_dashboard/plugins/courses/");

$args = [
    "course" => $newcourse,
    "category" => $category,
    "editoroptions" => $editoroptions,
    "returnto" => 0,
    "returnurl" => $returnurl,
];

$formaction = new moodle_url("/local/kopere_dashboard/plugins/courses/create.php", ["category" => $categoryid]);
$editform = new course_edit_form($formaction, $args);

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    $createdcourse = create_course($data, $editoroptions);

    $newcoursecontext = context_course::instance($createdcourse->id, MUST_EXIST);
    if (is_siteadmin($USER->id)) {
        $enroluser = $CFG->enroladminnewcourse;
    } else {
        $enroluser = !is_viewing($newcoursecontext, null, "moodle/role:assign");
    }
    if (!empty($CFG->creatornewroleid) && $enroluser && !is_enrolled($newcoursecontext, null, "moodle/role:assign")) {
        enrol_try_internal_enrol($createdcourse->id, $USER->id, $CFG->creatornewroleid);
    }

    redirect(
        new moodle_url("/local/kopere_dashboard/plugins/courses/view.php", ["id" => $createdcourse->id]),
        get_string("success_course_created", "koperedashboard_courses", format_string($createdcourse->fullname)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

ob_start();
$editform->display();
$formhtml = ob_get_clean();

$content = $OUTPUT->render_from_template("koperedashboard_courses/create", [
    "formhtml" => $formhtml,
    "backurl" => new moodle_url("/local/kopere_dashboard/plugins/courses/"),
]);
layout::page_render($context, $content, true);
