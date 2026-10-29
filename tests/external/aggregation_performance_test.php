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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Opt-in synthetic aggregation benchmark. Never uses live course data.
 *
 * @package local_gugrades
 * @copyright 2026
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_gugrades\external;

use core_external\external_api;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/gugrades/tests/external/gugrades_aggregation_testcase.php');

/**
 * Measure scaling without introducing timing-dependent assertions.
 *
 * @group aggregation_performance
 */
final class aggregation_performance_test extends gugrades_aggregation_testcase {
    /**
     * Avoid even creating fixtures in the ordinary test suite.
     */
    protected function setUp(): void {
        if (getenv('GUGRADES_BENCHMARK') !== '1') {
            $this->markTestSkipped('Set GUGRADES_BENCHMARK=1 to run the synthetic benchmark.');
        }
        parent::setUp();
    }

    /**
     * Fixed workloads at increasing student counts.
     * @return array
     */
    public static function workloads(): array {
        $sizes = getenv('GUGRADES_BENCHMARK_SIZES');
        $sizes = $sizes === false ? ['100', '400', '800'] : explode(',', $sizes);
        $cases = [];
        foreach ($sizes as $size) {
            if (!ctype_digit($size) || (int)$size < 2 || (int)$size > 800) {
                throw new \InvalidArgumentException('Benchmark sizes must be integers between 2 and 800.');
            }
            foreach (['original', 'from2026', 'from2026-dual'] as $scenario) {
                $cases[$scenario . '-' . $size] = [(int)$size, $scenario];
            }
        }
        return $cases;
    }

    /**
     * Measure one phase, excluding fixture creation and correctness assertions.
     * @param string $phase
     * @param int $students
     * @param string $scenario
     * @param callable $operation
     * @return mixed
     */
    private function measure(string $phase, int $students, string $scenario, callable $operation): mixed {
        global $DB;

        $reads = $DB->perf_get_reads();
        $writes = $DB->perf_get_writes();
        $dbtime = $DB->perf_get_queries_time();
        $memory = memory_get_usage(true);
        if (function_exists('memory_reset_peak_usage')) {
            memory_reset_peak_usage();
        }
        $trace = getenv('GUGRADES_BENCHMARK_TRACE_PHASE') === $phase;
        $traceoptions = [];
        if ($trace) {
            if (!function_exists('xdebug_start_trace') || !in_array('trace', xdebug_info('mode'))) {
                throw new \coding_exception('Benchmark tracing requires Xdebug with XDEBUG_MODE=trace.');
            }
            if ($students > 100) {
                throw new \coding_exception('Function traces are limited to 100 students; use untraced benchmarks for larger runs.');
            }
            foreach (['xdebug.var_display_max_depth' => '0', 'xdebug.var_display_max_children' => '0',
                    'xdebug.var_display_max_data' => '2048'] as $option => $value) {
                $traceoptions[$option] = ini_get($option);
                ini_set($option, $value);
            }
            $tracepath = '/tmp/gugrades-' . $scenario . '-' . $students . '-' . $phase;
            xdebug_start_trace($tracepath, XDEBUG_TRACE_COMPUTERIZED);
        }
        $start = microtime(true);
        try {
            $result = $operation();
        } finally {
            if ($trace) {
                $tracefile = xdebug_stop_trace();
                foreach ($traceoptions as $option => $value) {
                    ini_set($option, $value);
                }
                fwrite(STDOUT, "\nGUGRADES_TRACE " . $tracefile . "\n");
            }
        }
        $seconds = microtime(true) - $start;
        $metrics = [
            'scenario' => $scenario,
            'students' => $students,
            'phase' => $phase,
            'seconds' => round($seconds, 4),
            'reads' => $DB->perf_get_reads() - $reads,
            'writes' => $DB->perf_get_writes() - $writes,
            'db_seconds' => round($DB->perf_get_queries_time() - $dbtime, 4),
            'peak_mib' => round(memory_get_peak_usage(true) / 1048576, 2),
            'peak_extra_mib' => round(max(0, memory_get_peak_usage(true) - $memory) / 1048576, 2),
            'phpunit_cache_bypass' => true,
        ];
        if (is_array($result) && isset($result['users'])) {
            $metrics['response_bytes'] = strlen(json_encode($result, JSON_THROW_ON_ERROR));
        }
        fwrite(STDOUT, "\nGUGRADES_BENCHMARK " . json_encode($metrics, JSON_THROW_ON_ERROR) . "\n");
        return $result;
    }

    /**
     * Write a revision using the normal history/latest-grade path.
     * @param int $userid
     * @param int $itemid
     * @param int $grade
     */
    private function write_result(int $userid, int $itemid, int $grade): void {
        \local_gugrades\grades::write_grade(
            courseid: $this->course->id, gradeitemid: $itemid, userid: $userid,
            admingrade: '', rawgrade: $grade, convertedgrade: $grade, displaygrade: (string)$grade,
            weightedgrade: 0, gradetype: 'FIRST', other: '', iscurrent: true,
            iserror: false, auditcomment: 'Synthetic benchmark', ispoints: false,
        );
    }

