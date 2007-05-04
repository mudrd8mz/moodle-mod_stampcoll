CREATE TABLE prefix_stampcoll (
  id serial primary key,
  course int NOT NULL default '0',
  name varchar(255) NOT NULL default '',
  text text NOT NULL,
  format smallint NOT NULL default '0',
  image varchar(255) NOT NULL default '',
  publish smallint NOT NULL default '0',
  timemodified int NOT NULL default '0',
  teachercancollect smallint NOT NULL default '1',
  displayzero smallint NOT NULL default '0'
);

CREATE INDEX prefix_stampcoll_course_idx ON prefix_stampcoll (course);

CREATE TABLE prefix_stampcoll_stamps (
  id serial primary key,
  stampcollid int NOT NULL default '0',
  userid int NOT NULL default '0',
  comment varchar(255) NOT NULL default '',
  timemodified int NOT NULL default '0'
);

CREATE INDEX prefix_stampcoll_stamps_stampcollid_idx ON prefix_stampcoll_stamps (stampcollid);
CREATE INDEX prefix_stampcoll_stamps_userid_idx ON prefix_stampcoll_stamps (userid);

INSERT INTO prefix_log_display VALUES ('stampcoll', 'view', 'stampcoll', 'name');
INSERT INTO prefix_log_display VALUES ('stampcoll', 'update', 'stampcoll', 'name');
INSERT INTO prefix_log_display VALUES ('stampcoll', 'add', 'stampcoll', 'name');
INSERT INTO prefix_log_display VALUES ('stampcoll', 'view stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO prefix_log_display VALUES ('stampcoll', 'add stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO prefix_log_display VALUES ('stampcoll', 'update stamp', 'user', 'concat(firstname, \' \', lastname)');
INSERT INTO prefix_log_display VALUES ('stampcoll', 'delete stamp', 'user', 'concat(firstname, \' \', lastname)');
