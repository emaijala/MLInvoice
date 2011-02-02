alter table pklasku_base add column (
  bank_iban varchar(50) NOT NULL,
  bank_swiftbic varchar(30) NOT NULL,
  bank_name2 varchar(50) NULL,
  bank_account2 varchar(30) NULL,
  bank_iban2 varchar(50) NULL,
  bank_swiftbic2 varchar(30) NULL,
  bank_name3 varchar(50) NULL,
  bank_account3 varchar(30) NULL,
  bank_iban3 varchar(50) NULL,
  bank_swiftbic3 varchar(30) NULL,
  vat_registered tinyint default 0 NOT NULL
);

INSERT INTO pklasku_invoice_state (id, name, order_no) VALUES (4, 'MITÄTÖITY', 20);
INSERT INTO pklasku_invoice_state (id, name, order_no) VALUES (5, '1. HUOMAUTUS', 25);
INSERT INTO pklasku_invoice_state (id, name, order_no) VALUES (6, '2. HUOMAUTUS', 30);
INSERT INTO pklasku_invoice_state (id, name, order_no) VALUES (7, 'PERINTÄ', 35);

CREATE TABLE pklasku_product (
  id int(11) NOT NULL auto_increment,
  product_name varchar(100) NOT NULL,
  description varchar(100) NULL,
  product_code varchar(100) NULL,
  product_group varchar(100) NULL,
  internal_info text,
  unit_price decimal(9,2) NULL,
  type_id int(11) default NULL,
  vat_percent decimal(9,1) NOT NULL default 0,
  vat_included tinyint NOT NULL default 0,
  PRIMARY KEY  (id)
) TYPE=MyISAM;

alter table pklasku_invoice_row add column (
  product_id int(11) default NULL,
  vat_included tinyint NOT NULL default 0,  
  reminder_row tinyint NOT NULL default 0
);

alter table pklasku_invoice_row change column vat vat decimal(9,1) default 0;

alter table pklasku_invoice add column (
  refunded_invoice_id int(11) NULL,
  print_date int(11) default NULL
);

alter table pklasku_invoice add column (
  archived tinyint NOT NULL default 0
);

alter table pklasku_company add column (
  customer_no int(11) default NULL
);
update pklasku_company set customer_no = id;

alter table pklasku_company add column (
  default_ref_number varchar(100) default NULL
);

CREATE TABLE pklasku_settings (
  id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL,
  value text NULL,
  PRIMARY KEY  (id)
) ENGINE=MyISAM;

alter table pklasku_invoice_state add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_row_type add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_company_type add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_base add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_company add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_company_contact add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_product add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_invoice add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_invoice_row add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_session_type add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_users add column (
  deleted tinyint NOT NULL default 0
);

alter table pklasku_quicksearch change column name name varchar(255) NULL;
alter table pklasku_quicksearch change column form func varchar(100) NULL;
update pklasku_quicksearch set func='invoices' where func='invoice';
update pklasku_quicksearch set func='companies' where func='company';

alter table pklasku_base add column (
  logo_top decimal(9,2) NULL,
  logo_left decimal(9,2) NULL,
  logo_width decimal(9,2) NULL,
  logo_bottom_margin decimal(9,2) NULL
);

CREATE TABLE pklasku_print_template (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(100) NOT NULL,
  filename varchar(255) default NULL,
  parameters varchar(255) NOT NULL,
  output_filename varchar(255) default NULL,
  type varchar(100) NOT NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=MyISAM;

alter table pklasku_invoice add column (
  info text default NULL,
  internal_info text default NULL
);