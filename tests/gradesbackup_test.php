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
 * Tests for the grade value backup table.
 *
 * @package    local_gugrades
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades;

/**
 * Grade value backup tests.
 * @group local_gugrades
 */
final class gradesbackup_test extends \advanced_testcase {
    /**
     * Set up.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Create a course, teacher and grade item, and log the teacher in.
     *
     * @return array
     */
    protected function course_with_item(): array {
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->setUser($teacher);
        $gradeitem = $this->getDataGenerator()->create_grade_item(['courseid' => $course->id]);

        return [$course, $student, $gradeitem];
    }

    /**
     * Write one grade through the normal MyGrades path.
     *
     * @param int $courseid
     * @param int $gradeitemid
     * @param int $userid
     * @param string $gradetype
     * @param string $displaygrade
     * @param float|null $rawgrade
     * @param string $admingrade
     */
    protected function write_grade(
        int $courseid,
        int $gradeitemid,
        int $userid,
        string $gradetype,
        string $displaygrade,
        float|null $rawgrade,
        string $admingrade = '',
    ): void {
        grades::write_grade(
            courseid: $courseid,
            gradeitemid: $gradeitemid,
            userid: $userid,
            admingrade: $admingrade,
            rawgrade: $rawgrade,
            convertedgrade: $rawgrade,
            displaygrade: $displaygrade,
            weightedgrade: 0,
            gradetype: $gradetype,
            other: '',
            iscurrent: true,
            iserror: false,
            auditcomment: 'Captured',
            ispoints: true,
        );
    }

    /**
     * Saving a grade copies the actual value, and a later save replaces that copy.
     *
     * @covers \local_gugrades\grades::write_grade
     * @covers \local_gugrades\grades::save_grade_backup
     */
    public function test_write_grade_copies_actual_value(): void {
        global $DB;

        [$course, $student, $gradeitem] = $this->course_with_item();
        $this->write_grade($course->id, $gradeitem->id, $student->id, 'FIRST', '40', 40);
        $this->write_grade($course->id, $gradeitem->id, $student->id, 'FIRST', '55', 55);
        $this->write_grade($course->id, $gradeitem->id, $student->id, 'SECOND', 'NS', null, 'NOSUBMISSION');

        $values = $DB->get_records('local_gugrades_gradesbackup', ['gradeitemid' => $gradeitem->id], 'gradetype');
        $this->assertCount(2, $values);

        $first = $DB->get_record('local_gugrades_gradesbackup', [
            'gradeitemid' => $gradeitem->id,
            'userid' => $student->id,
            'gradetype' => 'FIRST',
        ], '*', MUST_EXIST);
        $current = $DB->get_record('local_gugrades_grade', [
            'gradeitemid' => $gradeitem->id,
            'userid' => $student->id,
            'gradetype' => 'FIRST',
            'iscurrent' => 1,
        ], '*', MUST_EXIST);
        $this->assertEquals(55.0, (float) $first->rawgrade);
        $this->assertEquals(55.0, (float) $first->convertedgrade);
        $this->assertSame('55', $first->displaygrade);
        $this->assertSame('', $first->admingrade);
        $this->assertEquals($current->id, $first->gradeid);
        $this->assertSame('Captured', $current->auditcomment);
        $history = $DB->count_records('local_gugrades_grade', [
            'gradeitemid' => $gradeitem->id,
            'gradetype' => 'FIRST',
        ]);
        $this->assertSame(2, $history);

        $second = $DB->get_record('local_gugrades_gradesbackup', [
            'gradeitemid' => $gradeitem->id,
            'gradetype' => 'SECOND',
        ], '*', MUST_EXIST);
        $this->assertSame('NOSUBMISSION', $second->admingrade);
        $this->assertSame('NS', $second->displaygrade);
        $this->assertNull($second->rawgrade);
    }

