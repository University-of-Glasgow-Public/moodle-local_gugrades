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
 * Original regulations
 *
 * Defines aggregation rules for original regulations
 *
 * @package    local_gugrades
 * @copyright  2026
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace regulations_original;

defined('MOODLE_INTERNAL') || die();

class regulation {

    /**
     * Get short name
     * @return string
     */
    public function shortname(): string {
        return 'original';
    }

    /**
     * Display name of regulation.
     * @return string
     */
    public function displayname(): string {
        return 'Old regulations (to 2026)';
    }

    /**
     * Active regulation.
     * Decide if this is the active regulation based on course settings.
     * @param object $course
     * @return bool
     */
    public function is_active(object $course): bool {
        global $DB;

        // This works up to 1st August 2026.
        $enddate = strtotime('2026-08-01');

        // Check there is a startdate (unlikely to be an issue).
        if (!$course->startdate) {
            throw new \moodle_exception('Course start date has not been set');
        }

        return $course->startdate < $enddate;
    }

    /**
     * Get list of available admin grades, given level
     * NOTE: Level == 0, means 'grand'/final total
     * List of admincode 'names' is returned for level;
     * translation is done in admingrades class.
     * @param int $level
     * @return array
     */
    public function get_admingrades(int $level): array {
        if ($level == 0) {
            return [
                'GOODCAUSE_FO',
                'DEFERRED',
                'GOODCAUSECREDITWITHHELD',
                'CREDITWITHHELD',
                'UNSATISFACTORY',
                'SATISFACTORY',
                'NOTPASSED',
                'PASSED',
                'NOTCOMPLETE',
                'COMPLETE',
                'CREDITREFUSED',
                'CREDITAWARDED',
                'AUDITONLY',
                'INTERRUPTIONOFSTUDIES',
            ];
        } else if ($level == 1) {
            return [
                'GOODCAUSE_FO',
                'GOODCAUSE_NR',
                'NOSUBMISSION',
                'DEFERRED',
                'INTERRUPTIONOFSTUDIES',
            ];
        } else {
            return [
                'GOODCAUSE_FO',
                'GOODCAUSE_NR',
                'NOSUBMISSION',
                'NOSUBMISSION_0',
                'DEFERRED',
                'INTERRUPTIONOFSTUDIES',
            ];
        }
    }

