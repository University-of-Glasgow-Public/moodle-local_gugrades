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
 * Tests for the MyGrades cleanup scheduled task.
 *
 * @package    local_gugrades
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\task;

use core_external\external_api;
use local_gugrades\external\get_aggregation_page;
use local_gugrades\external\get_capture_page;
use local_gugrades\external\get_levelonecategories;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/gugrades/tests/external/gugrades_advanced_testcase.php');

/**
 * Cleanup task tests
 *
 * @covers \local_gugrades\task\cleanup
 */
final class cleanup_test extends \local_gugrades\external\gugrades_advanced_testcase {
    /**
     * @var int
     */
    protected int $columnid;

    /**
     * Called before every test
     */
    protected function setUp(): void {
        parent::setUp();

        global $DB;
        $this->columnid = $DB->insert_record('local_gugrades_column', (object) [
            'courseid' => 1,
            'gradeitemid' => 1,
            'gradetype' => 'CATEGORY',
            'other' => '',
            'points' => 1,
        ]);
    }

    /**
     * Insert a grade row with optional overrides.
     *
     * @param array $overrides
     * @return int
     */
    protected function insert_grade(array $overrides): int {
        global $DB;

        $record = (object) array_merge([
            'courseid' => 1,
            'gradeitemid' => 1,
            'userid' => 2,
            'points' => 1,
            'rawgrade' => null,
            'convertedgrade' => null,
            'admingrade' => '',
            'displaygrade' => '',
            'weightedgrade' => 0,
            'gradetype' => 'CATEGORY',
            'columnid' => $this->columnid,
            'iscurrent' => 0,
            'iserror' => 0,
            'isprovisional' => 0,
            'notavailable' => 0,
            'auditby' => 2,
            'audittimecreated' => time() - (200 * 86400),
            'auditcomment' => '',
            'dropped' => 0,
            'catoverride' => 0,
        ], $overrides);

        return $DB->insert_record('local_gugrades_grade', $record);
    }

    /**
     * Run the scheduled cleanup task, discarding mtrace output.
     */
    protected function run_cleanup(): void {
        ob_start();
        (new cleanup())->execute();
        ob_end_clean();
    }

