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
 * Library of interface functions and constants for module stampcoll
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * in Moodle should be placed here.
 *
 * @package    mod
 * @subpackage stampcoll
 * @copyright  2007 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

////////////////////////////////////////////////////////////////////////////////
// Moodle core API                                                            //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function stampcoll_supports($feature) {

    switch($feature) {
        case FEATURE_MOD_INTRO:         return true;
        case FEATURE_GROUPS:            return true;
        case FEATURE_GRADE_HAS_GRADE:   return false;
        default:                        return null;
    }
}

/**
 * Creates a new instance of the stamp collection and returns its id
 *
 * @param object $stampcoll object containing data defined by the mod_form.php
 * @param mod_stampcoll_mod_form $mform
 * @return int id of the new instance
 */
function stampcoll_add_instance(stdClass $stampcoll, mod_stampcoll_mod_form $mform) {
    global $DB, $COURSE;

    $stampcoll->timemodified = time();
    $stampcoll->image = null;

    $context = get_context_instance(CONTEXT_MODULE, $stampcoll->coursemodule);
    $imageoptions = array('subdirs' => false, 'maxfiles' => 1, 'accepted_types' => array('image'),
        'maxbytes' => $COURSE->maxbytes, 'return_types' => FILE_INTERNAL);
    if ($draftitemid = file_get_submitted_draft_itemid('image')) {
        file_save_draft_area_files($draftitemid, $context->id, 'mod_stampcoll', 'image', 0, $imageoptions);
        $fs = get_file_storage();
        foreach ($fs->get_area_files($context->id, 'mod_stampcoll', 'image', 0, 'timemodified DESC', false) as $storedfile) {
            $stampcoll->image = $storedfile->get_filename();
            // note: $storedfile->get_imageinfo() returns width, height and mimetype
            break;
        }
    }

    // save the new record into the database and reload it
    return $DB->insert_record('stampcoll', $stampcoll);
}

/**
 * Updates an existing instance of stamp collection with new data
 *
 * @param object $stampcoll object containing data defined by the mod_form.php
 * @param mod_stampcoll_mod_form $mform
 * @return boolean
 */
function stampcoll_update_instance(stdClass $stampcoll, mod_stampcoll_mod_form $mform) {
    global $DB, $COURSE;

    $stampcoll->id = $stampcoll->instance;
    $stampcoll->timemodified = time();

    $context = get_context_instance(CONTEXT_MODULE, $stampcoll->coursemodule);
    $imageoptions = array('subdirs' => false, 'maxfiles' => 1, 'accepted_types' => array('image'),
        'maxbytes' => $COURSE->maxbytes, 'return_types' => FILE_INTERNAL);
    if ($draftitemid = file_get_submitted_draft_itemid('image')) {
        $stampcoll->image = null;
        file_save_draft_area_files($draftitemid, $context->id, 'mod_stampcoll', 'image', 0, $imageoptions);
        $fs = get_file_storage();
        foreach ($fs->get_area_files($context->id, 'mod_stampcoll', 'image', 0, 'timemodified DESC', false) as $storedfile) {
            $stampcoll->image = $storedfile->get_filename();
            // note: $storedfile->get_imageinfo() returns width, height and mimetype
            break;
        }
    }

    $DB->update_record('stampcoll', $stampcoll);

    return true;
}

/**
 * Deletes the instance of stamp collection and any data that depends on it
 *
 * @param int $id of an instance to be deleted
 * @return bool
 */
