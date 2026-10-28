<?php
// kiosk.php - Patient Registration & Self-Check-in Kiosk
$pageTitle = 'Patient Registration Kiosk';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch active services
$servicesStmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY id ASC");
$services = $servicesStmt->fetchAll();
?>

<main class="main-wrapper">
    <div class="page-header">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(14, 165, 233, 0.15); border: 1px solid var(--primary); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; color: var(--primary-light); text-transform: uppercase; margin-bottom: 0.5rem;">
                <span>🎟️</span> Patient Self-Service Kiosk
            </div>
            <h1 class="page-title">Patient Check-in & Queue Ticket</h1>
            <p class="page-subtitle">Register to receive a unique queue number and track your live waiting line status.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="display.php" target="_blank" class="btn btn-outline">
                <span>📺</span> View TV Waiting Board ↗
            </a>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1.3fr 0.9fr; gap: 1.75rem; align-items: start;">
        
        <!-- Registration Form Card -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <span>📝</span> Step 1: Patient Information & Service Selection
                </h2>
                <span class="badge badge-priority">Quick Check-in</span>
            </div>

            <form id="kioskRegisterForm" onsubmit="handleRegistration(event)">
                
                <!-- Quick Anonymous / Auto Fill option -->
                <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                    <div>
                        <strong style="font-size: 0.88rem; color: #fff;">Need quick testing?</strong>
                        <div style="font-size: 0.78rem; color: var(--text-secondary);">Populate fictional patient profile with 1-click.</div>
                    </div>
                    <button type="button" onclick="fillRandomPatient()" class="btn btn-outline btn-sm">
                        <span>🎲</span> Generate Fictional Patient
                    </button>
                </div>

                <!-- Patient Name -->
                <div class="form-group">
                    <label class="form-label" for="patientName">Patient Full Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="patientName" name="patient_name" class="form-control" placeholder="e.g. Maria Clara Santos" required>
                </div>

                <!-- Contact Phone -->
                <div class="form-group">
                    <label class="form-label" for="patientPhone">Contact Number <small style="color: var(--text-muted);">(Optional / Fictional)</small></label>
                    <input type="text" id="patientPhone" name="patient_phone" class="form-control" placeholder="e.g. +63 912 345 6789">
                </div>

                <!-- Patient Category / Priority -->
                <div class="form-group">
                    <label class="form-label">Patient Category <small style="color: var(--text-muted);">(Priority queue applied to Seniors, PWDs & Emergency)</small></label>
                    <div class="chip-grid">
                        <div class="chip-item">
                            <input type="radio" name="patient_type" id="typeRegular" value="Regular" checked>
                            <label for="typeRegular" class="chip-label">
                                <span class="chip-icon">👤</span>
                                <span class="chip-title">Regular</span>
                                <span class="chip-sub">Standard Line</span>
                            </label>
                        </div>
                        <div class="chip-item">
                            <input type="radio" name="patient_type" id="typeSenior" value="Senior">
                            <label for="typeSenior" class="chip-label">
                                <span class="chip-icon">🧓</span>
                                <span class="chip-title">Senior (60+)</span>
                                <span class="chip-sub">Priority Lane</span>
                            </label>
                        </div>
                        <div class="chip-item">
                            <input type="radio" name="patient_type" id="typePWD" value="PWD">
                            <label for="typePWD" class="chip-label">
                                <span class="chip-icon">♿</span>
                                <span class="chip-title">PWD</span>
                                <span class="chip-sub">Priority Lane</span>
                            </label>
                        </div>
                        <div class="chip-item">
                            <input type="radio" name="patient_type" id="typePregnant" value="Pregnant">
                            <label for="typePregnant" class="chip-label">
                                <span class="chip-icon">🤰</span>
                                <span class="chip-title">Pregnant</span>
                                <span class="chip-sub">Priority Lane</span>
                            </label>
                        </div>
                        <div class="chip-item">
                            <input type="radio" name="patient_type" id="typeChild" value="Child">
                            <label for="typeChild" class="chip-label">
                                <span class="chip-icon">👶</span>
                                <span class="chip-title">Pediatric</span>
                                <span class="chip-sub">Child Care</span>
                            </label>
                        </div>
                        <div class="chip-item">
                            <input type="radio" name="patient_type" id="typeEmergency" value="Emergency">
                            <label for="typeEmergency" class="chip-label" style="border-color: rgba(244, 63, 94, 0.4);">
                                <span class="chip-icon">🚨</span>
                                <span class="chip-title" style="color: var(--danger-light);">Urgent</span>
                                <span class="chip-sub">Immediate Triage</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Service / Department Required -->
                <div class="form-group">
                    <label class="form-label">Service Required <span style="color: var(--danger);">*</span></label>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem;">
                        <?php foreach ($services as $idx => $s): ?>
                            <div class="chip-item">
                                <input type="radio" name="service_id" id="service_<?= $s['id'] ?>" value="<?= $s['id'] ?>" <?= ($idx === 0) ? 'checked' : '' ?>>
                                <label for="service_<?= $s['id'] ?>" class="chip-label" style="text-align: left; align-items: flex-start; padding: 0.85rem;">
                                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; margin-bottom: 0.35rem;">
                                        <span style="font-size: 1.15rem;">
                                            <?php
                                            $icons = [
                                                'GEN' => '🩺',
                                                'PED' => '🧸',
                                                'DEN' => '🦷',
                                                'LAB' => '🧪',
                                                'VAC' => '💉',
                                                'PRI' => '⚡'
                                            ];
                                            echo $icons[$s['code']] ?? '🩺';
                                            ?>
                                        </span>
                                        <span class="badge" style="background: <?= htmlspecialchars($s['color']) ?>20; color: <?= htmlspecialchars($s['color']) ?>; border: 1px solid <?= htmlspecialchars($s['color']) ?>50;">
                                            <?= htmlspecialchars($s['prefix']) ?>-Queue
                                        </span>
                                    </div>
                                    <div class="chip-title"><?= htmlspecialchars($s['name']) ?></div>
                                    <div class="chip-sub">Est. ~<?= $s['estimated_mins'] ?> mins</div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Notes / Reason for Visit -->
                <div class="form-group">
                    <label class="form-label" for="patientNotes">Reason for Visit / Symptoms <small style="color: var(--text-muted);">(Optional)</small></label>
                    <textarea id="patientNotes" name="notes" rows="2" class="form-control" placeholder="e.g. Mild headache, blood pressure checkup"></textarea>
                </div>

                <button type="submit" id="btnSubmitTicket" class="btn btn-primary btn-lg" style="width: 100%;">
                    <span>🎟️</span> Get Queue Ticket
                </button>
            </form>
        </div>

        <!-- Sidebar: Ticket Status Tracker & Clinic Info -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- Live Ticket Tracker Card -->
            <div class="card" style="border-top: 3px solid var(--primary);">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>🔍</span> Check My Ticket Status
                    </h2>
                </div>
                <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 1rem;">
                    Already holding a ticket slip? Enter your ticket number to see your position in line.
                </p>
                <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                    <input type="text" id="trackInput" class="form-control" placeholder="e.g. A002 or S001" style="text-transform: uppercase; font-family: 'JetBrains Mono', monospace; font-weight: 700; font-size: 1.1rem;">
                    <button onclick="trackMyTicket()" class="btn btn-primary">Track</button>
                </div>

                <!-- Track Result Display Box -->
                <div id="trackResultBox" style="display: none; background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem;">
                    <!-- Injected dynamically -->
                </div>
            </div>

            <!-- Estimated Wait Time Guide Card -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <span>ℹ️</span> Clinic Waiting Guidelines
                    </h2>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6;">
                    <p style="margin-bottom: 0.75rem;">
                        🔔 <strong>Audio Announcements:</strong> Watch and listen to the TV Display Board. Your ticket number will be called with an audio chime and spoken aloud when it's your turn.
                    </p>
                    <p style="margin-bottom: 0.75rem;">
                        ⚡ <strong>Priority Lane:</strong> Senior Citizens (60+), Persons with Disabilities (PWD), and Expectant Mothers are given priority seating and expedited calling.
                    </p>
                    <p>
                        ⏳ <strong>Missed Call:</strong> If you are momentarily away when called, your number will be placed in the Skipped list and can be recalled by clinic staff once you return.
                    </p>
                </div>
            </div>

        </div>

    </div>
