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
 * Test functions around check_integrity
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
require_once($CFG->dirroot . '/local/gugrades/tests/external/gugrades_advanced_testcase.php');

/**
 * Test(s) for get_all_strings webservice
 */
final class check_integrity_test extends \local_gugrades\external\gugrades_advanced_testcase {
    /**
     * @var int $gradeitemsecondx
     */
    protected int $gradeitemsecondx;

    /**
     * Called before every test
     */
    protected function setUp(): void {
        parent::setUp();

        // Final item has an invalid grade type.
        $seconditemx = $this->getDataGenerator()->create_grade_item(
            ['courseid' => $this->course->id, 'gradetype' => GRADE_TYPE_TEXT]
        );
        $this->move_gradeitem_to_category($seconditemx->id, $this->gradecatsecond->id);

        $this->gradeitemsecondx = $seconditemx->id;
    }

    /**
     * Check creating an "exactly out of 22 grade" in the old regs
     * and then swapping to the new. 
     *
     * @covers \local_gugrades\external\check_integrity::execute
     */
    public function test_grade22_from_old_to_new(): void {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Make sure course is using "old" regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->startdate = strtotime('2026-01-01');
        $DB->update_record('course', $course);

        // Import grades.
        $userlist = [
            $this->student->id,
            $this->student2->id,
        ];

        // Assign2 (which is useing scale).
        $this->import_grades($this->course->id, $this->gradeitemidassign22, $userlist);

        // Get the capture page.
        $page = get_capture_page::execute($this->course->id, $this->gradeitemidassign22, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];

        $this->assertEquals('H', $fred['grades'][0]['displaygrade']);
        $this->assertEquals('FIRST', $fred['grades'][0]['gradetype']);

        $columns = $page['columns'];
        $this->assertFalse($columns[0]['points']);

        // If we change the date to enforce new rules, the above import will be broken.
        $course->startdate = strtotime('2027-01-01');
        $DB->update_record('course', $course);     

        // Integrity check
        $errors = check_integrity::execute($this->course->id);
        $errors = external_api::clean_returnvalue(
            check_integrity::execute_returns(),
            $errors
        );

        // Assign 22 is flagged by both the grademax=22 new-regs check and the range/points check.
        $this->assertCount(2, $errors['erroritems']);
        $this->assertEquals('Assign 22', $errors['erroritems'][0]['itemname']);
        $this->assertEquals('Assign 22', $errors['erroritems'][1]['itemname']);
    }

