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
 * Definition of log events
 *
 * @package    mod
 * @subpackage stampcoll
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$logs = array(
    array('module' => 'stampcoll', 'action' => 'view',         'mtable' => 'stampcoll', 'field' => 'name'),
    array('module' => 'stampcoll', 'action' => 'update',       'mtable' => 'stampcoll', 'field' => 'name'),
    array('module' => 'stampcoll', 'action' => 'add',          'mtable' => 'stampcoll', 'field' => 'name'),
    array('module' => 'stampcoll', 'action' => 'update stamp', 'mtable' => 'user',      'field' => 'concat(firstname, \' \', lastname)'),
    array('module' => 'stampcoll', 'action' => 'delete stamp', 'mtable' => 'user',      'field' => 'concat(firstname, \' \', lastname)'),
);
