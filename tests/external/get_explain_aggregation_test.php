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
 * Test functions around get_explain_aggregation API.
 * @package    local_gugrades
 * @copyright  2025
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
 * More test(s) for get_aggregation_page webservice
 */
final class get_explain_aggregation_test extends \local_gugrades\external\gugrades_aggregation_testcase {
    /**
     * Regulation dates covered by numeric explanation tests.
     * @return array
     */
    public static function calculation_regulations(): array {
        return ['original' => ['2025-01-01'], 'from2026' => ['2027-01-01']];
    }

    /**
     * Explain exposes the numeric working without changing ordinary aggregation results.
     *
     * @dataProvider calculation_regulations
     * @param string $startdate
     * @covers \local_gugrades\api::get_explain_aggregation
     */
    public function test_weighted_calculation_working(string $startdate): void {
        global $DB;

        $this->setUser($this->teacher);
        $DB->set_field('course', 'startdate', strtotime($startdate), ['id' => $this->course->id]);
        $root = \grade_category::fetch_course_category($this->course->id);
        $this->build_schema([(object) [
            'name' => 'Calculation parent',
            'category' => true,
            'children' => [],
        ]], $root->id);
        $this->build_schema([(object) [
            'name' => 'Calculation total',
            'category' => true,
            'children' => [
                (object) ['name' => 'Calculation one', 'category' => false, 'type' => 'schedulea', 'weight' => 0.6],
                (object) ['name' => 'Calculation two', 'category' => false, 'type' => 'schedulea', 'weight' => 0.4],
            ],
        ]], $this->get_grade_category('Calculation parent'));
        foreach (['Calculation one' => 20, 'Calculation two' => 4] as $name => $grade) {
            \local_gugrades\grades::write_grade(
                courseid: $this->course->id,
                gradeitemid: $this->get_gradeitemid($name),
                userid: $this->student->id,
                admingrade: '',
                rawgrade: $grade,
                convertedgrade: $grade,
                displaygrade: (string) $grade,
                weightedgrade: 0,
                gradetype: 'FIRST',
                other: '',
                iscurrent: true,
                iserror: false,
                auditcomment: '',
                ispoints: false,
            );
        }
        $categoryid = $this->get_grade_category('Calculation total');
        $result = get_explain_aggregation::execute($this->course->id, $categoryid, $this->student->id);
        $result = external_api::clean_returnvalue(get_explain_aggregation::execute_returns(), $result);

        $this->assertStringContainsString('20 * 0.6 + 4 * 0.4 = 12 + 1.6 = 13.6', $result['calculation']);
        $this->assertStringContainsString('14', $result['calculation']);
        $this->assertStringContainsString('Calculation one: grade 20 out of 22', $result['calculation']);
        $normal = \local_gugrades\aggregation::aggregate_user_helper($this->course->id, $categoryid, $this->student->id);
        $this->assertFalse(property_exists($normal, 'calculation'));

        $DB->insert_record('local_gugrades_altered_weight', (object) [
            'courseid' => $this->course->id,
            'categoryid' => $categoryid,
            'gradeitemid' => $this->get_gradeitemid('Calculation one'),
            'userid' => $this->student->id,
            'weight' => 0.4,
            'timealtered' => time(),
        ]);
        $altered = get_explain_aggregation::execute($this->course->id, $categoryid, $this->student->id);
        $this->assertStringContainsString('20 * 0.5 + 4 * 0.5 = 10 + 2 = 12', $altered['calculation']);

        $DB->set_field('grade_categories', 'droplow', 1, ['id' => $categoryid]);
        $dropped = get_explain_aggregation::execute($this->course->id, $categoryid, $this->student->id);
        $this->assertStringContainsString('20 * 1 = 20 = 20', $dropped['calculation']);
        $this->assertStringContainsString('Calculation two: ' . get_string('dropped', 'local_gugrades'), $dropped['calculation']);
    }

    /**
     * @var object $gradecatsummative
     */
    protected object $gradecatsummative;

