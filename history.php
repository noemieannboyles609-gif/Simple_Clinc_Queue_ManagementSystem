<?php
// history.php - Queue Transaction History & Audit Records
$pageTitle = 'Queue History & Records';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch services for filter dropdown
$servicesStmt = $pdo->query("SELECT * FROM services ORDER BY id ASC");
$services = $servicesStmt->fetchAll();
?>

<main class="main-wrapper">
    <div class="page-header">
        <div>
            <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: rgba(6, 182, 212, 0.15); border: 1px solid var(--cyan); padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.75rem; font-weight: 700; color: var(--cyan); text-transform: uppercase; margin-bottom: 0.5rem;">
                <span>📜</span> Historical Records & Audit Trail
            </div>
            <h1 class="page-title">Queue Transaction History</h1>
            <p class="page-subtitle">Inspect completed patient visits, service durations, waiting times, and export clinic audit reports.</p>
        </div>

        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <button onclick="exportCSV()" class="btn btn-outline" id="btnExportCSV">
                <span>📥</span> Export to CSV
            </button>
            <button onclick="exportJSON()" class="btn btn-outline" id="btnExportJSON">
                <span>💾</span> Export JSON
            </button>
            <button onclick="window.print()" class="btn btn-outline">
                <span>🖨️</span> Print Log
            </button>
        </div>
    </div>

    <!-- Filter & Search Card -->
    <div class="card" style="margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: flex-end;">
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="historySearch">Search Keyword</label>
                <input type="text" id="historySearch" class="form-control" placeholder="Ticket # or Patient Name..." oninput="debounceSearch()">
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="historyStatus">Filter by Status</label>
                <select id="historyStatus" class="form-select" onchange="fetchHistory()">
                    <option value="">All Statuses</option>
                    <option value="Completed" selected>Completed Only</option>
                    <option value="Skipped">Skipped / Missed</option>
                    <option value="Serving">Currently Serving</option>
                    <option value="Waiting">Waiting</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" for="historyService">Department / Service</label>
                <select id="historyService" class="form-select" onchange="fetchHistory()">
                    <option value="0">All Departments</option>
                    <?php foreach ($services as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button onclick="fetchHistory()" class="btn btn-primary" style="flex: 1;">
                    <span>🔍</span> Apply Filters
                </button>
                <button onclick="resetFilters()" class="btn btn-outline" title="Reset Filters">
                    Reset
                </button>
            </div>

        </div>
    </div>

    <!-- History Summary Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; font-size: 0.9rem; color: var(--text-secondary); flex-wrap: wrap; gap: 0.5rem;">
        <div>
            Showing <strong id="recordCount" style="color: #fff;">0</strong> records
        </div>
        <div style="display: flex; gap: 1.5rem;">
            <div>Avg Wait: <strong id="histAvgWait" style="color: var(--warning-light);">-</strong></div>
            <div>Avg Service Time: <strong id="histAvgService" style="color: var(--success-light);">-</strong></div>
        </div>
    </div>

    <!-- History Data Table Card -->
    <div class="card">
        <div class="table-responsive">
            <table class="data-table" id="historyTable">
                <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Patient Name</th>
                        <th>Category</th>
                        <th>Department</th>
                        <th>Station & Staff</th>
                        <th>Check-in</th>
                        <th>Wait Time</th>
                        <th>Service Duration</th>
                        <th>Status</th>
                        <th>Remarks / Notes</th>
                    </tr>
                </thead>
                <tbody id="historyTableBody">
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">
                            Loading queue transaction history...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
let searchTimer = null;
let currentHistoryData = [];

function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(fetchHistory, 300);
}

function resetFilters() {
    document.getElementById('historySearch').value = '';
    document.getElementById('historyStatus').value = '';
    document.getElementById('historyService').value = '0';
    fetchHistory();
}

async function fetchHistory() {
    const search = document.getElementById('historySearch').value.trim();
    const status = document.getElementById('historyStatus').value;
    const serviceId = document.getElementById('historyService').value;

    const params = new URLSearchParams({
        action: 'history',
        search: search,
        status: status,
        service_id: serviceId
    });

    try {
        const res = await fetch(`api/queue.php?${params.toString()}`);
        const data = await res.json();
        if (!data.success) return;

        currentHistoryData = data.history || [];
        renderHistoryTable(currentHistoryData);

    } catch (e) {
        console.error('Error fetching history:', e);
    }
}

function formatDuration(seconds) {
    if (!seconds || seconds <= 0) return '0m';
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    if (m === 0) return `${s}s`;
    return `${m}m ${s > 0 ? s + 's' : ''}`;
}

