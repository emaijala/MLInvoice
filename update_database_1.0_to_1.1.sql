create table vllasku_settings (
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

alter table vllasku_invoice_state add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_row_type add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_company_type add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_base add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_company add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_company_contact add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_product add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_invoice add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_invoice_row add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_session_type add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_users add column (
  deleted tinyint NOT NULL default 0
);

alter table vllasku_quicksearch change column name name varchar(255) NULL;
alter table vllasku_quicksearch change column form func varchar(100) NULL;
update vllasku_quicksearch set func='invoices' where func='invoice';
update vllasku_quicksearch set func='companies' where func='company';

alter table vllasku_base add column (
  logo_top decimal(9,2) NULL,
  logo_left decimal(9,2) NULL,
  logo_width decimal(9,2) NULL,
  logo_bottom_margin decimal(9,2) NULL
);
