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
 * Defines a form to add or edit a stamp
 *
 * @package    mod
 * @subpackage stampcoll
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Defines a form to add or edit a stamp
 */
class stampcoll_stamp_form extends moodleform {

    /**
     * Defines the form elements
     */
    public function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $data  = $this->_customdata;

        //----------------------------------------------------------------------
        if (empty($data['current'])) {
            // the form is used to add a new stamp
            $mform->addElement('header', 'stampform', get_string('addstamp', 'stampcoll'));
        } else {
            // the form is used to edit some existing stamp
            $mform->addElement('header', 'stampform', get_string('editstamp', 'stampcoll'));
        }

        //----------------------------------------------------------------------
        if (!empty($data['userfrom'])) {
            // we have the giver's details available - let us display them
            $mform->addElement('static', 'from',
                get_string('from'),
                $OUTPUT->user_picture($data['userfrom'], array('size' => 16)).' '.fullname($data['userfrom']));
        }

        //----------------------------------------------------------------------
        $mform->addElement('textarea', 'text', get_string('stamptext', 'stampcoll'), array('cols' => 40, 'rows' => 5));
        $mform->setType('text', PARAM_RAW);

        //----------------------------------------------------------------------
        $mform->addGroup(array(
            $mform->createElement('submit', 'submit', get_string('addstampbutton', 'stampcoll')),
            $mform->createElement('cancel', 'cancel', get_string('cancel'))),
            'controlbuttons', '&nbsp;', array(' '), false);

        //----------------------------------------------------------------------
        $mform->addElement('hidden', 'userto');
        $mform->setType('userto', PARAM_INT);

        $mform->addElement('hidden', 'userfrom');
        $mform->setType('userfrom', PARAM_INT);
    }
}
