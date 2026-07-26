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
 * Test functions around aggregation export
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
require_once($CFG->dirroot . '/local/gugrades/tests/external/gugrades_aggregation_testcase.php');

/**
 * Test(s) aggregation export
 *
 */
final class aggregation_export_test extends \local_gugrades\external\gugrades_aggregation_testcase {
    /**
     * Called before every test
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        // Install test schema.
        $this->gradeitemids = $this->load_schema('schema2');
    }

    /**
     * Test get_aggregation_export_plugins
     *
     * @covers \local_gugrades\external\get_aggregation_export_plugins::execute
     */
    public function test_get_aggregation_export_plugins(): void {

        $courseid = $this->course->id;
        $categoryid = $this->get_grade_category('Summative');

        // Get plugins.
        $exportplugins = get_aggregation_export_plugins::execute($courseid, $categoryid);
        $exportplugins = external_api::clean_returnvalue(
            get_aggregation_export_plugins::execute_returns(),
            $exportplugins
        );

        $plugins = $exportplugins['plugins'];
        $filename = $exportplugins['filename'];

        $this->assertEquals('mycampus', $plugins[1]['name']);
        $this->assertEquals('MyCampus export', $plugins[1]['description']);
        $this->assertStringStartsWith('MyGrades_', $filename);
    }

    /**
     * Clean up form array to only have expected keys
     * @param array $form
     * @return array
     */
    protected function clean_form($form) {
        $newform = [];
        foreach ($form as $record) {
            $newform[] = [
                'identifier' => $record['identifier'],
                'selected' => $record['selected'],
            ];
        }

        return $newform;
    }

    /**
     * Test get_aggregation_export_form
     *
     * @covers \local_gugrades\external\get_aggregation_export_plugins::execute
     */
    public function test_get_aggregation_export_form(): void {
        global $DB, $CFG;

        $courseid = $this->course->id;
        $categoryid = $this->get_grade_category('Summative');

        // Install test data for student.
        $userlist = [
            $this->student->id,
        ];
        $this->load_data('data2c', $this->student->id);
        foreach ($this->gradeitemids as $gradeitemid) {
            $this->import_grades($this->course->id, $gradeitemid, $userlist);
        }

        // Get form for 'mycampus' plugin
        // (which doesn't have a form).
        $form = get_aggregation_export_form::execute($courseid, $categoryid, 'mycampus');
        $form = external_api::clean_returnvalue(
            get_aggregation_export_form::execute_returns(),
            $form
        );

        $this->assertFalse($form['hasform']);
        $this->assertCount(0, $form['form']);

        // Same again for 'custom' form plugin
        // (which does).
        $form = get_aggregation_export_form::execute($courseid, $categoryid, 'custom');
        $form = external_api::clean_returnvalue(
            get_aggregation_export_form::execute_returns(),
            $form
        );

        $this->assertTrue($form['hasform']);
        $form = $form['form'];
        $this->assertEquals('studentname', $form[0]['identifier']);
        $this->assertEquals(get_string('studentname', 'local_gugrades'), $form[0]['description']);
        $this->assertEquals('Summative', $form[7]['description']);
        $this->assertEquals('warnings', $form[15]['identifier']);
        $this->assertEquals(get_string('showwarnings', 'local_gugrades'), $form[15]['description']);
        $this->assertEquals('auditcomment', $form[16]['identifier']);
        $this->assertEquals(get_string('showauditcomment', 'local_gugrades'), $form[16]['description']);

        // Set *everything* for initial test.
        foreach ($form as $key => $record) {
            $form[$key]['selected'] = true;
        }

        // Get CSV data.
        $form = $this->clean_form($form);
        $data = get_aggregation_export_data::execute($courseid, $categoryid, 0, 'custom', $form);
        $data = external_api::clean_returnvalue(
            get_aggregation_export_data::execute_returns(),
            $data
        );

        // Get expected data.
        $path = $CFG->dirroot . '/local/gugrades/tests/external/gradedata/aggregation_export.csv';
        $expected = file_get_contents($path);

        // Parse the CSVs to make errors easier to see. 
        $expectedArray = str_getcsv($expected, "\n");
        $actualArray = str_getcsv($data['csv'], "\n");

        $this->assertSame($expectedArray, $actualArray);

        // Check user preferences have been set.
        $preferences = explode(',', get_user_preferences('local_gugrades_customaggregationexportselect_' . $categoryid));
        $this->assertCount(17, $preferences);
        $this->assertEquals('idnumber', $preferences[1]);

        // Get form again, to check saved settings.
        $form = get_aggregation_export_form::execute($courseid, $categoryid, 'custom');
        $form = external_api::clean_returnvalue(
            get_aggregation_export_form::execute_returns(),
            $form
        );

        $this->assertTrue($form['hasform']);
        $form = $form['form'];
        $this->assertTrue($form[0]['selected']);

        // Write admin grade for aggregated category.
        $itemid = $this->get_gradeitemid_for_category('Summative');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $itemid,
            userid:         $this->student->id,
            reason:         'CATEGORY',
            other:          '',
            admingrade:     'GOODCAUSE_NR',
            scale:          0,
            grade:          0,
            notes:          'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Get CSV data.
        $form = $this->clean_form($form);
        $data = get_aggregation_export_data::execute($courseid, $categoryid, 0, 'custom', $form);
        $data = external_api::clean_returnvalue(
            get_aggregation_export_data::execute_returns(),
            $data
        );

        // Get expected data.
        $path = $CFG->dirroot . '/local/gugrades/tests/external/gradedata/aggregation_export_admin.csv';
        $expected = file_get_contents($path);

        $this->assertEquals($expected, $data['csv']);
    }

