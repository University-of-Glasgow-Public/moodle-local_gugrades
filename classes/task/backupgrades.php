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
 * Scheduled copy of current MyGrades values.
 *
 * @package    local_gugrades
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\task;

/**
 * Copy current grades into the backup table.
 * Listed under Site administration > Server > Tasks > Scheduled tasks.
 */
class backupgrades extends \core\task\scheduled_task {
    /** @var int Seconds spent copying on each run. */
    public const TIME_LIMIT = 25;

    /**
     * Task name shown in the scheduled task list.
     *
     * @return string
     */
    public function get_name() {
        return get_string('gradesbackuptask', 'local_gugrades');
    }

    /**
     * Copy grades until the time limit is reached or none remain.
     */
    public function execute() {
        \local_gugrades\grades::run_grade_backup_batches(gradesbackup::BATCH_SIZE, self::TIME_LIMIT);
    }
}
