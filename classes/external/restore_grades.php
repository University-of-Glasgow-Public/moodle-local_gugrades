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
 * Restore missing MyGrades values from the backup tables.
 *
 * @package    local_gugrades
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Restore missing grades for a course.
 */
class restore_grades extends external_api {
    /**
     * Define function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
        ]);
    }

    /**
     * Write missing grades and columns back from the backup.
     *
     * @param int $courseid
     * @return array
     */
    public static function execute($courseid) {
        global $DB;

        \local_gugrades\development::increase_debugging();

        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);
        $courseid = $params['courseid'];
        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/gugrades:resetcourse', $context);
        require_capability('local/gugrades:editgrades', $context);

        $userids = $DB->get_fieldset_sql(
            "SELECT DISTINCT userid FROM {local_gugrades_gradesbackup} WHERE courseid = :courseid",
            ['courseid' => $courseid]
        );
        $restored = \local_gugrades\grades::restore_missing_grade_backups($courseid);
        \local_gugrades\api::reset_all_caches();

        $event = \local_gugrades\event\grades_restored::create([
            'objectid' => $courseid,
            'context' => $context,
            'other' => [
                'restored' => $restored,
            ],
        ]);
        $event->trigger();

        \local_gugrades\audit::write(
            $courseid,
            \local_gugrades\audit::relateduserid_for($userids),
            0,
            $restored
                ? get_string('restoregradessuccess', 'local_gugrades', $restored)
                : get_string('restoregradesnone', 'local_gugrades')
        );

        return [
            'restored' => $restored,
            'message' => $restored
                ? get_string('restoregradessuccess', 'local_gugrades', $restored)
                : get_string('restoregradesnone', 'local_gugrades'),
        ];
    }

    /**
     * Define result.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'restored' => new external_value(PARAM_INT, 'Number of grades written back'),
            'message' => new external_value(PARAM_TEXT, 'Message for the user'),
        ]);
    }
}
