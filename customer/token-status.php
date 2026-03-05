<?php
/**
 * Token Status Check
 */

require_once __DIR__ . '/../config/config.php';

$pageTitle = 'Check Token Status';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        #qr-modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:999; align-items:center; justify-content:center; }
        #qr-modal-overlay.open { display:flex; }
        #qr-modal-box { background:#fff; border-radius:16px; padding:24px; width:90%; max-width:380px; text-align:center; }
        #qr-reader { width:100%; border-radius:10px; overflow:hidden; margin:12px 0; }
        #qr-status { font-size:13px; color:#555; min-height:20px; margin-bottom:8px; }
        .btn-scan { background:#7c3aed; color:#fff; border:none; border-radius:10px; padding:12px; font-size:15px; cursor:pointer; width:100%; margin-top:8px; }
        .btn-scan:hover { background:#6d28d9; }
        .btn-close-qr { background:#e5e7eb; color:#374151; border:none; border-radius:10px; padding:10px; font-size:14px; cursor:pointer; width:100%; margin-top:6px; }
    </style>
</head>
<body class="customer-layout">
    <div class="customer-container">
        <header class="customer-header">
            <h1><?php echo APP_NAME; ?></h1>
            <p>Check Your Token Status</p>
        </header>
        
        <div class="status-search-card">
            <form id="searchForm" onsubmit="searchToken(event)">
                <div class="form-group">
                    <label for="token_number">Enter Token Number</label>
                    <input type="text" id="token_number" name="token_number" 
                           class="form-control" placeholder="e.g., REG-0001" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Check Status</button>
                <button type="button" onclick="openQrScanner()" class="btn-scan" style="margin-top:10px;">📷 Scan QR Code</button>
            </form>
        </div>

        <!-- QR Scanner Modal -->
        <div id="qr-modal-overlay">
            <div id="qr-modal-box">
                <h3 style="margin:0 0 4px; font-size:17px;">Scan Token QR Code</h3>
                <p id="qr-status">Starting camera…</p>
                <div id="qr-reader"></div>
                <button class="btn-close-qr" onclick="closeQrScanner()">✕ Cancel</button>
            </div>
        </div>
        
        <div id="tokenStatus" class="token-status-card" style="display: none;">
            <!-- Status will be displayed here -->
        </div>
        
        <div class="customer-footer">
            <p><a href="<?php echo BASE_URL; ?>/customer/">Get New Token</a></p>
            <p><a href="<?php echo BASE_URL; ?>">Back to Home</a></p>
        </div>
    </div>
    
    <script>
    const BASE_URL = '<?php echo BASE_URL; ?>';
    let autoRefreshInterval;
    
    async function searchToken(event) {
        if (event) event.preventDefault();
        
        const tokenNumber = document.getElementById('token_number').value.trim();
        if (!tokenNumber) return;
        
        try {
            const response = await fetch(`${BASE_URL}/api/get-token.php?token_number=${encodeURIComponent(tokenNumber)}`);
            const result = await response.json();
            
            if (result.success) {
                displayTokenStatus(result.data);
                startAutoRefresh(tokenNumber);
            } else {
                document.getElementById('tokenStatus').innerHTML = `
                    <div class="alert alert-error">${result.message}</div>
                `;
                document.getElementById('tokenStatus').style.display = 'block';
            }
        } catch (error) {
            alert('Error fetching token status');
        }
    }
    
    function displayTokenStatus(token) {
        const statusCard = document.getElementById('tokenStatus');
        
        let statusColor = 'info';
        let statusMessage = '';
        
        switch(token.status) {
            case 'waiting':
                statusColor = 'warning';
                statusMessage = 'Please wait for your turn';
                break;
            case 'called':
                statusColor = 'success';
                statusMessage = `Please proceed to Counter ${token.counter_number}`;
                break;
            case 'serving':
                statusColor = 'primary';
                statusMessage = `Now being served at Counter ${token.counter_number}`;
                break;
            case 'completed':
                statusColor = 'success';
                statusMessage = 'Service completed';
                break;
            case 'cancelled':
                statusColor = 'danger';
                statusMessage = 'Token cancelled';
                break;
            case 'no_show':
                statusColor = 'danger';
                statusMessage = 'Marked as no-show';
                break;
        }
        
        statusCard.innerHTML = `
            <div class="token-status-display">
                <div class="status-header alert-${statusColor}">
                    <h2>${statusMessage}</h2>
                </div>
                
                <div class="token-number-display">${token.token_number}</div>
                
                <div class="status-details">
                    <div class="detail-item">
                        <strong>Service:</strong> ${token.service_name}
                    </div>
                    <div class="detail-item">
                        <strong>Priority:</strong> ${token.priority_type.replace('_', ' ').toUpperCase()}
                    </div>
                    <div class="detail-item">
                        <strong>Status:</strong> 
                        <span class="status-badge status-${token.status}">${token.status.toUpperCase()}</span>
                    </div>
                    ${token.counter_number ? `
                        <div class="detail-item highlight">
                            <strong>Counter:</strong> ${token.counter_number}
                        </div>
                    ` : ''}
                    ${token.queue_position ? `
                        <div class="detail-item">
                            <strong>Queue Position:</strong> #${token.queue_position}
                        </div>
                    ` : ''}
                    ${token.estimated_wait_time && token.status === 'waiting' ? `
                        <div class="detail-item">
                            <strong>Estimated Wait:</strong> ${token.estimated_wait_time} minutes
                        </div>
                    ` : ''}
                    ${token.tokens_ahead !== undefined ? `
                        <div class="detail-item">
                            <strong>Tokens Ahead:</strong> ${token.tokens_ahead}
                        </div>
                    ` : ''}
                    <div class="detail-item">
                        <strong>Issued At:</strong> ${new Date(token.issued_at).toLocaleString()}
                    </div>
                </div>
                
                ${token.status === 'waiting' || token.status === 'called' ? `
                    <div class="alert alert-info">
                        <strong>Note:</strong> This page auto-refreshes every 30 seconds. 
                        Listen for your token number announcement.
                    </div>
                ` : ''}
            </div>
        `;
        
        statusCard.style.display = 'block';
    }
    
    function startAutoRefresh(tokenNumber) {
        // Clear any existing interval
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
        
        // Refresh every 10 seconds
        autoRefreshInterval = setInterval(() => {
            fetch(`${BASE_URL}/api/get-token.php?token_number=${encodeURIComponent(tokenNumber)}`)
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        displayTokenStatus(result.data);
                        
                        // Stop refreshing if completed, cancelled, or no-show
                        if (['completed', 'cancelled', 'no_show'].includes(result.data.status)) {
                            clearInterval(autoRefreshInterval);
                        }
                    }
                })
                .catch(err => console.error('Auto-refresh error:', err));
        }, 10000);
    }
    
    // ── QR Scanner ────────────────────────────────────────────
    let qrScanner = null;

    function openQrScanner() {
        document.getElementById('qr-modal-overlay').classList.add('open');
        document.getElementById('qr-status').textContent = 'Starting camera…';

        Html5Qrcode.getCameras().then(cameras => {
            if (!cameras || cameras.length === 0) {
                document.getElementById('qr-status').textContent = 'No camera found.';
                return;
            }
            // prefer back/environment camera
            const cam = cameras.find(c => /back|environment/i.test(c.label)) || cameras[cameras.length - 1];
            qrScanner = new Html5Qrcode('qr-reader');
            qrScanner.start(
                cam.id,
                { fps: 10, qrbox: { width: 220, height: 220 } },
                (decodedText) => {
                    // Extract token number from URL or use raw text
                    let token = decodedText;
                    try {
                        const u = new URL(decodedText);
                        token = u.searchParams.get('token_number') || u.searchParams.get('token') || decodedText;
                    } catch (e) { /* not a URL, use raw */ }
                    closeQrScanner();
                    document.getElementById('token_number').value = token;
                    searchToken();
                },
                () => { /* ignore frame errors */ }
            ).then(() => {
                document.getElementById('qr-status').textContent = 'Point camera at the QR code.';
            }).catch(err => {
                document.getElementById('qr-status').textContent = 'Camera error: ' + err;
            });
        }).catch(() => {
            document.getElementById('qr-status').textContent = 'Camera permission denied.';
        });
    }

    function closeQrScanner() {
        document.getElementById('qr-modal-overlay').classList.remove('open');
        if (qrScanner) {
            qrScanner.stop().catch(() => {});
            qrScanner = null;
        }
    }

    // ── Auto-load from URL ────────────────────────────────────
    const urlParams = new URLSearchParams(window.location.search);
    const tokenNumber = urlParams.get('token_number') || urlParams.get('token');
    const tokenId = urlParams.get('token_id');
    
    if (tokenNumber) {
        document.getElementById('token_number').value = tokenNumber;
        searchToken();
    } else if (tokenId) {
        fetch(`${BASE_URL}/api/get-token.php?token_id=${tokenId}`)
            .then(res => res.json())
            .then(result => {
                if (result.success) {
                    document.getElementById('token_number').value = result.data.token_number;
                    displayTokenStatus(result.data);
                    startAutoRefresh(result.data.token_number);
                }
            });
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    });
    </script>
    
    <!-- Footer -->
    <div style="position: fixed; bottom: 10px; right: 10px; font-size: 11px; color: #666;">
        Developed by Ymath
    </div>
</body>
</html>
