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
 * catalog_service.php
 *
 * @package   koperedashboard_allcourses
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace koperedashboard_allcourses\service;

use core_course_category;
use dml_exception;
use enrol_self_plugin;
use stdClass;

/**
 * Reads the course catalogue shown in the "All courses" page.
 *
 * Enrolment eligibility is always delegated to the core enrol_self plugin so that enrolment
 * keys, date windows, capacity limits and cohort restrictions keep being honoured.
 */
class catalog_service {
    /**
     * Every visible course, flagged with whether the user is already enrolled.
     *
     * Courses the user has not joined yet are returned first, so the actionable ones are on top.
     * The returned records carry the raw course fields plus "categoryname" and "enrolled".
     *
     * @param int $userid
     * @param string $query Optional free text filter on full name, short name or id number.
     * @param int $limit
     * @return array<int,stdClass>
     * @throws dml_exception
     */
    public static function get_catalogue_courses(int $userid, string $query = "", int $limit = 200): array {
        global $DB;

        $params = [
            "siteid" => SITEID,
            "userid" => $userid,
        ];

        $conditions = [
            "c.id <> :siteid",
            "c.visible = 1",
            "cc.visible = 1",
        ];

        $query = trim($query);
        if ($query !== "") {
            $like = "%" . $DB->sql_like_escape($query) . "%";
            $conditions[] = "(" .
                $DB->sql_like("c.fullname", ":q1", false) . " OR " .
                $DB->sql_like("c.shortname", ":q2", false) . " OR " .
                $DB->sql_like("c.idnumber", ":q3", false) .
                ")";
            $params += [
                "q1" => $like,
                "q2" => $like,
                "q3" => $like,
            ];
        }

        // Enrolled by any method, so the row can be shown as "already joined".
        $enrolledsql = "CASE WHEN EXISTS (SELECT 1
                                            FROM {user_enrolments} ue
                                            JOIN {enrol} e ON e.id = ue.enrolid
                                           WHERE e.courseid = c.id
                                             AND ue.userid = :userid)
                             THEN 1 ELSE 0 END";

        $sql = "SELECT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.startdate, c.enddate,
                       c.visible, c.category, cc.name AS categoryname,
                       {$enrolledsql} AS enrolled
                  FROM {course} c
                  JOIN {course_categories} cc ON cc.id = c.category
                 WHERE " . implode(" AND ", $conditions) . "
              ORDER BY enrolled ASC, c.fullname ASC";

        $courses = $DB->get_records_sql($sql, $params, 0, $limit);

        // Drop anything the user is not allowed to even see the details of.
        foreach ($courses as $id => $course) {
            if (!core_course_category::can_view_course_info($course)) {
                unset($courses[$id]);
            }
        }

        return $courses;
    }

    /**
     * How many courses the user has not joined yet.
     *
     * @param int $userid
     * @return int
     * @throws dml_exception
     */
    public static function count_available_courses(int $userid): int {
        $notenrolled = 0;
        foreach (self::get_catalogue_courses($userid, "", 0) as $course) {
            if (empty($course->enrolled)) {
                $notenrolled++;
            }
        }

        return $notenrolled;
    }

    /**
     * The self enrolment instance of a course that the current user may use, if any.
     *
     * A course can hold several self enrolment instances; the first one the user is
     * actually allowed to use wins. When none is usable the reason reported by core
     * for the last candidate is returned instead.
     *
     * @param int $courseid
     * @return array{instance: stdClass|null, reason: string} "reason" is only filled when instance is null.
     * @throws dml_exception
     */
    public static function get_usable_self_instance(int $courseid): array {
        global $DB;

        $plugin = self::get_self_plugin();
        if ($plugin === null) {
            return ["instance" => null, "reason" => get_string("error_selfdisabled", "koperedashboard_allcourses")];
        }

        $instances = $DB->get_records("enrol", [
            "courseid" => $courseid,
            "enrol" => "self",
            "status" => ENROL_INSTANCE_ENABLED,
        ], "sortorder ASC, id ASC");

        $reason = get_string("error_unavailable", "koperedashboard_allcourses");

        foreach ($instances as $instance) {
            $can = $plugin->can_self_enrol($instance);
            if ($can === true) {
                return ["instance" => $instance, "reason" => ""];
            }

            // can_self_enrol() may return markup (it can embed a continue button); keep the text only.
            if (is_string($can) && trim(strip_tags($can)) !== "") {
                $reason = trim(strip_tags($can));
            }
        }

        return ["instance" => null, "reason" => $reason];
    }

    /**
     * The core self enrolment plugin, or null when it is disabled site wide.
     *
     * @return enrol_self_plugin|null
     */
    public static function get_self_plugin(): ?enrol_self_plugin {
        $plugin = enrol_get_plugin("self");

        return ($plugin instanceof enrol_self_plugin) ? $plugin : null;
    }

    /**
     * Certificate view URLs for courses the user has completed, keyed by course id.
     *
     * Only courses core marks as complete for the user are considered, and only when they
     * contain a customcert activity the user is actually allowed to see.
     *
     * @param int $userid
     * @param array<int,int> $courseids
     * @return array<int,string> courseid => mod_customcert view.php URL
     * @throws dml_exception
     */
    public static function get_certificate_urls(int $userid, array $courseids): array {
        global $DB;

        if (empty($courseids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, "courseid");
        $completedcourseids = $DB->get_fieldset_select(
            "course_completions",
            "course",
            "userid = :userid AND course {$insql} AND timecompleted > 0",
            $inparams + ["userid" => $userid]
        );

        $urls = [];
        foreach ($completedcourseids as $courseid) {
            $cmid = self::get_first_certificate_cmid($userid, (int) $courseid);
            if ($cmid !== null) {
                $urls[(int) $courseid] = (new \moodle_url("/mod/customcert/view.php", ["id" => $cmid]))->out(false);
            }
        }

        return $urls;
    }

    /**
     * The course module id of the first customcert activity the user can see in a course.
     *
     * @param int $userid
     * @param int $courseid
     * @return int|null
     */
    private static function get_first_certificate_cmid(int $userid, int $courseid): ?int {
        $modinfo = get_fast_modinfo($courseid, $userid);
        foreach ($modinfo->get_instances_of("customcert") as $cm) {
            if ($cm->uservisible) {
                return (int) $cm->id;
            }
        }

        return null;
    }
}
