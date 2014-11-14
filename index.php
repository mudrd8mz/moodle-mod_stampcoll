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
 * Lists all Stamp collection instances in the course
 *
 * @package    mod_stampcoll
 * @copyright  2007 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$event = \mod_stampcoll\event\course_module_instance_list_viewed::create(array(
    'context' => context_course::instance($course->id)
));
$event->trigger();

$coursecontext = context_course::instance($course->id);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/mod/stampcoll/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

// Output starts here.
echo $OUTPUT->header();

if (!$stampcolls = get_all_instances_in_course('stampcoll', $course)) {
    notice(get_string('noinstances', 'stampcoll'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// Get the ids of stampcoll instances, in order they appear in the course.
$stampcollids = array();
foreach ($stampcolls as $stampcoll) {
    $stampcollids[] = $stampcoll->id;
}

$table = new html_table();

if ($course->format == 'weeks') {
    $table->head  = array (get_string('week'), get_string('name'), get_string('numberofstamps', 'stampcoll'));
    $table->align = array ('center', 'left', 'center');

} else if ($course->format == 'topics') {
    $table->head  = array (get_string('topic'), get_string('name'), get_string('numberofstamps', 'stampcoll'));
    $table->align = array ('center', 'left', 'center');

} else {
    $table->head  = array (get_string('name'), get_string('numberofstamps', 'stampcoll') );
    $table->align = array ('left', 'left');
}

$currentsection = '';

foreach ($stampcolls as $stampcoll) {
    $cm = get_coursemodule_from_instance('stampcoll', $stampcoll->id, $course->id, false, MUST_EXIST);
    $context = context_module::instance($cm->id);
    $canviewownstamps = has_capability('mod/stampcoll:viewownstamps', $context, null, false);
    $canviewotherstamps = has_capability('mod/stampcoll:viewotherstamps', $context);
    $canviewsomestamps = $canviewownstamps || $canviewotherstamps;

    if (! $canviewsomestamps) {
        $countmystamps = get_string('notallowedtoviewstamps', 'stampcoll');
    } else {
        // TODO Separated group mode and actual state of enrolments not taken into account here yet.
        $rawstamps = $DB->get_records('stampcoll_stamps', array('stampcollid' => $stampcoll->id), 'timecreated', '*');

        $counttotalstamps = count($rawstamps);
        $countmystamps = 0;
        foreach ($rawstamps as $s) {
            if ($s->userid == $USER->id) {
                $countmystamps++;
            }
        }
        unset($rawstamps);
        unset($s);
    }

    $printsection = '';
    if ($stampcoll->section !== $currentsection) {
        if ($stampcoll->section) {
            $printsection = $stampcoll->section;
        }
        if ($currentsection !== '') {
            $table->data[] = 'hr';
        }
        $currentsection = $stampcoll->section;
    }

    if (!$stampcoll->visible) {
        $activitylink = html_writer::link(
            new moodle_url('/mod/stampcoll/view.php', array('id' => $stampcoll->coursemodule)),
            format_string($stampcoll->name, true),
            array('class' => 'dimmed'));
    } else {
        $activitylink = html_writer::link(
            new moodle_url('/mod/stampcoll/view.php', array('id' => $stampcoll->coursemodule)),
            format_string($stampcoll->name, true));
    }

    if (! $canviewsomestamps) {
        $stats = get_string('notallowedtoviewstamps', 'stampcoll');
    } else {
        $stats = '';
        if ($canviewownstamps) {
            $stats .= $countmystamps;
        }
        if ($canviewotherstamps) {
            $stats .= ' ('. ($counttotalstamps - $countmystamps) .')';
        }
    }

    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = array ($printsection, $activitylink, $stats);
    } else {
        $table->data[] = array ($printsection, $stats);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'stampcoll'));
echo html_writer::table($table);
echo $OUTPUT->footer();
