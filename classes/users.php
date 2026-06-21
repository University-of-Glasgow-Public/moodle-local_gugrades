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

define('NOTES_SHORTNAME_LENGTH', 30);

/**
 * Group functions used to manipulate user-related data
 */
class users {
    /**
     * Get course module from grade item
     * @param int $itemid Grade item ID
     * @param int $courseid
     * @return object|bool
     */
    public static function get_cm_from_grade_item(int $itemid, int $courseid) {
        global $DB;

        $item = $DB->get_record('grade_items', ['id' => $itemid], '*', MUST_EXIST);

        // This only works when itemtype is mod (not surprisingly).
        if ($item->itemtype != 'mod') {
            return false;
        }

        // Set to -1 to avoid calculation of dynamic user-depended data.
        $modinfo = get_fast_modinfo($courseid, -1);
        if (!$cm = $modinfo->instances[$item->itemmodule][$item->iteminstance]) {
            throw new \moodle_exception('Unable to find course module for gradeitemid = ' . $itemid);
        }
        return $cm;
    }

    /**
     * Get availability for user.
     * @param int $gradeitemid
     * @param int $userid
     * @return boolean
     */
    public static function available_for_user(int $gradeitemid, int $userid) {
        global $DB;

        $item = \local_gugrades\grades::get_gradeitem($gradeitemid);

        // If the gradeitem is NOT is module then it's simply available.
        if ($item->itemtype != 'mod') {
            return true;
        }

        // Get course module.
        $courseid = $item->courseid;
        $modinfo = get_fast_modinfo($courseid, $userid);
        if (!$cm = $modinfo->instances[$item->itemmodule][$item->iteminstance]) {
            throw new \moodle_exception('Unable to find course module for gradeitemid = ' . $gradeitemid);
        }

        return (bool) $cm->visible;
    }

    /**
     * Factory to get correct class for assignment type
     * These are found in local_gugrades/classes/activities
     * Pick manual for manual grades, xxx_activity for activity xxx (if exists) or default_activity
     * for everything else
     * @param int $gradeitemid
     * @param int $courseid
     * @param int $groupid
     * @return object
     */
    public static function activity_factory(int $gradeitemid, int $courseid, int $groupid = 0) {
        global $DB;

        $item = $DB->get_record('grade_items', ['id' => $gradeitemid], '*', MUST_EXIST);
        $module = $item->itemmodule;
        if ($item->itemtype == 'manual') {
            return new \local_gugrades\activities\manual($gradeitemid, $courseid, $groupid);
        } else {
            $classname = '\\local_gugrades\\activities\\' . $module . '_activity';
            if (class_exists($classname, true)) {
                return new $classname($gradeitemid, $courseid, $groupid);
            } else {
                return new \local_gugrades\activities\default_activity($gradeitemid, $courseid, $groupid);
            }
        }
    }

    /**
     * Get users who can "be graded". Usually students.
     * @param \context $context
     * @param string $firstname (first letter of first name)
     * @param string $lastname (first letter of last name)
     * @param int $groupid (0 means ignore groups)
     * @return array
     */
    public static function get_gradeable_users(\context $context, $firstname = '', $lastname = '', $groupid = 0) {
        $fields = 'u.id, u.username, u.idnumber, u.firstname, u.lastname, u.email,
            u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.picture, u.imagealt';
        $users = get_enrolled_users($context, 'moodle/grade:view', $groupid, $fields);

        // Filter.
        if ($firstname || $lastname) {
            $users = array_filter($users, function ($user) use ($firstname, $lastname) {
                if ($firstname && (strcasecmp(substr($user->firstname, 0, 1), $firstname))) {
                    return false;
                }
                if ($lastname && (strcasecmp(substr($user->lastname, 0, 1), $lastname))) {
                    return false;
                }
                return true;
            });
        }

        return $users;
    }

    /**
     * Convenience function to get a count of all users in the course
     * @param int $courseid
     * @return int
     */
    public static function count_enrolled_users(int $courseid) {
        $context = \context_course::instance($courseid);
        $users = self::get_gradeable_users($context);

        return count($users);
    }

