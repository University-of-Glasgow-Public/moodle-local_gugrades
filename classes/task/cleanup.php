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
 * Cleanup database tables
 *
 * @package    local_gugrades
 * @copyright  2026 Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\task;

/**
 * Cleanup old data
 */
class cleanup extends \core\task\scheduled_task {
    /**
     * Get task name
     * @return string
     */
    public function get_name() {
        // Shown in admin screens.
        return get_string('cleanuptask', 'local_gugrades');
    }

    /**
     * Unused historical (iscurrent = 0) aggregated category grades with no
     * numeric grade, converted grade, admin grade or category override.
     * Current capture grades and live aggregation data are never matched.
     *
     * @return array Select SQL and params
     */
    public static function unused_category_select(): array {
        $cutoff = time() - (183 * 86400);
        $select = "gradetype = :gradetype
            AND rawgrade IS NULL
            AND convertedgrade IS NULL
            AND catoverride = 0
            AND (admingrade IS NULL OR admingrade = :emptyadmin)
            AND audittimecreated < :cutoff";
        $params = [
            'gradetype' => 'CATEGORY',
            'emptyadmin' => '',
            'cutoff' => $cutoff,
        ];

        return [$select, $params];
    }

    /**
     * Cleanup
     */
    public function execute() {
        global $DB;

        // Delete unused intermediate category grades after 6 months.
        [$select, $params] = self::unused_category_select();
        $count = $DB->count_records_select('local_gugrades_grade', $select, $params);
        mtrace("Deleting {$count} unused intermediate MyGrades CATEGORY records older than 6 months.");
        $DB->delete_records_select('local_gugrades_grade', $select, $params);

        return true;
    }
}