    /**
     * Load the aggregation page as MyGrades does.
     *
     * @param int $gradecategoryid
     * @param bool $aggregate
     * @return array
     */
    protected function load_aggregation_page(int $gradecategoryid, bool $aggregate): array {
        $page = get_aggregation_page::execute($this->course->id, $gradecategoryid, '', '', 0, $aggregate);
        return external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );
    }

    /**
     * Index aggregation users by userid.
     *
     * @param array $users
     * @return array
     */
    protected function users_by_id(array $users): array {
        $indexed = [];
        foreach ($users as $user) {
            $indexed[$user['id']] = $user;
        }
        return $indexed;
    }

    /**
     * Age empty CATEGORY rows so the 6-month cleanup will select them.
     *
     * @param int $courseid
     */
    protected function age_empty_category_grades(int $courseid): void {
        global $DB;

        $old = time() - (200 * 86400);
        $DB->set_field_select(
            'local_gugrades_grade',
            'audittimecreated',
            $old,
            "courseid = :courseid
                AND gradetype = :gradetype
                AND rawgrade IS NULL
                AND convertedgrade IS NULL
                AND catoverride = 0
                AND (admingrade IS NULL OR admingrade = :emptyadmin)",
            [
                'courseid' => $courseid,
                'gradetype' => 'CATEGORY',
                'emptyadmin' => '',
            ]
        );
    }

    /**
     * The task must only delete unused empty CATEGORY placeholders.
     */
    public function test_cleanup_only_removes_unused_category_grades(): void {
        global $DB;

        $old = time() - (200 * 86400);
        $recent = time() - (10 * 86400);

        $unusedid = $this->insert_grade(['audittimecreated' => $old]);
        $currentemptyid = $this->insert_grade(['iscurrent' => 1, 'audittimecreated' => $old]);
        $withrawid = $this->insert_grade(['rawgrade' => 15.0, 'audittimecreated' => $old]);
        $convertedid = $this->insert_grade(['convertedgrade' => 12.0, 'audittimecreated' => $old]);
        $adminid = $this->insert_grade(['admingrade' => 'MV', 'audittimecreated' => $old]);
        $overrideid = $this->insert_grade(['catoverride' => 1, 'audittimecreated' => $old]);
        $firstid = $this->insert_grade(['gradetype' => 'FIRST', 'audittimecreated' => $old]);
        $recentid = $this->insert_grade(['audittimecreated' => $recent]);

        $this->run_cleanup();

        $this->assertFalse($DB->record_exists('local_gugrades_grade', ['id' => $unusedid]));
        $this->assertFalse($DB->record_exists('local_gugrades_grade', ['id' => $currentemptyid]));
        $this->assertTrue($DB->record_exists('local_gugrades_grade', ['id' => $withrawid]));
        $this->assertTrue($DB->record_exists('local_gugrades_grade', ['id' => $convertedid]));
        $this->assertTrue($DB->record_exists('local_gugrades_grade', ['id' => $adminid]));
        $this->assertTrue($DB->record_exists('local_gugrades_grade', ['id' => $overrideid]));
        $this->assertTrue($DB->record_exists('local_gugrades_grade', ['id' => $firstid]));
        $this->assertTrue($DB->record_exists('local_gugrades_grade', ['id' => $recentid]));
    }

    /**
     * After unused CATEGORY data is removed, opening the course in MyGrades
     * must still work (categories, aggregation, nested category, capture).
     *
     * @covers \local_gugrades\external\get_aggregation_page::execute
     * @covers \local_gugrades\external\get_capture_page::execute
     * @covers \local_gugrades\external\get_levelonecategories::execute
     */
    public function test_mygrades_still_loads_after_cleanup(): void {
        global $DB;

        $this->setUser($this->teacher);

        $userlist = [
            $this->student->id,
            $this->student2->id,
        ];
        $this->import_grades($this->course->id, $this->gradeitemidassign1, $userlist);
        $this->import_grades($this->course->id, $this->gradeitemidassign2, $userlist);

        $before = $this->load_aggregation_page($this->gradecatsumm->id, true);
        $this->assertNotEmpty($before['users']);
        $beforeusers = $this->users_by_id($before['users']);
        $this->assertArrayHasKey($this->student->id, $beforeusers);
        $this->assertArrayHasKey($this->student2->id, $beforeusers);

        $this->age_empty_category_grades($this->course->id);

        [$select, $params] = cleanup::unused_category_select();
        $todelete = $DB->count_records_select('local_gugrades_grade', $select, $params);
        $this->assertGreaterThan(0, $todelete, 'Expected unused empty CATEGORY rows for cleanup to remove.');

        $this->run_cleanup();

        $this->assertSame(
            0,
            $DB->count_records_select('local_gugrades_grade', $select, $params),
            'Cleanup should have removed the unused empty CATEGORY rows.'
        );

        $categories = get_levelonecategories::execute($this->course->id);
        $categories = external_api::clean_returnvalue(
            get_levelonecategories::execute_returns(),
            $categories
        );
        $this->assertNotEmpty($categories['categories']);
        $this->assertEquals('Summative', $categories['categories'][0]['fullname']);

        // View without forcing a full recalculate — this is the normal "go back in" path.
        $after = $this->load_aggregation_page($this->gradecatsumm->id, false);
        $this->assertCount(count($before['users']), $after['users']);
        $afterusers = $this->users_by_id($after['users']);

        foreach ($beforeusers as $userid => $user) {
            $this->assertArrayHasKey($userid, $afterusers);
            $this->assertEquals($user['displayname'], $afterusers[$userid]['displayname']);
            $this->assertEquals($user['displaygrade'], $afterusers[$userid]['displaygrade']);
        }

        $secondlevel = $this->load_aggregation_page($this->gradecatsecond->id, false);
        $this->assertCount(count($before['users']), $secondlevel['users']);

        $capture = get_capture_page::execute($this->course->id, $this->gradeitemidassign2, '', '', 0, false);
        $capture = external_api::clean_returnvalue(
            get_capture_page::execute_returns(),
            $capture
        );
        $this->assertTrue($capture['gradesimported']);
        $this->assertCount(2, $capture['users']);
        $this->assertEquals('assign', $capture['itemtype']);
        $this->assertEquals('Assignment 2', $capture['itemname']);
    }
}
