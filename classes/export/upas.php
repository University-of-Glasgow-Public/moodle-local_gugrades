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
 * UPAS aggregation export
 * @package    local_gugrades
 * @copyright  2026
 * @author     Michael Clark
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_gugrades\export;

/**
 * UPAS CSV export of aggregated course results
 */
class upas extends base {
    /**
     * Define name of export
     * @return string
     */
    public function get_name() {
        return get_string('upasexport', 'local_gugrades');
    }

    /**
     * Only available for courses starting on or after 1 August 2026
     * @param int $courseid
     * @return bool
     */
    public function is_available(int $courseid) {
        $regulation = \local_gugrades\regulations::get_active_regulation($courseid);

        return $regulation->shortname() === 'from2026';
    }

    /**
     * Academic year from course start date (1 August cutoff)
     * @param int $startdate
     * @return string
     */
    protected function get_academic_year(int $startdate): string {
        $year = (int)date('Y', $startdate);
        $month = (int)date('n', $startdate);
        if ($month < 8) {
            $year--;
        }

        return $year . '-' . substr((string)($year + 1), -2);
    }

    /**
     * Proposed download filename
     * @param int $courseid
     * @param int $groupid
     * @return string
     */
    public function get_filename(int $courseid, int $groupid = 0) {
        $course = get_course($courseid);
        [$subject, $catalognbr] = $this->parse_course_code($course->shortname);
        $academicyear = $this->get_academic_year($course->startdate);

        if ($subject !== '' && $catalognbr !== '') {
            $filename = $subject . '_' . $catalognbr . '_UPAS_' . $academicyear;
        } else {
            $filename = $course->shortname . '_UPAS_' . $academicyear;
        }

        $groupname = $this->get_filename_group_suffix($groupid);
        if ($groupname !== '') {
            $filename .= '_' . $groupname;
        }

        return $filename;
    }

    /**
     * Safe group name for appending to a filename
     * @param int $groupid
     * @return string
     */
    protected function get_filename_group_suffix(int $groupid): string {
        if ($groupid <= 0) {
            return '';
        }

        $groupname = groups_get_group_name($groupid);
        if (!$groupname) {
            return '';
        }

        $groupname = preg_replace('/\s+/', '_', $groupname);
        $groupname = preg_replace('/[^A-Za-z0-9._-]+/', '_', $groupname);
        $groupname = trim($groupname, '._-');

        return $groupname;
    }

    /**
     * Parse Subject and Catalog Nbr from a course shortcode
     * @param string $shortcode
     * @return array
     */
    protected function parse_course_code(string $shortcode): array {
        $shortcode = strtoupper($shortcode);
        if (preg_match('/([A-Z]+)(\d+)/', $shortcode, $match)) {
            return [$match[1], $match[2]];
        }

        return ['', ''];
    }

    /**
     * Sanitise a grade for MyCampus / UPAS (letter or admin code only)
     * @param string $admingrade
     * @param mixed $rawgrade
     * @param string $displaygrade
     * @return string
     */
    protected function sanitise_grade_value($admingrade, $rawgrade, $displaygrade) {

        // First check if there is an admin grade.
        if ($admingrade) {
            // Change GOODCAUSE_NR to GOODCAUSE_FO.
            if ($admingrade == 'GOODCAUSE_NR') {
                $admingrade = 'GOODCAUSE_FO';
            }
            [$grade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);

            return $grade;
        }

        // Failing that, does it appear to be an actual grade?
        if ($rawgrade) {
            $parts = explode(' ', $displaygrade);

            return $parts[0];
        }

        // First-sitting letter stored without a raw value (e.g. D1).
        if ($displaygrade && !str_contains($displaygrade, ' ')) {
            return $displaygrade;
        }

        return '';
    }

    /**
     * Current / award grade from aggregated user record
     * @param object $user
     * @return string
     */
    protected function sanitise_award_grade($user) {
        return $this->sanitise_grade_value($user->admingrade, $user->rawgrade, $user->displaygrade);
    }

