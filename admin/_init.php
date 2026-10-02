<?php
/**
 * Inisialisasi halaman admin (wajib login).
 */

require_once dirname(__DIR__) . '/app/bootstrap.php';

$user = require_login();
