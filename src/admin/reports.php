<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db          = Database::getInstance();
$busModel    = new Bus($db);
$tentModel   = new Tent($db);
$reportModel = new ReportGenerator($db);

$buses = $busModel->getAllWithOccupancy();
$tents = $tentModel->getAllWithOccupancy();

$type = ($_GET['type'] ?? 'bus') === 'tent' ? 'tent' : 'bus';
$id   = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $id = $type === 'bus'
        ? (int) ($buses[0]['bus_id'] ?? 0)
        : (int) ($tents[0]['tent_id'] ?? 0);
}

$manifest = $type === 'bus' && $id > 0 ? $reportModel->getBusManifest($id) : null;
$roster   = $type === 'tent' && $id > 0 ? $reportModel->getTentRoster($id) : null;

// Handle CSV Export
if (($_GET['export'] ?? '') === 'csv') {
    if ($type === 'bus' && $manifest) {
        $rows = [];
        foreach ($manifest['passengers'] as $idx => $p) {
            $rows[] = [$idx + 1, $p['full_name'], $p['national_id'], $p['phone'], $p['campaign_type'], $p['tent_number'] ?? 'Unassigned'];
        }
        $csv = $reportModel->formatCsv(['#', 'Full Name', 'National ID', 'Phone', 'Campaign', 'Assigned Tent'], $rows);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="manifest_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $manifest['bus']['bus_number']) . '.csv"');
        echo $csv;
        exit;
    }
    if ($type === 'tent' && $roster) {
        $rows = [];
        foreach ($roster['occupants'] as $idx => $o) {
            $rows[] = [$idx + 1, $o['full_name'], $o['national_id'], $o['phone'], $o['campaign_type'], $o['bus_number'] ?? 'Unassigned'];
        }
        $csv = $reportModel->formatCsv(['#', 'Full Name', 'National ID', 'Phone', 'Campaign', 'Assigned Bus'], $rows);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="roster_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $roster['tent']['tent_number']) . '.csv"');
        echo $csv;
        exit;
    }
}

$pageTitle = 'Printable Manifests & Rosters';
$activeNav = 'reports';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header no-print">
    <div>
        <h1>Bus Manifests &amp; Tent Rosters</h1>
        <p class="page-subtitle">Generate printable passenger lists and CSV exports for campaign ground staff.</p>
    </div>
    <div class="actions-inline">
        <button type="button" onclick="window.print()" class="btn">Print / Save as PDF</button>
        <a href="/admin/reports.php?type=<?= e($type) ?>&id=<?= $id ?>&export=csv" class="btn btn-sec">Export CSV</a>
    </div>
</div>

