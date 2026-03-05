<?php
/**
 * Port Manager Class
 * Handles Vessels and Schedules
 */

require_once __DIR__ . '/../config/database.php';

class PortManager {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // --- VESSEL METHODS ---

    public function getAllVessels() {
        $stmt = $this->db->query("SELECT * FROM vessels ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function getVesselById($id) {
        $stmt = $this->db->prepare("SELECT * FROM vessels WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function addVessel($name, $type, $regNum, $owner, $contact, $maxCapacity = 0) {
        $stmt = $this->db->prepare("
            INSERT INTO vessels (name, type, max_capacity, registration_number, owner_name, contact_number)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$name, $type, $maxCapacity, $regNum, $owner, $contact]);
    }

    public function updateVessel($id, $name, $type, $regNum, $owner, $contact, $maxCapacity = 0) {
        $stmt = $this->db->prepare("
            UPDATE vessels 
            SET name = ?, type = ?, max_capacity = ?, registration_number = ?, owner_name = ?, contact_number = ?
            WHERE id = ?
        ");
        return $stmt->execute([$name, $type, $maxCapacity, $regNum, $owner, $contact, $id]);
    }

    public function deleteVessel($id) {
        $stmt = $this->db->prepare("DELETE FROM vessels WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // --- SCHEDULE METHODS ---

    public function getAllSchedules() {
        $stmt = $this->db->query("
            SELECT s.*, v.name as vessel_name, v.type as vessel_type
            FROM vessel_schedules s
            LEFT JOIN vessels v ON s.vessel_id = v.id
            ORDER BY s.departure_time DESC
        ");
        return $stmt->fetchAll();
    }

    public function getScheduleById($id) {
        $stmt = $this->db->prepare("SELECT * FROM vessel_schedules WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function addSchedule($vesselId, $tripNum, $origin, $destination, $deptTime, $arrTime, $status) {
        $stmt = $this->db->prepare("
            INSERT INTO vessel_schedules (vessel_id, trip_number, origin, destination, departure_time, arrival_time, status)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$vesselId, $tripNum, $origin, $destination, $deptTime, $arrTime, $status]);
    }

    public function updateSchedule($id, $vesselId, $tripNum, $origin, $destination, $deptTime, $arrTime, $status) {
        $stmt = $this->db->prepare("
            UPDATE vessel_schedules 
            SET vessel_id = ?, trip_number = ?, origin = ?, destination = ?, departure_time = ?, arrival_time = ?, status = ?
            WHERE id = ?
        ");
        return $stmt->execute([$vesselId, $tripNum, $origin, $destination, $deptTime, $arrTime, $status, $id]);
    }

    public function deleteSchedule($id) {
        $stmt = $this->db->prepare("DELETE FROM vessel_schedules WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // --- STANDARD SCHEDULE METHODS ---

    public function getAllStandardSchedules() {
        $stmt = $this->db->query("
            SELECT s.*, v.name as vessel_name, v.type as vessel_type
            FROM standard_schedules s
            LEFT JOIN vessels v ON s.vessel_id = v.id
            ORDER BY s.schedule_name ASC
        ");
        return $stmt->fetchAll();
    }

    public function getStandardScheduleById($id) {
        $stmt = $this->db->prepare("SELECT * FROM standard_schedules WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function addStandardSchedule($data) {
        $stmt = $this->db->prepare("
            INSERT INTO standard_schedules 
            (vessel_id, schedule_name, trip_number_prefix, origin, destination, departure_time, arrival_time, fare,
             monday, tuesday, wednesday, thursday, friday, saturday, sunday, 
             is_active, effective_from, effective_until, notes, capacity_per_trip)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['vessel_id'], $data['schedule_name'], $data['trip_number_prefix'],
            $data['origin'], $data['destination'], $data['departure_time'], $data['arrival_time'], $data['fare'] ?? 0.00,
            $data['monday'] ?? 0, $data['tuesday'] ?? 0, $data['wednesday'] ?? 0, 
            $data['thursday'] ?? 0, $data['friday'] ?? 0, $data['saturday'] ?? 0, $data['sunday'] ?? 0,
            $data['is_active'] ?? 1, $data['effective_from'], $data['effective_until'], $data['notes'],
            $data['capacity_per_trip'] ?? null
        ]);
    }

    public function updateStandardSchedule($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE standard_schedules 
            SET vessel_id = ?, schedule_name = ?, trip_number_prefix = ?, origin = ?, destination = ?, 
                departure_time = ?, arrival_time = ?, fare = ?,
                monday = ?, tuesday = ?, wednesday = ?, thursday = ?, friday = ?, saturday = ?, sunday = ?,
                is_active = ?, effective_from = ?, effective_until = ?, notes = ?, capacity_per_trip = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['vessel_id'], $data['schedule_name'], $data['trip_number_prefix'],
            $data['origin'], $data['destination'], $data['departure_time'], $data['arrival_time'], $data['fare'] ?? 0.00,
            $data['monday'] ?? 0, $data['tuesday'] ?? 0, $data['wednesday'] ?? 0, 
            $data['thursday'] ?? 0, $data['friday'] ?? 0, $data['saturday'] ?? 0, $data['sunday'] ?? 0,
            $data['is_active'] ?? 1, $data['effective_from'], $data['effective_until'], $data['notes'],
            $data['capacity_per_trip'] ?? null,
            $id
        ]);
    }

    public function deleteStandardSchedule($id) {
        $stmt = $this->db->prepare("DELETE FROM standard_schedules WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getSchedulesByDay($dayOfWeek) {
        // $dayOfWeek: 'monday', 'tuesday', etc.
        $stmt = $this->db->prepare("
            SELECT s.*, v.name as vessel_name, v.type as vessel_type
            FROM standard_schedules s
            LEFT JOIN vessels v ON s.vessel_id = v.id
            WHERE s.$dayOfWeek = 1 AND s.is_active = 1
            AND (s.effective_from <= CURDATE())
            AND (s.effective_until IS NULL OR s.effective_until >= CURDATE())
            ORDER BY s.departure_time ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // --- SCHEDULE EXCEPTION METHODS ---

    public function getAllScheduleExceptions() {
        $stmt = $this->db->query("
            SELECT e.*, 
                   v.name as vessel_name, v.type as vessel_type,
                   s.schedule_name as standard_schedule_name
            FROM schedule_exceptions e
            LEFT JOIN vessels v ON e.vessel_id = v.id
            LEFT JOIN standard_schedules s ON e.standard_schedule_id = s.id
            ORDER BY e.exception_date DESC
        ");
        return $stmt->fetchAll();
    }

    public function addScheduleException($data) {
        $stmt = $this->db->prepare("
            INSERT INTO schedule_exceptions 
            (standard_schedule_id, vessel_id, exception_type, exception_date, trip_number, 
             origin, destination, departure_time, arrival_time, status, reason, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['standard_schedule_id'], $data['vessel_id'], $data['exception_type'],
            $data['exception_date'], $data['trip_number'],
            $data['origin'], $data['destination'], $data['departure_time'], $data['arrival_time'],
            $data['status'], $data['reason'], $data['notes']
        ]);
    }

    public function updateScheduleException($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE schedule_exceptions 
            SET standard_schedule_id = ?, vessel_id = ?, exception_type = ?, exception_date = ?, 
                trip_number = ?, origin = ?, destination = ?, departure_time = ?, arrival_time = ?,
                status = ?, reason = ?, notes = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['standard_schedule_id'], $data['vessel_id'], $data['exception_type'],
            $data['exception_date'], $data['trip_number'],
            $data['origin'], $data['destination'], $data['departure_time'], $data['arrival_time'],
            $data['status'], $data['reason'], $data['notes'],
            $id
        ]);
    }

    public function deleteScheduleException($id) {
        $stmt = $this->db->prepare("DELETE FROM schedule_exceptions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getExceptionsByDate($date) {
        $stmt = $this->db->prepare("
            SELECT e.*, v.name as vessel_name, v.type as vessel_type
            FROM schedule_exceptions e
            LEFT JOIN vessels v ON e.vessel_id = v.id
            WHERE e.exception_date = ?
            ORDER BY e.departure_time ASC
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    // --- VESSEL SERVICE METHODS ---

    public function getVesselServices($vesselId) {
        $stmt = $this->db->prepare("
            SELECT sc.*, vs.price AS vessel_price, vs.notes AS vessel_notes,
                   COALESCE(NULLIF(vs.price, 0), sc.base_price, 0) AS effective_price
            FROM service_categories sc
            INNER JOIN vessel_services vs ON sc.id = vs.service_category_id
            WHERE vs.vessel_id = ? AND sc.is_active = 1
            ORDER BY sc.name ASC
        ");
        $stmt->execute([$vesselId]);
        return $stmt->fetchAll();
    }

    public function addVesselService($vesselId, $serviceCategoryId) {
        $stmt = $this->db->prepare("
            INSERT INTO vessel_services (vessel_id, service_category_id)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE vessel_id = vessel_id
        ");
        return $stmt->execute([$vesselId, $serviceCategoryId]);
    }

    public function removeVesselService($vesselId, $serviceCategoryId) {
        $stmt = $this->db->prepare("
            DELETE FROM vessel_services 
            WHERE vessel_id = ? AND service_category_id = ?
        ");
        return $stmt->execute([$vesselId, $serviceCategoryId]);
    }

    public function setVesselServices($vesselId, $serviceCategoryIds, $prices = []) {
        // Delete existing services
        $stmt = $this->db->prepare("DELETE FROM vessel_services WHERE vessel_id = ?");
        $stmt->execute([$vesselId]);

        // Add new services with prices
        if (!empty($serviceCategoryIds)) {
            $stmt = $this->db->prepare("
                INSERT INTO vessel_services (vessel_id, service_category_id, price)
                VALUES (?, ?, ?)
            ");
            foreach ($serviceCategoryIds as $serviceCategoryId) {
                $price = isset($prices[$serviceCategoryId]) ? floatval($prices[$serviceCategoryId]) : 0.00;
                $stmt->execute([$vesselId, $serviceCategoryId, $price]);
            }
        }
        return true;
    }

    /**
     * Get the configured price for a specific vessel+service combination.
     * Returns 0.00 if not set.
     */
    public function getVesselServicePrice($vesselId, $serviceCategoryId) {
        $stmt = $this->db->prepare("
            SELECT price FROM vessel_services
            WHERE vessel_id = ? AND service_category_id = ?
        ");
        $stmt->execute([$vesselId, $serviceCategoryId]);
        $row = $stmt->fetch();
        return $row ? floatval($row['price']) : 0.00;
    }
    
    /**
     * Get current passenger count for a vessel on a specific trip/schedule
     */
    public function getVesselPassengerCount($vesselId, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(passenger_count), 0) AS count 
            FROM tokens 
            WHERE vessel_id = ?
            AND DATE(issued_at) = ?
            AND status NOT IN ('cancelled')
        ");
        $stmt->execute([$vesselId, $date]);
        $result = $stmt->fetch();
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Check if vessel has available capacity
     */
    public function hasAvailableCapacity($vesselId, $date = null) {
        $vessel = $this->getVesselById($vesselId);
        if (!$vessel || $vessel['max_capacity'] <= 0) {
            return true; // No capacity limit set
        }
        
        $currentCount = $this->getVesselPassengerCount($vesselId, $date);
        return $currentCount < $vessel['max_capacity'];
    }
    
    /**
     * Get remaining capacity for a vessel
     */
    public function getRemainingCapacity($vesselId, $date = null) {
        $vessel = $this->getVesselById($vesselId);
        if (!$vessel || $vessel['max_capacity'] <= 0) {
            return null; // No capacity limit set
        }
        $currentCount = $this->getVesselPassengerCount($vesselId, $date);
        return max(0, $vessel['max_capacity'] - $currentCount);
    }

    // --- TRIP STATUS METHODS ---

    /**
     * Update the live status of a standard schedule's current trip
     */
    public function updateTripStatus($scheduleId, $status, $reason = '') {
        $stmt = $this->db->prepare("
            UPDATE standard_schedules SET trip_status = ?, delay_reason = ? WHERE id = ?
        ");
        return $stmt->execute([$status, $reason, $scheduleId]);
    }

    /**
     * Get today's active schedules with token/passenger counts
     */
    public function getTodaySchedules($date = null) {
        if (!$date) $date = date('Y-m-d');
        $dayName = strtolower(date('l', strtotime($date)));

        $stmt = $this->db->prepare("
            SELECT s.*,
                   v.name AS vessel_name, v.type AS vessel_type, v.max_capacity,
                   COUNT(t.id) AS tokens_issued,
                   COALESCE(SUM(t.passenger_count), 0) AS passengers_booked
            FROM standard_schedules s
            LEFT JOIN vessels v ON s.vessel_id = v.id
            LEFT JOIN tokens t ON t.schedule_id = s.id
                 AND DATE(t.issued_at) = ?
                 AND t.status NOT IN ('cancelled')
            WHERE s.is_active = 1
              AND s.$dayName = 1
              AND (s.effective_from <= ?)
              AND (s.effective_until IS NULL OR s.effective_until >= ?)
            GROUP BY s.id
            ORDER BY s.departure_time ASC
        ");
        $stmt->execute([$date, $date, $date]);
        return $stmt->fetchAll();
    }

    /**
     * Get remaining capacity for a specific schedule on a date
     */
    public function getScheduleRemainingCapacity($scheduleId, $date = null) {
        if (!$date) $date = date('Y-m-d');
        $stmt = $this->db->prepare("
            SELECT s.capacity_per_trip,
                   COALESCE(SUM(t.passenger_count), 0) AS booked
            FROM standard_schedules s
            LEFT JOIN tokens t ON t.schedule_id = s.id
                 AND DATE(t.issued_at) = ?
                 AND t.status NOT IN ('cancelled')
            WHERE s.id = ?
            GROUP BY s.id
        ");
        $stmt->execute([$date, $scheduleId]);
        $row = $stmt->fetch();
        if (!$row || !$row['capacity_per_trip']) return null;
        return max(0, $row['capacity_per_trip'] - $row['booked']);
    }

    /**
     * Check if a schedule is fully booked on a given date
     */
    public function isScheduleFullyBooked($scheduleId, $date = null) {
        $remaining = $this->getScheduleRemainingCapacity($scheduleId, $date);
        return ($remaining !== null && $remaining <= 0);
    }

    // --- ANNOUNCEMENT METHODS ---

    public function getActiveAnnouncements($location = 'customer') {
        $col = ($location === 'display') ? 'show_display' : 'show_customer';
        $stmt = $this->db->prepare("
            SELECT * FROM announcements
            WHERE is_active = 1 AND $col = 1
              AND (starts_at IS NULL OR starts_at <= NOW())
              AND (ends_at IS NULL OR ends_at >= NOW())
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllAnnouncements() {
        $stmt = $this->db->query("SELECT * FROM announcements ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function addAnnouncement($data) {
        $stmt = $this->db->prepare("
            INSERT INTO announcements (title, body, type, show_customer, show_display, is_active, starts_at, ends_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['title'], $data['body'], $data['type'],
            $data['show_customer'] ?? 1, $data['show_display'] ?? 1,
            $data['is_active'] ?? 1,
            !empty($data['starts_at']) ? $data['starts_at'] : null,
            !empty($data['ends_at'])   ? $data['ends_at']   : null,
            $data['created_by'] ?? null
        ]);
    }

    public function updateAnnouncement($id, $data) {
        $stmt = $this->db->prepare("
            UPDATE announcements
            SET title=?, body=?, type=?, show_customer=?, show_display=?, is_active=?, starts_at=?, ends_at=?
            WHERE id=?
        ");
        return $stmt->execute([
            $data['title'], $data['body'], $data['type'],
            $data['show_customer'] ?? 1, $data['show_display'] ?? 1,
            $data['is_active'] ?? 1,
            !empty($data['starts_at']) ? $data['starts_at'] : null,
            !empty($data['ends_at'])   ? $data['ends_at']   : null,
            $id
        ]);
    }

    public function deleteAnnouncement($id) {
        $stmt = $this->db->prepare("DELETE FROM announcements WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // --- DAILY SUMMARY REPORT ---

    public function getDailySummary($date = null) {
        if (!$date) $date = date('Y-m-d');
        // Overall totals
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_tokens,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN status = 'no_show'   THEN 1 ELSE 0 END) AS no_shows,
                SUM(CASE WHEN status = 'waiting'   THEN 1 ELSE 0 END) AS still_waiting,
                SUM(COALESCE(passenger_count,1)) AS total_passengers,
                SUM(COALESCE(fare_paid,0))        AS total_revenue,
                AVG(CASE WHEN actual_wait_time IS NOT NULL THEN actual_wait_time END) AS avg_wait_min
            FROM tokens WHERE DATE(issued_at) = ?
        ");
        $stmt->execute([$date]);
        $summary = $stmt->fetch();

        // Per-service breakdown
        $stmt = $this->db->prepare("
            SELECT sc.name AS service_name, sc.code,
                   COUNT(t.id) AS tokens,
                   SUM(COALESCE(t.passenger_count,1)) AS passengers,
                   SUM(CASE WHEN t.status='completed' THEN 1 ELSE 0 END) AS completed,
                   SUM(COALESCE(t.fare_paid,0)) AS revenue
            FROM service_categories sc
            LEFT JOIN tokens t ON t.service_category_id = sc.id AND DATE(t.issued_at) = ?
            GROUP BY sc.id
            ORDER BY tokens DESC
        ");
        $stmt->execute([$date]);
        $summary['by_service'] = $stmt->fetchAll();

        // Per-trip breakdown
        $stmt = $this->db->prepare("
            SELECT s.schedule_name, s.departure_time, s.origin, s.destination, s.trip_status,
                   v.name AS vessel_name, v.type AS vessel_type,
                   COUNT(t.id) AS tokens,
                   SUM(COALESCE(t.passenger_count,1)) AS passengers,
                   SUM(COALESCE(t.fare_paid,0)) AS revenue
            FROM standard_schedules s
            LEFT JOIN vessels v ON s.vessel_id = v.id
            LEFT JOIN tokens t ON t.schedule_id = s.id AND DATE(t.issued_at) = ?
            GROUP BY s.id
            ORDER BY s.departure_time ASC
        ");
        $stmt->execute([$date]);
        $summary['by_trip'] = $stmt->fetchAll();

        // Per-vessel revenue breakdown
        $stmt = $this->db->prepare("
            SELECT v.name AS vessel_name, v.type AS vessel_type,
                   COUNT(DISTINCT s.id) AS total_trips,
                   COUNT(t.id) AS tokens,
                   SUM(COALESCE(t.passenger_count,1)) AS passengers,
                   SUM(CASE WHEN t.status='completed' THEN 1 ELSE 0 END) AS completed,
                   SUM(COALESCE(t.fare_paid,0)) AS revenue
            FROM vessels v
            LEFT JOIN standard_schedules s ON s.vessel_id = v.id
            LEFT JOIN tokens t ON t.schedule_id = s.id AND DATE(t.issued_at) = ?
            GROUP BY v.id
            ORDER BY revenue DESC
        ");
        $stmt->execute([$date]);
        $summary['by_vessel'] = $stmt->fetchAll();

        return $summary;
    }
}