    /**
     * Get first sitting / first attempt grade for a user in this category
     * @param int $courseid
     * @param int $gradecategoryid
     * @param object $user
     * @return string
     */
    protected function get_first_attempt_grade(int $courseid, int $gradecategoryid, object $user) {
        global $DB;

        $gradecatitem = \local_gugrades\grades::get_gradeitem_from_gradecategoryid($gradecategoryid);

        $categoryparams = [
            'courseid' => $courseid,
            'gradeitemid' => $gradecatitem->id,
            'userid' => $user->id,
            'gradetype' => 'CATEGORY',
        ];

        // Prefer first-sitting fields stored on the aggregated CATEGORY grade.
        $category = $DB->get_record('local_gugrades_grade', $categoryparams + ['iscurrent' => 1]);
        if ($category && ($category->first_admingrade || $category->first_displaygrade)) {
            return $this->sanitise_grade_value(
                $category->first_admingrade,
                $category->first_rawgrade,
                $category->first_displaygrade
            );
        }

        // If this category has a designated resit item, use the first sitting item.
        $resititemid = \local_gugrades\grades::get_resit_itemid($gradecategoryid);
        if ($resititemid) {
            $items = $DB->get_records('grade_items', ['categoryid' => $gradecategoryid]);
            foreach ($items as $item) {
                if ($item->id == $resititemid) {
                    continue;
                }

                $first = $DB->get_record('local_gugrades_grade', [
                    'courseid' => $courseid,
                    'gradeitemid' => $item->id,
                    'userid' => $user->id,
                    'gradetype' => 'FIRST',
                    'iscurrent' => 1,
                ]);
                if ($first) {
                    return $this->sanitise_grade_value($first->admingrade, $first->rawgrade, $first->displaygrade);
                }

                $provisional = \local_gugrades\grades::get_provisional_from_id($courseid, $item->id, $user->id);
                if ($provisional) {
                    return $this->sanitise_grade_value(
                        $provisional->admingrade,
                        $provisional->rawgrade,
                        $provisional->displaygrade
                    );
                }
            }
        }

        // If the award has been overridden, the previous aggregated CATEGORY grade is the first attempt.
        if ($category && !empty($category->catoverride)) {
            $previous = $DB->get_records(
                'local_gugrades_grade',
                $categoryparams + ['catoverride' => 0],
                'id DESC',
                '*',
                0,
                1
            );
            $previous = reset($previous);
            if ($previous) {
                return $this->sanitise_grade_value(
                    $previous->admingrade,
                    $previous->rawgrade,
                    $previous->displaygrade
                );
            }
        }

        // No separate first sitting - initial grade is the same as the award.
        return $this->sanitise_award_grade($user);
    }

    /**
     * Return data for CSV export
     * @param int $courseid
     * @param int $gradecategoryid
     * @param int $groupid
     * @param array $form
     * @return string
     */
    public function get_form_data(int $courseid, int $gradecategoryid, int $groupid, array $form) {

        $course = get_course($courseid);
        [$subject, $catalognbr] = $this->parse_course_code($course->shortname);

        // Get list of students.
        $users = \local_gugrades\aggregation::get_users($courseid, $gradecategoryid, '', '', $groupid);

        // Aggregate all the users.
        [$columns] = \local_gugrades\aggregation::get_columns($courseid, $gradecategoryid);
        [$users] = \local_gugrades\aggregation::add_aggregation_fields_to_users($courseid, $gradecategoryid, $users, $columns);

        // Array holds CSV lines.
        $lines = [];

        // Header.
        $lines[] = [
            'Student Number',
            'Last Name',
            'First Name',
            'Subject',
            'Catalog Nbr',
            'Descr Course Name',
            'First Attempt Grade',
            'Award Grade',
        ];

        // Iterate over users getting requested data.
        foreach ($users as $user) {
            $lines[] = [
                $user->idnumber,
                $user->lastname,
                $user->firstname,
                $subject,
                $catalognbr,
                $course->fullname,
                $this->get_first_attempt_grade($courseid, $gradecategoryid, $user),
                $this->sanitise_award_grade($user),
            ];
        }

        return $this->convert_csv($lines);
    }
}
