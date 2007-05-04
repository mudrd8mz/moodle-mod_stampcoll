CREATE TABLE prefix_stampcoll (
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
  UNIQUE KEY prefix_stampcoll_id_idx (id),
  KEY prefix_stampcoll_course_idx (course)
) COMMENT='Available stamp collections are stored here.';

CREATE TABLE prefix_stampcoll_stamps (
  id int(10) unsigned NOT NULL auto_increment,
  stampcollid int(10) unsigned NOT NULL default '0',
  userid int(10) unsigned NOT NULL default '0',
  comment varchar(255) NOT NULL default '',
  timemodified int(10) NOT NULL default '0',
  PRIMARY KEY  (id),
  UNIQUE KEY prefix_stampcoll_stamps_id_idx (id),
  KEY prefix_stampcoll_stamps_userid_idx (userid),
  KEY prefix_stampcoll_stamps_stampcollid_idx (stampcollid)
) COMMENT='All collected stamps are stored here.';

INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('stampcoll', 'view', 'stampcoll', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('stampcoll', 'update', 'stampcoll', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('stampcoll', 'add', 'stampcoll', 'name');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('stampcoll', 'view stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('stampcoll', 'add stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('stampcoll', 'update stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO prefix_log_display (module, action, mtable, field) VALUES ('stampcoll', 'delete stamp', 'user', 'concat(firstname, \' \', lastname)');
