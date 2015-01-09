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
 * @package    mod_stampcoll
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/addstamp_form.php');

$scid = required_param('scid', PARAM_INT);  // Stamp collection instance id.

$stampcollr = $DB->get_record('stampcoll', array('id' => $scid), '*', MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $stampcollr->course), '*', MUST_EXIST);
$cm         = get_coursemodule_from_instance('stampcoll', $stampcollr->id, $course->id, false, MUST_EXIST);

require_login($course, false, $cm);

$stampcoll = new stampcoll($stampcollr, $cm, $course);

if (isguestuser()) {
    print_error('guestsarenotallowed');
}

$PAGE->set_url(new moodle_url('/mod/stampcoll/addstamp.php', array('scid' => $stampcoll->id)));
$PAGE->set_title($stampcoll->name);
$PAGE->set_heading($course->fullname);

require_capability('mod/stampcoll:givestamps', $stampcoll->context);

$form = new stampcoll_stamp_form();

if ($data = $form->get_data()) {

    if ($data->userfrom != $USER->id) {
        throw new moodle_exception('invalid_userfrom_id', 'stampcoll');
    }

    if (!has_capability('mod/stampcoll:collectstamps', $stampcoll->context, $data->userto, false)) {
        throw new moodle_exception('invalid_userto_id', 'stampcoll');
    }

    $stampid = $DB->insert_record('stampcoll_stamps', array(
        'stampcollid'   => $stampcoll->id,
        'userid'        => $data->userto,
        'giver'         => $data->userfrom,
        'text'          => $data->text,
        'timecreated'   => time()));

    $event = \mod_stampcoll\event\stamp_added::create(array(
        'objectid' => $stampid,
        'context' => $stampcoll->context,
        'courseid' => $stampcoll->course->id,
        'relateduserid' => $data->userto,
    ));

    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('stampcoll', $stampcollr);
    $event->trigger();

    $event = \mod_stampcoll\event\stamp_granted::create(array(
        'objectid' => $stampid,
        'context' => $stampcoll->context,
        'courseid' => $stampcoll->course->id,
        'userid' => $data->userto,
        'relateduserid' => $USER->id,
    ));

    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('stampcoll', $stampcollr);
    $event->trigger();

    redirect(new moodle_url('/mod/stampcoll/view.php', array('id' => $cm->id, 'view' => 'single', 'userid' => $data->userto)));
}

redirect(new moodle_url('/mod/stampcoll/view.php', array('id' => $cm->id, 'view' => 'all')));
