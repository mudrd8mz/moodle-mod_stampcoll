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
 * @package    mod
 * @subpackage stampcoll
 * @copyright  2007 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // course id

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

add_to_log($course->id, 'stampcoll', 'view all', 'index.php?id='.$course->id, '');

if (!$stampcolls = get_all_instances_in_course('stampcoll', $course)) {
    notice(get_string('noinstances', 'stampcoll'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

// get the ids of stampcoll instances, in order they appear in the course
$stampcollids = array();
foreach ($stampcolls as $stampcoll) {
    $stampcollids[] = $stampcoll->id;
}

// todo cont here


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
    if (! $cm = get_coursemodule_from_instance('stampcoll', $stampcoll->id)) {
        error('Course Module ID was incorrect');
    }
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    include(dirname(__FILE__).'/caps.php');

    if (! $cap_viewsomestamps) {
        $count_mystamps = get_string('notallowedtoviewstamps', 'stampcoll');
    } else {
        if (! $allstamps = stampcoll_get_stamps($stampcoll->id)) {
            $allstamps = array();
        }
        $count_totalstamps = count($allstamps);
        $count_mystamps = 0;
        foreach ($allstamps as $s) {
            if ($s->userid == $USER->id) {
                $count_mystamps++;
            }
        }
        unset($allstamps);
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
    
    //Calculate the href
    if (!$stampcoll->visible) {
        //Show dimmed if the mod is hidden
        $tt_href = '<a class="dimmed" href="view.php?id='.$stampcoll->coursemodule.'">';
        $tt_href .= format_string($stampcoll->name, true);
        $tt_href .= '</a>';
    } else {
        //Show normal if the mod is visible
        $tt_href = '<a href="view.php?id='.$stampcoll->coursemodule.'">';
        $tt_href .= format_string($stampcoll->name, true);
        $tt_href .= '</a>';
    }

    if (! $cap_viewsomestamps) {
        $aa = get_string('notallowedtoviewstamps', 'stampcoll');
    } else {
        $aa = '';
        if ($cap_viewownstamps) {
            $aa .= $count_mystamps;
        }
        if ($cap_viewotherstamps) {
            $aa .= ' ('. ($count_totalstamps - $count_mystamps) .')';
        }
    }
        
    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = array ($printsection, $tt_href, $aa);
    } else {
        $table->data[] = array ($tt_href, $aa);
    }
}
print_table($table);

print_footer($course);
