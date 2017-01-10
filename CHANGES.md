### 3.3.1 ###

* Grunt is now used to compile the LESS to CSS.
* The layout fixed and improved for the Boost theme

Tested on Moodle 3.2.

### 3.3 ###

* Added support for Moodle 2.9 - does not use `add_intro_editor()` for this and higher versions.

### 3.2 ###

* Added new button _Toggle stamp display mode_ button that allows to display stamps together
  with their text comments. For accessibility reasons, displaying the text via the `alt` and the
  `title` image attribute was dropped.
* Validation checks for the text maximum length (255 characters) are now performed when giving
  a stamp. In the stamps management screen, attempting to add/update a stamp with too long text
  leads to silently skipping such change. This should be improved in the future.

### 3.1 ###

* The activity description can be displayed at the course main page.
* New event triggered when user receives a stamp (allows integration with the Level Up! block).

### 3.0 ###

* Added support for the new events and logging APIs.
* Added Behat tests for the main features.
* Added generator for automatic creation of the module instances.
* Added accessibility labels to textarea fields at the management screen.
* The order of stamps at the management screen no longer affected by the stamp modification time.
* Changed versioning scheme. The Git 'master' branch now contains the most recent stable code.

Tested on Moodle 2.7 and 2.8.

### 2.6.0 ###

* Dropping support for old upgrade path. To upgrade the plugin to v2.7.0, it must already run v2.0.0 at least.
* Headings structure fixed according [Moodle HTML guidelines](http://docs.moodle.org/dev/HTML_Guidelines#Activity_page).
* Deprecated get_context_instance() function calls replaced with
  context\_xxx::instance() variants.
* Fixed the single view URL at the management screen (CONTRIB-4070) and some
  more moodle\_url calls.
* Moodle coding guidelines applied (checked by local\_codechecker version 2014021700).

### 2.5.0 ###

* Fixed XMLDB/SQL upgrade from 1.9.x

### 2.4.1 ###

* Fixed XMLDB/SQL upgrade from 1.9.x

### 2.4.0 ###

* Added SVG icon of the module
* Added SVG image of the default stamp
* Activity settings form reworded to make it clear that no stamp image implies
  using the default stamp.
