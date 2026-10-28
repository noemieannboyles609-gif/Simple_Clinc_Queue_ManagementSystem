<?php
// staff.php - Clinic Staff & Doctor Calling Station
$pageTitle = 'Staff Calling Station';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch counters
$countersStmt = $pdo->query("SELECT * FROM counters WHERE status != 'Inactive' ORDER BY id ASC");
$counters = $countersStmt->fetchAll();

// Fetch services
$servicesStmt = $pdo->query("SELECT * FROM services WHERE is_active = 1 ORDER BY id ASC");
$services = $servicesStmt->fetchAll();

$defaultCounterId = intval($_GET['counter_id'] ?? ($counters[0]['id'] ?? 1));
?>

<main class="main-wrapper">
    <!-- Top Station Header & Counter Switcher -->
    <div class="page-header" style="align-items: center;">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(245, 158, 11, 0.15); border: 1px solid var(--warning); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; color: var(--warning-light); text-transform: uppercase; margin-bottom: 0.5rem;">
                <span>👨‍⚕️</span> Staff Calling & Consultation Console
            </div>
            <h1 class="page-title">Doctor & Staff Calling Station</h1>
            <p class="page-subtitle">Manage patient waiting lines, call next in queue, track consultation time, and handle skips or recalls.</p>
        </div>

        <!-- Station Selector Form -->
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.75rem 1.25rem; display: flex; align-items: center; gap: 1rem;">
            <label for="stationSelect" style="font-size: 0.85rem; font-weight: 700; color: var(--text-secondary); white-space: nowrap;">
                Operating Station:
            </label>
            <select id="stationSelect" class="form-select" style="min-width: 240px; font-weight: 600;" onchange="changeCounter(this.value)">
                <?php foreach ($counters as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($c['id'] == $defaultCounterId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['counter_number']) ?> &mdash; <?= htmlspecialchars($c['staff_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Active Console Grid -->
    <div style="display: grid; grid-template-columns: 1.15fr 1fr; gap: 1.5rem; align-items: start; margin-bottom: 2rem;">
        
        <!-- LEFT: Currently Serving & Action Controls -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            
            <!-- CURRENT SERVING CARD -->
            <div class="card" id="currentServingBox" style="border: 2px solid rgba(16, 185, 129, 0.4); background: linear-gradient(145deg, rgba(16, 185, 129, 0.08), rgba(15, 23, 42, 0.95));">
                <div class="card-header">
                    <div style="display: flex; align-items: center; gap: 0.6rem;">
                        <span class="pulse-dot"></span>
                        <h2 class="card-title" style="color: var(--success-light);">Current Consultation</h2>
                    </div>
                    <!-- Live Consultation Stopwatch -->
                    <div id="servingTimer" style="font-family: 'JetBrains Mono', monospace; font-size: 1.1rem; font-weight: 700; color: #fff; background: rgba(0, 0, 0, 0.35); padding: 0.3rem 0.85rem; border-radius: var(--radius-full); border: 1px solid var(--border-color);">
                        ⏱️ 00:00
                    </div>
                </div>

                <div id="servingActiveContent" style="display: none;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem;">
                        <div>
                            <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em;">TICKET NUMBER</span>
                            <div id="curTicketNumber" style="font-family: 'JetBrains Mono', monospace; font-size: 3.5rem; font-weight: 900; color: #fff; line-height: 1;">A002</div>
                        </div>
                        <div style="text-align: right;">
                            <span id="curPatientTypeBadge" class="badge badge-priority" style="font-size: 0.85rem; padding: 0.35rem 0.8rem;">Regular</span>
                            <div id="curServiceName" style="font-size: 1rem; font-weight: 700; color: var(--primary-light); margin-top: 0.4rem;">General Medicine</div>
                        </div>
                    </div>

                    <div style="background: rgba(11, 17, 32, 0.6); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1rem; margin-bottom: 1.25rem;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem; font-size: 0.95rem;">
                            <span style="color: var(--text-secondary);">Patient Name:</span>
                            <strong id="curPatientName" style="color: #fff;">-</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.35rem; font-size: 0.9rem;">
                            <span style="color: var(--text-secondary);">Contact / Phone:</span>
                            <span id="curPatientPhone" style="color: var(--text-secondary);">-</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.9rem;">
                            <span style="color: var(--text-secondary);">Waiting Time Before Called:</span>
                            <span id="curWaitTime" style="color: var(--warning-light); font-weight: 600;">-</span>
                        </div>
                        <div id="curNotesRow" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border-color); font-size: 0.85rem; color: var(--text-secondary); display: none;">
                            <strong>Reason for visit:</strong> <span id="curNotesText"></span>
                        </div>
                    </div>

                    <!-- Consultation Action Buttons -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 0.75rem;">
                        <button onclick="openCompleteModal()" class="btn btn-success btn-lg" id="btnComplete">
                            <span>✅</span> Complete Consultation
                        </button>
                        <button onclick="reannounceCurrentTicket()" class="btn btn-outline btn-lg" id="btnRecallVoice">
                            <span>📢</span> Repeat Calling Voice
                        </button>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <button onclick="openSkipModal()" class="btn btn-warning btn-sm" id="btnSkip">
                            <span>⏭️</span> Skip Patient
                        </button>
                        <button onclick="openTransferModal()" class="btn btn-outline btn-sm" id="btnTransfer">
                            <span>🔀</span> Transfer to Dept...
                        </button>
                    </div>
                </div>

                <!-- Empty State (No ticket currently serving) -->
                <div id="servingEmptyContent" style="text-align: center; padding: 2.5rem 1rem;">
                    <div style="font-size: 2.5rem; margin-bottom: 0.75rem;">🛋️</div>
                    <h3 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.35rem;">Station Ready for Next Patient</h3>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1.5rem; max-width: 360px; margin-left: auto; margin-right: auto;">
                        Click the button below to call the next prioritized waiting patient to this station.
                    </p>
                    <button onclick="callNextPatient()" class="btn btn-primary btn-lg" id="btnCallNextBig" style="font-size: 1.15rem; padding: 1rem 2rem;">
                        <span>🔔</span> CALL NEXT PATIENT
                    </button>
                </div>
            </div>

            <!-- SKIPPED / RECALL MANAGEMENT LIST -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span>⏭️</span> Skipped Patients (<span id="skippedCount">0</span>)
                    </h3>
                    <span class="badge badge-skipped">Awaiting Recall</span>
                </div>
                <div id="skippedContainer">
                    <div style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                        No skipped patients.
                    </div>
                </div>
            </div>

        </div>

        <!-- RIGHT: Live Waiting List Table & Direct Calling -->
        <div class="card" style="display: flex; flex-direction: column; min-height: 600px;">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        <span>📋</span> Waiting Patients Line (<span id="waitingCount">0</span>)
                    </h3>
                    <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.2rem;">
                        Ordered by Priority Category and Arrival Time
                    </div>
                </div>
                <button onclick="callNextPatient()" class="btn btn-primary btn-sm">
                    <span>🔔</span> Call Next
                </button>
            </div>

            <!-- Waiting Queue Filters -->
            <div style="display: flex; gap: 0.5rem; margin-bottom: 1rem;">
                <select id="filterService" class="form-select" style="font-size: 0.85rem;" onchange="loadStaffQueueData()">
                    <option value="0">All Services / Departments</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Waiting List Container -->
            <div class="table-responsive" style="flex: 1;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Priority</th>
                            <th>Wait Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="waitingTableBody">
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                Loading waiting queue...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<!-- COMPLETE CONSULTATION MODAL -->
<div class="modal-backdrop" id="completeModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">✅ Complete Patient Consultation</h3>
            <button class="modal-close" onclick="App.closeModal('completeModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                Finishing consultation for <strong id="modalCompleteTicket" style="color: #fff;">-</strong>. You may enter optional consultation summary or prescriptions refilled.
            </p>
            <div class="form-group">
                <label class="form-label" for="completeNotes">Consultation Summary / Remarks</label>
                <textarea id="completeNotes" class="form-control" rows="3" placeholder="e.g. Prescribed amoxicillin 500mg, advised 3 days rest. Normal vital signs."></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="App.closeModal('completeModal')">Cancel</button>
            <button type="button" class="btn btn-success" onclick="submitComplete()">Finish & Mark Completed</button>
        </div>
    </div>
</div>

<!-- SKIP PATIENT MODAL -->
<div class="modal-backdrop" id="skipModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">⏭️ Skip Patient Number</h3>
            <button class="modal-close" onclick="App.closeModal('skipModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                Skipping <strong id="modalSkipTicket" style="color: #fff;">-</strong>. The ticket will be kept in the Skipped List and can be recalled once the patient returns.
            </p>
            <div class="form-group">
                <label class="form-label" for="skipReasonSelect">Reason for Skipping</label>
                <select id="skipReasonSelect" class="form-select">
                    <option value="Patient did not respond after 3 calls">Patient did not respond after 3 calls</option>
                    <option value="Momentarily stepped out of waiting area">Momentarily stepped out of waiting area</option>
                    <option value="Undergoing prior laboratory/vital testing">Undergoing prior laboratory/vital testing</option>
                    <option value="Requested delayed turn">Requested delayed turn</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="App.closeModal('skipModal')">Cancel</button>
            <button type="button" class="btn btn-warning" onclick="submitSkip()">Confirm Skip</button>
        </div>
    </div>
</div>

<!-- TRANSFER MODAL -->
<div class="modal-backdrop" id="transferModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title">🔀 Transfer Patient to Another Department</h3>
            <button class="modal-close" onclick="App.closeModal('transferModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 1rem;">
                Transfer ticket <strong id="modalTransferTicket" style="color: #fff;">-</strong> to another service queue (e.g. for Blood Test, Dental, or Pharmacy).
            </p>
            <div class="form-group">
                <label class="form-label" for="targetServiceSelect">Target Department / Service</label>
                <select id="targetServiceSelect" class="form-select">
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label" for="transferNotes">Transfer Instructions / Reason</label>
                <input type="text" id="transferNotes" class="form-control" placeholder="e.g. Referred for CBC and Urinalysis lab test">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="App.closeModal('transferModal')">Cancel</button>
            <button type="button" class="btn btn-primary" onclick="submitTransfer()">Transfer Patient</button>
        </div>
    </div>
</div>

<script>
let currentStationId = <?= $defaultCounterId ?>;
let activeTicket = null;
let timerInterval = null;
let consultationStartSeconds = 0;

function changeCounter(newId) {
    currentStationId = parseInt(newId);
    const url = new URL(window.location);
    url.searchParams.set('counter_id', currentStationId);
    window.history.pushState({}, '', url);
    loadStaffQueueData();
}

async function loadStaffQueueData() {
    try {
        const serviceFilter = document.getElementById('filterService').value;
        const res = await fetch(`api/queue.php?action=list&service_id=${serviceFilter}`);
        const data = await res.json();
        if (!data.success) return;

        // Check if current counter has an active serving ticket
        const thisCounter = data.counters.find(c => c.id == currentStationId);
        const servingTicket = data.tickets.find(t => t.counter_id == currentStationId && t.status === 'Serving');

        renderServingTicket(servingTicket, thisCounter);
        renderWaitingList(data.tickets.filter(t => t.status === 'Waiting'));
        renderSkippedList(data.tickets.filter(t => t.status === 'Skipped'));

    } catch (e) {
        console.error('Error loading staff queue data:', e);
    }
}

function renderServingTicket(ticket, counter) {
    const activeBox = document.getElementById('servingActiveContent');
    const emptyBox = document.getElementById('servingEmptyContent');

    if (ticket) {
        activeTicket = ticket;
        activeBox.style.display = 'block';
        emptyBox.style.display = 'none';

        document.getElementById('curTicketNumber').textContent = ticket.ticket_number;
        document.getElementById('curPatientName').textContent = ticket.patient_name;
        document.getElementById('curPatientPhone').textContent = ticket.patient_phone || 'None provided';
        document.getElementById('curServiceName').textContent = ticket.service_name;
        document.getElementById('curPatientTypeBadge').textContent = ticket.patient_type;

        // Wait time
        const waitMins = Math.round((ticket.wait_duration_secs || 0) / 60);
        document.getElementById('curWaitTime').textContent = `${waitMins} min(s)`;

        // Notes
        const notesRow = document.getElementById('curNotesRow');
        if (ticket.notes && ticket.notes.trim() !== '') {
            notesRow.style.display = 'block';
            document.getElementById('curNotesText').textContent = ticket.notes;
        } else {
            notesRow.style.display = 'none';
        }

        // Timer
        if (ticket.called_time) {
            const calledMs = new Date(ticket.called_time.replace(' ', 'T')).getTime();
            const nowMs = Date.now();
            consultationStartSeconds = Math.max(0, Math.floor((nowMs - calledMs) / 1000));
        } else {
            consultationStartSeconds = 0;
        }
        startTimer();

    } else {
        activeTicket = null;
        activeBox.style.display = 'none';
        emptyBox.style.display = 'block';
        stopTimer();
    }
}

function startTimer() {
    stopTimer();
    const timerEl = document.getElementById('servingTimer');
    const update = () => {
        consultationStartSeconds++;
        const mins = String(Math.floor(consultationStartSeconds / 60)).padStart(2, '0');
        const secs = String(consultationStartSeconds % 60).padStart(2, '0');
        timerEl.textContent = `⏱️ ${mins}:${secs}`;
    };
    update();
    timerInterval = setInterval(update, 1000);
}

function stopTimer() {
    if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    document.getElementById('servingTimer').textContent = '⏱️ 00:00';
}

function renderWaitingList(waitingList) {
    const tbody = document.getElementById('waitingTableBody');
    const badge = document.getElementById('waitingCount');
    badge.textContent = waitingList.length;
    tbody.innerHTML = '';

    if (waitingList.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem 1rem;">
                    No patients currently waiting in line.
                </td>
            </tr>
        `;
        return;
    }

    waitingList.forEach(t => {
        const tr = document.createElement('tr');
        
        // Calculate wait elapsed
        const checkInMs = new Date(t.check_in_time.replace(' ', 'T')).getTime();
        const waitMins = Math.max(1, Math.round((Date.now() - checkInMs) / 60000));

        tr.innerHTML = `
            <td>
                <span class="table-ticket-badge">${t.ticket_number}</span>
            </td>
            <td>
                <strong>${t.patient_name}</strong>
                <div style="font-size: 0.75rem; color: var(--text-muted);">${t.patient_phone || ''}</div>
            </td>
            <td>
                <span style="font-size: 0.85rem; color: var(--primary-light);">${t.service_name}</span>
            </td>
            <td>
                <span class="badge ${t.patient_type === 'Regular' ? 'badge-regular' : 'badge-priority'}">
                    ${t.patient_type}
                </span>
            </td>
            <td>
                <span style="color: var(--warning-light); font-weight: 600;">~${waitMins}m</span>
            </td>
            <td>
                <button onclick="callSpecificTicket(${t.id})" class="btn btn-outline btn-sm" title="Call this patient now">
                    <span>🔔</span> Call
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderSkippedList(skippedList) {
    const container = document.getElementById('skippedContainer');
    const badge = document.getElementById('skippedCount');
    badge.textContent = skippedList.length;
    container.innerHTML = '';

    if (skippedList.length === 0) {
        container.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 1.5rem;">No skipped patients.</div>';
        return;
    }

    skippedList.forEach(s => {
        const item = document.createElement('div');
        item.style.cssText = 'background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 0.75rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem;';
        
        item.innerHTML = `
            <div>
                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.2rem;">
                    <span class="table-ticket-badge" style="font-size: 0.95rem;">${s.ticket_number}</span>
                    <strong style="font-size: 0.9rem; color: #fff;">${s.patient_name}</strong>
                </div>
                <div style="font-size: 0.78rem; color: var(--danger-light);">
                    Reason: ${s.skip_reason || 'No response'}
                </div>
            </div>
            <button onclick="recallSkippedTicket(${s.id})" class="btn btn-primary btn-sm">
                <span>🔄</span> Recall to Station
            </button>
        `;
        container.appendChild(item);
    });
}

// ==========================================
// ACTION HANDLERS
// ==========================================

async function callNextPatient() {
    const serviceFilter = document.getElementById('filterService').value;
    const formData = new FormData();
    formData.append('action', 'call_next');
    formData.append('counter_id', currentStationId);
    formData.append('service_id', serviceFilter);

    try {
        const res = await fetch('api/queue.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success && data.ticket) {
            App.showToast(data.message, 'success');
            // Trigger audio
            App.announceTicket(
                data.ticket.ticket_number,
                `${data.ticket.counter_number} - ${data.ticket.counter_name}`,
                data.ticket.service_name
            );
            loadStaffQueueData();
        } else {
            App.showToast(data.message || 'No patients waiting', 'warning');
        }
    } catch (e) {
        App.showToast('Server error while calling next patient', 'danger');
    }
}

async function callSpecificTicket(ticketId) {
    const formData = new FormData();
    formData.append('action', 'call_specific');
    formData.append('ticket_id', ticketId);
    formData.append('counter_id', currentStationId);

    try {
        const res = await fetch('api/queue.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success && data.ticket) {
            App.showToast(data.message, 'success');
            App.announceTicket(
                data.ticket.ticket_number,
                `${data.ticket.counter_number} - ${data.ticket.counter_name}`,
                data.ticket.service_name
            );
            loadStaffQueueData();
        } else {
            App.showToast(data.message || 'Error calling ticket', 'danger');
        }
    } catch (e) {
        App.showToast('Server error', 'danger');
    }
}

function reannounceCurrentTicket() {
    if (!activeTicket) return;
    callSpecificTicket(activeTicket.id);
}

// Complete Modal
function openCompleteModal() {
    if (!activeTicket) return;
    document.getElementById('modalCompleteTicket').textContent = `${activeTicket.ticket_number} (${activeTicket.patient_name})`;
    document.getElementById('completeNotes').value = '';
    App.openModal('completeModal');
}

async function submitComplete() {
    if (!activeTicket) return;
    const notes = document.getElementById('completeNotes').value.trim();
    
    const formData = new FormData();
    formData.append('action', 'complete');
    formData.append('ticket_id', activeTicket.id);
    formData.append('notes', notes);

    try {
        const res = await fetch('api/queue.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            App.closeModal('completeModal');
            App.showToast(data.message, 'success');
            loadStaffQueueData();
        } else {
            App.showToast(data.message || 'Error completing ticket', 'danger');
        }
    } catch (e) {
        App.showToast('Server error', 'danger');
    }
}

// Skip Modal
function openSkipModal() {
    if (!activeTicket) return;
    document.getElementById('modalSkipTicket').textContent = `${activeTicket.ticket_number} (${activeTicket.patient_name})`;
    App.openModal('skipModal');
}

async function submitSkip() {
    if (!activeTicket) return;
    const reason = document.getElementById('skipReasonSelect').value;

    const formData = new FormData();
    formData.append('action', 'skip');
    formData.append('ticket_id', activeTicket.id);
    formData.append('reason', reason);

    try {
        const res = await fetch('api/queue.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            App.closeModal('skipModal');
            App.showToast(data.message, 'warning');
            loadStaffQueueData();
        } else {
            App.showToast(data.message || 'Error skipping ticket', 'danger');
        }
    } catch (e) {
        App.showToast('Server error', 'danger');
    }
}

// Recall Skipped
async function recallSkippedTicket(ticketId) {
    const formData = new FormData();
    formData.append('action', 'recall');
    formData.append('ticket_id', ticketId);
    formData.append('counter_id', currentStationId);

    try {
        const res = await fetch('api/queue.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            App.showToast(data.message, 'success');
            loadStaffQueueData();
        } else {
            App.showToast(data.message || 'Error recalling ticket', 'danger');
        }
    } catch (e) {
        App.showToast('Server error', 'danger');
    }
}

// Transfer Modal
function openTransferModal() {
    if (!activeTicket) return;
    document.getElementById('modalTransferTicket').textContent = `${activeTicket.ticket_number} (${activeTicket.patient_name})`;
    document.getElementById('transferNotes').value = '';
    App.openModal('transferModal');
}

async function submitTransfer() {
    if (!activeTicket) return;
    const targetService = document.getElementById('targetServiceSelect').value;
    const notes = document.getElementById('transferNotes').value.trim();

    const formData = new FormData();
    formData.append('action', 'transfer');
    formData.append('ticket_id', activeTicket.id);
    formData.append('target_service_id', targetService);
    formData.append('notes', notes);

    try {
        const res = await fetch('api/queue.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            App.closeModal('transferModal');
            App.showToast(data.message, 'info');
            loadStaffQueueData();
        } else {
            App.showToast(data.message || 'Error transferring ticket', 'danger');
        }
    } catch (e) {
        App.showToast('Server error', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadStaffQueueData();
    setInterval(loadStaffQueueData, 4000);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
