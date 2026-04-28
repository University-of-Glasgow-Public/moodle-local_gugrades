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
 * Test MGU-1439
 * Capping for engineering
 * @package    local_gugrades
 * @copyright  2026
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\external;

use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/gugrades/tests/external/gugrades_aggregation_testcase.php');

/**
 * Test(s) for resit web services
 */
final class mgu_1439_engineering_test extends \local_gugrades\external\gugrades_aggregation_testcase {

    /**
     * Additional setup steps
     * (Push course into engineering category)
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        // Create new category for engineering.
        $category = $this->getDataGenerator()->create_category([
            'name' => 'Engineering',
        ]);

        // Change config to this category.
        set_config('engineeringcat', $category->id, 'local_gugrades');

        // Switch test course into this category and new regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->category = $category->id;
        $course->startdate = strtotime('2027-01-01');
        $DB->update_record('course', $course);
    }

    /**
     * Test detecting course is in Engineering
     */
    public function test_regulations_detection(): void {

        // Label on page, is part of L1 categories component.
        $l1categories = get_levelonecategories::execute($this->course->id);
        $l1categories = external_api::clean_returnvalue(
            get_levelonecategories::execute_returns(),
            $l1categories
        );

        $this->assertEquals('Academic Regs from 2026/27', $l1categories['regulation']);
        $this->assertEquals('Engineering', $l1categories['regulationextra']);
    }

}