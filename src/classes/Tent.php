<?php
declare(strict_types=1);

class Tent
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAllWithOccupancy(): array
    {
        $sql = 'SELECT t.tent_id, t.tent_number, t.location_zone, t.max_capacity,
                       COUNT(a.attendee_id) AS assigned_count
                FROM TENTS t
                LEFT JOIN ATTENDEES a ON a.tent_id = t.tent_id
                GROUP BY t.tent_id, t.tent_number, t.location_zone, t.max_capacity
                ORDER BY t.tent_number ASC';
        return $this->db->query($sql);
    }

    public function findById(int $tentId): ?array
    {
        return $this->db->queryOne(
            'SELECT tent_id, tent_number, location_zone, max_capacity
             FROM TENTS WHERE tent_id = :tent_id',
            [':tent_id' => $tentId]
        );
    }

    public function getOccupancy(int $tentId, ?int $excludeAttendeeId = null): int
    {
        if ($excludeAttendeeId !== null) {
            $row = $this->db->queryOne(
                'SELECT COUNT(*) AS cnt FROM ATTENDEES WHERE tent_id = :tent_id AND attendee_id <> :exclude_id',
                [':tent_id' => $tentId, ':exclude_id' => $excludeAttendeeId]
            );
        } else {
            $row = $this->db->queryOne(
                'SELECT COUNT(*) AS cnt FROM ATTENDEES WHERE tent_id = :tent_id',
                [':tent_id' => $tentId]
            );
        }
        return (int) ($row['cnt'] ?? 0);
    }

    public function hasCapacity(int $tentId, ?int $excludeAttendeeId = null): bool
    {
        $tent = $this->findById($tentId);
        if (!$tent) {
            return false;
        }
        $current = $this->getOccupancy($tentId, $excludeAttendeeId);
        return $current < (int) $tent['max_capacity'];
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO TENTS (tent_number, location_zone, max_capacity)
             VALUES (:tent_number, :location_zone, :max_capacity)',
            [
                ':tent_number'   => trim($data['tent_number']),
                ':location_zone' => trim($data['location_zone']),
                ':max_capacity'  => (int) $data['max_capacity'],
            ]
        );
    }

    public function update(int $tentId, array $data): bool
    {
        $affected = $this->db->execute(
            'UPDATE TENTS
             SET tent_number = :tent_number,
                 location_zone = :location_zone,
                 max_capacity = :max_capacity
             WHERE tent_id = :tent_id',
            [
                ':tent_number'   => trim($data['tent_number']),
                ':location_zone' => trim($data['location_zone']),
                ':max_capacity'  => (int) $data['max_capacity'],
                ':tent_id'       => $tentId,
            ]
        );
        return $affected >= 0;
    }
}
