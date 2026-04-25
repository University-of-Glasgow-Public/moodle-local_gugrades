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
 * Regulation subplugin interface
 *
 *
 * @package    local_gugrades
 * @copyright  2026
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades;

defined('MOODLE_INTERNAL') || die();

interface IRegulation {

    /**
     * Get short name
     * @return string
     */
    public function shortname(): string;

    /**
     * Display name of regulation.
     * @return string
     */
    public function displayname(): string;

    /**
     * Active regulation.
     * Decide if this is the active regulation based on course settings.
     * @param object $course
     * @return bool
     */
    public function is_active(object $course): bool;

    /**
     * Get aggregation object
     * @param int $courseid
     * @param string $atype
     * @return object
     */
    public function get_aggregation(int $courseid, string $atype): object;

    /**
     * Is NS0 supported.
     * This appears on menu options in original version.
     * @return bool
     */
    public function is_ns0_available(): bool;

    /**
     * Is completion supported
     * @return bool
     */
    public function is_completion_supported(): bool;

    /**
     * Get list of available admin grades, given level
     * NOTE: Level == 0, means 'grand'/final total
     * List of admincode 'names' is returned for level;
     * translation is done in admingrades class.
     * @param int $level
     * @return array
     */
    public function get_admingrades(int $level): array;
}