<?php
/**
 * Titik masuk tunggal untuk memuat konfigurasi, database, dan helper.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/helpers.php';

db(); // pastikan database & data awal siap
