<?php  // $Id$

    require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
    require_once(dirname(__FILE__).'/lib.php');

    $id = required_param('id',PARAM_INT);   // stamp ID

    if (! $stamp = stampcoll_get_stamp($id)) {
        error("Invalid stamp ID");
    }
 
    if (! $stampcoll = stampcoll_get_stampcoll($stamp->stampcollid)) {
        error("Invalid stamp collection ID");
    }

    if (! $cm = get_coursemodule_from_instance('stampcoll', $stampcoll->id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $stampcoll->course)) {
        error("Invalid course ID");
    }

/// Get capabilities
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    include(dirname(__FILE__).'/caps.php');

/// Check if the user is allowed to see this stamp
    if (empty($USER->id) 
        || (($USER->id !== $stamp->userid) && (! $cap_viewotherstamps))
        || (($USER->id == $stamp->userid) && (! $cap_viewownstamps))) {
            error("You are not allowed to view this information");
    }
    
    $stampimage = stampcoll_image($stampcoll->id);

    add_to_log($course->id, "stampcoll", "view stamp", "popupcomment.php?id=$id", $stamp->userid, '');

    print_header();

    print_box_start();

    echo '<div class="picture">'.$stampimage.'</div>';
    echo '<div class="comment">'.format_text($stamp->comment).'</div>';
    echo '<div class="timemodified">'.get_string('timemodified', 'stampcoll').': '.userdate($stamp->timemodified).'</div>';
    
    print_box_end();

    close_window_button();

    print_footer('none');

?>
