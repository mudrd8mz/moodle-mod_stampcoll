v2.6.0
======

* Dropping support for old upgrade path. To upgrade the plugin to v2.7.0, it must already run v2.0.0 at least.
* Headings structure fixed according [Moodle HTML guidelines](http://docs.moodle.org/dev/HTML_Guidelines#Activity_page).
* Deprecated get_context_instance() function calls replaced with
  context\_xxx::instance() variants.
* Fixed the single view URL at the management screen (CONTRIB-4070) and some
  more moodle\_url calls.
* Moodle coding guidelines applied (checked by local\_codechecker version 2014021700).


v2.5.0
======

* Fixed XMLDB/SQL upgrade from 1.9.x


v2.4.1
======

* Fixed XMLDB/SQL upgrade from 1.9.x


v2.4.0
======

* Added SVG icon of the module
* Added SVG image of the default stamp
* Activity settings form reworded to make it clear that no stamp image implies
  using the default stamp.