<div class="card no-print">
    <form method="GET" action="/admin/reports.php" class="filter-bar" style="margin-bottom:0;">
        <div class="form-group">
            <label for="type">Report Type</label>
            <select id="type" name="type" onchange="this.form.submit()">
                <option value="bus" <?= $type === 'bus' ? 'selected' : '' ?>>Bus Passenger Manifest</option>
                <option value="tent" <?= $type === 'tent' ? 'selected' : '' ?>>Tent Occupancy Roster</option>
            </select>
        </div>

        <div class="form-group" style="flex:2;">
            <label for="id">Select <?= $type === 'bus' ? 'Bus' : 'Tent' ?></label>
            <select id="id" name="id" onchange="this.form.submit()">
                <?php if ($type === 'bus'): ?>
                    <?php foreach ($buses as $b): ?>
                        <option value="<?= (int) $b['bus_id'] ?>" <?= $id === (int) $b['bus_id'] ? 'selected' : '' ?>>
                            <?= e($b['bus_number']) ?> &mdash; <?= e($b['driver_name']) ?> (<?= (int) $b['assigned_count'] ?>/<?= (int) $b['max_capacity'] ?>)
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($tents as $t): ?>
                        <option value="<?= (int) $t['tent_id'] ?>" <?= $id === (int) $t['tent_id'] ? 'selected' : '' ?>>
                            <?= e($t['tent_number']) ?> &mdash; <?= e($t['location_zone']) ?> (<?= (int) $t['assigned_count'] ?>/<?= (int) $t['max_capacity'] ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <button type="submit" class="btn">Generate Report</button>
    </form>
</div>

<?php if ($type === 'bus' && $manifest): ?>
<div class="card">
    <div class="manifest-header">
        <div>
            <div style="font-size:12px;text-transform:uppercase;color:var(--muted);font-weight:700;">Official Campaign Transport Manifest</div>
            <h2 style="margin:4px 0;">
                Bus <?= e($manifest['bus']['bus_number']) ?> Passenger List
                <?php if (($manifest['bus']['status'] ?? 'active') === 'out_of_service'): ?>
                    <span class="badge badge-full">OUT OF SERVICE</span>
                <?php endif; ?>
            </h2>
            <div style="font-size:13px;">
                <strong>Driver:</strong> <?= e($manifest['bus']['driver_name']) ?> (<?= e($manifest['bus']['driver_phone']) ?>) &bull;
                <strong>Departure:</strong> <?= e($manifest['bus']['departure_time']) ?>
            </div>
        </div>
        <div style="text-align:right;font-size:13px;">
            <div><strong>Passenger Load:</strong> <?= count($manifest['passengers']) ?> / <?= (int) $manifest['bus']['max_capacity'] ?></div>
            <div><strong>Generated:</strong> <?= date('Y-m-d H:i') ?></div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="resp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>National ID</th>
                    <th>Mobile Phone</th>
                    <th>Campaign</th>
                    <th>Assigned Tent</th>
                    <th>Boarding Check</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($manifest['passengers'])): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No passengers currently assigned to this bus.</td></tr>
                <?php else: ?>
                    <?php foreach ($manifest['passengers'] as $idx => $p): ?>
                    <tr>
                        <td data-label="Seat #">#<?= $idx + 1 ?></td>
                        <td data-label="Passenger" class="cell-primary"><strong><?= e($p['full_name']) ?></strong></td>
                        <td data-label="National ID"><code><?= e($p['national_id']) ?></code></td>
                        <td data-label="Mobile"><a href="tel:<?= e($p['phone']) ?>"><?= e($p['phone']) ?></a></td>
                        <td data-label="Campaign"><?= e($p['campaign_type']) ?></td>
                        <td data-label="Assigned Tent"><strong><?= e($p['tent_number'] ?? 'Unassigned') ?></strong></td>
                        <td data-label="Boarding Check" style="font-family:monospace;">[ &nbsp; ] Boarded</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($type === 'tent' && $roster): ?>
<div class="card">
    <div class="manifest-header">
        <div>
            <div style="font-size:12px;text-transform:uppercase;color:var(--muted);font-weight:700;">Official Camp Accommodation Roster</div>
            <h2 style="margin:4px 0;">Tent <?= e($roster['tent']['tent_number']) ?> Occupant List</h2>
            <div style="font-size:13px;"><strong>Location Zone:</strong> <?= e($roster['tent']['location_zone']) ?></div>
        </div>
        <div style="text-align:right;font-size:13px;">
            <div><strong>Occupancy:</strong> <?= count($roster['occupants']) ?> / <?= (int) $roster['tent']['max_capacity'] ?></div>
            <div><strong>Generated:</strong> <?= date('Y-m-d H:i') ?></div>
        </div>
    </div>

    <div class="table-wrap">
        <table class="resp-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Full Name</th>
                    <th>National ID</th>
                    <th>Mobile Phone</th>
                    <th>Campaign</th>
                    <th>Assigned Bus</th>
                    <th>Check-In</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($roster['occupants'])): ?>
                    <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px;">No occupants currently assigned to this tent.</td></tr>
                <?php else: ?>
                    <?php foreach ($roster['occupants'] as $idx => $o): ?>
                    <tr>
                        <td data-label="Space #">#<?= $idx + 1 ?></td>
                        <td data-label="Occupant" class="cell-primary"><strong><?= e($o['full_name']) ?></strong></td>
                        <td data-label="National ID"><code><?= e($o['national_id']) ?></code></td>
                        <td data-label="Mobile"><a href="tel:<?= e($o['phone']) ?>"><?= e($o['phone']) ?></a></td>
                        <td data-label="Campaign"><?= e($o['campaign_type']) ?></td>
                        <td data-label="Assigned Bus"><strong><?= e($o['bus_number'] ?? 'Unassigned') ?></strong></td>
                        <td data-label="Check-In" style="font-family:monospace;">[ &nbsp; ] Checked In</td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
