<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/auth_check.php';
requireAdmin();

$db          = Database::getInstance();
$busModel    = new Bus($db);
$driverModel = new Driver($db);
$tentModel   = new Tent($db);
$itinModel   = new Itinerary($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid CSRF token.');
        header('Location: /admin/logistics.php');
        exit;
    }

    $action = (string) ($_POST['action'] ?? '');
    try {
        if ($action === 'add_bus') {
            $busModel->create($_POST);
            setFlash('success', "Bus '" . trim((string) $_POST['bus_number']) . "' added.");
        } elseif ($action === 'update_bus') {
            $busId  = (int) ($_POST['bus_id'] ?? 0);
            $newCap = (int) ($_POST['max_capacity'] ?? 1);
            $occ    = $busModel->getOccupancy($busId);
            if ($newCap < $occ) {
                setFlash('error', "Cannot reduce capacity below current occupancy ({$occ} passengers).");
            } else {
                $busModel->update($busId, $_POST);
                setFlash('success', 'Bus details, driver, status & departure time updated! Driver and attendee portals now show the latest info.');
            }
        } elseif ($action === 'add_tent') {
            $tentModel->create($_POST);
            setFlash('success', "Tent '" . trim((string) $_POST['tent_number']) . "' added.");
        } elseif ($action === 'add_itinerary') {
            $itinModel->create($_POST);
            setFlash('success', 'Itinerary milestone published to bus driver & passengers.');
        } elseif ($action === 'delete_itinerary') {
            $itinModel->delete((int) ($_POST['itinerary_id'] ?? 0));
            setFlash('success', 'Itinerary milestone removed.');
        }
    } catch (Throwable $e) {
        setFlash('error', 'Operation failed: ' . $e->getMessage());
    }

    header('Location: /admin/logistics.php');
    exit;
}

$buses       = $busModel->getAllWithOccupancy();
$drivers     = $driverModel->getAll();
$tents       = $tentModel->getAllWithOccupancy();
$itineraries = $itinModel->getAll();

$pageTitle = 'Buses, Tents & Itineraries';
$activeNav = 'logistics';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header">
    <div>
        <h1>Campaign Logistics &amp; Itineraries</h1>
        <p class="page-subtitle">Manage buses, assigned drivers, service status (Active / Out of Service), tents, and schedules.</p>
    </div>
    <a href="/admin/drivers.php" class="btn btn-sec">Manage Drivers</a>
</div>