    /**
     * Get user record from userid
     * Check that user is a valid "student" in the course
     * @param \context $context
     * @param int $userid
     * @return object
     */
    public static function get_gradeable_user(\context $context, int $userid) {
        global $DB;

        if (!is_enrolled($context, $userid, 'moodle/grade:view')) {
            throw new \moodle_exception('Not a gradeable user in this course. Userid = ' . $userid);
        }

        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

        return $user;
    }

    /**
     * Get available users for given activity
     * @param object $cmi (cm_info)
     * @param \context $context
     * @param string $firstname (first letter of first name)
     * @param string $lastname (first letter of last name)
     * @param int $groupid
     * @return array
     */
    public static function get_available_users_from_cm($cmi, $context, $firstname, $lastname, $groupid) {

        // See https://moodledev.io/docs/apis/subsystems/availability.
        $info = new \core_availability\info_module($cmi);

        // Get all the possible users in this course.
        $users = self::get_gradeable_users($context, $firstname, $lastname, $groupid);

        // Filter using availability API.
        $filteredusers = $info->filter_user_list($users);

        return array_values($filteredusers);
    }

    /**
     * Add pictures to user records
     * @param int $courseid
     * @param int $gradeitemid
     * @param array $users
     * @return array
     */
    public static function add_pictures_and_profiles_to_user_records(int $courseid, int $gradeitemid, array $users) {
        foreach ($users as $id => $user) {
            $users[$id] = self::add_picture_and_profile_to_user_record($courseid, $gradeitemid, $user);
        }

        return $users;
    }

    /**
     * Add picture to single user record
     * $gradeitemid can be 0 and so an empty note is returned
     * @param int $courseid
     * @param int $gradeitemid
     * @param object $user
     * @return object
     */
    public static function add_picture_and_profile_to_user_record(int $courseid, int $gradeitemid, object $user) {
        global $PAGE, $DB;

        $cache = \cache::make('local_gugrades', 'userpicture');
        if ($pictureurl = $cache->get($user->id)) {
            $user->pictureurl = $pictureurl;
        } else {
            $userpicture = new \user_picture($user);
            $pictureurl = $userpicture->get_url($PAGE)->out(false);
            $user->pictureurl = $pictureurl;
            $cache->set($user->id, $pictureurl);
        }

        // Also add profile url while we are here.
        $profile = new \moodle_url('/user/view.php', ['course' => $courseid, 'id' => $user->id]);
        $user->profileurl = $profile->out(false);

        // Also add short note
        $params = [
            'courseid' => $courseid,
            'gradeitemid' => $gradeitemid,
            'userid' => $user->id,
        ];
        if ($gradeitemid && $noterecord = $DB->get_record('local_gugrades_notes', $params)) {
            $user->shortnote = shorten_text(html_to_text($noterecord->note), NOTES_SHORTNAME_LENGTH);
        } else {
            $user->shortnote = '';
        }

        return $user;
    }

    /**
     * Add gradehidden flag to user records
     * @param array $users
     * @param int $gradeitemid
     * @return array
     */
    public static function add_gradehidden_to_user_records(array $users, int $gradeitemid) {
        foreach ($users as $id => $user) {
            $users[$id] = self::add_gradehidden_to_user_record($user, $gradeitemid);
        }

        return $users;
    }

    /**
     * Add gradehidden to user record
     * @param object $user
     * @param int $gradeitemid
     * @return object
     */
    public static function add_gradehidden_to_user_record(object $user, int $gradeitemid) {
        global $DB;

        $user->gradehidden = $DB->record_exists(
            'local_gugrades_hidden',
            ['gradeitemid' => $gradeitemid, 'userid' => $user->id]
        );

        return $user;
    }

    /**
     * Count the number of users in a given course
     * @param int $courseid
     * @return int
     */
    public static function count_participants(int $courseid) {
        $context = \context_course::instance($courseid);

        return count_enrolled_users($context);
    }

