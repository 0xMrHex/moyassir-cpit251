<?php
declare(strict_types=1);

class Driver
{
    private Database $db;
    private User $userModel;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->userModel = new User($db);
    }

    public function getAll(): array
    {
        return $this->db->query(
            'SELECT d.driver_id, d.user_id, d.full_name, d.license_number, d.phone, d.status, d.created_at,
                    u.username,
                    b.bus_id AS assigned_bus_id, b.bus_number AS assigned_bus_number, b.status AS assigned_bus_status
             FROM DRIVERS d
             INNER JOIN USERS u ON d.user_id = u.user_id
             LEFT JOIN BUSES b ON b.driver_id = d.driver_id
             ORDER BY d.driver_id ASC'
        );
    }

    public function findById(int $driverId): ?array
    {
        return $this->db->queryOne(
            'SELECT d.driver_id, d.user_id, d.full_name, d.license_number, d.phone, d.status, d.created_at,
                    u.username
             FROM DRIVERS d
             INNER JOIN USERS u ON d.user_id = u.user_id
             WHERE d.driver_id = :driver_id',
            [':driver_id' => $driverId]
        );
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->db->queryOne(
            'SELECT d.driver_id, d.user_id, d.full_name, d.license_number, d.phone, d.status, d.created_at,
                    u.username
             FROM DRIVERS d
             INNER JOIN USERS u ON d.user_id = u.user_id
             WHERE d.user_id = :user_id',
            [':user_id' => $userId]
        );
    }

    public function findByLicense(string $license): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM DRIVERS WHERE license_number = :license',
            [':license' => trim($license)]
        );
    }

    public function getAssignedBuses(int $driverId): array
    {
        return $this->db->query(
            'SELECT b.bus_id, b.bus_number, b.max_capacity, b.departure_time, b.status,
                    COUNT(a.attendee_id) AS assigned_count
             FROM BUSES b
             LEFT JOIN ATTENDEES a ON a.bus_id = b.bus_id
             WHERE b.driver_id = :driver_id
             GROUP BY b.bus_id, b.bus_number, b.max_capacity, b.departure_time, b.status
             ORDER BY b.bus_number ASC',
            [':driver_id' => $driverId]
        );
    }

    /**
     * Atomically creates a driver USERS login account (role = 'driver'),
     * inserts DRIVERS profile, and optionally assigns to a specific bus.
     */
    public function createWithAccount(array $data, string $defaultPassword = 'Driver@2026'): array
    {
        $username = trim((string) ($data['username'] ?? ''));
        $fullName = trim((string) ($data['full_name'] ?? ''));
        $license  = trim((string) ($data['license_number'] ?? ''));
        $phone    = trim((string) ($data['phone'] ?? ''));
        $status   = ($data['status'] ?? 'active') === 'out_of_service' ? 'out_of_service' : 'active';
        $busId    = !empty($data['bus_id']) ? (int) $data['bus_id'] : null;

        if ($username === '' || $fullName === '' || $license === '' || $phone === '') {
            return ['ok' => false, 'error' => 'Username, Full Name, License Number, and Phone are required.'];
        }

        if ($this->userModel->findByUsername($username)) {
            return ['ok' => false, 'error' => "Username '{$username}' is already taken."];
        }

        if ($this->findByLicense($license)) {
            return ['ok' => false, 'error' => "License Number '{$license}' is already registered."];
        }

        if ($busId !== null && $status === 'out_of_service') {
            return ['ok' => false, 'error' => 'Cannot assign an Out of Service driver to a bus. Set status to Active first.'];
        }

        $this->db->beginTransaction();
        try {
            $userId = $this->userModel->create($username, $defaultPassword, 'driver');
            $driverId = $this->db->insert(
                'INSERT INTO DRIVERS (user_id, full_name, license_number, phone, status)
                 VALUES (:user_id, :full_name, :license_number, :phone, :status)',
                [
                    ':user_id'        => $userId,
                    ':full_name'      => $fullName,
                    ':license_number' => $license,
                    ':phone'          => $phone,
                    ':status'         => $status,
                ]
            );

            if ($busId !== null) {
                $this->db->execute(
                    'UPDATE BUSES SET driver_id = :driver_id WHERE bus_id = :bus_id',
                    [':driver_id' => $driverId, ':bus_id' => $busId]
                );
            }

            $this->db->commit();
            return ['ok' => true, 'driver_id' => $driverId, 'user_id' => $userId];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Database error while adding driver: ' . $e->getMessage()];
        }
    }

    /**
     * Assign or unassign a driver to/from a specific bus.
     */
    public function assignToBus(int $driverId, ?int $busId): array
    {
        $driver = $this->findById($driverId);
        if (!$driver) {
            return ['ok' => false, 'error' => 'Driver not found.'];
        }

        if ($busId !== null && $driver['status'] === 'out_of_service') {
            return ['ok' => false, 'error' => "Driver '{$driver['full_name']}' is Out of Service and cannot be assigned to a bus."];
        }

        $this->db->beginTransaction();
        try {
            // Clear any previous bus assignment for this driver so each driver handles one bus cleanly
            $this->db->execute(
                'UPDATE BUSES SET driver_id = NULL WHERE driver_id = :driver_id',
                [':driver_id' => $driverId]
            );

            if ($busId !== null) {
                $this->db->execute(
                    'UPDATE BUSES SET driver_id = :driver_id WHERE bus_id = :bus_id',
                    [':driver_id' => $driverId, ':bus_id' => $busId]
                );
            }

            $this->db->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['ok' => false, 'error' => 'Failed to assign driver: ' . $e->getMessage()];
        }
    }

    public function setStatus(int $driverId, string $status): array
    {
        $driver = $this->findById($driverId);
        if (!$driver) {
            return ['ok' => false, 'error' => 'Driver not found.'];
        }
        $normalized = $status === 'out_of_service' ? 'out_of_service' : 'active';
        $this->db->execute(
            'UPDATE DRIVERS SET status = :status WHERE driver_id = :driver_id',
            [':status' => $normalized, ':driver_id' => $driverId]
        );
        return ['ok' => true, 'status' => $normalized];
    }

    /**
     * Deletes the linked USERS row; ON DELETE CASCADE removes DRIVERS and
     * BUSES.driver_id is automatically set to NULL via ON DELETE SET NULL.
     */
    public function delete(int $driverId): bool
    {
        $existing = $this->findById($driverId);
        if (!$existing) {
            return false;
        }
        return $this->userModel->delete((int) $existing['user_id']);
    }
}
