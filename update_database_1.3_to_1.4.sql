INSERT INTO vllasku_session_type (name, order_no, time_out, access_level) VALUES ('Vain katselu', 0, 3600, 0);

ALTER TABLE vllasku_product CHANGE COLUMN description description VARCHAR(255);

alter table vllasku_base add column (
  org_unit_number varchar(35) default NULL
);

alter table vllasku_company add column (
  org_unit_number varchar(35) default NULL
);

INSERT INTO vllasku_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('Finvoice', 'invoice_printer_finvoice.php', '', 'finvoice_%d.xml', 'invoice', 40, 1);
INSERT INTO vllasku_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('Finvoice Styled', 'invoice_printer_finvoice.php', 'Finvoice.xsl', 'finvoice_%d.xml', 'invoice', 50, 1);
INSERT INTO vllasku_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('Lasku virtuaaliviivakoodilla', 'invoice_printer.php', 'invoice,fi,Y', 'lasku_%d.pdf', 'invoice', 60, 1);
