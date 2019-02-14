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
 * Privacy subsystem implementation for mod_stampcoll.
 *
 * @package     mod_stampcoll
 * @copyright   2019 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_stampcoll\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\approved_userlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\transform;
use \core_privacy\local\request\userlist;
use \core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/stampcoll/locallib.php');

/**
 * Implementation of the privacy subsystem plugin provider for the stamp collection module.
 *
 * @package     mod_stampcoll
 * @copyright   2019 Tony Butler <a.butler4@lancaster.ac.uk>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,
        // This plugin provides data directly to the privacy subsystem.
        \core_privacy\local\request\plugin\provider,
        // This plugin is capable of determining which users have data within it.
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Return metadata about this plugin.
     *
     * @param   collection  $collection The initialised collection to add items to.
     * @return  collection  $collection A listing of user data stored through this plugin.
     */
    public static function get_metadata(collection $collection) : collection {
        // The 'stampcoll' table does not store any specific user data.

        // The 'stampcoll_stamps' table stores data about stamps collected by users.
        $collection->add_database_table('stampcoll_stamps', [
            'stampcollid'  => 'privacy:metadata:stampcoll_stamps:stampcollid',
            'userid'       => 'privacy:metadata:stampcoll_stamps:userid',
            'giver'        => 'privacy:metadata:stampcoll_stamps:giver',
            'modifier'     => 'privacy:metadata:stampcoll_stamps:modifier',
            'text'         => 'privacy:metadata:stampcoll_stamps:text',
            'timecreated'  => 'privacy:metadata:stampcoll_stamps:timecreated',
            'timemodified' => 'privacy:metadata:stampcoll_stamps:timemodified'
        ], 'privacy:metadata:stampcoll_stamps');

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param   int         $userid         The user to search.
     * @return  contextlist $contextlist    The list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                  JOIN {stampcoll} sc ON sc.id = cm.instance
                  JOIN {stampcoll_stamps} s ON s.stampcollid = sc.id
                 WHERE s.userid = :ownerid
                    OR s.giver = :giverid
                    OR s.modifier = :modifierid";
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modulename'   => 'stampcoll',
            'ownerid'      => $userid,
            'giverid'      => $userid,
            'modifierid'   => $userid
        ];

        // Add all stamp collection contexts in which the user owns, has given, or has modified a stamp.
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT s.userid, s.giver, s.modifier
                  FROM {stampcoll_stamps} s
                  JOIN {stampcoll} sc ON sc.id = s.stampcollid
                  JOIN {course_modules} cm ON cm.instance = sc.id
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
                 WHERE cm.id = :instanceid";
        $params = [
            'modulename' => 'stampcoll',
            'instanceid' => $context->instanceid
        ];

        // Include stamp owners, givers and modifiers.
        $userlist->add_from_sql('userid', $sql, $params);
        $userlist->add_from_sql('giver', $sql, $params);
        $userlist->add_from_sql('modifier', $sql, $params);
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $sql = "SELECT c.id AS contextid,
                       cm.id AS cmid,
                       sc.*,
                       s.userid AS owner,
                       s.giver,
                       s.modifier
                  FROM {context} c
                  JOIN {course_modules} cm ON cm.id = c.instanceid
                  JOIN {stampcoll} sc ON sc.id = cm.instance
                  JOIN {stampcoll_stamps} s ON s.stampcollid = sc.id AND (
                           s.userid = :ownerid OR
                           s.giver = :giverid OR
                           s.modifier = :modifierid
                       )
                 WHERE c.id {$contextsql}";
        $params = [
            'ownerid'    => $userid,
            'giverid'    => $userid,
            'modifierid' => $userid
        ] + $contextparams;

        $stampcolls = $DB->get_recordset_sql($sql, $params);

        // Keep a mapping of stampcollid to contextid.
        $mappings = [];
        foreach ($stampcolls as $stampcoll) {
            $mappings[$stampcoll->id] = $stampcoll->contextid;
            $context = \context::instance_by_id($mappings[$stampcoll->id]);

            // Store the main stamp collection data.
            $data = helper::get_context_data($context, $user);
            writer::with_context($context)
                ->export_data([], $data);
            helper::export_context_files($context, $user);
        }
        $stampcolls->close();

        if (!empty($mappings)) {
            // Store all stamp data for each collection.
            static::export_stamp_data($userid, $mappings);
        }
    }

    /**
     * Export data for all stamps that we have detected to be associated in any way with this user.
     *
     * @param   int     $userid     The userid of the user whose data is to be exported.
     * @param   array   $mappings   A list of mappings of stampcollid to contextid.
     */
    protected static function export_stamp_data(int $userid, array $mappings) {
        global $DB;

        // Find all of the stamps belonging to each collection.
        list($stampcollsql, $stampcollparams) = $DB->get_in_or_equal(array_keys($mappings), SQL_PARAMS_NAMED);
        $select = "stampcollid {$stampcollsql} AND (
                       userid = :ownerid OR
                       giver = :giverid OR
                       modifier = :modifierid
                   )";
        $params = $stampcollparams + [
            'ownerid'    => $userid,
            'giverid'    => $userid,
            'modifierid' => $userid
        ];

        $stamps = $DB->get_recordset_select('stampcoll_stamps', $select, $params, 'id');

        foreach ($stamps as $stamp) {
            $context = \context::instance_by_id($mappings[$stamp->stampcollid]);

            // Build the stamp subcontext.
            $subcontext = [
                get_string('stamps', 'mod_stampcoll'),
                implode('-', [$stamp->id, $stamp->text])
            ];

            // Build and store the stamp data.
            $giver = !empty($stamp->giver) ? fullname(user_get_users_by_id([$stamp->giver])[$stamp->giver]) : '';
            $modifier = !empty($stamp->modifier) ? fullname(user_get_users_by_id([$stamp->modifier])[$stamp->modifier]) : '';
            $modified = !empty($stamp->timemodified) ? transform::datetime($stamp->timemodified) : '';
            $stampdata = (object) [
                'comment'      => format_string($stamp->text, true),
                'owner'        => fullname(user_get_users_by_id([$stamp->userid])[$stamp->userid]),
                'giver'        => $giver,
                'given'        => transform::datetime($stamp->timecreated),
                'modifier'     => $modifier,
                'modified'     => $modified,
                'owned_by_you' => transform::yesno($stamp->userid == $userid)
            ];
            writer::with_context($context)
                ->export_data($subcontext, $stampdata);
        }
        $stamps->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param   context $context    The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // Check that this is a context_module.
        if (!$context instanceof \context_module) {
            return;
        }

        // Get the course module.
        if (!$cm = get_coursemodule_from_id('stampcoll', $context->instanceid)) {
            return;
        }

        $stampcollid = $cm->instance;

        $DB->delete_records('stampcoll_stamps', ['stampcollid' => $stampcollid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param   approved_contextlist    $contextlist    The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid], MUST_EXIST);

            // Delete all stamps owned by the user.
            $DB->delete_records('stampcoll_stamps', ['stampcollid' => $instanceid, 'userid' => $userid]);

            // In the case of stamps given or modified by the user, just set the relevant fields to null.
            $DB->set_field('stampcoll_stamps', 'giver', null, ['stampcollid' => $instanceid, 'giver' => $userid]);
            $DB->set_field('stampcoll_stamps', 'modifier', null, ['stampcollid' => $instanceid, 'modifier' => $userid]);
        }
    }

    /**
     * Delete data for multiple users within a single context.
     *
     * @param   approved_userlist   $userlist   The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        $cm = $DB->get_record('course_modules', ['id' => $context->instanceid]);

        list($usersql, $userparams) = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $select = "stampcollid = :stampcollid AND userid {$usersql}";
        $params = array_merge(['stampcollid' => $cm->instance], $userparams);

        $DB->delete_records_select('stampcoll_stamps', $select, $params);

        // For stamps given or modified by the users just nullify the relevant fields.
        $select = "stampcollid = :stampcollid AND giver {$usersql}";
        $DB->set_field_select('stampcoll_stamps', 'giver', null, $select, $params);
        $select = "stampcollid = :stampcollid AND modifier {$usersql}";
        $DB->set_field_select('stampcoll_stamps', 'modifier', null, $select, $params);
    }

}
