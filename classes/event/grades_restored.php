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
 * Grades restored from the backup tables.
 *
 * @package    local_gugrades
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\event;

/**
 * Missing grades were written back from the backup.
 */
class grades_restored extends \core\event\base {
    /**
     * Initialise event.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'course';
    }

    /**
     * Get event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventgradesrestored', 'local_gugrades');
    }

    /**
     * Get event description.
     *
     * @return string
     */
    public function get_description() {
        $restored = $this->other['restored'] ?? 0;
        return "The user with id '$this->userid' restored $restored MyGrades grades for course '$this->objectid'.";
    }
}
