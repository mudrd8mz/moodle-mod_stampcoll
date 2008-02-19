<?php  //$Id$

// This file keeps track of upgrades to 
// the stampcoll module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_stampcoll_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    if ($result && $oldversion < 2008021900) { 

    /// CONTRIB-288 Drop field "publish" from the table "stampcoll" and controll the access by capabilities
        if ($collections = get_records('stampcoll', 'publish', '0')) {
            // collections with publish set to STAMPCOLL_PUBLISH_NONE - prevent displaying from legacy:students
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:student
                    if ($studentroles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW)) {
                        foreach ($studentroles as $studentrole) {
                            // prevent students from viewing own stamps 
                            assign_capability('mod/stampcoll:viewownstamps', CAP_PREVENT, $studentrole->id, $context->id);
                        }
                    }
                }
            }
        }
        if ($collections = get_records('stampcoll', 'publish', '2')) {
            // collections with publish set to STAMPCOLL_PUBLISH_ALL - allow legacy:students to view others' stamps
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:student
                    if ($studentroles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW)) {
                        foreach ($studentroles as $studentrole) {
                            // allow students to view others' stamps 
                            assign_capability('mod/stampcoll:viewotherstamps', CAP_ALLOW, $studentrole->id, $context->id);
                        }
                    }
                }
            }
        }
        $table = new XMLDBTable('stampcoll');
        $field = new XMLDBField('publish');
        $result = $result && drop_field($table, $field);
    

    /// CONTRIB-289 Drop field "teachercancollect" in the table "mdl_stampcoll"
        if ($collections = get_records('stampcoll', 'teachercancollect', '1')) {
            // collections which allow teachers to collect stamps
            foreach ($collections as $collection) {
                if ($cm = get_coursemodule_from_instance('stampcoll', $collection->id)) {
                    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
                    // find all roles with legacy:teacher and legacy:editingteacher
                    // and allow them to collect stamps 
                    if ($teacherroles = get_roles_with_capability('moodle/legacy:teacher', CAP_ALLOW)) {
                        foreach ($teacherroles as $teacherrole) {
                            assign_capability('mod/stampcoll:collectstamps', CAP_ALLOW, $teacherrole->id, $context->id);
                        }
                    }
                    if ($teacherroles = get_roles_with_capability('moodle/legacy:editingteacher', CAP_ALLOW)) {
                        foreach ($teacherroles as $teacherrole) {
                            assign_capability('mod/stampcoll:collectstamps', CAP_ALLOW, $teacherrole->id, $context->id);
                        }
                    }
                }
            }
        }
        $table = new XMLDBTable('stampcoll');
        $field = new XMLDBField('teachercancollect');
        $result = $result && drop_field($table, $field);
    }


/// if ($result && $oldversion < YYYYMMDD00) { //New version in version.php
///     $result = result of "/lib/ddllib.php" function calls
/// }

    return $result;
}

?>
