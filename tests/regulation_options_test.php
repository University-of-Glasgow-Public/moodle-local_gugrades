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
 * Tests for from2026 regulation option caching.
 *
 * @package    local_gugrades
 * @copyright  2026
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades;

defined('MOODLE_INTERNAL') || die();

/**
 * MGU-1596: Empty options must be stored independently of school-specific options.
 */
final class regulation_options_test extends \advanced_testcase {

    /**
     * All outcomes, including ordinary courses, populate their own cache entry.
     */
    public function test_options_cache_is_keyed_by_course(): void {
        $this->resetAfterTest();

        $engineering = $this->getDataGenerator()->create_category();
        $nursing = $this->getDataGenerator()->create_category();
        $nursingchild = $this->getDataGenerator()->create_category(['parent' => $nursing->id]);
        set_config('engineeringcat', $engineering->id, 'local_gugrades');
        set_config('nursingcat', $nursing->id, 'local_gugrades');

        $ordinarycourse = $this->getDataGenerator()->create_course();
        $engineeringcourse = $this->getDataGenerator()->create_course(['category' => $engineering->id]);
        $nursingcourse = $this->getDataGenerator()->create_course(['category' => $nursingchild->id]);
        $regulation = new \regulations_from2026\regulation();
        $property = new \ReflectionProperty($regulation, 'options');
        $previous = $property->getValue();

        try {
            $property->setValue(null, []);
            $this->assertSame([], $regulation->get_options($ordinarycourse->id));
            $this->assertSame(['engineering'], $regulation->get_options($engineeringcourse->id));
            $this->assertSame(['nursing'], $regulation->get_options($nursingcourse->id));
            $this->assertSame([], (new \regulations_from2026\regulation())->get_options($ordinarycourse->id));
            $this->assertSame([
                $ordinarycourse->id => [],
                $engineeringcourse->id => ['engineering'],
                $nursingcourse->id => ['nursing'],
            ], $property->getValue());
        } finally {
            $property->setValue(null, $previous);
        }
    }

    /**
     * PHPUnit continues bypassing cache reads when fixtures change within a test.
     */
    public function test_unit_test_options_remain_fresh(): void {
        $this->resetAfterTest();

        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        set_config('engineeringcat', 0, 'local_gugrades');
        set_config('nursingcat', 0, 'local_gugrades');
        $regulation = new \regulations_from2026\regulation();
        $property = new \ReflectionProperty($regulation, 'options');
        $previous = $property->getValue();

        try {
            $this->assertSame([], $regulation->get_options($course->id));
            set_config('nursingcat', $category->id, 'local_gugrades');
            $this->assertSame(['nursing'], $regulation->get_options($course->id));
            set_config('engineeringcat', $category->id, 'local_gugrades');
            $this->assertSame(['engineering'], $regulation->get_options($course->id));
            set_config('engineeringcat', 0, 'local_gugrades');
            set_config('nursingcat', 0, 'local_gugrades');
            $this->assertSame([], $regulation->get_options($course->id));
        } finally {
            $property->setValue(null, $previous);
        }
    }
}
