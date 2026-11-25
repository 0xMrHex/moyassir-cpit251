<?php
declare(strict_types=1);

class Bus
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAllWithOccupancy(): array
    {
        $sql = "SELECT b.bus_id, b.bus_number, b.driver_id, b.max_capacity, b.departure_time, b.status,
                       COALESCE(d.full_name, 'Unassigned') AS driver_name,
                       COALESCE(d.phone, '—') AS driver_phone,
                       d.status AS driver_status,
                       COUNT(a.attendee_id) AS assigned_count
                FROM BUSES b
                LEFT JOIN DRIVERS d ON b.driver_id = d.driver_id
                LEFT JOIN ATTENDEES a ON a.bus_id = b.bus_id
                GROUP BY b.bus_id, b.bus_number, b.driver_id, b.max_capacity, b.departure_time, b.status,
                         d.full_name, d.phone, d.status
                ORDER BY b.bus_number ASC";
        return $this->db->query($sql);
    }

    public function findById(int $busId): ?array
    {
        return $this->db->queryOne(
            "SELECT b.bus_id, b.bus_number, b.driver_id, b.max_capacity, b.departure_time, b.status,
                    COALESCE(d.full_name, 'Unassigned') AS driver_name,
                    COALESCE(d.phone, '—') AS driver_phone,
                    d.status AS driver_status
             FROM BUSES b
             LEFT JOIN DRIVERS d ON b.driver_id = d.driver_id
             WHERE b.bus_id = :bus_id",
            [':bus_id' => $busId]
        );
    }

    /**
     * Count attendees currently on a bus, optionally excluding a specific attendee
     * so editing/re-saving an already-assigned attendee does not double-count their seat.
     */
    public function getOccupancy(int $busId, ?int $excludeAttendeeId = null): int
    {
        if ($excludeAttendeeId !== null) {
            $row = $this->db->queryOne(
                'SELECT COUNT(*) AS cnt FROM ATTENDEES WHERE bus_id = :bus_id AND attendee_id <> :exclude_id',
                [':bus_id' => $busId, ':exclude_id' => $excludeAttendeeId]
            );
        } else {
            $row = $this->db->queryOne(
                'SELECT COUNT(*) AS cnt FROM ATTENDEES WHERE bus_id = :bus_id',
                [':bus_id' => $busId]
            );
        }
        return (int) ($row['cnt'] ?? 0);
    }

    public function hasCapacity(int $busId, ?int $excludeAttendeeId = null): bool
    {
        $bus = $this->findById($busId);
        if (!$bus) {
            return false;
        }
        $current = $this->getOccupancy($busId, $excludeAttendeeId);
        return $current < (int) $bus['max_capacity'];
    }

    public function create(array $data): int
    {
        $driverId = !empty($data['driver_id']) ? (int) $data['driver_id'] : null;
        $status   = ($data['status'] ?? 'active') === 'out_of_service' ? 'out_of_service' : 'active';

        if ($driverId !== null) {
            $this->validateDriverAssignable($driverId);
        }

        $this->db->beginTransaction();
        try {
            if ($driverId !== null) {
                $this->db->execute(
                    'UPDATE BUSES SET driver_id = NULL WHERE driver_id = :driver_id',
                    [':driver_id' => $driverId]
                );
            }

            $busId = $this->db->insert(
                'INSERT INTO BUSES (bus_number, driver_id, max_capacity, departure_time, status)
                 VALUES (:bus_number, :driver_id, :max_capacity, :departure_time, :status)',
                [
                    ':bus_number'     => trim($data['bus_number']),
                    ':driver_id'      => $driverId,
                    ':max_capacity'   => (int) $data['max_capacity'],
                    ':departure_time' => trim($data['departure_time']),
                    ':status'         => $status,
                ]
            );
            $this->db->commit();
            return $busId;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function update(int $busId, array $data): bool
    {
        $driverId = isset($data['driver_id']) && $data['driver_id'] !== '' ? (int) $data['driver_id'] : null;
        $status   = ($data['status'] ?? 'active') === 'out_of_service' ? 'out_of_service' : 'active';

        if ($driverId !== null) {
            $this->validateDriverAssignable($driverId);
        }

        $this->db->beginTransaction();
        try {
            if ($driverId !== null) {
                // Unassign driver from any other bus first so 1 driver operates at most 1 bus
                $this->db->execute(
                    'UPDATE BUSES SET driver_id = NULL WHERE driver_id = :driver_id AND bus_id <> :bus_id',
                    [':driver_id' => $driverId, ':bus_id' => $busId]
                );
            }

            $affected = $this->db->execute(
                'UPDATE BUSES
                 SET bus_number = :bus_number,
                     driver_id = :driver_id,
                     max_capacity = :max_capacity,
                     departure_time = :departure_time,
                     status = :status
                 WHERE bus_id = :bus_id',
                [
                    ':bus_number'     => trim($data['bus_number']),
                    ':driver_id'      => $driverId,
                    ':max_capacity'   => (int) $data['max_capacity'],
                    ':departure_time' => trim($data['departure_time']),
                    ':status'         => $status,
                    ':bus_id'         => $busId,
                ]
            );
            $this->db->commit();
            return $affected >= 0;
        } catch (Throwable $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    public function setStatus(int $busId, string $status): bool
    {
        $normalized = $status === 'out_of_service' ? 'out_of_service' : 'active';
        return $this->db->execute(
            'UPDATE BUSES SET status = :status WHERE bus_id = :bus_id',
            [':status' => $normalized, ':bus_id' => $busId]
        ) >= 0;
    }

    private function validateDriverAssignable(int $driverId): void
    {
        $driver = $this->db->queryOne(
            'SELECT driver_id, full_name, status FROM DRIVERS WHERE driver_id = :driver_id',
            [':driver_id' => $driverId]
        );
        if (!$driver) {
            throw new InvalidArgumentException('Selected driver does not exist.');
        }
        if (($driver['status'] ?? 'active') === 'out_of_service') {
            throw new InvalidArgumentException(
                "Driver '{$driver['full_name']}' is Out of Service and cannot be assigned to a bus."
            );
        }
    }
}
