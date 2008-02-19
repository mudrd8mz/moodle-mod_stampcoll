<?php // $Id$

/// MODULE CONSTANTS //////////////////////////////////////////////////////

/**
 * Default number of students per page
 */
define('STAMPCOLL_USERS_PER_PAGE', 30);

/**
 * Some obsolete configuration constants. These are kept here for backward compatibility and upgrade process.
 */
define('STAMPCOLL_PUBLISH_NONE',    '0');
define('STAMPCOLL_PUBLISH_SELFONLY',    '1');
define('STAMPCOLL_PUBLISH_ALL',         '2');

/// MODULE FUNCTIONS //////////////////////////////////////////////////////

/**
 * @todo Documenting this function
 */
function stampcoll_user_outline($course, $user, $mod, $stampcoll) {
    if ($stamps = get_records_select("stampcoll_stamps", "userid=$user->id AND stampcollid=$stampcoll->id")) {
        $result->info = get_string("numberofcollectedstamps", "stampcoll").": ".count($stamps);
        $result->time = 0;  // empty
        return $result;
    }
    return NULL;
}

/**
 * @todo Documenting this function
 */
function stampcoll_user_complete($course, $user, $mod, $stampcoll) {
    if (!$allstamps = stampcoll_get_stamps($stampcoll->id)) {
        // no stamps yet in this instance
        if ($stampcoll->publish == STAMPCOLL_PUBLISH_NONE) {
            echo get_string("stampsarenotpublic", "stampcoll");
            return true;
        } else { 
            echo get_string("nostampscollected", "stampcoll");
            return true;
        }
    }

    $userstamps = array();
    foreach ($allstamps as $s) {
        $userstamps[$s->userid][] = $s;
    }
    unset($allstamps);
    unset($s);

    if ((isteacher($course->id)) || ($stampcoll->publish <> STAMPCOLL_PUBLISH_NONE)) {
        if (isset($userstamps[$user->id])) {
            $mystamps = $userstamps[$user->id];
        } else {
            $mystamps = array();
        }
        unset($userstamps);
        $stampimage = stampcoll_image($stampcoll->id);
        $stampimages = format_text(get_string("numberofcollectedstamps", "stampcoll").": ".count($mystamps));
        foreach ($mystamps as $s) {
            $stampimages .= '<li>';
            $link = userdate($s->timemodified). ' ';
            $stampimages .= stampcoll_linktostampdetails($s->id, $link);
            $stampimages .= format_text($s->comment);
            $stampimages .= '</li>';
        }
        unset($s);

        echo '<div class="stamppictures">'.$stampimages.'</div>';
    } else {
        echo get_string("nostamps", "stampcoll");
    }
}

/**
 * Create a new instance of stamp collection and return the id number. 
 *
 * @param object $stampcoll Object containing data defined by the form in mod.html
 * @return int ID number of the new instance
 */
function stampcoll_add_instance($stampcoll) {
    $stampcoll->timemodified = time();
    $stampcoll->text = trim($stampcoll->text);
    return insert_record("stampcoll", $stampcoll);
}

/**
 * Update an existing instance of stamp collection with new data.
 *
 * @param object $stampcoll Object containing data defined by the form in mod.html
 * @return boolean
 */
function stampcoll_update_instance($stampcoll) {
    $stampcoll->id = $stampcoll->instance;
    $stampcoll->timemodified = time();
    $stampcoll->text = trim($stampcoll->text);
    return update_record('stampcoll', $stampcoll);
}


/**
 * Delete the instance of stamp collection and any data that depends on it.
 *
 * @param int $id ID of an instance to be deleted
 * @return bool
 */
function stampcoll_delete_instance($id) {
    if (! $stampcoll = get_record("stampcoll", "id", "$id")) {
        return false;
    }

    $result = true;

    if (! delete_records("stampcoll_stamps", "stampcollid", "$stampcoll->id")) {
        $result = false;
    }

    if (! delete_records("stampcoll", "id", "$stampcoll->id")) {
        $result = false;
    }

    return $result;
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
 * Get all users who can collect stamps in the given Stamp Collection
 *
 * Returns array of users with the capability mod/stampcoll:collectstamps. Caller may specify the group.
 * If groupmembersonly used, do not return users who are not in any group.
 *
 * @uses $CFG;
 * @param object $cm Course module record
 * @param object $context Current context
 * @param int $currentgroup ID of group the users must be in
 * @return array Array of users
 */
function stampcoll_get_users_can_collect($cm, $context, $currentgroup=false) {
    global $CFG;
    $users = get_users_by_capability($context, 'mod/stampcoll:collectstamps', 'u.id,u.picture,u.firstname,u.lastname',
                        '', '', '', $currentgroup, '', false, true);

    /// If groupmembersonly used, remove users who are not in any group
    /// XXX this has not been tested yet !!!
    if ($users && !empty($CFG->enablegroupings) && $cm->groupmembersonly) {
        if ($groupingusers = groups_get_grouping_members($cm->groupingid, 'u.id,u.picture,u.firstname,u.lastname' )) {
            $users = array_intersect($users, $groupingusers);
        }
    }
    return $users;
}

/**
 * Return full record of the stamp collection.
 *
 * @param int $stampcallid ID of an module instance
 * @return object Object containing instance data
 */
function stampcoll_get_stampcoll($stampcollid) {
    return get_record("stampcoll", "id", $stampcollid);
}

/**
 * Return all stamps in module instance.
 *
 * @param int $stamcallid ID of an module instance
 * @return array|false Array of found stamps (as objects) or false if no stamps or error occured
 */
function stampcoll_get_stamps($stampcollid) {
    return get_records("stampcoll_stamps", "stampcollid", $stampcollid, "id");
}

/**
 * Return one stamp.
 *
 * @param int $stamid ID of an stamp record
 * @return object|false Found stamp (as object) or false if not such stamp or error occured
 */
function stampcoll_get_stamp($stampid) {
    return get_record("stampcoll_stamps", "id", $stampid);
}

/**
 * Generate HTML to print the stamp image.
 *
 * @uses $CFG
 * @uses $course Is this a hack?
 * @param int $stampcollid ID of an module instance
 * @return string HTML tag to print the stamp image
 * @todo Maybe replace global $course by $COURSE
 */
function stampcoll_image($stampcollid, $alt="") {
    global $CFG, $course;
    $sc = stampcoll_get_stampcoll($stampcollid);
    $tag = '<img border="0" src="';
    if(empty($sc->image) || $sc->image == "default") {
        $tag .= "$CFG->wwwroot/mod/stampcoll/defaultstamp.gif";
    } else {
        if ($CFG->slasharguments) {
        $tag .= "$CFG->wwwroot/file.php/$course->id/$sc->image";

        } else {
            $tag .= "$CFG->wwwroot/file.php?file=/$course->id/$sc->image";
        }
    }
    $tag .= '" alt="'.$alt.'"';
    $tag .= ' />';
    return $tag;
}


/**
 * Generate HTML link to popup new windows with stamp details.
 *
 * @param int $stampid ID of an stamp
 * @param string $linkname Text to be displayed as web link
 * @return string HTML to print the link
 */
function stampcoll_linktostampdetails($stampid, $linkname='click here', $title='') {
    $title = strip_tags($title);
    $title = str_replace("\"", "`", $title);
    $title = str_replace("'", "`", $title);
    return link_to_popup_window("/mod/stampcoll/popupcomment.php?id=$stampid", 'popup', $linkname, 250, 400, $title, 'none', true);
}


?>
