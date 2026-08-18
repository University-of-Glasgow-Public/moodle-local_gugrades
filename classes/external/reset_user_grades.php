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
 * Define function reset_user_grades
 * @package    local_gugrades
 * @copyright  2026
 * @author     Michael Clark
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Define function reset user MyGrades data
 */
class reset_user_grades extends external_api {
    /**
     * Define function parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'userid' => new external_value(PARAM_INT, 'User id'),
        ]);
    }

    /**
     * Execute function
     * @param int $courseid
     * @param int $userid
     * @return array
     */
    public static function execute($courseid, $userid) {
        \local_gugrades\development::increase_debugging();

        self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'userid' => $userid,
        ]);
        $context = \context_course::instance($courseid);
        self::validate_context($context);
        require_capability('local/gugrades:resetcourse', $context);

        \local_gugrades\api::reset_user_grades($courseid, $userid);
        \local_gugrades\audit::write($courseid, $userid, 0, 'Removed user MyGrades data.');

        return [];
    }

    /**
     * Define result
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([]);
    }
}
