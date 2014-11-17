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
 * @category    test
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_stampcoll_generator_testcase extends advanced_testcase {

    public function test_create_instance() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        $this->assertFalse($DB->record_exists('stampcoll', array('course' => $course->id)));
        $stampcoll = $this->getDataGenerator()->create_module('stampcoll', array('course' => $course));
        $records = $DB->get_records('stampcoll', array('course' => $course->id), 'id');
        $this->assertEquals(1, count($records));
        $this->assertTrue(array_key_exists($stampcoll->id, $records));
        $this->assertEquals(0, $records[$stampcoll->id]->displayzero);

        $params = array('course' => $course->id, 'name' => 'Another Stamp collection', 'displayzero' => 1);
        $stampcoll = $this->getDataGenerator()->create_module('stampcoll', $params);
        $records = $DB->get_records('stampcoll', array('course' => $course->id), 'id');
        $this->assertEquals(2, count($records));
        $this->assertEquals('Another Stamp collection', $records[$stampcoll->id]->name);
        $this->assertEquals(1, $records[$stampcoll->id]->displayzero);
    }
}
