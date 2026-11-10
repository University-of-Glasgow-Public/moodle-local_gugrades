<?php
// This file is part of Moodle - https://moodle.org/
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
 * Capture the actual numeric strategy inputs only for an Explain request.
 * @package local_gugrades
 * @copyright 2026 Howard Miller
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait calculation_trace {
    /** @var string Numeric strategy selected by the regulation. */
    public string $calculationstrategy = '';

    /** @var array Final strategy inputs, after availability and regulation filtering. */
    public array $calculationitems = [];

    /** @var float|null Numeric result before category caps. */
    public ?float $calculationvalue = null;

    /** @var array Cap applications in execution order. */
    public array $calculationcaps = [];

    /** @var array<int, string> Reasons why individual inputs were excluded. */
    public array $calculationexclusions = [];

    /**
     * Record availability exclusions using the regulation's actual filtered list.
     * @param array $items
     * @param int $userid
     * @return array
     */
    public function availability(array $items, int $userid) {
        $available = parent::availability($items, $userid);
        $included = array_fill_keys(array_column($available, 'itemid'), true);
        foreach ($items as $item) {
            if (!isset($included[$item->itemid])) {
                $this->calculationexclusions[$item->itemid] = get_string('notavailable', 'local_gugrades');
            }
        }
        return $available;
    }

    /**
     * Record grades removed by the actual drop-lowest operation.
     * @param array $items
     * @param int $n
     * @return array
     */
    public function droplow(array $items, int $n) {
        $result = parent::droplow($items, $n);
        foreach ($result[1] as $item) {
            $this->calculationexclusions[$item->itemid] = get_string('dropped', 'local_gugrades');
        }
        return $result;
    }

    /**
     * Save the actual inputs and result without changing strategy selection.
     * @param string $strategy
     * @param array $items
     * @param float $value
     * @return float
     */
    private function record_calculation(string $strategy, array $items, float $value): float {
        if ($this->calculationvalue === null) {
            $this->calculationstrategy = $strategy;
            $this->calculationitems = array_map(static fn($item) => clone $item, $items);
            $this->calculationvalue = $value;
        }
        return $value;
    }

    /**
     * Record the regulation's mean calculation.
     * @param array $items
     * @return float
     */
    public function strategy_mean(array $items) {
        return $this->record_calculation(__FUNCTION__, $items, parent::strategy_mean($items));
    }

    /**
     * Record the regulation's weighted mean calculation.
     * @param array $items
     * @return float
     */
    public function strategy_weighted_mean(array $items) {
        return $this->record_calculation(__FUNCTION__, $items, parent::strategy_weighted_mean($items));
    }

    /**
     * Record the regulation's simple weighted mean calculation.
     * @param array $items
     * @return float
     */
    public function strategy_simple_weighted_mean(array $items) {
        return $this->record_calculation(__FUNCTION__, $items, parent::strategy_simple_weighted_mean($items));
    }

    /**
     * Record the regulation's minimum calculation.
     * @param array $items
     * @return float
     */
    public function strategy_min(array $items) {
        return $this->record_calculation(__FUNCTION__, $items, parent::strategy_min($items));
    }

    /**
     * Record the regulation's maximum calculation.
     * @param array $items
     * @return float
     */
    public function strategy_max(array $items) {
        return $this->record_calculation(__FUNCTION__, $items, parent::strategy_max($items));
    }

    /**
     * Record the regulation's median calculation.
     * @param array $items
     * @return float
     */
    public function strategy_median(array $items) {
        return $this->record_calculation(__FUNCTION__, $items, parent::strategy_median($items));
    }

    /**
     * Record the regulation's mode calculation.
     * @param array $items
     * @return float
     */
    public function strategy_mode(array $items) {
        return $this->record_calculation(__FUNCTION__, $items, parent::strategy_mode($items));
    }
}
