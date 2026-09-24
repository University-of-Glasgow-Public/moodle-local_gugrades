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
 * Test functions around MGU-1560 reassessments.
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
 * Test(s) for two columns 
 */
final class mgu_1560_new_reg_reassessment_test extends \local_gugrades\external\gugrades_aggregation_testcase {
    /**
     * @var int $gradeitemsecondx
     */
    protected int $gradeitemsecondx;

    /**
     * @var array $gradeitemids
     */
    protected array $gradeitemids;

    /**
     * @var object $gradecatsummative
     */
    protected object $gradecatsummative;

    /**
     * @var int $mapid
     */
    protected int $mapid;

    /**
     * Called before every test
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        // Install test schema.
        $this->gradeitemids = $this->load_schema('schema18');

        // Get the grade category 'summative'.
        $this->gradecatsummative = $DB->get_record('grade_categories', ['fullname' => 'Summative'], '*', MUST_EXIST);

        // Make a conversion map.
        $this->mapid = $this->make_conversion_map();
    }

    /**
     * Check basics of reassessment in new regs
     * 
     * @return void
     */
    public function test_basic_two_reassessment(): void {
        global $DB;

        // Make sure that we're a teacher
        $this->setUser($this->teacher);

        // Import grades only for one student (so far).
        $userlist = [
            $this->student->id,
        ];

        // Ensure new regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->startdate = strtotime('2027-01-01');
        $DB->update_record('course', $course);

        // Get summer exam
        $gradecatsummer = $DB->get_record('grade_categories', ['fullname' => 'Summer exam'], '*', MUST_EXIST);

        // Get aggregation page for category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];

        // No reassessment set, so 1st attempt grade should be empty
        $this->assertFalse($page['isreassessment']);

        // Mark as resit (set resit flag for category)
        $flags = [
            [
                'gradecategoryid' => $gradecatsummer->id,
                'gradeitemid' => 0,
                'engexam' => false,
                'resit' => true,
            ]
        ];
        $nothing = write_flags::execute($this->course->id, $flags);
        $nothing = external_api::clean_returnvalue(
            write_flags::execute_returns(),
            $nothing,
        );

        // Get aggregation page for category.
        // At this point there should be no data. 
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('Grades missing', $fred['displaygrade']);

        // Install test data for student.
        $this->load_data('data18a', $this->student->id);
        foreach ($this->gradeitemids as $gradeitemid) {
            $this->import_grades($this->course->id, $gradeitemid, $userlist);
        }

        // Get aggregation page for category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertTrue($page['isreassessment']);
        $this->assertEquals('A5', $fred['displaygrade']);

        // Set summer resit to EC
        $summerresit = $this->get_gradeitemid('Summer resit');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $summerresit,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     'GOODCAUSE_FO',
            scale:          0,
            grade:          0,
            notes:          'Good cause'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get aggregation page for category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('EC', $fred['displaygrade']);
    }

    /**
     * Check slightly more complex example of reassessment in new regs
     * 
     * @return void
     */
    public function test_three_reassessment(): void {
        global $DB;

        // Make sure that we're a teacher
        $this->setUser($this->teacher);

        // Import grades only for one student (so far).
        $userlist = [
            $this->student->id,
        ];

        // Ensure new regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->startdate = strtotime('2027-01-01');
        $DB->update_record('course', $course);

        // Get scale exam
        $gradecatscale = $DB->get_record('grade_categories', ['fullname' => 'Scale exam'], '*', MUST_EXIST);

        // Mark as resit (set resit flag for category)
        $flags = [
            [
                'gradecategoryid' => $gradecatscale->id,
                'gradeitemid' => 0,
                'engexam' => false,
                'resit' => true,
            ]
        ];
        $nothing = write_flags::execute($this->course->id, $flags);
        $nothing = external_api::clean_returnvalue(
            write_flags::execute_returns(),
            $nothing,
        );

        // Get aggregation page for category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatscale->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('Grades missing', $fred['displaygrade']);

        // Add first grade - miserable failure
        $questionx = $this->get_gradeitemid('Question X');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $questionx,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     '',
            scale:          7,
            grade:          0,
            notes:          'Miserable failure'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get aggregation page for category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatscale->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('E2', $fred['displaygrade']);

        // Add EC for Question Y
        $questiony = $this->get_gradeitemid('Question Y');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $questiony,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     'GOODCAUSE_FO',
            scale:          0,
            grade:          0,
            notes:          'Extra credit'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get aggregation page for category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatscale->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('EC', $fred['displaygrade']);

        // Add Grade for Question Z 
        $questionz = $this->get_gradeitemid('Question Z');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $questionz,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     '',
            scale:          2,
            grade:          0,
            notes:          'Extra credit'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get aggregation page for category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatscale->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];   
        $this->assertEquals('E2', $fred['displaygrade']);
    }
}