<div class="card">
    <h2>Buses, Drivers &amp; Departure Schedules</h2>
    <div class="table-wrap" style="margin-bottom:18px;">
        <table>
            <thead>
                <tr>
                    <th>Bus #</th>
                    <th>Assigned Driver</th>
                    <th>Service Status</th>
                    <th>Capacity (Used/Max)</th>
                    <th>Departure Time</th>
                    <th>Save</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buses as $b): ?>
                <tr>
                    <form method="POST" action="/admin/logistics.php">
                        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                        <input type="hidden" name="action" value="update_bus">
                        <input type="hidden" name="bus_id" value="<?= (int) $b['bus_id'] ?>">
                        <td><input type="text" name="bus_number" value="<?= e($b['bus_number']) ?>" required style="min-width:95px;"></td>
                        <td>
                            <select name="driver_id" style="min-width:155px;">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($drivers as $d): ?>
                                    <option value="<?= (int) $d['driver_id'] ?>" <?= (int) ($b['driver_id'] ?? 0) === (int) $d['driver_id'] ? 'selected' : '' ?>>
                                        <?= e($d['full_name']) ?><?= ($d['status'] ?? 'active') === 'out_of_service' ? ' (OOS)' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <select name="status" style="min-width:130px;">
                                <option value="active" <?= ($b['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="out_of_service" <?= ($b['status'] ?? 'active') === 'out_of_service' ? 'selected' : '' ?>>Out of Service</option>
                            </select>
                        </td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <span><?= (int) $b['assigned_count'] ?> /</span>
                                <input type="number" name="max_capacity" min="<?= max(1, (int) $b['assigned_count']) ?>" value="<?= (int) $b['max_capacity'] ?>" style="width:70px;" required>
                            </div>
                        </td>
                        <td><input type="text" name="departure_time" value="<?= e($b['departure_time']) ?>" required style="min-width:210px;"></td>
                        <td><button type="submit" class="btn btn-sm">Update</button></td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h3>+ Add New Bus</h3>
    <form method="POST" action="/admin/logistics.php" class="filter-bar">
        <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
        <input type="hidden" name="action" value="add_bus">
        <div class="form-group"><label>Bus #</label><input type="text" name="bus_number" placeholder="BUS-103" required></div>
        <div class="form-group">
            <label>Assign Driver</label>
            <select name="driver_id">
                <option value="">-- Unassigned --</option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?= (int) $d['driver_id'] ?>"><?= e($d['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Status</label>
            <select name="status">
                <option value="active">Active</option>
                <option value="out_of_service">Out of Service</option>
            </select>
        </div>
        <div class="form-group" style="max-width:105px;"><label>Max Seats</label><input type="number" name="max_capacity" min="1" value="45" required></div>
        <div class="form-group" style="flex:2;"><label>Departure Time</label><input type="text" name="departure_time" placeholder="8 Dhul-Hijjah — 08:00 AM" required></div>
        <button type="submit" class="btn">Add Bus</button>
    </form>
</div>

<div class="grid-2">
    <div class="card">
        <h2>Tents &amp; Housing Units</h2>
        <div class="table-wrap" style="margin-bottom:16px;">
            <table>
                <thead><tr><th>Tent #</th><th>Location Zone</th><th>Occupancy</th></tr></thead>
                <tbody>
                    <?php foreach ($tents as $t): ?>
                    <tr>
                        <td><strong><?= e($t['tent_number']) ?></strong></td>
                        <td><?= e($t['location_zone']) ?></td>
                        <td><?= (int) $t['assigned_count'] ?> / <?= (int) $t['max_capacity'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <h3>+ Add New Tent</h3>
        <form method="POST" action="/admin/logistics.php">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="add_tent">
            <div class="form-grid">
                <div class="form-group"><label>Tent #</label><input type="text" name="tent_number" placeholder="TENT-C03" required></div>
                <div class="form-group"><label>Max Capacity</label><input type="number" name="max_capacity" min="1" value="12" required></div>
            </div>
            <div class="form-group"><label>Location Zone</label><input type="text" name="location_zone" placeholder="Mina Zone C — Gate 12" required></div>
            <button type="submit" class="btn btn-sm">Add Tent</button>
        </form>
    </div>

    <div class="card">
        <h2>Bus Itinerary Milestones</h2>
        <div class="table-wrap" style="margin-bottom:16px;">
            <table>
                <thead><tr><th>Bus</th><th>Event &amp; Schedule</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($itineraries as $it): ?>
                    <tr>
                        <td><strong><?= e($it['bus_number']) ?></strong></td>
                        <td>
                            <strong><?= e($it['title']) ?></strong><br>
                            <small style="color:var(--primary);"><?= e($it['event_date']) ?></small><br>
                            <small style="color:var(--muted);"><?= e($it['description']) ?></small>
                        </td>
                        <td>
                            <form method="POST" action="/admin/logistics.php">
                                <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                                <input type="hidden" name="action" value="delete_itinerary">
                                <input type="hidden" name="itinerary_id" value="<?= (int) $it['itinerary_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <h3>+ Publish Itinerary Event</h3>
        <form method="POST" action="/admin/logistics.php">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="add_itinerary">
            <div class="form-grid">
                <div class="form-group">
                    <label>Target Bus</label>
                    <select name="bus_id" required>
                        <?php foreach ($buses as $b): ?>
                            <option value="<?= (int) $b['bus_id'] ?>"><?= e($b['bus_number']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Event Date / Time</label><input type="text" name="event_date" placeholder="10 Dhul-Hijjah — 06:00 AM" required></div>
            </div>
            <div class="form-group"><label>Event Title</label><input type="text" name="title" placeholder="Jamarat Al-Aqabah Group Walk" required></div>
            <div class="form-group"><label>Description</label><textarea name="description" rows="2" placeholder="Assembly point and instructions..." required></textarea></div>
            <button type="submit" class="btn btn-sm">Publish Event</button>
        </form>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
