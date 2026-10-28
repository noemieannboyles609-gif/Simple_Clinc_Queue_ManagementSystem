/**
 * Clinic Queue Management System - Core Client Library
 * Handles: Web Audio chime, Web Speech synthesis, Toasts, Modals, Audio Preferences
 */

const App = {
    audioEnabled: localStorage.getItem('clinic_audio_enabled') !== 'false', // default true
    audioContext: null,

    init() {
        this.updateAudioButtonState();
        this.startClock();
    },

    // ==========================================
    // AUDIO ENGINE (Web Audio API Chime + Speech)
    // ==========================================
    toggleAudio() {
        this.audioEnabled = !this.audioEnabled;
        localStorage.setItem('clinic_audio_enabled', this.audioEnabled);
        this.updateAudioButtonState();

        if (this.audioEnabled) {
            this.playChime();
            this.showToast('Voice & chime announcements enabled', 'success');
        } else {
            this.showToast('Announcements muted', 'warning');
        }
    },

    updateAudioButtonState() {
        const btns = document.querySelectorAll('.audio-btn');
        btns.forEach(btn => {
            if (this.audioEnabled) {
                btn.classList.add('enabled');
                btn.setAttribute('title', 'Voice Announcements: ON (Click to Mute)');
                btn.innerHTML = '🔊';
            } else {
                btn.classList.remove('enabled');
                btn.setAttribute('title', 'Voice Announcements: MUTED (Click to Enable)');
                btn.innerHTML = '🔇';
            }
        });
    },

    getAudioContext() {
        if (!this.audioContext) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                this.audioContext = new AudioContext();
            }
        }
        if (this.audioContext && this.audioContext.state === 'suspended') {
            this.audioContext.resume();
        }
        return this.audioContext;
    },

    // Gentle 2-tone melodic clinic chime (D5 -> A5 with harmonic decay)
    playChime() {
        if (!this.audioEnabled) return;
        try {
            const ctx = this.getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            
            // Tone 1: 587.33 Hz (D5)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(587.33, now);
            gain1.gain.setValueAtTime(0.25, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.6);

            // Tone 2: 880.00 Hz (A5)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(880.0, now + 0.28);
            gain2.gain.setValueAtTime(0.28, now + 0.28);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.2);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.28);
            osc2.stop(now + 1.2);

        } catch (e) {
            console.warn('Audio chime playback error:', e);
        }
    },

    // Vocal Speech Announcement using browser SpeechSynthesis
    announceTicket(ticketNumber, counterName, serviceName) {
        if (!this.audioEnabled) return;

        // Play chime first
        this.playChime();

        if ('speechSynthesis' in window) {
            // Space letters/digits in ticketNumber so "A002" sounds like "A - 0 - 0 - 2" instead of "a hundred two"
            const spokenTicket = ticketNumber.split('').join(' ');
            const speechText = `Now serving queue number ${spokenTicket}. Please proceed to ${counterName}.`;

            setTimeout(() => {
                window.speechSynthesis.cancel(); // clear queue
                const utterance = new SpeechSynthesisUtterance(speechText);
                utterance.rate = 0.9;
                utterance.pitch = 1.0;
                utterance.volume = 1.0;

                // Pick an English voice if available
                const voices = window.speechSynthesis.getVoices();
                const engVoice = voices.find(v => v.lang.startsWith('en') && (v.name.includes('Natural') || v.name.includes('Google') || v.name.includes('Female') || v.name.includes('Zira') || v.name.includes('Samantha')));
                if (engVoice) {
                    utterance.voice = engVoice;
                }

                window.speechSynthesis.speak(utterance);
            }, 600);
        }
    },

    // ==========================================
    // TOAST NOTIFICATIONS
    // ==========================================
    showToast(message, type = 'info') {
        let container = document.getElementById('toastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'toastContainer';
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' ? '✅' : (type === 'danger' ? '❌' : (type === 'warning' ? '⚠️' : 'ℹ️'));
        toast.innerHTML = `<span>${icon}</span><span style="flex:1;">${message}</span>`;
        
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(10px)';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    // ==========================================
    // MODAL DIALOGS
    // ==========================================
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('open');
            document.body.style.overflow = '';
        }
    },

    // ==========================================
    // LIVE CLOCK
    // ==========================================
    startClock() {
        const clockEls = document.querySelectorAll('.live-clock');
        if (!clockEls.length) return;

        const update = () => {
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateStr = now.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
            
            clockEls.forEach(el => {
                el.innerHTML = `<span>📅 ${dateStr}</span> <span style="font-weight: 700; color: #fff; margin-left: 0.5rem;">🕒 ${timeStr}</span>`;
            });
        };
        update();
        setInterval(update, 1000);
    },

    // ==========================================
    // PRINT TICKET HELPER
    // ==========================================
    printTicket() {
        window.print();
    }
};

// Auto initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();

    // Close modal when backdrop clicked
    document.querySelectorAll('.modal-backdrop').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                App.closeModal(modal.id);
            }
        });
    });
});
