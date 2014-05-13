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
 * Internal library of functions for stamp collection module
 *
 * @package    mod_stampcoll
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Full-featured Stamps collection module API
 *
 * This wraps the stampcoll database record with a set of methods that are called
 * from the module itself. The class should be initialized right after you get
 * $stampcoll, $cm and $course records at the begining of the script.
 */
class stampcoll {

    /** default number of users per page when displaying reports */
    const USERS_PER_PAGE = 30;

    /** @var stdClass course module record */
    public $cm;

    /** @var stdClass course record */
    public $course;

    /** @var stdClass context object */
    public $context;

    /** @var int stampcoll instance identifier */
    public $id;

    /** @var string stampcoll activity name */
    public $name;

    /** @var string introduction or description of the activity */
    public $intro;

    /** @var int format of the {@link $intro} */
    public $introformat;

    /** @var null|string null to use the default image name, the file name otherwise */
    public $image;

    /** @var int the last modification time stamps */
    public $timemodified;

    /** @var bool should the users without any stamp be listed, too */
    public $displayzero;

    /**
     * Initializes the stampcoll API instance using the data from DB
     *
     * Makes deep copy of all passed records properties. Replaces integer $course attribute
     * with a full database record (course should not be stored in instances table anyway).
     *
     * @param stdClass $dbrecord stamps collection instance data from the {stampcoll} table
     * @param stdClass|cm_info $cm course module record as returned by {@link get_coursemodule_from_id()}
     * @param stdClass $course course record from {course} table
     * @param stdClass $context the context of the stampcoll instance
     */
    public function __construct(stdClass $dbrecord, stdClass $cm, stdClass $course, stdClass $context=null) {
        foreach ($dbrecord as $field => $value) {
            if (property_exists('stampcoll', $field)) {
                $this->{$field} = $value;
            }
        }
        $this->cm = $cm;
        $this->course = $course;
        if (is_null($context)) {
            $this->context = context_module::instance($this->cm->id);
        } else {
            $this->context = $context;
        }
    }

    /**
     * @return moodle_url to view the stampcoll instance
     */
    public function view_url() {
        return new moodle_url('/mod/stampcoll/view.php', array('id' => $this->cm->id));
    }

    /**
     * @return moodle_url to manage stamps in this stampcoll instance
     */
    public function managestamps_url() {
        return new moodle_url('/mod/stampcoll/managestamps.php', array('cmid' => $this->cm->id));
    }
}

/**
 * Represents a single stamp to be rendered
 */
class stampcoll_stamp implements renderable {

    /** @var stampcoll stampcoll instance */
    public $stampcoll;

    /** @var int */
    public $id;

    /** @var int */
    public $holderid;

    /** @var int|null */
    public $giverid = null;

    /** @var int|null */
    public $modifierid = null;

    /** @var string */
    public $text = null;

    /** @var int */
    public $timecreated = null;

    /** @var int|null */
    public $timemodified = null;

    /**
     * Maps the data from the database record to the instance properties
     *
     * @param stampcoll $stampcoll module instance
     * @param stdClass $record database record from mdl_stamcoll_stamps table
     */
    public function __construct(stampcoll $stampcoll, stdClass $record) {

        $this->stampcoll = $stampcoll;

        if (!isset($record->id) or !isset($record->userid)) {
            throw new coding_exception('the stamp record must provide id and userid');
        }

        $this->id          = $record->id;
        $this->holderid    = $record->userid;
        $this->timecreated = $record->timecreated;

        if (isset($record->giver)) {
            $this->giverid = $record->giver;
        }

        if (isset($record->modifier)) {
            $this->modifierid = $record->modifier;
        }

        if (isset($record->text)) {
            $this->text = $record->text;
        }

        if (isset($record->timemodified)) {
            $this->timemodified = $record->timemodified;
        }
    }
}


/**
 * Base collection of stamps to be displayed
 */
abstract class stampcoll_collection {

    /** @var stampcoll module instance */
    public $stampcoll;

    /** @var array internal list of registered users */
    protected $users = array();

    /** @var array internal list of stamp holder ids, in order they should appear */
    protected $holderids = array();

    /** @var array internal list of stamps, indexed by the holder */
    protected $stamps = array();

