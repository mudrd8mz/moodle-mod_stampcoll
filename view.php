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
 * Prints a particular instance of stamp collection module
 *
 * The script prints either user's own stamps or all stamps collected in this
 * activity.
 *
 * @package    mod
 * @subpackage stampcoll
 * @copyright  2007 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$cmid       = required_param('id', PARAM_INT);            // course module id
$view       = optional_param('view', 'all', PARAM_ALPHA); // display mode all|own

$cm         = get_coursemodule_from_id('stampcoll', $cmid, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$stampcoll  = $DB->get_record('stampcoll', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);

$PAGE->set_url(new moodle_url('/mod/stampcoll/view.php', array('id' => $cmid, 'view' => $view)));
$PAGE->set_title($stampcoll->name);
$PAGE->set_heading($course->fullname);

require_capability('mod/stampcoll:view', $PAGE->context);

add_to_log($course->id, 'stampcoll', 'view', 'view.php?id=$cm->id', $stampcoll->id, $cm->id);

$canviewownstamps = has_capability('mod/stampcoll:viewownstamps', $PAGE->context, null, false);
$canviewotherstamps = has_any_capability(array(
    'mod/stampcoll:managestamps',
    'mod/stampcoll:viewotherstamps'),
    $PAGE->context);
$canviewsomestamps = $canviewownstamps || $canviewotherstamps;
$canviewonlyownstamps = $canviewownstamps && (!$canviewotherstamps);

if ($canviewonlyownstamps and $view == 'all') {
    $view = 'own';
}

$output = $PAGE->get_renderer('mod_stampcoll');
echo $output->header();

if (trim($stampcoll->intro)) {
    echo $output->box(format_module_intro('stampcoll', $stampcoll, $cmid), 'generalbox');
}

if (!$canviewsomestamps) {
    notice(get_string('notallowedtoviewstamps', 'mod_stampcoll'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

if ($view == 'own') {

    // construct the sql returning all stamp info to display
    $sql = "SELECT s.id AS stampid, s.userid AS holderid, s.text AS stamptext, s.timemodified AS stamptimemodified,".
                   user_picture::fields('gu', null, 'giverid', 'giver')."
              FROM {stampcoll_stamps} s
         LEFT JOIN {user} gu ON s.giver = gu.id AND gu.deleted = 0
             WHERE s.stampcollid = :stampcollid AND s.userid = :holderid
          ORDER BY s.timemodified";
    $params = array('stampcollid' => $stampcoll->id, 'holderid' => $USER->id);

    // prepare the renderable collection
    $collection = new stampcoll_singleuser_collection($USER);

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $record) {
        if (!empty($record->giverid)) {
            $collection->register_user(user_picture::unalias($record, null, 'giverid', 'giver'));
        }
        if (!empty($record->stampid)) {
            $stamp = (object)array(
                'id'            => $record->stampid,
                'userid'        => $record->holderid,
                'giver'         => $record->giverid,
                'text'          => $record->stamptext,
                'timemodified'  => $record->stamptimemodified,
            );
            $collection->add_stamp($stamp);
        }
    }
    $rs->close();

    echo $output->render($collection);

} else if ($view == 'all') {

    $groupmode = groups_get_activity_groupmode($cm);

    if ($groupmode == NOGROUPS) {
        $groupid = false;

    } else {
        groups_print_activity_menu($cm, $PAGE->url);
        $groupid = groups_get_activity_group($cm);

        if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $PAGE->context)) {
            if (!groups_is_member($groupid)) {
                // this should not happen but...
                notice(get_string('groupusernotmember', 'core_error'), new moodle_url('/course/view.php', array('id' => $course->id)));
            }
        }
    }

    // get the sql returning all actively enrolled users who can collect stamps
    list($enrolsql, $enrolparams) = get_enrolled_sql($PAGE->context, 'mod/stampcoll:collectstamps', $groupid, true);

    // construct the sql returning all stamps info to display
    $sql = "SELECT ".user_picture::fields('hu', null, 'holderid', 'holder').",
                   s.id AS stampid, s.text AS stamptext, s.timemodified AS stamptimemodified,".
                   user_picture::fields('gu', null, 'giverid', 'giver')."
              FROM {user} hu
              JOIN ($enrolsql) eu ON hu.id = eu.id
         LEFT JOIN {stampcoll_stamps} s ON s.stampcollid = :stampcollid AND s.userid = hu.id
         LEFT JOIN {user} gu ON s.giver = gu.id AND gu.deleted = 0
          ORDER BY s.timemodified";
    $params = array_merge($enrolparams, array('stampcollid' => $stampcoll->id));

    // prepare the renderable collection
    $collection = new stampcoll_multiuser_collection();

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $record) {
        if (!empty($record->holderid)) {
            $collection->register_user(user_picture::unalias($record, null, 'holderid', 'holder'));
        }
        if (!empty($record->giverid)) {
            $collection->register_user(user_picture::unalias($record, null, 'giverid', 'giver'));
        }
        if (!empty($record->stampid)) {
            $stamp = (object)array(
                'id'            => $record->stampid,
                'userid'        => $record->holderid,
                'giver'         => $record->giverid,
                'text'          => $record->stamptext,
                'timemodified'  => $record->stamptimemodified,
            );
            $collection->add_stamp($stamp);
        }
    }
    $rs->close();

    echo $output->render($collection);
}

echo $output->footer();
