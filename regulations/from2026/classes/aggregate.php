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
 * Aggregation rules from 2026
 * This class defines basic functional logic.
 * It could be overriden for custom instances.
 *
 * @package    local_gugrades
 * @copyright  2026
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
 * @phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameUnderscore
 */

namespace regulations_from2026;

/**
 * aggregation 'rules'
 */
class aggregate {
    /**
     * @var int $courseid
     */
    private int $courseid;

    /**
     * @var array $validusers
     */
    private array $validusers = [];

    /**
     * @var string $explain
     */
    private string $explain = '';

    /**
     * @var string $atype
     */
    private string $atype;

    /**
     * @var bool $nursingx
     */
    private bool $nursingx;




    /**
     * Note that MV0 grades were found (and dropped) in pre-process
     * Their presence (even though dropped) effects the aggregated admin grade
     * (see MGU-1110)
     * @var bool $mv0found
     */
    private bool $mv0found = false;

    /**
     * @var string $atype
     */

    /**
     * Set some required data
     * @param int $courseid
     * @param string $atype
     */
    public function set_data(int $courseid, string $atype) {
        $this->courseid = $courseid;
        $this->atype = $atype;
    }

    /**
     * Use the array of items for a given gradecategory and produce
     * an aggregated grade (or not).
     * The category object is provided to identify aggregation settings
     * and so on
     * Note that this will be for one gradecategory for one user, only.
     * &$conditionmet bubbles up Nursing X conditions
     * Return array has the following...
     * - parent grade value (See MGU-821)
     * - raw aggregated grade
     * - display grade (e.g. scale)
     * - completion % (NOT USED)
     * - error
     * @param int $courseid
     * @param object $category
     * @param array $items
     * @param int $level
     * @param int $userid
     * @param bool &$conditionmet
     * @return array ['rounded' grade, grade val, admingrade, grade disp, completion, error, explain, not available]
     */
    public function aggregate_user_category(int $courseid, object $category, array $items, int $level, int $userid, bool &$conditionmet = false) {

        // Get basic data about aggregation
        // (this is also a check that it actually exists).
        $keephigh = $category->keephigh;
        $droplow = $category->droplow;
        $aggmethod = $category->aggregation;
        $atype = $category->atype;
        $itemid = $category->itemid;

        // Special rules, Engineering, Nursing.
        $regulation = \local_gugrades\regulations::get_active_regulation($courseid);
        $options = $regulation->get_options($courseid);
        $isengineering = in_array('engineering', $options);
        $isnursingug = in_array('nursingug', $options);
        $isnursingpgt = in_array('nursingpgt', $options);

        // If it's not nursing, it doesn't matter what this is set to.
        // UG is D3:9, PGT is C3:12.
        $lowestnursinggrade = $isnursingug ? 9 : 12;

        // If it is nursing, check for nursing conditions.
        if ($isnursingug || $isnursingpgt) {
            if ($this->nursing_check($items, $lowestnursinggrade)) {
                $conditionmet = true;
            }
        }

        // Initialise 'explain' string.
        $explain = '';

        // Logic will be different if this category is for resits.
        $isresitcategory = \local_gugrades\grades::is_resit_category($category->categoryid);

        // 0 based keys, please.
        $items = array_values($items);

        // Get the correct aggregation function.
        [$aggfunction, $isweighted] = $this->strategy_factory($aggmethod);

        // Populate lists of available users.
        // Used to drop unavailable.
        $items = $this->availability($items, $userid);

        // MGU-1349: If there are now no 'available' items left, the
        // aggregated category is 'not available'.
        if (count($items) == 0) {
            $explain = get_string('explain_notavailable', 'local_gugrades');
            [$admingrade, $error, $displaygrade] = $this->all_unavailable_total($level);

            return [0, 0, $admingrade, $displaygrade, 0, $error, $explain, true];
        }

        // Need to have a valid aggregation type to actually do the aggregation.
        if ($category->atype == \local_gugrades\GRADETYPE_ERROR) {
            // Additional check for zero weights. This is an exceptional case for atype = ERROR. So check specifically.
            if ($this->weight_error($items, $aggmethod)) {
                $explain = get_string('explain_zeroweights', 'local_gugrades');
                return [null, null, '', null, 0, get_string('cannotaggregate', 'local_gugrades'), $explain, false];
            }

            $explain = get_string('explain_gradetypeerror', 'local_gugrades');

            return [null, null, '', null, 0, get_string('cannotaggregate', 'local_gugrades'), $explain, false];
        }

        // Admingrade check for anything that happens before drop lowest and
        // checks for all items graded etc.
        // Skip this if we're dealing with a resit category.
        if (!$isresitcategory && ($admingrade = $this->admin_grade_precheck($level, $items))) {
            [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
            $explain = $this->get_explain();

            return [0, 0, $admingrade, $displaygrade, 0, '', $explain, false];
        }

        // Quick check - all items must have a grade.
        foreach ($items as $item) {
            if ($item->grademissing) {
                $explain = get_string('explain_gradesmissing', 'local_gugrades');

                return [null, null, '', null, 0, get_string('gradesmissing', 'local_gugrades'), $explain, false];
            }
        }

        // If this is a resit category then we may be able to resolve aggregation before doing anything else.
        if ($isresitcategory) {
            // Find the resit item id (must exist).
            $resititemid = \local_gugrades\grades::get_resit_itemid($category->categoryid, true);

            [$rawgrade, $admingrade, $explain] = $this->resit($items, $resititemid);
            if ($explain != '') {
                if ($admingrade) {
                    [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
                    return [0, 0, $admingrade, $displaygrade, 0, '', $explain, false];
                } else {
                    if (($atype == \local_gugrades\GRADETYPE_SCHEDULEA) || ($atype == \local_gugrades\GRADETYPE_SCHEDULEB)) {
                        [$convertedgrade, $convertedgradevalue] = $this->convert($rawgrade, $atype);
                        $parentgrade = $this->get_grade_for_parent($rawgrade, $convertedgradevalue);
                        $displaygrade = $this->format_displaygrade(
                            $convertedgrade,
                            $rawgrade,
                            $convertedgradevalue,
                            0,
                            $level
                        );

                        return [$parentgrade, $rawgrade, '', $displaygrade, 0, '', $explain, false];
                    } else {
                        $roundpoints = $this->round_float($rawgrade);
                        return [$roundpoints, $roundpoints, '', $roundpoints, 0, '', $explain, false];
                    }
                }
            }
        }

        // If a resit category and we got this far, then none of the remaining checks are needed.
        // (We *know* that there cannot be any admin grades).
        if (!$isresitcategory) {
            // Pre-process. Can optionally return aggregated grade.
            [$admingrade, $items] = $this->pre_process_items($items, $isweighted, $level);
            if ($admingrade) {
                [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
                $explain = $this->get_explain();

                return [0, 0, $admingrade, $displaygrade, 0, '', $explain, false];
            }

            // ..."drop lowest" items.
            // NOTE: droplow is NOT supported for level 1.
            if (($droplow > 0) && ($level > 1)) {
                [$items, $droppeditems] = $this->droplow($items, $droplow);
                \local_gugrades\aggregation::flag_dropped_items($droppeditems, $userid);
            }

            // If we've got here and there are no grades to aggregate (possibly due to drop lowest)
            // then it's an error.
            // UNLESS any MV0s already dumped.
            if (count($items) == 0) {
                if ($this->get_mv0found()) {
                    [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name('GOODCAUSE_NR');

                    return [0, 0, 'GOODCAUSE_NR', $displaygrade, 0, '', $explain, false];
                } else {
                    $explain = get_string('explain_noitems', 'local_gugrades');

                    return [null, null, '', null, 0, get_string('cannotaggregate', 'local_gugrades'), $explain, false];
                }
            }

            // If level = 1 then check admin grades for 'top' level. TODO - Ticket number?
            if ($level == 1) {
                if ($admingrade = $this->admin_grades_level1($items)) {
                    [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
                    $explain = $this->get_explain();

                    return [0, 0, $admingrade, $displaygrade, 0, '', $explain, false];
                }
            }
        }

        // Record normalised weights.
        \local_gugrades\aggregation::record_weights($items, $userid);

        // Check for zero weights with whatever items remain.
        if ($this->weight_error($items, $aggmethod)) {
            $explain = get_string('explain_zeroweights', 'local_gugrades');
            return [null, null, '', null, 0, get_string('cannotaggregate', 'local_gugrades'), $explain, false];
        }

        // Now call the appropriate aggregation function to do the sums.
        $aggregatedgrade = call_user_func([$this, $aggfunction], $items);

        // If this is a scale convert the numeric grade to the appropriate.
        if (($atype == \local_gugrades\GRADETYPE_SCHEDULEA) || ($atype == \local_gugrades\GRADETYPE_SCHEDULEB)) {
            [$convertedgrade, $convertedgradevalue] = $this->convert($aggregatedgrade, $atype);

            // Should we pass back convertedgradevalue or aggregatedgrade (see MGU-821).
            $parentgrade = $this->get_grade_for_parent($aggregatedgrade, $convertedgradevalue);

            // How do we want to display this?
            $displaygrade = $this->format_displaygrade(
                $convertedgrade,
                $aggregatedgrade,
                $convertedgradevalue,
                $level
            );

            // MGU-1442: Is this nursing? Do we need to apply an X?
            if (($level == 1) && ($isnursingug || $isnursingpgt) && $conditionmet) {

                $displaygrade = 'X' . $displaygrade;
            }

            $explain = get_string('explain_schedule', 'local_gugrades');

            return [$parentgrade, $aggregatedgrade, '', $displaygrade, 0, '', $explain, false];
        }

        // Return points grades.
        $explain = get_string('explain_points', 'local_gugrades');

        return [$aggregatedgrade, $aggregatedgrade, '', $aggregatedgrade, 0, '', $explain, false];
    }

    /**
     * Check nursing conditions
     * @param array $items
     * @param int $lowestgrade
     * @return bool
     */
    protected function nursing_check(array $items, int $lowestgrade): bool {

        // Is there an NS (NOSUBMISSION) in the array?
        if (in_array('NOSUBMISSION', array_column($items, 'admingrade'))) {
            return true;
        }

        // Any valid grades < $lowestgrade?
        foreach ($items as $item) {

            // Skip items that are missing a grade or have an admingrade.
            if ($item->grademissing || !empty($item->admingrade) || $item->iscategory) {
                continue;
            }

            if ($item->grade < $lowestgrade) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get explain string
     * @return string
     */
    public function get_explain() {
        return $this->explain;
    }

    /**
     * Get list of valid userids for gradeitemid
     * @param int $courseid
     * @param int $gradeitemid
     * @return array
     */
    private function get_valid_userids(int $courseid, int $gradeitemid) {

        // Don't try to remember grades if unit test.
        $isunittest = \local_gugrades\api::is_unit_test();

        // Putting lists of valid users into global space.
        // Not ideal, but better than looking them up millions of times.
        global $GUGRADES_VALIDUSERS;

        if (!is_array($GUGRADES_VALIDUSERS)) {
            $GUGRADES_VALIDUSERS = [];
        }

        if (!$isunittest && array_key_exists($gradeitemid, $GUGRADES_VALIDUSERS)) {
            return $GUGRADES_VALIDUSERS[$gradeitemid];
        } else {
            $activity = \local_gugrades\users::activity_factory($gradeitemid, $courseid, 0);
            $userids = $activity->get_user_ids();
            $GUGRADES_VALIDUSERS[$gradeitemid] = $userids;
            return $userids;
        }
    }

    /**
     * Check availability
     * TODO: Need to cache (or something) getting the lists of users. Can't do that for every user :(
     * @param array $items
     * @param int $userid
     * @return array
     */
    public function availability(array $items, int $userid) {

        $filtereditems = [];
        foreach ($items as $id => $item) {
            // MGU-1349 (et al): If this is a category, then it must be available
            // UNLESS its $notavailable flag is true.
            if ($item->iscategory) {
                if (!$item->notavailable) {
                    $filtereditems[$id] = $item;
                }
                continue;
            }

            // Ordinary items must be checked against list of valid users.
            $userids = $this->get_valid_userids($this->courseid, $item->itemid);
            if (empty($userids)) {
                continue;
            }

            // MGU-1351
            // A missing grade that is tagged as a resit is a proxy for not available.
            if (\local_gugrades\grades::is_resit_gradeitem($item->itemid) && $item->grademissing) {
                continue;
            }

            // Check user can 'see' this gradeitem.
            $available = in_array($userid, $userids);
            if ($available) {
                $filtereditems[$id] = $item;
            }
        }

        return $filtereditems;
    }

    /**
     * If there are no items left after availability checked
     * (i.e. ALL items turned out to be not available) then
     * determine what the category total should be.
     * This is mostly an issue for Level 1
     * @param int $level
     * @return array
     */
    public function all_unavailable_total(int $level) {

        $strnotavailable = get_string('notavailable', 'local_gugrades');

        // MGU-1349: At level 1, return CW and no error.
        if ($level == 1) {
            $admingrade = 'CREDITWITHHELD';
            [$displaygrade, ] = \local_gugrades\admingrades::get_displaygrade_from_name($admingrade);
            return [$admingrade, '', $displaygrade];
        } else {
            // If not L1, then the total is just 'Not available'.
            return ['', $strnotavailable, $strnotavailable];
        }
    }

    /**
     * Pre-process grades for aggregation.
     * Allows grades to be 'normalised' prior to aggregation.
     * @param array $items
     * @param bool $isweighted
     * @param int $level
     * @return array
     */
    public function pre_process_items(array $items, bool $isweighted, int $level) {

        // Drop any GOODCAUSE_NR.
        // UNLESS, level 1, any are weight>10% in which case grade is ECC. (MGU-1387, rule 4)
        $newitems = [];
        foreach ($items as $item) {
            $weight = $isweighted ? $item->weight * 100 : 100 / count($items);
            if ($item->admingrade != 'GOODCAUSE_NR') {
                $newitems[] = $item;
            } else {
                if (($level == 1) && ($weight > 10)) {
                    return ['GOODCAUSE_NR', []];
                }
                $this->mv0found = true;
            }
        }

        // If this has resulted in ALL items being removed then the
        // result is also GOODCAUSE_NR.
        if (count($newitems) == 0) {
            $this->explain = get_string('explain_allmv0', 'local_gugrades');
            $agrade = 'GOODCAUSE_NR';
        } else {
            $agrade = false;
        }

        return [$agrade, $newitems];
    }

    /**
     * Getter for mv0found
     * @return bool
     */
    public function get_mv0found() {
        return $this->mv0found;
    }

    /**
     * Drop lowest n items from grades
     * @param array $items
     * @param int $n
     * @return [array, array]
     */
    public function droplow(array $items, int $n) {

        // If we're not going to return anything, anyway...
        if ($n >= count($items)) {
            return [[], $items];
        }

        // Sort items by grade (ascending).
        usort($items, function ($g1, $g2) {

            // Usort only likes integers, so the 100* is required.
            $normalised1 = 100 * $g1->grade / $g1->grademax;
            $normalised2 = 100 * $g2->grade / $g2->grademax;

            // If either are admingrades, then just make them -1
            // Such that they are sorted below zero. MGU-1116.
            $normalised1 = empty($g1->admingrade) ? $normalised1 : -1;
            $normalised2 = empty($g2->admingrade) ? $normalised2 : -1;

            return $normalised1 - $normalised2;
        });

        $notdropitems = array_slice($items, $n);

        // Find gradeitems that are being dropped
        // (So that they can be marked as such).
        $droppeditems = array_slice($items, 0, $n);

        return [$notdropitems, $droppeditems];
    }

    /**
     * Admingrade check done BEFORE we check that all grades are
     * available
     * NOTE: Order is critical (see spec)
     * (Please excuse inefficient coding)
     * @param int $level
     * @param array $items
     * @return string
     */
    public function admin_grade_precheck(int $level, array $items) {

        // Any '07' admin grades means aggregation is 07.
        foreach ($items as $item) {
            if ($item->admingrade == 'DEFERRED') {
                $this->explain = get_string('explain_any07', 'local_gugrades');
                return 'DEFERRED';
            }
        }

        // If there is a mix of MV and NS then aggregation is MV (Good Cause Withheld)
        // See MGU-1009
        // Replaced by: MGU-1210
        // Level 1 only.
        if ($level == 1) {
            $nsfound = false;
            $mvfound = false;
            foreach ($items as $item) {
                if ($item->admingrade == 'GOODCAUSE_FO') {
                    $mvfound = true;
                }
                if ($item->admingrade == 'NOSUBMISSION') {
                    $nsfound = true;
                }
            }
            if ($nsfound && $mvfound) {
                $this->explain = get_string('explain_mixmvns', 'local_gugrades');
                return 'GOODCAUSE_FO';
            }
        }

        // Any 'INTERRUPTIONOFSTUDIES' admin grades potentially means aggregation is IS (MGU-1002).
        foreach ($items as $item) {
            if ($item->admingrade == 'INTERRUPTIONOFSTUDIES') {
                $this->explain = get_string('explain_anyis', 'local_gugrades');
                return 'INTERRUPTIONOFSTUDIES';
            }
        }

        // Any 'MV' admin grades means aggregation is MV.
        foreach ($items as $item) {
            if ($item->admingrade == 'GOODCAUSE_FO') {
                $this->explain = get_string('explain_anymv', 'local_gugrades');
                return 'GOODCAUSE_FO';
            }
        }

        // If ALL grades are NS, then return NS (MGU-1191) at level 2
        // At Level 1, drop through and just return H
        $allns = true;
        foreach ($items as $item) {
            if ($item->admingrade != 'NOSUBMISSION') {
                $allns = false;
            }
        }
        if ($allns) {
            // MGU-1216.
            if ($level > 1) {
                $this->explain = get_string('explain_allnslevel2', 'local_gugrades');
                return 'NOSUBMISSION';
            }
        }

        // No admin grade found.
        return '';
    }

    /**
     * Logic for admingrades in >= level2, TODO Ticket?
     * Works out if aggregated grade is some admin grade
     * Returns this or empty string if not.
     *
     * @param array $items
     * @return string
     */
    public function admin_grades_level1(array $items) {

        // TODO: Is this needed?

        return '';
    }

    /**
     * MGU-1351:
     * Handle resit grade.
     * - If there is only one item, then that's the grade (or admingrade)
     * - If there are any admingrade then the resit grade is the grade (or admingrade)
     * - If there are two valid grades then aggregation completes 'normally'
     * Return the item that will be aggregated result or null if not.
     * Return the appropriate explain.
     * @param array $items
     * @param int $resititemid
     * @return array  [rawgrade, admingrade, explain]
     */
    public function resit(array $items, $resititemid) {

        // Make very sure array is index 0.
        $items = array_values($items);
        $maxgrade = $this->get_max_grade();

        $explain = '';

        // If only one item...
        if (count($items) == 1) {
            $explain = get_string('explain_resitoneitem', 'local_gugrades');
            $item = $items[0];

            // Normalise grade.
            $norm = $maxgrade * $item->grade / $item->grademax;
            return [$norm, $item->admingrade, $explain];
        }

        // Which item (index) is the resit item.
        $resitindex = false;
        $firstindex = false;
        foreach ($items as $index => $item) {
            if ($item->itemid == $resititemid) {
                $resitindex = $index;
            }
            if ($item->itemid != $resititemid) {
                $firstindex = $index;
            }
        }
        if ($resitindex === false) {
            throw new \moodle_exception('Resit itemid was not in list of aggregation items. ResititemID = ' . $resititemid);
        }

        // If the resit grade is NS/NS0 then the first grade is taken,
        // unless it too is admin. (This is 'rule 8').
        $resit = $items[$resitindex];
        if ($resit->admingrade == 'NOSUBMISSION') {
            $first = $items[$firstindex];
            if ($first->admingrade == '') {
                $explain = get_string('explain_resitnosubmission', 'local_gugrades');

                // Normalise grade.
                $norm = $maxgrade * $first->grade / $first->grademax;
                return [$norm, $first->admingrade, $explain];
            }
        }

        // If there are any admin grades, then the resit item is the result.
        if ($items[0]->admingrade || $items[1]->admingrade) {
            $explain = get_string('explain_resitadmingrade', 'local_gugrades');
            $item = $items[$resitindex];

            // Normalise grade.
            $norm = $maxgrade * $item->grade / $item->grademax;
            return [$norm, $item->admingrade, $explain];
        }

        // In which case, we must have two grades and can allow normal aggregation to procede.
        // Signified by empty explain.
        return [0, '', ''];
    }


    /**
     * Dummy completion
     * @param array $items
     * @param bool $weighted
     * @return int
     */
    public function completion(array $items, bool $weighted) {
        return 0;
    }

    /**
     * Round to a specific number of decimal places.
     * Spec says 5, but giving the opportunity to change.
     * @param float $value
     * @return float
     */
    public function round_float(float $value) {

        // MGU-1236.
        return round($value, 5, PHP_ROUND_HALF_DOWN);
    }

    /**
     * Does aggregation strategy allow specification of weights?
     * NOTE: simple weighted mean, does NOT use weights
     * @param int $aggregationid
     * @return bool
     */
    public function is_strategy_weighted(int $aggregationid) {

        return $aggregationid == \GRADE_AGGREGATE_WEIGHTED_MEAN;
    }

    /**
     * Check for weights that sum to zero if weighted strategy
     * @param array $items
     * @param int $aggregationid
     * @return boolean
     */
    public function weight_error(array $items, int $aggregationid) {
        if (!$this::is_strategy_weighted($aggregationid)) {
            return false;
        }

        $weight = 0;
        foreach ($items as $item) {
            $weight += $item->weight;
        }

        // Allow some 'slack'.
        return $weight < 0.001;
    }

    //
    // Following are functions for all the basic aggregation strategies. These mostly
    // replicate what core Moodle Gradebook does and are as specified in the Moodle docs.
    //

    /**
     * Choose aggregation strategy method
     * And detect if weighted
     * @param int $aggregationid
     * @return [string, boolean]
     */
    public function strategy_factory(int $aggregationid) {

        // Array defines which aggregation type calls which function.
        $lookup = [
            \GRADE_AGGREGATE_MEAN => 'mean',
            \GRADE_AGGREGATE_MEDIAN => 'median',
            \GRADE_AGGREGATE_MIN => 'min',
            \GRADE_AGGREGATE_MAX => 'max',
            \GRADE_AGGREGATE_MODE => 'mode',
            \GRADE_AGGREGATE_WEIGHTED_MEAN => 'weighted_mean',
            \GRADE_AGGREGATE_WEIGHTED_MEAN2 => 'simple_weighted_mean',
            \GRADE_AGGREGATE_SUM => 'mean', // Natural does the same thing as mean.
        ];
        if (array_key_exists($aggregationid, $lookup)) {
            $agf = $lookup[$aggregationid];
        } else {
            throw new \moodle_exception('Unknown or unsupported aggregation strategy. Aggregation ID ' . $aggregationid);
        }

        $weighted = $aggregationid = \GRADE_AGGREGATE_WEIGHTED_MEAN;

        return ["strategy_" . $agf, $weighted];
    }

    /**
     * Establish the maximum grade according to $atype (the aggregated type)
     */
    protected function get_max_grade() {
        if (($this->atype == \local_gugrades\GRADETYPE_SCHEDULEA) || ($this->atype == \local_gugrades\GRADETYPE_SCHEDULEB)) {
            return 22;
        }
        if ($this->atype == \local_gugrades\GRADETYPE_POINTS) {
            return 100;
        }

        // If we get here, $atype was presumably ERROR (or something we don't know about).
        throw new \moodle_exception('Unhandled aggregation type - ' . $this->atype);
    }

    /**
     * Strategy - mean of grades
     * @param array $items
     * @return float
     */
    public function strategy_mean(array $items) {
        $sum = 0.0;
        $count = 0;
        $maxgrade = $this->get_max_grade();
        foreach ($items as $item) {
            $sum += $item->grade / $item->grademax;
            $count++;
        }

        return $this->round_float($sum * $maxgrade / $count);
    }

    /**
     * Strategy - weighted mean of grades
     * @param array $items
     * @return float
     */
    public function strategy_weighted_mean(array $items) {
        $sum = 0.0;
        $count = 0;
        $sumweights = 0;
        $maxgrade = $this->get_max_grade();
        foreach ($items as $item) {
            $sum += $item->weight * $item->grade / $item->grademax;
            $sumweights += $item->weight;
            $count++;
        }

        return $this->round_float($sum * $maxgrade / $sumweights);
    }

    /**
     * Strategy - simple weighted mean of grades
     * (Essentially - sum of grades divided by sum of max grades)
     * @param array $items
     * @return float
     */
    public function strategy_simple_weighted_mean(array $items) {
        $sum = 0.0;
        $sumgrademax = 0;
        $maxgrade = $this->get_max_grade();
        foreach ($items as $item) {
            $sum += $item->grade;
            $sumgrademax += $item->grademax;
        }

        return $this->round_float($maxgrade * $sum / $sumgrademax);
    }

    /**
     * Strategy - minimum grade
     * Note that the normalised percentage grade is returned
     * @param array $items
     * @return float
     */
    public function strategy_min(array $items) {
        $grades = [];
        $maxgrade = $this->get_max_grade();
        foreach ($items as $item) {
            $norm = $maxgrade * $item->grade / $item->grademax;
            $grades[] = $norm;
        }

        return $this->round_float(min($grades));
    }

    /**
     * Strategy - maximum grade
     * Note that the normalised percentage grade is returned
     * @param array $items
     * @return float
     */
    public function strategy_max(array $items) {
        $grades = [];
        $maxgrade = $this->get_max_grade();
        foreach ($items as $item) {
            $norm = $maxgrade * $item->grade / $item->grademax;
            $grades[] = $norm;
        }

        return $this->round_float(max($grades));
    }

    /**
     * Strategy - median grade
     * Note that the normalised percentage grade is returned
     * @param array $items
     * @return float
     */
    public function strategy_median(array $items) {
        $grades = [];
        $maxgrade = $this->get_max_grade();
        foreach ($items as $item) {
            $norm = $maxgrade * $item->grade / $item->grademax;
            $grades[] = $norm;
        }

        sort($grades);

        // If odd number of grades it's just the middle value.
        $medianindex = intval(count($grades) / 2);
        $roundindex = intval(round($medianindex, PHP_ROUND_HALF_UP));

        if ($roundindex == 0) {
            return $this->round_float($grades[0]);
        } else if ($roundindex != $medianindex) {
            return $this->round_float($grades[$medianindex]);
        } else {
            // It's the mean of the two middle values.
            $midh = $grades[$roundindex];
            $midl = $grades[$roundindex - 1];

            return $this->round_float(($midh + $midl) / 2);
        }
    }

    /**
     * Strategy - median mode
     * Note that the normalised percentage grade is returned
     * This is rounded to int - which makes sense to me!
     * @param array $items
     * @return float
     */
    public function strategy_mode(array $items) {
        $grades = [];
        $maxgrade = $this->get_max_grade();
        foreach ($items as $item) {
            $norm = round($maxgrade * $item->grade / $item->grademax, PHP_ROUND_HALF_DOWN);
            $grades[] = (int)$norm;
        }

        // Witchcraft!
        $values = array_count_values($grades);
        $mode = array_search(max($values), $values);

        return $this->round_float($mode);
    }

    /**
     * Convert numeric 0-22 to Schedule A
     * @param float $rawgrade
     * @return [string, int]
     */
    protected function convert_schedulea(float $rawgrade) {
        $schedulea = \local_gugrades\mapping\schedulea::get_map();

        // This MATTERS - round the float rawgrade to an integer
        // "15.5 and all higher values less than 16.5 should become 16
        // [Guide to code of assessment].
        $grade = round($rawgrade, 0, PHP_ROUND_HALF_UP);

        if (!array_key_exists($grade, $schedulea)) {
            throw new \moodle_exception('Raw grade out of valid range - ' . $rawgrade);
        }

        return [$schedulea[$grade], $grade];
    }

    /**
     * Convert numeric 0-22 to Schedule B
     * @param float $rawgrade
     * @return [string, int]
     */
    protected function convert_scheduleb(float $rawgrade) {
        return \local_gugrades\mapping\scheduleb::convert($rawgrade);
    }

    /**
     * Convert float grade to Schedule A / B
     * @param float $rawgrade
     * @param string $atype
     * @return [string, int]
     */
    public function convert($rawgrade, $atype) {
        if ($atype == \local_gugrades\GRADETYPE_SCHEDULEA) {
            return $this->convert_schedulea($rawgrade);
        } else if ($atype == \local_gugrades\GRADETYPE_SCHEDULEB) {
            return $this->convert_scheduleb($rawgrade);
        } else {
            throw new \moodle_exception('Invalid atype - ' . $atype);
        }
    }

    /**
     * Which grade is 'passed up' from aggregation when converting to scale
     * The 'raw' grade or the graded point after conversion?
     * This is here in case there are different views about this
     * See MGU-821
     * @param float $rawgrade
     * @param int $gradepoint
     * @return float|int
     */
    public function get_grade_for_parent(float $rawgrade, int $gradepoint) {

        // Finger in the air - and use $gradepoint. If you want raw grade
        // just return the other value.
        // Decided - Grade Point it is.
        return $gradepoint;
    }

    /**
     * Format displaygrade for Schedule A / B
     * @param string $convertedgrade
     * @param float $rawgrade
     * @param float $gradepoint
     * @param int $level
     * @return string
     */
    public function format_displaygrade(string $convertedgrade, float $rawgrade, float $gradepoint, int $level) {

        // If >level 1, then we always return the combination (no 75% rule).
        if ($level > 1) {
            return $convertedgrade;
        }

        return $convertedgrade . " ($rawgrade)";
    }
}
