<?php
declare(strict_types=1);

class Itinerary
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        return $this->db->query(
            'SELECT i.itinerary_id, i.title, i.event_date, i.description, i.bus_id, b.bus_number
             FROM ITINERARIES i
             INNER JOIN BUSES b ON i.bus_id = b.bus_id
             ORDER BY b.bus_number ASC, i.itinerary_id ASC'
        );
    }

    public function getByBusId(int $busId): array
    {
        return $this->db->query(
            'SELECT itinerary_id, title, event_date, description, bus_id
             FROM ITINERARIES
             WHERE bus_id = :bus_id
             ORDER BY itinerary_id ASC',
            [':bus_id' => $busId]
        );
    }

    public function create(array $data): int
    {
        return $this->db->insert(
            'INSERT INTO ITINERARIES (title, event_date, description, bus_id)
             VALUES (:title, :event_date, :description, :bus_id)',
            [
                ':title'       => trim($data['title']),
                ':event_date'  => trim($data['event_date']),
                ':description' => trim($data['description']),
                ':bus_id'      => (int) $data['bus_id'],
            ]
        );
    }

    public function update(int $itineraryId, array $data): bool
    {
        $affected = $this->db->execute(
            'UPDATE ITINERARIES
             SET title = :title,
                 event_date = :event_date,
                 description = :description,
                 bus_id = :bus_id
             WHERE itinerary_id = :itinerary_id',
            [
                ':title'        => trim($data['title']),
                ':event_date'   => trim($data['event_date']),
                ':description'  => trim($data['description']),
                ':bus_id'       => (int) $data['bus_id'],
                ':itinerary_id' => $itineraryId,
            ]
        );
        return $affected >= 0;
    }

    public function delete(int $itineraryId): bool
    {
        return $this->db->execute(
            'DELETE FROM ITINERARIES WHERE itinerary_id = :itinerary_id',
            [':itinerary_id' => $itineraryId]
        ) > 0;
    }
}
