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
 * edit.php
 *
 * Reuses Moodle's real course_edit_form (same one powering /course/edit.php) so course editing
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
require_capability("koperedashboard/courses:manage", $context);

$courseid = required_param("id", PARAM_INT);

if ($courseid == SITEID) {
    throw new moodle_exception("cannoteditsiteform");
}

$editcourse = get_course($courseid);
$editcourse = course_get_format($editcourse)->get_course();
$category = $DB->get_record("course_categories", ["id" => $editcourse->category], "*", MUST_EXIST);
$coursecontext = context_course::instance($editcourse->id);
require_capability("moodle/course:update", $coursecontext);

$PAGE->set_url(new moodle_url("/local/kopere_dashboard/plugins/courses/edit.php", ["id" => $courseid]));
$PAGE->add_body_class("local-kopere_dashboard");
$PAGE->set_context($context);
$PAGE->set_title(get_string("edit_title", "koperedashboard_courses", format_string($editcourse->fullname)));
$PAGE->requires->css("/local/kopere_dashboard/plugins/courses/styles.css");

$editoroptions = [
    "maxfiles" => EDITOR_UNLIMITED_FILES,
    "maxbytes" => $CFG->maxbytes,
    "trusttext" => false,
    "noclean" => true,
    "context" => $coursecontext,
    "subdirs" => file_area_contains_subdirs($coursecontext, "course", "summary", 0),
];

$editcourse = file_prepare_standard_editor($editcourse, "summary", $editoroptions, $coursecontext, "course", "summary", 0);

$overviewfilesoptions = course_overviewfiles_options($editcourse);
if ($overviewfilesoptions) {
    file_prepare_standard_filemanager(
        $editcourse, "overviewfiles", $overviewfilesoptions, $coursecontext, "course", "overviewfiles", 0
    );
}

$editcourse->tags = core_tag_tag::get_item_tags_array("core", "course", $editcourse->id);

$returnurl = new moodle_url("/local/kopere_dashboard/plugins/courses/view.php", ["id" => $editcourse->id]);

$args = [
    "course" => $editcourse,
    "category" => $category,
    "editoroptions" => $editoroptions,
    "returnto" => 0,
    "returnurl" => $returnurl,
];

$formaction = new moodle_url("/local/kopere_dashboard/plugins/courses/edit.php", ["id" => $courseid]);
$editform = new course_edit_form($formaction, $args);

if ($editform->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editform->get_data()) {
    update_course($data, $editoroptions);

    redirect(
        $returnurl,
        get_string("success_course_updated", "koperedashboard_courses", format_string($editcourse->fullname)),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

ob_start();
$editform->display();
$formhtml = ob_get_clean();

$content = $OUTPUT->render_from_template("koperedashboard_courses/edit", [
    "formhtml" => $formhtml,
    "backurl" => $returnurl,
    "coursename" => format_string($editcourse->fullname),
]);
layout::page_render($context, $content, true);
