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
 * Original regulations
 *
 * Defines aggregation rules for original regulations
 *
 * @package    local_gugrades
 * @copyright  2026
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace regulations_from2026;

defined('MOODLE_INTERNAL') || die();

class regulation implements \local_gugrades\IRegulation {

    /**
     * Get short name
     * @return string
     */
    public function shortname(): string {
        return 'from2026';
    }

    /**
     * Display name of regulation.
     * @return string
     */
    public function displayname(): string {
        return 'Academic Regs from 2026/27';
    }

    /**
     * Active regulation.
     * Decide if this is the active regulation based on course settings.
     * @param object $course
     * @return bool
     */
    public function is_active(object $course): bool {
        global $DB;

        // This works up to 1st August 2026.
        $startdate = strtotime('2026-08-01');

        // Check there is a startdate (unlikely to be an issue).
        if (!$course->startdate) {
            throw new \moodle_exception('Course start date has not been set');
        }

        return $course->startdate > $startdate;
    }

    /**
     * Get aggregation object
     * @param int $courseid
     * @param string $atype
     * @return object
     */
    public function get_aggregation(int $courseid, string $atype): object {
        $class = new("\\regulations_from2026\\aggregate");
        $class->set_data($courseid, $atype);

        return $class;
    }

    /**
     * Is NS0 supported.
     * This appears on menu options in original version.
     * @return bool
     */
    public function is_ns0_available(): bool {
        return false;
    }

    /**
     * Is completion supported
     * @return bool
     */
    public function is_completion_supported(): bool {
        return false;
    }

    /**
     * Get list of available admin grades, given level
     * NOTE: Level == 0, means 'grand'/final total
     * List of admincode 'names' is returned for level;
     * translation is done in admingrades class.
     * @param int $level
     * @return array
     */
    public function get_admingrades(int $level): array {
        if ($level == 0) {
            return [
                'GOODCAUSE_FO',
                'DEFERRED',
                'GOODCAUSECREDITWITHHELD',
                'CREDITWITHHELD',
                'UNSATISFACTORY',
                'SATISFACTORY',
                'NOTPASSED',
                'PASSED',
                'NOTCOMPLETE',
                'COMPLETE',
                'CREDITREFUSED',
                'CREDITAWARDED',
                'AUDITONLY',
                'INTERRUPTIONOFSTUDIES',
            ];
        } else if ($level == 1) {
            return [
                'GOODCAUSE_FO',
                'GOODCAUSE_NR',
                'NOSUBMISSION',
                'DEFERRED',
                'INTERRUPTIONOFSTUDIES',
            ];
        } else {
            return [
                'GOODCAUSE_FO',
                'GOODCAUSE_NR',
                'NOSUBMISSION',
                'DEFERRED',
                'INTERRUPTIONOFSTUDIES',
            ];
        }
    }

}