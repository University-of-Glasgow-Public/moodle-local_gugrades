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
 * Test MGU-1447
 * Points with a maxgrade of 22 AND using the new regulations now get ignored, otherwise, do as before.
 * @package    local_gugrades
 * @author     Greg Pedder <greg.pedder@glasgow.ac.uk>
 * @copyright  2026 University of Glasgow
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\external;

use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/gugrades/tests/external/gugrades_aggregation_testcase.php');

/**
 * Test(s) for course activities that are graded in points, specifically with a grademax of 22, using old or new regulations.
 */
final class mgu_1447_grading_points_22_regs_test extends \local_gugrades\external\gugrades_aggregation_testcase {

    /**
     * @var object $category
     */
    protected object $category;

    /**
     * @var object $gradecatsummative
     */
    protected object $gradecatsummative;

    /**
     * Additional setup steps
     * (Push course into its own category)
     */
    protected function setUp(): void {
        
        parent::setUp();
    }

    /**
     * Test that grading activities with a mixture of Points (with grademax=22) and Schedule A passes when using old regs.
     */
    public function test_grading_points_22_using_old_regulations() {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Create a new category for our course.
        $this->category = $this->getDataGenerator()->create_category([
            'name' => 'Mixed activities using old regulations',
        ]);

        // Change config to this category.
        set_config('workshopoldcat', $this->category->id, 'local_gugrades');

        // Switch test course into this category and new regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->category = $this->category->id;
        $course->startdate = strtotime('2026-01-01');
        $DB->update_record('course', $course);

        // Label on page, is part of L1 categories component.
        $l1categories = get_levelonecategories::execute($course->id);
        $l1categories = external_api::clean_returnvalue(
            get_levelonecategories::execute_returns(),
            $l1categories
        );

        // Confirm that we're using the old regulations.
        $this->assertEquals('original', $l1categories['regulationshort']);

        // Install test schema.
        $this->gradeitemids = $this->load_schema('schema15');

        // Get the grade category 'Summative'.
        $this->gradecatsummative = $DB->get_record('grade_categories', ['fullname' => 'Summative'], '*', MUST_EXIST);

        // Import grades only for one student (so far).
        $userlist = [
            $this->student->id,
        ];

        // Install test data for student.
        $this->load_data('data15', $this->student->id);

        foreach ($this->gradeitemids as $gradeitemid) {
            $this->import_grades($this->course->id, $gradeitemid, $userlist);
        }

        // Get aggregation page for above.
        $page = get_aggregation_page::execute($this->course->id, $this->gradecatsummative->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        // This is the aggregated category grade.
        $this->assertEquals('C2 (13)', $fred['displaygrade']);
    
    }
    
    /**
     * Test that grading activities with a mixture of Points (with grademax=22) and Schedule A fails when using new regs.
     */
    public function test_grading_points_22_using_new_regulations() {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Create a new category for our course.
        $category = $this->getDataGenerator()->create_category([
            'name' => 'Mixed activities using new regulations',
        ]);

        // Change config to this category.
        set_config('workshopnewcat', $category->id, 'local_gugrades');

        // Switch test course into this category and new regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->category = $category->id;
        $course->startdate = strtotime('2026-08-01');
        $DB->update_record('course', $course);

        // Label on page, is part of L1 categories component.
        $l1categories = get_levelonecategories::execute($this->course->id);
        $l1categories = external_api::clean_returnvalue(
            get_levelonecategories::execute_returns(),
            $l1categories
        );

        // Confirm that we're using the new regulations.
        $this->assertEquals('Academic Regs from 2026/27', $l1categories['regulation']);

        // Install test schema.
        $this->gradeitemids = $this->load_schema('schema15');

        // Get the grade category 'Summative'.
        $this->gradecatsummative = $DB->get_record('grade_categories', ['fullname' => 'Summative'], '*', MUST_EXIST);

        // Import grades only for one student (so far).
        $userlist = [
            $this->student->id,
        ];

        // Install test data for student.
        $this->load_data('data15', $this->student->id);

        foreach ($this->gradeitemids as $gradeitemid) {
            $this->import_grades($this->course->id, $gradeitemid, $userlist);
        }

        // Get aggregation page for above.
        $page = get_aggregation_page::execute($this->course->id, $this->gradecatsummative->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        // This should fail given that we can't aggregate when using a mixture of points and scales.
        $this->assertEquals('Cannot aggregate', $fred['displaygrade']);
    }

}