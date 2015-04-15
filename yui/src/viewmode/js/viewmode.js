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
 * @package     mod_stampcoll
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

var CSS = {
        TOGGLECLASS: 'viewmode-full'
    },
    SELECTORS = {
        STAMPWRAPPER: '.stamp-wrapper',
        PLACEHOLDER: '#mod_stampcoll_viewmode_toggle'
    },
    NS;


M.mod_stampcoll = M.mod_stampcoll || {};
M.mod_stampcoll.viewmode = {};
NS = M.mod_stampcoll.viewmode;

NS.init = function() {
    var placeholder = Y.one(SELECTORS.PLACEHOLDER);
    var button = Y.Node.create('<a href="" class="btn">' + M.util.get_string('toggleviewmode', 'mod_stampcoll') + '</a>');

    button.on('click', function(e) {
        e.preventDefault();
        Y.all(SELECTORS.STAMPWRAPPER).toggleClass(CSS.TOGGLECLASS);
    });

    if (placeholder) {
        placeholder.replace(button);
    }
};