    /**
     * Use the array of items for a given gradecategory and produce
     * an aggregated grade (or not).
     * The category object is provided to identify aggregation settings
     * and so on
     * Note that this will be for one gradecategory for one user, only.
     * Return array has the following...
     * - parent grade value (See MGU-821)
     * - raw aggregated grade
     * - display grade (e.g. scale)
     * - completion %
     * - error
     * @param int $courseid
     * @param object $category
     * @param array $items
     * @param int $level
     * @param int $userid
     * @return array ['rounded' grade, grade val, admingrade, grade disp, completion, error, explain, not available]
     */
    protected static function aggregate_user_category(int $courseid, object $category, array $items, int $level, int $userid) {

        // Get basic data about aggregation
        // (this is also a check that it actually exists).
        $keephigh = $category->keephigh;
        $droplow = $category->droplow;
        $aggmethod = $category->aggregation;
        $atype = $category->atype;
        $itemid = $category->itemid;
        $completion = 0;

        // Initialise 'explain' string.
        $explain = '';

        // Logic will be different if this category is for resits.
        $isresitcategory = \local_gugrades\grades::is_resit_category($category->categoryid);

        // Get appropriate aggregation 'rule' set.
        $aggregation = self::aggregation_factory($courseid, $category->atype);

        // 0 based keys, please.
        $items = array_values($items);

        // Get the correct aggregation function.
        $aggfunction = $aggregation->strategy_factory($aggmethod);

        // Populate lists of available users.
        // Used to drop unavailable.
        $items = $aggregation->availability($items, $userid);

        // MGU-1349: If there are now no 'available' items left, the
        // aggregated category is 'not available'.
        if (count($items) == 0) {
            $explain = get_string('explain_notavailable', 'local_gugrades');
            [$admingrade, $error, $displaygrade] = $aggregation->all_unavailable_total($level);

            return [0, 0, $admingrade, $displaygrade, $completion, $error, $explain, true];
        }

        // If level 1 then calculate completion %age.
        // This can be calculated even though we can't run rest of aggregation (incomplete).
        if ($level == 1) {
            $weighted = $aggregation->is_strategy_weighted($aggmethod);
            $completion = $aggregation->completion($items, $weighted);
        }

        // Need to have a valid aggregation type to actually do the aggregation.
        if ($category->atype == \local_gugrades\GRADETYPE_ERROR) {
            // Additional check for zero weights. This is an exceptional case for atype = ERROR. So check specifically.
            if ($aggregation->weight_error($items, $aggmethod)) {
                $explain = get_string('explain_zeroweights', 'local_gugrades');
                return [null, null, '', null, $completion, get_string('cannotaggregate', 'local_gugrades'), $explain, false];
            }

            $explain = get_string('explain_gradetypeerror', 'local_gugrades');

            return [null, null, '', null, $completion, get_string('cannotaggregate', 'local_gugrades'), $explain, false];
        }

        // Admingrade check for anything that happens before drop lowest and
        // checks for all items graded etc.
        // Skip this if we're dealing with a resit category.
        if (!$isresitcategory && ($admingrade = $aggregation->admin_grade_precheck($level, $items))) {
            [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
            $explain = $aggregation->get_explain();

            return [0, 0, $admingrade, $displaygrade, $completion, '', $explain, false];
        }

        // Quick check - all items must have a grade.
        foreach ($items as $item) {
            if ($item->grademissing) {
                $explain = get_string('explain_gradesmissing', 'local_gugrades');

                return [null, null, '', null, $completion, get_string('gradesmissing', 'local_gugrades'), $explain, false];
            }
        }

        // If this is a resit category then we may be able to resolve aggregation before doing anything else.
        if ($isresitcategory) {
            // Find the resit item id (must exist).
            $resititemid = \local_gugrades\grades::get_resit_itemid($category->categoryid, true);

            [$rawgrade, $admingrade, $explain] = $aggregation->resit($items, $resititemid);
            if ($explain != '') {
                if ($admingrade) {
                    [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
                    return [0, 0, $admingrade, $displaygrade, 0, '', $explain, false];
                } else {
                    if (($atype == \local_gugrades\GRADETYPE_SCHEDULEA) || ($atype == \local_gugrades\GRADETYPE_SCHEDULEB)) {
                        [$convertedgrade, $convertedgradevalue] = $aggregation->convert($rawgrade, $atype);
                        $parentgrade = $aggregation->get_grade_for_parent($rawgrade, $convertedgradevalue);
                        $displaygrade = $aggregation->format_displaygrade(
                            $convertedgrade,
                            $rawgrade,
                            $convertedgradevalue,
                            0,
                            $level
                        );

                        return [$parentgrade, $rawgrade, '', $displaygrade, 0, '', $explain, false];
                    } else {
                        $roundpoints = $aggregation->round_float($rawgrade);
                        return [$roundpoints, $roundpoints, '', $roundpoints, 0, '', $explain, false];
                    }
                }
            }
        }

        // If a resit category and we got this far, then none of the remaining checks are needed.
        // (We *know* that there cannot be any admin grades).
        if (!$isresitcategory) {
            // Pre-process. Can optionally return aggregated grade.
            [$admingrade, $items] = $aggregation->pre_process_items($items);
            if ($admingrade) {
                [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
                $explain = $aggregation->get_explain();

                return [0, 0, $admingrade, $displaygrade, $completion, '', $explain, false];
            }

            // ..."drop lowest" items.
            // NOTE: droplow is NOT supported for level 1.
            if (($droplow > 0) && ($level > 1)) {
                [$items, $droppeditems] = $aggregation->droplow($items, $droplow);
                self::flag_dropped_items($droppeditems, $userid);
            }

            // If we've got here and there are no grades to aggregate (possibly due to drop lowest)
            // then it's an error.
            // UNLESS any MV0s already dumped.
            if (count($items) == 0) {
                if ($aggregation->get_mv0found()) {
                    [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name('GOODCAUSE_NR');

                    return [0, 0, 'GOODCAUSE_NR', $displaygrade, $completion, '', $explain, false];
                } else {
                    $explain = get_string('explain_noitems', 'local_gugrades');

                    return [null, null, '', null, $completion, get_string('cannotaggregate', 'local_gugrades'), $explain, false];
                }
            }

            // If >=level2 then check for admin grades (see MGU-726).
            if ($level >= 2) {
                if ($admingrade = $aggregation->admin_grades_level2($items)) {
                    [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
                    $explain = $aggregation->get_explain();

                    return [0, 0, $admingrade, $displaygrade, $completion, '', $explain, false];
                }
            }

            // If level = 1 then check admin grades for 'top' level. TODO - Ticket number?
            if ($level == 1) {
                if ($admingrade = $aggregation->admin_grades_level1($items, $completion)) {
                    [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
                    $explain = $aggregation->get_explain();

                    return [0, 0, $admingrade, $displaygrade, $completion, '', $explain, false];
                }
            }
        }

        // Record normalised weights.
        self::record_weights($items, $userid);

        // Check for zero weights with whatever items remain.
        if ($aggregation->weight_error($items, $aggmethod)) {
            $explain = get_string('explain_zeroweights', 'local_gugrades');
            return [null, null, '', null, $completion, get_string('cannotaggregate', 'local_gugrades'), $explain, false];
        }

        // Now call the appropriate aggregation function to do the sums.
        $aggregatedgrade = call_user_func([$aggregation, $aggfunction], $items);

        // If this is a scale convert the numeric grade to the appropriate.
        if (($atype == \local_gugrades\GRADETYPE_SCHEDULEA) || ($atype == \local_gugrades\GRADETYPE_SCHEDULEB)) {
            [$convertedgrade, $convertedgradevalue] = $aggregation->convert($aggregatedgrade, $atype);

            // Should we pass back convertedgradevalue or aggregatedgrade (see MGU-821).
            $parentgrade = $aggregation->get_grade_for_parent($aggregatedgrade, $convertedgradevalue);

            // How do we want to display this?
            $displaygrade = $aggregation->format_displaygrade(
                $convertedgrade,
                $aggregatedgrade,
                $convertedgradevalue,
                $completion,
                $level
            );

            $explain = get_string('explain_schedule', 'local_gugrades');

            return [$parentgrade, $aggregatedgrade, '', $displaygrade, $completion, '', $explain, false];
        }

        // Return points grades.
        $explain = get_string('explain_points', 'local_gugrades');

        return [$aggregatedgrade, $aggregatedgrade, '', $aggregatedgrade, $completion, '', $explain, false];
    }

}