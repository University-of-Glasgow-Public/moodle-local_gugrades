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
 * Regulations plugins
 *
 * Deals with regulations subplugins that define various different
 * regulation regimes.
 *
 * @package    local_gugrades
 * @copyright  2026
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades;

defined('MOODLE_INTERNAL') || die();

class regulations {

    /**
     * Get aggregation/regulations subplugins
     *
     */
    public static function get_regulations() {
        $regulations = [];
        $plugins = \core_component::get_plugin_list('regulations');

        foreach ($plugins as $pluginname => $regulation) {
            $classname = "\\regulations_" . $pluginname . "\\regulation";
            $class = new($classname);
            $regulations[$pluginname] = $class;
            continue;
        }

        return $regulations;
    }

    /**
     * Get the active regulations class
     * Works on a first one to "win" basis
     * TODO: This *definitely* needs some sort of cache.
     * @param int $courseid
     * @return object
     */
    public static function get_active_regulation(int $courseid): object {
        global $DB;

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $regulations = self::get_regulations();

        foreach ($regulations as $regulation) {
            if ($regulation->is_active($course)) {
                return $regulation;
            }
        }

        // If none found.
        throw new \moodle_exception('Active regulation not found for course');
    }
}