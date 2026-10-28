<?php
// display.php - Public Queue Display Board (TV / Waiting Room Digital Signage)
$pageTitle = 'Queue Display Board (TV View)';
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CareQueue Display Board | Live Waiting Room TV</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #050811;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(14, 165, 233, 0.12) 0%, transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 45%);
            overflow-x: hidden;
            user-select: none;
        }

        .tv-header {
            background: rgba(11, 17, 32, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 2px solid var(--border-color);
            padding: 1rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .tv-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .tv-brand-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--cyan));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 0 20px var(--primary-glow);
        }

        .tv-brand-title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.01em;
        }

        .tv-brand-sub {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .tv-clock {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.25rem;
            font-weight: 700;
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
            padding: 0.4rem 1rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .tv-main {
            padding: 1.5rem 2rem;
            max-width: 1720px;
            margin: 0 auto;
            width: 100%;
        }

        .tv-call-flash {
            animation: flash-animation 1.5s ease-in-out 3;
        }

        @keyframes flash-animation {
            0%, 100% { border-color: var(--success); box-shadow: 0 0 30px var(--success-glow); }
            50% { border-color: #fff; box-shadow: 0 0 60px rgba(255, 255, 255, 0.8); }
        }

        .tv-footer-ticker {
            background: rgba(11, 17, 32, 0.95);
            border-top: 1px solid var(--border-color);
            padding: 0.75rem 2rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.9rem;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }
    </style>
</head>
<body>

    <!-- TV Display Header -->
    <header class="tv-header">
        <div class="tv-brand">
            <div class="tv-brand-icon">🏥</div>
            <div>
                <div class="tv-brand-title">CAREQUEUE CLINIC</div>
                <div class="tv-brand-sub">Queue Status Display Board</div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 1rem;">
            <div class="tv-clock live-clock" id="displayClock">--:--:--</div>
            
            <button class="btn btn-outline btn-sm audio-btn" onclick="App.toggleAudio()" id="tvAudioBtn" title="Toggle Sound">
                🔊
            </button>
            <button class="btn btn-outline btn-sm" onclick="toggleFullscreen()" title="Toggle Fullscreen">
                ⛶ Fullscreen
            </button>
            <a href="index.php" class="btn btn-outline btn-sm" title="Back to Portal">
                &larr; Exit TV View
            </a>
        </div>
    </header>

    <!-- TV Display Main Grid -->
    <main class="tv-main">
        
        <!-- TOP SECTION: Current Spotlight Hero + Next Waiting Queue -->
        <div class="display-hero">
            
            <!-- CURRENTLY SERVING SPOTLIGHT (LEFT) -->
            <div class="hero-serving" id="heroCard">
                <div class="hero-badge">
                    <span class="pulse-dot"></span>
                    <span>NOW SERVING</span>
                </div>
                
                <div class="hero-ticket" id="heroTicketNumber">---</div>
                
                <div class="hero-counter" id="heroCounterName">Please Wait for Calling</div>
                
                <div class="hero-meta" id="heroMeta">
                    Consultation Room &bull; Waiting for next patient
                </div>

                <div id="heroPatientBadge" style="margin-top: 1.25rem;">
                    <!-- Patient type / Name preview -->
                </div>
            </div>

            <!-- UPCOMING PATIENT QUEUE (RIGHT) -->
            <div class="card" style="display: flex; flex-direction: column;">
                <div class="card-header">
                    <h2 class="card-title" style="font-size: 1.25rem;">
                        <span>⏳</span> Next In Line (Upcoming Patients)
                    </h2>
                    <span class="badge badge-waiting" id="upcomingCountBadge">0 Waiting</span>
                </div>
                
                <div class="upcoming-list" id="upcomingContainer" style="flex: 1; align-content: flex-start;">
                    <!-- Rendered via JS -->
                </div>

                <div style="margin-top: 1rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem; color: var(--text-secondary);">
                    <span>Please have your ticket slip and identification ready.</span>
                    <span style="color: var(--warning-light);">🔔 Listen for chime</span>
                </div>
            </div>

        </div>

        <!-- STATIONS / ACTIVE COUNTERS GRID -->
        <div style="margin-top: 1.5rem; margin-bottom: 5rem;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1rem;">
                <h2 style="font-size: 1.35rem; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 0.6rem;">
                    <span>🩺</span> Consultation Rooms & Service Counters
                </h2>
                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                    Updated automatically in real-time
                </div>
            </div>

            <div class="counter-grid" id="countersContainer">
                <!-- Rendered dynamically -->
            </div>
        </div>

    </main>

    <!-- TV BOTTOM TICKER -->
    <footer class="tv-footer-ticker">
        <div style="display: flex; align-items: center; gap: 2rem; overflow: hidden; white-space: nowrap;">
            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--success-light); font-weight: 700;">
                <span>✅ RECENTLY COMPLETED:</span>
                <span id="tickerCompleted" style="color: #fff; font-weight: 500;">None yet today</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--danger-light); font-weight: 700; margin-left: 2rem;">
                <span>⚠️ SKIPPED (APPROACH DESK):</span>
                <span id="tickerSkipped" style="color: #fff; font-weight: 500;">None</span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 1rem; color: var(--text-muted); font-size: 0.8rem;">
            <span>CareQueue Display 1.0</span>
            <span class="live-pill" style="padding: 0.2rem 0.6rem; font-size: 0.7rem;">Live Signal</span>
        </div>
    </footer>

    <!-- Audio & Scripts -->
    <script src="assets/js/app.js"></script>
    <script>
        let lastAnnouncedTicketId = null;
        let isInitialLoad = true;

        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.log('Error attempting fullscreen:', err);
                });
            } else {
                document.exitFullscreen();
            }
        }

        async function fetchDisplayData() {
            try {
                const res = await fetch('api/queue.php?action=display');
                const data = await res.json();
                if (!data.success) return;

                renderHero(data.last_called);
                renderUpcoming(data.upcoming);
                renderCounters(data.counters);
                renderTickers(data.completed, data.skipped);

                // Voice & Chime announcement trigger
                if (data.last_called && data.last_called.id) {
                    if (lastAnnouncedTicketId !== data.last_called.id) {
                        if (!isInitialLoad) {
                            // Flash hero card
                            const hero = document.getElementById('heroCard');
                            hero.classList.remove('tv-call-flash');
                            void hero.offsetWidth; // trigger reflow
                            hero.classList.add('tv-call-flash');

                            // Announce
                            App.announceTicket(
                                data.last_called.ticket_number,
                                `${data.last_called.counter_number} - ${data.last_called.counter_name}`,
                                data.last_called.service_name
                            );
                        }
                        lastAnnouncedTicketId = data.last_called.id;
                    }
                }
                isInitialLoad = false;

            } catch (err) {
                console.error('Display fetch error:', err);
            }
        }

        function renderHero(called) {
            const ticketEl = document.getElementById('heroTicketNumber');
            const counterEl = document.getElementById('heroCounterName');
            const metaEl = document.getElementById('heroMeta');
            const badgeEl = document.getElementById('heroPatientBadge');

            if (called) {
                ticketEl.textContent = called.ticket_number;
                counterEl.textContent = `${called.counter_number} &bull; ${called.counter_name}`;
                metaEl.innerHTML = `Doctor / Staff: <strong style="color: #fff;">${called.staff_name}</strong> &bull; Service: <span style="color: var(--primary-light);">${called.service_name}</span>`;
                
                // Mask patient name for HIPAA/Privacy compliance e.g. "M*** S***"
                const maskedName = maskPatientName(called.patient_name);
                badgeEl.innerHTML = `
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <span class="badge badge-priority" style="font-size: 0.85rem; padding: 0.35rem 0.8rem;">
                            Patient: ${maskedName} (${called.patient_type})
                        </span>
                        <span style="font-size: 0.85rem; color: var(--text-secondary);">
                            Called at ${called.called_time ? called.called_time.substring(11, 16) : ''}
                        </span>
                    </div>
                `;
            } else {
                ticketEl.textContent = '---';
                counterEl.textContent = 'Waiting for Next Patient Calling';
                metaEl.textContent = 'Please relax in the waiting lounge.';
                badgeEl.innerHTML = '';
            }
        }

        function maskPatientName(name) {
            if (!name) return 'Guest';
            return name.split(' ').map(part => {
                if (part.length <= 2) return part;
                return part[0] + '*'.repeat(Math.max(1, part.length - 2)) + part[part.length - 1];
            }).join(' ');
        }

        function renderUpcoming(upcoming) {
            const container = document.getElementById('upcomingContainer');
            const badge = document.getElementById('upcomingCountBadge');
            badge.textContent = `${upcoming ? upcoming.length : 0} Waiting`;

            container.innerHTML = '';
            if (!upcoming || upcoming.length === 0) {
                container.innerHTML = `
                    <div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 2.5rem 1rem;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🎉</div>
                        <div>No patients currently waiting in line.</div>
                    </div>
                `;
                return;
            }

            upcoming.forEach(item => {
                const div = document.createElement('div');
                div.className = 'upcoming-item';
                div.innerHTML = `
                    <div class="upcoming-ticket">${item.ticket_number}</div>
                    <div class="upcoming-service">${item.service_name}</div>
                    <div style="margin-top: 0.35rem;">
                        <span class="badge ${item.patient_type === 'Regular' ? 'badge-regular' : 'badge-priority'}" style="font-size: 0.65rem; padding: 0.15rem 0.45rem;">
                            ${item.patient_type}
                        </span>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        function renderCounters(counters) {
            const container = document.getElementById('countersContainer');
            container.innerHTML = '';

            if (!counters || counters.length === 0) {
                container.innerHTML = '<div style="color: var(--text-muted);">No active counters configured.</div>';
                return;
            }

            counters.forEach(c => {
                const isServing = c.serving_ticket && c.serving_ticket.trim() !== '';
                const card = document.createElement('div');
                card.className = `counter-card ${isServing ? 'active-serving' : ''}`;

                card.innerHTML = `
                    <div class="counter-card-header">
                        <span class="counter-num-tag">${c.counter_number}</span>
                        ${isServing ? '<span class="badge badge-serving">Serving</span>' : '<span class="badge badge-waiting">Ready</span>'}
                    </div>

                    <div style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem;">
                        ${c.name}
                    </div>

                    <div class="counter-ticket-display ${!isServing ? 'idle' : ''}">
                        ${isServing ? c.serving_ticket : 'Available'}
                    </div>

                    <div class="counter-staff-name" style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                        <span>👨‍⚕️ ${c.staff_name}</span>
                        ${isServing && c.service_name ? `<span style="font-size: 0.75rem; color: var(--primary-light);">${c.service_name}</span>` : ''}
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function renderTickers(completed, skipped) {
            const compEl = document.getElementById('tickerCompleted');
            if (completed && completed.length > 0) {
                compEl.innerHTML = completed.map(c => `
                    <span style="display: inline-flex; align-items: center; gap: 0.3rem; margin-right: 1rem;">
                        <strong style="color: var(--primary-light); font-family: 'JetBrains Mono', monospace;">${c.ticket_number}</strong> (${c.counter_number || 'Clinic'})
                    </span>
                `).join(' • ');
            } else {
                compEl.textContent = 'None yet today';
            }

            const skipEl = document.getElementById('tickerSkipped');
            if (skipped && skipped.length > 0) {
                skipEl.innerHTML = skipped.map(s => `
                    <span style="display: inline-flex; align-items: center; gap: 0.3rem; margin-right: 1rem;">
                        <strong style="color: var(--danger-light); font-family: 'JetBrains Mono', monospace;">${s.ticket_number}</strong>
                    </span>
                `).join(' • ');
            } else {
                skipEl.textContent = 'None';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchDisplayData();
            // Poll every 2.5 seconds for instantaneous response
            setInterval(fetchDisplayData, 2500);

            // User gesture helper for Web Audio
            document.body.addEventListener('click', () => {
                App.getAudioContext();
            }, { once: true });
        });
    </script>
</body>
</html>
