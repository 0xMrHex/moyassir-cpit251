<?php
/**
 * CPIT251: Hajj and Umrah Registration System
 * Database Configuration — Relational SQLite 3 via PHP PDO
 */

declare(strict_types=1);

return [
    'path'        => getenv('SQLITE_PATH') ?: dirname(__DIR__, 2) . '/data/hajj_umrah.sqlite',
    'schema_file' => dirname(__DIR__, 2) . '/sql/schema.sql',
    'seed_file'   => dirname(__DIR__, 2) . '/sql/seed.sql',
];
