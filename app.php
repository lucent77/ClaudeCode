#!/usr/bin/env php
<?php
/**
 * Creodent Anonymous Voice - CLI Entry Point
 *
 * Usage:
 *   php app.php migrate        - Run database migrations
 *   php app.php migrate:fresh  - Drop all tables and re-migrate
 *   php app.php seed           - Seed the database with initial data
 *   php app.php digest:weekly  - Generate weekly digest
 */

declare(strict_types=1);

// Define base path
define('BASE_PATH', __DIR__);

// Load autoloader
require BASE_PATH . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->safeLoad();

// Get command
$command = $argv[1] ?? 'help';

// Database connection
function getDb(): ?PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $name = $_ENV['DB_NAME'] ?? 'creodent_voice';
        $user = $_ENV['DB_USER'] ?? 'root';
        $pass = $_ENV['DB_PASS'] ?? '';

        try {
            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$name}`");
        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    return $pdo;
}

// Output helpers
function info(string $message): void
{
    echo "\033[32m[INFO]\033[0m {$message}\n";
}

function error(string $message): void
{
    echo "\033[31m[ERROR]\033[0m {$message}\n";
}

function warning(string $message): void
{
    echo "\033[33m[WARNING]\033[0m {$message}\n";
}

// Commands
switch ($command) {
    case 'migrate':
        runMigrations();
        break;

    case 'migrate:fresh':
        dropAllTables();
        runMigrations();
        break;

    case 'seed':
        runSeeder();
        break;

    case 'digest:weekly':
        generateWeeklyDigest();
        break;

    case 'help':
    default:
        showHelp();
        break;
}

function showHelp(): void
{
    echo <<<HELP
Creodent Anonymous Voice CLI

Usage:
  php app.php <command>

Commands:
  migrate         Run database migrations
  migrate:fresh   Drop all tables and re-migrate
  seed            Seed the database with initial data
  digest:weekly   Generate weekly digest
  help            Show this help message

HELP;
}

function dropAllTables(): void
{
    $db = getDb();

    info("Dropping all tables...");

    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $db->exec("DROP TABLE IF EXISTS `{$table}`");
        info("Dropped table: {$table}");
    }

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    info("All tables dropped.");
}

function runMigrations(): void
{
    $db = getDb();

    info("Running migrations...");

    // Create migrations tracking table
    $db->exec("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration VARCHAR(255) NOT NULL,
            batch INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Get already run migrations
    $stmt = $db->query("SELECT migration FROM migrations");
    $ran = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Migration files
    $migrationsPath = BASE_PATH . '/database/migrations';

    if (!is_dir($migrationsPath)) {
        mkdir($migrationsPath, 0755, true);
    }

    // Create the main schema migration if it doesn't exist
    $schemaFile = $migrationsPath . '/001_create_schema.php';
    if (!file_exists($schemaFile)) {
        createSchemaMigration($schemaFile);
    }

    // Get all migration files
    $files = glob($migrationsPath . '/*.php');
    sort($files);

    $batch = (int) ($db->query("SELECT MAX(batch) FROM migrations")->fetchColumn() ?? 0) + 1;

    foreach ($files as $file) {
        $migration = basename($file);

        if (in_array($migration, $ran, true)) {
            continue;
        }

        info("Migrating: {$migration}");

        $sql = require $file;

        if (is_string($sql)) {
            $db->exec($sql);
        } elseif (is_callable($sql)) {
            $sql($db);
        }

        $stmt = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$migration, $batch]);

        info("Migrated: {$migration}");
    }

    info("Migrations complete.");
}

function createSchemaMigration(string $file): void
{
    $sql = <<<'SQL'
<?php
/**
 * Initial Schema Migration
 */

return <<<'MIGRATION'
-- Users table
CREATE TABLE IF NOT EXISTS users (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('moderator','owner','exec','viewer') NOT NULL DEFAULT 'viewer',
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL,
  INDEX idx_users_email (email),
  INDEX idx_users_role (role)
);

-- Posts table
CREATE TABLE IF NOT EXISTS posts (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  public_id CHAR(26) NOT NULL UNIQUE,
  category ENUM('process','tools','communication','leadership','benefits','other') NOT NULL,
  title VARCHAR(160) NOT NULL,
  body TEXT NOT NULL,
  status ENUM('queued','open','in_progress','resolved','rejected','duplicate') NOT NULL DEFAULT 'queued',
  visibility ENUM('public','internal') NOT NULL DEFAULT 'public',
  department_hint VARCHAR(100) NULL,
  label ENUM('urgent','compliance','safety','culture') NULL,
  owner_id BIGINT NULL,
  merged_into_post_id BIGINT NULL,
  due_date DATE NULL,
  created_ip VARBINARY(16) NULL,
  created_ua VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_posts_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_posts_merge FOREIGN KEY (merged_into_post_id) REFERENCES posts(id) ON DELETE SET NULL,
  INDEX idx_posts_status (status),
  INDEX idx_posts_category (category),
  INDEX idx_posts_owner (owner_id),
  INDEX idx_posts_created (created_at)
);

-- Post votes table
CREATE TABLE IF NOT EXISTS post_votes (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  post_id BIGINT NOT NULL,
  vote_key CHAR(64) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_vote (post_id, vote_key),
  CONSTRAINT fk_votes_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  INDEX idx_votes_post (post_id)
);

-- Comments table
CREATE TABLE IF NOT EXISTS comments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  post_id BIGINT NOT NULL,
  user_id BIGINT NULL,
  is_official BOOLEAN NOT NULL DEFAULT 0,
  body TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_comments_post (post_id)
);

-- Attachments table
CREATE TABLE IF NOT EXISTS attachments (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  post_id BIGINT NOT NULL,
  filename VARCHAR(255) NOT NULL,
  path VARCHAR(255) NOT NULL,
  mime VARCHAR(100) NOT NULL,
  size INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_attachments_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  INDEX idx_attachments_post (post_id)
);

-- Moderation audit table
CREATE TABLE IF NOT EXISTS moderation_audit (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  post_id BIGINT NOT NULL,
  moderator_id BIGINT NOT NULL,
  action ENUM('approve','reject','ask_revision','merge','unmerge','redact','change_status','assign_owner') NOT NULL,
  notes TEXT NULL,
  previous_status VARCHAR(50) NULL,
  new_status VARCHAR(50) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  CONSTRAINT fk_audit_user FOREIGN KEY (moderator_id) REFERENCES users(id),
  INDEX idx_audit_post (post_id),
  INDEX idx_audit_created (created_at)
);

-- Digests table
CREATE TABLE IF NOT EXISTS digests (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  week_start DATE NOT NULL,
  week_end DATE NOT NULL,
  summary MEDIUMTEXT NOT NULL,
  public_url VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_digests_week (week_start, week_end)
);

-- Content flags table
CREATE TABLE IF NOT EXISTS content_flags (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  post_id BIGINT NOT NULL,
  rule_code VARCHAR(50) NOT NULL,
  matched_snippet VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_flags_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
  INDEX idx_flags_post (post_id)
);

-- Rate limiting table
CREATE TABLE IF NOT EXISTS rate_limits (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  ip_hash CHAR(64) NOT NULL,
  action VARCHAR(50) NOT NULL,
  attempts INT NOT NULL DEFAULT 1,
  window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_rate (ip_hash, action),
  INDEX idx_rate_window (window_start)
);
MIGRATION;
SQL;

    file_put_contents($file, $sql);
    info("Created schema migration file");
}

function runSeeder(): void
{
    $db = getDb();

    info("Running seeders...");

    // Check if admin user exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute(['admin@creodent.com']);

    if ($stmt->fetch()) {
        warning("Admin user already exists, skipping...");
    } else {
        // Create admin user
        $stmt = $db->prepare("
            INSERT INTO users (name, email, password_hash, role, status)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            'Admin',
            'admin@creodent.com',
            password_hash('ChangeMe!2025', PASSWORD_DEFAULT),
            'moderator',
            'active'
        ]);
        info("Created admin user: admin@creodent.com (password: ChangeMe!2025)");
    }

    info("Seeding complete.");
}

function generateWeeklyDigest(): void
{
    $db = getDb();

    info("Generating weekly digest...");

    // Calculate week range (last 7 days)
    $weekEnd = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('-7 days'));

    // Get statistics
    $stats = [];

    // New posts this week
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM posts
        WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
        AND status NOT IN ('queued', 'rejected')
    ");
    $stmt->execute([$weekStart, $weekEnd]);
    $stats['new_posts'] = $stmt->fetchColumn();

    // Resolved this week
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM posts
        WHERE status = 'resolved'
        AND updated_at >= ? AND updated_at < DATE_ADD(?, INTERVAL 1 DAY)
    ");
    $stmt->execute([$weekStart, $weekEnd]);
    $stats['resolved'] = $stmt->fetchColumn();

    // Top categories
    $stmt = $db->prepare("
        SELECT category, COUNT(*) as count FROM posts
        WHERE created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
        AND status NOT IN ('queued', 'rejected')
        GROUP BY category
        ORDER BY count DESC
        LIMIT 5
    ");
    $stmt->execute([$weekStart, $weekEnd]);
    $stats['top_categories'] = $stmt->fetchAll();

    // Top voted posts
    $stmt = $db->prepare("
        SELECT p.public_id, p.title, p.status, COUNT(v.id) as votes
        FROM posts p
        LEFT JOIN post_votes v ON p.id = v.post_id
        WHERE p.status NOT IN ('queued', 'rejected')
        GROUP BY p.id
        ORDER BY votes DESC
        LIMIT 5
    ");
    $stmt->execute();
    $stats['top_posts'] = $stmt->fetchAll();

    // Generate summary
    $summary = "# Weekly Digest ({$weekStart} to {$weekEnd})\n\n";
    $summary .= "## Overview\n";
    $summary .= "- New feedback received: {$stats['new_posts']}\n";
    $summary .= "- Issues resolved: {$stats['resolved']}\n\n";

    if (!empty($stats['top_categories'])) {
        $summary .= "## Top Categories\n";
        foreach ($stats['top_categories'] as $cat) {
            $summary .= "- " . ucfirst($cat['category']) . ": {$cat['count']} posts\n";
        }
        $summary .= "\n";
    }

    if (!empty($stats['top_posts'])) {
        $summary .= "## Most Supported Feedback\n";
        foreach ($stats['top_posts'] as $post) {
            $summary .= "- [{$post['title']}](/posts/{$post['public_id']}) - {$post['votes']} votes ({$post['status']})\n";
        }
    }

    // Save digest
    $stmt = $db->prepare("
        INSERT INTO digests (week_start, week_end, summary)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$weekStart, $weekEnd, $summary]);

    $digestId = $db->lastInsertId();

    info("Digest generated with ID: {$digestId}");
    info("Summary:\n{$summary}");
}
