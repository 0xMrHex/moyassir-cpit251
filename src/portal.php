<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
requireAttendee();

$db             = Database::getInstance();
$attModel       = new Attendee($db);
$itineraryModel = new Itinerary($db);
$supportModel   = new SupportTicket($db);

$attendee = $attModel->findByUserId((int) $_SESSION['user_id']);
$itineraries = ($attendee && !empty($attendee['bus_id']))
    ? $itineraryModel->getByBusId((int) $attendee['bus_id'])
    : [];
$myTickets = $attendee ? $supportModel->getByAttendeeId((int) $attendee['attendee_id']) : [];

$pageTitle = 'My Pilgrim Portal';
$activeNav = 'portal';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!$attendee): ?>
    <div class="alert alert-error">Your attendee profile could not be found. Please contact the campaign administration desk.</div>
<?php else:
    $busOos = ($attendee['bus_status'] ?? 'active') === 'out_of_service';
?>
    <div class="portal-hero">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
            <div>
                <div style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;opacity:.85;">
                    Pilgrim Logistics Pass &bull; <?= e($attendee['campaign_type']) ?> Campaign
                </div>
                <h1><?= e($attendee['full_name']) ?></h1>
                <div class="portal-meta">
                    <span>National ID: <strong><?= e($attendee['national_id']) ?></strong></span>
                    <span>Mobile: <strong><?= e($attendee['phone']) ?></strong></span>
                    <span>Attendee #: <strong>#<?= (int) $attendee['attendee_id'] ?></strong></span>
                </div>
            </div>
            <a href="/support.php#support-form" class="btn btn-hero-support no-print">
                Contact Customer Support &rarr;
            </a>
        </div>
    </div>

    <?php if ($busOos): ?>
        <div class="alert alert-error" role="alert">
            <strong>Transport Notice:</strong> Your assigned bus (<strong><?= e($attendee['bus_number']) ?></strong>) is temporarily marked <strong>Out of Service</strong>. Please wait for an updated bus assignment from Campaign Administration.
        </div>
    <?php endif; ?>

    <div class="grid-2">
        <div class="card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                <h2 style="margin:0;">Assigned Transport (Bus)</h2>
                <?php if (!empty($attendee['bus_number'])): ?>
                    <span class="badge <?= $busOos ? 'badge-full' : 'badge-ok' ?>">
                        <?= $busOos ? 'OUT OF SERVICE' : 'ACTIVE' ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php if (!empty($attendee['bus_number'])): ?>
                <div class="detail-row">
                    <span class="detail-key">Bus Number</span>
                    <span class="detail-val" style="font-size:18px;color:var(--primary);"><?= e($attendee['bus_number']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Bus Driver</span>
                    <span class="detail-val"><?= e($attendee['driver_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Driver Contact</span>
                    <span class="detail-val">
                        <?php if ($attendee['driver_phone'] !== '—'): ?>
                            <a href="tel:<?= e($attendee['driver_phone']) ?>"><?= e($attendee['driver_phone']) ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Scheduled Departure</span>
                    <span class="detail-val"><?= e($attendee['departure_time']) ?></span>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="margin:0;">Your bus assignment is currently pending. Please check back shortly.</div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Assigned Accommodation (Tent)</h2>
            <?php if (!empty($attendee['tent_number'])): ?>
                <div class="detail-row">
                    <span class="detail-key">Tent / Block Number</span>
                    <span class="detail-val" style="font-size:18px;color:var(--accent);"><?= e($attendee['tent_number']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Location Zone</span>
                    <span class="detail-val"><?= e($attendee['location_zone']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-key">Campaign Program</span>
                    <span class="detail-val"><?= e($attendee['campaign_type']) ?></span>
                </div>
            <?php else: ?>
                <div class="alert alert-info" style="margin:0;">Your tent housing assignment is currently pending. Please check back shortly.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
            <h2 style="margin:0;">Live Personal Itinerary Schedule</h2>
            <button type="button" onclick="window.print()" class="btn btn-sm btn-outline no-print">Print / Save Pass</button>
        </div>

        <?php if (empty($itineraries)): ?>
            <p style="color:var(--muted);margin:0;">No itinerary milestones are published for your bus yet.</p>
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

    <div class="card support-cta-card no-print">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;">
            <div>
                <h2 style="margin:0 0 4px;">Need Help? Campaign Customer Support</h2>
                <p style="margin:0;color:var(--muted);font-size:13.5px;">
                    Have an issue with your bus, tent zone, departure schedule, or need urgent field assistance? Send a direct message to Campaign Administration.
                    <?php if (!empty($myTickets)): ?>
                        <br><strong>You have <?= count($myTickets) ?> support <?= count($myTickets) === 1 ? 'request' : 'requests' ?> on file.</strong>
                    <?php endif; ?>
                </p>
            </div>
            <a href="/support.php#support-form" class="btn">Contact Customer Support</a>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
