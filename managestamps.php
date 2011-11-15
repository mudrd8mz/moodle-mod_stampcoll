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
 * @package    mod
 * @subpackage stampcoll
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/locallib.php');

$cmid       = required_param('cmid', PARAM_INT);                                // course module id
$sortby     = optional_param('sortby', 'lastname', PARAM_ALPHA);                // sort by column
$sorthow    = optional_param('sorthow', 'ASC', PARAM_ALPHA);                    // sort direction
$page       = optional_param('page', 0, PARAM_INT);                             // page
$updatepref = optional_param('updatepref', false, PARAM_BOOL);                  // is the preferences form being saved
$perpage    = optional_param('perpage', STAMPCOLL_USERS_PER_PAGE, PARAM_INT);   // users per page preference
$delete     = optional_param('delete', null, PARAM_INT);                        // stamp id to delete
$confirmed  = optional_param('confirmed', false, PARAM_BOOL);                   // confirm the operation

$cm         = get_coursemodule_from_id('stampcoll', $cmid, 0, false, MUST_EXIST);
$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$stampcoll  = $DB->get_record('stampcoll', array('id' => $cm->instance), '*', MUST_EXIST);

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

$PAGE->set_url(new moodle_url('/mod/stampcoll/managestamps.php', array('cmid' => $cmid)));
$PAGE->set_title($stampcoll->name);
$PAGE->set_heading($course->fullname);

require_capability('mod/stampcoll:managestamps', $PAGE->context);

add_to_log($course->id, 'stampcoll', 'manage', 'view.php?id='.$cm->id, $stampcoll->id, $cm->id);

$output = $PAGE->get_renderer('mod_stampcoll');

if ($updatepref) {
    require_sesskey();
    if ($perpage > 0) {
        set_user_preference('stampcoll_perpage', $perpage);
    }
    redirect($PAGE->url);
}

if ($delete) {
    // make sure the stamp is from this collection
    $stamp = $DB->get_record('stampcoll_stamps', array('id' => $delete, 'stampcollid' => $stampcoll->id), '*', MUST_EXIST);
    $stamp = new stampcoll_stamp($stampcoll, $stamp);
    if (!$confirmed) {
        // let the user confirm
        echo $output->header();
        echo $output->confirm($output->render($stamp) . ' ' . get_string('deletestampconfirm', 'mod_stampcoll'),
            new moodle_url($PAGE->url, array('delete' => $stamp->id, 'confirmed' => 1)),
            $PAGE->url);
        echo $output->footer();
        die();
    } else {
        require_sesskey();
        add_to_log($course->id, 'stampcoll', 'delete stamp', 'view.php?id='.$cm->id, $stamp->holderid, $cm->id);
        $DB->delete_records('stampcoll_stamps', array('id' => $stamp->id));
        redirect($PAGE->url);
    }
}

if ($data = data_submitted()) {
    require_sesskey();
    $now = time();
    // get the list of userids of users who are allowed to collect stamps here
    $holderids = array_keys(get_enrolled_users($PAGE->context, 'mod/stampcoll:collectstamps', 0, 'u.id'));

    // add new stamps
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
                // in the bulk mode, only stamps with text can be added
                continue;
            }

            add_to_log($course->id, 'stampcoll', 'add stamp', 'view.php?id='.$cm->id, $holderid, $cm->id);

            $DB->insert_record('stampcoll_stamps', array(
                'stampcollid'   => $stampcoll->id,
                'userid'        => $holderid,
                'giver'         => $USER->id,
                'text'          => $text,
                'timemodified'  => $now),
            false, true);
        }
    }

    // update existing stamps
    if (!empty($data->stampnewtext) and is_array($data->stampnewtext)) {

        // get the list of stamps that can be modified via this bulk operation
        list($subsql1, $params1) = $DB->get_in_or_equal(array_keys($data->stampnewtext));
        list($subsql2, $params2) = $DB->get_in_or_equal($holderids);
        $params = array_merge(array($stampcoll->id), $params1, $params2);
        $stamps = $DB->get_records_select('stampcoll_stamps', "stampcollid = ? AND id $subsql1 AND userid $subsql2", $params, '', 'id, userid, text');
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

            if ($current->text !== $text) {
                $update = new stdClass();
                $update->id = $stampid;
                $update->text = $text;
                $update->timemodified = $now;

                add_to_log($course->id, 'stampcoll', 'update stamp', 'view.php?id='.$cm->id, $current->userid, $cm->id);

                $DB->update_record('stampcoll_stamps', $update, true);
            }
        }
    }

    redirect($PAGE->url);
}

echo $output->header();

$PAGE->url->param('sortby', $sortby);
$PAGE->url->param('sorthow', $sorthow);

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

// in the first query, get the list of users to be displayed
$sql = "SELECT COUNT(*)
          FROM (SELECT DISTINCT(u.id)
                  FROM {user} u
                  JOIN ($enrolsql) eu ON u.id = eu.id
             LEFT JOIN {stampcoll_stamps} s ON s.stampcollid = :stampcollid AND s.userid = u.id) t";

$params = array_merge($enrolparams, array('stampcollid' => $stampcoll->id));

$totalcount = $DB->count_records_sql($sql, $params);

// in the second query, get the list of user ids to display based on the sorting and paginating
$sql = "SELECT u.id, u.firstname, u.lastname, COUNT(s.id) AS count
          FROM {user} u
          JOIN ($enrolsql) eu ON u.id = eu.id
     LEFT JOIN {stampcoll_stamps} s ON s.stampcollid = :stampcollid AND s.userid = u.id
      GROUP BY u.id, u.firstname, u.lastname
      ORDER BY $sortby $sorthow";

$params = array_merge($enrolparams, array('stampcollid' => $stampcoll->id));

$perpage = get_user_preferences('stampcoll_perpage', STAMPCOLL_USERS_PER_PAGE);

$userids = array_keys($DB->get_records_sql($sql, $params, $page * $perpage, $perpage));

// prepare the renderable collection
$collection             = new stampcoll_management_collection($stampcoll, $userids);
$collection->sortedby   = $sortby;
$collection->sortedhow  = $sorthow;
$collection->page       = $page;
$collection->perpage    = $perpage;
$collection->totalcount = $totalcount;

if ($userids) {
    // in the third query, get all stamps info to display
    list($holdersql, $holderparam) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

    $sql = "SELECT ".user_picture::fields('hu', null, 'holderid', 'holder').",
                   s.id AS stampid, s.text AS stamptext, s.timemodified AS stamptimemodified,".
                   user_picture::fields('gu', null, 'giverid', 'giver')."
              FROM {user} hu
         LEFT JOIN {stampcoll_stamps} s ON s.stampcollid = :stampcollid AND s.userid = hu.id
         LEFT JOIN {user} gu ON s.giver = gu.id AND gu.deleted = 0
             WHERE hu.id $holdersql
          ORDER BY s.timemodified";

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
                'timemodified'  => $record->stamptimemodified,
            );
            $collection->add_stamp($stamp);
        }
    }
    $rs->close();
}

echo $output->render($collection);

echo $output->footer();
