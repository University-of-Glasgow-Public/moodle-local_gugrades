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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Behat steps in plugin local_gugrades
 *
 * @package    local_gugrades
 * @category   test
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_gugrades extends behat_base {
     /**
      * Creates URLS
      *
      * @param string $type
      *
      * @return string
      */
    protected function resolve_page_url(string $type): moodle_url {
        switch ($type) {
            case 'Settings':
                return new moodle_url('/admin/settings.php?section=managelocalgugrades');
            default:
                throw new Exception('Unrecognised page type "' . $type . '."');
        }
    }
     /**
      * Returns fixed step argument (with \\" replaced back to ")
      *
      * @param string $argument
      *
      * @return string
      */
    protected function fixstepargument($argument) {
        return str_replace('\\"', '"', $argument);
    }
     /**
      * Adding the ability to select elements using class and id
      * @When I click the element with class :class
      *
      * @param string $class $element
      *
      * @return string
      */
    public function iclickelementwithclass($class) {
        $element = $this->getSession()->getPage()->find('css', '.' . $class);
        if (!$element) {
                throw new \Exception("Element with class '$class' not found");
        }
        $element->click();
    }
     /**
      * Finding the last cell in the table
      * @Then I should see :text in the total cell
      *
      * @param string $text $cell
      *
      * @return string
      */
    public function totalcellshouldcontain($text) {
        $cell = $this->getSession()->getPage()->find('xpath', '//tbody//tr[1]//td[last()]');

        if (!$cell) {
            throw new \Exception("Could not find total cell");
        }

        if (strpos($cell->getText(), $text) === false) {
            throw new \Exception("Total cell contains '{$cell->getText()}' not '$text'");
        }
    }
     /**
      * Use this to wait for Vue
      * @When I wait :seconds seconds
      *
      * @param int $seconds
      *
      * @return int
      */
    public function iWaitSeconds(int $seconds): void {
        sleep($seconds);
    }
}
