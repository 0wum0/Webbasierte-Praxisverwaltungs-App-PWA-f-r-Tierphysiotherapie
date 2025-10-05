<?php
declare(strict_types=1);

// ================== BOOTSTRAP ==================
$DEBUG = isset($_GET['debug']); // ?debug=1 zeigt SQL-Fehler an
if ($DEBUG) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/../includes/db.php';

// Wir liefern HTML für das Modal zurück
header('Content-Type: text/html; charset=utf-8');

// ================== HELPERS ==================
function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Führt eine SELECT-Query aus und gibt [] bei Fehlern zurück.
 * Wenn $onError nicht null ist, wird dieser Text als Alert im Output gepuffert.
 */
function tryFetchAll(PDO $pdo, string $sql, array $params = [], ?string $onError = null, bool $debug = false): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        if ($debug) {
            echo '<div class="alert alert-danger mb-2"><strong>SQL-Fehler:</strong> '.h($e->getMessage()).'</div>';
        } elseif ($onError) {
            echo '<div class="alert alert-warning mb-2">'.h($onError).'</div>';
        }
        return [];
    }
}

function tryFetchOne(PDO $pdo, string $sql, array $params = [], ?string $onError = null, bool $debug = false): ?array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        if ($debug) {
            echo '<div class="alert alert-danger mb-2"><strong>SQL-Fehler:</strong> '.h($e->getMessage()).'</div>';
        } elseif ($onError) {
            echo '<div class="alert alert-warning mb-2">'.h($onError).'</div>';
        }
        return null;
    }
}

/** Fallback falls mbstring fehlt */
function str_limit(?string $txt, int $len, string $suffix = '…'): string {
    $txt = $txt ?? '';
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($txt, 0, $len, $suffix, 'UTF-8');
    }
    return (strlen($txt) > $len) ? substr($txt, 0, $len) . $suffix : $txt;
}

/** Fügt style für Scrollbar ein, wenn Anzahl > $limit ist */
function scrollStyle(array $arr, int $limit = 5, int $maxHeight = 220): string {
    return (count($arr) > $limit) ? 'max-height: '.$maxHeight.'px; overflow-y: auto;' : '';
}

// ================== INPUT VALIDATION ==================
$type = $_GET['type'] ?? '';
$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!in_array($type, ['patient', 'owner'], true) || $id <= 0) {
    echo '<div class="alert alert-danger mb-0">Ungültige Anfrage.</div>';
    exit;
}

