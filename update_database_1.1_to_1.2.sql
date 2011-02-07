CREATE TABLE vllasku_print_template (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(100) NOT NULL,
  filename varchar(255) default NULL,
  parameters varchar(255) NOT NULL,
  output_filename varchar(255) default NULL,
  type varchar(100) NOT NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

alter table vllasku_invoice add column (
  info text default NULL,
  internal_info text default NULL
);

alter table vllasku_company add column (
  inactive tinyint NOT NULL default 0
);

alter table vllasku_session_type change column name name varchar(255) default NULL;
UPDATE vllasku_session_type set order_no=20, name='Ylläpitäjä' where id=2;
INSERT INTO vllasku_session_type (id, name, order_no, time_out, access_level) VALUES (3, 'Käyttäjä - varmuuskopioija', 10, 3600, 90);
