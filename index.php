<?php  // $Id$

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_course_login($course);

    add_to_log($course->id, "stampcoll", "view all", "index.php?id=$course->id", "");

    $strstamps = get_string("modulenameplural", "stampcoll");

    $navigation = build_navigation($strstamps);
    print_header_simple("$strstamps", "",
                 $navigation, "", "", true, "", navmenu($course));


    if (! $stampcolls = get_all_instances_in_course("stampcoll", $course)) {
        notice("There are no stamp collections", "../../course/view.php?id=$course->id");
    }

    if ($course->format == "weeks") {
        $table->head  = array (get_string("week"), get_string("name"), get_string("numberofstamps", "stampcoll"));
        $table->align = array ("center", "left", "center");
    } else if ($course->format == "topics") {
        $table->head  = array (get_string("topic"), get_string("name"), get_string("numberofstamps", "stampcoll"));
        $table->align = array ("center", "left", "center");
    } else {
        $table->head  = array (get_string("name"), get_string("numberofstamps", "stampcoll") );
        $table->align = array ("left", "left");
    }

    $currentsection = "";

    foreach ($stampcolls as $stampcoll) {
        if (!$allstamps = stampcoll_get_stamps($stampcoll->id)) {
            $allstamps = array();
        }
        $count_totalstamps = count($allstamps);

        $userstamps = array();
        foreach ($allstamps as $s) {
            $userstamps[$s->userid][] = $s;
        }
        unset($allstamps);
        unset($s);

        if (!isteacher($course->id) && $stampcoll->publish == STAMPCOLL_PUBLISH_NONE) {
            $count_mystamps = get_string("stampsarenotpublic", "stampcoll");
        } else {
            if (isset($userstamps[$USER->id])) {
                $count_mystamps = count($userstamps[$USER->id]);
            } else {
                $count_mystamps = 0;
            }
        }

        $printsection = "";
        if ($stampcoll->section !== $currentsection) {
            if ($stampcoll->section) {
                $printsection = $stampcoll->section;
            }
            if ($currentsection !== "") {
                $table->data[] = 'hr';
            }
            $currentsection = $stampcoll->section;
        }
        
        //Calculate the href
        if (!$stampcoll->visible) {
            //Show dimmed if the mod is hidden
            $tt_href = "<a class=\"dimmed\" href=\"view.php?id=$stampcoll->coursemodule\">".format_string($stampcoll->name,true)."</a>";
        } else {
            //Show normal if the mod is visible
            $tt_href = "<a href=\"view.php?id=$stampcoll->coursemodule\">".format_string($stampcoll->name,true)."</a>";
        }

        $aa = '';
        
        if (isteacher($course->id)) {
            if ($stampcoll->teachercancollect) {
                $aa .= $count_mystamps;
            }
            $aa .= " ($count_totalstamps)";
        } else {        
            $aa = $count_mystamps;
        }
        
        if ($course->format == "weeks" || $course->format == "topics") {
            $table->data[] = array ($printsection, $tt_href, $aa);
        } else {
            $table->data[] = array ($tt_href, $aa);
        }
    }
    echo "<br />";
    print_table($table);

    print_footer($course);
 
?>
