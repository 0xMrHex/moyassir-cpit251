<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db          = Database::getInstance();
$driverModel = new Driver($db);
$busModel    = new Bus($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token mismatch. Please try again.');
        header('Location: /admin/drivers.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'add_driver') {
        $password = (string) ($_POST['password'] ?? 'Driver@2026');
        $res = $driverModel->createWithAccount($_POST, $password !== '' ? $password : 'Driver@2026');
        if ($res['ok']) {
            $uname = trim((string) ($_POST['username'] ?? ''));
            setFlash('success', "Driver added successfully! Driver Portal login username: {$uname}.");
        } else {
            setFlash('error', $res['error']);
        }
    } elseif ($action === 'assign_bus') {
        $driverId = (int) ($_POST['driver_id'] ?? 0);
        $busId    = isset($_POST['bus_id']) && $_POST['bus_id'] !== '' ? (int) $_POST['bus_id'] : null;
        $res = $driverModel->assignToBus($driverId, $busId);
        if ($res['ok']) {
            setFlash('success', $busId ? 'Driver assigned to bus successfully.' : 'Driver unassigned from bus.');
        } else {
            setFlash('error', $res['error']);
        }
    } elseif ($action === 'toggle_driver_status') {
        $driverId  = (int) ($_POST['driver_id'] ?? 0);
        $newStatus = (string) ($_POST['status'] ?? 'active');
        $res = $driverModel->setStatus($driverId, $newStatus);
        if ($res['ok']) {
            $label = $res['status'] === 'out_of_service' ? 'Out of Service' : 'Active';
            setFlash('success', "Driver status updated to {$label}.");
        } else {
            setFlash('error', $res['error']);
        }
    } elseif ($action === 'toggle_bus_status') {
        $busId     = (int) ($_POST['bus_id'] ?? 0);
        $newStatus = (string) ($_POST['status'] ?? 'active');
        $busModel->setStatus($busId, $newStatus);
        $label = $newStatus === 'out_of_service' ? 'Out of Service' : 'Active';
        setFlash('success', "Bus service status updated to {$label}.");
    } elseif ($action === 'delete_driver') {
        $driverId = (int) ($_POST['driver_id'] ?? 0);
        $existing = $driverModel->findById($driverId);
        if ($existing && $driverModel->delete($driverId)) {
            setFlash('success', "Removed driver '{$existing['full_name']}' and unlinked any assigned bus.");
        } else {
            setFlash('error', 'Unable to remove driver.');
        }
    }

    header('Location: /admin/drivers.php');
    exit;
}

$drivers = $driverModel->getAll();
$buses   = $busModel->getAllWithOccupancy();

$pageTitle = 'Bus Drivers Management';
$activeNav = 'drivers';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Bus Drivers Management</h1>
        <p class="page-subtitle">Add or remove drivers, assign drivers to specific buses, and toggle Active / Out of Service status.</p>
    </div>
    <a href="/admin/logistics.php" class="btn btn-outline">Manage Buses &amp; Itineraries</a>
</div>