</main>

<!-- PRINTABLE TICKET MODAL -->
<div class="modal-backdrop" id="ticketModal">
    <div class="modal-card" style="max-width: 440px;">
        <div class="modal-header">
            <h3 class="modal-title">🎉 Ticket Issued Successfully!</h3>
            <button class="modal-close" onclick="App.closeModal('ticketModal')">&times;</button>
        </div>
        <div class="modal-body" style="padding: 1.5rem 1rem;">
            <!-- Thermal Ticket Preview Container -->
            <div class="ticket-slip-container">
                <div class="ticket-slip" id="printableTicket">
                    <div class="slip-clinic-name">CAREQUEUE CLINIC</div>
                    <div class="slip-clinic-sub">Quality Healthcare Queue System</div>
                    <div class="slip-divider"></div>
                    
                    <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; font-weight: 700;">
                        YOUR QUEUE NUMBER
                    </div>
                    <div class="slip-ticket-number" id="slipTicketNumber">A001</div>
                    <div class="slip-service-name" id="slipServiceName">General Medicine</div>
                    
                    <div style="margin: 0.5rem 0;" id="slipTypeBadgeContainer">
                        <span class="badge badge-regular" id="slipPatientType">Regular</span>
                    </div>

                    <table class="slip-info-table">
                        <tr>
                            <td>Patient Name:</td>
                            <td id="slipPatientName">Maria Santos</td>
                        </tr>
                        <tr>
                            <td>Patients Ahead:</td>
                            <td id="slipPatientsAhead" style="color: #0284c7;">3 Ahead</td>
                        </tr>
                        <tr>
                            <td>Est. Wait Time:</td>
                            <td id="slipEstWait">~15 Mins</td>
                        </tr>
                        <tr>
                            <td>Check-in Time:</td>
                            <td id="slipCheckInTime">2026-10-21 10:30</td>
                        </tr>
                    </table>

                    <div class="slip-divider"></div>

                    <!-- Visual Barcode -->
                    <div class="slip-barcode-bars">
                        <span style="height: 38px;"></span>
                        <span style="height: 25px;"></span>
                        <span style="height: 35px; width: 5px;"></span>
                        <span style="height: 20px;"></span>
                        <span style="height: 35px;"></span>
                        <span style="height: 30px; width: 4px;"></span>
                        <span style="height: 38px;"></span>
                        <span style="height: 22px;"></span>
                        <span style="height: 36px; width: 6px;"></span>
                        <span style="height: 28px;"></span>
                        <span style="height: 38px;"></span>
                        <span style="height: 24px; width: 4px;"></span>
                        <span style="height: 38px;"></span>
                    </div>

                    <div class="slip-footer-note">
                        Please proceed to the waiting lounge and watch the TV Display Board. Thank you!
                    </div>

                    <div class="slip-cut-line"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="App.closeModal('ticketModal')">Close</button>
            <button type="button" class="btn btn-primary" onclick="App.printTicket()">
                <span>🖨️</span> Print Ticket
            </button>
        </div>
    </div>
