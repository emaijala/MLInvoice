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
  penalty_interest_row tinyint NOT NULL default 0
);

alter table pklasku_invoice_row change column vat vat decimal(9,1) default 0;

alter table pklasku_invoice add column (
  refunded_invoice_id int(11) NULL,
  print_date int(11) default NULL
);
