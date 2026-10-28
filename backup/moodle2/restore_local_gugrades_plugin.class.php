<?php
// This file is part of Moodle
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
 * Defines restore_local_gugrades class.
 *
 * @package     local_gugrades
 * @author      Howard Miller
 * @copyright   Howard Miller
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore plugin class.
 *
 * @package    local_gugrades
 * @author     Howard Miller
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_gugrades_plugin extends restore_local_plugin {
    /**
     * Return the paths to be handled by the plugin (course level)
     */
    protected function define_course_plugin_structure() {

        $paths = [];
        $coursepath = $this->get_pathfor('/');

        $paths[] = new restore_path_element('gugrades_config', $coursepath . '/gugrades_config');
        $paths[] = new restore_path_element('gugrades_map', $coursepath . '/gugrades_maps/gugrades_map');
        $paths[] = new restore_path_element('gugrades_resitcat', $coursepath . '/gugrades_resitcats/gugrades_resitcat');
        $paths[] = new restore_path_element(
            'gugrades_map_value',
            $coursepath .
            '/gugrades_map_values/gugrades_map_value/gm_values/gm_value'
        );
        $paths[] = new restore_path_element('gugrades_map_item', $coursepath . '/gugrades_map_items/gugrades_map_item');
        $paths[] = new restore_path_element('gugrades_column', $coursepath . '/gugrades_columns/gugrades_column');
        $paths[] = new restore_path_element('gugrades_grade', $coursepath . '/gugrades_grades/gugrades_grade');
        $paths[] = new restore_path_element('gugrades_weight', $coursepath . '/gugrades_weights/gugrades_weight');
        $paths[] = new restore_path_element('gugrades_audit', $coursepath . '/gugrades_audits/gugrades_audit');
        $paths[] = new restore_path_element('gugrades_hidden', $coursepath . '/gugrades_hiddens/gugrades_hidden');
        $paths[] = new restore_path_element('gugrades_resit', $coursepath . '/gugrades_resits/gugrades_resit');
        $paths[] = new restore_path_element('gugrades_note', $coursepath . '/gugrades_notes/gugrades_note');
        $paths[] = new restore_path_element('gugrades_ugpg', $coursepath . '/gugrades_ugpgs/gugrades_ugpg');
        $paths[] = new restore_path_element(
            'gugrades_columnbackup',
            $coursepath . '/gugrades_columnbackups/gugrades_columnbackup'
        );
        $paths[] = new restore_path_element(
            'gugrades_gradebackup',
            $coursepath . '/gugrades_gradebackups/gugrades_gradebackup'
        );

        return $paths;
    }

    /**
     * Process config data
     * @param array $data
     */
    public function process_gugrades_config($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->gradeitemid = 0;

        $DB->insert_record('local_gugrades_config', $data);
    }

    /**
     * Process conversion map
     * @param array $data
     */
    public function process_gugrades_map($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        $newid = $DB->insert_record('local_gugrades_map', $data);
        $this->set_mapping('gugrades_map', $oldid, $newid);
    }

    /**
     * Process resit category
     * @param array $data
     */
    public function process_gugrades_resitcat($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->courseid = $this->task->get_courseid();
        $data->gradecategoryid = $data->gradecategoryid;
        $data->gradeitemid = $data->gradeitemid;

        $DB->insert_record('local_gugrades_resit', $data);
    }

    /**
     * Process conversion map items
     * @param array $data
     */
    public function process_gugrades_map_value($data) {
        global $DB;

        $data = (object) $data;
        $data->mapid = $this->get_mappingid('gugrades_map', $data->mapid);

        $DB->insert_record('local_gugrades_map_value', $data);
    }

    /**
     * Process map items
     * @param array $data
     */
    public function process_gugrades_map_item($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->mapid = $this->get_mappingid('gugrades_map', $data->mapid);

        $DB->insert_record('local_gugrades_map_item', $data);
    }

    /**
     * Process columns
     * @param array $data
     */
    public function process_gugrades_column($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->courseid = $this->task->get_courseid();

        $newid = $DB->insert_record('local_gugrades_column', $data);
        $this->set_mapping('gugrades_column', $oldid, $newid);
    }

    /**
     * Process grades
     * @param array $data
     */
    public function process_gugrades_grade($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id ?? 0;
        unset($data->id);
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->columnid = $this->get_mappingid('gugrades_column', $data->columnid);
        $data->auditby = $this->get_mappingid('user', $data->auditby);

        $newid = $DB->insert_record('local_gugrades_grade', $data);
        if ($oldid) {
            $this->set_mapping('gugrades_grade', $oldid, $newid);
        }
    }

    /**
     * Process altered weights
     * @param array $data
     */
    public function process_gugrades_weight($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        // Negative to prevent unique key errors.
        $data->categoryid = 0 - $data->categoryid;
        $data->gradeitemid = 0 - $data->gradeitemid;

        $DB->insert_record('local_gugrades_altered_weight', $data);
    }

    /**
     * Process audit trail
     * @param array $data
     */
    public function process_gugrades_audit($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        // RELATED_MULTIPLE is not a user id, so keep it through restore.
        if ((int) $data->relateduserid === \local_gugrades\audit::RELATED_MULTIPLE) {
            $data->relateduserid = \local_gugrades\audit::RELATED_MULTIPLE;
        } else {
            $data->relateduserid = $this->get_mappingid('user', $data->relateduserid);
        }

        $DB->insert_record('local_gugrades_audit', $data);
    }

    /**
     * Process hidden items
     * @param array $data
     */
    public function process_gugrades_hidden($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->gradeitemid = 0 - $data->gradeitemid;

        $DB->insert_record('local_gugrades_hidden', $data);
    }

    /**
     * Process resits
     * @param array $data
     */
    public function process_gugrades_resit($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);

        $DB->insert_record('local_gugrades_resitrequired', $data);
    }

    /**
     * Process notes
     * @param array $data
     */
    public function process_gugrades_note($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->gradeitemid = 0 - $data->gradeitemid;

        $DB->insert_record('local_gugrades_notes', $data);
    }

    /**
     * Process the UG/PG status recorded for a student on this course.
     * @param array $data
     */
    public function process_gugrades_ugpg($data) {
        global $DB;

        $data = (object) $data;
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->userid) {
            return;
        }

        $DB->insert_record('local_gugrades_ugpg', $data);
    }

    /**
     * Process a column backup row.
     * Column ids are remapped onto the restored columns. A column that exists
     * only in the backup is created so the grades backup can still point at it.
     *
     * @param array $data
     */
    public function process_gugrades_columnbackup($data) {
        global $DB;

        $data = (object) $data;
        $oldcolumnid = $data->columnid;
        unset($data->id);
        $data->courseid = $this->task->get_courseid();

        $newcolumnid = $this->get_mappingid('gugrades_column', $oldcolumnid);
        if (!$newcolumnid) {
            $column = (object) [
                'courseid' => $data->courseid,
                'gradeitemid' => $data->gradeitemid,
                'gradetype' => $data->gradetype,
                'other' => $data->other ?? '',
                'points' => empty($data->points) ? 0 : 1,
            ];
            $newcolumnid = $DB->insert_record('local_gugrades_column', $column);
            $this->set_mapping('gugrades_column', $oldcolumnid, $newcolumnid);
        }
        $data->columnid = $newcolumnid;

        $DB->insert_record('local_gugrades_columnbackup', $data);
        $this->mark_backup_tables_included();
    }

    /**
     * Process a grade backup row.
     *
     * @param array $data
     */
    public function process_gugrades_gradebackup($data) {
        global $DB;

        $data = (object) $data;
        unset($data->id);
        $data->courseid = $this->task->get_courseid();
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->columnid = $this->get_mappingid('gugrades_column', $data->columnid);
        if (!$data->userid || !$data->columnid) {
            return;
        }
        $gradeid = $this->get_mappingid('gugrades_grade', $data->gradeid);
        $data->gradeid = $gradeid ?: 0;

        $DB->insert_record('local_gugrades_gradesbackup', $data);
        $this->mark_backup_tables_included();
    }

    /**
     * Remember that this backup file contained the grade backup tables.
     */
    protected function mark_backup_tables_included(): void {
        if (!$this->get_mappingid('gugrades_backupincluded', 1)) {
            $this->set_mapping('gugrades_backupincluded', 1, 1);
        }
    }

    /**
     * Update ids that were unknown when course.xml processed
     */
    protected function after_restore_course() {
        global $DB;

        // Map items.
        $items = $DB->get_recordset('local_gugrades_map_item', ['courseid' => $this->task->get_courseid()]);
        foreach ($items as $item) {
            $item->gradeitemid = $this->get_mappingid('grade_item', $item->gradeitemid);
            $item->gradecategoryid = $this->get_mappingid('grade_category', $item->gradecategoryid);
            $DB->update_record('local_gugrades_map_item', $item);
        }

        // Resit categories
        $resitcats = $DB->get_recordset('local_gugrades_resit', ['courseid' => $this->task->get_courseid()]);
        foreach ($resitcats as $resitcat) {
            $resitcat->gradeitemid = $this->get_mappingid('grade_item', $resitcat->gradeitemid);
            $resitcat->gradecategoryid = $this->get_mappingid('grade_category', $resitcat->gradecategoryid);
            $DB->update_record('local_gugrades_resit', $resitcat);
        }

        // Missing grade items in columns.
        $columns = $DB->get_recordset('local_gugrades_column', ['courseid' => $this->task->get_courseid()]);
        foreach ($columns as $column) {
            $column->gradeitemid = $this->get_mappingid('grade_item', $column->gradeitemid);
            $DB->update_record('local_gugrades_column', $column);
        }

        // Missing grade items in grades.
        $grades = $DB->get_recordset('local_gugrades_grade', ['courseid' => $this->task->get_courseid()]);
        foreach ($grades as $grade) {
            $grade->gradeitemid = $this->get_mappingid('grade_item', $grade->gradeitemid);
            $DB->update_record('local_gugrades_grade', $grade);
        }

        // Altered weights.
        $weights = $DB->get_recordset('local_gugrades_altered_weight', ['courseid' => $this->task->get_courseid()]);
        foreach ($weights as $weight) {
            $weight->categoryid = $this->get_mappingid('grade_category', 0 - $weight->categoryid);
            $weight->gradeitemid = $this->get_mappingid('grade_item', 0 - $weight->gradeitemid);
            $DB->update_record('local_gugrades_altered_weight', $weight);
        }

        // Audit trail.
        $audits = $DB->get_recordset('local_gugrades_audit', ['courseid' => $this->task->get_courseid()]);
        foreach ($audits as $audit) {
            $audit->gradeitemid = $this->get_mappingid('grade_item', $audit->gradeitemid);
            $DB->update_record('local_gugrades_audit', $audit);
        }

        // Hidden items.
        $hiddens = $DB->get_recordset('local_gugrades_hidden', ['courseid' => $this->task->get_courseid()]);
        foreach ($hiddens as $hidden) {
            $hidden->gradeitemid = $this->get_mappingid('grade_item', 0 - $hidden->gradeitemid);
            $DB->update_record('local_gugrades_hidden', $hidden);
        }

        // Notes.
        $notess = $DB->get_recordset('local_gugrades_notes', ['courseid' => $this->task->get_courseid()]);
        foreach ($notess as $note) {
            $note->gradeitemid = $this->get_mappingid('grade_item', 0 - $note->gradeitemid);
            $DB->update_record('local_gugrades_notes', $note);
        }

        $courseid = $this->task->get_courseid();

        // Grade items were not mapped when the backup rows were inserted.
        $columnbackups = $DB->get_recordset('local_gugrades_columnbackup', ['courseid' => $courseid]);
        foreach ($columnbackups as $columnbackup) {
            $columnbackup->gradeitemid = $this->get_mappingid('grade_item', $columnbackup->gradeitemid);
            $DB->update_record('local_gugrades_columnbackup', $columnbackup);
        }
        $columnbackups->close();

        $gradebackups = $DB->get_recordset('local_gugrades_gradesbackup', ['courseid' => $courseid]);
        foreach ($gradebackups as $gradebackup) {
            $gradebackup->gradeitemid = $this->get_mappingid('grade_item', $gradebackup->gradeitemid);
            $DB->update_record('local_gugrades_gradesbackup', $gradebackup);
        }
        $gradebackups->close();

        // Older backups have no copy of these tables. Build one from the restored grades.
        // A backup that included them is left as it was, including rows whose live grade was removed.
        if (!$this->get_mappingid('gugrades_backupincluded', 1)) {
            \local_gugrades\grades::rebuild_grade_backups($courseid);
        }
    }
}