    /**
     * Check creating an "exactly out of 22 grade" in the old regs
     * and then swapping to the new. 
     *
     * @covers \local_gugrades\external\check_integrity::execute
     */
    public function test_grade22_from_new_to_old(): void {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Make sure course is using "new" regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->startdate = strtotime('2027-01-01');
        $DB->update_record('course', $course);

        // Import grades.
        $userlist = [
            $this->student->id,
            $this->student2->id,
        ];

        // Assign2 (which is useing scale).
        $this->import_grades($this->course->id, $this->gradeitemidassign22, $userlist);

        // Get the capture page.
        $page = get_capture_page::execute($this->course->id, $this->gradeitemidassign22, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];

        $this->assertEquals('0', $fred['grades'][0]['displaygrade']);
        $this->assertEquals('FIRST', $fred['grades'][0]['gradetype']);

        $columns = $page['columns'];
        $this->assertTrue($columns[0]['points']);

        // If we change the date to enforce old rules, the above import will still show as points. 
        // This isn't a problem for import but could break somewhere else. 
        $course->startdate = strtotime('2026-01-01');
        $DB->update_record('course', $course);    
        
        // Get the capture page.
        $page = get_capture_page::execute($this->course->id, $this->gradeitemidassign22, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        // Integrity check
        $errors = check_integrity::execute($this->course->id);
        $errors = external_api::clean_returnvalue(
            check_integrity::execute_returns(),
            $errors
        );

        $this->assertCount(2, $errors['erroritems']);
        $this->assertEquals('Assign 22', $errors['erroritems'][0]['itemname']);
    }

    /**
     * Check for any admin grades that are not permitted for current regs.
     * 
     */
    public function test_invalid_admin_grades(): void {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Make sure course is using "old" regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->startdate = strtotime('2026-01-01');
        $DB->update_record('course', $course);     
        
        // Add an NS0 to gradeitemsecond1
        $nothing = write_additional_grade::execute(
            courseid: $this->course->id,
            gradeitemid: $this->gradeitemsecond1,
            userid: $this->student->id,
            reason: 'SECOND',
            other: '',
            admingrade: 'NOSUBMISSION_0',
            scale: 0,
            grade: 0,
            notes: 'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // If we change the date to enforce new rules, NS0 is not permitted.
        $course->startdate = strtotime('2027-01-01');
        $DB->update_record('course', $course);   

        // Integrity check
        $errors = check_integrity::execute($this->course->id);
        $errors = external_api::clean_returnvalue(
            check_integrity::execute_returns(),
            $errors
        );

        $this->assertCount(1, $errors['erroritems']);
        $this->assertEquals('Second item 1', $errors['erroritems'][0]['itemname']);
    }

    /**
     * Check for changing gradebook settings out from underneath MyGrades
     */
    public function test_gradebook_change(): void {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Make sure course is using "old" regs.
        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->startdate = strtotime('2026-01-01');
        $DB->update_record('course', $course);     
        
        // Add grade 100 to gradeitemsecond1
        $nothing = write_additional_grade::execute(
            courseid: $this->course->id,
            gradeitemid: $this->gradeitemsecond1,
            userid: $this->student->id,
            reason: 'SECOND',
            other: '',
            admingrade: '',
            scale: 0,
            grade: 100,
            notes: 'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        ); 
        
        // Get the capture page.
        $page = get_capture_page::execute($this->course->id, $this->gradeitemsecond1, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('100', $fred['grades'][0]['displaygrade']);

        // Change the grademax for the grade item.
        $grade = $DB->get_record('grade_items', ['id' => $this->gradeitemsecond1], '*', MUST_EXIST);
        $grade->grademax = 50;
        $DB->update_record('grade_items', $grade);

        // Integrity check
        $errors = check_integrity::execute($this->course->id);
        $errors = external_api::clean_returnvalue(
            check_integrity::execute_returns(),
            $errors
        );

        $this->assertCount(1, $errors['erroritems']);
        $this->assertEquals('Second item 1', $errors['erroritems'][0]['itemname']);

        // Put grade back
        $grade->grademax = 100;
        $DB->update_record('grade_items', $grade);

        // Integrity check
        $errors = check_integrity::execute($this->course->id);
        $errors = external_api::clean_returnvalue(
            check_integrity::execute_returns(),
            $errors
        );

        $this->assertCount(0, $errors['erroritems']);

        // Change grade item to a scale
        $grade->gradetype = GRADE_TYPE_SCALE;
        $grade->grademax = 23.0;
        $grade->grademin = 1.0;
        $grade->scaleid = $this->scale->id;
        $DB->update_record('grade_items', $grade);

        // Integrity check
        $errors = check_integrity::execute($this->course->id);
        $errors = external_api::clean_returnvalue(
            check_integrity::execute_returns(),
            $errors
        );

        $this->assertCount(1, $errors['erroritems']);
        $this->assertEquals('Second item 1', $errors['erroritems'][0]['itemname']);
    }

    /**
     * Reassessment structure cleanup must not run for new regs courses.
     *
     * @covers \local_gugrades\external\check_integrity::execute
     */
    public function test_reassessment_structure_skipped_for_new_regs(): void {
        global $DB;

        $this->setUser($this->teacher);

        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->startdate = strtotime('2027-01-01');
        $DB->update_record('course', $course);

        $resitcat = $this->create_resit_test_category('Resit skip new regs');
        save_resit_item::execute($this->course->id, $resitcat['resititemid'], true);

        $extra = $this->getDataGenerator()->create_grade_item(
            ['courseid' => $this->course->id, 'itemname' => 'Extra assignment']
        );
        $this->move_gradeitem_to_category($extra->id, $resitcat['categoryid']);

        $result = check_integrity::execute($this->course->id);
        $result = external_api::clean_returnvalue(
            check_integrity::execute_returns(),
            $result
        );

        $this->assertCount(0, $result['reassessmentnotices']);
        $this->assertTrue($DB->record_exists('local_gugrades_resit', ['gradecategoryid' => $resitcat['categoryid']]));
    }

    /**
     * Reassessment notices must not interfere with other integrity errors.
     *
     * @covers \local_gugrades\external\check_integrity::execute
     */
    public function test_reassessment_notices_do_not_suppress_other_integrity_errors(): void {
        global $DB;

        $this->setUser($this->teacher);

        $course = $DB->get_record('course', ['id' => $this->course->id], '*', MUST_EXIST);
        $course->startdate = strtotime('2026-01-01');
        $DB->update_record('course', $course);

        $resitcat = $this->create_resit_test_category('Resit notice category');
        save_resit_item::execute($this->course->id, $resitcat['resititemid'], true);

        $nothing = write_additional_grade::execute(
            courseid: $this->course->id,
            gradeitemid: $resitcat['firstitemid'],
            userid: $this->student->id,
            reason: 'SECOND',
            other: '',
            admingrade: '',
            scale: 0,
            grade: 100,
            notes: 'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        $extra = $this->getDataGenerator()->create_grade_item(
            ['courseid' => $this->course->id, 'itemname' => 'Extra assignment']
        );
        $this->move_gradeitem_to_category($extra->id, $resitcat['categoryid']);

        $grade = $DB->get_record('grade_items', ['id' => $resitcat['firstitemid']], '*', MUST_EXIST);
        $grade->grademax = 50;
        $DB->update_record('grade_items', $grade);

        $this->assertNotEquals(2, \local_gugrades\grades::count_resit_category_children($resitcat['categoryid']));

        $result = check_integrity::execute($this->course->id);
        $result = external_api::clean_returnvalue(
            check_integrity::execute_returns(),
            $result
        );

        $this->assertCount(1, $result['reassessmentnotices']);
        $this->assertCount(1, $result['erroritems']);
        $this->assertEquals('Resit notice category', $result['reassessmentnotices'][0]['itemname']);
        $this->assertEquals('Resit first sitting', $result['erroritems'][0]['itemname']);
        $this->assertFalse($DB->record_exists('local_gugrades_resit', ['gradecategoryid' => $resitcat['categoryid']]));
    }

    /**
     * Create a grade category with two items suitable for reassessment tests.
     *
     * @param string $categoryname
     * @return array
     */
    protected function create_resit_test_category(string $categoryname): array {
        $category = $this->getDataGenerator()->create_grade_category([
            'courseid' => $this->course->id,
            'fullname' => $categoryname,
            'parent' => $this->gradecatsumm->id,
        ]);

        $firstitem = $this->getDataGenerator()->create_grade_item([
            'courseid' => $this->course->id,
            'itemname' => 'Resit first sitting',
        ]);
        $this->move_gradeitem_to_category($firstitem->id, $category->id);

        $resititem = $this->getDataGenerator()->create_grade_item([
            'courseid' => $this->course->id,
            'itemname' => 'Resit second sitting',
        ]);
        $this->move_gradeitem_to_category($resititem->id, $category->id);

        return [
            'categoryid' => $category->id,
            'firstitemid' => $firstitem->id,
            'resititemid' => $resititem->id,
        ];
    }

}
