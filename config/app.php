<?php
/**
 * Configuration générale et fonctions utilitaires — MBOOLO
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

// Base URL dynamique
function baseUrl(string $path = ''): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = dirname($scriptName);
    $base = ($dir === '/' || $dir === '\\') ? '' : $dir;
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

// Protection CSRF
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Flash Messages
function setFlash(string $type, string $message): void {
    if (!isset($_SESSION['flash'])) {
        $_SESSION['flash'] = [];
    }
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// Échappement XSS
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Format monétaire
function formatMoney($amount, string $currency = 'FCFA'): string {
    return number_format((float)$amount, 0, ',', ' ') . ' ' . $currency;
}

// Format date française
function formatDate(?string $datetime, bool $withTime = false): string {
    if (!$datetime) return '-';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return '-';
    return date($withTime ? 'd/m/Y à H:i' : 'd/m/Y', $timestamp);
}
