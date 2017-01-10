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
 * Stamp collection module rendering support
 *
 * @package    mod_stampcoll
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Stamp collection module renderer
 */
class mod_stampcoll_renderer extends plugin_renderer_base {

    /**
     * Renders a stamp image
     *
     * @param stampcoll_stamp $stamp
     * @return string HTML
     */
    protected function render_stampcoll_stamp(stampcoll_stamp $stamp) {

        if (empty($stamp->stampcoll->image)) {
            $src = $this->pix_url('defaultstamp', 'mod_stampcoll');

        } else {
            $src = moodle_url::make_pluginfile_url($stamp->stampcoll->context->id, 'mod_stampcoll',
                'image', 0, '/', $stamp->stampcoll->image);
        }

        $attributes = array('src' => $src, 'alt' => get_string('stampalt', 'mod_stampcoll'), 'class' => 'stamp');
        $stampimg   = html_writer::empty_tag('img', $attributes);
        $stampimg = html_writer::div($stampimg, 'stamp-image');
        $stamptext = html_writer::div(nl2br(s($stamp->text)), 'stamp-text');

        $stampdiv = html_writer::div($stampimg.$stamptext, 'stamp-wrapper');

        return $stampdiv;
    }

    /**
     * Renders stamps collected by a single user
     *
     * @param stampcoll_collection $collection
     * @return string
     */
    protected function render_stampcoll_singleuser_collection(stampcoll_singleuser_collection $collection) {
        global $USER;

        $holder = $collection->get_holder();

        if (empty($holder)) {
            throw new coding_exception('attempt to render stampcoll_singleuser_collection without registered holder');
        }

        $out = $this->output->heading($this->output->user_picture($holder) . ' ' . fullname($holder), 3);

        $collected = $collection->list_stamps($holder->id);
        $count     = count($collected);

        if ($count == 0) {
            $out .= $this->output->heading(get_string('nostampscollected', 'stampcoll'), 4);

        } else if ($holder->id == $USER->id) {
            $out .= $this->output->heading(get_string('numberofyourstamps', 'stampcoll', $count), 4);

        } else {
            $out .= $this->output->heading(get_string('numberofcollectedstamps', 'stampcoll', $count), 4);
        }

        if (!empty($collected)) {
            $out .= $this->output->box_start('collection singleuser');
            foreach ($collected as $stamp) {
                $out .= $this->render($stamp);
            }
            $out .= $this->output->box_end();
        }

        return $out;
    }

    /**
     * Renders a table with multiple users' stamps
     *
     * @param stampcoll_collection $collection
     * @return string HTML
     */
    protected function render_stampcoll_multiuser_collection(stampcoll_multiuser_collection $collection) {

        $holders = $collection->list_stamp_holders();

        if (empty($holders)) {
            return $this->output->heading(get_string('nostampsincollection', 'stampcoll'), 4);
        }

        $htmlpagingbar = $this->render(new paging_bar($collection->totalcount, $collection->page,
            $collection->perpage, $this->page->url, 'page'));

        $table = new html_table();
        $table->attributes['class'] = 'collection multiuser';

        $sortbyfirstname = $this->helper_sortable_heading(get_string('firstname'),
            'firstname', $collection->sortedby, $collection->sortedhow);
        $sortbylastname = $this->helper_sortable_heading(get_string('lastname'),
            'lastname', $collection->sortedby, $collection->sortedhow);
        if ($this->helper_fullname_format() == 'lf') {
            $sortbyname = $sortbylastname . ' / ' . $sortbyfirstname;
        } else {
            $sortbyname = $sortbyfirstname . ' / ' . $sortbylastname;
        }

        $sortbycount = $this->helper_sortable_heading(get_string('numberofstamps', 'stampcoll'),
            'count', $collection->sortedby, $collection->sortedhow);

        $table->head = array('', $sortbyname, $sortbycount, '');

        $table->colclasses = array('picture', 'fullname', 'count', 'stamps');

        foreach ($holders as $holder) {
            $picture    = $this->output->user_picture($holder);
            $fullname   = fullname($holder);
            $fullname   = html_writer::link(
                            new moodle_url($collection->stampcoll->view_url(), array('view' => 'single', 'userid' => $holder->id)),
                            $fullname);
            $collected  = $collection->list_stamps($holder->id);
            $count      = count($collected);
            $stamps     = '';

            if (!empty($collected)) {
                foreach ($collected as $stamp) {
                    $stamps .= $this->render($stamp);
                }
            }

            $row = array($picture, $fullname, $count, $stamps);
            $table->data[] = $row;
        }

        $htmltable       = html_writer::table($table);
        $htmlpreferences = $this->helper_preferences_form($collection->perpage);

        return $htmlpagingbar . $htmltable . $htmlpagingbar . $htmlpreferences;
    }