    /**
     * The base constructor
     *
     * @param stampcoll $stampcoll module instance
     */
    public function __construct(stampcoll $stampcoll) {
        $this->stampcoll = $stampcoll;
    }

    /**
     * Registers stamp holder information
     *
     * @param stdClass $userinfo {@see self::register_user() for expected fields}
     * @return int|bool
     */
    public function register_holder(stdClass $userinfo) {

        if (!isset($userinfo->id)) {
            throw new coding_exception('userinfo must define user id');
        }

        if (!in_array($userinfo->id, $this->holderids)) {
            $this->holderids[] = $userinfo->id;
        }

        return $this->register_user($userinfo);
    }

    /**
     * Register general user (stamp holder or giver) information
     *
     * @param stdClass $userinfo {@see user_picture::unalias() for expected fields}
     * @return int|bool
     */
    public function register_user(stdClass $userinfo) {

        if (!isset($userinfo->id)) {
            throw new coding_exception('userinfo must define user id');
        }

        if (isset($this->users[$userinfo->id])) {
            return 1;
        }

        $this->users[$userinfo->id] = $userinfo;

        return true;
    }

    /**
     * Returns the previously registered user info
     *
     * @param mixed $userid
     * @return stdClass|bool false if has not been registered, object otherwise
     */
    public function get_user_info($userid) {

        if (!isset($this->users[$userid])) {
            return false;
        }

        return $this->users[$userid];
    }

    /**
     * Adds given stamp to the collection
     *
     * @param stdClass $stamp
     * @return void
     */
    public function add_stamp(stdClass $stamp) {

        if (!isset($stamp->userid)) {
            throw new coding_exception('stamp object must define user id');
        }

        if (!isset($this->stamps[$stamp->userid])) {
            $this->stamps[$stamp->userid] = array();
        }

        $this->stamps[$stamp->userid][] = $stamp;
    }

    /**
     * Returns the previously added stamps for the given holder
     *
     * @param int $holderid the user id
     * @return array of {@link stampcoll_stamp} instances
     */
    public function list_stamps($holderid) {

        if (!isset($this->stamps[$holderid])) {
            return array();
        }

        $stamps = array();

        foreach ($this->stamps[$holderid] as $record) {
            $stamps[] = new stampcoll_stamp($this->stampcoll, $record);
        }

        return $stamps;
    }
}


/**
 * Collection of single user's stamps
 */
class stampcoll_singleuser_collection extends stampcoll_collection implements renderable {

    /** @var int user id of the holder of the collection */
    private $holderid = null;

    /**
     * Registers the holder of the collection
     *
     * @param stampcoll $stamcoll module instance
     * @param stdClass $holder user object
     */
    public function __construct(stampcoll $stampcoll, stdClass $holder) {
        parent::__construct($stampcoll);
        $this->register_user($holder);
        $this->holderid = $holder->id;
    }

    /**
     * Returns the information of the collection holder
     *
     * @return stdClass
     */
    public function get_holder() {
        return $this->get_user_info($this->holderid);
    }
}


/**
 * Collection of multiple users' stamps
 */
class stampcoll_multiuser_collection extends stampcoll_collection implements renderable {

    /** @var string how are the data sorted */
    public $sortedby = 'lastname';

    /** @var string how are the data sorted */
    public $sortedhow = 'ASC';

    /** @var int page number to display */
    public $page = 0;

    /** @var int number of stamo holders to display per page */
    public $perpage = 30;

    /** @var int the total number or stamp holders to display */
    public $totalcount = null;

    /**
     * Initializes the list of users to display
     *
     * Users data must be provided by subsequential calls of {@see register_user()}.
     *
     * @param stampcoll $stamcoll module instance
     * @param array $holderids ordered list of user ids
     */
    public function __construct(stampcoll $stampcoll, array $holderids = array()) {
        parent::__construct($stampcoll);
        $this->holderids = $holderids;
    }

    /**
     * Returns the list of stamp holders in order they were registered
     *
     * @return array of stdClass
     */
    public function list_stamp_holders() {

        $holders = array();
        foreach ($this->holderids as $holderid) {
            $holders[] = $this->users[$holderid];
        }

        return $holders;
    }
}


/**
 * Collection of multiple users' stamps used at the managestamps.php screen
 */
class stampcoll_management_collection extends stampcoll_multiuser_collection implements renderable {
}
