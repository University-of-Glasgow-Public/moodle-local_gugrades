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
 * Test MGU-1442
 * Capping and 'X' grades for nursing
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
final class mgu_1442_nursing_test extends \local_gugrades\external\gugrades_aggregation_testcase {

    /**
     * @var object $gradecatsummative
     */
    protected object $gradecatsummative;

    /**
     * Additional setup steps
     * (Push course into engineering category)
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        // Create new category for Nursing.
        $category = $this->getDataGenerator()->create_category([
            'name' => 'Nursing Undergraduate',
        ]);

        // Change config to this category.
        set_config('nursingcat', $category->id, 'local_gugrades');

        // Switch test course into this category and new regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->category = $category->id;
        $course->startdate = strtotime('2027-01-01');
        $DB->update_record('course', $course);

        // Install test schema.
        $this->gradeitemids = $this->load_schema('schema8');

        // Get the grade category 'Summative'.
        $this->gradecatsummative = $DB->get_record('grade_categories', ['fullname' => 'Summative'], '*', MUST_EXIST);
    }

    /**
     * Test detecting course is in Nursing
     */
    public function test_regulations_detection(): void {

        // Label on page, is part of L1 categories component.
        $l1categories = get_levelonecategories::execute($this->course->id);
        $l1categories = external_api::clean_returnvalue(
            get_levelonecategories::execute_returns(),
            $l1categories
        );

        $this->assertEquals('Academic Regs from 2026/27', $l1categories['regulation']);
        $this->assertEquals('Nursing', $l1categories['regulationextra']);
    }

    /**
     * Test schema 8 with Nursing UG rules
     */
    public function test_education_aggregation(): void {

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Import grades only for one student (so far).
        $userlist = [
            $this->student->id,
        ];

        // Install test data for student.
        $this->load_data('data8a', $this->student->id);

        foreach ($this->gradeitemids as $gradeitemid) {
            $this->import_grades($this->course->id, $gradeitemid, $userlist);
        }

        // Set aggregation strategy.
        $this->set_strategy($this->gradecatsummative->id, \GRADE_AGGREGATE_WEIGHTED_MEAN);

        // Get aggregation page for above.
        $page = get_aggregation_page::execute($this->course->id, $this->gradecatsummative->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('XC2 (13)', $fred['displaygrade']);

        // Write grade 17 for Question 3
        // Should remove X
        $question3 = $this->get_gradeitemid('Question 3');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $question3,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     '',
            scale:          17,
            grade:          0,
            notes:          'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get aggregation page for above.
        $page = get_aggregation_page::execute($this->course->id, $this->gradecatsummative->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('C1 (14)', $fred['displaygrade']);

        // Make Question 3 no submission.
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $question3,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     'NOSUBMISSION',
            scale:          0,
            grade:          0,
            notes:          'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get aggregation page for above.
        $page = get_aggregation_page::execute($this->course->id, $this->gradecatsummative->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('XC2 (12.66667)', $fred['displaygrade']);
    }

}