<?php
// admin.php - Clinic Administrator Dashboard & Management Console
$pageTitle = 'Administrator Dashboard';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch counters
$countersStmt = $pdo->query("SELECT * FROM counters ORDER BY id ASC");
$counters = $countersStmt->fetchAll();

// Fetch services
$servicesStmt = $pdo->query("SELECT * FROM services ORDER BY id ASC");
$services = $servicesStmt->fetchAll();
?>

<main class="main-wrapper">
    <div class="page-header">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(139, 92, 246, 0.15); border: 1px solid var(--purple); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; color: var(--purple-light); text-transform: uppercase; margin-bottom: 0.5rem;">
                <span>📊</span> Administrator Executive Console
            </div>
            <h1 class="page-title">Clinic Queue Analytics & Configuration</h1>
            <p class="page-subtitle">Real-time service throughput, waiting line analytics, counter administration, and queue maintenance.</p>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <button onclick="openCounterModal()" class="btn btn-primary">
                <span>➕</span> Add Station / Room
            </button>
            <button onclick="reseedDemoData()" class="btn btn-outline" style="border-color: var(--primary);">
                <span>🔄</span> Reseed Demo Patients
            </button>
            <button onclick="resetQueueConfirm()" class="btn btn-danger btn-sm">
                <span>⚠️</span> Reset Today's Queue
            </button>
        </div>
    </div>

    <!-- KPI Metrics Row -->
    <div class="stats-grid" id="adminKpis">
        <div class="stat-card">
            <div class="stat-icon blue">👥</div>
            <div class="stat-content">
                <div class="stat-value" id="adminTotal">-</div>
                <div class="stat-label">Total Visits Today</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div class="stat-content">
                <div class="stat-value" id="adminWaiting">-</div>
                <div class="stat-label">Currently Waiting</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">🩺</div>
            <div class="stat-content">
                <div class="stat-value" id="adminServing">-</div>
                <div class="stat-label">Currently Serving</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">✅</div>
            <div class="stat-content">
                <div class="stat-value" id="adminCompleted">-</div>
                <div class="stat-label">Completed Consults</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rose">⏱️</div>
            <div class="stat-content">
                <div class="stat-value" id="adminAvgWait">- m</div>
                <div class="stat-label">Avg Waiting Time</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">⌛</div>
            <div class="stat-content">
                <div class="stat-value" id="adminAvgService">- m</div>
                <div class="stat-label">Avg Consult Duration</div>
            </div>
        </div>
    </div>

    <!-- Analytics Charts & Breakdown Grid -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Service Volume Breakdown Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <span>🩺</span> Department / Service Queue Volume
                </h3>
                <span class="badge badge-regular">Today's Visits</span>
            </div>
            <div id="serviceBarsContainer" style="display: flex; flex-direction: column; gap: 1rem; padding: 0.5rem 0;">
                <!-- Injected via JS -->
            </div>
        </div>

        <!-- Patient Demographics & Priority Category -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <span>👥</span> Patient Category & Priority Distribution
                </h3>
                <span class="badge badge-priority">Demographics</span>
            </div>
            <div id="patientTypeBarsContainer" style="display: flex; flex-direction: column; gap: 1rem; padding: 0.5rem 0;">
                <!-- Injected via JS -->
            </div>
        </div>

    </div>

    <!-- Hourly Queue Inflow & Station Performance -->
    <div style="display: grid; grid-template-columns: 1.1fr 1fr; gap: 1.5rem; margin-bottom: 2rem;">
        
        <!-- Hourly Traffic Trend (SVG Bar Graph) -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <span>📈</span> Hourly Patient Arrival Distribution
                </h3>
                <span class="badge badge-serving">Traffic Density</span>
            </div>
            <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 1rem;">
                Visual breakdown of peak queue check-in hours to assist staff scheduling.
            </p>
            <div id="hourlyChartContainer" style="height: 180px; display: flex; align-items: flex-end; gap: 0.75rem; padding-top: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">
                <!-- Injected via JS -->
            </div>
            <div id="hourlyLabelsContainer" style="display: flex; gap: 0.75rem; font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem; justify-content: space-between;">
                <!-- Labels -->
            </div>
        </div>

        <!-- Counter / Staff Throughput -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <span>👨‍⚕️</span> Station & Staff Performance
                </h3>
                <span class="badge badge-regular">Throughput</span>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Station</th>
                            <th>Staff In-Charge</th>
                            <th>Served</th>
                            <th>Avg Time</th>
                        </tr>
                    </thead>
                    <tbody id="counterPerfTableBody">
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 1.5rem;">
                                Loading staff metrics...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- COUNTER & ROOM MANAGEMENT TABLE -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <span>🏥</span> Consultation Rooms & Counter Management
                </h3>
                <div style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.2rem;">
                    Configure consultation rooms, assign staff members, and toggle station availability.
                </div>
            </div>
            <button onclick="openCounterModal()" class="btn btn-primary btn-sm">
                <span>➕</span> Add Station
            </button>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Station #</th>
                        <th>Room / Department Name</th>
                        <th>Staff In-Charge</th>
                        <th>Operational Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="adminCountersTableBody">
                    <?php foreach ($counters as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['counter_number']) ?></strong></td>
                            <td><?= htmlspecialchars($c['name']) ?></td>
                            <td><span style="color: var(--text-secondary);"><?= htmlspecialchars($c['staff_name']) ?></span></td>
                            <td>
                                <?php if ($c['status'] === 'Active'): ?>
                                    <span class="badge badge-serving">Active</span>
                                <?php elseif ($c['status'] === 'Break'): ?>
                                    <span class="badge badge-waiting">On Break</span>
                                <?php else: ?>
                                    <span class="badge badge-skipped">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick='editCounter(<?= json_encode($c) ?>)' class="btn btn-outline btn-sm">
                                    <span>✏️</span> Edit
                                </button>
                                <a href="staff.php?counter_id=<?= $c['id'] ?>" class="btn btn-outline btn-sm">
                                    <span>🩺</span> Open Desk
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- ADD / EDIT COUNTER MODAL -->
<div class="modal-backdrop" id="counterModal">
    <div class="modal-card">
        <div class="modal-header">
            <h3 class="modal-title" id="counterModalTitle">Add Consultation Room / Station</h3>
            <button class="modal-close" onclick="App.closeModal('counterModal')">&times;</button>
        </div>
        <form id="counterForm" onsubmit="saveCounter(event)">
            <input type="hidden" id="counterId" name="counter_id" value="0">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="counterNum">Station Identifier <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="counterNum" name="counter_number" class="form-control" placeholder="e.g. Counter 6 or Room 104" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="counterName">Room / Department Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="counterName" name="name" class="form-control" placeholder="e.g. Ophthalmology & Eye Care" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="counterStaff">Staff / Doctor In-Charge <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="counterStaff" name="staff_name" class="form-control" placeholder="e.g. Dr. Arthur Pendelton" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="counterStatus">Operational Status</label>
                    <select id="counterStatus" name="status" class="form-select">
                        <option value="Active">Active</option>
                        <option value="Break">Break</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="App.closeModal('counterModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="btnSaveCounter">Save Station</button>
            </div>
        </form>
    </div>