<div class="card">
    <h2>Registered Bus Drivers (<?= count($drivers) ?>)</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Driver &amp; Login</th>
                    <th>License #</th>
                    <th>Phone</th>
                    <th>Driver Status</th>
                    <th>Assign to Bus</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($drivers)): ?>
                    <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px;">No drivers registered yet. Add one below.</td></tr>
                <?php else: ?>
                    <?php foreach ($drivers as $d):
                        $isOos = ($d['status'] ?? 'active') === 'out_of_service';
                    ?>
                    <tr>
                        <td>
                            <strong><?= e($d['full_name']) ?></strong><br>
                            <small style="color:var(--muted);">Login: <code><?= e($d['username']) ?></code></small>
                        </td>
                        <td><code><?= e($d['license_number']) ?></code></td>
                        <td><?= e($d['phone']) ?></td>
                        <td>
                            <span class="badge <?= $isOos ? 'badge-full' : 'badge-ok' ?>">
                                <?= $isOos ? 'OUT OF SERVICE' : 'ACTIVE' ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/admin/drivers.php" class="actions-inline">
                                <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                <input type="hidden" name="action" value="assign_bus">
                                <input type="hidden" name="driver_id" value="<?= (int) $d['driver_id'] ?>">
                                <select name="bus_id" style="width:auto;min-width:165px;padding:6px 8px;font-size:13px;">
                                    <option value="">-- Unassigned --</option>
                                    <?php foreach ($buses as $b): ?>
                                        <option value="<?= (int) $b['bus_id'] ?>" <?= (int) ($d['assigned_bus_id'] ?? 0) === (int) $b['bus_id'] ? 'selected' : '' ?>>
                                            <?= e($b['bus_number']) ?><?= ($b['status'] ?? 'active') === 'out_of_service' ? ' (Out of Service)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn btn-sm btn-sec">Assign</button>
                            </form>
                        </td>
                        <td>
                            <div class="actions-inline">
                                <form method="POST" action="/admin/drivers.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="toggle_driver_status">
                                    <input type="hidden" name="driver_id" value="<?= (int) $d['driver_id'] ?>">
                                    <input type="hidden" name="status" value="<?= $isOos ? 'active' : 'out_of_service' ?>">
                                    <button type="submit" class="btn btn-sm <?= $isOos ? '' : 'btn-outline' ?>">
                                        <?= $isOos ? 'Set Active' : 'Out of Service' ?>
                                    </button>
                                </form>
                                <form method="POST" action="/admin/drivers.php" onsubmit="return confirm('Remove driver <?= e($d['full_name']) ?> and delete their portal login?');">
                                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                    <input type="hidden" name="action" value="delete_driver">
                                    <input type="hidden" name="driver_id" value="<?= (int) $d['driver_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <h2>+ Add New Bus Driver</h2>
        <p style="font-size:13px;color:var(--muted);margin-top:-6px;">Automatically creates a Driver Portal login account so the driver can view their assigned bus &amp; schedule.</p>
        <form method="POST" action="/admin/drivers.php">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="add_driver">

            <div class="form-grid">
                <div class="form-group">
                    <label for="full_name">Driver Full Name *</label>
                    <input type="text" id="full_name" name="full_name" placeholder="e.g., Salem Al-Mutairi" required>
                </div>
                <div class="form-group">
                    <label for="username">Portal Login Username *</label>
                    <input type="text" id="username" name="username" placeholder="e.g., driver4" required>
                </div>
                <div class="form-group">
                    <label for="license_number">Driving License # *</label>
                    <input type="text" id="license_number" name="license_number" placeholder="e.g., DL-2026-305" required>
                </div>
                <div class="form-group">
                    <label for="phone">Mobile Number *</label>
                    <input type="text" id="phone" name="phone" placeholder="05XXXXXXXX" required>
                </div>
                <div class="form-group">
                    <label for="status">Initial Service Status *</label>
                    <select id="status" name="status">
                        <option value="active">Active (In Service)</option>
                        <option value="out_of_service">Out of Service</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="bus_id">Assign to Specific Bus (Optional)</label>
                    <select id="bus_id" name="bus_id">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($buses as $b): ?>
                            <option value="<?= (int) $b['bus_id'] ?>">
                                <?= e($b['bus_number']) ?> (Current: <?= e($b['driver_name']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Initial Driver Portal Password *</label>
                <input type="text" id="password" name="password" value="Driver@2026" required>
            </div>

            <button type="submit" class="btn">Add Driver &amp; Create Login</button>
        </form>
    </div>

    <div class="card">
        <h2>Bus Fleet Service Status Control</h2>
        <p style="font-size:13px;color:var(--muted);margin-top:-6px;">Mark any bus as Out of Service for maintenance or return it to Active duty. Out-of-service buses block new passenger allocations and alert drivers/attendees.</p>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Bus</th>
                        <th>Assigned Driver</th>
                        <th>Bus Status</th>
                        <th>Toggle</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($buses as $b):
                        $busOos = ($b['status'] ?? 'active') === 'out_of_service';
                    ?>
                    <tr>
                        <td><strong><?= e($b['bus_number']) ?></strong><br><small style="color:var(--muted);"><?= (int) $b['assigned_count'] ?>/<?= (int) $b['max_capacity'] ?> seats</small></td>
                        <td><?= e($b['driver_name']) ?></td>
                        <td>
                            <span class="badge <?= $busOos ? 'badge-full' : 'badge-ok' ?>">
                                <?= $busOos ? 'OUT OF SERVICE' : 'ACTIVE' ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" action="/admin/drivers.php">
                                <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                <input type="hidden" name="action" value="toggle_bus_status">
                                <input type="hidden" name="bus_id" value="<?= (int) $b['bus_id'] ?>">
                                <input type="hidden" name="status" value="<?= $busOos ? 'active' : 'out_of_service' ?>">
                                <button type="submit" class="btn btn-sm <?= $busOos ? '' : 'btn-danger' ?>">
                                    <?= $busOos ? 'Restore Active' : 'Set Out of Service' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