</div>

<script>
const fictionalNames = [
    'Juan Carlos Mendoza', 'Maria Angelica Reyes', 'Danilo Fernandez',
    'Patricia Mae Ramos', 'Gabriel Sebastian Cruz', 'Lourdes Bautista',
    'Eduardo Alfonso Garcia', 'Camille Joyce Navarro', 'Mark Anthony Torres',
    'Kristine Angela Diaz', 'Rafael Dominic Santos', 'Beatrice Nicole Lim'
];

function fillRandomPatient() {
    const randomName = fictionalNames[Math.floor(Math.random() * fictionalNames.length)];
    const randomPhone = '+63 9' + Math.floor(100000000 + Math.random() * 900000000);
    document.getElementById('patientName').value = randomName;
    document.getElementById('patientPhone').value = randomPhone;
    document.getElementById('patientNotes').value = 'General consultation & health check';
    App.showToast('Fictional patient profile generated!', 'info');
}

async function handleRegistration(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSubmitTicket');
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Generating Ticket...';

    const form = document.getElementById('kioskRegisterForm');
    const formData = new FormData(form);
    formData.append('action', 'register');

    try {
        const res = await fetch('api/queue.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (data.success && data.ticket) {
            const t = data.ticket;
            // Populate printable ticket slip
            document.getElementById('slipTicketNumber').textContent = t.ticket_number;
            document.getElementById('slipServiceName').textContent = t.service_name;
            document.getElementById('slipPatientName').textContent = t.patient_name;
            document.getElementById('slipPatientType').textContent = t.patient_type;
            document.getElementById('slipPatientsAhead').textContent = t.patients_ahead + ' Patient(s) Ahead';
            document.getElementById('slipEstWait').textContent = '~' + t.estimated_wait_mins + ' Mins';
            document.getElementById('slipCheckInTime').textContent = t.check_in_time;

            // Reset form
            form.reset();

            // Open modal
            App.openModal('ticketModal');
            App.showToast(data.message, 'success');

            // Play pleasant chime
            App.playChime();
        } else {
            App.showToast(data.message || 'Registration failed', 'danger');
        }
    } catch (err) {
        console.error('Registration error:', err);
        App.showToast('Server connection error. Please try again.', 'danger');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>🎟️</span> Get Queue Ticket';
    }
}

