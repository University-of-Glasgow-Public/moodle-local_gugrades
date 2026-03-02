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
 * Test MGU-1424
 * Category grade conversion not reliable for decimal grades
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
final class mgu_1424_test extends \local_gugrades\external\gugrades_aggregation_testcase {
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
        $this->gradeitemids = $this->load_schema('schema13');

        // Get the grade category 'summative'.
        $this->gradecatsummative = $DB->get_record('grade_categories', ['fullname' => 'Summative'], '*', MUST_EXIST);

        // Create conversion map (stolen from Maths department).
         $jsonmap = '{
            "name": "MathsMap",
            "schedule": "schedulea",
            "maxgrade": 100,
            "inuse": true,
            "map": [
                {
                    "band": "H",
                    "bound": 0,
                    "grade": 0
                },
                {
                    "band": "G2",
                    "bound": 1,
                    "grade": 1
                },
                {
                    "band": "G1",
                    "bound": 10,
                    "grade": 2
                },
                {
                    "band": "F3",
                    "bound": 20,
                    "grade": 3
                },
                {
                    "band": "F2",
                    "bound": 23,
                    "grade": 4
                },
                {
                    "band": "F1",
                    "bound": 26,
                    "grade": 5
                },
                {
                    "band": "E3",
                    "bound": 30,
                    "grade": 6
                },
                {
                    "band": "E2",
                    "bound": 33,
                    "grade": 7
                },
                {
                    "band": "E1",
                    "bound": 36,
                    "grade": 8
                },
                {
                    "band": "D3",
                    "bound": 40,
                    "grade": 9
                },
                {
                    "band": "D2",
                    "bound": 43,
                    "grade": 10
                },
                {
                    "band": "D1",
                    "bound": 46,
                    "grade": 11
                },
                {
                    "band": "C3",
                    "bound": 50,
                    "grade": 12
                },
                {
                    "band": "C2",
                    "bound": 53,
                    "grade": 13
                },
                {
                    "band": "C1",
                    "bound": 56,
                    "grade": 14
                },
                {
                    "band": "B3",
                    "bound": 60,
                    "grade": 15
                },
                {
                    "band": "B2",
                    "bound": 63,
                    "grade": 16
                },
                {
                    "band": "B1",
                    "bound": 66,
                    "grade": 17
                },
                {
                    "band": "A5",
                    "bound": 70,
                    "grade": 18
                },
                {
                    "band": "A4",
                    "bound": 75,
                    "grade": 19
                },
                {
                    "band": "A3",
                    "bound": 80,
                    "grade": 20
                },
                {
                    "band": "A2",
                    "bound": 85,
                    "grade": 21
                },
                {
                    "band": "A1",
                    "bound": 90,
                    "grade": 22
                }
            ]
        }';

        $mapid = import_conversion_map::execute($this->course->id, $jsonmap);
        $mapid = external_api::clean_returnvalue(
            import_conversion_map::execute_returns(),
            $mapid
        );
        $this->mapid = $mapid['mapid'];
    }

    /**
     * Test conversion with non percent maxgrades.
     *
     * @covers \local_gugrades\external\get_aggregation_page::execute
     * @return void
     */
    public function test_resit_without_percentage(): void {
        global $DB;

        // Make sure that we're a teacher.
        $this->setUser($this->teacher);

        // Test get_activities web service.
        $treejson = get_activities::execute($this->course->id, $this->gradecatsummative->id, true);
        $treejson = external_api::clean_returnvalue(
            get_activities::execute_returns(),
            $treejson
        );
        $tree = json_decode($treejson['activities']);

        // There should be resit candidates.
        $this->assertTrue($tree->anyresitcandidates);

        // Start by examining 'Summer exam', which has two simple grade items.
        $summerexam = $tree->categories[0];
        $summerexamcat = $summerexam->category;

        // Should be a resit candidate but no resits.
        $this->assertTrue($summerexamcat->resitcandidate);
        $this->assertFalse($summerexamcat->resititemid);

        // Set the resit item.
        $resit = $summerexam->items[1];
        $this->assertEquals('Resit', $resit->itemname);
        $resitid = $resit->id;

        $result = save_resit_item::execute($this->course->id, $resitid, true);
        $result = external_api::clean_returnvalue(
            save_resit_item::execute_returns(),
            $result
        );

        // Write grade 12 for Exam.
        $examid = $this->get_gradeitemid('Exam');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $examid,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     '',
            scale:          0,
            grade:          12,
            notes:          'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get the grade category 'Summer exam'.
        $gradecatsummer = $DB->get_record('grade_categories', ['fullname' => 'Summer exam'], '*', MUST_EXIST);

        // Get aggregation page for above resit
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals(20, $fred['displaygrade']);

        // Select conversion.
        $nothing = select_conversion::execute($this->course->id, 0, $gradecatsummer->id, $this->mapid);
        $nothing = external_api::clean_returnvalue(
            select_conversion::execute_returns(),
            $nothing
        );

        // Get converted aggregation page for above resit.
        $page = get_aggregation_page::execute($this->course->id, $gradecatsummer->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        // G1 returned here (which is wrong).
        $fred = $page['users'][0];
        $this->assertEquals('F3 (4.40000)', $fred['displaygrade']);


        // Write grade 12 for ItemA.
        $itemaid = $this->get_gradeitemid('ItemA');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $itemaid,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     '',
            scale:          0,
            grade:          12,
            notes:          'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Write grade 12 for ItemB.
        $itembid = $this->get_gradeitemid('ItemB');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $itembid,
            userid:         $this->student->id,
            reason:         'SECOND',
            other:          '',
            admingrade:     '',
            scale:          0,
            grade:          12,
            notes:          'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get the grade category 'Aggregated category'.
        $gradecatagg = $DB->get_record('grade_categories', ['fullname' => 'Aggregated category'], '*', MUST_EXIST);

        // Get aggregation page for above category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatagg->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals(20, $fred['displaygrade']);

        // Select conversion.
        $nothing = select_conversion::execute($this->course->id, 0, $gradecatagg->id, $this->mapid);
        $nothing = external_api::clean_returnvalue(
            select_conversion::execute_returns(),
            $nothing
        );

        // Get aggregation page for above category.
        $page = get_aggregation_page::execute($this->course->id, $gradecatagg->id, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('F3 (4.40000)', $fred['displaygrade']);

    }

}
