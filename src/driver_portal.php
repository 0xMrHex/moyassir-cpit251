<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
requireDriver();

$db             = Database::getInstance();
$driverModel    = new Driver($db);
$itineraryModel = new Itinerary($db);
$reportModel    = new ReportGenerator($db);

$driver        = $driverModel->findByUserId((int) $_SESSION['user_id']);
$assignedBuses = $driver ? $driverModel->getAssignedBuses((int) $driver['driver_id']) : [];

$pageTitle = 'Driver Schedule Portal';
$activeNav = 'driver_portal';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!$driver): ?>
    <div class="alert alert-error">Your driver profile could not be found. Please contact Campaign Administration.</div>
<?php else:
    $driverOos = ($driver['status'] ?? 'active') === 'out_of_service';
?>
    <div class="portal-hero">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">
                Campaign Bus Driver Portal (Read-Only)
            </div>
            <span class="badge <?= $driverOos ? 'badge-full' : 'badge-ok' ?>">
                <?= $driverOos ? 'DRIVER STATUS: OUT OF SERVICE' : 'DRIVER STATUS: ACTIVE' ?>
            </span>
        </div>
        <h1><?= e($driver['full_name']) ?></h1>
        <div class="portal-meta">
            <span>License #: <strong><?= e($driver['license_number']) ?></strong></span>
            <span>Mobile: <strong><?= e($driver['phone']) ?></strong></span>
            <span>Username: <strong><?= e($driver['username']) ?></strong></span>
        </div>
    </div>

    <?php if ($driverOos): ?>
        <div class="alert alert-error" role="alert">
            <strong>Notice:</strong> Your driver status is currently marked as <strong>Out of Service</strong> by Campaign Administration. Please contact dispatch before operating any vehicle.
        </div>
    <?php endif; ?>

    <?php if (empty($assignedBuses)): ?>
        <div class="card">
            <h2>Assigned Bus &amp; Schedule</h2>
            <div class="alert alert-info" style="margin:0;">
                You do not currently have a bus assigned. Once Campaign Administration assigns you to a bus, your vehicle details, departure schedule, and passenger manifest will appear here automatically.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($assignedBuses as $bus):
            $busOos      = ($bus['status'] ?? 'active') === 'out_of_service';
            $itineraries = $itineraryModel->getByBusId((int) $bus['bus_id']);
            $manifest    = $reportModel->getBusManifest((int) $bus['bus_id']);
            $passengers  = $manifest['passengers'] ?? [];
            $assigned    = (int) $bus['assigned_count'];
            $cap         = max(1, (int) $bus['max_capacity']);
            $pct         = min(100, (int) round(($assigned / $cap) * 100));
        ?>
            <?php if ($busOos): ?>
                <div class="alert alert-error" role="alert">
                    <strong>Vehicle Alert:</strong> Your assigned bus <strong><?= e($bus['bus_number']) ?></strong> is currently marked <strong>OUT OF SERVICE</strong>. Do not dispatch until cleared by Administration.
                </div>
            <?php endif; ?>

            <div class="grid-2">
                <div class="card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <h2 style="margin:0;">Assigned Bus Details</h2>
                        <span class="badge <?= $busOos ? 'badge-full' : 'badge-ok' ?>">
                            <?= $busOos ? 'OUT OF SERVICE' : 'ACTIVE' ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Bus Number</span>
                        <span class="detail-val" style="font-size:20px;color:var(--primary);"><?= e($bus['bus_number']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Scheduled Departure</span>
                        <span class="detail-val"><?= e($bus['departure_time']) ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-key">Passenger Load</span>
                        <span class="detail-val"><?= $assigned ?> / <?= $cap ?> seats</span>
                    </div>
                    <div class="progress-bar" style="margin-top:10px;">
                        <div class="progress-fill <?= $busOos || $assigned >= $cap ? 'full' : '' ?>" style="width: <?= $pct ?>%;"></div>
                    </div>
                </div>

                <div class="card">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                        <h2 style="margin:0;">Assigned Bus Schedule</h2>
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline no-print">Print Schedule</button>
                    </div>
                    <?php if (empty($itineraries)): ?>
                        <p style="color:var(--muted);margin:0;">No schedule milestones published for <?= e($bus['bus_number']) ?> yet.</p>
                    <?php else: ?>
                        <ul class="timeline">
                            <?php foreach ($itineraries as $item): ?>
                                <li>
                                    <div class="timeline-date"><?= e($item['event_date']) ?></div>
                                    <div class="timeline-title"><?= e($item['title']) ?></div>
                                    <p class="timeline-desc"><?= e($item['description']) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2>Passenger Manifest — <?= e($bus['bus_number']) ?> (<?= count($passengers) ?> Pilgrims)</h2>
                <div class="table-wrap">
                    <table class="resp-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Passenger Name</th>
                                <th>National ID</th>
                                <th>Phone</th>
                                <th>Tent / Zone</th>
                                <th>Boarding</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($passengers)): ?>
                                <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:18px;">No passengers currently assigned to this bus.</td></tr>
                            <?php else: ?>
                                <?php foreach ($passengers as $idx => $p): ?>
                                <tr>
                                    <td data-label="Seat #">#<?= $idx + 1 ?></td>
                                    <td data-label="Passenger" class="cell-primary"><strong><?= e($p['full_name']) ?></strong></td>
                                    <td data-label="National ID"><code><?= e($p['national_id']) ?></code></td>
                                    <td data-label="Mobile"><a href="tel:<?= e($p['phone']) ?>"><?= e($p['phone']) ?></a></td>
                                    <td data-label="Tent"><strong><?= e($p['tent_number'] ?? 'Unassigned') ?></strong></td>
                                    <td data-label="Boarding" style="font-family:monospace;">[ &nbsp; ] On Board</td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