// ================== RENDER PATIENT ==================
if ($type === 'patient') {
    // Patient inkl. Besitzer
    $patient = tryFetchOne(
        $pdo,
        "SELECT p.*, o.firstname, o.lastname, o.email
         FROM patients p
         JOIN owners o ON p.owner_id = o.id
         WHERE p.id = :id",
        [':id' => $id],
        'Patient konnte nicht geladen werden.',
        $DEBUG
    );

    if (!$patient) {
        echo '<div class="alert alert-warning mb-0">Patient nicht gefunden.</div>';
        exit;
    }

    // Termine
    $appts = tryFetchAll(
        $pdo,
        "SELECT a.* 
           FROM appointments a 
          WHERE a.patient_id = :id 
       ORDER BY a.appointment_date DESC 
          LIMIT 50",
        [':id' => $id],
        'Termine konnten nicht geladen werden.',
        $DEBUG
    );

    // Behandlungen (Tabelle kann fehlen -> Fehler wird abgefangen)
    $treatments = tryFetchAll(
        $pdo,
        "SELECT t.* 
           FROM treatments t 
          WHERE t.patient_id = :id 
       ORDER BY t.date DESC 
          LIMIT 50",
        [':id' => $id],
        'Behandlungen konnten nicht geladen werden (Tabelle vorhanden?).',
        $DEBUG
    );

    // Notizen (Tabelle kann fehlen -> Fehler wird abgefangen)
    $notes = tryFetchAll(
        $pdo,
        "SELECT n.* 
           FROM notes n 
          WHERE n.patient_id = :id 
       ORDER BY n.created_at DESC 
          LIMIT 50",
        [':id' => $id],
        'Notizen konnten nicht geladen werden (Tabelle vorhanden?).',
        $DEBUG
    );

    // Rechnungen
    $invoices = tryFetchAll(
        $pdo,
        "SELECT i.* 
           FROM invoices i 
          WHERE i.patient_id = :id 
       ORDER BY i.last_updated DESC 
          LIMIT 50",
        [':id' => $id],
        'Rechnungen konnten nicht geladen werden.',
        $DEBUG
    );

    ?>
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h4 class="mb-0">🐾 Patient: <?= h($patient['name'] ?? '') ?>
                <?php if (!empty($patient['species'])): ?>
                    <small class="text-muted ms-1">
                        (<?= h($patient['species']) ?><?= !empty($patient['breed']) ? ' · '.h($patient['breed']) : '' ?>)
                    </small>
                <?php endif; ?>
            </h4>
            <div class="d-flex gap-2">
                <a href="patient.php?id=<?= $id ?>" class="btn btn-sm btn-secondary">Akte öffnen</a>
                <a href="edit_patient.php?id=<?= $id ?>" class="btn btn-sm btn-warning">Bearbeiten</a>
                <a href="invoices.php?patient_id=<?= $id ?>" class="btn btn-sm btn-primary">Rechnungen</a>
            </div>
        </div>
        <p class="text-muted mb-2">
            Besitzer: <strong><?= h(($patient['firstname'] ?? '').' '.($patient['lastname'] ?? '')) ?></strong>
            <?= !empty($patient['email']) ? ' · '.h($patient['email']) : '' ?>
        </p>
        <hr class="border-secondary"/>

        <div class="row g-3">
            <!-- Termine -->
            <div class="col-12 col-lg-6">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-header border-secondary d-flex justify-content-between">
                        <strong>Termine</strong>
                        <a class="btn btn-sm btn-outline-info" href="appointments.php?patient_id=<?= $id ?>">Alle</a>
                    </div>
                    <div class="card-body" style="<?= scrollStyle($appts, 5, 220) ?>">
                        <?php if (!$appts): ?>
                            <p class="text-muted mb-0">Keine Termine vorhanden.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($appts as $a): ?>
                                    <li class="list-group-item bg-dark text-light d-flex justify-content-between">
                                        <span><?= h(date('d.m.Y H:i', strtotime($a['appointment_date']))) ?></span>
                                        <span class="text-muted"><?= h($a['notes'] ?? '-') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="mt-3">
                            <a class="btn btn-sm btn-outline-light" href="appointments.php?new=1&patient_id=<?= $id ?>">Neuen Termin anlegen</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Behandlungen -->
            <div class="col-12 col-lg-6">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-header border-secondary d-flex justify-content-between">
                        <strong>Behandlungen</strong>
                        <a class="btn btn-sm btn-outline-info" href="treatments.php?patient_id=<?= $id ?>">Alle</a>
                    </div>
                    <div class="card-body" style="<?= scrollStyle($treatments, 5, 220) ?>">
                        <?php if (!$treatments): ?>
                            <p class="text-muted mb-0">Keine Behandlungen vorhanden.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($treatments as $t): ?>
                                    <li class="list-group-item bg-dark text-light">
                                        <?= h(date('d.m.Y', strtotime($t['date'] ?? $t['created_at'] ?? 'now'))) ?>
                                        – <?= h(str_limit($t['description'] ?? $t['notes'] ?? '-', 60)) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="mt-3">
                            <a class="btn btn-sm btn-outline-light" href="new_treatment.php?patient_id=<?= $id ?>">Neue Behandlung</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notizen -->
            <div class="col-12 col-lg-6">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-header border-secondary d-flex justify-content-between">
                        <strong>Notizen</strong>
                        <a class="btn btn-sm btn-outline-info" href="notes.php?patient_id=<?= $id ?>">Alle</a>
                    </div>
                    <div class="card-body" style="<?= scrollStyle($notes, 5, 220) ?>">
                        <?php if (!$notes): ?>
                            <p class="text-muted mb-0">Keine Notizen vorhanden.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($notes as $n): ?>
                                    <li class="list-group-item bg-dark text-light">
                                        <?= h(str_limit($n['content'] ?? $n['note'] ?? '-', 80)) ?>
                                        <div class="small text-muted">
                                            <?= h(date('d.m.Y', strtotime($n['created_at'] ?? $n['date'] ?? 'now'))) ?>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="mt-3">
                            <a class="btn btn-sm btn-outline-light" href="new_note.php?patient_id=<?= $id ?>">Neue Notiz</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rechnungen -->
            <div class="col-12 col-lg-6">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-header border-secondary d-flex justify-content-between">
                        <strong>Rechnungen</strong>
                        <a class="btn btn-sm btn-outline-info" href="invoices.php?patient_id=<?= $id ?>">Alle</a>
                    </div>
                    <div class="card-body" style="<?= scrollStyle($invoices, 5, 220) ?>">
                        <?php if (!$invoices): ?>
                            <p class="text-muted mb-0">Keine Rechnungen vorhanden.</p>
                        <?php else: ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($invoices as $inv): ?>
                                    <li class="list-group-item bg-dark text-light d-flex justify-content-between align-items-center">
                                        <span>#<?= (int)($inv['id'] ?? 0) ?> · <?= h($inv['status'] ?? '-') ?></span>
                                        <span>
                                            <?= number_format((float)($inv['amount'] ?? 0), 2, ',', '.') ?> €
                                            <?php if (!empty($inv['id'])): ?>
                                                <a class="btn btn-sm btn-outline-secondary ms-2" target="_blank" href="invoice_pdf.php?id=<?= (int)$inv['id'] ?>">PDF</a>
                                                <a class="btn btn-sm btn-outline-warning ms-1" href="edit_invoice.php?id=<?= (int)$inv['id'] ?>">Bearb.</a>
                                            <?php endif; ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="mt-3">
                            <a class="btn btn-sm btn-outline-light" href="new_invoice.php?patient_id=<?= $id ?>">Rechnung erstellen</a>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /row -->
    </div>
    <?php
    exit;
}