    /**
     * Get the course code from the gudatabase tables
     * @param int $courseid
     * @param int $userid
     * @return string
     */
    public static function get_course_code(int $courseid, int $userid) {
        global $DB;

        // Is enrol_gudatabase installed at all?
        if (!\local_gugrades\export::is_enrol_gudatabase_enabled()) {
            return '';
        }

        if (
            $gudatabasecode = $DB->get_record(
                'enrol_gudatabase_users',
                ['userid' => $userid, 'courseid' => $courseid],
                '*',
                IGNORE_MULTIPLE
            )
        ) {
            $code = $gudatabasecode->code;
        } else {
            $code = '';
        }

        return $code;
    }

    /**
     * Allow display of CSV import button
     * Only if one or more ID number
     * @param array $users
     * @return bool
     */
    public static function showcsvimport(array $users) {
        foreach ($users as $user) {
            if ($user->idnumber) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear availability cache.
     * @param int $courseid
     */
    public static function clear_availability_cache(int $courseid) {
        global $DB;

        $items = $DB->get_records('grade_items', ['courseid' => $courseid]);
        foreach ($items as $item) {
            $cachetag = 'AVAILABLE_' . $courseid . '_' . $item->id;
        }
    }

    /**
     * Get firstname and lastname initials
     * @param object $user
     * @return array
     */
    public static function get_initials(object $user) {
        $first = empty($user->firstname) ? '' : \core_text::substr($user->firstname, 0, 1);
        $last = empty($user->lastname) ? '' : \core_text::substr($user->lastname, 0, 1);

        $first = \core_text::strtoupper($first);
        $last = \core_text::strtoupper($last);

        return [$first, $last];
    }

    /**
     * Get a user profile field
     * @param int $userid
     * @param string $fieldname
     * @return string
     */
    protected static function get_profile_field(int $userid, string $fieldname): string {
        global $CFG;

        require_once($CFG->dirroot . '/user/profile/lib.php');

        // Fetch all custom profile fields for the student
        $profilefields = profile_user_record($userid);

        $value = $profilefields->$fieldname ?? '';

        return $value;
    }

    /**
     * Determine if user is UG, PG or neither
     * Returns 'Postgraduate Taught', 'Lifelong Learning, 'Undergraduate', 'Postgraduate Research',
     * 'External'. Or 'Unknown' if no record found
     * If no data for a user, we call enrol_gudatabase in an attempt to do something
     * about it. However, as we don't want to keep hammering the external enrolment database
     * we'll have a cache for this information. 
     * @param int $userid
     * @return string
     */
    public static function get_ugpg(int $userid): string {
        global $DB, $CFG;

        $cache = \cache::make('local_gugrades', 'ugpg');
        if ($ugpg = $cache->get($userid)) {
            return $ugpg;
        }

        // If not in cache, is it in the 'ugpg' profile field?
        if ($ugpg = self::get_profile_field($userid, 'ugpg')) {
            $cache->set($userid, $ugpg);

            return $ugpg;
        }

        // If that fails, we prompt local_gugrades to load the data from enrolment database
        // NOTE: Checks that custom profile fields for program data have been added to
        // api::background_setup(), so it's reasonably certain the ugpg field exists.
        $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        require_once("$CFG->dirroot/enrol/gudatabase/lib.php");
        $plugin = new \enrol_gudatabase_plugin();

        // If it's not configured, then we can only return Unknown
        if (!$plugin->is_configured()) {
            $cache->set($userid, 'Unknown');
            return 'Unknown';
        }
        $plugin->external_programdata($user);

        // Now try again to see if data has appeared in profile field.
        if ($ugpg = self::get_profile_field($userid, 'ugpg')) {
            $cache->set($userid, $ugpg);

            return $ugpg;
        }

        // Failed. Not a student at all or missing record.
        $cache->set($userid, 'Unknown');

        return 'Unknown';
    }
}
