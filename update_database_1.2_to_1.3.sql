alter table vllasku_base add column (
  invoice_email_from varchar(512) NULL,
  invoice_email_bcc varchar(512) NULL,
  invoice_email_subject varchar(255) NULL,
  invoice_email_body text NULL
);

alter table vllasku_print_template add column (
  new_window tinyint NOT NULL default 0
);

INSERT INTO vllasku_print_template (name, filename, parameters, output_filename, type, order_no) VALUES ('Sähköposti', 'invoice_printer_email.php', 'invoice', 'lasku_%d.pdf','invoice', 7);