    /**
     * Called before every test
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        // Install test schema.
        $this->gradeitemids = $this->load_schema('schema4');

        // Get the grade category 'summative'.
        $this->gradecatsummative = $DB->get_record('grade_categories', ['fullname' => 'Summative'], '*', MUST_EXIST);
    }

    /**
     * Test basic data for summer exam
     *
     * @covers \local_gugrades\external\get_aggregation_page::execute
     */
    public function test_summer_exam(): void {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Import grades only for one student (so far).
        $userlist = [
            $this->student->id,
        ];

        // Install test data for student.
        $this->load_data('data4a', $this->student->id);

        // Get the grade category 'Summer exam'.
        $gradecatsummer = $DB->get_record('grade_categories', ['fullname' => 'Summer exam'], '*', MUST_EXIST);

        // Set aggregation strategy.
        $this->set_strategy($gradecatsummer->id, \GRADE_AGGREGATE_WEIGHTED_MEAN);

        foreach ($this->gradeitemids as $gradeitemid) {
            $this->import_grades($this->course->id, $gradeitemid, $userlist);
        }

        // Explain current page.
        $explain = get_explain_aggregation::execute($this->course->id, $gradecatsummer->id, $this->student->id);
        $explain = external_api::clean_returnvalue(
            get_explain_aggregation::execute_returns(),
            $explain
        );

        $fields = $explain['fields'];
        $this->assertCount(4, $fields);
        $this->assertEquals('Question 4', $fields[3]['itemname']);

        return;

        // Add and set map.
        $jsonmap = '{
            "name": "Test import map",
            "schedule": "scheduleb",
            "maxgrade": 100,
            "inuse": false,
            "map": [
                {
                    "band": "H",
                    "bound": 0,
                    "grade": 0
                },
                {
                    "band": "G0",
                    "bound": 9,
                    "grade": 2
                },
                {
                    "band": "F0",
                    "bound": 19,
                    "grade": 5
                },
                {
                    "band": "E0",
                    "bound": 29,
                    "grade": 8
                },
                {
                    "band": "D0",
                    "bound": 39,
                    "grade": 11
                },
                {
                    "band": "C0",
                    "bound": 53,
                    "grade": 14
                },
                {
                    "band": "B0",
                    "bound": 59,
                    "grade": 17
                },
                {
                    "band": "A0",
                    "bound": 69,
                    "grade": 22
                }
            ]
        }';

        $mapid = import_conversion_map::execute($this->course->id, $jsonmap);
        $mapid = external_api::clean_returnvalue(
            import_conversion_map::execute_returns(),
            $mapid
        );
        $mapid = $mapid['mapid'];

        // Add conversion map to summer exam category.
        $nothing = select_conversion::execute($this->course->id, 0, $gradecatsummer->id, $mapid);
        $nothing = external_api::clean_returnvalue(
            select_conversion::execute_returns(),
            $nothing
        );

        // Get aggregation page now with conversion.
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $this->assertEquals('B', $page['atype']);
        $this->assertTrue($page['allowconversion']);
        $this->assertEquals('Test import map', $page['conversion']);
        $users = $page['users'];
        $this->assertEquals('A0 (18.75704)', $users[0]['displaygrade']);
        $this->assertEquals(18.75704, $users[0]['rawgrade']);
        $this->assertEquals(22, $users[0]['total']);

        // Now check page at Level 1 (Summative).
        $gradecatsummative = $DB->get_record('grade_categories', ['fullname' => 'Summative'], '*', MUST_EXIST);
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummative->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $this->assertTrue($page['toplevel']);
        $this->assertEquals('A', $page['atype']);
        $users = $page['users'];
        $this->assertEquals('A5 (17.8)', $users[0]['displaygrade']);
        $this->assertEquals(17.8, $users[0]['rawgrade']);
        $this->assertEquals(18, $users[0]['total']);

        // Get corresponding itemid for summerexam.
        $summerexamitem = $DB->get_record(
            'grade_items',
            ['itemtype' => 'category', 'iteminstance' => $gradecatsummer->id],
            '*',
            MUST_EXIST
        );

        // Override category grade for gradecatsummer.
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $summerexamitem->id,
            userid:         $this->student->id,
            reason:         'CATEGORY',
            other:          '',
            admingrade:     '',
            scale:          11, // D0.
            grade:          0,
            notes:          'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get aggregation page now with override.
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertTrue($fred['overridden']);
        $this->assertEquals('D0 (11.00000)', $fred['displaygrade']);
        $this->assertEquals(11, $fred['rawgrade']);

        $grades = $DB->get_records('local_gugrades_grade', ['gradeitemid' => $summerexamitem->id, 'userid' => $this->student->id]);

        // Remove the grade mapping.
        // Removing the mapping should also remove any overridden grades.
        $nothing = select_conversion::execute($this->course->id, 0, $gradecatsummer->id, 0);
        $nothing = external_api::clean_returnvalue(
            select_conversion::execute_returns(),
            $nothing
        );

        $grades = $DB->get_records('local_gugrades_grade', ['gradeitemid' => $summerexamitem->id, 'userid' => $this->student->id]);

        // Get aggregation page with mapping removed.
        // Override should also be cancelled.
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, false);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $this->assertEquals('P', $page['atype']);
        $this->assertEquals('', $page['conversion']);
        $fred = $page['users'][0];
        $this->assertEquals(85.25926, $fred['rawgrade']);
        $this->assertEquals('85.25926', $fred['displaygrade']);
    }
}
