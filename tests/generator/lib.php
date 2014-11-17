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
 * Provides data generator for the Stamp collection module
 *
 * @package     mod_stampcoll
 * @category    test
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Data generator for the Stamp collection module
 *
 * @copyright 2014 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_stampcoll_generator extends testing_module_generator {

    /**
     * Creates module instance
     *
     * @see testing_module_generator::create_instance()
     * @param array|stdClass $record
     * @param array $option
     * @return stdClass
     */
    public function create_instance($record = null, array $options = null) {

        $record = (array)$record + array(
            'image' => null,
            'displayzero' => 0,
        );

        return parent::create_instance($record, $options);
    }
}
