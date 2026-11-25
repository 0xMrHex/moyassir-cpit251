<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth_check.php';
requireAttendee();

$db           = Database::getInstance();
$attModel     = new Attendee($db);
$supportModel = new SupportTicket($db);

$attendee = $attModel->findByUserId((int) $_SESSION['user_id']);
$error    = '';

$form = [
    'category' => 'Transport / Bus',
    'priority' => 'normal',
    'subject'  => '',
    'message'  => '',
];

if ($attendee && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    $form = [
        'category' => trim((string) ($_POST['category'] ?? 'Transport / Bus')),
        'priority' => trim((string) ($_POST['priority'] ?? 'normal')),
        'subject'  => trim((string) ($_POST['subject'] ?? '')),
        'message'  => trim((string) ($_POST['message'] ?? '')),
    ];

    if (!verifyCsrfToken($csrf)) {
        $error = 'Security token expired. Please submit your message again.';
    } else {
        $res = $supportModel->create((int) $attendee['attendee_id'], $form);
        if ($res['ok']) {
            setFlash(
                'success',
                'Your customer support message (Ticket #' . (int) $res['ticket_id'] . ') has been sent directly to Campaign Administration.'
            );
            header('Location: /support.php');
            exit;
        }
        $error = (string) $res['error'];
    }
}

$tickets = $attendee ? $supportModel->getByAttendeeId((int) $attendee['attendee_id']) : [];

$pilgrimPrompts = [
    [
        'label'    => 'Bus Gate / Pickup Help',
        'category' => 'Transport / Bus',
        'priority' => 'normal',
        'subject'  => 'Bus Assembly Point & Gate Inquiry',
        'text'     => 'Assalamu Alaikum, could you please confirm the exact assembly gate and boarding time for my assigned bus?',
    ],
    [
        'label'    => 'Tent / Mattress Request',
        'category' => 'Accommodation / Tent',
        'priority' => 'normal',
        'subject'  => 'Tent Placement Assistance',
        'text'     => 'Assalamu Alaikum, I would like assistance regarding my assigned tent space / family proximity.',
    ],
    [
        'label'    => 'Urgent Field Help',
        'category' => 'Medical / Urgent',
        'priority' => 'urgent',
        'subject'  => 'Urgent Field Assistance Needed',
        'text'     => 'I need immediate field assistance at my current location. Please contact me on my registered mobile number.',
    ],
];

$pageTitle = 'Customer Support';
$activeNav = 'support';
require_once __DIR__ . '/includes/header.php';
?>

<?php if (!$attendee): ?>
    <div class="alert alert-error">Your attendee profile could not be found. Please contact the campaign administration desk.</div>
