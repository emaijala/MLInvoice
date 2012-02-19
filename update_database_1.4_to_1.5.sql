ALTER TABLE vllasku_product CHANGE COLUMN unit_price unit_price decimal(9,5);

ALTER TABLE vllasku_invoice_row CHANGE COLUMN price price decimal(9,5);

INSERT INTO vllasku_print_template (name, filename, parameters, output_filename, type, order_no, inactive) VALUES ('Lomakkeeton lasku', 'invoice_printer_formless.php', 'invoice,fi,N', 'lasku_%d.pdf', 'invoice', 70, 1);

