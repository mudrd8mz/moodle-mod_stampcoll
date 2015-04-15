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
 * Stamps management screen
 *
 * @package    mod_stampcoll
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$cmid       = required_param('cmid', PARAM_INT);                                // Course module id.
$sortby     = optional_param('sortby', 'lastname', PARAM_ALPHA);                // Sort by column.
$sorthow    = optional_param('sorthow', 'ASC', PARAM_ALPHA);                    // Sort direction.
$page       = optional_param('page', 0, PARAM_INT);                             // Page.
$updatepref = optional_param('updatepref', false, PARAM_BOOL);                  // Is the preferences form being saved?
$perpage    = optional_param('perpage', stampcoll::USERS_PER_PAGE, PARAM_INT);  // 'Users per page' preference.
$delete     = optional_param('delete', null, PARAM_INT);                        // Stamp id to delete.
$confirmed  = optional_param('confirmed', false, PARAM_BOOL);                   // Confirm the operation.

$cm         = get_coursemodule_from_id('stampcoll', $cmid, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$stampcollr = $DB->get_record('stampcoll', array('id' => $cm->instance), '*', MUST_EXIST);

if (!in_array($sortby, array('firstname', 'lastname', 'count'))) {
    $sortby = 'lastname';
}

if ($sorthow != 'ASC' and $sorthow != 'DESC') {
    $sorthow = 'ASC';
}

if ($page < 0) {
    $page = 0;
}

require_login($course, true, $cm);

$stampcoll = new stampcoll($stampcollr, $cm, $course);

$PAGE->set_url($stampcoll->managestamps_url());
$PAGE->set_title($stampcoll->name);
$PAGE->set_heading($course->fullname);

require_capability('mod/stampcoll:managestamps', $stampcoll->context);

$event = \mod_stampcoll\event\course_module_viewed::create(array(
    'objectid' => $stampcoll->id,
    'context' => $stampcoll->context,
    'other' => array(
        'viewmode' => 'manage',
    ),
));

$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('stampcoll', $stampcollr);
$event->trigger();

$output = $PAGE->get_renderer('mod_stampcoll');

if ($updatepref) {
    require_sesskey();
    if ($perpage > 0) {
        set_user_preference('stampcoll_perpage', $perpage);
    }
    redirect($PAGE->url);
}

if ($delete) {
    // Make sure the stamp is from this collection.
    $stamprecord = $DB->get_record('stampcoll_stamps', array('id' => $delete, 'stampcollid' => $stampcoll->id), '*', MUST_EXIST);
    $stamp = new stampcoll_stamp($stampcoll, $stamprecord);
    if (!$confirmed) {
        // Let the user confirm.
        echo $output->header();
        echo $output->heading(format_string($stampcoll->name, false, array('context' => $stampcoll->context)));
        echo $output->confirm($output->render($stamp) . ' ' . get_string('deletestampconfirm', 'mod_stampcoll'),
            new moodle_url($PAGE->url, array('delete' => $stamp->id, 'confirmed' => 1)),
            $PAGE->url);
        echo $output->footer();
        die();
    } else {
        require_sesskey();
        $event = \mod_stampcoll\event\stamp_deleted::create(array(
            'objectid' => $stamp->id,
            'context' => $stampcoll->context,
            'courseid' => $stampcoll->course->id,
            'relateduserid' => $stamp->holderid,
        ));

        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('stampcoll', $stampcollr);
        $event->add_record_snapshot('stampcoll_stamps', $stamprecord);
        $event->trigger();

        $DB->delete_records('stampcoll_stamps', array('id' => $stamp->id));
        redirect($PAGE->url);
    }
}

if ($data = data_submitted()) {
    require_sesskey();
    $now = time();
    // Get the list of userids of users who are allowed to collect stamps here.
    $holderids = array_keys(get_enrolled_users($stampcoll->context, 'mod/stampcoll:collectstamps', 0, 'u.id'));

    // Add new stamps.
    if (!empty($data->addnewstamp) and is_array($data->addnewstamp)) {
        foreach ($data->addnewstamp as $holderid => $text) {
            $holderid = clean_param($holderid, PARAM_INT);
            if (empty($holderid)) {
                debugging('Invalid holderid');
                continue;
            }
            if (!in_array($holderid, $holderids)) {
                debugging('Invalid stamp recipient '.$holderid);
                continue;
            }
            $text = trim($text);
            if ($text === '') {
                // In the bulk mode, only stamps with text can be added.
                continue;
            }
            if (core_text::strlen($text) > 255) {
                debugging('Stamp text too long - user id '.$holderid);
                continue;
            }

            $stampid = $DB->insert_record('stampcoll_stamps', array(
                'stampcollid'   => $stampcoll->id,
                'userid'        => $holderid,
                'giver'         => $USER->id,
                'text'          => $text,
                'timecreated'   => $now),
            true, true);

            $event = \mod_stampcoll\event\stamp_added::create(array(
                'objectid' => $stampid,
                'context' => $stampcoll->context,
                'courseid' => $stampcoll->course->id,
                'relateduserid' => $holderid,
            ));

            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('course_modules', $cm);
            $event->add_record_snapshot('stampcoll', $stampcollr);
            $event->trigger();

            $event = \mod_stampcoll\event\stamp_granted::create(array(
                'objectid' => $stampid,
                'context' => $stampcoll->context,
                'courseid' => $stampcoll->course->id,
                'userid' => $holderid,
                'relateduserid' => $USER->id,
            ));

            $event->add_record_snapshot('course', $course);
            $event->add_record_snapshot('course_modules', $cm);
            $event->add_record_snapshot('stampcoll', $stampcollr);
            $event->trigger();
        }
    }

    // Update existing stamps.
    if (!empty($data->stampnewtext) and is_array($data->stampnewtext)) {

        // Get the list of stamps that can be modified via this bulk operation.
        list($subsql1, $params1) = $DB->get_in_or_equal(array_keys($data->stampnewtext));
        list($subsql2, $params2) = $DB->get_in_or_equal($holderids);
        $params = array_merge(array($stampcoll->id), $params1, $params2);
        $stamps = $DB->get_records_select('stampcoll_stamps',
            "stampcollid = ? AND id $subsql1 AND userid $subsql2", $params, '', 'id, userid, text');
        $stampids = array_keys($stamps);

        foreach ($data->stampnewtext as $stampid => $text) {
            $stampid = clean_param($stampid, PARAM_INT);
            if (empty($stampid)) {
                debugging('Invalid stampid');
                continue;
            }
            if (!in_array($stampid, $stampids)) {
                debugging('Invalid stamp record '.$stampid);
                continue;
            }
            $current = $stamps[$stampid];

            if (core_text::strlen($text) > 255) {
                debugging('Stamp text too long - stamp id '.$stampid);
                continue;
            }

            if ($current->text !== $text) {
                $update = new stdClass();
                $update->id = $stampid;
                $update->text = $text;
                $update->timemodified = $now;
                $update->modifier = $USER->id;

                $DB->update_record('stampcoll_stamps', $update, true);

                $event = \mod_stampcoll\event\stamp_updated::create(array(
                    'objectid' => $stampid,
                    'context' => $stampcoll->context,
                    'courseid' => $stampcoll->course->id,
                    'relateduserid' => $current->userid,
                ));

                $event->add_record_snapshot('course', $course);
                $event->add_record_snapshot('course_modules', $cm);
                $event->add_record_snapshot('stampcoll', $stampcollr);
                $event->trigger();
            }
        }
    }

    redirect($PAGE->url);
}

echo $output->header();

echo $output->heading(format_string($stampcoll->name, false, array('context' => $stampcoll->context)));

$PAGE->url->param('sortby', $sortby);
$PAGE->url->param('sorthow', $sorthow);

$groupmode = groups_get_activity_groupmode($cm);

if ($groupmode == NOGROUPS) {
    $groupid = false;

} else {
    groups_print_activity_menu($cm, $PAGE->url);
    $groupid = groups_get_activity_group($cm);

    if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $stampcoll->context)) {
        if (!groups_is_member($groupid)) {
            // This should not happen but...
            notice(get_string('groupusernotmember', 'core_error'), new moodle_url('/course/view.php', array('id' => $course->id)));
        }
    }
}

