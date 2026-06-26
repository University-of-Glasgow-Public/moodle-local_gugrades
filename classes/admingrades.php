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
 * Language EN
 *
 * @package    local_gugrades
 * @copyright  2023
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/grade/lib.php');

/**
 * Handles admin grades in one place
 */
class admingrades {
    /**
     * Default definitions of admin grades and where they may be used.
     * levels means....
     * These are all the *possible* admingrades, not necessarily those
     * valid for the current regulations regime.
     * Code and description are defaults, they can be changed in the
     * MyGrades settings page. 
     * @return array
     */
    private static function defaults() {
        return [
            'GOODCAUSE_FO' => [
                'default' => [
                    'code' => 'EC',
                    'description' => get_string('adminmv', 'local_gugrades'),
                ],
            ],
            'GOODCAUSE_NR' => [
                'default' => [
                    'code' => 'ECC',
                    'description' => get_string('adminmv0', 'local_gugrades'),
                ],
            ],
            'NOSUBMISSION' => [
                'default' => [
                    'code' => 'NS',
                    'description' => get_string('adminns', 'local_gugrades'),
                ],
            ],
            'NOSUBMISSION_0' => [
                'default' => [
                    'code' => 'NS0',
                    'description' => get_string('adminns0', 'local_gugrades'),
                ],
            ],
            'DEFERRED' => [
                'default' => [
                    'code' => 'DFR',
                    'description' => get_string('admin07', 'local_gugrades'),
                ],
            ],
            'GOODCAUSECREDITWITHHELD' => [
                'default' => [
                    'code' => 'ECW',
                    'description' => get_string('admingcw', 'local_gugrades'),
                ],
            ],
            'CREDITWITHHELD' => [
                'default' => [
                    'code' => 'CW',
                    'description' => get_string('admincw', 'local_gugrades'),
                ],
            ],
            'CREDITNOTAWARDED' => [
                'default' => [
                    'code' => 'CNA',
                    'description' => get_string('admincan', 'local_gugrades'),
                ]
            ],
            'UNSATISFACTORY' => [
                'name' => 'UNSATISFACTORY',
                'default' => [
                    'code' => 'UNS',
                    'description' => get_string('adminuns', 'local_gugrades'),
                ],
            ],
            'SATISFACTORY' => [
                'default' => [
                    'code' => 'SAT',
                    'description' => get_string('adminsat', 'local_gugrades'),
                ],
            ],
            'NOTPASSED' => [
                'default' => [
                    'code' => 'NP',
                    'description' => get_string('adminnp', 'local_gugrades'),
                ],
            ],
            'PASSED' => [
                'default' => [
                    'code' => 'P',
                    'description' => get_string('adminp', 'local_gugrades'),
                ],
            ],
            'NOTCOMPLETE' => [
                'default' => [
                    'code' => 'NC',
                    'description' => get_string('adminnc', 'local_gugrades'),
                ],
            ],
            'COMPLETE' => [
                'default' => [
                    'code' => 'CP',
                    'description' => get_string('admincp', 'local_gugrades'),
                ],
            ],
            'CREDITREFUSED' => [
                'default' => [
                    'code' => 'CR',
                    'description' => get_string('admincr', 'local_gugrades'),
                ],
            ],
            'CREDITNOTYETAWARDED' => [
                'default' => [
                    'code' => 'CNY',
                    'description' => get_string('admincny', 'local_gugrades'),
                ]
            ],
            'CREDITAWARDED' => [
                'default' => [
                    'code' => 'CA',
                    'description' => get_string('adminca', 'local_gugrades'),
                ],
            ],
            'AUDITONLY' => [
                'default' => [
                    'code' => 'AU',
                    'description' => get_string('adminau', 'local_gugrades'),
                ],
            ],
            'INTERRUPTIONOFSTUDIES' => [
                'default' => [
                    'code' => 'IS',
                    'description' => get_string('adminis', 'local_gugrades'),
                ],
            ],
        ];
    }

    /**
     * Get map from old to new database entry codes
     * used (once) in db/upgrade.php
     * @return array
     */
    public static function get_upgrade_map() {
        $defaults = self::defaults();
        $maps = [];
        foreach ($defaults as $name => $default) {
            $maps[$default['default']['code']] = $name;
        }

        return $maps;
    }

    /**
     * Get the data for settings page
     * @return array
     */
    public static function get_settings_data() {

        return self::defaults();
    }

    /**
     * Get the settings tag for admin grade
     * @param string $admingrade
     * @return string
     */
    public static function get_setting_tag(string $admingrade) {
        return 'admingrade_' . strtolower($admingrade);
    }

    /**
     * Check that admingrade (name) is valid
     * @param string $admingrade
     * @throws \moodle_exception
     */
    public static function validate_admingrade(string $admingrade) {
        $defaults = self::defaults();
        if (!array_key_exists($admingrade, $defaults)) {
            throw new \moodle_exception('Attempt to write invalid admin grade - "' . $admingrade . '"');
        }

        return $defaults[$admingrade];
    }

