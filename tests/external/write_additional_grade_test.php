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
 * Test write additional grade web service
 * @package    local_gugrades
 * @copyright  2024
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
 * Test has_capability web service.
 */
final class write_additional_grade_test extends \local_gugrades\external\gugrades_advanced_testcase {
    /**
     * Add some grades and check capture page data is correct
     *
     * @covers \local_gugrades\external\write_additional_grade::execute
     */
    public function test_new_column(): void {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Import grades.
        $userlist = [
            $this->student->id,
            $this->student2->id,
        ];

        // Assign2 (which is using scale).
        $this->import_grades($this->course->id, $this->gradeitemidassign2, $userlist);

        // Add additional grade.
        $nothing = write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign2,
            $this->student->id,
            'SECOND',
            '',
            '',
            18,
            0,
            'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        $audit = $DB->get_record('local_gugrades_audit', [
            'courseid' => $this->course->id,
            'relateduserid' => $this->student->id,
            'gradeitemid' => $this->gradeitemidassign2,
        ], '*', MUST_EXIST);
        $this->assertStringContainsString(get_string('gradetypesecond', 'local_gugrades'), $audit->message);

        // Get capture page.
        $page = get_capture_page::execute($this->course->id, $this->gradeitemidassign2, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        // Check data for user 0 (Fred Bloggs).
        $fred = $page['users'][0];
        $this->assertEquals(true, $fred['alert']);

        $grades = $fred['grades'];
        $this->assertCount(3, $grades);
        $this->assertEquals('A5', $grades[1]['displaygrade']);
        $this->assertEquals('SECOND', $grades[1]['gradetype']);
        $this->assertEquals('A5', $grades[2]['displaygrade']);
        $this->assertEquals('PROVISIONAL', $grades[2]['gradetype']);

        // Add agreed grade and make sure alert (discrepancy) clears.
        $nothing = write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign2,
            $this->student->id,
            'AGREED',
            '',
            '',
            19,
            0,
            'Agreed grade'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get capture page.
        $page = get_capture_page::execute($this->course->id, $this->gradeitemidassign2, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals(false, $fred['alert']);
        $grades = $fred['grades'];
        $this->assertEquals('A4', $grades[3]['displaygrade']);
        $this->assertEquals('PROVISIONAL', $grades[3]['gradetype']);

        // Change agreed grade to 'No grade'.
        $nothing = write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign2,
            $this->student->id,
            'AGREED',
            '',
            '',
            -1,
            0,
            'Agreed grade is removed'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get capture page.
        $page = get_capture_page::execute($this->course->id, $this->gradeitemidassign2, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $grades = $fred['grades'];
        $this->assertEquals('No grade', $grades[2]['displaygrade']);
        $this->assertEquals('No grade', $grades[3]['displaygrade']);
    }

    /**
     * Audit messages describe both new and existing Other grade reasons.
     * @covers \local_gugrades\external\write_additional_grade::execute
     */
    public function test_audit_other_reason(): void {
        global $DB;

        $this->setUser($this->teacher);
        $this->import_grades($this->course->id, $this->gradeitemidassign2, [$this->student->id]);

        write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign2,
            $this->student->id,
            'OTHER',
            'Panel review',
            '',
            18,
            0,
            'Notes'
        );
        $column = $DB->get_record('local_gugrades_column', [
            'gradeitemid' => $this->gradeitemidassign2,
            'gradetype' => 'OTHER',
        ], '*', MUST_EXIST);
        $this->assertSame('Panel review', $column->other);
        write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign2,
            $this->student->id,
            'OTHER_' . $column->id,
            '',
            '',
            19,
            0,
            'Notes'
        );
        $audits = array_slice(array_values($DB->get_records('local_gugrades_audit', [
            'courseid' => $this->course->id,
            'relateduserid' => $this->student->id,
            'gradeitemid' => $this->gradeitemidassign2,
        ], 'id ASC')), -2);
        $this->assertCount(2, $audits);
        $this->assertStringContainsString(get_string('gradetypeother', 'local_gugrades') . ' (Panel review)', $audits[0]->message);
        $this->assertStringContainsString('(Panel review)', $audits[1]->message);
        $this->assertStringNotContainsString('OTHER_', $audits[1]->message);
    }

    /**
     * Audit messages use the configured administrative grade label, not its internal name.
     * @covers \local_gugrades\external\write_additional_grade::execute
     */
    public function test_audit_administrative_grade(): void {
        global $DB;

        $this->setUser($this->teacher);
        $this->import_grades($this->course->id, $this->gradeitemidassign2, [$this->student->id]);
        set_config('admingrade_nosubmission', json_encode([
            'code' => 'NS',
            'description' => 'Custom no submission description',
        ]), 'local_gugrades');

        write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign2,
            $this->student->id,
            'SECOND',
            '',
            'NOSUBMISSION',
            0,
            0,
            'Notes'
        );

        $audits = $DB->get_records('local_gugrades_audit', [
            'courseid' => $this->course->id,
            'relateduserid' => $this->student->id,
            'gradeitemid' => $this->gradeitemidassign2,
        ], 'id DESC', '*', 0, 1);
        $audit = reset($audits);
        $this->assertStringContainsString('NS - Custom no submission description', $audit->message);
        $this->assertStringContainsString(get_string('gradetypesecond', 'local_gugrades'), $audit->message);
        $this->assertStringNotContainsString('NOSUBMISSION', $audit->message);
        $this->assertTrue($DB->record_exists('local_gugrades_grade', [
            'gradeitemid' => $this->gradeitemidassign2,
            'userid' => $this->student->id,
            'admingrade' => 'NOSUBMISSION',
            'iscurrent' => 1,
        ]));
    }

    /**
     * Add the same grade and make sure alert is off
     *
     * @covers \local_gugrades\external\write_additional_grade::execute
     */
    public function test_alert_is_off(): void {

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Import grades.
        $userlist = [
            $this->student->id,
        ];

        // Assign2 (which is using scale).
        $this->import_grades($this->course->id, $this->gradeitemidassign2, $userlist);

        // Add additional grade.
        $nothing = write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign2,
            $this->student->id,
            'SECOND',
            '',
            '',
            20,
            0,
            'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get capture page.
        $page = get_capture_page::execute($this->course->id, $this->gradeitemidassign2, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        // Check data for user 0 (Fred Bloggs).
        $fred = $page['users'][0];
        $this->assertEquals(false, $fred['alert']);
    }

    /**
     * Points grades outside grademin/grademax must be rejected.
     * Previously these could be written via bulk edit and then broke capture (MGU-1344).
     *
     * @covers \local_gugrades\external\write_additional_grade::execute
     */
    public function test_points_grade_out_of_range_rejected(): void {

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Import points grades (assign1 defaults to max 100).
        $userlist = [
            $this->student->id,
        ];
        $this->import_grades($this->course->id, $this->gradeitemidassign1, $userlist);

        $this->expectException(\moodle_exception::class);

        write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign1,
            $this->student->id,
            'SECOND',
            '',
            '',
            0,
            999,
            'Out of range grade'
        );
    }

    /**
     * Valid points grades within range are still accepted.
     *
     * @covers \local_gugrades\external\write_additional_grade::execute
     */
    public function test_points_grade_in_range_accepted(): void {

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        $userlist = [
            $this->student->id,
        ];
        $this->import_grades($this->course->id, $this->gradeitemidassign1, $userlist);

        $nothing = write_additional_grade::execute(
            $this->course->id,
            $this->gradeitemidassign1,
            $this->student->id,
            'SECOND',
            '',
            '',
            0,
            88.5,
            'In range grade'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        $page = get_capture_page::execute($this->course->id, $this->gradeitemidassign1, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $grades = $fred['grades'];
        $second = null;
        foreach ($grades as $grade) {
            if ($grade['gradetype'] === 'SECOND') {
                $second = $grade;
                break;
            }
        }
        $this->assertNotNull($second);
        $this->assertEquals('88.5', (string)$second['displaygrade']);
    }
}
