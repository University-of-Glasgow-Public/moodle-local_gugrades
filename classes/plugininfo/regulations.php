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
 * GuGrades regulations plugin info
 *
 * @package    local_gugrades
 * @copyright  Howard Miller 2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_gugrades\plugininfo;

use core\plugininfo\base;

/**
 * The local_gugrades\plugininfo\regulations class.
 *
 * @package    local_gugrades
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class regulations extends base {
    /**
     * Should there be a way to uninstall the plugin via the administration UI?
     *
     * @return boolean
     */
    public function is_uninstall_allowed() {

        return true;
    }
}
