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
 * Defines all the restore steps that will be used by the restore_stampcoll_activity_task
 *
 * @package    plugintype
 * @subpackage pluginname
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore one stampcoll activity
 */
class restore_stampcoll_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('stampcoll', '/activity/stampcoll');
        if ($userinfo) {
            $paths[] = new restore_path_element('stampcoll_stamp', '/activity/stampcoll/stamps/stamp');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_stampcoll($data) {
        global $DB;

        $data = (object)$data;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('stampcoll', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_stampcoll_stamp($data) {
        global $DB;

        $data = (object)$data;
        $data->stampcollid = $this->get_new_parentid('stampcoll');
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!is_null($data->giver)) {
            $data->giver = $this->get_mappingid('user', $data->giver);
        }
        if (!is_null($data->modifier)) {
            $data->modifier = $this->get_mappingid('user', $data->modifier);
        }
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        if (!is_null($data->timemodified)) {
            $data->timemodified = $this->apply_date_offset($data->timemodified);
        }

        $DB->insert_record('stampcoll_stamps', $data);
    }

    protected function after_execute() {
        $this->add_related_files('mod_stampcoll', 'intro', null);
        $this->add_related_files('mod_stampcoll', 'image', null);
    }
}
