<?php  // $Id$

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);   // stamp ID

    if (! $stamp = stampcoll_get_stamp($id)) {
        error("Invalid stamp ID");
    }
 
    if (! $stampcoll = stampcoll_get_stampcoll($stamp->stampcollid)) {
        error("Invalid stamp collection ID");
    }

    if (! $course = get_record("course", "id", $stampcoll->course)) {
        error("Invalid course ID");
    }

    if (empty($USER->id) || (!isteacher($course->id) && $USER->id !== $stamp->userid) && $stampcoll->publish !== STAMPCOLL_DISPLAY_ALL) {
        error("You are not allowed to view this information");
    }
    
    $stampimage = stampcoll_image($stampcoll->id);

    add_to_log($course->id, "stampcoll", "view stamp", "popupcomment.php?id=$id", $stamp->userid, '');

    print_header();

    print_simple_box_start('center', '96%');

    echo '<div class="picture">'.$stampimage.'</div>';
    echo '<div class="comment">'.format_text($stamp->comment).'</div>';
    echo '<div class="timemodified">'.get_string('timemodified', 'stampcoll').': '.userdate($stamp->timemodified).'</div>';
    
    print_simple_box_end();

    close_window_button();

    print_footer('none');

?>
