CREATE TABLE mdl_stampcoll (
  id int(10) unsigned NOT NULL auto_increment,
  course int(10) unsigned NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  text text NOT NULL,
  format tinyint(2) unsigned NOT NULL default '0',
  image varchar(255) NOT NULL default '',
  publish tinyint(2) unsigned NOT NULL default '0',
  timemodified int(10) unsigned NOT NULL default '0',
  teachercancollect tinyint(2) unsigned NOT NULL default '1',
  displayzero tinyint(2) unsigned NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY mdl_stampcoll_id_idx (id),
  KEY mdl_stampcoll_course_idx (course)
) COMMENT='Available stamp collections are stored here.';

CREATE TABLE mdl_stampcoll_stamps (
  id int(10) unsigned NOT NULL auto_increment,
  stampcollid int(10) unsigned NOT NULL default '0',
  userid int(10) unsigned NOT NULL default '0',
  comment varchar(255) NOT NULL default '',
  timemodified int(10) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY mdl_stampcoll_stamps_id_idx (id),
  KEY mdl_stampcoll_stamps_userid_idx (userid),
  KEY mdl_stampcoll_stamps_stampcollid_idx (stampcollid)
) COMMENT='All collected stamps are stored here.';

INSERT INTO mdl_log_display VALUES (' ', 'stampcoll', 'view', 'stampcoll', 'name');
INSERT INTO mdl_log_display VALUES (' ', 'stampcoll', 'update', 'stampcoll', 'name');
INSERT INTO mdl_log_display VALUES (' ', 'stampcoll', 'add', 'stampcoll', 'name');
INSERT INTO mdl_log_display VALUES (' ', 'stampcoll', 'view stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO mdl_log_display VALUES (' ', 'stampcoll', 'add stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO mdl_log_display VALUES (' ', 'stampcoll', 'update stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO mdl_log_display VALUES (' ', 'stampcoll', 'delete stamp', 'user', 'concat(firstname, \' \', lastname)');