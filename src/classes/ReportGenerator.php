<?php
declare(strict_types=1);

class ReportGenerator
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function getBusManifest(int $busId): ?array
    {
        $bus = $this->db->queryOne(
            "SELECT b.bus_id, b.bus_number, b.max_capacity, b.departure_time, b.status,
                    COALESCE(d.full_name, 'Unassigned') AS driver_name,
                    COALESCE(d.phone, '—') AS driver_phone
             FROM BUSES b
             LEFT JOIN DRIVERS d ON b.driver_id = d.driver_id
             WHERE b.bus_id = :bus_id",
            [':bus_id' => $busId]
        );
        if (!$bus) {
            return null;
        }

        $passengers = $this->db->query(
            'SELECT a.attendee_id, a.full_name, a.national_id, a.phone, a.campaign_type,
                    t.tent_number, t.location_zone
             FROM ATTENDEES a
             LEFT JOIN TENTS t ON a.tent_id = t.tent_id
             WHERE a.bus_id = :bus_id
             ORDER BY a.full_name ASC',
            [':bus_id' => $busId]
        );

        return [
            'bus'        => $bus,
            'passengers' => $passengers,
        ];
    }

    public function getTentRoster(int $tentId): ?array
    {
        $tent = $this->db->queryOne(
            'SELECT tent_id, tent_number, location_zone, max_capacity
             FROM TENTS WHERE tent_id = :tent_id',
            [':tent_id' => $tentId]
        );
        if (!$tent) {
            return null;
        }

        $occupants = $this->db->query(
            "SELECT a.attendee_id, a.full_name, a.national_id, a.phone, a.campaign_type,
                    b.bus_number, COALESCE(d.full_name, 'Unassigned') AS driver_name
             FROM ATTENDEES a
             LEFT JOIN BUSES b ON a.bus_id = b.bus_id
             LEFT JOIN DRIVERS d ON b.driver_id = d.driver_id
             WHERE a.tent_id = :tent_id
             ORDER BY a.full_name ASC",
            [':tent_id' => $tentId]
        );

        return [
            'tent'      => $tent,
            'occupants' => $occupants,
        ];
    }

    public function getSummaryStats(): array
    {
        $totalAtt = (int) (($this->db->queryOne('SELECT COUNT(*) AS cnt FROM ATTENDEES'))['cnt'] ?? 0);
        $totalBus = (int) (($this->db->queryOne('SELECT COUNT(*) AS cnt FROM BUSES'))['cnt'] ?? 0);
        $oosBus   = (int) (($this->db->queryOne("SELECT COUNT(*) AS cnt FROM BUSES WHERE status = 'out_of_service'"))['cnt'] ?? 0);
        $totalDrv = (int) (($this->db->queryOne('SELECT COUNT(*) AS cnt FROM DRIVERS'))['cnt'] ?? 0);
        $oosDrv   = (int) (($this->db->queryOne("SELECT COUNT(*) AS cnt FROM DRIVERS WHERE status = 'out_of_service'"))['cnt'] ?? 0);
        $totalTent = (int) (($this->db->queryOne('SELECT COUNT(*) AS cnt FROM TENTS'))['cnt'] ?? 0);
        $unassigned = (int) (($this->db->queryOne(
            'SELECT COUNT(*) AS cnt FROM ATTENDEES WHERE bus_id IS NULL OR tent_id IS NULL'
        ))['cnt'] ?? 0);
        $hajjCount = (int) (($this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM ATTENDEES WHERE campaign_type = 'Hajj'"
        ))['cnt'] ?? 0);
        $umrahCount = (int) (($this->db->queryOne(
            "SELECT COUNT(*) AS cnt FROM ATTENDEES WHERE campaign_type = 'Umrah'"
        ))['cnt'] ?? 0);

        return [
            'total_attendees'        => $totalAtt,
            'total_buses'            => $totalBus,
            'out_of_service_buses'   => $oosBus,
            'total_drivers'          => $totalDrv,
            'out_of_service_drivers' => $oosDrv,
            'total_tents'            => $totalTent,
            'unassigned'             => $unassigned,
            'hajj_count'             => $hajjCount,
            'umrah_count'            => $umrahCount,
        ];
    }

    public function formatCsv(array $headers, array $rows): string
    {
        $fp = fopen('php://temp', 'r+');
        fputcsv($fp, $headers);
        foreach ($rows as $row) {
            fputcsv($fp, $row);
        }
        rewind($fp);
        $csv = (string) stream_get_contents($fp);
        fclose($fp);
        return $csv;
    }
}
