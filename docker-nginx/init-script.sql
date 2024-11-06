CREATE DATABASE iF NOT EXISTS mlinvoice;
GRANT ALL PRIVILEGES ON mlinvoice.* TO 'mlinvoice'@'%';
ALTER USER 'mlinvoice' IDENTIFIED WITH mysql_native_password BY 'mlinvoice';