    /**
     * Tests for mycampus plugin
     *
     * @covers \local_gugrades\external\get_aggregation_export_plugins::execute
     */
    public function test_mycampus_export(): void {

        $courseid = $this->course->id;
        $categoryid = $this->get_grade_category('Summative');

        // Install test data for student.
        $userlist = [
            $this->student->id,
        ];
        $this->load_data('data2c', $this->student->id);
        foreach ($this->gradeitemids as $gradeitemid) {
            $this->import_grades($this->course->id, $gradeitemid, $userlist);
        }

        // Check the correct data on the page.
        $page = get_aggregation_page::execute($this->course->id, $categoryid, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        // Get form for 'mycampus' plugin
        // (which doesn't have a form).
        $form = get_aggregation_export_form::execute($courseid, $categoryid, 'mycampus');
        $form = external_api::clean_returnvalue(
            get_aggregation_export_form::execute_returns(),
            $form
        );

        $this->assertFalse($form['hasform']);
        $this->assertCount(0, $form['form']);

        // Get CSV data.
        $data = get_aggregation_export_data::execute($courseid, $categoryid, 0, 'mycampus', []);
        $data = external_api::clean_returnvalue(
            get_aggregation_export_data::execute_returns(),
            $data
        );

        $expected = '"EMPLID","Name","Grade"
"1234567","Bloggs,Fred","D1"
"1234560","Perez,Juan",""
';
        $this->assertEquals($expected, $data['csv']);

        // Write admin grade for aggregated category.
        $itemid = $this->get_gradeitemid_for_category('Summative');
        $nothing = write_additional_grade::execute(
            courseid:       $this->course->id,
            gradeitemid:    $itemid,
            userid:         $this->student->id,
            reason:         'CATEGORY',
            other:          '',
            admingrade:     'GOODCAUSE_NR',
            scale:          0,
            grade:          0,
            notes:          'Test notes'
        );
        $nothing = external_api::clean_returnvalue(
            write_additional_grade::execute_returns(),
            $nothing
        );

        // Check the correct data on the page.
        $page = get_aggregation_page::execute($this->course->id, $categoryid, '', '', 0, true);
        $page = external_api::clean_returnvalue(
            get_aggregation_page::execute_returns(),
            $page
        );

        $fred = $page['users'][0];
        $this->assertEquals('ECC', $fred['displaygrade']);

        // Get CSV data with admingrade.
        $data = get_aggregation_export_data::execute($courseid, $categoryid, 0, 'mycampus', []);
        $data = external_api::clean_returnvalue(
            get_aggregation_export_data::execute_returns(),
            $data
        );

        $expected = '"EMPLID","Name","Grade"
"1234567","Bloggs,Fred","EC"
"1234560","Perez,Juan",""
';
        $this->assertEquals($expected, $data['csv']);
    }
}
