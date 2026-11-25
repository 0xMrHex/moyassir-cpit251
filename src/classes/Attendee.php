<?php
declare(strict_types=1);

class Attendee
{
    private Database $db;
    private User $userModel;
    private Bus $busModel;
    private Tent $tentModel;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userModel = new User($db);
        $this->busModel = new Bus($db);
        $this->tentModel = new Tent($db);
    }

    public function getAll(
        string $search = '',
        ?int $busId = null,
        ?int $tentId = null,
        string $campaignType = ''
    ): array {
        $sql = "SELECT a.attendee_id, a.user_id, a.full_name, a.national_id, a.phone,
                       a.campaign_type, a.bus_id, a.tent_id, a.registered_at,
                       b.bus_number, b.departure_time, b.status AS bus_status,
                       COALESCE(d.full_name, 'Unassigned') AS driver_name,
                       t.tent_number, t.location_zone
                FROM ATTENDEES a
                LEFT JOIN BUSES b ON a.bus_id = b.bus_id
                LEFT JOIN DRIVERS d ON b.driver_id = d.driver_id
                LEFT JOIN TENTS t ON a.tent_id = t.tent_id
                WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' AND (LOWER(a.full_name) LIKE :q1 OR LOWER(a.national_id) LIKE :q2 OR LOWER(a.phone) LIKE :q3)';
            $like = '%' . strtolower($search) . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
        }

        if ($busId !== null && $busId > 0) {
            $sql .= ' AND a.bus_id = :bus_id';
            $params[':bus_id'] = $busId;
        }

        if ($tentId !== null && $tentId > 0) {
            $sql .= ' AND a.tent_id = :tent_id';
            $params[':tent_id'] = $tentId;
        }

        if ($campaignType === 'Hajj' || $campaignType === 'Umrah') {
            $sql .= ' AND a.campaign_type = :campaign_type';
            $params[':campaign_type'] = $campaignType;
        }

        $sql .= ' ORDER BY a.attendee_id DESC';
        return $this->db->query($sql, $params);
    }

    public function findById(int $attendeeId): ?array
    {
        return $this->db->queryOne(
            "SELECT a.*, u.username,
                    b.bus_number, b.departure_time, b.status AS bus_status,
                    COALESCE(d.full_name, 'Unassigned') AS driver_name,
                    COALESCE(d.phone, '—') AS driver_phone,
                    d.status AS driver_status,
                    t.tent_number, t.location_zone
             FROM ATTENDEES a
             INNER JOIN USERS u ON a.user_id = u.user_id
             LEFT JOIN BUSES b ON a.bus_id = b.bus_id
             LEFT JOIN DRIVERS d ON b.driver_id = d.driver_id
             LEFT JOIN TENTS t ON a.tent_id = t.tent_id
             WHERE a.attendee_id = :attendee_id",
            [':attendee_id' => $attendeeId]
        );
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->db->queryOne(
            "SELECT a.*, u.username,
                    b.bus_number, b.departure_time, b.status AS bus_status,
                    COALESCE(d.full_name, 'Unassigned') AS driver_name,
                    COALESCE(d.phone, '—') AS driver_phone,
                    d.status AS driver_status,
                    t.tent_number, t.location_zone
             FROM ATTENDEES a
             INNER JOIN USERS u ON a.user_id = u.user_id
             LEFT JOIN BUSES b ON a.bus_id = b.bus_id
             LEFT JOIN DRIVERS d ON b.driver_id = d.driver_id
             LEFT JOIN TENTS t ON a.tent_id = t.tent_id
             WHERE a.user_id = :user_id",
            [':user_id' => $userId]
        );
    }

    public function findByNationalId(string $nationalId): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM ATTENDEES WHERE national_id = :national_id',
            [':national_id' => trim($nationalId)]
        );
    }

    /**
     * Atomically creates a linked USERS login account (username = national_id)
     * and ATTENDEES profile, enforcing bus service status and bus/tent capacity limits.
     */
    public function createWithAccount(array $data, string $defaultPassword = 'Pilgrim@2026'): array
    {
        $nationalId = trim($data['national_id'] ?? '');
        $fullName   = trim($data['full_name'] ?? '');
        $phone      = trim($data['phone'] ?? '');
        $type       = in_array($data['campaign_type'] ?? 'Hajj', ['Hajj', 'Umrah'], true)
                        ? $data['campaign_type']
                        : 'Hajj';
        $busId      = !empty($data['bus_id']) ? (int) $data['bus_id'] : null;
        $tentId     = !empty($data['tent_id']) ? (int) $data['tent_id'] : null;

        if ($fullName === '' || $nationalId === '' || $phone === '') {
            return ['ok' => false, 'error' => 'Full Name, National ID, and Phone are required.'];
        }

        if ($this->findByNationalId($nationalId) || $this->userModel->findByUsername($nationalId)) {
            return ['ok' => false, 'error' => "An attendee with National ID '{$nationalId}' is already registered."];
        }

        if ($busId !== null) {
            $bus = $this->busModel->findById($busId);
            if (!$bus) {
                return ['ok' => false, 'error' => 'Selected bus does not exist.'];
            }
            if (($bus['status'] ?? 'active') === 'out_of_service') {
                return ['ok' => false, 'error' => "Bus out of service: {$bus['bus_number']} is currently out of service and cannot accept passengers."];
            }
            if (!$this->busModel->hasCapacity($busId)) {
                $label = "{$bus['bus_number']} ({$bus['max_capacity']}/{$bus['max_capacity']})";
                return ['ok' => false, 'error' => "Bus at max capacity: {$label} cannot accept additional passengers."];
            }
        }

        if ($tentId !== null && !$this->tentModel->hasCapacity($tentId)) {
            $tent = $this->tentModel->findById($tentId);
            $label = $tent ? "{$tent['tent_number']} ({$tent['max_capacity']}/{$tent['max_capacity']})" : "#{$tentId}";
            return ['ok' => false, 'error' => "Tent at max capacity: {$label} cannot accept additional occupants."];
        }

        $this->db->beginTransaction();
        try {
            $userId = $this->userModel->create($nationalId, $defaultPassword, 'attendee');
            $attendeeId = $this->db->insert(
                'INSERT INTO ATTENDEES (user_id, full_name, national_id, phone, campaign_type, bus_id, tent_id)
                 VALUES (:user_id, :full_name, :national_id, :phone, :campaign_type, :bus_id, :tent_id)',
                [
                    ':user_id'       => $userId,
                    ':full_name'     => $fullName,
                    ':national_id'   => $nationalId,
                    ':phone'         => $phone,
                    ':campaign_type' => $type,
                    ':bus_id'        => $busId,
                    ':tent_id'       => $tentId,
                ]
            );
            $this->db->commit();
            return ['ok' => true, 'attendee_id' => $attendeeId, 'user_id' => $userId];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Database error while registering attendee: ' . $e->getMessage()];
        }
    }

    public function update(int $attendeeId, array $data): array
    {
        $existing = $this->findById($attendeeId);
        if (!$existing) {
            return ['ok' => false, 'error' => 'Attendee record not found.'];
        }

        $fullName   = trim($data['full_name'] ?? '');
        $nationalId = trim($data['national_id'] ?? '');
        $phone      = trim($data['phone'] ?? '');
        $type       = in_array($data['campaign_type'] ?? 'Hajj', ['Hajj', 'Umrah'], true)
                        ? $data['campaign_type']
                        : 'Hajj';

        if ($fullName === '' || $nationalId === '' || $phone === '') {
            return ['ok' => false, 'error' => 'Full Name, National ID, and Phone are required.'];
        }

        $dup = $this->findByNationalId($nationalId);
        if ($dup && (int) $dup['attendee_id'] !== $attendeeId) {
            return ['ok' => false, 'error' => "Another attendee already uses National ID '{$nationalId}'."];
        }

        $this->db->beginTransaction();
        try {
            $this->db->execute(
                'UPDATE ATTENDEES
                 SET full_name = :full_name,
                     national_id = :national_id,
                     phone = :phone,
                     campaign_type = :campaign_type
                 WHERE attendee_id = :attendee_id',
                [
                    ':full_name'     => $fullName,
                    ':national_id'   => $nationalId,
                    ':phone'         => $phone,
                    ':campaign_type' => $type,
                    ':attendee_id'   => $attendeeId,
                ]
            );

            if ($nationalId !== $existing['national_id']) {
                $this->db->execute(
                    'UPDATE USERS SET username = :username WHERE user_id = :user_id',
                    [':username' => $nationalId, ':user_id' => (int) $existing['user_id']]
                );
            }

            if (!empty($data['new_password'])) {
                $this->userModel->updatePassword((int) $existing['user_id'], (string) $data['new_password']);
            }

            $this->db->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Update failed: ' . $e->getMessage()];
        }
    }

    public function delete(int $attendeeId): bool
    {
        $existing = $this->findById($attendeeId);
        if (!$existing) {
            return false;
        }
        return $this->userModel->delete((int) $existing['user_id']);
    }

    /**
     * Assign or reassign Bus and Tent with strict server-side service-status & capacity validation.
     */
    public function assignLogistics(int $attendeeId, ?int $busId, ?int $tentId): array
    {
        $existing = $this->findById($attendeeId);
        if (!$existing) {
            return ['ok' => false, 'error' => 'Attendee not found.'];
        }

        if ($busId !== null) {
            $bus = $this->busModel->findById($busId);
            if (!$bus) {
                return ['ok' => false, 'error' => 'Selected bus does not exist.'];
            }
            if (($bus['status'] ?? 'active') === 'out_of_service') {
                return [
                    'ok'    => false,
                    'error' => "Bus out of service: {$bus['bus_number']} is currently out of service and cannot accept passengers.",
                ];
            }
            if (!$this->busModel->hasCapacity($busId, $attendeeId)) {
                return [
                    'ok'    => false,
                    'error' => "Bus at max capacity: {$bus['bus_number']} already has {$bus['max_capacity']}/{$bus['max_capacity']} seats occupied.",
                ];
            }
        }

        if ($tentId !== null) {
            $tent = $this->tentModel->findById($tentId);
            if (!$tent) {
                return ['ok' => false, 'error' => 'Selected tent does not exist.'];
            }
            if (!$this->tentModel->hasCapacity($tentId, $attendeeId)) {
                return [
                    'ok'    => false,
                    'error' => "Tent at max capacity: {$tent['tent_number']} already has {$tent['max_capacity']}/{$tent['max_capacity']} spaces occupied.",
                ];
            }
        }

        $this->db->execute(
            'UPDATE ATTENDEES SET bus_id = :bus_id, tent_id = :tent_id WHERE attendee_id = :attendee_id',
            [
                ':bus_id'      => $busId,
                ':tent_id'     => $tentId,
                ':attendee_id' => $attendeeId,
            ]
        );

        return ['ok' => true];
    }

    public function countTotal(): int
    {
        $row = $this->db->queryOne('SELECT COUNT(*) AS cnt FROM ATTENDEES');
        return (int) ($row['cnt'] ?? 0);
    }
}
