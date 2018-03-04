CREATE TABLE mlinvoice_invoice_state (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  invoice_open tinyint NOT NULL default 0,
  invoice_unpaid tinyint NOT NULL default 0,
  invoice_offer tinyint NOT NULL default 0,
  invoice_offer_sent tinyint NOT NULL default 0,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_row_type (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_company_type (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_base (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  inactive tinyint NOT NULL default 0,
  name varchar(100) NOT NULL,
  contact_person varchar(50) NOT NULL,
  street_address varchar(100) NOT NULL,
  zip_code varchar(10) NOT NULL,
  city varchar(50) NOT NULL,
  country varchar(255) default NULL,
  phone varchar(50) NOT NULL,
  www varchar(255) default NULL,
  email varchar(512) default NULL,
  company_id varchar(15) default NULL,
  org_unit_number varchar(35) default NULL,
  payment_intermediator varchar(100) default NULL,
  bank_name varchar(50) NOT NULL,
  bank_account varchar(30) NOT NULL,
  bank_iban varchar(50) NOT NULL,
  bank_swiftbic varchar(30) NOT NULL,
  bank_name2 varchar(50) NOT NULL DEFAULT '',
  bank_account2 varchar(30) NOT NULL DEFAULT '',
  bank_iban2 varchar(50) NOT NULL DEFAULT '',
  bank_swiftbic2 varchar(30) NOT NULL DEFAULT '',
  bank_name3 varchar(50) NOT NULL DEFAULT '',
  bank_account3 varchar(30) NOT NULL DEFAULT '',
  bank_iban3 varchar(50) NOT NULL DEFAULT '',
  bank_swiftbic3 varchar(30) NOT NULL DEFAULT '',
  vat_registered tinyint default 0 NOT NULL,
  logo_filename varchar(255) NULL,
  logo_filesize integer(11) NULL,
  logo_filetype varchar(255) NULL,
  logo_filedata longblob NULL,
  logo_top decimal(9,2) NULL,
  logo_left decimal(9,2) NULL,
  logo_width decimal(9,2) NULL,
  logo_bottom_margin decimal(9,2) NULL,
  invoice_email_from varchar(512) NULL,
  invoice_email_bcc varchar(512) NULL,
  invoice_email_subject varchar(255) NULL,
  invoice_email_body text NULL,
  receipt_email_subject varchar(255) NULL,
  receipt_email_body text NULL,
  order_confirmation_email_subject varchar(255) NULL,
  order_confirmation_email_body text NULL,
  offer_email_subject varchar(255) NULL,
  offer_email_body text NULL,
  invoice_default_info text NULL,
  invoice_default_foreword text NULL,
  invoice_default_afterword text NULL,
  offer_default_foreword text NULL,
  offer_default_afterword text NULL,
  terms_of_payment varchar(255) NULL,
  period_for_complaints varchar(255) NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_delivery_terms (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_delivery_method (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_company (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  inside_info text,
  type_id int(11) default NULL,
  company_name varchar(100) NOT NULL,
  contact_person varchar(100) default NULL,
  street_address varchar(100) default NULL,
  zip_code varchar(10) default NULL,
  city varchar(100) default NULL,
  country varchar(255) default NULL,
  phone varchar(30) default NULL,
  fax varchar(30) default NULL,
  email varchar(512) default NULL,
  gsm varchar(30) default NULL,
  billing_address text,
  www varchar(100) default NULL,
  info text,
  company_id varchar(15) default NULL,
  org_unit_number varchar(35) default NULL,
  payment_intermediator varchar(100) default NULL,
  customer_no int(11) default NULL,
  default_ref_number varchar(100) default NULL,
  inactive tinyint NOT NULL default 0,
  delivery_terms_id int(11) default NULL,
  delivery_method_id int(11) default NULL,
  payment_days int(11) default NULL,
  terms_of_payment varchar(255) NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (type_id) REFERENCES mlinvoice_company_type(id),
  FOREIGN KEY (delivery_terms_id) REFERENCES mlinvoice_delivery_terms(id),
  FOREIGN KEY (delivery_method_id) REFERENCES mlinvoice_delivery_method(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_company_contact (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  company_id int(11) NOT NULL default '0',
  contact_person varchar(100) default NULL,
  person_title varchar(100) default NULL,
  email varchar(512) default NULL,
  phone varchar(30) default NULL,
  gsm varchar(30) default NULL,
  contact_type varchar(100) default NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (company_id) REFERENCES mlinvoice_company(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_company_tag (
  id int(11) NOT NULL auto_increment,
  tag varchar(100) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_company_tag_link (
  id int(11) NOT NULL auto_increment,
  tag_id int(11) NOT NULL,
  company_id int(11) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (tag_id) REFERENCES mlinvoice_company_tag(id),
  FOREIGN KEY (company_id) REFERENCES mlinvoice_company(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_contact_tag (
  id int(11) NOT NULL auto_increment,
  tag varchar(100) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_contact_tag_link (
  id int(11) NOT NULL auto_increment,
  tag_id int(11) NOT NULL,
  contact_id int(11) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (tag_id) REFERENCES mlinvoice_contact_tag(id),
  FOREIGN KEY (contact_id) REFERENCES mlinvoice_company_contact(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_product (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  product_name varchar(100) NOT NULL,
  description varchar(255) NULL,
  product_code varchar(100) NULL,
  product_group varchar(100) NULL,
  barcode1 varchar(255) NULL,
  barcode1_type varchar(20) NULL,
  barcode2 varchar(255) NULL,
  barcode2_type varchar(20) NULL,
  internal_info text,
  unit_price decimal(15,5) NULL,
  purchase_price decimal(15,5) NULL,
  type_id int(11) default NULL,
  vat_percent decimal(9,1) NOT NULL default 0,
  vat_included tinyint NOT NULL default 0,
  discount decimal(4,1) NULL,
  discount_amount decimal(15,5) NULL,
  price_decimals decimal(1,0) NOT NULL default 2,
  order_no int(11) default NULL,
  stock_balance decimal(11,2) default NULL,
  vendor varchar(255) NULL,
  vendors_code varchar(100) NULL,
  weight decimal(15,5) NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (type_id) REFERENCES mlinvoice_row_type(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_custom_price (
  id int(11) NOT NULL auto_increment,
  company_id int(11) NOT NULL,
  discount decimal(4,1) NULL,
  multiplier decimal(10,5) NULL,
  valid_until int(11) default NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (company_id) REFERENCES mlinvoice_company(id) ON DELETE CASCADE
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_custom_price_map (
  id int(11) NOT NULL auto_increment,
  custom_price_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  unit_price decimal(15,5) NULL,
  discount decimal(4,1) NULL,
  discount_amount decimal(15,5) NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (custom_price_id) REFERENCES mlinvoice_custom_price(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES mlinvoice_product(id) ON DELETE CASCADE
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_invoice (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
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
  info text default NULL,
  internal_info text default NULL,
  interval_type int(11) NOT NULL default 0,
  next_interval_date int(11) default NULL,
  delivery_terms_id int(11) default NULL,
  delivery_method_id int(11) default NULL,
  foreword text default NULL,
  afterword text default NULL,
  delivery_time varchar(100) default NULL,
  uuid varchar(50) default NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (company_id) REFERENCES mlinvoice_company(id),
  FOREIGN KEY (state_id) REFERENCES mlinvoice_invoice_state(id),
  FOREIGN KEY (base_id) REFERENCES mlinvoice_base(id),
  FOREIGN KEY (delivery_method_id) REFERENCES mlinvoice_delivery_method(id)
) ENGINE=INNODB AUTO_INCREMENT=1 CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_invoice_row (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  invoice_id int(11) default NULL,
  product_id int(11) default NULL,
  description varchar(255) default NULL,
  type_id int(11) default NULL,
  pcs decimal(9,2) default NULL,
  price decimal(15,5) default NULL,
  row_date int(11) default NULL,
  vat decimal(9,1) NOT NULL default 0,
  vat_included tinyint NOT NULL default 0,
  order_no int(11) default NULL,
  reminder_row tinyint NOT NULL default 0,
  partial_payment tinyint NOT NULL default 0,
  discount decimal(4,1) NULL,
  discount_amount decimal(15,5) NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (invoice_id) REFERENCES mlinvoice_invoice(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES mlinvoice_product(id),
  FOREIGN KEY (type_id) REFERENCES mlinvoice_row_type(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_session_type (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  time_out int(11) default NULL,
  access_level int(11) default NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_users (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  email varchar(512) default NULL,
  login varchar(255) default NULL,
  passwd varchar(255) default NULL,
  type_id int(11) default NULL,
  token varchar(255) default NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (type_id) REFERENCES mlinvoice_session_type(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_stock_balance_log (
  id int(11) NOT NULL auto_increment,
  time timestamp NOT NULL default CURRENT_TIMESTAMP,
  user_id int(11) NOT NULL,
  product_id int(11) NOT NULL,
  stock_change decimal(11,2) NOT NULL,
  description varchar(255) NOT NULL,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES mlinvoice_users(id),
  FOREIGN KEY (product_id) REFERENCES mlinvoice_product(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_quicksearch (
  id int(11) NOT NULL auto_increment,
  user_id int(11) NOT NULL,
  name varchar(255) default NULL,
  func varchar(100) default NULL,
  form varchar(100) default NULL,
  whereclause text,
  PRIMARY KEY (id),
  FOREIGN KEY (user_id) REFERENCES mlinvoice_users(id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_settings (
  id int(11) NOT NULL auto_increment,
  name varchar(100) NOT NULL,
  value text NULL,
  PRIMARY KEY  (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_print_template (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(100) NOT NULL,
  filename varchar(255) default NULL,
  parameters varchar(255) NOT NULL,
  output_filename varchar(255) default NULL,
  type varchar(100) NOT NULL,
  order_no int(11) default NULL,
  new_window tinyint NOT NULL default 0,
  inactive tinyint NOT NULL default 0,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_session (
  id char(255) NOT NULL,
  data longblob NULL,
  session_timestamp timestamp NOT NULL default CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX i_session_timestamp(session_timestamp)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_state (
  id char(32) NOT NULL,
  data varchar(100) NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

CREATE TABLE mlinvoice_default_value (
  id int(11) NOT NULL auto_increment,
  deleted tinyint NOT NULL default 0,
  name varchar(255) default NULL,
  order_no int(11) default NULL,
  type varchar(100) NULL,
  content text NULL,
  additional text NULL,
  PRIMARY KEY (id)
) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;

SET NAMES 'utf8';

INSERT INTO mlinvoice_state (id, data) VALUES ('version', '58');

INSERT INTO mlinvoice_state (id, data) VALUES ('tableconversiondone', '1');

INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid) VALUES (1, 'StateOpen', 5, 1, 1);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid) VALUES (2, 'StateSent', 10, 0, 1);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid) VALUES (3, 'StatePaid', 15, 0, 0);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid) VALUES (4, 'StateAnnulled', 20, 0, 0);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid) VALUES (5, 'StateFirstReminder', 25, 0, 1);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid) VALUES (6, 'StateSecondReminder', 30, 0, 1);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid) VALUES (7, 'StateDebtCollection', 35, 0, 1);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid) VALUES (8, 'StatePaidInCash', 17, 0, 0);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid, invoice_offer) VALUES (9, 'StateOfferOpen', 40, 1, 0, 1);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent) VALUES (10, 'StateOfferSent', 45, 1, 0, 1, 1);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent) VALUES (11, 'StateOfferUnrealised', 50, 0, 0, 1, 1);
INSERT INTO mlinvoice_invoice_state (id, name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent) VALUES (12, 'StateOfferRealised', 55, 0, 0, 1, 1);

INSERT INTO mlinvoice_row_type (id, name, order_no) VALUES (1, 'TypeHour', 5);
INSERT INTO mlinvoice_row_type (id, name, order_no) VALUES (2, 'TypeDay', 10);
INSERT INTO mlinvoice_row_type (id, name, order_no) VALUES (3, 'TypeMonth', 15);
INSERT INTO mlinvoice_row_type (id, name, order_no) VALUES (4, 'TypePieces', 20);
INSERT INTO mlinvoice_row_type (id, name, order_no) VALUES (5, 'TypeYear', 25);
INSERT INTO mlinvoice_row_type (id, name, order_no) VALUES (6, 'TypeLot', 30);
INSERT INTO mlinvoice_row_type (id, name, order_no) VALUES (8, 'TypeKilometer', 35);
INSERT INTO mlinvoice_row_type (id, name, order_no) VALUES (9, 'TypeKilogram', 40);

INSERT INTO mlinvoice_session_type (id, name, order_no, time_out, access_level) VALUES (1, 'SessionTypeUser', 1, 3600, 1);
INSERT INTO mlinvoice_session_type (id, name, order_no, time_out, access_level) VALUES (2, 'SessionTypeAdmin', 20, 3600, 99);
INSERT INTO mlinvoice_session_type (id, name, order_no, time_out, access_level) VALUES (3, 'SessionTypeBackupUser', 10, 3600, 90);
INSERT INTO mlinvoice_session_type (id, name, order_no, time_out, access_level) VALUES (4, 'SessionTypeReadOnly', 0, 3600, 0);

INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no) VALUES ('PrintInvoiceFinnish', 'invoice_printer.php', 'invoice', 'lasku_%d.pdf', 'invoice', 5);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no) VALUES ('PrintDispatchNoteFinnish', 'invoice_printer.php', 'dispatch', 'lahetysluettelo_%d.pdf', 'invoice', 20);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no) VALUES ('PrintReceiptFinnish', 'invoice_printer.php', 'receipt', 'kuitti_%d.pdf', 'invoice', 25);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no) VALUES ('PrintEmailFinnish', 'invoice_printer_email.php', 'invoice', 'lasku_%d.pdf', 'invoice', 10);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailEnglish', 'invoice_printer_email.php', 'invoice,en-US', 'invoice_%d.pdf', 'invoice', 11, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailSwedish', 'invoice_printer_email.php', 'invoice,sv-FI', 'faktura_%d.pdf', 'invoice', 12, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceEnglish', 'invoice_printer.php', 'invoice,en', 'invoice_%d.pdf', 'invoice', 15, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintReceiptEnglish', 'invoice_printer.php', 'receipt,en', 'receipt_%d.pdf', 'invoice', 30, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintFinvoice', 'invoice_printer_finvoice.php', '', 'finvoice_%d.xml', 'invoice', 40, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintFinvoiceStyled', 'invoice_printer_finvoice.php', 'Finvoice.xsl', 'finvoice_%d.xml', 'invoice', 50, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceFinnishWithVirtualBarcode', 'invoice_printer.php', 'invoice,fi,Y', 'lasku_%d.pdf', 'invoice', 60, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceFinnishFormless', 'invoice_printer_formless.php', 'invoice,fi,N', 'lasku_%d.pdf', 'invoice', 70, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceEnglishWithVirtualBarcode', 'invoice_printer.php', 'invoice,en,Y', 'invoice_%d.pdf', 'invoice', 70, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceEnglishFormless', 'invoice_printer_formless.php', 'invoice,en,N', 'invoice_%d.pdf', 'invoice', 80, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceSwedish', 'invoice_printer.php', 'invoice,sv-FI,N', 'faktura_%d.pdf', 'invoice', 90, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintInvoiceSwedishFormless', 'invoice_printer_formless.php', 'invoice,sv-FI,N', 'faktura_%d.pdf', 'invoice', 100, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailReceiptFinnish', 'invoice_printer_email.php', 'receipt', 'kuitti_%d.pdf', 'invoice', 110, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailReceiptSwedish', 'invoice_printer_email.php', 'receipt,sv-FI', 'kvitto_%d.pdf', 'invoice', 120, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailReceiptEnglish', 'invoice_printer_email.php', 'receipt,en', 'receipt_%d.pdf', 'invoice', 130, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationFinnish', 'invoice_printer_order_confirmation.php', 'receipt', 'tilausvahvistus_%d.pdf', 'invoice', 140, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationSwedish', 'invoice_printer_order_confirmation.php', 'receipt,sv-FI', 'orderbekraftelse_%d.pdf', 'invoice', 150, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEnglish', 'invoice_printer_order_confirmation.php', 'receipt,en', 'order_confirmation_%d.pdf', 'invoice', 160, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEmailFinnish', 'invoice_printer_order_confirmation_email.php', 'receipt', 'tilausvahvistus_%d.pdf', 'invoice', 170, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEmailSwedish', 'invoice_printer_order_confirmation_email.php', 'receipt,sv-FI', 'orderbekraftelse_%d.pdf', 'invoice', 180, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEmailEnglish', 'invoice_printer_order_confirmation_email.php', 'receipt,en', 'order_confirmation_%d.pdf', 'invoice', 190, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintFinvoiceSOAP', 'invoice_printer_finvoice_soap.php', '', 'finvoice_%d.xml', 'invoice', 55, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOfferFinnish', 'invoice_printer_offer.php', 'offer', 'tarjous_%d.pdf', 'offer', 200, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOfferSwedish', 'invoice_printer_offer.php', 'offer,sv-FI', 'anbud_%d.pdf', 'offer', 210, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOfferEnglish', 'invoice_printer_offer.php', 'offer,en', 'offer_%d.pdf', 'offer', 220, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOfferEmailFinnish', 'invoice_printer_offer_email.php', 'offer', 'tarjous_%d.pdf', 'offer', 230, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOfferEmailSwedish', 'invoice_printer_offer_email.php', 'offer,sv-FI', 'anbud_%d.pdf', 'offer', 240, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOfferEmailEnglish', 'invoice_printer_offer_email.php', 'offer,en', 'offer_%d.pdf', 'offer', 250, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailNoAttachment', 'invoice_printer_email.php', 'invoice,fi-FI,N,attachment=false', '', 'invoice', 260, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintEmailReceiptNoAttachment', 'invoice_printer_email.php', 'receipt,fi-FI,N,attachment=false', '', 'invoice', 270, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOrderConfirmationEmailNoAttachment', 'invoice_printer_order_confirmation_email.php', 'receipt,fi-FI,N,attachment=false', '', 'invoice', 280, 1);
INSERT INTO mlinvoice_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('PrintOfferEmailNoAttachment', 'invoice_printer_offer_email.php', 'offer,fi-FI,N,attachment=false', '', 'offer', 280, 1);

INSERT INTO mlinvoice_users (id, name, email, login, passwd, type_id) VALUES (1, 'Administrator', '', 'admin', md5('admin'), 2);

-- Indexes for quick search results
CREATE INDEX mlinvoice_company_name on mlinvoice_company(company_name);
CREATE INDEX mlinvoice_company_id on mlinvoice_company(company_id);
CREATE INDEX mlinvoice_company_deleted on mlinvoice_company(deleted);
CREATE INDEX mlinvoice_invoice_no on mlinvoice_invoice(invoice_no);
CREATE INDEX mlinvoice_invoice_ref_number on mlinvoice_invoice(ref_number);
CREATE INDEX mlinvoice_invoice_name on mlinvoice_invoice(name);
CREATE INDEX mlinvoice_invoice_deleted on mlinvoice_invoice(deleted);
CREATE INDEX mlinvoice_base_name on mlinvoice_base(name);
CREATE INDEX mlinvoice_base_deleted on mlinvoice_base(deleted);
CREATE INDEX mlinvoice_product_name on mlinvoice_product(product_name);
CREATE INDEX mlinvoice_product_code on mlinvoice_product(product_code);
CREATE INDEX mlinvoice_product_deleted on mlinvoice_product(deleted);
CREATE INDEX mlinvoice_product_order_no_deleted on mlinvoice_product(order_no, deleted);
CREATE INDEX mlinvoice_users_name on mlinvoice_users(name);
CREATE INDEX mlinvoice_users_deleted on mlinvoice_users(deleted);


-- ***** The following rows just add some sample data *****
INSERT INTO mlinvoice_base (id, name, contact_person, street_address, zip_code, city, phone, bank_name, bank_account, bank_iban, bank_swiftbic, www, email, company_id) VALUES (1, 'Testilaskuttaja', 'Taavi Testaaja', 'Testitie', '00730', 'HELSINKI', '+358 50 123456', 'Pankki', '123456-654321', 'FI12 3456 7890 1234 56', 'FIHHPANK', 'http://labs.fi/', 'info@labs', '123456-7');

INSERT INTO mlinvoice_company (id, inside_info, type_id, company_name, contact_person, street_address, zip_code, city, phone, fax, email, gsm, billing_address, www, info, company_id) VALUES (1, NULL, NULL, 'Testifirma', NULL, 'Testitie', '00730', 'HELSINKI', '050-123 4567', '-', 'info@labs', '050-123 4567', 'Testifirma\r\nTestitie\r\n00730 HELSINKI', 'www.labs.fi', '', 'FI-123456-x');

INSERT INTO mlinvoice_company_contact (id, company_id, contact_person, person_title, email, phone, gsm) VALUES (1, 1, 'Ere Maijala', 'Päällikkö', 'info@labs', '-', '050-123 4567');

INSERT INTO mlinvoice_invoice
  (id, name, company_id, invoice_no, invoice_date, due_date, payment_date, ref_number, state_id, reference, base_id)
  VALUES (1, 'Testi', 1, '100', 20121230, 20130113, NULL, '', 1, '', 1);

INSERT INTO mlinvoice_invoice_row (id, invoice_id, description, type_id, pcs, price, row_date, vat, order_no)
  VALUES (1, 1, 'Testirivi 1', 3, 12.00, 150.00, 20121219, 24, 5);


