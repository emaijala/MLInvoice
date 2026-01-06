<?php
/**
 * Database Updater.
 *
 * PHP version 8
 *
 * Copyright (C) Ere Maijala 2026.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */

namespace MLInvoice\Database;

use DI\Attribute\Inject;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Database Updater.
 *
 * @category MLInvoice
 * @package  MLInvoice\Database
 * @author   Ere Maijala <ere@labs.fi>
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     http://labs.fi/mlinvoice.eng.php
 */
class DatabaseUpdater
{
    /**
     * Constructor
     *
     * @param EntityManagerInterface $entityManager Entity manager
     */
    public function __construct(
        #[Inject('config')] protected array $config,
        protected EntityManagerInterface $entityManager,
        protected LoggerInterface $logger,
    ) {
    }

    /**
     * Verify database status and upgrade as necessary.
     * Expects all pre-1.6.0 changes to have been already made.
     *
     * @return string status (OK|UPGRADED|FAILED)
     */
    public function verifyDatabase(): string
    {
        $conn = $this->entityManager->getConnection();
        $prefix = $this->config['Database']['table_prefix'] ?? 'mlinvoice_';
        // phpcs:disable Generic.Files.LineLength
        $res = $conn->executeQuery("SHOW TABLES LIKE '{$prefix}state'");
        if ($res->rowCount() === 0) {
            $res = $this->query(
                <<<EOT
    CREATE TABLE {$prefix}state (
    id char(32) NOT NULL,
    data varchar(100) NULL,
    PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
            );
            if (!$res) {
                return 'FAILED';
            }
            $conn->executeQuery(
                "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '15')"
            );
        }

        // Convert any MyISAM tables to InnoDB
        $res = $conn->executeQuery("SELECT data FROM {$prefix}state WHERE id=?", ['tableconversiondone']);
        if ($res->rowCount() === 0) {
            $conn->beginTransaction();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
            $res = $conn->executeQuery(
                "SHOW TABLE STATUS WHERE Name like '{$prefix}_' AND ENGINE='MyISAM'"
            );
            while (($row = $res->fetchAssociative()) !== false) {
                $res2 = $this->query(
                    'ALTER TABLE `' . $row['Name'] . '` ENGINE=INNODB'
                );
                if (!$res2) {
                    $conn->rollBack();
                    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
                    $this->logger->critical(
                        'Database upgrade query failed. Please convert the tables using'
                        . ' MyISAM engine to InnoDB engine manually'
                    );
                    return 'FAILED';
                }
            }
            $conn->executeStatement(
                "INSERT INTO {$prefix}state (id, data) VALUES ('tableconversiondone', '1')"
            );
            $conn->commit();
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        }

        $res = $conn->executeQuery(
            "SELECT data FROM {$prefix}state WHERE id=?", ['version']
        );
        $version = $res->rowCount() ? $res->fetchAssociative()['data'] : 0;
        $updates = [];
        if ($version < 16) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}invoice ADD CONSTRAINT FOREIGN KEY (base_id) REFERENCES {$prefix}base(id)",
                    "ALTER TABLE {$prefix}invoice ADD COLUMN interval_type int(11) NOT NULL default 0",
                    "ALTER TABLE {$prefix}invoice ADD COLUMN next_interval_date int(11) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '16')"
                ]
            );
        }
        if ($version < 17) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}invoice_state CHANGE COLUMN name name varchar(255)",
                    "UPDATE {$prefix}invoice_state set name='StateOpen' where id=1",
                    "UPDATE {$prefix}invoice_state set name='StateSent' where id=2",
                    "UPDATE {$prefix}invoice_state set name='StatePaid' where id=3",
                    "UPDATE {$prefix}invoice_state set name='StateAnnulled' where id=4",
                    "UPDATE {$prefix}invoice_state set name='StateFirstReminder' where id=5",
                    "UPDATE {$prefix}invoice_state set name='StateSecondReminder' where id=6",
                    "UPDATE {$prefix}invoice_state set name='StateDebtCollection' where id=7",
                    "UPDATE {$prefix}print_template set name='PrintInvoiceFinnish' where name='Lasku'",
                    "UPDATE {$prefix}print_template set name='PrintDispatchNoteFinnish' where name='Lähetysluettelo'",
                    "UPDATE {$prefix}print_template set name='PrintReceiptFinnish' where name='Kuitti'",
                    "UPDATE {$prefix}print_template set name='PrintEmailFinnish' where name='Email'",
                    "UPDATE {$prefix}print_template set name='PrintInvoiceEnglish' where name='Invoice'",
                    "UPDATE {$prefix}print_template set name='PrintReceiptEnglish' where name='Receipt'",
                    "UPDATE {$prefix}print_template set name='PrintFinvoice' where name='Finvoice'",
                    "UPDATE {$prefix}print_template set name='PrintFinvoiceStyled' where name='Finvoice Styled'",
                    "UPDATE {$prefix}print_template set name='PrintInvoiceFinnishWithVirtualBarcode' where name='Lasku virtuaaliviivakoodilla'",
                    "UPDATE {$prefix}print_template set name='PrintInvoiceFinnishFormless' where name='Lomakkeeton lasku'",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintInvoiceEnglishWithVirtualBarcode', 'invoice_printer.php', 'invoice,en,Y', 'invoice_%d.pdf', 'invoice', 70, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintInvoiceEnglishFormless', 'invoice_printer_formless.php', 'invoice,en,N', 'invoice_%d.pdf', 'invoice', 80, 1)",
                    "ALTER TABLE {$prefix}row_type CHANGE COLUMN name name varchar(255)",
                    "UPDATE {$prefix}row_type set name='TypeHour' where name='h'",
                    "UPDATE {$prefix}row_type set name='TypeDay' where name='pv'",
                    "UPDATE {$prefix}row_type set name='TypeMonth' where name='kk'",
                    "UPDATE {$prefix}row_type set name='TypePieces' where name='kpl'",
                    "UPDATE {$prefix}row_type set name='TypeYear' where name='vuosi'",
                    "UPDATE {$prefix}row_type set name='TypeLot' where name='erä'",
                    "UPDATE {$prefix}row_type set name='TypeKilometer' where name='km'",
                    "UPDATE {$prefix}row_type set name='TypeKilogram' where name='kg'",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '17')"
                ]
            );
        }
        if ($version < 18) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base ADD COLUMN country varchar(255) default NULL",
                    "ALTER TABLE {$prefix}company ADD COLUMN country varchar(255) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '18')"
                ]
            );
        }
        if ($version < 19) {
            $updates = array_merge(
                $updates,
                [
                    "UPDATE {$prefix}session_type set name='SessionTypeUser' where name='Käyttäjä'",
                    "UPDATE {$prefix}session_type set name='SessionTypeAdmin' where name='Ylläpitäjä'",
                    "UPDATE {$prefix}session_type set name='SessionTypeBackupUser' where name='Käyttäjä - varmuuskopioija'",
                    "UPDATE {$prefix}session_type set name='SessionTypeReadOnly' where name='Vain laskujen ja raporttien tarkastelu'",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '19')"
                ]
            );
        }
        if ($version < 20) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product CHANGE COLUMN unit_price unit_price decimal(15,5)",
                    "ALTER TABLE {$prefix}invoice_row CHANGE COLUMN price price decimal(15,5)",
                    "ALTER TABLE {$prefix}product CHANGE COLUMN discount discount decimal(4,1) NULL",
                    "ALTER TABLE {$prefix}invoice_row CHANGE COLUMN discount discount decimal(4,1) NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '20')"
                ]
            );
        }
        if ($version < 21) {
            $updates = array_merge(
                $updates,
                [
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintInvoiceSwedish', 'invoice_printer.php', 'invoice,sv-FI,Y', 'faktura_%d.pdf', 'invoice', 90, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintInvoiceSwedishFormless', 'invoice_printer_formless.php', 'invoice,sv-FI,N', 'faktura_%d.pdf', 'invoice', 100, 1)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '21')"
                ]
            );
        }
        if ($version < 22) {
            $updates = array_merge(
                $updates,
                [
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintEmailReceiptFinnish', 'invoice_printer_email.php', 'receipt', 'kuitti_%d.pdf', 'invoice', 110, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintEmailReceiptSwedish', 'invoice_printer_email.php', 'receipt,sv-FI', 'kvitto_%d.pdf', 'invoice', 120, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintEmailReceiptEnglish', 'invoice_printer_email.php', 'receipt,en', 'receipt_%d.pdf', 'invoice', 130, 1)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '22')"
                ]
            );
        }
        if ($version < 23) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product ADD COLUMN order_no int(11) default NULL",
                    "ALTER TABLE {$prefix}users CHANGE COLUMN name name varchar(255)",
                    "ALTER TABLE {$prefix}users CHANGE COLUMN login login varchar(255)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '23')"
                ]
            );
        }
        if ($version < 24) {
            $updates = array_merge(
                $updates,
                [
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOrderConfirmationFinnish', 'invoice_printer_order_confirmation.php', 'receipt', 'tilausvahvistus_%d.pdf', 'invoice', 140, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOrderConfirmationSwedish', 'invoice_printer_order_confirmation.php', 'receipt,sv-FI', 'orderbekraftelse_%d.pdf', 'invoice', 150, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOrderConfirmationEnglish', 'invoice_printer_order_confirmation.php', 'receipt,en', 'order_confirmation_%d.pdf', 'invoice', 160, 1)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '24')"
                ]
            );
        }
        if ($version < 25) {
            $updates = array_merge(
                $updates,
                [
                    <<<EOT
    CREATE TABLE {$prefix}delivery_terms (
    id int(11) NOT NULL auto_increment,
    deleted tinyint NOT NULL default 0,
    name varchar(255) default NULL,
    order_no int(11) default NULL,
    PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
    EOT
                    ,
                    <<<EOT
    CREATE TABLE {$prefix}delivery_method (
    id int(11) NOT NULL auto_increment,
    deleted tinyint NOT NULL default 0,
    name varchar(255) default NULL,
    order_no int(11) default NULL,
    PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
    EOT
                    ,
                    "ALTER TABLE {$prefix}invoice ADD COLUMN delivery_terms_id int(11) default NULL",
                    "ALTER TABLE {$prefix}invoice ADD CONSTRAINT FOREIGN KEY (delivery_terms_id) REFERENCES {$prefix}delivery_terms(id)",
                    "ALTER TABLE {$prefix}invoice ADD COLUMN delivery_method_id int(11) default NULL",
                    "ALTER TABLE {$prefix}invoice ADD CONSTRAINT FOREIGN KEY (delivery_method_id) REFERENCES {$prefix}delivery_method(id)",
                    "ALTER TABLE {$prefix}company ADD COLUMN delivery_terms_id int(11) default NULL",
                    "ALTER TABLE {$prefix}company ADD CONSTRAINT FOREIGN KEY (delivery_terms_id) REFERENCES {$prefix}delivery_terms(id)",
                    "ALTER TABLE {$prefix}company ADD COLUMN delivery_method_id int(11) default NULL",
                    "ALTER TABLE {$prefix}company ADD CONSTRAINT FOREIGN KEY (delivery_method_id) REFERENCES {$prefix}delivery_method(id)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '25')"
                ]
            );
        }

        if ($version < 26) {
            $updates = array_merge(
                $updates,
                [
                    "CREATE INDEX {$prefix}company_name on {$prefix}company(company_name)",
                    "CREATE INDEX {$prefix}company_id on {$prefix}company(company_id)",
                    "CREATE INDEX {$prefix}company_deleted on {$prefix}company(deleted)",
                    "CREATE INDEX {$prefix}invoice_no on {$prefix}invoice(invoice_no)",
                    "CREATE INDEX {$prefix}invoice_ref_number on {$prefix}invoice(ref_number)",
                    "CREATE INDEX {$prefix}invoice_name on {$prefix}invoice(name)",
                    "CREATE INDEX {$prefix}invoice_deleted on {$prefix}invoice(deleted)",
                    "CREATE INDEX {$prefix}base_name on {$prefix}base(name)",
                    "CREATE INDEX {$prefix}base_deleted on {$prefix}base(deleted)",
                    "CREATE INDEX {$prefix}product_name on {$prefix}product(product_name)",
                    "CREATE INDEX {$prefix}product_code on {$prefix}product(product_code)",
                    "CREATE INDEX {$prefix}product_deleted on {$prefix}product(deleted)",
                    "CREATE INDEX {$prefix}product_order_no_deleted on {$prefix}product(order_no, deleted)",
                    "CREATE INDEX {$prefix}users_name on {$prefix}users(name)",
                    "CREATE INDEX {$prefix}users_deleted on {$prefix}users(deleted)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '26')"
                ]
            );
        }

        if ($version < 27) {
            $updates = array_merge(
                $updates,
                [
                    "INSERT INTO {$prefix}invoice_state (name, order_no) VALUES ('StatePaidInCash', 17)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '27')"
                ]
            );
        }

        if ($version < 28) {
            $updates = array_merge(
                $updates,
                [
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOrderConfirmationEmailFinnish', 'invoice_printer_order_confirmation_email.php', 'receipt', 'tilausvahvistus_%d.pdf', 'invoice', 170, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOrderConfirmationEmailSwedish', 'invoice_printer_order_confirmation_email.php', 'receipt,sv-FI', 'orderbekraftelse_%d.pdf', 'invoice', 180, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOrderConfirmationEmailEnglish', 'invoice_printer_order_confirmation_email.php', 'receipt,en', 'order_confirmation_%d.pdf', 'invoice', 190, 1)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '28')"
                ]
            );
        }

        if ($version < 29) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}session CHANGE COLUMN id id varchar(255)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '29')"
                ]
            );
        }

        if ($version < 30) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base ADD COLUMN payment_intermediator varchar(100) default NULL",
                    "ALTER TABLE {$prefix}company ADD COLUMN payment_intermediator varchar(100) default NULL",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintFinvoiceSOAP', 'invoice_printer_finvoice_soap.php', '', 'finvoice_%d.xml', 'invoice', 55, 1)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '30')"
                ]
            );
        }

        if ($version < 31) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product ADD COLUMN ean_code1 varchar(13) default NULL",
                    "ALTER TABLE {$prefix}product ADD COLUMN ean_code2 varchar(13) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '31')"
                ]
            );
        }

        if ($version < 32) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product ADD COLUMN purchase_price decimal(15,5) NULL",
                    "ALTER TABLE {$prefix}product ADD COLUMN stock_balance int(11) default NULL",
                    <<<EOT
    CREATE TABLE {$prefix}stock_balance_log (
    id int(11) NOT NULL auto_increment,
    time timestamp NOT NULL default CURRENT_TIMESTAMP,
    user_id int(11) NOT NULL,
    product_id int(11) NOT NULL,
    stock_change int(11) NOT NULL,
    description varchar(255) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (user_id) REFERENCES {$prefix}users(id),
    FOREIGN KEY (product_id) REFERENCES {$prefix}product(id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
    EOT
                    ,
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '32')"
                ]
            );
        }

        if ($version < 33) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base ADD COLUMN receipt_email_subject varchar(255) NULL",
                    "ALTER TABLE {$prefix}base ADD COLUMN receipt_email_body text NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '33')"
                ]
            );
        }

        if ($version < 34) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product CHANGE COLUMN stock_balance stock_balance decimal(11,2) default NULL",
                    "ALTER TABLE {$prefix}stock_balance_log CHANGE COLUMN stock_change stock_change decimal(11,2) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '34')"
                ]
            );
        }

        if ($version < 35) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}invoice_state ADD COLUMN invoice_open tinyint NOT NULL default 0",
                    "ALTER TABLE {$prefix}invoice_state ADD COLUMN invoice_unpaid tinyint NOT NULL default 0",
                    "UPDATE {$prefix}invoice_state SET invoice_open=1 WHERE id IN (1)",
                    "UPDATE {$prefix}invoice_state SET invoice_unpaid=1 WHERE id IN (2, 5, 6, 7)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '35')"
                ]
            );
        }

        if ($version < 36) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product CHANGE COLUMN ean_code1 barcode1 varchar(255) default NULL",
                    "ALTER TABLE {$prefix}product CHANGE COLUMN ean_code2 barcode2 varchar(255) default NULL",
                    "ALTER TABLE {$prefix}product ADD COLUMN barcode1_type varchar(20) default NULL",
                    "ALTER TABLE {$prefix}product ADD COLUMN barcode2_type varchar(20) default NULL",
                    "UPDATE {$prefix}product SET barcode1_type='EAN13' WHERE barcode1 IS NOT NULL",
                    "UPDATE {$prefix}product SET barcode2_type='EAN13' WHERE barcode2 IS NOT NULL",
                    "ALTER TABLE {$prefix}base ADD COLUMN order_confirmation_email_subject varchar(255) NULL",
                    "ALTER TABLE {$prefix}base ADD COLUMN order_confirmation_email_body text NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '36')"
                ]
            );
        }

        if ($version < 37) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}company ADD COLUMN payment_days int(11) default NULL",
                    "ALTER TABLE {$prefix}company ADD COLUMN terms_of_payment varchar(255) NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '37')"
                ]
            );
        }

        if ($version < 38) {
            $updates = array_merge(
                $updates,
                [
                    "UPDATE {$prefix}invoice_row ir SET ir.row_date=(SELECT i.invoice_date"
                    . " FROM {$prefix}invoice i where i.id=ir.invoice_id) WHERE ir.row_date IS NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '38')"
                ]
            );
        }

        if ($version < 39) {
            // Check for a bug in database creation script in v1.12.0 and v1.12.1
            $rows = dbParamQuery(
                "SELECT count(*) as cnt FROM information_schema.columns WHERE table_schema = '"
                . _DB_NAME_ . "' AND table_name   = '{$prefix}invoice_row' AND column_name = 'partial_payment'"
            );
            $count = $rows[0]['cnt'];
            if ($count == 0) {
                $updates = array_merge(
                    $updates,
                    [
                        "ALTER TABLE {$prefix}invoice_row ADD COLUMN partial_payment tinyint NOT NULL default 0",
                        "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '39')"
                    ]
                );
            }
        }

        if ($version < 40) {
            $updates = array_merge(
                $updates,
                [
                    "UPDATE {$prefix}invoice_state SET invoice_unpaid=1 WHERE id=1",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '40')"
                ]
            );
        }

        if ($version < 41) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base ADD COLUMN invoice_default_info text NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '41')"
                ]
            );
        }

        if ($version < 42) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}invoice_state ADD COLUMN invoice_offer tinyint NOT NULL default 0",
                    "ALTER TABLE {$prefix}invoice_state ADD COLUMN invoice_offer_sent tinyint NOT NULL default 0",
                    "INSERT INTO {$prefix}invoice_state (name, order_no, invoice_open, invoice_unpaid, invoice_offer)"
                    . " VALUES ('StateOfferOpen', 40, 1, 0, 1)",
                    "INSERT INTO {$prefix}invoice_state (name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent)"
                    . " VALUES ('StateOfferSent', 45, 1, 0, 1, 1)",
                    "INSERT INTO {$prefix}invoice_state (name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent)"
                    . " VALUES ('StateOfferUnrealised', 50, 0, 0, 1, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOfferFinnish', 'invoice_printer_offer.php', 'offer', 'tarjous_%d.pdf', 'offer', 200, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOfferSwedish', 'invoice_printer_offer.php', 'offer,sv-FI', 'anbud_%d.pdf', 'offer', 210, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOfferEnglish', 'invoice_printer_offer.php', 'offer,en', 'offer_%d.pdf', 'offer', 220, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOfferEmailFinnish', 'invoice_printer_offer_email.php', 'offer', 'tarjous_%d.pdf', 'offer', 230, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOfferEmailSwedish', 'invoice_printer_offer_email.php', 'offer,sv-FI', 'anbud_%d.pdf', 'offer', 240, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOfferEmailEnglish', 'invoice_printer_offer_email.php', 'offer,en', 'offer_%d.pdf', 'offer', 250, 1)",
                    "ALTER TABLE {$prefix}base ADD COLUMN offer_email_subject varchar(255) NULL",
                    "ALTER TABLE {$prefix}base ADD COLUMN offer_email_body text NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '42')"
                ]
            );
        }

        if ($version < 43) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}company_contact ADD COLUMN contact_type VARCHAR(100) NULL",
                    "INSERT INTO {$prefix}invoice_state (name, order_no, invoice_open, invoice_unpaid, invoice_offer, invoice_offer_sent)"
                    . " VALUES ('StateOfferRealised', 55, 0, 0, 1, 1)",
                    "ALTER TABLE {$prefix}base ADD COLUMN invoice_default_foreword text NULL",
                    "ALTER TABLE {$prefix}base ADD COLUMN invoice_default_afterword text NULL",
                    "ALTER TABLE {$prefix}base ADD COLUMN offer_default_foreword text NULL",
                    "ALTER TABLE {$prefix}base ADD COLUMN offer_default_afterword text NULL",
                    "ALTER TABLE {$prefix}invoice ADD COLUMN foreword text NULL",
                    "ALTER TABLE {$prefix}invoice ADD COLUMN afterword text NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '43')"
                ]
            );
        }

        if ($version < 44) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}invoice ADD COLUMN delivery_time varchar(100) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '44')"
                ]
            );
        }

        if ($version < 45) {
            $updates = array_merge(
                $updates,
                [
                    <<<EOT
    CREATE TABLE {$prefix}default_value (
    id int(11) NOT NULL auto_increment,
    deleted tinyint NOT NULL default 0,
    name varchar(255) default NULL,
    order_no int(11) default NULL,
    type varchar(100) NULL,
    content text NULL,
    PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    ,
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '45')"
                ]
            );
        }

        if ($version < 46) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base ADD COLUMN terms_of_payment varchar(255) NULL",
                    "ALTER TABLE {$prefix}base ADD COLUMN period_for_complaints varchar(255) NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '46')"
                ]
            );
        }

        if ($version < 47) {
            $updates = array_merge(
                $updates,
                [
                    "UPDATE {$prefix}print_template SET type='offer' WHERE filename LIKE 'invoice_printer_offer%'",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '47')"
                ]
            );
        }

        if ($version < 48) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product ADD COLUMN vendor varchar(255) NULL",
                    "ALTER TABLE {$prefix}product ADD COLUMN vendors_code varchar(100) NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '48')"
                ]
            );
        }

        if ($version < 49) {
            $updates = array_merge(
                $updates,
                [
                    <<<EOT
    CREATE TABLE {$prefix}company_tag (
    id int(11) NOT NULL auto_increment,
    tag varchar(100) default NULL,
    PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
    EOT
                    , <<<EOT
    CREATE TABLE {$prefix}company_tag_link (
    id int(11) NOT NULL auto_increment,
    tag_id int(11) NOT NULL,
    company_id int(11) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (tag_id) REFERENCES {$prefix}company_tag(id),
    FOREIGN KEY (company_id) REFERENCES {$prefix}company(id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci
    EOT
                    , <<<EOT
    CREATE TABLE {$prefix}contact_tag (
    id int(11) NOT NULL auto_increment,
    tag varchar(100) default NULL,
    PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    , <<<EOT
    CREATE TABLE {$prefix}contact_tag_link (
    id int(11) NOT NULL auto_increment,
    tag_id int(11) NOT NULL,
    contact_id int(11) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (tag_id) REFERENCES {$prefix}contact_tag(id),
    FOREIGN KEY (contact_id) REFERENCES {$prefix}company_contact(id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    ,
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '49')"
                ]
            );
        }

        if ($version < 50) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product ADD COLUMN discount_amount decimal(15,5) NULL",
                    "ALTER TABLE {$prefix}invoice_row ADD COLUMN discount_amount decimal(15,5) NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '50')"
                ]
            );
        }

        if ($version < 51) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base CHANGE COLUMN email email varchar(512) default NULL",
                    "ALTER TABLE {$prefix}company CHANGE COLUMN email email varchar(512) default NULL",
                    "ALTER TABLE {$prefix}company_contact CHANGE COLUMN email email varchar(512) default NULL",
                    "ALTER TABLE {$prefix}users CHANGE COLUMN email email varchar(512) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '51')"
                ]
            );
        }

        if ($version < 52) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base ADD COLUMN inactive tinyint NOT NULL default 0",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '52')"
                ]
            );
        }

        if ($version < 53) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}invoice ADD COLUMN uuid varchar(50) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '53')"
                ]
            );
        }

        if ($version < 54) {
            $updates = array_merge(
                $updates,
                [
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintEmailEnglish', 'invoice_printer_email.php', 'invoice,en-US', 'invoice_%d.pdf', 'invoice', 11, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintEmailSwedish', 'invoice_printer_email.php', 'invoice,sv-FI', 'faktura_%d.pdf', 'invoice', 12, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintEmailNoAttachment', 'invoice_printer_email.php', 'invoice,fi-FI,N,attachment=false', '', 'invoice', 260, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintEmailReceiptNoAttachment', 'invoice_printer_email.php', 'receipt,fi-FI,N,attachment=false', '', 'invoice', 270, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOrderConfirmationEmailNoAttachment', 'invoice_printer_order_confirmation_email.php', 'receipt,fi-FI,N,attachment=false', '', 'invoice', 280, 1)",
                    "INSERT INTO {$prefix}print_template (name, filename, parameters, output_filename, type, order_no, inactive)"
                    . " VALUES ('PrintOfferEmailNoAttachment', 'invoice_printer_offer_email.php', 'offer,fi-FI,N,attachment=false', '', 'offer', 280, 1)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '54')"
                ]
            );
        }

        if ($version < 55) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}product ADD COLUMN weight decimal(15,5) NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '55')"
                ]
            );
        }

        if ($version < 56) {
            $updates = array_merge(
                $updates,
                [
                    <<<EOT
    CREATE TABLE {$prefix}custom_price (
        id int(11) NOT NULL auto_increment,
        company_id int(11) NOT NULL,
        discount decimal(4,1) NULL,
        multiplier decimal(10,5) NULL,
        valid_until int(11) default NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (company_id) REFERENCES {$prefix}company(id) ON DELETE CASCADE
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    , <<<EOT
    CREATE TABLE {$prefix}custom_price_map (
        id int(11) NOT NULL auto_increment,
        custom_price_id int(11) NOT NULL,
        product_id int(11) NOT NULL,
        unit_price decimal(15,5) NULL,
        discount decimal(4,1) NULL,
        discount_amount decimal(15,5) NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (custom_price_id) REFERENCES {$prefix}custom_price(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES {$prefix}product(id) ON DELETE CASCADE
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    ,
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '56')"
                ]
            );
        }

        if ($version < 57) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}default_value ADD COLUMN additional text NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '57')"
                ]
            );
        }

        if ($version < 58) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}users ADD COLUMN token varchar(255) NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '58')"
                ]
            );
        }

        if ($version < 59) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base CHANGE COLUMN bank_account bank_account varchar(30) NOT NULL DEFAULT ''",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '59')"
                ]
            );
        }

        if ($version < 60) {
            $updates = array_merge(
                $updates,
                [
                    <<<EOT
    CREATE TABLE {$prefix}send_api_config (
        id int(11) NOT NULL auto_increment,
        base_id int(11) NOT NULL,
        name varchar(255) NULL,
        method varchar(255) NULL,
        username varchar(255) NULL,
        password varchar(255) NULL,
        reference varchar(255) NULL,
        post_class tinyint default 0 NOT NULL,
        add_to_queue tinyint default 0 NOT NULL,
        finvoice_mail_backup tinyint default 0 NOT NULL,
        PRIMARY KEY (id),
        FOREIGN KEY (base_id) REFERENCES {$prefix}base(id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    ,
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '60')"
                ]
            );
        }

        if ($version < 61) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}company ADD COLUMN invoice_vatless tinyint NOT NULL default 0",
                    "ALTER TABLE {$prefix}company ADD COLUMN invoice_default_foreword text NULL",
                    "ALTER TABLE {$prefix}company ADD COLUMN invoice_default_afterword text NULL",
                    "ALTER TABLE {$prefix}company ADD COLUMN offer_default_foreword text NULL",
                    "ALTER TABLE {$prefix}company ADD COLUMN offer_default_afterword text NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '61')"
                ]
            );
        }

        if ($version < 62) {
            $updates = array_merge(
                $updates,
                [
                    <<<EOT
    CREATE TABLE {$prefix}invoice_type (
        id int(11) NOT NULL auto_increment,
        deleted tinyint NOT NULL default 0,
        identifier varchar(255) default NULL,
        name varchar(255) default NULL,
        order_no int(11) default NULL,
        PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    ,
                    "ALTER TABLE {$prefix}invoice ADD COLUMN type_id int(11) default NULL",
                    "ALTER TABLE {$prefix}invoice ADD CONSTRAINT FOREIGN KEY (type_id) REFERENCES {$prefix}invoice_type(id)",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '62')"
                ]
            );
        }

        if ($version < 63) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}company ADD COLUMN delivery_address text default NULL",
                    "ALTER TABLE {$prefix}invoice ADD COLUMN delivery_address text default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '63')"
                ]
            );
        }

        if ($version < 64) {
            $updates = array_merge(
                $updates,
                [
                    <<<EOT
    CREATE TABLE {$prefix}attachment (
        id int(11) NOT NULL auto_increment,
        name varchar(255) NOT NULL,
        mimetype varchar(255) NOT NULL,
        description varchar(255) default NULL,
        date int(11) default NULL,
        filename varchar(255) NOT NULL,
        filesize integer(11) NULL,
        filedata longblob NOT NULL,
        order_no int(11) default NULL,
        PRIMARY KEY (id)
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    ,
                    <<<EOT
    CREATE TABLE {$prefix}invoice_attachment (
        id int(11) NOT NULL auto_increment,
        invoice_id int(11) NOT NULL,
        name varchar(255) NOT NULL,
        mimetype varchar(255) NOT NULL,
        description varchar(255) default NULL,
        date int(11) default NULL,
        filename varchar(255) NOT NULL,
        filesize integer(11) NULL,
        filedata longblob NOT NULL,
        order_no int(11) default NULL,
        send tinyint NOT NULL default 0,
        PRIMARY KEY (id),
        FOREIGN KEY (invoice_id) REFERENCES {$prefix}invoice(id) ON DELETE CASCADE
    ) ENGINE=INNODB CHARACTER SET utf8 COLLATE utf8_swedish_ci;
    EOT
                    ,
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '64')"
                ]
            );
        }

        if ($version < 65) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}company ADD COLUMN invoice_default_reference varchar(50) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '65')"
                ]
            );
        }

        if ($version < 66) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}send_api_config ADD COLUMN directory varchar(255) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '66')"
                ]
            );
        }

        if ($version < 67) {
            $updates = array_merge(
                $updates,
                [
                    "UPDATE {$prefix}invoice_state SET invoice_open=0 WHERE name='StateOfferSent'",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '67')"
                ]
            );
        }

        if ($version < 68) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}invoice_state ADD COLUMN invoice_template tinyint NOT NULL default 0",
                    "INSERT INTO {$prefix}invoice_state (name, order_no, invoice_template)"
                    . " VALUES ('StateInvoiceRecurringTemplate', 60, 1)",
                    "ALTER TABLE {$prefix}invoice ADD COLUMN template_invoice_id int(11) default NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '68')"
                ]
            );
        }

        if ($version < 69) {
            $updates = array_merge(
                $updates,
                [
                    "ALTER TABLE {$prefix}base ADD COLUMN payment_recipient_name varchar(100) NULL",
                    "REPLACE INTO {$prefix}state (id, data) VALUES ('version', '69')"
                ]
            );
        }

        // phpcs:enable Generic.Files.LineLength
        if (!empty($updates)) {
            $conn->beginTransaction();
            foreach ($updates as $update) {
                if (!$this->query($update)) {
                    $conn->rollBack();
                    $all = implode("\n", array_map(fn ($s) => "$s\n", $updates));
                    $this->logger->critical(
                        "Database upgrade query failed. Please execute the following queries manually:\n$all"
                    );
                    return 'FAILED';
                }
            }
            $conn->commit();
            return 'UPGRADED';
        }
        return 'OK';
    }

    /**
     * Execute a query.
     *
     * @param string $query
     *
     * @return bool
     */
    protected function query(string $query): bool
    {
        try {
            $this->entityManager->getConnection()->executeStatement($query);
            return true;
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
