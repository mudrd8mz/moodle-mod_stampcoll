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
 * Keeps track of upgrades to the Stamp collection module
 *
 * @package    mod
 * @subpackage stampcoll
 * @copyright  2007 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Checks if some upgrade steps are needed and performs them eventually
 *
 * @param int $oldversion the current version we are upgrading from
 * @return true
 */
function xmldb_stampcoll_upgrade($oldversion = 0) {
    global $CFG, $DB, $OUTPUT;

    $dbman = $DB->get_manager();

    /**
     * Only upgrades from the version for Moodle 1.9 are supported
     */
    if ($oldversion < 2008022003) {
        throw new upgrade_exception('mod_stampcoll', $oldversion, 'Unable to upgrade such an old version of the module.');
    }

    /**
     * Rename field text to intro
     */
    if ($oldversion < 2011070100) {
       $table = new xmldb_table('stampcoll');
       $field = new xmldb_field('text', XMLDB_TYPE_TEXT, 'small', null, XMLDB_NOTNULL, null, null, 'name');
       $dbman->rename_field($table, $field, 'intro');
       upgrade_mod_savepoint(true, 2011070100, 'stampcoll');
    }

    /**
     * Make intro field nullable
     */
    if ($oldversion < 2011070101) {
       $table = new xmldb_table('stampcoll');
       $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'small', null, null, null, null, 'name');
       $dbman->change_field_notnull($table, $field);
       upgrade_mod_savepoint(true, 2011070101, 'stampcoll');
    }

    /**
     * Make intro field big
     */
    if ($oldversion < 2011070102) {
       $table = new xmldb_table('stampcoll');
       $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'big', null, null, null, null, 'name');
       $dbman->change_field_precision($table, $field);
       upgrade_mod_savepoint(true, 2011070102, 'stampcoll');
    }

    /**
     * Rename field format to introformat
     */
    if ($oldversion < 2011070103) {
       $table = new xmldb_table('stampcoll');
       $field = new xmldb_field('format', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');
       $dbman->rename_field($table, $field, 'introformat');
       upgrade_mod_savepoint(true, 2011070103, 'stampcoll');
    }

    return true;
}
