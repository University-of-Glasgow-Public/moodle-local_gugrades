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
 * Define function delete_capture_column
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
 * Define function delete_capture_column
 */
class delete_capture_column extends external_api {
    /**
     * Define function parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course id'),
            'gradeitemid' => new external_value(PARAM_INT, 'Grade item id'),
            'columnid' => new external_value(PARAM_INT, 'Column id'),
        ]);
    }

    /**
     * Execute function
     * @param int $courseid
     * @param int $gradeitemid
     * @param int $columnid
     * @return array
     */
    public static function execute($courseid, $gradeitemid, $columnid) {
        \local_gugrades\development::increase_debugging();

        self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'gradeitemid' => $gradeitemid,
            'columnid' => $columnid,
        ]);
        $context = \context_course::instance($courseid);
        self::validate_context($context);

        // Admin-only feature.
        require_capability('local/gugrades:resetcourse', $context);

        \local_gugrades\api::delete_capture_column($courseid, $gradeitemid, $columnid);
        \local_gugrades\audit::write($courseid, $gradeitemid, 0, 'Deleted capture column.');

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

