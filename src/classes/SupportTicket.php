<?php
/**
 * SupportTicket Model — Pilgrim Customer Support Requests & Admin Responses
 */

declare(strict_types=1);

class SupportTicket
{
    public const ALLOWED_CATEGORIES = [
        'Transport / Bus',
        'Accommodation / Tent',
        'Schedule / Itinerary',
        'Medical / Urgent',
        'General Inquiry',
    ];

    public const ALLOWED_PRIORITIES = ['normal', 'urgent'];
    public const ALLOWED_STATUSES   = ['open', 'in_progress', 'resolved'];

    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Submit a new customer support ticket from a logged-in pilgrim.
     */
    public function create(int $attendeeId, array $data): array
    {
        $category = trim((string) ($data['category'] ?? 'General Inquiry'));
        $priority = trim((string) ($data['priority'] ?? 'normal'));
        $subject  = trim((string) ($data['subject'] ?? ''));
        $message  = trim((string) ($data['message'] ?? ''));

        if (!in_array($category, self::ALLOWED_CATEGORIES, true)) {
            return ['ok' => false, 'error' => 'Please select a valid support category.'];
        }
        if (!in_array($priority, self::ALLOWED_PRIORITIES, true)) {
            return ['ok' => false, 'error' => 'Please select a valid priority level.'];
        }
        if ($subject === '' || strlen($subject) > 120) {
            return ['ok' => false, 'error' => 'Please enter a short subject (1–120 characters).'];
        }
        if ($message === '' || strlen($message) > 1500) {
            return ['ok' => false, 'error' => 'Please describe your issue in the message box (1–1500 characters).'];
        }

        $ticketId = $this->db->insert(
            'INSERT INTO SUPPORT_TICKETS (attendee_id, category, priority, subject, message, status)
             VALUES (:attendee_id, :category, :priority, :subject, :message, :status)',
            [
                ':attendee_id' => $attendeeId,
                ':category'    => $category,
                ':priority'    => $priority,
                ':subject'     => $subject,
                ':message'     => $message,
                ':status'      => 'open',
            ]
        );

        return ['ok' => true, 'ticket_id' => $ticketId];
    }

    /**
     * Retrieve all support tickets submitted by a specific attendee (newest first).
     */
    public function getByAttendeeId(int $attendeeId): array
    {
        return $this->db->query(
            'SELECT ticket_id, attendee_id, category, priority, subject, message,
                    status, admin_reply, created_at, updated_at
             FROM SUPPORT_TICKETS
             WHERE attendee_id = :attendee_id
             ORDER BY ticket_id DESC',
            [':attendee_id' => $attendeeId]
        );
    }

    /**
     * Retrieve all tickets with full pilgrim logistics context for the Admin Support Inbox.
     */
    public function getAll(?string $statusFilter = null, ?string $priorityFilter = null): array
    {
        $where  = [];
        $params = [];

        if ($statusFilter !== null && $statusFilter !== '' && in_array($statusFilter, self::ALLOWED_STATUSES, true)) {
            $where[]           = 'st.status = :status';
            $params[':status'] = $statusFilter;
        }
        if ($priorityFilter !== null && $priorityFilter !== '' && in_array($priorityFilter, self::ALLOWED_PRIORITIES, true)) {
            $where[]             = 'st.priority = :priority';
            $params[':priority'] = $priorityFilter;
        }

        $whereSql = empty($where) ? '' : ('WHERE ' . implode(' AND ', $where));

        return $this->db->query(
            "SELECT st.ticket_id, st.attendee_id, st.category, st.priority, st.subject, st.message,
                    st.status, st.admin_reply, st.created_at, st.updated_at,
                    a.full_name, a.national_id, a.phone, a.campaign_type,
                    b.bus_number, t.tent_number
             FROM SUPPORT_TICKETS st
             INNER JOIN ATTENDEES a ON st.attendee_id = a.attendee_id
             LEFT JOIN BUSES b ON a.bus_id = b.bus_id
             LEFT JOIN TENTS t ON a.tent_id = t.tent_id
             {$whereSql}
             ORDER BY
                CASE st.status WHEN 'open' THEN 0 WHEN 'in_progress' THEN 1 ELSE 2 END ASC,
                CASE st.priority WHEN 'urgent' THEN 0 ELSE 1 END ASC,
                st.ticket_id DESC",
            $params
        );
    }

    public function findById(int $ticketId): ?array
    {
        return $this->db->queryOne(
            'SELECT ticket_id, attendee_id, category, priority, subject, message,
                    status, admin_reply, created_at, updated_at
             FROM SUPPORT_TICKETS
             WHERE ticket_id = :ticket_id',
            [':ticket_id' => $ticketId]
        );
    }

    /**
     * Save admin response and update ticket status.
     */
    public function respond(int $ticketId, string $status, string $adminReply): array
    {
        $status     = trim($status);
        $adminReply = trim($adminReply);

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return ['ok' => false, 'error' => 'Invalid ticket status selected.'];
        }
        if (!$this->findById($ticketId)) {
            return ['ok' => false, 'error' => 'Support ticket not found.'];
        }

        $this->db->execute(
            'UPDATE SUPPORT_TICKETS
             SET status = :status,
                 admin_reply = :admin_reply,
                 updated_at = CURRENT_TIMESTAMP
             WHERE ticket_id = :ticket_id',
            [
                ':status'      => $status,
                ':admin_reply' => $adminReply === '' ? null : $adminReply,
                ':ticket_id'   => $ticketId,
            ]
        );

        return ['ok' => true];
    }

    /**
     * Delete a support ticket record by ID.
     */
    public function delete(int $ticketId): bool
    {
        return $this->db->execute(
            'DELETE FROM SUPPORT_TICKETS WHERE ticket_id = :ticket_id',
            [':ticket_id' => $ticketId]
        ) > 0;
    }

    /**
     * Count active (open + in_progress) tickets for navigation & dashboard badges.
     */
    public function countActive(): int
    {
        $row = $this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM SUPPORT_TICKETS WHERE status IN ('open', 'in_progress')"
        );
        return (int) ($row['cnt'] ?? 0);
    }
}