async function trackMyTicket() {
    const input = document.getElementById('trackInput').value.trim();
    if (!input) {
        App.showToast('Please enter your ticket number', 'warning');
        return;
    }

    const box = document.getElementById('trackResultBox');
    box.style.display = 'block';
    box.innerHTML = '<div style="text-align: center; color: var(--text-muted);">Looking up ticket status...</div>';

    try {
        const res = await fetch(`api/queue.php?action=track&ticket_number=${encodeURIComponent(input)}`);
        const data = await res.json();

        if (!data.success) {
            box.innerHTML = `<div style="color: var(--danger-light); text-align: center;">❌ ${data.message}</div>`;
            return;
        }

        const t = data.ticket;
        let statusBadge = '';
        if (t.status === 'Waiting') {
            statusBadge = `<span class="badge badge-waiting">Waiting in Line</span>`;
        } else if (t.status === 'Serving') {
            statusBadge = `<span class="badge badge-serving">NOW SERVING!</span>`;
        } else if (t.status === 'Completed') {
            statusBadge = `<span class="badge badge-completed">Completed</span>`;
        } else if (t.status === 'Skipped') {
            statusBadge = `<span class="badge badge-skipped">Skipped / Recall</span>`;
        }

        let detailsHtml = '';
        if (t.status === 'Serving') {
            detailsHtml = `
                <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); border-radius: var(--radius-md); padding: 1rem; margin-top: 0.75rem; text-align: center;">
                    <div style="font-size: 0.85rem; color: var(--success-light); font-weight: 700; text-transform: uppercase;">Please Proceed Now To:</div>
                    <div style="font-size: 1.4rem; font-weight: 800; color: #fff; margin: 0.25rem 0;">${t.counter_number}</div>
                    <div style="font-size: 0.9rem; color: var(--text-secondary);">${t.counter_name} &bull; ${t.staff_name}</div>
                </div>
            `;
        } else if (t.status === 'Waiting') {
            detailsHtml = `
                <div style="margin-top: 0.75rem; font-size: 0.9rem;">
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-secondary);">Patients ahead:</span>
                        <strong style="color: #fff;">${data.ahead_count} patient(s)</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 0.35rem 0; border-bottom: 1px solid var(--border-color);">
                        <span style="color: var(--text-secondary);">Estimated wait:</span>
                        <strong style="color: var(--warning-light);">~${data.estimated_wait_mins} minutes</strong>
                    </div>
                </div>
            `;
        } else if (t.status === 'Skipped') {
            detailsHtml = `
                <div style="margin-top: 0.75rem; background: rgba(244, 63, 94, 0.1); border: 1px solid var(--danger); border-radius: var(--radius-md); padding: 0.75rem; font-size: 0.85rem; color: var(--danger-light);">
                    Your number was called but you were not present. Please approach the reception or staff station to be recalled!
                </div>
            `;
        }

        box.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                <span class="table-ticket-badge" style="font-size: 1.3rem;">${t.ticket_number}</span>
                ${statusBadge}
            </div>
            <div style="font-size: 0.95rem; font-weight: 700; color: #fff;">${t.patient_name}</div>
            <div style="font-size: 0.8rem; color: var(--text-muted);">${t.service_name} &bull; Registered at ${t.check_in_time}</div>
            ${detailsHtml}
        `;

    } catch (e) {
        box.innerHTML = `<div style="color: var(--danger-light); text-align: center;">Error fetching ticket info.</div>`;
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
