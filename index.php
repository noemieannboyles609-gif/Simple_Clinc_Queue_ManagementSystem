<?php
// index.php - Main Portal & Command Hub
$pageTitle = 'Overview & Portal';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="main-wrapper">
    <!-- Hero Banner -->
    <div style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.15), rgba(16, 185, 129, 0.08)); border: 1px solid rgba(14, 165, 233, 0.25); border-radius: var(--radius-xl); padding: 2.25rem 2rem; margin-bottom: 2rem; position: relative; overflow: hidden;">
        <div style="max-width: 800px; position: relative; z-index: 2;">
            <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(14, 165, 233, 0.2); border: 1px solid var(--primary); padding: 0.3rem 0.8rem; border-radius: var(--radius-full); font-size: 0.8rem; font-weight: 700; color: var(--primary-light); text-transform: uppercase; margin-bottom: 0.75rem;">
                <span>✨</span> Simple Clinic Queue Management System
            </div>
            <h1 style="font-size: 2.4rem; font-weight: 800; color: #fff; line-height: 1.15; letter-spacing: -0.02em; margin-bottom: 0.75rem;">
                Streamlined Patient Flow & Real-Time Queue Control
            </h1>
            <p style="color: var(--text-secondary); font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
                A comprehensive solution to organize clinic visitors, automatically assign queue numbers, call waiting patients with voice and chime announcements, and display live waiting lines across multi-counter clinic stations.
            </p>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="login.php" class="btn btn-primary btn-lg" id="btnGoLogin" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                    <span>🔐</span> Sign In / Access Portal
                </a>
                <a href="kiosk.php" class="btn btn-outline btn-lg" id="btnGoKiosk">
                    <span>🎟️</span> Patient Self-Check-in
                </a>
                <a href="display.php" target="_blank" class="btn btn-success btn-lg" id="btnGoDisplay">
                    <span>📺</span> Open TV Display Board ↗
                </a>
                <a href="staff.php" class="btn btn-outline btn-lg" id="btnGoStaff">
                    <span>👨‍⚕️</span> Staff Station
                </a>
                <button onclick="reseedDemoData()" class="btn btn-outline" style="border-style: dashed; border-color: var(--primary);" id="btnReseed">
                    <span>🔄</span> Reset Demo Data
                </button>
            </div>
        </div>
    </div>

    <!-- Live Statistics Row -->
    <div class="stats-grid" id="statsRow">
        <div class="stat-card">
            <div class="stat-icon yellow">⏳</div>
            <div class="stat-content">
                <div class="stat-value" id="statWaiting">-</div>
                <div class="stat-label">
    <a href="staff.php" style="text-decoration: none; color: inherit;">Waiting Patients</a></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green">🩺</div>
            <div class="stat-content">
                <div class="stat-value" id="statServing">-</div>
                <div class="stat-label">Currently Serving</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">✅</div>
            <div class="stat-content">
                <div class="stat-value" id="statCompleted">-</div>
                <div class="stat-label">Completed Consultations</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon rose">⏭️</div>
            <div class="stat-content">
                <div class="stat-value" id="statSkipped">-</div>
                <div class="stat-label">Skipped / Recalls</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">⏱️</div>
            <div class="stat-content">
                <div class="stat-value" id="statAvgWait">- min</div>
                <div class="stat-label">Avg Wait Time</div>
            </div>
        </div>
    </div>

    <!-- Role Hub Navigation Grid -->
    <h2 style="font-size: 1.35rem; font-weight: 700; color: #fff; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.5rem;">
        <span>🎯</span> Select Role & Workflow
    </h2>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-bottom: 2.5rem;">
        
        <!-- 1. Patient Registration Kiosk -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: rgba(14, 165, 233, 0.15); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                        🎟️
                    </div>
                    <span class="badge badge-priority">Role: Patient</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">Patient Registration Kiosk</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                    Walk-in self-service touch kiosk. Patients register with basic info, select department or priority category (Senior/PWD), and receive an instant printable queue ticket with estimated wait time.
                </p>
                <ul style="color: var(--text-muted); font-size: 0.85rem; padding-left: 1.25rem; margin-bottom: 1.5rem; line-height: 1.6;">
                    <li>Unique ticket generation (e.g., A001, P002)</li>
                    <li>Estimated wait time & position preview</li>
                    <li>Real-time ticket status tracking</li>
                </ul>
            </div>
            <a href="kiosk.php" class="btn btn-primary" style="width: 100%;">Open Patient Kiosk &rarr;</a>
        </div>

        <!-- 2. TV Queue Display Board -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: rgba(16, 185, 129, 0.15); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                        📺
                    </div>
                    <span class="badge badge-serving">Public Display</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">Lobby Queue Display Board</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                    High-contrast digital signage board designed for waiting room TV monitors. Features voice announcements, melodic airport chime, multi-counter grid, and upcoming ticket tickers.
                </p>
                <ul style="color: var(--text-muted); font-size: 0.85rem; padding-left: 1.25rem; margin-bottom: 1.5rem; line-height: 1.6;">
                    <li>Synthesized chime and vocal calling</li>
                    <li>Currently serving spotlight with glowing badges</li>
                    <li>Live auto-refresh every 3 seconds</li>
                </ul>
            </div>
            <a href="display.php" target="_blank" class="btn btn-success" style="width: 100%;">Launch TV Board (New Tab) &rarr;</a>
        </div>

        <!-- 3. Clinic Staff Station -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: rgba(245, 158, 11, 0.15); color: var(--warning); display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                        👨‍⚕️
                    </div>
                    <span class="badge badge-waiting">Role: Staff / Doctor</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">Clinic Staff Calling Station</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                    Operational console for doctors and reception staff. Select your consultation room, call the next prioritized patient, complete consultations with elapsed timers, or skip/recall patients.
                </p>
                <ul style="color: var(--text-muted); font-size: 0.85rem; padding-left: 1.25rem; margin-bottom: 1.5rem; line-height: 1.6;">
                    <li>1-Click "Call Next Patient" button</li>
                    <li>Consultation duration stopwatch</li>
                    <li>Skip management with reason notes & Recall</li>
                </ul>
            </div>
            <a href="staff.php" class="btn btn-warning" style="width: 100%;">Open Staff Station &rarr;</a>
        </div>

        <!-- 4. Administrator Dashboard -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: rgba(139, 92, 246, 0.15); color: var(--purple); display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                        📊
                    </div>
                    <span class="badge" style="background: rgba(139, 92, 246, 0.2); color: var(--purple-light);">Role: Administrator</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">Clinic Analytics & Dashboard</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                    Complete administrative oversight. Monitor queue volumes, service distribution charts, doctor throughput, configure clinic service counters, and manage queue reset operations.
                </p>
                <ul style="color: var(--text-muted); font-size: 0.85rem; padding-left: 1.25rem; margin-bottom: 1.5rem; line-height: 1.6;">
                    <li>Live throughput and wait-time metrics</li>
                    <li>Service category & hourly traffic visual charts</li>
                    <li>Add & configure consultation rooms/counters</li>
                </ul>
            </div>
            <a href="admin.php" class="btn btn-outline" style="width: 100%; border-color: var(--purple);">Open Admin Dashboard &rarr;</a>
        </div>

        <!-- 5. Queue History & Audit -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                    <div style="width: 48px; height: 48px; border-radius: var(--radius-md); background: rgba(6, 182, 212, 0.15); color: var(--cyan); display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
                        📜
                    </div>
                    <span class="badge badge-regular">Records & Audit</span>
                </div>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: #fff; margin-bottom: 0.5rem;">Queue Transaction History</h3>
                <p style="color: var(--text-secondary); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1rem;">
                    Full historical log of all patient queue entries. Search by ticket number or name, filter by status or date, inspect service times, and export records to CSV or JSON.
                </p>
                <ul style="color: var(--text-muted); font-size: 0.85rem; padding-left: 1.25rem; margin-bottom: 1.5rem; line-height: 1.6;">
                    <li>Check-in, called, and completed timestamps</li>
                    <li>Precise wait and service duration logging</li>
                    <li>1-Click CSV and JSON export</li>
                </ul>
            </div>
            <a href="history.php" class="btn btn-outline" style="width: 100%;">View Queue History &rarr;</a>
        </div>

    </div>

    <!-- Active Counters Live Overview Table -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <span>🩺</span> Active Clinic Stations & Current Calling Status
            </h3>
            <span class="badge badge-serving">Auto-Updating</span>
        </div>
        <div class="table-responsive">
            <table class="data-table" id="countersTable">
                <thead>
                    <tr>
                        <th>Station</th>
                        <th>Department / Room</th>
                        <th>Staff In-Charge</th>
                        <th>Currently Serving</th>
                        <th>Patient Name</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="countersTableBody">
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                            Loading live clinic station status...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
async function loadOverviewData() {
    try {
        const res = await fetch('api/queue.php?action=display');
        const data = await res.json();
        if (!data.success) return;

        // Update counts
        const c = data.counts || {};
        document.getElementById('statWaiting').textContent = c.waiting_count || 0;
        document.getElementById('statServing').textContent = c.serving_count || 0;
        document.getElementById('statCompleted').textContent = c.completed_count || 0;
        document.getElementById('statSkipped').textContent = c.skipped_count || 0;

        // Also fetch stats for avg wait
        const statsRes = await fetch('api/queue.php?action=stats');
        const statsData = await statsRes.json();
        if (statsData.success && statsData.summary) {
            document.getElementById('statAvgWait').textContent = statsData.summary.avg_wait_mins + ' min';
        }

        // Render counters table
        const tbody = document.getElementById('countersTableBody');
        tbody.innerHTML = '';
        if (data.counters && data.counters.length > 0) {
            data.counters.forEach(cnt => {
                const tr = document.createElement('tr');
                const isServing = cnt.serving_ticket && cnt.serving_ticket.trim() !== '';
                
                tr.innerHTML = `
                    <td><strong>${cnt.counter_number}</strong></td>
                    <td>${cnt.name}</td>
                    <td><span style="color: var(--text-secondary);">${cnt.staff_name}</span></td>
                    <td>
                        ${isServing ? `<span class="table-ticket-badge" style="background: rgba(16, 185, 129, 0.2); color: var(--success-light);">${cnt.serving_ticket}</span>` : '<span style="color: var(--text-muted);">Idle / Ready</span>'}
                    </td>
                    <td>${isServing ? cnt.serving_patient : '<span style="color: var(--text-muted);">&mdash;</span>'}</td>
                    <td>
                        ${isServing ? '<span class="badge badge-serving">Serving</span>' : '<span class="badge badge-waiting">Available</span>'}
                    </td>
                    <td>
                        <a href="staff.php?counter_id=${cnt.id}" class="btn btn-outline btn-sm">Operate Station &rarr;</a>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
    } catch (e) {
        console.error('Error fetching overview data:', e);
    }
}

async function reseedDemoData() {
    if (!confirm('This will refresh the queue with fictional demonstration patients. Proceed?')) return;
    try {
        const res = await fetch('api/queue.php?action=seed', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            App.showToast(data.message, 'success');
            loadOverviewData();
        } else {
            App.showToast(data.message || 'Error reseeding', 'danger');
        }
    } catch (e) {
        App.showToast('Server communication error', 'danger');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadOverviewData();
    setInterval(loadOverviewData, 5000);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
