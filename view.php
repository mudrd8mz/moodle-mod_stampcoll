<?php  // $Id$

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id',PARAM_INT);    // Course Module ID

    if (! $cm = get_record("course_modules", "id", $id)) {
        error("Course Module ID was incorrect");
    }

    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }

    require_course_login($course, false, $cm);

    if (!$stampcoll = stampcoll_get_stampcoll($cm->instance)) {
        error("Course module is incorrect");
    }

    $stampimage = stampcoll_image($stampcoll->id);
    $strstampcoll = get_string("modulename", "stampcoll");
    $strstampcolls = get_string("modulenameplural", "stampcoll");

    add_to_log($course->id, "stampcoll", "view", "view.php?id=$cm->id", $stampcoll->id, $cm->id);

    print_header_simple(format_string($stampcoll->name), "",
                 "<a href=\"index.php?id=$course->id\">$strstampcolls</a> -> ".format_string($stampcoll->name), "", "", true,
                  update_module_button($cm->id, $course->id, $strstampcoll), navmenu($course, $cm));

    if (isteacher($course->id)) {
        echo '<div class="reportlink">';
        echo "<a href=\"editstamps.php?id=$cm->id\">".get_string("editstamps", "stampcoll")."</a>";
        echo '</div>';
    } else if (!$cm->visible) {
        notice(get_string("activityiscurrentlyhidden"), "../../course/view.php?id=$course->id");
    }

    if ($stampcoll->text) {
        print_simple_box(format_text($stampcoll->text, $stampcoll->format), 'center', '70%', '', 5, 'generalbox', 'intro');
    }

    if ((!isteacher($course->id)) && ($stampcoll->publish == STAMPCOLL_PUBLISH_NONE)) {
        notice(get_string("stampsarenotpublic", "stampcoll"), "../../course/view.php?id=$course->id");
    }
    
    if (!$allstamps = stampcoll_get_stamps($stampcoll->id)) {
        notice(get_string('nostampsyet', 'stampcoll'), "../../course/view.php?id=$course->id");
    }
    
    /// Load all stamps into an array
    $userstamps = array();
    foreach ($allstamps as $s) {
        $userstamps[$s->userid][] = $s; 
    }
    unset($allstamps);
    unset($s);
    
    if ((!isteacher($course->id)) && ($stampcoll->publish == STAMPCOLL_PUBLISH_SELFONLY)) {
        if (isset($userstamps[$USER->id])) {
            $mystamps = $userstamps[$USER->id];
        } else {
            $mystamps = array();
        }
        unset($userstamps);
        $stampimages = '';
        foreach ($mystamps as $s) {
            $stampimages .= stampcoll_linktostampdetails($s->id, $stampimage, $s->comment);
        }
        unset($s);

        print_simple_box_start('center', '70%');

        print_heading(get_string('numberofyourstamps', 'stampcoll', count($mystamps)));
        echo '<div class="stamppictures">'.$stampimages.'</div>';

        print_simple_box_end();
        
    }
    
    if ((isteacher($course->id)) || ($stampcoll->publish == STAMPCOLL_PUBLISH_ALL)) {
        /// Check to see if groups are being used in this stampcoll
        if ($groupmode = groupmode($course, $cm)) {   // Groups are being used
            $currentgroup = setup_and_print_groups($course, $groupmode, "view.php?id=$cm->id");
        } else {
            $currentgroup = false;
        }

        if ($currentgroup) {
            $users = get_group_users($currentgroup, "u.firstname ASC", '', 'u.id, u.picture, u.firstname, u.lastname');
        } else {
            $users = get_course_users($course->id, "u.firstname ASC", '', 'u.id, u.picture, u.firstname, u.lastname') + get_admins();
        }

        if (!$users) {
            print_heading(get_string("nousersyet"));
        }


        /// First we check to see if the form has just been submitted
        /// to request user_preference updates
        if (isset($_POST['updatepref'])){
            $perpage = optional_param('perpage', 30, PARAM_INT);
            $perpage = ($perpage <= 0) ? 30 : $perpage ;
            set_user_preference('stampcoll_perpage', $perpage);
        }

        /// Next we get perpage param from database
        $perpage    = get_user_preferences('stampcoll_perpage', 30);
        
        $page = optional_param('page', 0, PARAM_INT);

        $tablecolumns = array('picture', 'fullname', 'count', 'stamps');
        $tableheaders = array('', get_string('fullname'), get_string('numberofstamps', 'stampcoll'), '');

        require_once($CFG->libdir.'/tablelib.php');

        $table = new flexible_table('mod-stampcoll-stamps');

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->define_baseurl($CFG->wwwroot.'/mod/stampcoll/view.php?id='.$cm->id.'&amp;currentgroup='.$currentgroup);

        $table->sortable(true);
        $table->collapsible(false);
        $table->initialbars(true);

        $table->column_class('picture', 'picture');
        $table->column_class('fullname', 'fullname');
        $table->column_class('count', 'count');
        $table->column_class('stamps', 'stamps');
        $table->column_style('stamps', 'width', '50%');

        $table->set_attribute('cellspacing', '0');
        $table->set_attribute('id', 'stamps');
        $table->set_attribute('class', 'stamps');
        $table->set_attribute('width', '90%');
        $table->set_attribute('align', 'center');

        $table->setup();

        if (!$stampcoll->teachercancollect) {
            $teachers = get_course_teachers($course->id);
            if (!empty($teachers)) {
                $keys = array_keys($teachers);
            }
            foreach ($keys as $key) {
                unset($users[$key]);
            }
        }
        
        if (empty($users)) {
            print_heading(get_string('nousers','stampcoll'));
            return true;
        }

    /// Construct the SQL

        if ($where = $table->get_sql_where()) {
            $where .= ' AND ';
        }

        
        
        if ($sort = $table->get_sql_sort()) {
            $sort = ' ORDER BY '.$sort;
        }

        $select = 'SELECT u.id, u.firstname, u.lastname, u.picture, COUNT(s.id) AS count ';
        $sql = 'FROM '.$CFG->prefix.'user AS u '.
               'LEFT JOIN '.$CFG->prefix.'stampcoll_stamps s ON u.id = s.userid AND s.stampcollid = '.$stampcoll->id.' '.
               'WHERE '.$where.'u.id IN ('.implode(',', array_keys($users)).') GROUP BY u.id, u.firstname, u.lastname, u.picture ';

        if (!$stampcoll->displayzero) {
            $sql .= 'HAVING COUNT(s.id) > 0 ';
        }

        $table->pagesize($perpage, count($users));
        
        if($table->get_page_start() !== '' && $table->get_page_size() !== '') {
            $limit = ' '.sql_paging_limit($table->get_page_start(), $table->get_page_size());     
        }
        else {
            $limit = '';
        }

        if (($ausers = get_records_sql($select.$sql.$sort.$limit)) !== false) {
            
            foreach ($ausers as $auser) {
                $picture = print_user_picture($auser->id, $course->id, $auser->picture, false, true);
                $fullname = fullname($auser);
                $count = $auser->count;
                $stamps = '';
                if (isset($userstamps[$auser->id])) {
                    foreach ($userstamps[$auser->id] as $s) {
                        $stamps .= stampcoll_linktostampdetails($s->id, $stampimage, $s->comment);
                    }
                    unset($s);
                }
                $row = array($picture, $fullname, $count, $stamps);
                $table->add_data($row);
            }
        }
        
        $table->print_html();  /// Print the whole table
        
        /// Mini form for setting user preference
        echo '<br />';
        echo '<form name="options" action="view.php?id='.$cm->id.'" method="post">';
        echo '<input type="hidden" id="updatepref" name="updatepref" value="1" />';
        echo '<table id="optiontable" align="center">';
        echo '<tr align="right"><td>';
        echo '<label for="perpage">'.get_string('studentsperpage','stampcoll').'</label>';
        echo ':</td>';
        echo '<td align="left">';
        echo '<input type="text" id="perpage" name="perpage" size="1" value="'.$perpage.'" />';
        helpbutton('pagesize', get_string('studentsperpage','stampcoll'), 'stampcoll');
        echo '</td></tr>';
        echo '<tr>';
        echo '<td colspan="2" align="right">';
        echo '<input type="submit" value="'.get_string('savepreferences').'" />';
        echo '</td></tr></table>';
        echo '</form>';
        ///End of mini form
    }
        
    print_footer($course);
?>
