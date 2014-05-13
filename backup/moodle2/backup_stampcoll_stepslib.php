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
 * Defines all the backup steps that will be used by the backup_stampcoll_activity_task
 *
 * @package    mod_stampcoll
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the complete stampcoll structure for backup, with file and id annotations
 */
class backup_stampcoll_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        $userinfo = $this->get_setting_value('userinfo');

        $stampcoll = new backup_nested_element('stampcoll', array('id'), array(
            'name', 'intro', 'introformat', 'image', 'timemodified', 'displayzero'));

        $stamps = new backup_nested_element('stamps');

        $stamp = new backup_nested_element('stamp', array('id'), array(
            'userid', 'text', 'giver', 'timecreated', 'modifier', 'timemodified'));

        $stampcoll->add_child($stamps);
        $stamps->add_child($stamp);

        $stampcoll->set_source_table('stampcoll', array('id' => backup::VAR_ACTIVITYID));

        if ($userinfo) {
            $stamp->set_source_table('stampcoll_stamps', array('stampcollid' => backup::VAR_PARENTID));
        }

        $stamp->annotate_ids('user', 'userid');
        $stamp->annotate_ids('user', 'giver');
        $stamp->annotate_ids('user', 'modifier');

        $stampcoll->annotate_files('mod_stampcoll', 'intro', null);
        $stampcoll->annotate_files('mod_stampcoll', 'image', null);

        return $this->prepare_activity_structure($stampcoll);
    }
}
