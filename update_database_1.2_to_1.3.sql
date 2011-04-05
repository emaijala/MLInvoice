alter table vllasku_base add column (
  invoice_email_from varchar(512) NULL,
  invoice_email_bcc varchar(512) NULL,
  invoice_email_subject varchar(255) NULL,
  invoice_email_body text NULL
);

