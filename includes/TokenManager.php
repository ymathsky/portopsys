<?php
/**
 * Token Management Class
 * Handles all token-related operations
 */

require_once __DIR__ . '/../config/config.php';

class TokenManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Generate a new token
     */
    public function generateToken($serviceCategoryId, $priorityType = 'regular', $customerData = [], $vesselId = null, $bookingData = []) {
        try {
            // Get service category details
            $stmt = $this->db->prepare("SELECT code, avg_service_time FROM service_categories WHERE id = ? AND is_active = 1");
            $stmt->execute([$serviceCategoryId]);
            $service = $stmt->fetch();
            
            if (!$service) {
                throw new Exception("Invalid service category");
            }
            
            // Acquire named lock to prevent race conditions on per-day token numbering
            $lockName = 'tok_seq_' . md5($service['code'] . date('Y-m-d'));
            $this->db->query("SELECT GET_LOCK('{$lockName}', 10)");
            try {
                // Generate token number (sequential, no gaps)
                $tokenNumber = $this->generateTokenNumber($service['code']);

                // Calculate queue position and estimated wait time
                $queuePosition     = $this->getQueuePosition($serviceCategoryId, $priorityType);
                $estimatedWaitTime = $this->calculateEstimatedWaitTime($serviceCategoryId, $priorityType);

                // Insert token
                $stmt = $this->db->prepare("
                    INSERT INTO tokens (
                        token_number, service_category_id, vessel_id, customer_name, customer_mobile,
                        customer_email, priority_type, queue_position, estimated_wait_time,
                        schedule_id, booking_type, fare_paid, passenger_count
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $tokenNumber,
                    $serviceCategoryId,
                    $vesselId,
                    $customerData['name']    ?? null,
                    $customerData['mobile']  ?? null,
                    $customerData['email']   ?? null,
                    $priorityType,
                    $queuePosition,
                    $estimatedWaitTime,
                    $bookingData['schedule_id']     ?? null,
                    $bookingData['booking_type']    ?? 'walkin',
                    $bookingData['fare_paid']       ?? 0,
                    $bookingData['passenger_count'] ?? 1,
                ]);

                $tokenId = $this->db->lastInsertId();
            } finally {
                $this->db->query("SELECT RELEASE_LOCK('{$lockName}')");
            }
            
            // Log to history
            $this->logTokenHistory($tokenId, null, 'waiting', null, 'System');
            
            // Send notification if enabled and contact info provided
            if (!empty($customerData['mobile']) && SMS_ENABLED) {
                $this->sendNotification($tokenId, 'sms', $customerData['mobile']);
            }
            
            return [
                'token_id' => $tokenId,
                'token_number' => $tokenNumber,
                'queue_position' => $queuePosition,
                'estimated_wait_time' => $estimatedWaitTime
            ];
            
        } catch (Exception $e) {
            throw new Exception("Error generating token: " . $e->getMessage());
        }
    }
    
    /**
     * Generate unique token number
     */
    private function generateTokenNumber($serviceCode) {
        // Use MAX() to find the highest sequence number used today for this code.
        // The caller holds a GET_LOCK, so this is race-condition-free.
        $stmt = $this->db->prepare("
            SELECT COALESCE(
                MAX(CAST(SUBSTRING_INDEX(token_number, '-', -1) AS UNSIGNED)),
                0
            ) AS max_seq
            FROM tokens
            WHERE token_number LIKE CONCAT(?, '-%')
            AND DATE(issued_at) = CURDATE()
        ");
        $stmt->execute([$serviceCode]);
        $result  = $stmt->fetch();
        $nextNum = (int)($result['max_seq'] ?? 0) + 1;
        return $serviceCode . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Calculate queue position based on priority
     */
    private function getQueuePosition($serviceCategoryId, $priorityType) {
        $priorityWeight = PRIORITY_WEIGHTS[$priorityType] ?? 50;
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM tokens t
            INNER JOIN service_categories sc ON t.service_category_id = sc.id
            WHERE t.status = 'waiting'
            AND t.service_category_id = ?
        ");
        $stmt->execute([$serviceCategoryId]);
        $result = $stmt->fetch();
        
        return $result['count'] + 1;
    }
    
    /**
     * Calculate estimated wait time
     */
    private function calculateEstimatedWaitTime($serviceCategoryId, $priorityType) {
        // Get average service time
        $stmt = $this->db->prepare("SELECT avg_service_time FROM service_categories WHERE id = ?");
        $stmt->execute([$serviceCategoryId]);
        $service = $stmt->fetch();
        $avgServiceTime = $service['avg_service_time'] ?? 10;
        
        // Count waiting tokens with higher or equal priority
        $priorityWeight = PRIORITY_WEIGHTS[$priorityType] ?? 50;
        
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count
            FROM tokens
            WHERE status IN ('waiting', 'called')
            AND service_category_id = ?
        ");
        $stmt->execute([$serviceCategoryId]);
        $result = $stmt->fetch();
        
        $waitingCount = $result['count'];
        
        // Get active counters for this service
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT cs.counter_id) as count
            FROM counter_services cs
            INNER JOIN service_counters sc ON cs.counter_id = sc.id
            WHERE cs.service_category_id = ?
            AND sc.is_active = 1
            AND sc.current_status IN ('available', 'serving')
        ");
        $stmt->execute([$serviceCategoryId]);
        $result = $stmt->fetch();
        $activeCounters = max($result['count'], 1);
        
        // Calculate estimated time
        $estimatedTime = ($waitingCount / $activeCounters) * $avgServiceTime;
        
        return round($estimatedTime);
    }
    
    /**
     * Get token details
     */
    public function getToken($tokenId) {
        $stmt = $this->db->prepare("
            SELECT t.*, sc.name as service_name, sc.code as service_code,
                   c.counter_number, c.counter_name
            FROM tokens t
            INNER JOIN service_categories sc ON t.service_category_id = sc.id
            LEFT JOIN service_counters c ON t.counter_id = c.id
            WHERE t.id = ?
        ");
        $stmt->execute([$tokenId]);
        return $stmt->fetch();
    }
    
    /**
     * Get token by token number
     */
    public function getTokenByNumber($tokenNumber) {
        $stmt = $this->db->prepare("
            SELECT t.*, sc.name as service_name, sc.code as service_code,
                   c.counter_number, c.counter_name
            FROM tokens t
            INNER JOIN service_categories sc ON t.service_category_id = sc.id
            LEFT JOIN service_counters c ON t.counter_id = c.id
            WHERE t.token_number = ?
            ORDER BY t.issued_at DESC
            LIMIT 1
        ");
        $stmt->execute([$tokenNumber]);
        return $stmt->fetch();
    }
    
    /**
     * Call a specific token to a counter (for QR code scanning)
     */
    public function callSpecificToken($tokenId, $counterId) {
        try {
            $this->db->beginTransaction();
            
            // Get token details
            $token = $this->getToken($tokenId);
            
            if (!$token) {
                throw new Exception("Token not found");
            }
            
            if ($token['status'] !== 'waiting' && $token['status'] !== 'pending') {
                throw new Exception("Token is not in waiting status");
            }
            
            // Update token status
            $stmt = $this->db->prepare("
                UPDATE tokens 
                SET status = 'called',
                    counter_id = ?,
                    called_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$counterId, $tokenId]);
            
            // Log to history
            $this->logTokenHistory($tokenId, $counterId, 'called', null, $_SESSION['full_name'] ?? 'Staff');
            
            $this->db->commit();
            
            return $this->getToken($tokenId);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Call next token for a specific counter
     */
    public function callNextToken($counterId) {
        try {
            $this->db->beginTransaction();
            
            // Get counter details and available services
            $stmt = $this->db->prepare("
                SELECT cs.service_category_id
                FROM counter_services cs
                WHERE cs.counter_id = ?
            ");
            $stmt->execute([$counterId]);
            $serviceIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($serviceIds)) {
                throw new Exception("No services assigned to this counter");
            }
            
            // Find next waiting token with priority
            $placeholders = str_repeat('?,', count($serviceIds) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT t.id
                FROM tokens t
                INNER JOIN service_categories sc ON t.service_category_id = sc.id
                WHERE t.status = 'waiting'
                AND t.service_category_id IN ($placeholders)
                ORDER BY 
                    CASE t.priority_type
                        WHEN 'emergency' THEN 1
                        WHEN 'urgent' THEN 1
                        WHEN 'senior' THEN 2
                        WHEN 'pwd' THEN 2
                        WHEN 'pregnant' THEN 2
                        WHEN 'student' THEN 3
                        ELSE 4
                    END,
                    sc.priority_level DESC,
                    t.issued_at ASC
                LIMIT 1
            ");
            $stmt->execute($serviceIds);
            $nextToken = $stmt->fetch();
            
            if (!$nextToken) {
                $this->db->rollBack();
                return null;
            }
            
            // Update token status to 'called'
            $stmt = $this->db->prepare("
                UPDATE tokens 
                SET status = 'called', counter_id = ?, called_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$counterId, $nextToken['id']]);
            
            // Update counter status
            $stmt = $this->db->prepare("
                UPDATE service_counters 
                SET current_status = 'serving', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$counterId]);
            
            // Log history
            $this->logTokenHistory($nextToken['id'], 'waiting', 'called', $counterId, $_SESSION['username'] ?? 'System');
            
            $this->db->commit();
            
            // Get full token details
            return $this->getToken($nextToken['id']);
            
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error calling next token: " . $e->getMessage());
        }
    }
    
    /**
     * Start serving a token
     */
    public function startServing($tokenId, $counterId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tokens 
                SET status = 'serving', counter_id = ?, serving_at = NOW(),
                    actual_wait_time = TIMESTAMPDIFF(MINUTE, issued_at, NOW())
                WHERE id = ? AND status = 'called'
            ");
            $stmt->execute([$counterId, $tokenId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Token not in 'called' status");
            }
            
            $this->logTokenHistory($tokenId, 'called', 'serving', $counterId, $_SESSION['username'] ?? 'System');
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error starting service: " . $e->getMessage());
        }
    }
    
    /**
     * Complete a token service
     */
    public function completeToken($tokenId, $notes = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tokens 
                SET status = 'completed', completed_at = NOW(),
                    service_duration = TIMESTAMPDIFF(MINUTE, serving_at, NOW()),
                    notes = ?
                WHERE id = ? AND status = 'serving'
            ");
            $stmt->execute([$notes, $tokenId]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Token not in 'serving' status");
            }
            
            // Get token details for counter update
            $token = $this->getToken($tokenId);
            
            // Update counter to available
            if ($token['counter_id']) {
                $stmt = $this->db->prepare("
                    UPDATE service_counters 
                    SET current_status = 'available', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$token['counter_id']]);
            }
            
            $this->logTokenHistory($tokenId, 'serving', 'completed', $token['counter_id'], $_SESSION['username'] ?? 'System');
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error completing token: " . $e->getMessage());
        }
    }
    
    /**
     * Cancel a token
     */
    public function cancelToken($tokenId, $reason = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE tokens 
                SET status = 'cancelled', notes = ?
                WHERE id = ? AND status IN ('waiting', 'called')
            ");
            $stmt->execute([$reason, $tokenId]);
            
            $this->logTokenHistory($tokenId, null, 'cancelled', null, $_SESSION['username'] ?? 'Customer');
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error cancelling token: " . $e->getMessage());
        }
    }
    
    /**
     * Mark token as no-show
     */
    public function markNoShow($tokenId) {
        try {
            $token = $this->getToken($tokenId);
            
            $stmt = $this->db->prepare("
                UPDATE tokens 
                SET status = 'no_show'
                WHERE id = ? AND status = 'called'
            ");
            $stmt->execute([$tokenId]);
            
            // Free up the counter
            if ($token['counter_id']) {
                $stmt = $this->db->prepare("
                    UPDATE service_counters 
                    SET current_status = 'available', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$token['counter_id']]);
            }
            
            $this->logTokenHistory($tokenId, 'called', 'no_show', $token['counter_id'], $_SESSION['username'] ?? 'System');
            
            return true;
        } catch (Exception $e) {
            throw new Exception("Error marking no-show: " . $e->getMessage());
        }
    }
    
    /**
     * Get waiting queue
     */
    public function getWaitingQueue($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT * FROM active_queue 
            WHERE status = 'waiting'
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get currently serving tokens
     */
    public function getCurrentlyServing() {
        $stmt = $this->db->query("
            SELECT * FROM active_queue 
            WHERE status IN ('called', 'serving')
            ORDER BY called_at DESC
        ");
        return $stmt->fetchAll();
    }
    
    /**
     * Log token history
     */
    private function logTokenHistory($tokenId, $statusFrom, $statusTo, $counterId, $changedBy) {
        $stmt = $this->db->prepare("
            INSERT INTO token_history (token_id, status_from, status_to, counter_id, changed_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$tokenId, $statusFrom, $statusTo, $counterId, $changedBy]);
    }
    
    /**
     * Send notification
     */
    private function sendNotification($tokenId, $type, $recipient) {
        $token = $this->getToken($tokenId);
        
        $message = "Your token {$token['token_number']} for {$token['service_name']} has been generated. ";
        $message .= "Estimated wait time: {$token['estimated_wait_time']} minutes. ";
        $message .= "Queue position: {$token['queue_position']}";
        
        $stmt = $this->db->prepare("
            INSERT INTO notifications (token_id, notification_type, recipient, message)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$tokenId, $type, $recipient, $message]);
        
        // Here you would integrate with actual SMS/Email service
        // For now, just log it
    }
    
    /**
     * Get statistics for today
     */
    public function getTodayStatistics() {
        $stmt = $this->db->query("
            SELECT 
                COUNT(*) as total_tokens,
                COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting,
                COUNT(CASE WHEN status = 'serving' THEN 1 END) as serving,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
                COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show,
                AVG(CASE WHEN actual_wait_time IS NOT NULL THEN actual_wait_time END) as avg_wait_time,
                AVG(CASE WHEN service_duration IS NOT NULL THEN service_duration END) as avg_service_time
            FROM tokens
            WHERE DATE(issued_at) = CURDATE()
        ");
        return $stmt->fetch();
    }

    /**
     * Hourly token breakdown for today
     */
    public function getHourlyStats() {
        $stmt = $this->db->query("
            SELECT HOUR(issued_at) as hour, COUNT(*) as count
            FROM tokens
            WHERE DATE(issued_at) = CURDATE()
            GROUP BY HOUR(issued_at)
            ORDER BY hour ASC
        ");
        $rows = $stmt->fetchAll();
        // Fill all 24 hours with 0
        $hourly = array_fill(0, 24, 0);
        foreach ($rows as $r) {
            $hourly[(int)$r['hour']] = (int)$r['count'];
        }
        return $hourly;
    }

    /**
     * 7-day token volume trend
     */
    public function getWeeklyTrend() {
        $stmt = $this->db->query("
            SELECT DATE(issued_at) as day, COUNT(*) as count
            FROM tokens
            WHERE issued_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
            GROUP BY DATE(issued_at)
            ORDER BY day ASC
        ");
        $rows = $stmt->fetchAll();
        $map = [];
        foreach ($rows as $r) $map[$r['day']] = (int)$r['count'];

        $labels = [];
        $data   = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('M j', strtotime($date));
            $data[]   = $map[$date] ?? 0;
        }
        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Per-counter performance today
     */
    public function getCounterPerformance() {
        $stmt = $this->db->query("
            SELECT
                sc.counter_name,
                sc.counter_number,
                COUNT(t.id) as tokens_handled,
                AVG(t.service_duration) as avg_service_time,
                COUNT(CASE WHEN t.status = 'no_show' THEN 1 END) as no_shows
            FROM service_counters sc
            LEFT JOIN tokens t ON sc.id = t.counter_id AND DATE(t.issued_at) = CURDATE()
            WHERE sc.is_active = 1
            GROUP BY sc.id, sc.counter_name, sc.counter_number
            ORDER BY tokens_handled DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Outcome breakdown today (for doughnut chart)
     */
    public function getOutcomeBreakdown() {
        $stats = $this->getTodayStatistics();
        return [
            'completed' => (int)($stats['completed'] ?? 0),
            'cancelled' => (int)($stats['cancelled'] ?? 0),
            'no_show'   => (int)($stats['no_show'] ?? 0),
            'waiting'   => (int)($stats['waiting'] ?? 0),
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  RECALL — re-call a token that was already called
    // ─────────────────────────────────────────────────────────────

    /**
     * Recall a token (set it back to 'called', increment recall_count)
     */
    public function recallToken($tokenId) {
        try {
            $token = $this->getToken($tokenId);
            if (!$token) {
                throw new Exception("Token not found");
            }
            if (!in_array($token['status'], ['called', 'no_show'])) {
                throw new Exception("Only called or no-show tokens can be recalled");
            }

            $stmt = $this->db->prepare("
                UPDATE tokens
                SET status       = 'called',
                    called_at    = NOW(),
                    serving_at   = NULL,
                    recall_count = recall_count + 1
                WHERE id = ?
            ");
            $stmt->execute([$tokenId]);

            $this->logTokenHistory($tokenId, $token['status'], 'called', $token['counter_id'], $_SESSION['username'] ?? 'Staff');

            return $this->getToken($tokenId);
        } catch (Exception $e) {
            throw new Exception("Error recalling token: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  TRANSFER — move a token to a different counter
    // ─────────────────────────────────────────────────────────────

    /**
     * Transfer a token to a different counter
     */
    public function transferToken($tokenId, $newCounterId) {
        try {
            $this->db->beginTransaction();

            $token = $this->getToken($tokenId);
            if (!$token) {
                throw new Exception("Token not found");
            }
            if (!in_array($token['status'], ['called', 'serving', 'waiting'])) {
                throw new Exception("Token cannot be transferred in its current status");
            }

            // Verify target counter exists and is active
            $stmt = $this->db->prepare("SELECT id, counter_number, current_status FROM service_counters WHERE id = ? AND is_active = 1");
            $stmt->execute([$newCounterId]);
            $newCounter = $stmt->fetch();
            if (!$newCounter) {
                throw new Exception("Target counter not found or inactive");
            }

            $oldCounterId = $token['counter_id'];

            // Update token's counter assignment
            $stmt = $this->db->prepare("
                UPDATE tokens
                SET counter_id = ?,
                    called_at  = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$newCounterId, $tokenId]);

            // Free the old counter if it was serving this token
            if ($oldCounterId && $token['status'] === 'serving') {
                $stmt = $this->db->prepare("
                    UPDATE service_counters
                    SET current_status = 'available', updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$oldCounterId]);
            }

            $this->logTokenHistory(
                $tokenId,
                $token['status'],
                $token['status'],   // status doesn't change, only counter
                $newCounterId,
                ($_SESSION['username'] ?? 'Staff') . ' (transfer from ctr ' . ($oldCounterId ?? 'none') . ')'
            );

            $this->db->commit();
            return $this->getToken($tokenId);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error transferring token: " . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  PRE-BOOKING / RESERVATION
    // ─────────────────────────────────────────────────────────────

    /**
     * Generate a unique reservation code like RES-A3X9K2
     */
    private function generateReservationCode() {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // omit I/O/0/1 for readability
        do {
            $code = 'RES-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $stmt = $this->db->prepare("SELECT id FROM tokens WHERE reservation_code = ? LIMIT 1");
            $stmt->execute([$code]);
        } while ($stmt->fetch()); // retry on collision
        return $code;
    }

    /**
     * Create an advance reservation token (status = 'pending')
     */
    public function createReservation($serviceCategoryId, $priorityType = 'regular', $customerData = [], $vesselId = null, $bookingData = []) {
        try {
            // Get service category
            $stmt = $this->db->prepare("SELECT code FROM service_categories WHERE id = ? AND is_active = 1");
            $stmt->execute([$serviceCategoryId]);
            $service = $stmt->fetch();
            if (!$service) {
                throw new Exception("Invalid service category");
            }

            // Acquire named lock to prevent race conditions on token numbering
            $lockName = 'tok_seq_' . md5($service['code'] . date('Y-m-d'));
            $this->db->query("SELECT GET_LOCK('{$lockName}', 10)");
            $tokenNumber = $this->generateTokenNumber($service['code']);
            $this->db->query("SELECT RELEASE_LOCK('{$lockName}')");

            $reservationCode = $this->generateReservationCode();
            $reservedDate    = $bookingData['reserved_for_date'] ?? date('Y-m-d', strtotime('+1 day'));

            $stmt = $this->db->prepare("
                INSERT INTO tokens (
                    token_number, service_category_id, vessel_id,
                    customer_name, customer_mobile, customer_email,
                    priority_type, status, queue_position, estimated_wait_time,
                    schedule_id, booking_type, fare_paid, passenger_count,
                    reservation_code, reserved_for_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', 0, 0, ?, 'prebooked', ?, ?, ?, ?)
            ");
            $stmt->execute([
                $tokenNumber,
                $serviceCategoryId,
                $vesselId,
                $customerData['name']   ?? null,
                $customerData['mobile'] ?? null,
                $customerData['email']  ?? null,
                $priorityType,
                $bookingData['schedule_id']       ?? null,
                $bookingData['fare_paid']         ?? 0,
                $bookingData['passenger_count']   ?? 1,
                $reservationCode,
                $reservedDate,
            ]);

            $tokenId = $this->db->lastInsertId();
            $this->logTokenHistory($tokenId, null, 'pending', null, 'Customer');

            return [
                'token_id'         => $tokenId,
                'token_number'     => $tokenNumber,
                'reservation_code' => $reservationCode,
                'reserved_for_date'=> $reservedDate,
            ];
        } catch (Exception $e) {
            throw new Exception("Error creating reservation: " . $e->getMessage());
        }
    }

    /**
     * Look up a reservation by its code
     */
    public function getReservationByCode($code) {
        $stmt = $this->db->prepare("
            SELECT t.*,
                   sc.name  AS service_name,
                   sc.code  AS service_code,
                   v.name   AS vessel_name,
                   v.vessel_type,
                   s.departure_time,
                   s.arrival_time,
                   r.name   AS route_name
            FROM   tokens t
            INNER  JOIN service_categories sc ON t.service_category_id = sc.id
            LEFT   JOIN vessels            v  ON t.vessel_id            = v.id
            LEFT   JOIN schedules          s  ON t.schedule_id          = s.id
            LEFT   JOIN routes             r  ON s.route_id             = r.id
            WHERE  t.reservation_code = ?
            LIMIT  1
        ");
        $stmt->execute([$code]);
        return $stmt->fetch();
    }

    /**
     * Redeem a reservation — converts it to an active 'waiting' token
     */
    public function redeemReservation($code) {
        try {
            $this->db->beginTransaction();

            $reservation = $this->getReservationByCode($code);
            if (!$reservation) {
                throw new Exception("Reservation not found");
            }
            if ($reservation['status'] !== 'pending') {
                throw new Exception("Reservation has already been redeemed or cancelled");
            }

            // Recalculate queue position now that they're actually arriving
            $queuePos   = $this->getQueuePosition($reservation['service_category_id'], $reservation['priority_type']);
            $waitMinutes = $this->calculateEstimatedWaitTime($reservation['service_category_id'], $reservation['priority_type']);

            $stmt = $this->db->prepare("
                UPDATE tokens
                SET status              = 'waiting',
                    queue_position      = ?,
                    estimated_wait_time = ?,
                    issued_at           = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$queuePos, $waitMinutes, $reservation['id']]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Reservation could not be redeemed — it may have already been used");
            }

            $this->logTokenHistory($reservation['id'], 'pending', 'waiting', null, 'Customer');
            $this->db->commit();

            return $this->getToken($reservation['id']);
        } catch (Exception $e) {
            $this->db->rollBack();
            throw new Exception("Error redeeming reservation: " . $e->getMessage());
        }
    }
}