<?php else: ?>
    <div class="page-header">
        <div>
            <h1>Campaign Customer Support</h1>
            <p class="page-subtitle">Fill out the form below to send a direct assistance request to Campaign Administration.</p>
        </div>
        <a href="/portal.php" class="btn btn-outline">&larr; Back to My Pilgrim Pass</a>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="card" id="support-form">
        <h2>Contact Customer Support — Submit a Message</h2>
        <p style="font-size:13px;color:var(--muted);margin-top:-6px;margin-bottom:14px;">
            Your pilgrim identity and current assignments (<strong><?= e($attendee['full_name']) ?></strong> &bull;
            ID: <code><?= e($attendee['national_id']) ?></code> &bull;
            Bus: <strong><?= e($attendee['bus_number'] ?? 'Unassigned') ?></strong> &bull;
            Tent: <strong><?= e($attendee['tent_number'] ?? 'Unassigned') ?></strong>) are attached automatically.
        </p>

        <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:6px;">Quick Topic Templates (optional — tap to fill):</div>
        <div class="quick-chips" style="margin-bottom:14px;">
            <?php foreach ($pilgrimPrompts as $pp): ?>
                <button type="button" class="chip-btn"
                        data-target="message"
                        data-subject-target="subject"
                        data-subject="<?= e($pp['subject']) ?>"
                        data-category-target="category"
                        data-category="<?= e($pp['category']) ?>"
                        data-priority-target="priority"
                        data-priority="<?= e($pp['priority']) ?>"
                        data-text="<?= e($pp['text']) ?>">
                    + <?= e($pp['label']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <form method="POST" action="/support.php#support-form">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

            <div class="form-grid">
                <div class="form-group">
                    <label for="category">Support Topic / Category</label>
                    <select id="category" name="category" required>
                        <?php foreach (SupportTicket::ALLOWED_CATEGORIES as $cat): ?>
                            <option value="<?= e($cat) ?>" <?= $form['category'] === $cat ? 'selected' : '' ?>>
                                <?= e($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Urgency Level</label>
                    <select id="priority" name="priority" required>
                        <option value="normal" <?= $form['priority'] === 'normal' ? 'selected' : '' ?>>Normal — Standard Inquiry</option>
                        <option value="urgent" <?= $form['priority'] === 'urgent' ? 'selected' : '' ?>>Urgent — Immediate Field Assistance</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="subject">Subject / Short Summary</label>
                <input type="text" id="subject" name="subject" maxlength="120"
                       value="<?= e($form['subject']) ?>"
                       placeholder="e.g., Need help finding BUS-101 gate or tent placement request" required>
            </div>

            <div class="form-group">
                <label for="message">Please describe how Customer Support can assist you</label>
                <textarea id="message" name="message" class="composer-textarea" rows="4" maxlength="1500"
                          placeholder="Write your full message, location details, or request for Campaign Administration..." required><?= e($form['message']) ?></textarea>
            </div>

            <button type="submit" class="btn" style="width:100%;max-width:320px;min-height:42px;">Send Message to Campaign Admin</button>
        </form>
    </div>

    <div class="card">
        <h2>My Support Requests &amp; Admin Replies (<?= count($tickets) ?>)</h2>

        <?php if (empty($tickets)): ?>
            <p style="color:var(--muted);margin:0;">You have not submitted any customer support messages yet.</p>
        <?php else: ?>
            <?php foreach ($tickets as $t):
                $status = (string) $t['status'];
                $statusBadge = match ($status) {
                    'resolved'    => 'badge-ok',
                    'in_progress' => 'badge-umrah',
                    default       => 'badge-hajj',
                };
                $statusLabel = strtoupper(str_replace('_', ' ', $status));
                $isUrgent = ($t['priority'] === 'urgent');
            ?>
                <div class="ticket-card <?= $status === 'resolved' ? 'resolved' : ($isUrgent ? 'urgent' : '') ?>">
                    <div class="ticket-meta">
                        <div>
                            <strong>Ticket #<?= (int) $t['ticket_id'] ?></strong> &bull;
                            <span><?= e($t['category']) ?></span>
                            <?php if ($isUrgent): ?>
                                <span class="badge badge-full" style="margin-left:6px;">URGENT</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="badge <?= $statusBadge ?>"><?= e($statusLabel) ?></span>
                            <span style="margin-left:6px;"><?= e($t['created_at']) ?></span>
                        </div>
                    </div>

                    <div class="chat-thread" style="margin-bottom:0;">
                        <div class="chat-bubble pilgrim">
                            <div class="chat-sender">
                                <span>Your Message</span>
                                <span><?= e($t['created_at']) ?></span>
                            </div>
                            <div style="font-weight:700;font-size:15px;color:var(--text);margin-bottom:4px;"><?= e($t['subject']) ?></div>
                            <div style="white-space:pre-line;"><?= e($t['message']) ?></div>
                        </div>

                        <?php if (!empty($t['admin_reply'])): ?>
                            <div class="chat-bubble admin">
                                <div class="chat-sender">
                                    <span>Campaign Administration Reply</span>
                                    <span><?= e($t['updated_at']) ?></span>
                                </div>
                                <div style="white-space:pre-line;"><?= e($t['admin_reply']) ?></div>
                            </div>
                        <?php else: ?>
                            <div style="font-size:12.5px;color:var(--muted);padding-left:4px;">
                                Awaiting response from Campaign Administration.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
