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

INSERT INTO vllasku_print_template (id, name, filename, parameters, output_filename, type, order_no) VALUES (1, 'Lasku', 'invoice_printer.php', 'invoice', 'lasku_%d.pdf', 'invoice', 5);
INSERT INTO vllasku_print_template (id, name, filename, parameters, output_filename, type, order_no) VALUES (2, 'Lähetysluettelo', 'invoice_printer.php', 'dispatch', 'lahetysluettelo_%d.pdf','invoice', 10);
INSERT INTO vllasku_print_template (id, name, filename, parameters, output_filename, type, order_no) VALUES (3, 'Kuitti', 'invoice_printer.php', 'receipt', 'kuitti_%d.pdf','invoice', 15);

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

DROP TABLE IF EXISTS vllasku_session;

CREATE TABLE vllasku_session (
  id char(32) NOT NULL,
  data longblob NULL,
  session_timestamp timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX i_session_timestamp(session_timestamp)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