// Get the sql returning all actively enrolled users who can collect stamps.
list($enrolsql, $enrolparams) = get_enrolled_sql($stampcoll->context, 'mod/stampcoll:collectstamps', $groupid, true);

// In the first query, get the list of users to be displayed.
$sql = "SELECT COUNT(*)
          FROM (SELECT DISTINCT(u.id)
                  FROM {user} u
                  JOIN ($enrolsql) eu ON u.id = eu.id
             LEFT JOIN {stampcoll_stamps} s ON s.stampcollid = :stampcollid AND s.userid = u.id) t";

$params = array_merge($enrolparams, array('stampcollid' => $stampcoll->id));

$totalcount = $DB->count_records_sql($sql, $params);

// In the second query, get the list of user ids to display based on the sorting and paginating.
$sql = "SELECT u.id, u.firstname, u.lastname, COUNT(s.id) AS count
          FROM {user} u
          JOIN ($enrolsql) eu ON u.id = eu.id
     LEFT JOIN {stampcoll_stamps} s ON s.stampcollid = :stampcollid AND s.userid = u.id
      GROUP BY u.id, u.firstname, u.lastname
      ORDER BY $sortby $sorthow";

$params = array_merge($enrolparams, array('stampcollid' => $stampcoll->id));

$perpage = get_user_preferences('stampcoll_perpage', stampcoll::USERS_PER_PAGE);

$userids = array_keys($DB->get_records_sql($sql, $params, $page * $perpage, $perpage));

// Prepare the renderable collection.
$collection             = new stampcoll_management_collection($stampcoll, $userids);
$collection->sortedby   = $sortby;
$collection->sortedhow  = $sorthow;
$collection->page       = $page;
$collection->perpage    = $perpage;
$collection->totalcount = $totalcount;

if ($userids) {
    // In the third query, get all stamps info to display.
    list($holdersql, $holderparam) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

    $sql = "SELECT ".user_picture::fields('hu', null, 'holderid', 'holder').",
                   s.id AS stampid, s.text AS stamptext,
                   s.timecreated AS stamptimecreated, s.timemodified AS stamptimemodified,".
                   user_picture::fields('gu', null, 'giverid', 'giver')."
              FROM {user} hu
         LEFT JOIN {stampcoll_stamps} s ON s.stampcollid = :stampcollid AND s.userid = hu.id
         LEFT JOIN {user} gu ON s.giver = gu.id AND gu.deleted = 0
             WHERE hu.id $holdersql
          ORDER BY s.timecreated DESC";

    $params = array_merge(array('stampcollid' => $stampcoll->id), $holderparam);

    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $record) {
        if (!empty($record->holderid)) {
            $collection->register_holder(user_picture::unalias($record, null, 'holderid', 'holder'));
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
                'timecreated'   => $record->stamptimecreated,
                'timemodified'  => $record->stamptimemodified,
            );
            $collection->add_stamp($stamp);
        }
    }
    $rs->close();
}

echo $output->render($collection);

echo $output->footer();
