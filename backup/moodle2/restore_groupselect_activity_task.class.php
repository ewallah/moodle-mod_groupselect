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
 * Class definition for the restore activity
 *
 * @package   mod_groupselect
 * @copyright 2018 HTW Chur Roger Barras
 * @copyright  2011 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Because it exists (must).
require_once($CFG->dirroot . '/mod/groupselect/backup/moodle2/restore_groupselect_stepslib.php');

/**
 * Groupselect restore task that provides all the settings and steps to perform one complete restore of the activity.
 *
 * @copyright  2011 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_groupselect_activity_task extends restore_activity_task {
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Label only has one structure step.
        $this->add_step(new restore_groupselect_activity_structure_step('groupselect_structure', 'groupselect.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('groupselect', ['intro'], 'groupselect');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('GROUPSELECTVIEWBYID', '/mod/groupselect/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('GROUPSELECTINDEX', '/mod/groupselect/index.php?id=$1', 'course');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * groupselect logs. It must return one array
     * of {@see restore_log_rule} objects
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('groupselect', 'select', 'view.php?id={course_module}', '{groupselect}');
        $rules[] = new restore_log_rule('groupselect', 'unselect', 'view.php?id={course_module}', '{groupselect}');
        $rules[] = new restore_log_rule('groupselect', 'add', 'view.php?id={course_module}', '{groupselect}');
        $rules[] = new restore_log_rule('groupselect', 'update', 'view.php?id={course_module}', '{groupselect}');
        $rules[] = new restore_log_rule('groupselect', 'view', 'view.php?id={course_module}', '{groupselect}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@see restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@see restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        $rules[] = new restore_log_rule('groupselect', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
