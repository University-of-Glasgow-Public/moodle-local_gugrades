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
 * Deal with audit train
 *
 * @package    local_gugrades
 * @copyright  2023
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades;

/**
 * Static class implementation to deal with audit trail
 */
class audit {
    /**
     * relateduserid value stored when an action affects more than one user.
     * Zero means the action is not tied to a user.
     */
    public const RELATED_MULTIPLE = -1;

    /**
     * Related user id to store for an action.
     * Zero when nobody is affected, the user id when one user is affected,
     * or RELATED_MULTIPLE when more than one user is affected.
     * @param int[] $userids
     * @return int
     */
    public static function relateduserid_for(array $userids): int {
        $ids = [];
        foreach ($userids as $userid) {
            $userid = (int) $userid;
            if ($userid > 0) {
                $ids[$userid] = true;
            }
        }

        $count = count($ids);
        if ($count === 0) {
            return 0;
        }
        if ($count === 1) {
            return (int) array_key_first($ids);
        }

        return self::RELATED_MULTIPLE;
    }

    /**
     * Write audit
     * Utility function to add stuff to the audit trail
     * @param int $courseid
     * @param int $relateduserid User the action was done to, 0 if none, or RELATED_MULTIPLE.
     * @param int $gradeitemid
     * @param string $message
     */
    public static function write(int $courseid, int $relateduserid, int $gradeitemid, string $message) {
        global $USER, $DB;

        $a = new \stdClass();
        $a->courseid = $courseid;
        $a->userid = $USER->id;
        $a->relateduserid = $relateduserid;
        $a->gradeitemid = $gradeitemid;
        $a->timecreated = time();
        $a->message = $message;

        $DB->insert_record('local_gugrades_audit', $a);
    }

    /**
     * Read audit history
     * @param int $courseid
     * @param int $userid (all if not specied)
     * @return array
     */
    public static function read(int $courseid, int $userid = 0) {
        global $DB;

        $context = \context_course::instance($courseid);
        if (!$userid) {
            $params = ['courseid' => $courseid];
            $usersql = '';
        } else {
            $params = ['courseid' => $courseid, 'userid' => $userid];
            $usersql = 'AND userid = :userid';
        }

        $sql = "SELECT * FROM {local_gugrades_audit}
            WHERE courseid = :courseid
            $usersql
            ORDER BY timecreated DESC";
        $items = $DB->get_records_sql($sql, $params);

        // Additional info.
        $newitems = [];
        $gradeitemcache = [];
        foreach ($items as $item) {
            $item->time = userdate($item->timecreated);

            // Get (if possible) name of grade item.
            $gradeitem = null;
            if (!$item->gradeitemid) {
                $item->gradeitem = '';
            } else if (array_key_exists($item->gradeitemid, $gradeitemcache)) {
                $gradeitem = $gradeitemcache[$item->gradeitemid];
            } else {
                if ($gradeitem = $DB->get_record('grade_items', ['id' => $item->gradeitemid])) {
                    $gradeitemcache[$item->gradeitemid] = $gradeitem;
                }
            }
            if ($gradeitem) {
                // If gradeitem is a category, then more work to do.
                if ($gradeitem->itemtype == 'category') {
                    $category = $DB->get_record('grade_categories', ['id' => $gradeitem->iteminstance], '*', MUST_EXIST);
                    $item->gradeitem = $category->fullname;
                } else {
                    $item->gradeitem = $gradeitem->itemname;
                }
            } else {
                $item->gradeitem = '';
            }

            // Get user.
            $item->username = '-';
            if ($item->userid) {
                if ($user = $DB->get_record('user', ['id' => $item->userid])) {
                    $item->username = fullname($user);
                }
            }

            // Get related user.
            $item->relatedusername = '-';
            if ((int) $item->relateduserid === self::RELATED_MULTIPLE) {
                $item->relatedusername = get_string('multipleusersaffected', 'local_gugrades');
            } else if ($item->relateduserid) {
                if ($relateduser = $DB->get_record('user', ['id' => $item->relateduserid])) {
                    $item->relatedusername = fullname($relateduser);
                }
            }

            $newitems[] = $item;
        }

        return $newitems;
    }
}