// ================== RENDER OWNER ==================
$owner = tryFetchOne(
    $pdo,
    "SELECT * FROM owners WHERE id = :id",
    [':id' => $id],
    'Besitzer konnte nicht geladen werden.',
    $DEBUG
);

if (!$owner) {
    echo '<div class="alert alert-warning mb-0">Besitzer nicht gefunden.</div>';
    exit;
}

$patients = tryFetchAll(
    $pdo,
    "SELECT * FROM patients WHERE owner_id = :id ORDER BY name ASC LIMIT 50",
    [':id' => $id],
    'Patienten konnten nicht geladen werden.',
    $DEBUG
);

$appts = tryFetchAll(
    $pdo,
    "SELECT a.* 
       FROM appointments a 
       JOIN patients p ON a.patient_id = p.id 
      WHERE p.owner_id = :id 
   ORDER BY a.appointment_date DESC 
      LIMIT 50",
    [':id' => $id],
    'Termine konnten nicht geladen werden.',
    $DEBUG
);

$invoices = tryFetchAll(
    $pdo,
    "SELECT i.* 
       FROM invoices i 
       JOIN patients p ON i.patient_id = p.id 
      WHERE p.owner_id = :id 
   ORDER BY i.last_updated DESC 
      LIMIT 50",
    [':id' => $id],
    'Rechnungen konnten nicht geladen werden.',
    $DEBUG
);
?>
<div class="container-fluid">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h4 class="mb-0">👤 Besitzer: <?= h(($owner['firstname'] ?? '').' '.($owner['lastname'] ?? '')) ?></h4>
        <div class="d-flex gap-2">
            <a href="owners.php" class="btn btn-sm btn-secondary">Besitzerliste</a>
            <a href="edit_owner.php?id=<?= $id ?>" class="btn btn-sm btn-warning">Bearbeiten</a>
        </div>
    </div>
    <p class="text-muted mb-2">
        <?= !empty($owner['email']) ? 'E-Mail: <strong>'.h($owner['email']).'</strong> · ' : '' ?>
        <?= !empty($owner['phone']) ? 'Telefon: <strong>'.h($owner['phone']).'</strong>' : '' ?>
    </p>
    <hr class="border-secondary"/>

    <div class="row g-3">
        <!-- Patienten -->
        <div class="col-12 col-lg-6">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header border-secondary"><strong>Patienten</strong></div>
                <div class="card-body" style="<?= scrollStyle($patients, 5, 220) ?>">
                    <?php if (!$patients): ?>
                        <p class="text-muted mb-0">Keine Patienten angelegt.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($patients as $p): ?>
                                <li class="list-group-item bg-dark text-light d-flex justify-content-between">
                                    <span><?= h($p['name'] ?? '') ?><?= !empty($p['species']) ? ' · '.h($p['species']) : '' ?></span>
                                    <span>
                                        <a class="btn btn-sm btn-outline-info" href="patient.php?id=<?= (int)$p['id'] ?>">Akte</a>
                                        <a class="btn btn-sm btn-outline-warning" href="edit_patient.php?id=<?= (int)$p['id'] ?>">Bearb.</a>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a class="btn btn-sm btn-outline-light" href="#" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#newOwnerPatientModal">Patient hinzufügen</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Termine -->
        <div class="col-12 col-lg-6">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header border-secondary"><strong>Termine</strong></div>
                <div class="card-body" style="<?= scrollStyle($appts, 5, 220) ?>">
                    <?php if (!$appts): ?>
                        <p class="text-muted mb-0">Keine Termine vorhanden.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($appts as $a): ?>
                                <li class="list-group-item bg-dark text-light d-flex justify-content-between">
                                    <span><?= h(date('d.m.Y H:i', strtotime($a['appointment_date']))) ?></span>
                                    <span class="text-muted"><?= h($a['notes'] ?? '-') ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a class="btn btn-sm btn-outline-light" href="appointments.php">Neuen Termin anlegen</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rechnungen -->
        <div class="col-12 col-lg-6">
            <div class="card bg-dark border-secondary h-100">
                <div class="card-header border-secondary"><strong>Rechnungen</strong></div>
                <div class="card-body" style="<?= scrollStyle($invoices, 5, 220) ?>">
                    <?php if (!$invoices): ?>
                        <p class="text-muted mb-0">Keine Rechnungen vorhanden.</p>
                    <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($invoices as $inv): ?>
                                <li class="list-group-item bg-dark text-light d-flex justify-content-between">
                                    <span>#<?= (int)($inv['id'] ?? 0) ?> – <?= h($inv['status'] ?? '-') ?></span>
                                    <span><?= number_format((float)($inv['amount'] ?? 0), 2, ',', '.') ?> €</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a class="btn btn-sm btn-outline-light" href="invoices.php">Rechnung erstellen</a>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /row -->
</div>