<?php
declare(strict_types=1);

/**
 * Application Version Information
 * 
 * This file defines the current version of the Tierphysio Manager application
 * Used by the installer/updater to determine if database migrations are needed
 */

// Current application version
define('APP_VERSION', '1.3.0');

// Version history
define('VERSION_HISTORY', [
    '1.3.0' => [
        'date' => '2025-01-06',
        'changes' => 'Behandlungspläne, Übungsbibliothek, Fortschrittsverfolgung für Patienten',
        'type' => 'feature'
    ],
    '1.2.0' => [
        'date' => '2025-01-06',
        'changes' => 'KPI Dashboard 2.0 mit Live-Statistiken, Chart.js Integration, Update-System mit Migrationserkennung',
        'type' => 'feature'
    ],
    '1.1.0' => [
        'date' => '2024-12-15',
        'changes' => 'Intelligentes Update-System mit automatischer Migrationsverwaltung',
        'type' => 'feature'
    ],
    '1.0.1' => [
        'date' => '2024-11-30',
        'changes' => 'Bugfixes für Admin-Login und Session-Management',
        'type' => 'bugfix'
    ],
    '1.0.0' => [
        'date' => '2024-11-01',
        'changes' => 'Initiale Veröffentlichung mit Grundfunktionen',
        'type' => 'release'
    ]
]);

/**
 * Get formatted changelog for display
 * 
 * @return array
 */
function getChangelog(): array {
    $changelog = [];
    foreach (VERSION_HISTORY as $version => $info) {
        $changelog[] = [
            'version' => $version,
            'date' => $info['date'],
            'changes' => $info['changes'],
            'type' => $info['type'],
            'icon' => match($info['type']) {
                'release' => '🚀',
                'feature' => '✨',
                'bugfix' => '🐛',
                'security' => '🔒',
                default => '📝'
            }
        ];
    }
    return $changelog;
}

/**
 * Compare two version strings
 * 
 * @param string $version1
 * @param string $version2
 * @return int Returns -1 if version1 < version2, 0 if equal, 1 if version1 > version2
 */
function compareVersions(string $version1, string $version2): int {
    return version_compare($version1, $version2);
}

/**
 * Check if update is available
 * 
 * @param string $currentDbVersion
 * @return bool
 */
function isUpdateAvailable(string $currentDbVersion): bool {
    return compareVersions(APP_VERSION, $currentDbVersion) > 0;
}