</div>

<script>
async function loadAdminAnalytics() {
    try {
        const res = await fetch('api/queue.php?action=stats');
        const data = await res.json();
        if (!data.success) return;

        // Populate KPIs
        const s = data.summary;
        document.getElementById('adminTotal').textContent = s.total_patients;
        document.getElementById('adminWaiting').textContent = s.waiting_count;
        document.getElementById('adminServing').textContent = s.serving_count;
        document.getElementById('adminCompleted').textContent = s.completed_count;
        document.getElementById('adminAvgWait').textContent = s.avg_wait_mins + 'm';
        document.getElementById('adminAvgService').textContent = s.avg_service_mins + 'm';

        // Render Service Volume Bars
        renderServiceBars(data.services, s.total_patients);

        // Render Patient Demographics Bars
        renderPatientTypeBars(data.patient_types, s.total_patients);

        // Render Hourly Traffic Chart
        renderHourlyChart(data.hourly);

        // Render Counter Throughput Table
        renderCounterPerf(data.counters);

    } catch (e) {
        console.error('Error loading analytics:', e);
    }
}

function renderServiceBars(services, total) {
    const container = document.getElementById('serviceBarsContainer');
    container.innerHTML = '';

    if (!services || services.length === 0) {
        container.innerHTML = '<div style="color: var(--text-muted);">No service data yet.</div>';
        return;
    }

    services.forEach(srv => {
        const pct = total > 0 ? Math.round((srv.ticket_count / total) * 100) : 0;
        const row = document.createElement('div');
        row.innerHTML = `
            <div style="display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 0.35rem;">
                <span style="color: #fff; font-weight: 600;">${srv.name}</span>
                <span style="color: var(--text-secondary); font-family: 'JetBrains Mono', monospace;">${srv.ticket_count} visits (${pct}%)</span>
            </div>
            <div style="height: 10px; background: rgba(255, 255, 255, 0.08); border-radius: 99px; overflow: hidden;">
                <div style="height: 100%; width: ${Math.max(5, pct)}%; background: ${srv.color || 'var(--primary)'}; border-radius: 99px; transition: width 0.6s ease;"></div>
            </div>
        `;
        container.appendChild(row);
    });
}

