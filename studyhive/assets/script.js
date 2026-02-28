/* ================================================================
   StudyHive v3 — Bold & Flashy JS
================================================================ */

/* ===== RIPPLE EFFECT ON BUTTONS ===== */
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn');
    if (!btn) return;
    const r = document.createElement('span');
    r.className = 'ripple-effect';
    const rect = btn.getBoundingClientRect();
    r.style.left = (e.clientX - rect.left) + 'px';
    r.style.top  = (e.clientY - rect.top)  + 'px';
    btn.appendChild(r);
    setTimeout(() => r.remove(), 700);
});

/* ===== TOAST SYSTEM ===== */
function showToast(msg, type = 'info', duration = 3500) {
    const icons = { success: '🎉', error: '❌', warn: '⚠️', info: 'ℹ️' };
    const container = document.getElementById('toast-container');
    if (!container) return;
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<span class="toast-icon">${icons[type]||'ℹ️'}</span>
                   <span class="toast-msg">${msg}</span>
                   <button class="toast-close" onclick="dismissToast(this.parentElement)">×</button>`;
    container.appendChild(t);
    setTimeout(() => dismissToast(t), duration);
    return t;
}
function dismissToast(el) {
    if (!el || el.classList.contains('hiding')) return;
    el.classList.add('hiding');
    setTimeout(() => el.remove(), 350);
}

/* ===== MODAL SYSTEM ===== */
let activeModal = null;
function openModal(id) {
    const overlay = document.getElementById('modal-overlay');
    const modal   = document.getElementById(id);
    if (!modal || !overlay) return;
    overlay.classList.add('active');
    modal.classList.add('active');
    activeModal = modal;
    document.body.style.overflow = 'hidden';
}
function closeModal() {
    if (activeModal) activeModal.classList.remove('active');
    const overlay = document.getElementById('modal-overlay');
    if (overlay) overlay.classList.remove('active');
    activeModal = null;
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

/* ===== CONFETTI ===== */
function launchConfetti(count = 60) {
    const colors = ['#2563eb','#7c3aed','#ec4899','#06b6d4','#f59e0b','#22c55e','#ef4444','#fff'];
    for (let i = 0; i < count; i++) {
        setTimeout(() => {
            const p = document.createElement('div');
            p.className = 'confetti-piece';
            p.style.cssText = `
                left: ${Math.random()*100}vw;
                top: -20px;
                background: ${colors[Math.floor(Math.random()*colors.length)]};
                transform: rotate(${Math.random()*360}deg);
                width: ${6+Math.random()*10}px;
                height: ${6+Math.random()*10}px;
                border-radius: ${Math.random() > .5 ? '50%' : '2px'};
                animation-duration: ${1.5+Math.random()*2}s;
                animation-delay: ${Math.random()*.5}s;
            `;
            document.body.appendChild(p);
            setTimeout(() => p.remove(), 4000);
        }, i * 25);
    }
}

/* ===== ANIMATE NUMBERS ===== */
function animateNumbers() {
    document.querySelectorAll('.stat-card .num').forEach(el => {
        const raw = el.textContent.trim();
        const target = parseInt(raw.replace(/[^\d]/g, ''));
        if (isNaN(target) || target === 0) return;
        const isSpecial = /[^\d]/.test(raw);
        if (isSpecial) return;
        el.textContent = '0';
        const dur = Math.min(1200, Math.max(400, target * 4));
        const start = performance.now();
        function update(now) {
            const p = Math.min((now - start) / dur, 1);
            const eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.floor(eased * target);
            if (p < 1) requestAnimationFrame(update);
            else el.textContent = target;
        }
        requestAnimationFrame(update);
    });
}

/* ===== LIVE SEARCH FILTER ===== */
function initBrowseFilter() {
    const searchInput   = document.getElementById('searchInput');
    const subjectFilter = document.getElementById('subjectFilter');
    const semFilter     = document.getElementById('semFilter');
    const noteCards     = document.querySelectorAll('.note-card');
    const noResults     = document.getElementById('noResults');
    if (!searchInput) return;

    function filterCards() {
        const q   = searchInput.value.toLowerCase().trim();
        const sub = subjectFilter.value.toLowerCase();
        const sem = semFilter.value;
        let vis = 0;
        noteCards.forEach((card, i) => {
            const title   = (card.dataset.title   || '').toLowerCase();
            const subject = (card.dataset.subject || '').toLowerCase();
            const semester = card.dataset.semester || '';
            const match = (title.includes(q) || subject.includes(q)) && (!sub || subject === sub) && (!sem || semester === sem);
            card.style.display = match ? '' : 'none';
            if (match) { card.style.animationDelay = (vis * 0.05) + 's'; vis++; }
        });
        if (noResults) noResults.style.display = vis === 0 ? 'block' : 'none';
    }
    searchInput.addEventListener('input', filterCards);
    subjectFilter.addEventListener('change', filterCards);
    semFilter.addEventListener('change', filterCards);
}

/* ===== STAR RATING ===== */
function initStarRating(noteId, currentRating) {
    const container = document.getElementById('stars-' + noteId);
    if (!container) return;
    const stars = container.querySelectorAll('.star');
    stars.forEach(star => {
        star.addEventListener('mouseenter', () => {
            const v = parseInt(star.dataset.value);
            stars.forEach(s => s.classList.toggle('hover', parseInt(s.dataset.value) <= v));
        });
        star.addEventListener('mouseleave', () => stars.forEach(s => s.classList.remove('hover')));
        star.addEventListener('click', () => submitRating(noteId, parseInt(star.dataset.value), stars));
    });
    stars.forEach(s => s.classList.toggle('filled', parseInt(s.dataset.value) <= (currentRating || 0)));
}

function submitRating(noteId, rating, starEls) {
    fetch('/studyhive/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'rate', note_id: noteId, stars: rating })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            starEls.forEach(s => {
                const v = parseInt(s.dataset.value);
                s.classList.toggle('filled', v <= rating);
                if (v === rating) { s.classList.add('pop'); setTimeout(() => s.classList.remove('pop'), 400); }
            });
            const avgEl = document.getElementById('avg-' + noteId);
            const cntEl = document.getElementById('cnt-' + noteId);
            if (avgEl && data.avg) avgEl.textContent = parseFloat(data.avg).toFixed(1);
            if (cntEl && data.count) cntEl.textContent = '(' + data.count + ')';
            showToast('⭐ Rating saved!', 'success');
        } else { showToast(data.message || 'Error saving rating', 'error'); }
    })
    .catch(() => showToast('Network error', 'error'));
}

/* ===== BOOKMARK ===== */
function toggleBookmark(noteId, btn) {
    btn.style.transform = 'scale(1.5) rotate(-20deg)';
    setTimeout(() => btn.style.transform = '', 300);
    fetch('/studyhive/api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bookmark', note_id: noteId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            btn.textContent = data.bookmarked ? '🔖' : '🔲';
            btn.classList.toggle('saved', data.bookmarked);
            showToast(data.bookmarked ? '🔖 Saved to bookmarks!' : 'Bookmark removed', data.bookmarked ? 'success' : 'info');
        }
    })
    .catch(() => showToast('Network error', 'error'));
}

/* ===== FILE UPLOAD ===== */
function initFileUpload() {
    const fi = document.getElementById('fileInput');
    const fl = document.getElementById('fileLabel');
    const fd = document.querySelector('.file-drop');
    if (!fi) return;
    fi.addEventListener('change', () => {
        const f = fi.files[0];
        if (f && fl) {
            const kb = (f.size/1024).toFixed(0);
            fl.innerHTML = `<strong style="color:var(--blue)">${f.name}</strong> <span style="color:var(--muted)">(${kb} KB)</span>`;
            fd && fd.classList.add('active');
            showToast('📎 File selected: ' + f.name, 'info', 2500);
        }
    });
    // Drag & drop
    if (fd) {
        fd.addEventListener('dragover', e => { e.preventDefault(); fd.classList.add('active'); });
        fd.addEventListener('dragleave', () => fd.classList.remove('active'));
        fd.addEventListener('drop', e => {
            e.preventDefault();
            fi.files = e.dataTransfer.files;
            fi.dispatchEvent(new Event('change'));
        });
    }
}

/* ===== CONFIRM MODAL ===== */
function confirmDelete(url, name) {
    const modal = document.getElementById('confirm-modal');
    if (!modal) return;
    document.getElementById('confirm-msg').textContent = `Are you sure you want to delete "${name}"? This cannot be undone.`;
    document.getElementById('confirm-ok').onclick = () => { window.location.href = url; };
    openModal('confirm-modal');
}

/* ===== CARD SHINE INIT ===== */
function initCardShine() {
    document.querySelectorAll('.card').forEach(card => {
        if (!card.querySelector('.card-shine')) {
            const shine = document.createElement('div');
            shine.className = 'card-shine';
            card.appendChild(shine);
        }
    });
}

/* ===== AUTO DISMISS ALERTS ===== */
function initAlerts() {
    document.querySelectorAll('.alert').forEach(a => {
        setTimeout(() => {
            a.style.transition = 'opacity .5s, transform .5s';
            a.style.opacity = '0'; a.style.transform = 'translateX(-10px)';
            setTimeout(() => a.remove(), 500);
        }, 4500);
    });
}

/* ===== PAGE LOAD FLASH ===== */
function pageLoadFlash() {
    const flash = document.createElement('div');
    flash.style.cssText = 'position:fixed;inset:0;background:linear-gradient(135deg,rgba(37,99,235,.06),rgba(124,58,237,.06));pointer-events:none;z-index:9999;animation:fadeIn .4s ease forwards reverse;animation-delay:.1s';
    document.body.appendChild(flash);
    setTimeout(() => flash.remove(), 600);
}

/* ===== INIT ===== */
document.addEventListener('DOMContentLoaded', () => {
    pageLoadFlash();
    initBrowseFilter();
    initFileUpload();
    initAlerts();
    initCardShine();
    setTimeout(animateNumbers, 200);
});
