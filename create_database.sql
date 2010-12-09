CREATE TABLE vllasku_invoice_state (
  id int(11) NOT NULL auto_increment,
  name varchar(15) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB AUTO_INCREMENT=4 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_row_type (
  id int(11) NOT NULL auto_increment,
  name varchar(15) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB AUTO_INCREMENT=9 CHARACTER SET utf8 COLLATE utf8_swedish_ci;


CREATE TABLE vllasku_company_type (
  id int(11) NOT NULL auto_increment,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB AUTO_INCREMENT=23 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_base (
  id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL,
  contact_person varchar(50) NOT NULL,
  street_address varchar(100) NOT NULL,
  zip_code varchar(10) NOT NULL,
  city varchar(50) NOT NULL,
  phone varchar(50) NOT NULL,
  www varchar(255) default NULL,
  email varchar(100) default NULL,
  company_id varchar(15) default NULL,
  bank_name varchar(50) NOT NULL,
  bank_account varchar(30) NOT NULL,
  bank_iban varchar(50) NOT NULL,
  bank_swiftbic varchar(30) NOT NULL,
  bank_name2 varchar(50) NOT NULL,
  bank_account2 varchar(30) NOT NULL,
  bank_iban2 varchar(50) NOT NULL,
  bank_swiftbic2 varchar(30) NOT NULL,
  bank_name3 varchar(50) NOT NULL,
  bank_account3 varchar(30) NOT NULL,
  bank_iban3 varchar(50) NOT NULL,
  bank_swiftbic3 varchar(30) NOT NULL,
  vat_registered tinyint default 0 NOT NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB AUTO_INCREMENT=1 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_company (
  id int(11) NOT NULL auto_increment,
  inside_info text,
  type_id int(11) default NULL,
  company_name varchar(100) NOT NULL,
  contact_person varchar(100) default NULL,
  street_address varchar(100) default NULL,
  zip_code varchar(10) default NULL,
  city varchar(100) default NULL,
  phone varchar(30) default NULL,
  fax varchar(30) default NULL,
  email varchar(50) default NULL,
  gsm varchar(30) default NULL,
  billing_address text,
  www varchar(100) default NULL,
  info text,
  company_id varchar(15) default NULL,
  customer_no int(11) default NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (type_id) REFERENCES vllasku_company_type(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_company_contact (
  id int(11) NOT NULL auto_increment,
  company_id int(11) NOT NULL default '0',
  contact_person varchar(100) default NULL,
  person_title varchar(100) default NULL,
  email varchar(50) default NULL,
  phone varchar(30) default NULL,
  gsm varchar(30) default NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (company_id) REFERENCES vllasku_company(id)
) ENGINE=INNODB AUTO_INCREMENT=1 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_product (
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
  PRIMARY KEY (id),
  FOREIGN KEY (type_id) REFERENCES vllasku_row_type(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_invoice (
  id int(11) NOT NULL auto_increment,
  name varchar(50) default NULL,
  company_id int(11) default NULL,
  invoice_no varchar(100) default NULL,
  invoice_date int(11) default NULL,
  due_date int(11) default NULL,
  payment_date int(11) default NULL,
  ref_number varchar(100) default NULL,
  state_id int(11) default NULL,
  reference varchar(50) default NULL,
  base_id int(11) default NULL,
  refunded_invoice_id int(11) default NULL,
  print_date int(11) default NULL,
  archived tinyint NOT NULL default 0,
  PRIMARY KEY (id),
  FOREIGN KEY (company_id) REFERENCES vllasku_company(id),
  FOREIGN KEY (state_id) REFERENCES vllasku_invoice_state(id)
) ENGINE=INNODB AUTO_INCREMENT=1 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_invoice_row (
  id int(11) NOT NULL auto_increment,
  invoice_id int(11) default NULL,
  product_id int(11) default NULL,
  description varchar(255) default NULL,
  type_id int(11) default NULL,
  pcs decimal(9,2) default NULL,
  price decimal(9,2) default NULL,
  row_date int(11) default NULL,
  vat decimal(9,1) NOT NULL default '0',
  vat_included tinyint NOT NULL default 0,
  order_no int(11) default NULL,
  reminder_row tinyint NOT NULL default 0,
  PRIMARY KEY (id),
  FOREIGN KEY (invoice_id) REFERENCES vllasku_invoice(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES vllasku_product(id),
  FOREIGN KEY (type_id) REFERENCES vllasku_row_type(id)
) ENGINE=INNODB AUTO_INCREMENT=1 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_session_type (
  id int(11) NOT NULL auto_increment,
  name varchar(15) default NULL,
  order_no int(11) default NULL,
  time_out int(11) default NULL,
  access_level int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB AUTO_INCREMENT=3 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_users (
  id int(11) NOT NULL auto_increment,
  name varchar(50) default NULL,
  email varchar(255) default NULL,
  login varchar(15) default NULL,
  passwd varchar(255) default NULL,
  type_id int(11) default NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (type_id) REFERENCES vllasku_session_type(id)
) ENGINE=INNODB AUTO_INCREMENT=2 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE vllasku_quicksearch (
  id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL,
  name varchar(15) default NULL,
  form varchar(100) default NULL,
  whereclause text,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES vllasku_users(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

SET NAMES 'utf8';

INSERT INTO vllasku_base (id, name, contact_person, street_address, zip_code, city, phone, bank_name, bank_account, bank_iban, bank_swiftbic, www, email, company_id) VALUES (1, 'Testifirma', 'Taavi Testaaja', 'Testitie', '00730', 'HELSINKI', '+358 50 123456', 'Pankki', '123456-654321', 'FI12 3456 7890 1234 56', 'FIHHPANK', 'http://sourceforge.net/', 'emaijala@gmail.com', '123456-7');

INSERT INTO vllasku_company (id, inside_info, type_id, company_name, contact_person, street_address, zip_code, city, phone, fax, email, gsm, billing_address, www, info, company_id) VALUES (1, NULL, NULL, 'Testifirma', NULL, 'Testitie', '00730', 'HELSINKI', '050-123 4567', '-', 'emaijala@gmail.com', '050-123 4567', 'Testifirma\r\nTestitie\r\n00730 HELSINKI', 'www.sourceforge.net', '', 'FI-123456-x');

INSERT INTO vllasku_company_contact (id, company_id, contact_person, person_title, email, phone, gsm) VALUES (1, 1, 'Ere Maijala', 'Päällikkö', 'emaijala@gmail.com', '-', '050-123 4567');

INSERT INTO vllasku_company_type (id, name, order_no) VALUES (1, 'Autoilu', 5);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (2, 'Elintarviketeollisuus', 10);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (3, 'Graafinen ala', 15);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (4, 'Henkilöstöhallinto', 20);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (5, 'Julkinen sektori', 25);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (6, 'Kemian teollisuus', 30);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (7, 'Kiinteistö', 35);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (8, 'Kuljetus ja logistiikka', 40);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (9, 'Kumi- ja muoviteollisuus', 45);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (10, 'Maa- ja metsätalous', 50);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (11, 'Markkinointi ja mainonta', 55);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (12, 'Matkailu, majoitus ja virkistystoiminta', 60);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (13, 'Metalli', 65);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (14, 'Rakentaminen', 70);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (15, 'Taideteollisuus', 75);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (16, 'Taloushallinto', 80);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (17, 'Tekstiili- ja vaatetusteollisuus', 85);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (18, 'Terveydenhuolto ja hyvinvointi', 90);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (19, 'Tietotekniikka', 95);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (20, 'Vapaa-aika ja harrastustoiminta', 100);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (21, 'Ympäristö', 105);
INSERT INTO vllasku_company_type (id, name, order_no) VALUES (22, 'Elektroniikka', 110);

INSERT INTO vllasku_invoice_state (id, name, order_no) VALUES (1, 'AVOIN', 5);
INSERT INTO vllasku_invoice_state (id, name, order_no) VALUES (2, 'LÄHETETTY', 10);
INSERT INTO vllasku_invoice_state (id, name, order_no) VALUES (3, 'MAKSETTU', 15);
INSERT INTO vllasku_invoice_state (id, name, order_no) VALUES (4, 'MITÄTÖITY', 20);
INSERT INTO vllasku_invoice_state (id, name, order_no) VALUES (5, '1. HUOMAUTUS', 25);
INSERT INTO vllasku_invoice_state (id, name, order_no) VALUES (6, '2. HUOMAUTUS', 30);
INSERT INTO vllasku_invoice_state (id, name, order_no) VALUES (7, 'PERINTÄ', 35);

INSERT INTO vllasku_row_type (id, name, order_no) VALUES (1, 'h', 5);
INSERT INTO vllasku_row_type (id, name, order_no) VALUES (2, 'pv', 10);
INSERT INTO vllasku_row_type (id, name, order_no) VALUES (3, 'kk', 15);
INSERT INTO vllasku_row_type (id, name, order_no) VALUES (4, 'kpl', 20);
INSERT INTO vllasku_row_type (id, name, order_no) VALUES (5, 'vuosi', 25);
INSERT INTO vllasku_row_type (id, name, order_no) VALUES (6, 'erä', 30);
INSERT INTO vllasku_row_type (id, name, order_no) VALUES (8, 'km', 35);

INSERT INTO vllasku_invoice 
  (id, name, company_id, invoice_no, invoice_date, due_date, payment_date, ref_number, state_id, reference, base_id) 
  VALUES (1, 'Testi', 1, '100', 20101230, 20110113, NULL, '', 1, '', 1);

INSERT INTO vllasku_invoice_row (id, invoice_id, description, type_id, pcs, price, row_date, vat, order_no) 
  VALUES (1, 1, 'Testirivi 1', 3, 12.00, 150.00, 20060515, 23, 5);

INSERT INTO vllasku_session_type (id, name, order_no, time_out, access_level) VALUES (1, 'Käyttäjä', 1, 3600, 1);
INSERT INTO vllasku_session_type (id, name, order_no, time_out, access_level) VALUES (2, 'Admin', 2, 3600, 99);

INSERT INTO vllasku_users (id, name, email, login, passwd, type_id) VALUES (1, 'Administrator', 'foo@bar.fi.not', 'admin', md5('admin'), 2);
