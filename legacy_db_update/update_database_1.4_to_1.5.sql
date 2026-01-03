ALTER TABLE vllasku_product CHANGE COLUMN unit_price unit_price decimal(15,5);

ALTER TABLE vllasku_invoice_row CHANGE COLUMN price price decimal(15,5);

INSERT INTO vllasku_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('Lomakkeeton lasku', 'invoice_printer_formless.php', 'invoice,fi,N', 'lasku_%d.pdf', 'invoice', 70, 1);

ALTER TABLE vllasku_product ADD COLUMN (
  price_decimals decimal(1,0) NOT NULL default 2
);

ALTER TABLE vllasku_product CHANGE COLUMN discount discount decimal(4,1) NULL;

ALTER TABLE vllasku_invoice_row CHANGE COLUMN discount discount decimal(4,1) NULL;