    /**
     * Renders a table with multiple users' stamps
     *
     * @param stampcoll_collection $collection
     * @return string HTML
     */
    protected function render_stampcoll_management_collection(stampcoll_management_collection $collection) {

        $holders = $collection->list_stamp_holders();

        if (empty($holders)) {
            return $this->output->heading(get_string('nocollectingusers', 'stampcoll'), 3);
        }

        $htmlpagingbar = $this->render(new paging_bar($collection->totalcount, $collection->page,
            $collection->perpage, $this->page->url, 'page'));

        $table = new html_table();
        $table->attributes['class'] = 'collection management';

        $sortbyfirstname = $this->helper_sortable_heading(get_string('firstname'),
            'firstname', $collection->sortedby, $collection->sortedhow);
        $sortbylastname = $this->helper_sortable_heading(get_string('lastname'),
            'lastname', $collection->sortedby, $collection->sortedhow);
        if ($this->helper_fullname_format() == 'lf') {
            $sortbyname = $sortbylastname . ' / ' . $sortbyfirstname;
        } else {
            $sortbyname = $sortbyfirstname . ' / ' . $sortbylastname;
        }

        $sortbycount = $this->helper_sortable_heading(get_string('numberofstamps', 'stampcoll'),
            'count', $collection->sortedby, $collection->sortedhow);

        $table->head = array(
            '',
            $sortbyname,
            $sortbycount,
            get_string('stamptext', 'mod_stampcoll'),
            get_string('stampgivenon', 'mod_stampcoll'),
            get_string('stampgivenby', 'mod_stampcoll'),
            get_string('action')
        );

        foreach ($holders as $holder) {
            $picture = $this->output->user_picture($holder);
            $fullname = fullname($holder);
            $holder->fullname = $fullname;
            $fullname = html_writer::link(
                new moodle_url($collection->stampcoll->view_url(), array('view' => 'single', 'userid' => $holder->id)),
                $fullname
            );
            $collected = $collection->list_stamps($holder->id);
            $count = count($collected);

            $label = html_writer::tag('label', get_string('managegivestampto', 'mod_stampcoll', $holder), array(
                'for' => 'stampcoll_add_'.$holder->id,
            ));

            $textform = html_writer::tag('textarea', '', array(
                'placeholder' => get_string('addstamp', 'mod_stampcoll'),
                'id' => 'stampcoll_add_'.$holder->id,
                'name' => 'addnewstamp['.$holder->id.']'
            ));

            $row = new html_table_row(array($picture, $fullname, $count, $label.$textform));
            $row->attributes['class'] = 'holderinfo';
            foreach ($row->cells as $cell) {
                $cell->rowspan = $count + 1;
            }
            // Make the cell for adding new stamp spanning over stamp-info cells.
            $cell->rowspan = 1;
            $cell->colspan = 4;
            $table->data[] = $row;

            if (!empty($collected)) {
                $counter = count($collected);
                foreach ($collected as $stamp) {
                    $label = html_writer::tag(
                        'label',
                        get_string('manageupdatestamp', 'mod_stampcoll', array(
                            'fullname' => $holder->fullname,
                            'number' => $counter,
                        )),
                        array(
                            'for' => 'stampcoll_update_'.$stamp->id,
                        )
                    );
                    $newtext = html_writer::tag('textarea', s($stamp->text), array(
                        'id' => 'stampcoll_update_'.$stamp->id,
                        'name' => 'stampnewtext['.$stamp->id.']',
                    ));
                    $oldtext = html_writer::empty_tag('input',
                        array('value' => s($stamp->text), 'type' => 'hidden', 'name' => 'stampoldtext['.$stamp->id.']'));
                    if ($stamp->giverid) {
                        $giver = $collection->get_user_info($stamp->giverid);
                        $picture = $this->output->user_picture($giver, array('size' => 16));
                        $fullname = fullname($giver);
                        $giver = $picture . ' ' . $fullname;
                    } else {
                        $giver = '-';
                    }
                    $row = new html_table_row(array(
                        $label.$newtext.$oldtext,
                        userdate($stamp->timecreated, get_string('strftimedate', 'core_langconfig')),
                        $giver,
                        html_writer::link(new moodle_url($collection->stampcoll->managestamps_url(),
                                          array('delete' => $stamp->id)), get_string('deletestamp', 'mod_stampcoll')),
                    ));
                    $row->attributes['class'] = 'stampinfo';
                    $table->data[] = $row;
                    $counter--;
                }
            }
        }

        $htmltable = html_writer::table($table);
        $htmlsubmit = html_writer::tag('div',
            html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('updatestamps', 'mod_stampcoll'),
                'class' => 'btn btn-primary')).
            html_writer::empty_tag('input', array('type' => 'hidden', 'value' => sesskey(), 'name' => 'sesskey')),
            array('class' => 'submitwrapper'));
        $htmlform = html_writer::tag('form', $htmltable . $htmlsubmit,
            array('id' => 'stampsmanager', 'action' => $this->page->url->out(), 'method' => 'post'));
        $htmlpreferences = $this->helper_preferences_form($collection->perpage);

        return $htmlpagingbar . $htmlform . $htmlpagingbar . $htmlpreferences;
    }

    /**
     * Renders a form to set the multiuser collection view preferences
     *
     * @param int $perpage current value of users per page setting
     * @return string HTML
     */
    protected function helper_preferences_form($perpage) {
        global $CFG;
        require_once($CFG->libdir.'/formslib.php');

        $mform = new MoodleQuickForm('preferences', 'post', $this->page->url, '', array('class' => 'preferences'));

        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->addElement('hidden', 'updatepref', 1);

        $mform->addElement('header', 'qgprefs', get_string('preferences'));
        $mform->setExpanded('qgprefs', false);

        $mform->addElement('text', 'perpage', get_string('perpage', 'stampcoll'), array('size' => 2));
        $mform->setType('perpage', PARAM_INT);
        $mform->setDefault('perpage', $perpage);

        $mform->addElement('submit', 'savepreferences', get_string('savepreferences'));

        ob_start();
        $mform->display();
        $out = ob_get_clean();

        return $out;
    }

    /**
     * Renders a text with icons to sort by the given column
     *
     * This is intended for table headings.
     *
     * @param string $text    The heading text
     * @param string $sortid  The column id used for sorting
     * @param string $sortby  Currently sorted by (column id)
     * @param string $sorthow Currently sorted how (ASC|DESC)
     *
     * @return string
     */
    protected function helper_sortable_heading($text, $sortid=null, $sortby=null, $sorthow=null) {

        $out = html_writer::tag('span', $text, array('class' => 'text'));

        if (!is_null($sortid)) {
            if ($sortby !== $sortid or $sorthow !== 'ASC') {
                $url = new moodle_url($this->page->url);
                $url->params(array('sortby' => $sortid, 'sorthow' => 'ASC'));
                $out .= $this->output->action_icon($url,
                    new pix_icon('t/up', get_string('sortbyx', 'core', s($text)), null, array('class' => 'sort asc')));
            }
            if ($sortby !== $sortid or $sorthow !== 'DESC') {
                $url = new moodle_url($this->page->url);
                $url->params(array('sortby' => $sortid, 'sorthow' => 'DESC'));
                $out .= $this->output->action_icon($url,
                    new pix_icon('t/down', get_string('sortbyxreverse', 'core', s($text)), null, array('class' => 'sort desc')));
            }
        }
        return $out;
    }

    /**
     * Tries to guess the fullname format set at the site
     *
     * @return string fl|lf
     */
    protected function helper_fullname_format() {

        $fake = new stdClass();
        $fake->lastname = 'LLLL';
        $fake->firstname = 'FFFF';
        $fullname = get_string('fullnamedisplay', '', $fake);
        if (strpos($fullname, 'LLLL') < strpos($fullname, 'FFFF')) {
            return 'lf';
        } else {
            return 'fl';
        }
    }
}