function renderPatientTypeBars(types, total) {
    const container = document.getElementById('patientTypeBarsContainer');
    container.innerHTML = '';

    if (!types || types.length === 0) {
        container.innerHTML = '<div style="color: var(--text-muted);">No demographic data yet.</div>';
        return;
    }

    const typeColors = {
        'Regular': '#94a3b8',
        'Senior': '#f59e0b',
        'PWD': '#8b5cf6',
        'Pregnant': '#ec4899',
        'Child': '#06b6d4',
        'Emergency': '#f43f5e'
    };

    types.forEach(t => {
        const pct = total > 0 ? Math.round((t.count / total) * 100) : 0;
        const color = typeColors[t.patient_type] || 'var(--primary)';
        const row = document.createElement('div');
        row.innerHTML = `
            <div style="display: flex; justify-content: space-between; font-size: 0.88rem; margin-bottom: 0.35rem;">
                <span style="color: #fff; font-weight: 600;">${t.patient_type}</span>
                <span style="color: var(--text-secondary); font-family: 'JetBrains Mono', monospace;">${t.count} patients (${pct}%)</span>
            </div>
            <div style="height: 10px; background: rgba(255, 255, 255, 0.08); border-radius: 99px; overflow: hidden;">
                <div style="height: 100%; width: ${Math.max(5, pct)}%; background: ${color}; border-radius: 99px; transition: width 0.6s ease;"></div>
            </div>
        `;
        container.appendChild(row);
    });
}

function renderHourlyChart(hourly) {
    const chart = document.getElementById('hourlyChartContainer');
    const labels = document.getElementById('hourlyLabelsContainer');
    chart.innerHTML = '';
    labels.innerHTML = '';

    if (!hourly || hourly.length === 0) {
        chart.innerHTML = '<div style="color: var(--text-muted); width: 100%; text-align: center;">No hourly data recorded today.</div>';
        return;
    }

    const maxCount = Math.max(...hourly.map(h => h.count), 1);

    hourly.forEach(h => {
        const heightPct = Math.max(15, Math.round((h.count / maxCount) * 100));
        const bar = document.createElement('div');
        bar.style.cssText = `flex: 1; height: ${heightPct}%; background: linear-gradient(180deg, var(--primary-light), var(--primary)); border-radius: 6px 6px 0 0; position: relative; transition: height 0.5s ease; cursor: pointer;`;
        bar.setAttribute('title', `${h.hour_of_day}:00 - ${h.count} patient(s)`);

        // Tooltip badge
        bar.innerHTML = `<span style="position: absolute; top: -22px; left: 50%; transform: translateX(-50%); font-size: 0.72rem; font-weight: 700; color: #fff;">${h.count}</span>`;
        chart.appendChild(bar);

        const lbl = document.createElement('div');
        lbl.style.cssText = 'flex: 1; text-align: center;';
        lbl.textContent = `${h.hour_of_day}:00`;
        labels.appendChild(lbl);
    });
}

function renderCounterPerf(counters) {
    const tbody = document.getElementById('counterPerfTableBody');
    tbody.innerHTML = '';

    if (!counters || counters.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" style="text-align: center; color: var(--text-muted);">No counter performance data.</td></tr>';
        return;
    }

    counters.forEach(c => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${c.counter_number}</strong></td>
            <td>${c.staff_name}</td>
            <td><span class="badge badge-completed">${c.served_count} consults</span></td>
            <td><strong style="color: var(--success-light);">${c.avg_mins}m</strong></td>
        `;
        tbody.appendChild(tr);
    });
}

// Modal Handlers
function openCounterModal() {
    document.getElementById('counterModalTitle').textContent = 'Add Consultation Room / Station';
    document.getElementById('counterId').value = '0';
    document.getElementById('counterForm').reset();
    App.openModal('counterModal');
}

function editCounter(c) {
    document.getElementById('counterModalTitle').textContent = 'Edit Consultation Room';
    document.getElementById('counterId').value = c.id;
    document.getElementById('counterNum').value = c.counter_number;
    document.getElementById('counterName').value = c.name;
    document.getElementById('counterStaff').value = c.staff_name;
    document.getElementById('counterStatus').value = c.status;
    App.openModal('counterModal');
}

async function saveCounter(e) {
    e.preventDefault();
    const form = document.getElementById('counterForm');
    const formData = new FormData(form);
    formData.append('action', 'save_counter');

    try {
        const res = await fetch('api/queue.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            App.closeModal('counterModal');
            App.showToast(data.message, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            App.showToast(data.message || 'Error saving counter', 'danger');
        }
    } catch (e) {
        App.showToast('Server error', 'danger');
    }
}

async function resetQueueConfirm() {
    if (!confirm('WARNING: This will clear all waiting, serving, and completed queue records for today. Proceed?')) return;
    try {
        const res = await fetch('api/queue.php?action=reset', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            App.showToast(data.message, 'warning');
            loadAdminAnalytics();
        }
    } catch (e) {
        App.showToast('Server error', 'danger');
    }
}

async function reseedDemoData() {
    if (!confirm('This will refresh the queue with fictional demonstration patients. Proceed?')) return;
    try {
        const res = await fetch('api/queue.php?action=seed', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            App.showToast(data.message, 'success');
            loadAdminAnalytics();
        }
    } catch (e) {
        App.showToast('Server error', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadAdminAnalytics();
    setInterval(loadAdminAnalytics, 6000);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
