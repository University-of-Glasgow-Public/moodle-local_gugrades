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
 * local_gugrades privacy provider test
 * @package    local_gugrades
 * @copyright  2026
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades;

class regulations_test extends \advanced_testcase {

    private $course;

    protected function setUp(): void {
        parent::setUp();

        // Create course with startdate in range of old regs.
        $this->course = $this->getDataGenerator()->create_course([
            'startdate' => strtotime('2026-01-01'),
        ]);
    }

    /**
     * Test function to detect subplugins and open their regulations class
     */
    public function test_regulations_subplugin(): void {

        $this->resetAfterTest(true);

        $regulations = \local_gugrades\regulations::get_regulations();

        // Check that we have an entry called 'original'.
        $this->assertArrayHasKey('original', $regulations);
        $this->assertIsObject($regulations['original']);

        // Check for regulation name.
        $original = $regulations['original'];
        $this->assertEquals('Old regulations (to 2026)', $original->displayname());

        // Check correct regulations are selected
        $regulation = \local_gugrades\regulations::get_active_regulation($this->course->id);
        $this->assertEquals('original', $regulation->shortname());
    }

    /**
     * Test 'from2026' regs are detected properly
     */
    public function test_from2026(): void {

        $this->resetAfterTest(true);

        // Create another course, this time starting in the time
        // period of the new regs.
        $newcourse = $this->getDataGenerator()->create_course([
            'startdate' => strtotime('2026-09-01'),
        ]);

        $regulation = \local_gugrades\regulations::get_active_regulation($newcourse->id);
        $this->assertEquals('from2026', $regulation->shortname());
    }

}