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
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stampcoll\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the 'stamp collection course module viewed' class.
 *
 * @copyright 2014 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Initialize the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'stampcoll';
        parent::init();
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {

        $description = parent::get_description();

        if (isset($this->other['viewmode'])) {
            $description .= ' The view mode was \''.$this->other['viewmode'].'\'.';
        }

        return $description;
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->other['viewmode'])) {
            throw new \coding_exception('The other[viewmode] event property not set.');
        }
    }
}