function renderHistoryTable(items) {
    const tbody = document.getElementById('historyTableBody');
    document.getElementById('recordCount').textContent = items.length;
    tbody.innerHTML = '';

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 3rem 1rem;">
                    No records found matching current criteria.
                </td>
            </tr>
        `;
        document.getElementById('histAvgWait').textContent = '-';
        document.getElementById('histAvgService').textContent = '-';
        return;
    }

    let totalWait = 0;
    let waitCount = 0;
    let totalService = 0;
    let serviceCount = 0;

    items.forEach(item => {
        if (item.wait_duration_secs > 0) {
            totalWait += parseInt(item.wait_duration_secs);
            waitCount++;
        }
        if (item.service_duration_secs > 0) {
            totalService += parseInt(item.service_duration_secs);
            serviceCount++;
        }

        let statusBadge = '';
        if (item.status === 'Completed') statusBadge = '<span class="badge badge-completed">Completed</span>';
        else if (item.status === 'Serving') statusBadge = '<span class="badge badge-serving">Serving</span>';
        else if (item.status === 'Waiting') statusBadge = '<span class="badge badge-waiting">Waiting</span>';
        else if (item.status === 'Skipped') statusBadge = '<span class="badge badge-skipped">Skipped</span>';
        else statusBadge = `<span class="badge badge-regular">${item.status}</span>`;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="table-ticket-badge">${item.ticket_number}</span></td>
            <td>
                <strong>${item.patient_name}</strong>
                <div style="font-size: 0.75rem; color: var(--text-muted);">${item.patient_phone || ''}</div>
            </td>
            <td>
                <span class="badge ${item.patient_type === 'Regular' ? 'badge-regular' : 'badge-priority'}">
                    ${item.patient_type}
                </span>
            </td>
            <td><span style="color: var(--primary-light); font-weight: 600;">${item.service_name}</span></td>
            <td>
                <div>${item.counter_number || '&mdash;'}</div>
                <div style="font-size: 0.75rem; color: var(--text-muted);">${item.staff_name || ''}</div>
            </td>
            <td><small>${item.check_in_time.substring(11, 16)}</small></td>
            <td><strong style="color: var(--warning-light);">${formatDuration(item.wait_duration_secs)}</strong></td>
            <td><strong style="color: var(--success-light);">${formatDuration(item.service_duration_secs)}</strong></td>
            <td>${statusBadge}</td>
            <td style="max-width: 220px; font-size: 0.8rem; color: var(--text-secondary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="${item.notes || item.skip_reason || ''}">
                ${item.skip_reason ? '<span style="color: var(--danger-light);">[Skipped] ' + item.skip_reason + '</span>' : (item.notes || '&mdash;')}
            </td>
        `;
        tbody.appendChild(tr);
    });

    const avgWaitMins = waitCount > 0 ? (totalWait / waitCount / 60).toFixed(1) : 0;
    const avgServiceMins = serviceCount > 0 ? (totalService / serviceCount / 60).toFixed(1) : 0;
    document.getElementById('histAvgWait').textContent = avgWaitMins + ' mins';
    document.getElementById('histAvgService').textContent = avgServiceMins + ' mins';
}

function exportCSV() {
    if (!currentHistoryData || currentHistoryData.length === 0) {
        App.showToast('No records to export', 'warning');
        return;
    }

    const headers = ['Ticket Number', 'Patient Name', 'Phone', 'Category', 'Service', 'Counter', 'Staff', 'Check In', 'Called', 'Completed', 'Wait (Secs)', 'Service (Secs)', 'Status', 'Notes'];
    const rows = currentHistoryData.map(d => [
        `"${d.ticket_number}"`,
        `"${d.patient_name.replace(/"/g, '""')}"`,
        `"${(d.patient_phone || '').replace(/"/g, '""')}"`,
        `"${d.patient_type}"`,
        `"${d.service_name}"`,
        `"${d.counter_number || ''}"`,
        `"${(d.staff_name || '').replace(/"/g, '""')}"`,
        `"${d.check_in_time}"`,
        `"${d.called_time || ''}"`,
        `"${d.completed_time || ''}"`,
        d.wait_duration_secs || 0,
        d.service_duration_secs || 0,
        `"${d.status}"`,
        `"${(d.notes || d.skip_reason || '').replace(/"/g, '""')}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `carequeue_history_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    App.showToast('CSV report downloaded successfully!', 'success');
}

function exportJSON() {
    if (!currentHistoryData || currentHistoryData.length === 0) {
        App.showToast('No records to export', 'warning');
        return;
    }

    const jsonStr = 'data:text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(currentHistoryData, null, 2));
    const link = document.createElement('a');
    link.setAttribute('href', jsonStr);
    link.setAttribute('download', `carequeue_history_${new Date().toISOString().slice(0, 10)}.json`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    App.showToast('JSON report exported successfully!', 'success');
}

document.addEventListener('DOMContentLoaded', fetchHistory);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
