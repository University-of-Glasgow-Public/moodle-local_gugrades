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
 * First-sitting aggregation plumbing tests.
 * @package local_gugrades
 * @copyright 2026
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_gugrades\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/gugrades/tests/external/gugrades_aggregation_testcase.php');

/**
 * Independent category results and their lifecycle.
 */
final class aggregation_first_sitting_test extends gugrades_aggregation_testcase {
    /**
     * @var array $categories
     */
    private array $categories;

    /**
     * Create three category levels and an unaffected sibling.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->setUser($this->teacher);
        $DB->set_field('course', 'startdate', strtotime('2027-01-01'), ['id' => $this->course->id]);
        $root = \grade_category::fetch_course_category($this->course->id);
        $this->build_schema([
            (object)[
                'name' => 'Dual total', 'category' => true,
                'children' => [
                    (object)[
                        'name' => 'Dual middle', 'category' => true,
                        'children' => [
                            (object)[
                                'name' => 'Dual reassessment', 'category' => true, 'aggregation' => 'max',
                                'children' => [
                                    (object)['name' => 'Attempt one', 'category' => false, 'type' => 'schedulea'],
                                    (object)['name' => 'Attempt two', 'category' => false, 'type' => 'schedulea'],
                                ],
                            ],
                        ],
                    ],
                    (object)['name' => 'Unaffected', 'category' => false, 'type' => 'schedulea'],
                ],
            ],
        ], $root->id);
        $this->categories = [];
        foreach (['Dual total', 'Dual middle', 'Dual reassessment'] as $name) {
            $this->categories[$name] = $DB->get_record('grade_categories', [
                'courseid' => $this->course->id, 'fullname' => $name,
            ], '*', MUST_EXIST);
        }
        foreach (['Attempt one' => 16, 'Attempt two' => 18, 'Unaffected' => 14] as $name => $grade) {
            $this->write_result($this->get_gradeitemid($name), $grade);
        }
    }

    /**
     * Write a known grade without introducing import/conversion behaviour.
     * @param int $itemid
     * @param float $grade
     * @param string $gradetype
     * @param bool $override
     */
    private function write_result(int $itemid, float $grade, string $gradetype = 'FIRST', bool $override = false): void {
        \local_gugrades\grades::write_grade(
            courseid: $this->course->id,
            gradeitemid: $itemid,
            userid: $this->student->id,
            admingrade: '',
            rawgrade: $grade,
            convertedgrade: $grade,
            displaygrade: (string)$grade,
            weightedgrade: 0,
            gradetype: $gradetype,
            other: '',
            iscurrent: true,
            iserror: false,
            auditcomment: '',
            ispoints: false,
            overwrite: true,
            catoverride: $override,
        );
    }

    /**
     * Set the reassessment flag.
     * @param bool $enabled
     */
    private function set_reassessment(bool $enabled): void {
        write_flags::execute($this->course->id, [[
            'gradecategoryid' => $this->categories['Dual reassessment']->id,
            'gradeitemid' => 0,
            'engexam' => false,
            'resit' => $enabled,
        ]]);
    }

    /**
     * Recalculate the complete tree.
     */
    private function aggregate(): void {
        \local_gugrades\aggregation::aggregate_user_helper(
            $this->course->id, $this->categories['Dual total']->id, $this->student->id, true,
        );
    }

    /**
     * Fetch an active category result.
     * @param string $name
     * @param string $gradetype
     * @return object
     */
    private function result(string $name, string $gradetype): object {
        global $DB;
        $itemid = \local_gugrades\grades::get_gradeitemid_from_gradecategoryid($this->categories[$name]->id);
        return $DB->get_record('local_gugrades_grade', [
            'gradeitemid' => $itemid, 'userid' => $this->student->id,
            'gradetype' => $gradetype, 'iscurrent' => 1,
        ], '*', MUST_EXIST);
    }

    /**
     * First-sitting selection propagates, and repeat runs overwrite their own rows.
     */
    public function test_results_and_lifecycle(): void {
        global $DB;

        $this->aggregate();
        $this->assertFalse($DB->record_exists('local_gugrades_grade', ['gradetype' => 'CATEGORY1']));
        $this->set_reassessment(true);
        $this->aggregate();
        $ids = [];
        $expected = ['Dual total' => 15, 'Dual middle' => 16, 'Dual reassessment' => 16];
        foreach (array_keys($this->categories) as $name) {
            $ordinary = $this->result($name, 'CATEGORY');
            $first = $this->result($name, 'CATEGORY1');
            $this->assertEquals($expected[$name], $first->rawgrade);
            $this->assertEquals($expected[$name], $first->convertedgrade);
            $ids[$name] = $first->id;
            $latest = \local_gugrades\grades::set_latest_grade(
                $this->course->id, $ordinary->gradeitemid, $this->student->id,
            );
            $this->assertEquals($ordinary->id, $latest->id);
        }
        $this->aggregate();
        foreach ($ids as $name => $id) {
            $this->assertEquals($id, $this->result($name, 'CATEGORY1')->id);
        }
        $this->assertEquals(3, $DB->count_records('local_gugrades_grade', [
            'userid' => $this->student->id, 'gradetype' => 'CATEGORY1', 'iscurrent' => 1,
        ]));
        $this->set_reassessment(false);
        $this->aggregate();
        $this->assertFalse($DB->record_exists('local_gugrades_grade', ['gradetype' => 'CATEGORY1', 'iscurrent' => 1]));
    }

