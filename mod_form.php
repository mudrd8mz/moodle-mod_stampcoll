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
 * This file defines the main Stamp collection module setting form
 *
 * @package    mod_stampcoll
 * @copyright  2008 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/lib/filelib.php');

/**
 * Stamp collection module setting form
 */
class mod_stampcoll_mod_form extends moodleform_mod {

    /**
     * Defines the form
     */
    public function definition() {
        global $COURSE, $CFG;

        $mform = $this->_form;

        // General.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Name.
        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Description.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor(false);
        }

        // Stamp collection.
        $mform->addElement('header', 'stampcollection', get_string('modulename', 'stampcoll'));

        // Stamp image.
        $imageoptions = array('subdirs' => false, 'maxfiles' => 1, 'accepted_types' => array('image'),
            'maxbytes' => $COURSE->maxbytes, 'return_types' => FILE_INTERNAL);
        $mform->addElement('filemanager', 'image', get_string('stampimage', 'stampcoll'), null, $imageoptions);
        $mform->addHelpButton('image', 'stampimage', 'stampcoll');

        // Display users with no stamps.
        $mform->addElement('selectyesno', 'displayzero', get_string('displayzero', 'stampcoll'));
        $mform->setDefault('displayzero', 0);

        // Common module settings.
        $this->standard_coursemodule_elements();

        // Buttons.
        $this->add_action_buttons();
    }

    /**
     * Sets the default form data
     *
     * When editing an existing instance, this method copies the current stamp image into the
     * draft area (standard filemanager workflow).
     *
     * @param array $defaultvalues
     */
    public function data_preprocessing(&$defaultvalues) {
        global $COURSE;

        parent::data_preprocessing($defaultvalues);

        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('image');
            $options = array('subdirs' => false, 'maxfiles' => 1, 'accepted_types' => array('image'),
                'maxbytes' => $COURSE->maxbytes, 'return_types' => FILE_INTERNAL);
            file_prepare_draft_area($draftitemid, $this->context->id, 'mod_stampcoll', 'image', 0, $options);
            $defaultvalues['image'] = $draftitemid;
        }
    }
}