function stampcoll_delete_instance($id) {
    global $DB;

    if (! $stampcoll = $DB->get_record('stampcoll', array('id' => $id))) {
        return false;
    }

    $DB->delete_records('stampcoll_stamps', array('stampcollid' => $stampcoll->id));
    $DB->delete_records('stampcoll', array('id' => $stampcoll->id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return stdClass|null
 */
function stampcoll_user_outline($course, $user, $mod, $stampcoll) {
    global $DB;

    if ($stamps = $DB->get_records_select('stampcoll_stamps', 'userid=? AND stamcollid=?', array($user->id, $stampcoll->id))) {
        $result = new stdClass();
        $result->info = get_string('numberofcollectedstamps', 'stampcoll', count($stamps));
        $result->time = 0;  // empty
        return $result;
    }
    return null;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @todo rewrite
 * @return string HTML
 */
function stampcoll_user_complete($course, $user, $mod, $stampcoll) {
    global $USER;

    return '';

    /*
    $context = get_context_instance(CONTEXT_MODULE, $mod->id); 
    if ($USER->id == $user->id) {
        if (!has_capability('mod/stampcoll:viewownstamps', $context)) {
            echo get_string('notallowedtoviewstamps', 'stampcoll');
            return true;
        }
    } else {
        if (!has_capability('mod/stampcoll:viewotherstamps', $context)) {
            echo get_string('notallowedtoviewstamps', 'stampcoll');
            return true;
        }
    }

    if (!$allstamps = stampcoll_get_stamps($stampcoll->id)) {
        // no stamps yet in this instance
        echo get_string('nostampscollected', "stampcoll");
        return true;
    }

    $userstamps = array();
    foreach ($allstamps as $s) {
        if ($s->userid == $user->id) {
            $userstamps[] = $s;
        }
    }
    unset($allstamps);
    unset($s);

    if (empty($userstamps)) {
        echo get_string('nostampscollected', 'stampcoll');
    } else {
        echo get_string('numberofcollectedstamps', 'stampcoll', count($userstamps));
        echo '<div class="stamppictures">';
        foreach ($userstamps as $s) {
            echo stampcoll_stamp($s, $stampcoll->image);
        }
        echo '</div>';
        unset($s);
    }
     */
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in stampcoll activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @return boolean
 */
function stampcoll_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;  //  True if anything was printed, otherwise false
}

/**
 * Returns all activity in stampcolls since a given time
 *
 * @param array $activities sequentially indexed array of objects
 * @param int $index
 * @param int $timestart
 * @param int $courseid
 * @param int $cmid
 * @param int $userid defaults to 0
 * @param int $groupid defaults to 0
 * @return void adds items into $activities and increases $index
 */
function stampcoll_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by {@see stampcoll_get_recent_mod_activity()}
 *
 * @return void
 */
function stampcoll_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 */
function stampcoll_cron () {
    return true;
}

/**
 * Return the users with data in one stamp collection.
 *
 * Return users with records in stampcoll_stamps.
 *
 * @uses $CFG
 * @param int $stampcollid ID of an module instance
 * @return array Array of unique users
 */
function stampcoll_get_participants($stampcollid) {
    global $CFG;
    $students = get_records_sql("SELECT DISTINCT u.id, u.id
                                 FROM {$CFG->prefix}user u,
                                      {$CFG->prefix}stampcoll_stamps s
                                 WHERE s.stampcollid = '$stampcollid' AND
                                       u.id = s.userid");
    return ($students);
}

/**
 * Returns all other caps used in the module
 *
 * @return array
 */
function stampcoll_get_extra_capabilities() {
    return array('moodle/site:accessallgroups');
}

////////////////////////////////////////////////////////////////////////////////
// File API                                                                   //
////////////////////////////////////////////////////////////////////////////////

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area stampcoll_intro for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function stampcoll_get_file_areas($course, $cm, $context) {
    return array('image' => get_string('filearea_image', 'stampcoll'));
}

/**
 * Serves the files from the stampcoll file areas
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return void this should never return to the caller
 */
function stampcoll_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    if (!has_capability('mod/stampcoll:view', $context)) {
        send_file_not_found();
    }

    if ($filearea === 'image') {
        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_stampcoll/$filearea/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            send_file_not_found();
        }

        $lifetime = isset($CFG->filelifetime) ? $CFG->filelifetime : 86400;

        // finally send the file
        send_stored_file($file, $lifetime, 0);
    }

    send_file_not_found();
}

////////////////////////////////////////////////////////////////////////////////
// Navigation API                                                             //
////////////////////////////////////////////////////////////////////////////////

/**
 * Extends the global navigation tree by adding stampcoll nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the stampcoll module instance
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function stampcoll_extend_navigation(navigation_node $navref, stdclass $course, stdclass $module, cm_info $cm) {

    $context            = get_context_instance(CONTEXT_MODULE, $cm->id);
    $canviewownstamps   = has_capability('mod/stampcoll:viewownstamps', $context);
    $canviewotherstamps = has_capability('mod/stampcoll:viewotherstamps', $context);

    if ($canviewownstamps and $canviewotherstamps) {
        $url = new moodle_url('/mod/stampcoll/view.php', array('id' => $cm->id, 'view' => 'own'));
        $ownstamps = $navref->add(get_string('ownstamps', 'stampcoll'), $url);
    }
}

/**
 * Extends the settings navigation with the stampcoll settings
 *
 * This function is called when the context for the page is a stampcoll module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@link settings_navigation}
 * @param navigation_node $stampcollnode {@link navigation_node}
 */
function stampcoll_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $stampcollnode=null) {
    global $PAGE;

    if (has_capability('mod/stampcoll:managestamps', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/stampcoll/managestamps.php', array('cmid' => $PAGE->cm->id));
        $stampcollnode->add(get_string('managestamps', 'mod_stampcoll'), $url, settings_navigation::TYPE_SETTING);
    }
}
