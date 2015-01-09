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
 * @package     mod_stampcoll
 * @category    event
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stampcoll\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The user received a stamp
 *
 * This event is triggered together with the {@link stamp_added} event but from
 * the recepient's point of view.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stamp_granted extends \core\event\base {

    /**
     * Initialize the event.
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'stampcoll_stamps';
    }

    /**
     * Returns localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventstampgranted', 'mod_stampcoll');
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' received stamp with id '$this->objectid' from the user with id '$this->relateduserid' ".
            "in the stamp collection with the course module id '$this->contextinstanceid'.";
    }

    /**
     * Return URL relevant to the event.
     *
     * @return moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/stampcoll/view.php', array(
            'id' => $this->contextinstanceid,
            'view' => 'single',
            'userid' => $this->userid,
        ));
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->contextinstanceid)) {
            throw new \coding_exception('The contextinstanceid event property not set.');
        }

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The relateduserid event property not set.');
        }
    }
}