    /**
     * Set empty settings to defaults
     */
    public static function setting_defaults() {
        $defaults = self::defaults();
        foreach ($defaults as $name => $default) {
            $tag = self::get_setting_tag($name);
            $setting = get_config('local_gugrades', $tag);
            if (empty($setting) || empty(json_decode($setting)->code)) {
                set_config($tag, json_encode($default['default']), 'local_gugrades');
            }
        }
    }

    /**
     * Get displaygrade and description from name
     * @param string $admingrade
     * @return array
     */
    public static function get_displaygrade_from_name($admingrade) {
        self::validate_admingrade($admingrade);

        // Admingrade details from settings.
        $tag = self::get_setting_tag($admingrade);
        $setting = get_config('local_gugrades', $tag);
        if (!$setting) {
            throw new \moodle_exception('Setting not found for tag "' . $tag . '"');
        }
        $admin = json_decode($setting);

        return [$admin->code, $admin->description];
    }

    /**
     * Filter explain string to convert any admingrade placeholders to code in config
     * MGU-1360
     * @param string $explain
     * @return string
     */
    public static function replace_explain(string $explain) {
        $defaults = self::defaults();
        foreach ($defaults as $name => $default) {
            [$admincode, ] = self::get_displaygrade_from_name($name);
            $explain = str_replace($name, $admincode, $explain);
        }

        return $explain;
    }

    /**
     * Updates *all* the instances of admingrades in the grade table
     * when an admingrade setting is changed.
     * @param string $name
     */
    public static function update_displaynames(string $name) {
        global $DB;

        [$displaygrade, ] = self::get_displaygrade_from_name($name);
        $sql = 'UPDATE {local_gugrades_grade} SET displaygrade = :displaygrade WHERE admingrade = :name';
        $DB->execute($sql, [
            'displaygrade' => $displaygrade,
            'name' => $name,
        ]);
    }

    /**
     * Get grades for supplied
     * Level =
     * @param int $courseid
     * @param int $level
     * @param bool $grandtotal
     * @return array
     */
    public static function get_admingrades_for_level(int $courseid, int $level, bool $grandtotal = false) {

        $defaults = self::defaults();
        $regulation = \local_gugrades\regulations::get_active_regulation($courseid);
        $codes = $regulation->get_admingrades($courseid, $grandtotal ? 0 : $level);

        $admingrades = [];
        foreach ($codes as $code) {
            [$displaygrade, $description] = self::get_displaygrade_from_name($code);
            $admingrades[$code] = "$displaygrade - $description";
        }

        return $admingrades;
    }

    /**
     * Get admincodes for non level 1 total menu
     * @param int $courseid
     * @param int $gradeitemid
     * @return array
     */
    public static function get_menu(int $courseid, int $gradeitemid) {
        $level = \local_gugrades\grades::get_gradeitem_level($gradeitemid);
        $admingrades = self::get_admingrades_for_level($courseid, $level, false);

        return $admingrades;
    }

    /**
     * Get admincodes for level 1 total menu
     * @param int $courseid
     * @return array
     */
    public static function get_menu_level_one(int $courseid) {
        $admingrades = self::get_admingrades_for_level($courseid, 1, true);

        return $admingrades;
    }

    /**
     * Get admingrades formatted for CSV Import
     * The valid code is the key => internal code (e.g. NOSUBMISSION)
     * @param int $courseid
     * @param int $level
     * @return array
     */
    public static function get_admingrades_csv(int $courseid, int $level) {

        $regulation = \local_gugrades\regulations::get_active_regulation($courseid);
        $codes = $regulation->get_admingrades($courseid, $level);
        $admingrades = [];

        foreach ($codes as $code) {
            [$displaygrade, $description] = self::get_displaygrade_from_name($code);
            $admingrades[$displaygrade] = $code;
        }

        /*
        foreach ($defaults as $name => $default) {
            // Has to be in items list.
            if (!self::flag_set($default, 'items')) {
                continue;
            }

            // If we're at level 1 then level2 items are not included.
            if (($level == 1) && self::flag_set($default, 'level2')) {
                continue;
            }

            [$displaygrade, ] = self::get_displaygrade_from_name($name);
            $admingrades[$displaygrade] = $name;
        }
        */

        return $admingrades;
    }

    /**
     * Validate admingrades for given courseid
     * @param int $courseid
     * @return bool
     */
    public static function are_admingrades_valid(int $courseid): bool {

        // Get ALL valid admingrades for regulations.
        $regulation = \local_gugrades\regulations::get_active_regulation($courseid);
        $admingrades = $regulation->get_admingrades($courseid, 0);
        $admingrades = array_merge($admingrades, $regulation->get_admingrades($courseid, 1));
        $admingrades = array_unique(array_merge($admingrades, $regulation->get_admingrades($courseid, 2)));

        return !\local_gugrades\grades::any_invalid_admingrades($courseid, $admingrades);
    }
}