    /**
     * Benchmark five categories, twelve assessments and two revisions per assessment.
     *
     * @dataProvider workloads
     * @param int $students
     * @param string $scenario
     */
    public function test_scaling(int $students, string $scenario): void {
        global $DB;

        $dual = $scenario === 'from2026-dual';
        $DB->set_field('course', 'startdate', strtotime(
            $scenario === 'original' ? '2025-01-01' : '2027-01-01',
        ), ['id' => $this->course->id]);
        $root = \grade_category::fetch_course_category($this->course->id);
        $children = [];
        for ($group = 1; $group <= 4; $group++) {
            $attempts = [];
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $attempts[] = (object)[
                    'name' => "Group $group attempt $attempt", 'category' => false, 'type' => 'schedulea',
                    'weight' => 1 / 3,
                ];
            }
            $children[] = (object)[
                'name' => "Group $group", 'category' => true, 'weight' => 0.25,
                'aggregation' => $group === 1 ? 'max' : 'weighted_mean',
                'droplow' => $dual && $group === 4 ? 1 : 0,
                'children' => $attempts,
            ];
        }
        $this->build_schema([(object)[
            'name' => 'Benchmark total', 'category' => true, 'children' => $children,
        ]], $root->id);
        $totalid = $this->get_grade_category('Benchmark total');
        $groupid = $this->get_grade_category('Group 1');
        if ($dual) {
            write_flags::execute($this->course->id, [[
                'gradecategoryid' => $groupid, 'gradeitemid' => 0, 'engexam' => false, 'resit' => true,
            ]]);
        }
        $userids = [$this->student->id, $this->student2->id];
        for ($index = 2; $index < $students; $index++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $this->course->id, 'student');
            $userids[] = $user->id;
        }
        $items = $DB->get_records('grade_items', ['courseid' => $this->course->id, 'itemtype' => 'manual']);
        $this->assertCount(12, $items);
        foreach ($userids as $index => $userid) {
            foreach ($items as $item) {
                $grade = str_ends_with($item->itemname, '1') ? 14 : 18;
                $this->write_result($userid, $item->id, $grade - 2);
                $this->write_result($userid, $item->id, $grade);
            }
            if ($dual && $index % 10 === 0) {
                $DB->insert_record('local_gugrades_altered_weight', (object)[
                    'courseid' => $this->course->id, 'categoryid' => $totalid,
                    'gradeitemid' => \local_gugrades\grades::get_gradeitemid_from_gradecategoryid($groupid),
                    'userid' => $userid, 'weight' => 0.3, 'timealtered' => time(),
                ]);
            }
        }
        foreach (['initial_recalculation', 'unchanged_recalculation'] as $phase) {
            $this->measure($phase, $students, $scenario,
                fn() => \local_gugrades\api::recalculate($this->course->id, $totalid));
        }
        $this->assertSame($students * 5, $DB->count_records('local_gugrades_grade', [
            'courseid' => $this->course->id, 'gradetype' => 'CATEGORY', 'iscurrent' => 1, 'iserror' => 0,
        ]));
        $this->assertSame($dual ? $students * 2 : $students, $DB->count_records('local_gugrades_grade', [
            'courseid' => $this->course->id, 'gradetype' => 'CATEGORY1', 'iscurrent' => 1, 'iserror' => 0,
        ]));
        foreach (['page_after_recalculation', 'repeated_page'] as $phase) {
            $page = $this->measure($phase, $students, $scenario, function () use ($totalid) {
                return external_api::clean_returnvalue(get_aggregation_page::execute_returns(),
                    get_aggregation_page::execute($this->course->id, $totalid, '', '', 0, false));
            });
            $this->assertCount($students, $page['users']);
            $this->assertTrue($page['dualgrades']);
            unset($page);
        }
        $firstitemid = $this->get_gradeitemid('Group 1 attempt 1');
        $this->measure('single_student_edit', $students, $scenario, function () use ($totalid, $firstitemid) {
            $this->write_result($this->student->id, $firstitemid, 10);
            \local_gugrades\api::recalculate($this->course->id, $totalid, $this->student->id);
        });
        $groupitemid = \local_gugrades\grades::get_gradeitemid_from_gradecategoryid($groupid);
        $this->assertEquals(18, $DB->get_field('local_gugrades_grade', 'convertedgrade', [
            'gradeitemid' => $groupitemid, 'userid' => $this->student->id, 'gradetype' => 'CATEGORY', 'iscurrent' => 1,
        ], MUST_EXIST));
        if ($dual) {
            $this->assertEquals(10, $DB->get_field('local_gugrades_grade', 'convertedgrade', [
                'gradeitemid' => $groupitemid, 'userid' => $this->student->id, 'gradetype' => 'CATEGORY1', 'iscurrent' => 1,
            ], MUST_EXIST));
        }
    }
}
