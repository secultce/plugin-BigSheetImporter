<?php

use function MapasCulturais\__exec;

$app = MapasCulturais\App::getInstance();
$em = $app->em;
$conn = $em->getConnection();

return [
    'create table sheet_import' => function () {
        __exec("CREATE TABLE sheet_import (
            id SERIAL PRIMARY KEY,
            date TIMESTAMP,
            user_id INTEGER,
            rows_amount INTEGER,
            rows_saved INTEGER
        )");

        __exec("ALTER TABLE sheet_import ADD FOREIGN KEY (user_id) REFERENCES usr(id)");
    },
    'create table row_sheet_import' => function () {
        __exec("CREATE TABLE row_sheet_import (
            id SERIAL PRIMARY KEY,
            sheet_id INTEGER,
            registration_number VARCHAR(13) UNIQUE,
            process_number CHAR(20) UNIQUE,
            sacc_number BIGINT,
            term_number BIGINT,
            interest_number CHAR(12),
            trasfer_value MONEY,
            process_date TIMESTAMP,
            communication_to_proponent_sent_date TIMESTAMP,
            asjur_receipt_date TIMESTAMP,
            proponent_signature_terms_sent_date TIMESTAMP,
            casa_civil_sent_date TIMESTAMP,
            doe_publish_date TIMESTAMP,
            installment_request_date TIMESTAMP,
            eparcerias_conference_date TIMESTAMP,
            interest_date TIMESTAMP,
            payment_date TIMESTAMP,
            signed_term_validity_init_date TIMESTAMP,
            signed_term_validity_end_date TIMESTAMP,
            fiscal_name TEXT,
            fiscal_cpf CHAR(14),
            fiscal_registry VARCHAR(12),
            FOREIGN KEY (sheet_id) REFERENCES sheet_import(id)
        )");
    },
    'create table import_occurrence' => function () {
        __exec("CREATE TABLE occurrence_import (
            id SERIAL PRIMARY KEY,
            date TIMESTAMP,
            sheet_id INTEGER,
            row_index INTEGER,
            column_index CHAR(1),
            occurrence VARCHAR(255),
            given_value VARCHAR(255),
            FOREIGN KEY (sheet_id) REFERENCES sheet_import(id)
        )");
    },
    'add notification_status to row_sheet_import table' => function () {
        __exec("ALTER TABLE row_sheet_import ADD notification_status SMALLINT NOT NULL");
    },
    'insert days of accountability notifications in term table' => function () {
        __exec("INSERT INTO term (taxonomy, term, description) VALUES 
            ('notifications_accountability', '85', 'raio'),
            ('notifications_accountability', '90', 'raio'),
            ('notifications_accountability', '105', 'raio'),
            ('notifications_accountability', '55', 'refo'),
            ('notifications_accountability', '60', 'refo')
        ");
    },
    'insert deadline for submitting accountability in term table' => function () {
        __exec("INSERT INTO term (taxonomy, term, description) VALUES 
            ('accountability_deadline', '90', 'raio'),
            ('accountability_deadline', '60', 'refo')
        ");
    },
];
