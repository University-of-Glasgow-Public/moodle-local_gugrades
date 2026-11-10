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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_gugrades\aggregation;

/**
 * Student-readable working produced exclusively when Explain is requested.
 * @package local_gugrades
 */
class calculation {
    /**
     * Create a regulation calculator with Explain-only instrumentation.
     * @param int $courseid
     * @param string $atype
     * @param object $regulation
     * @return object
     */
    public static function calculator(int $courseid, string $atype, object $regulation): object {
        if ($regulation->shortname() === 'from2026') {
            $calculator = new class extends \regulations_from2026\aggregate {
                use calculation_trace;

                /**
                 * Record an Engineering cap without changing its rules.
                 * @param int $courseid
                 * @param float $aggregatedgrade
                 * @param array $items
                 * @return float
                 */
                public function engineering_cap(int $courseid, float $aggregatedgrade, array $items) {
                    $grade = parent::engineering_cap($courseid, $aggregatedgrade, $items);
                    if ($grade != $aggregatedgrade) {
                        $this->calculationcaps[] = [
                            'before' => $aggregatedgrade,
                            'after' => $grade,
                            'reason' => get_string('calculationengineeringcap', 'local_gugrades'),
                        ];
                    }
                    return $grade;
                }

                /**
                 * Record the actual resit cap and its programme-specific reason.
                 * @param int $userid
                 * @param float $grade
                 * @return array
                 */
                protected function resit_cap(int $userid, float $grade): array {
                    $result = parent::resit_cap($userid, $grade);
                    if ($result[0] != $grade) {
                        $this->calculationcaps[] = [
                            'before' => $grade,
                            'after' => $result[0],
                            'reason' => $result[1],
                        ];
                    }
                    return $result;
                }
            };
        } else if ($regulation->shortname() === 'original') {
            $calculator = new class extends \regulations_original\aggregate {
                use calculation_trace;
            };
        } else {
            return $regulation->get_aggregation($courseid, $atype);
        }
        $calculator->set_data($courseid, $atype);
        return $calculator;
    }

    /**
     * Format a number without unnecessary trailing zeroes or grouping separators.
     * @param float $value
     * @return string
     */
    private static function number(float $value): string {
        if ($value == 0) {
            return '0';
        }
        return rtrim(rtrim(sprintf('%.10F', $value), '0'), '.');
    }

    /**
     * Describe the exact recorded strategy, rounding and cap applications.
     * @param object $calculator
     * @param object $category
     * @param array $input Original inputs before filtering.
     * @param object $result Actual category result.
     * @param bool $overridden
     * @return string
     */
    public static function describe(
        object $calculator,
        object $category,
        array $input,
        object $result,
        bool $overridden,
    ): string {
        if (!property_exists($calculator, 'calculationitems')) {
            return '';
        }
        $names = [];
        foreach ($category->children as $child) {
            $names[$child->itemid] = html_to_text($child->name, 0, false);
        }
        $lines = [html_to_text($category->name, 0, false)];
        if ($overridden) {
            $lines[] = get_string('calculationoverride', 'local_gugrades');
        }
        $items = $calculator->calculationitems;
        $strategy = $calculator->calculationstrategy;
        $scale = $category->atype === \local_gugrades\GRADETYPE_SCHEDULEA ||
            $category->atype === \local_gugrades\GRADETYPE_SCHEDULEB;
        $maximum = $scale ? 22 : 100;
        $used = [];
        $terms = [];
        $contributions = [];
        $values = [];
        $weighted = $strategy === 'strategy_weighted_mean';
        $mean = in_array($strategy, ['strategy_mean', 'strategy_simple_weighted_mean'], true);
        $denominator = $weighted ? array_sum(array_column($items, 'weight')) :
            ($strategy === 'strategy_simple_weighted_mean' ? array_sum(array_column($items, 'grademax')) : count($items));
        foreach ($items as $item) {
            $used[$item->itemid] = true;
            $values[] = self::number($item->grade * $maximum / $item->grademax);
            if (($weighted || $mean) && $denominator > 0 && $item->grademax > 0) {
                $grade = $item->grade * $maximum / $item->grademax;
                $weight = $weighted ? $item->weight / $denominator :
                    ($strategy === 'strategy_simple_weighted_mean' ? $item->grademax / $denominator : 1 / $denominator);
                $contribution = $grade * $weight;
                $terms[] = self::number($grade) . ' * ' . self::number($weight);
                $contributions[] = self::number($contribution);
                $lines[] = get_string('calculationitem', 'local_gugrades', (object) [
                    'name' => $names[$item->itemid] ?? $item->itemid,
                    'grade' => self::number($item->grade),
                    'maximum' => self::number($item->grademax),
                    'weight' => self::number(100 * $weight),
                    'contribution' => self::number($contribution),
                ]);
            } else {
                $lines[] = ($names[$item->itemid] ?? $item->itemid) . ': ' . self::number($item->grade) . '.';
            }
        }
        if ($calculator->calculationvalue !== null) {
            $value = self::number($calculator->calculationvalue);
            $working = $terms ? implode(' + ', $terms) . ' = ' . implode(' + ', $contributions) . ' = ' . $value : $value;
            if (in_array($strategy, ['strategy_min', 'strategy_max', 'strategy_median'], true)) {
                $working = substr($strategy, strlen('strategy_')) . '(' . implode(', ', $values) . ') = ' . $value;
            }
            $lines[] = get_string('calculationunrounded', 'local_gugrades', $working);
            if ($scale) {
                [$band, $point] = $calculator->convert($calculator->calculationvalue, $category->atype);
                $lines[] = get_string(
                    $calculator->calculationcaps ? 'calculationuncappedrounded' : 'calculationrounded',
                    'local_gugrades',
                    self::number($point) . ' = ' . $band,
                );
            }
        } else if (!empty($result->selection)) {
            $selection = $result->selection;
            $used[$selection->itemid] = true;
            $lines[] = get_string('calculationselected', 'local_gugrades', (object) [
                'name' => $names[$selection->itemid] ?? $selection->itemid,
                'grade' => $selection->display,
            ]);
        }
        foreach ($calculator->calculationcaps as $cap) {
            $lines[] = get_string('calculationcap', 'local_gugrades', (object) [
                'before' => self::number($cap['before']),
                'after' => self::number($cap['after']),
                'reason' => html_to_text(\local_gugrades\admingrades::replace_explain($cap['reason']), 0, false),
            ]);
        }
        if ($used) {
            foreach ($input as $item) {
                if (!isset($used[$item->itemid])) {
                    $name = $names[$item->itemid] ?? $item->itemid;
                    $lines[] = isset($calculator->calculationexclusions[$item->itemid]) ?
                        $name . ': ' . $calculator->calculationexclusions[$item->itemid] . '.' :
                        get_string('calculationexcluded', 'local_gugrades', $name);
                }
            }
        }
        if ($result->error || ($calculator->calculationvalue === null && !$overridden)) {
            $lines[] = html_to_text($result->explain ?: $result->error, 0, false);
        }
        $display = $result->display !== null && $result->display !== '' ? $result->display : $result->error;
        $lines[] = get_string('calculationfinal', 'local_gugrades', $display);
        return implode("\n", $lines);
    }
}
