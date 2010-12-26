CREATE TABLE vllasku_settings (
  id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL,
  value text NULL,
  PRIMARY KEY  (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

alter table vllasku_base add column (
  logo_filename varchar(255) NULL,
  logo_filesize integer(11) NULL,
  logo_filetype varchar(255) NULL,
  logo_filedata longblob NULL
);