    /**
     * An override affects only the ordinary branch, including at higher ancestors.
     */
    public function test_override_does_not_replace_first_sitting(): void {
        global $DB;

        $this->set_reassessment(true);
        $itemid = \local_gugrades\grades::get_gradeitemid_from_gradecategoryid(
            $this->categories['Dual reassessment']->id,
        );
        $this->write_result($itemid, 10, 'CATEGORY', true);
        $this->aggregate();
        $this->assertEquals(10, $this->result('Dual reassessment', 'CATEGORY')->convertedgrade);
        $this->assertEquals(16, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        $this->assertEquals(10, $this->result('Dual middle', 'CATEGORY')->convertedgrade);
        $this->assertEquals(16, $this->result('Dual middle', 'CATEGORY1')->convertedgrade);
        $this->assertEquals(12, $this->result('Dual total', 'CATEGORY')->convertedgrade);
        $this->assertEquals(15, $this->result('Dual total', 'CATEGORY1')->convertedgrade);
        $this->set_reassessment(false);
        $this->aggregate();
        $this->assertFalse($DB->record_exists('local_gugrades_grade', ['gradetype' => 'CATEGORY1', 'iscurrent' => 1]));
    }

    /**
     * Even a newer CATEGORY1 cannot replace the provisional or latest result.
     */
    public function test_first_sitting_is_not_provisional(): void {
        global $DB;

        $this->set_reassessment(true);
        $this->aggregate();
        $ordinary = $this->result('Dual total', 'CATEGORY');
        $DB->delete_records('local_gugrades_grade', [
            'gradeitemid' => $ordinary->gradeitemid, 'gradetype' => 'CATEGORY1',
        ]);
        $this->write_result($ordinary->gradeitemid, 5, 'CATEGORY1');
        $latest = $DB->get_record('local_gugrades_latest', [
            'gradeitemid' => $ordinary->gradeitemid, 'userid' => $this->student->id,
        ], '*', MUST_EXIST);
        $this->assertEquals($ordinary->id, $latest->gugradeid);
        \local_gugrades\grades::missing_latest($this->course->id);
        $latest = \local_gugrades\grades::set_latest_grade(
            $this->course->id, $ordinary->gradeitemid, $this->student->id,
        );
        $this->assertEquals($ordinary->id, $latest->id);
        $capture = \local_gugrades\usercapture::create(
            $this->course->id, $ordinary->gradeitemid, $this->student->id,
        );
        $this->assertEquals($ordinary->convertedgrade, $capture->get_provisional()->convertedgrade);
        $this->assertArrayNotHasKey('CATEGORY1', $capture->get_gradesbygradetype());
        foreach (\local_gugrades\api::get_history($ordinary->gradeitemid, $this->student->id) as $grade) {
            $this->assertNotEquals('CATEGORY1', $grade->gradetype);
        }
    }

    /**
     * Original regulations never enable the second path even when a flag exists.
     */
    public function test_original_regulations_remain_single_pass(): void {
        global $DB;

        $this->set_reassessment(true);
        $DB->set_field('course', 'startdate', strtotime('2025-01-01'), ['id' => $this->course->id]);
        $this->aggregate();
        $this->assertFalse($DB->record_exists('local_gugrades_grade', [
            'userid' => $this->student->id, 'gradetype' => 'CATEGORY1', 'iscurrent' => 1,
        ]));
        $this->assertEquals(18, $this->result('Dual reassessment', 'CATEGORY')->convertedgrade);
    }

    /**
     * Both results receive category-level points conversion before bubbling up.
     */
    public function test_category_conversion_applies_to_both_results(): void {
        global $DB;

        foreach (['Attempt one' => 60, 'Attempt two' => 80] as $name => $grade) {
            $itemid = $this->get_gradeitemid($name);
            $DB->set_field('grade_items', 'gradetype', GRADE_TYPE_VALUE, ['id' => $itemid]);
            $DB->set_field('grade_items', 'grademax', 100, ['id' => $itemid]);
            $DB->set_field('grade_items', 'scaleid', null, ['id' => $itemid]);
            $this->write_result($itemid, $grade);
            $DB->set_field('local_gugrades_grade', 'points', 1, ['gradeitemid' => $itemid]);
        }
        $mapid = $this->make_conversion_map();
        select_conversion::execute(
            $this->course->id, 0, $this->categories['Dual reassessment']->id, $mapid,
        );
        $this->set_reassessment(true);
        $this->aggregate();
        $ordinary = $this->result('Dual reassessment', 'CATEGORY');
        $first = $this->result('Dual reassessment', 'CATEGORY1');
        $this->assertEquals(13.2, $first->rawgrade);
        $this->assertNotEquals($first->rawgrade, $first->convertedgrade);
        [$display, $converted] = \local_gugrades\conversion::aggregation_conversion(13.2, 22, $mapid);
        $this->assertEquals((float)$converted, (float)$first->convertedgrade);
        $this->assertEquals($display, $first->displaygrade);
        $this->assertNotEquals($ordinary->rawgrade, $first->rawgrade);
        $this->assertEquals($first->convertedgrade, $this->result('Dual middle', 'CATEGORY1')->convertedgrade);
    }

    /**
     * Non-substantive administrative grades cannot replace the first sitting.
     */
    public function test_administrative_grade_propagates(): void {
        $this->set_reassessment(true);
        write_additional_grade::execute(
            courseid: $this->course->id,
            gradeitemid: $this->get_gradeitemid('Attempt two'),
            userid: $this->student->id,
            reason: 'SECOND',
            other: '',
            admingrade: 'GOODCAUSE_FO',
            scale: 0,
            grade: 0,
            notes: '',
        );
        $this->aggregate();
        foreach (['Dual total' => 15, 'Dual middle' => 16, 'Dual reassessment' => 16] as $name => $expected) {
            $first = $this->result($name, 'CATEGORY1');
            $this->assertEquals('', $first->admingrade);
            $this->assertEquals($expected, $first->convertedgrade);
            $this->assertFalse((bool)$first->iserror);
        }
        $this->assertEquals('GOODCAUSE_FO', $this->result('Dual reassessment', 'CATEGORY')->admingrade);
    }

    /**
     * First-sitting errors remain errors at every ancestor.
     */
    public function test_missing_grades_propagate(): void {
        global $DB;

        $this->set_reassessment(true);
        $itemid = \local_gugrades\grades::get_gradeitemid_from_gradecategoryid(
            $this->categories['Dual reassessment']->id,
        );
        $this->write_result($itemid, 10, 'CATEGORY', true);
        foreach (['Attempt one', 'Attempt two'] as $name) {
            $DB->delete_records('local_gugrades_grade', ['gradeitemid' => $this->get_gradeitemid($name)]);
        }
        $this->aggregate();
        foreach (array_keys($this->categories) as $name) {
            $first = $this->result($name, 'CATEGORY1');
            $this->assertTrue((bool)$first->iserror);
            $this->assertNull($first->convertedgrade);
            $this->assertNotEmpty($first->displaygrade);
            $this->assertFalse((bool)$this->result($name, 'CATEGORY')->iserror);
        }
    }

    /**
     * Nursing conditions below an override affect only the independent result.
     */
    public function test_nursing_conditions_are_independent(): void {
        global $DB;

        $category = $this->getDataGenerator()->create_category(['name' => 'Nursing Undergraduate']);
        set_config('nursingcat', $category->id, 'local_gugrades');
        $DB->set_field('course', 'category', $category->id, ['id' => $this->course->id]);
        $this->write_result($this->get_gradeitemid('Attempt one'), 4);
        $this->write_result($this->get_gradeitemid('Attempt two'), 2);
        $this->set_reassessment(true);
        $itemid = \local_gugrades\grades::get_gradeitemid_from_gradecategoryid(
            $this->categories['Dual reassessment']->id,
        );
        $this->write_result($itemid, 12, 'CATEGORY', true);
        $this->aggregate();
        $this->assertEquals('C2 (13)', $this->result('Dual total', 'CATEGORY')->displaygrade);
        $this->assertEquals('XD3 (9)', $this->result('Dual total', 'CATEGORY1')->displaygrade);
    }

    /**
     * Revision IDs do not reorder the first assignment.
     */
    public function test_current_revision_of_first_assignment_is_selected(): void {
        $this->set_reassessment(true);
        write_additional_grade::execute(
            courseid: $this->course->id,
            gradeitemid: $this->get_gradeitemid('Attempt one'),
            userid: $this->student->id,
            reason: 'SECOND', other: '', admingrade: '', scale: 13, grade: 0, notes: '',
        );
        $this->aggregate();
        $this->assertEquals(13, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        $this->assertEquals(18, $this->result('Dual reassessment', 'CATEGORY')->convertedgrade);
        $this->assertEquals(14, $this->result('Dual total', 'CATEGORY1')->convertedgrade);
    }

    /**
     * Early EC is ignored until its current revision becomes substantive.
     */
    public function test_early_ec_becomes_eligible_after_revision(): void {
        global $DB;

        $itemid = $this->get_gradeitemid('Attempt one');
        $DB->set_field('local_gugrades_grade', 'admingrade', 'GOODCAUSE_FO', ['gradeitemid' => $itemid]);
        $this->set_reassessment(true);
        $this->aggregate();
        $this->assertEquals(18, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        write_additional_grade::execute(
            courseid: $this->course->id, gradeitemid: $itemid, userid: $this->student->id,
            reason: 'SECOND', other: '', admingrade: '', scale: 1, grade: 0, notes: '',
        );
        $this->aggregate();
        $this->assertEquals(1, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        $this->write_result($itemid, 0, 'SECOND');
        $this->aggregate();
        $this->assertEquals(0, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        write_additional_grade::execute(
            courseid: $this->course->id, gradeitemid: $itemid, userid: $this->student->id,
            reason: 'SECOND', other: '', admingrade: 'NOSUBMISSION', scale: 0, grade: 0, notes: '',
        );
        $this->aggregate();
        $this->assertEquals('NOSUBMISSION', $this->result('Dual reassessment', 'CATEGORY1')->admingrade);
        $this->assertFalse((bool)$this->result('Dual reassessment', 'CATEGORY1')->iserror);
    }

    /**
     * EC-only categories use EC for both results until a substantive grade exists.
     */
    public function test_ec_only_category_totals(): void {
        global $DB;

        foreach (['Attempt one', 'Attempt two'] as $name) {
            $DB->set_field('local_gugrades_grade', 'admingrade', 'GOODCAUSE_FO', [
                'gradeitemid' => $this->get_gradeitemid($name),
            ]);
        }
        $this->set_reassessment(true);
        $this->aggregate();
        foreach (['CATEGORY1', 'CATEGORY'] as $type) {
            $result = $this->result('Dual reassessment', $type);
            $this->assertFalse((bool)$result->iserror);
            $this->assertEquals('GOODCAUSE_FO', $result->admingrade);
            $this->assertEquals('EC', $result->displaygrade);
            $this->assertEquals('GOODCAUSE_FO', $this->result('Dual middle', $type)->admingrade);
        }
    }

    /**
     * A single EC becomes a substantive first sitting, followed by a higher award.
     */
    public function test_ec_then_substantive_then_third_attempt(): void {
        global $DB;

        $firstid = $this->get_gradeitemid('Attempt one');
        $secondid = $this->get_gradeitemid('Attempt two');
        foreach ([$firstid, $secondid] as $itemid) {
            $DB->delete_records('local_gugrades_grade', ['gradeitemid' => $itemid]);
        }
        write_additional_grade::execute(
            courseid: $this->course->id, gradeitemid: $firstid, userid: $this->student->id,
            reason: 'SECOND', other: '', admingrade: 'GOODCAUSE_FO', scale: 0, grade: 0, notes: '',
        );
        $this->set_reassessment(true);
        $this->aggregate();
        foreach (['CATEGORY1', 'CATEGORY'] as $type) {
            $this->assertEquals('EC', $this->result('Dual reassessment', $type)->displaygrade);
            $this->assertFalse((bool)$this->result('Dual reassessment', $type)->iserror);
        }
        $this->write_result($secondid, 12);
        $this->aggregate();
        foreach (['CATEGORY1', 'CATEGORY'] as $type) {
            $this->assertEquals(12, $this->result('Dual reassessment', $type)->convertedgrade);
        }
        $this->build_schema([
            (object)['name' => 'Attempt three', 'category' => false, 'type' => 'schedulea'],
        ], $this->categories['Dual reassessment']->id);
        $this->write_result($this->get_gradeitemid('Attempt three'), 18);
        $this->aggregate();
        $this->assertEquals(12, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        $this->assertEquals(18, $this->result('Dual reassessment', 'CATEGORY')->convertedgrade);
        $this->assertEquals(13, $this->result('Dual total', 'CATEGORY1')->convertedgrade);
        $this->assertEquals(16, $this->result('Dual total', 'CATEGORY')->convertedgrade);
    }

    /**
     * Historical assignment IDs, not tree order, select the attempt.
     */
    public function test_assignment_order_is_not_tree_order(): void {
        global $DB;

        $DB->delete_records('local_gugrades_grade', [
            'gradeitemid' => $this->get_gradeitemid('Attempt one'),
        ]);
        $this->write_result($this->get_gradeitemid('Attempt one'), 20);
        $this->set_reassessment(true);
        $this->aggregate();
        $this->assertEquals(18, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        $this->assertEquals(20, $this->result('Dual reassessment', 'CATEGORY')->convertedgrade);
    }

    /**
     * Nested attempts use actual assessment chronology, not placeholder category rows.
     */
    public function test_child_category_assignment_order(): void {
        global $DB;

        $categoryid = $this->categories['Dual reassessment']->id;
        $this->build_schema([
            (object)[
                'name' => 'Nested late', 'category' => true,
                'children' => [(object)['name' => 'Nested assessment late', 'category' => false, 'type' => 'schedulea']],
            ],
            (object)[
                'name' => 'Nested early', 'category' => true,
                'children' => [(object)['name' => 'Nested assessment early', 'category' => false, 'type' => 'schedulea']],
            ],
        ], $categoryid);
        foreach (['Attempt one', 'Attempt two'] as $name) {
            $itemid = $this->get_gradeitemid($name);
            $DB->set_field('grade_items', 'gradetype', GRADE_TYPE_NONE, ['id' => $itemid]);
        }
        $this->aggregate();
        $earlyid = $this->get_gradeitemid('Nested assessment early');
        $this->write_result($earlyid, 18);
        $this->write_result($this->get_gradeitemid('Nested assessment late'), 16);
        $this->set_reassessment(true);
        $this->aggregate();
        $this->assertEquals(18, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        $this->write_result($earlyid, 12, 'SECOND');
        $this->aggregate();
        $this->assertEquals(12, $this->result('Dual reassessment', 'CATEGORY1')->convertedgrade);
        $this->assertEquals(16, $this->result('Dual reassessment', 'CATEGORY')->convertedgrade);
    }

    /**
     * Enabling reassessment repairs results without looking up external programme data.
     */
    public function test_page_repairs_first_sitting_without_programme_lookup(): void {
        global $DB;

        set_config('dbtype', '', 'enrol_gudatabase');
        $cache = \cache::make('local_gugrades', 'ugpg');
        $cache->delete($this->student->id);
        \local_gugrades\aggregation::aggregate(
            $this->course->id, $this->categories['Dual total']->id, [$this->student->id],
        );
        $this->assertFalse($DB->record_exists('local_gugrades_grade', ['gradetype' => 'CATEGORY1']));
        $this->set_reassessment(true);
        $DB->delete_records('local_gugrades_grade', [
            'courseid' => $this->course->id, 'gradetype' => 'CATEGORY1',
        ]);
        \local_gugrades\aggregation::invalidate_aggdata(
            $this->course->id, $this->categories['Dual total']->id, $this->student->id,
        );

        $page = get_aggregation_page::execute(
            $this->course->id, $this->categories['Dual total']->id, '', '', 0, false,
        );
        $page = \core_external\external_api::clean_returnvalue(get_aggregation_page::execute_returns(), $page);
        $this->assertTrue($page['dualgrades']);
        foreach (['Dual total' => 15, 'Dual middle' => 16, 'Dual reassessment' => 16] as $name => $expected) {
            $this->assertEquals($expected, $this->result($name, 'CATEGORY1')->rawgrade);
        }
        $this->assertEquals(16, $this->result('Dual total', 'CATEGORY')->rawgrade);
        $this->assertFalse($cache->get($this->student->id));
    }

    /**
     * A missing child result is repaired even when both parent results already exist.
     */
    public function test_page_repairs_only_user_with_missing_child_result(): void {
        global $DB;

        $this->set_reassessment(true);
        \local_gugrades\aggregation::aggregate($this->course->id, $this->categories['Dual total']->id,
            [$this->student->id, $this->student2->id]);
        $otherfirst = $DB->get_record('local_gugrades_grade', [
            'gradeitemid' => \local_gugrades\grades::get_gradeitemid_from_gradecategoryid($this->categories['Dual middle']->id),
            'userid' => $this->student2->id, 'gradetype' => 'CATEGORY1', 'iscurrent' => 1,
        ], '*', MUST_EXIST);
        $missing = $this->result('Dual middle', 'CATEGORY1');
        $DB->delete_records('local_gugrades_grade', ['id' => $missing->id]);
        [$columns] = \local_gugrades\aggregation::get_columns($this->course->id, $this->categories['Dual total']->id);
        $users = \local_gugrades\aggregation::get_users($this->course->id, $this->categories['Dual total']->id, '', '', 0);
        [$users, $debug] = \local_gugrades\aggregation::add_aggregation_fields_to_users(
            $this->course->id, $this->categories['Dual total']->id, $users, $columns);
        $this->assertCount(2, $users);
        $this->assertSame('1 User helper calls count.', $debug[0]['line']);
        $this->assertEquals(16, $this->result('Dual middle', 'CATEGORY1')->convertedgrade);
        $this->assertTrue($DB->record_exists('local_gugrades_grade', ['id' => $otherfirst->id, 'iscurrent' => 1]));
    }

    /**
     * Bulk runs reload altered-weight existence, including zero weights and later removal.
     */
    public function test_bulk_aggregation_reloads_altered_weights(): void {
        global $DB;

        $this->set_reassessment(true);
        $totalid = $this->categories['Dual total']->id;
        $aggregate = fn() => \local_gugrades\aggregation::aggregate($this->course->id, $totalid, [$this->student->id]);
        $aggregate();
        $this->assertEquals(15, $this->result('Dual total', 'CATEGORY1')->rawgrade);
        $middleid = \local_gugrades\grades::get_gradeitemid_from_gradecategoryid($this->categories['Dual middle']->id);
        $weightid = $DB->insert_record('local_gugrades_altered_weight', (object)[
            'courseid' => $this->course->id, 'categoryid' => $totalid, 'gradeitemid' => $middleid,
            'userid' => $this->student->id, 'weight' => 0, 'timealtered' => time(),
        ]);
        $aggregate();
        $this->assertEquals(14, $this->result('Dual total', 'CATEGORY1')->rawgrade);
        $this->assertEquals(14, $this->result('Dual total', 'CATEGORY')->rawgrade);
        $DB->delete_records('local_gugrades_altered_weight', ['id' => $weightid]);
        $aggregate();
        $this->assertEquals(15, $this->result('Dual total', 'CATEGORY1')->rawgrade);
        $this->assertEquals(16, $this->result('Dual total', 'CATEGORY')->rawgrade);
    }

    /**
     * Preloaded invalidation deletes every subtree entry without more database reads.
     */
    public function test_preloaded_cache_invalidation(): void {
        global $DB;

        $categories = \local_gugrades\grades::get_gradecategories_recursive($this->categories['Dual total']->id);
        $cache = \cache::make('local_gugrades', 'useraggdata');
        foreach ($categories as $category) {
            $cache->set('AGGDATA_' . $category->id . '_' . $this->student->id, (object)['cached' => true]);
            $cache->set('AGGDATA_' . $category->id . '_' . $this->student2->id, (object)['cached' => true]);
        }
        $reads = $DB->perf_get_reads();
        \local_gugrades\aggregation::invalidate_aggdata(
            $this->course->id, $this->categories['Dual total']->id, $this->student->id, $categories);
        $this->assertSame($reads, $DB->perf_get_reads());
        foreach ($categories as $category) {
            $this->assertFalse($cache->get('AGGDATA_' . $category->id . '_' . $this->student->id));
            $this->assertNotFalse($cache->get('AGGDATA_' . $category->id . '_' . $this->student2->id));
        }
    }

    /**
     * Bulk capture preserves current-ID selection, released/admin grades and CATEGORY1 exclusion.
     */
    public function test_bulk_capture_matches_individual_reads(): void {
        global $DB;

        $itemid = $this->get_gradeitemid('Attempt one');
        $base = $DB->get_record('local_gugrades_grade', [
            'gradeitemid' => $itemid, 'userid' => $this->student->id, 'iscurrent' => 1,
        ], '*', MUST_EXIST);
        foreach ([
            ['gradetype' => 'SECOND', 'rawgrade' => 0, 'convertedgrade' => 0, 'displaygrade' => 'H:0'],
            ['gradetype' => 'RELEASED', 'admingrade' => 'NOSUBMISSION', 'rawgrade' => 0,
                'convertedgrade' => 0, 'displaygrade' => 'NS'],
            ['gradetype' => 'THIRD', 'iscurrent' => 0, 'rawgrade' => 22],
            ['gradetype' => 'CATEGORY1', 'rawgrade' => 20],
        ] as $values) {
            $grade = clone $base;
            unset($grade->id);
            foreach ($values as $field => $value) {
                $grade->$field = $value;
            }
            $DB->insert_record('local_gugrades_grade', $grade);
            $individual = \local_gugrades\usercapture::create($this->course->id, $itemid, $this->student->id);
            $data = \local_gugrades\usercapture::load_aggregation_data(
                $this->course->id, [$this->student->id, $this->student2->id]);
            $bulk = \local_gugrades\usercapture::create($this->course->id, $itemid, $this->student->id, $data);
            $this->assertEquals($individual->get_provisional(), $bulk->get_provisional());
            $this->assertEquals($individual->get_grades(), $bulk->get_grades());
            $this->assertSame($individual->alert(), $bulk->alert());
            $this->assertNull(\local_gugrades\usercapture::create(
                $this->course->id, $itemid, $this->student2->id, $data)->get_provisional());
        }
        $data = \local_gugrades\usercapture::load_aggregation_data($this->course->id, [$this->student->id]);
        \local_gugrades\usercapture::create($this->course->id, $itemid, $this->student->id, $data);
        $reads = $DB->perf_get_reads();
        \local_gugrades\usercapture::create($this->course->id, $itemid, $this->student->id, $data);
        $this->assertSame($reads, $DB->perf_get_reads());
        $this->write_result($itemid, 10, 'AGREED');
        $fresh = \local_gugrades\usercapture::load_aggregation_data($this->course->id, [$this->student->id]);
        $this->assertEquals(10, \local_gugrades\usercapture::create(
            $this->course->id, $itemid, $this->student->id, $fresh)->get_provisional()->rawgrade);
    }

    /**
     * Bulk assessment reads retain the existing out-of-range validation.
     */
    public function test_bulk_capture_rejects_out_of_range_points(): void {
        global $DB;

        $itemid = $this->get_gradeitemid('Attempt one');
        $DB->set_field('local_gugrades_grade', 'points', 1, ['gradeitemid' => $itemid]);
        $DB->set_field('grade_items', 'grademax', 10, ['id' => $itemid]);
        $data = \local_gugrades\usercapture::load_aggregation_data($this->course->id, [$this->student->id]);
        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Out of range grade detected');
        \local_gugrades\usercapture::create($this->course->id, $itemid, $this->student->id, $data);
    }

    /**
     * Calculation and explanation do not need programme/profile display data.
     */
    public function test_calculation_and_explanation_without_programme_lookup(): void {
        set_config('dbtype', '', 'enrol_gudatabase');
        $cache = \cache::make('local_gugrades', 'ugpg');
        $cache->delete($this->student->id);
        $this->set_reassessment(true);
        $this->aggregate();
        $this->assertFalse($cache->get($this->student->id));

        $explain = \local_gugrades\aggregation::get_explain(
            $this->course->id, $this->categories['Dual total']->id, $this->student->id,
        );
        $this->assertNotEmpty($explain);
        $this->assertFalse($cache->get($this->student->id));
    }

    /**
     * The page and row refresh expose both totals and corresponding child grades.
     */
    public function test_aggregation_display_and_row_refresh(): void {
        $this->set_reassessment(true);
        foreach (array_keys($this->categories) as $name) {
            $page = get_aggregation_page::execute(
                $this->course->id, $this->categories[$name]->id, '', '', 0, false,
            );
            $page = \core_external\external_api::clean_returnvalue(get_aggregation_page::execute_returns(), $page);
            $this->assertTrue($page['dualgrades']);
            $user = array_values(array_filter($page['users'], function ($user) {
                return $user['id'] == $this->student->id;
            }))[0];
            $this->assertEquals($this->result($name, 'CATEGORY1')->displaygrade, $user['displaygrade1st']);
            $this->assertEquals($this->result($name, 'CATEGORY')->displaygrade, $user['displaygrade']);
            foreach ($page['columns'] as $index => $column) {
                $expecteddual = $column['categoryid'] != 0;
                $this->assertEquals($expecteddual, $column['dualgrades']);
                if ($expecteddual) {
                    $this->assertNotEmpty($user['fields'][$index]['display1st']);
                } else {
                    $this->assertSame('', $user['fields'][$index]['display1st']);
                }
            }
        }
        $this->write_result($this->get_gradeitemid('Attempt one'), 12, 'SECOND');
        $row = get_aggregation_user::execute($this->course->id, $this->categories['Dual total']->id, $this->student->id);
        $row = \core_external\external_api::clean_returnvalue(get_aggregation_user::execute_returns(), $row);
        $this->assertEquals($this->result('Dual total', 'CATEGORY1')->displaygrade, $row['displaygrade1st']);
        $this->assertEquals($this->result('Dual middle', 'CATEGORY1')->displaygrade, $row['fields'][0]['display1st']);
        $this->assertNotEquals($row['fields'][0]['display'], $row['fields'][0]['display1st']);
    }

    /**
     * Multiple dual children and a single grade feed separate weighted totals.
     */
    public function test_mixed_children_feed_separate_weighted_totals(): void {
        global $DB;

        $this->build_schema([
            (object)[
                'name' => 'Other reassessment', 'category' => true, 'aggregation' => 'max', 'weight' => 0.5,
                'children' => [
                    (object)['name' => 'Other first', 'category' => false, 'type' => 'schedulea'],
                    (object)['name' => 'Other award', 'category' => false, 'type' => 'schedulea'],
                ],
            ],
        ], $this->categories['Dual total']->id);
        $this->write_result($this->get_gradeitemid('Other first'), 6);
        $this->write_result($this->get_gradeitemid('Other award'), 10);
        $middleid = \local_gugrades\grades::get_gradeitemid_from_gradecategoryid($this->categories['Dual middle']->id);
        foreach ([$middleid, $this->get_gradeitemid('Unaffected')] as $itemid) {
            $DB->set_field('grade_items', 'aggregationcoef', 0.25, ['id' => $itemid]);
        }
        $other = $DB->get_record('grade_categories', ['fullname' => 'Other reassessment'], '*', MUST_EXIST);
        write_flags::execute($this->course->id, [
            ['gradecategoryid' => $other->id, 'gradeitemid' => 0, 'engexam' => false, 'resit' => true],
            ['gradecategoryid' => $this->categories['Dual reassessment']->id,
                'gradeitemid' => 0, 'engexam' => false, 'resit' => true],
        ]);
        $this->aggregate();
        $this->assertEquals(10.5, $this->result('Dual total', 'CATEGORY1')->rawgrade);
        $this->assertEquals(13, $this->result('Dual total', 'CATEGORY')->rawgrade);
    }

    /**
     * Duplicate first-sitting rows receive the same read repair as award rows.
     */
    public function test_first_sitting_duplicate_read_repair(): void {
        global $DB;

        $this->set_reassessment(true);
        $this->aggregate();
        foreach (['Dual total', 'Dual middle'] as $name) {
            $first = $this->result($name, 'CATEGORY1');
            unset($first->id);
            $DB->insert_record('local_gugrades_grade', $first);
        }
        $page = get_aggregation_page::execute(
            $this->course->id, $this->categories['Dual total']->id, '', '', 0, false,
        );
        $this->assertTrue($page['dualgrades']);
        foreach (['Dual total', 'Dual middle'] as $name) {
            $first = $this->result($name, 'CATEGORY1');
            $this->assertEquals(1, $DB->count_records('local_gugrades_grade', [
                'gradeitemid' => $first->gradeitemid, 'userid' => $this->student->id,
                'gradetype' => 'CATEGORY1', 'iscurrent' => 1,
            ]));
        }
    }

    /**
     * First-sitting descriptions are translated without making them editable.
     */
    public function test_first_sitting_description(): void {
        $this->assertSame('Category (1st Sitting)', \local_gugrades\gradetype::get_description('CATEGORY1'));
        $this->assertFalse(\local_gugrades\gradetype::can_gradetype_be_edited('CATEGORY1'));
        $this->assertArrayNotHasKey('CATEGORY1',
            \local_gugrades\gradetype::get_menu($this->get_gradeitemid('Attempt one')));
    }

    /**
     * UPAS exports each dual category's first-sitting result, not its award.
     */
    public function test_upas_first_sitting_export(): void {
        global $DB;

        $this->set_reassessment(true);
        $this->aggregate();
        $export = new \local_gugrades\export\upas();
        foreach (array_keys($this->categories) as $name) {
            $csv = $export->get_form_data($this->course->id, $this->categories[$name]->id, 0, []);
            $rows = array_map('str_getcsv', explode("\n", trim($csv)));
            $row = array_values(array_filter(array_slice($rows, 1), function ($row) {
                return $row[0] == $this->student->idnumber;
            }))[0];
            $this->assertSame(explode(' ', $this->result($name, 'CATEGORY1')->displaygrade)[0], $row[6]);
            $this->assertSame(explode(' ', $this->result($name, 'CATEGORY')->displaygrade)[0], $row[7]);
            $this->assertNotSame($row[6], $row[7]);
        }
        $itemid = $this->result('Dual reassessment', 'CATEGORY')->gradeitemid;
        $this->write_result($itemid, 10, 'CATEGORY', true);
        $this->aggregate();
        $csv = $export->get_form_data($this->course->id, $this->categories['Dual reassessment']->id, 0, []);
        $rows = array_map('str_getcsv', explode("\n", trim($csv)));
        $row = array_values(array_filter(array_slice($rows, 1), function ($row) {
            return $row[0] == $this->student->idnumber;
        }))[0];
        $this->assertSame('B2', $row[6]);
        $this->assertSame('10', $row[7]);

        $first = $this->result('Dual reassessment', 'CATEGORY1');
        foreach ([
            ['admingrade' => 'NOSUBMISSION', 'displaygrade' => 'NS', 'iserror' => 0, 'expected' => 'NS'],
            ['admingrade' => '', 'displaygrade' => 'Grades missing', 'iserror' => 1, 'expected' => ''],
        ] as $case) {
            $first->rawgrade = null;
            $first->convertedgrade = null;
            $first->admingrade = $case['admingrade'];
            $first->displaygrade = $case['displaygrade'];
            $first->iserror = $case['iserror'];
            $DB->update_record('local_gugrades_grade', $first);
            $csv = $export->get_form_data($this->course->id, $this->categories['Dual reassessment']->id, 0, []);
            $rows = array_map('str_getcsv', explode("\n", trim($csv)));
            $row = array_values(array_filter(array_slice($rows, 1), function ($row) {
                return $row[0] == $this->student->idnumber;
            }))[0];
            $this->assertSame($case['expected'], $row[6]);
            $this->assertSame('10', $row[7]);
        }
    }

    /**
     * Single-grade categories remain single in both page and row APIs.
     */
    public function test_single_grade_display(): void {
        $page = get_aggregation_page::execute(
            $this->course->id, $this->categories['Dual total']->id, '', '', 0, true,
        );
        $page = \core_external\external_api::clean_returnvalue(get_aggregation_page::execute_returns(), $page);
        $this->assertFalse($page['dualgrades']);
        foreach ($page['columns'] as $column) {
            $this->assertFalse($column['dualgrades']);
        }
        foreach ($page['users'] as $user) {
            $this->assertSame('', $user['displaygrade1st']);
            $this->assertFalse($user['isadmin1st']);
        }
    }
}
