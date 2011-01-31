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