    /**
     * The value copy remains after the main grade rows are removed, and can be restored.
     *
     * @covers \local_gugrades\grades::restore_missing_grade_backups
     */
    public function test_value_survives_main_table_delete_and_restores(): void {
        global $DB;

        [$course, $student, $gradeitem] = $this->course_with_item();
        $this->write_grade($course->id, $gradeitem->id, $student->id, 'FIRST', '42', 42);

        $DB->delete_records('local_gugrades_grade', ['courseid' => $course->id]);
        $DB->delete_records('local_gugrades_latest', ['courseid' => $course->id]);

        $value = $DB->get_record('local_gugrades_gradesbackup', ['courseid' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(42.0, (float) $value->rawgrade);

        $restored = grades::restore_missing_grade_backups($course->id);
        $this->assertSame(1, $restored);

        $grade = $DB->get_record('local_gugrades_grade', [
            'courseid' => $course->id,
            'userid' => $student->id,
            'iscurrent' => 1,
        ], '*', MUST_EXIST);
        $this->assertEquals(42.0, (float) $grade->rawgrade);
        $this->assertSame('42', $grade->displaygrade);
        $this->assertSame('Restored from grades backup', $grade->auditcomment);
    }

    /**
     * A MyGrades reset clears the main grades and keeps the value copy for recovery.
     *
     * @covers \local_gugrades\api::reset
     * @covers \local_gugrades\grades::restore_missing_grade_backups
     */
    public function test_value_survives_mygrades_reset_and_restores(): void {
        global $DB;

        [$course, $student, $gradeitem] = $this->course_with_item();
        $this->write_grade($course->id, $gradeitem->id, $student->id, 'FIRST', '18', 18);

        api::reset($course->id);

        $this->assertSame(0, $DB->count_records('local_gugrades_grade', ['courseid' => $course->id]));
        $value = $DB->get_record('local_gugrades_gradesbackup', ['courseid' => $course->id], '*', MUST_EXIST);
        $this->assertEquals(18.0, (float) $value->rawgrade);

        $restored = grades::restore_missing_grade_backups($course->id);
        $this->assertSame(1, $restored);
        $grade = $DB->get_record('local_gugrades_grade', [
            'gradeitemid' => $gradeitem->id,
            'userid' => $student->id,
            'iscurrent' => 1,
        ], '*', MUST_EXIST);
        $this->assertEquals(18.0, (float) $grade->rawgrade);
        $this->assertSame(1, $DB->count_records('local_gugrades_gradesbackup', ['courseid' => $course->id]));
    }

    /**
     * Course deletion and privacy deletion remove the value copy.
     *
     * @covers \local_gugrades\grades::delete_course
     * @covers \local_gugrades\privacy\provider::delete_data_for_all_users_in_context
     */
    public function test_course_and_privacy_deletion_remove_values(): void {
        global $DB;

        [$course, $student, $gradeitem] = $this->course_with_item();
        $this->write_grade($course->id, $gradeitem->id, $student->id, 'FIRST', '10', 10);
        grades::delete_course($course->id);
        $this->assertSame(0, $DB->count_records('local_gugrades_gradesbackup', ['courseid' => $course->id]));

        [$course, $student, $gradeitem] = $this->course_with_item();
        $this->write_grade($course->id, $gradeitem->id, $student->id, 'FIRST', '10', 10);
        $context = \context_course::instance($course->id);
        privacy\provider::delete_data_for_all_users_in_context($context);
        $this->assertSame(0, $DB->count_records('local_gugrades_gradesbackup', ['courseid' => $course->id]));
    }

    /**
     * Status messages are not copied, and they replace an earlier backup for that column.
     *
     * @covers \local_gugrades\grades::is_backup_grade
     * @covers \local_gugrades\grades::save_grade_backup
     */
    public function test_status_messages_are_not_backed_up(): void {
        global $DB;

        [$course, $student, $gradeitem] = $this->course_with_item();
        $this->write_grade($course->id, $gradeitem->id, $student->id, 'FIRST', '40', 40);
        $this->assertTrue($DB->record_exists('local_gugrades_gradesbackup', [
            'gradeitemid' => $gradeitem->id,
            'gradetype' => 'FIRST',
        ]));

        $messages = [
            ['FIRST', get_string('nograde', 'local_gugrades'), false, false],
            ['SECOND', get_string('gradesmissing', 'local_gugrades'), true, false],
            ['THIRD', get_string('cannotaggregate', 'local_gugrades'), true, false],
            ['RESIT', get_string('notavailable', 'local_gugrades'), false, true],
        ];
        foreach ($messages as [$gradetype, $display, $iserror, $notavailable]) {
            grades::write_grade(
                courseid: $course->id,
                gradeitemid: $gradeitem->id,
                userid: $student->id,
                admingrade: '',
                rawgrade: null,
                convertedgrade: null,
                displaygrade: $display,
                weightedgrade: 0,
                gradetype: $gradetype,
                other: '',
                iscurrent: true,
                iserror: $iserror,
                auditcomment: '',
                ispoints: true,
                overwrite: false,
                catoverride: false,
                notavailable: $notavailable,
            );
        }

        $this->write_grade(
            $course->id,
            $gradeitem->id,
            $student->id,
            'FOURTH',
            get_string('nograde', 'local_gugrades'),
            -1,
        );

        $this->assertSame(0, $DB->count_records('local_gugrades_gradesbackup', ['gradeitemid' => $gradeitem->id]));

        $this->write_grade($course->id, $gradeitem->id, $student->id, 'FIRST', '0', 0);
        $zero = $DB->get_record('local_gugrades_gradesbackup', [
            'gradeitemid' => $gradeitem->id,
            'gradetype' => 'FIRST',
        ], '*', MUST_EXIST);
        $this->assertEquals(0.0, (float) $zero->rawgrade);
    }
}
