<?php
/**
 * announcement_widget.php — Shared include
 *
 * Usage (PC view, read-only):
 *   <?php require_once 'announcement_widget.php'; ?>
 *
 * Usage (QA view, with compose panel):
 *   <?php $isQA = true; require_once 'announcement_widget.php'; ?>
 */
$isQA = $isQA ?? false;
?>
<style>
/* ── Announcement Widget ─────────────────────────────── */
#ann-widget {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
    overflow: hidden;
    margin-bottom: 24px;
}

#ann-widget .ann-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: linear-gradient(135deg, #0d6efd, #002b6b);
    color: #fff;
}

#ann-widget .ann-header h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: .4px;
}

#ann-widget .ann-body { padding: 0; }

/* Individual announcement card */
.ann-item {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 14px 20px;
    border-bottom: 1px solid #f0f0f0;
    transition: background .15s;
}
.ann-item:last-child { border-bottom: none; }
.ann-item:hover { background: #f8faff; }

/* Priority dot */
.ann-dot {
    width: 10px; height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 5px;
}
.ann-dot.urgent   { background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,.18); }
.ann-dot.important { background: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,.18); }
.ann-dot.normal   { background: #3b82f6; }

/* Priority badge */
.ann-badge {
    font-size: 9px; font-weight: 700;
    padding: 2px 7px; border-radius: 999px;
    text-transform: uppercase; letter-spacing: .5px;
    flex-shrink: 0;
}
.ann-badge.urgent   { background: #fee2e2; color: #b91c1c; }
.ann-badge.important { background: #fef3c7; color: #92400e; }
.ann-badge.normal   { background: #dbeafe; color: #1d4ed8; }

.ann-title  { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 3px; }
.ann-body-txt { font-size: 12px; color: #475569; line-height: 1.5; }
.ann-meta   { font-size: 10px; color: #94a3b8; margin-top: 4px; }

/* Delete btn (QA only) */
.ann-del-btn {
    background: none; border: none; cursor: pointer;
    color: #cbd5e1; font-size: 13px; padding: 2px 4px;
    border-radius: 4px; transition: color .2s;
    flex-shrink: 0; margin-left: auto;
}
.ann-del-btn:hover { color: #ef4444; }

/* Empty state */
.ann-empty {
    padding: 30px; text-align: center;
    color: #94a3b8; font-size: 13px;
}

/* ── Compose Panel (QA only) ──────────────────────────── */
#ann-compose-wrap { border-top: 2px dashed #e2e8f0; padding: 18px 20px; }
#ann-compose-wrap textarea,
#ann-compose-wrap input[type=text],
#ann-compose-wrap input[type=date],
#ann-compose-wrap select {
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 8px 12px;
    font-size: 13px;
    font-family: 'Poppins', sans-serif;
    width: 100%;
    margin-bottom: 10px;
    transition: border-color .2s;
}
#ann-compose-wrap textarea:focus,
#ann-compose-wrap input:focus,
#ann-compose-wrap select:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 3px rgba(13,110,253,.1);
}
#ann-compose-wrap textarea { resize: vertical; min-height: 70px; }
</style>

<!-- ── Widget HTML ── -->
<div id="ann-widget">
    <div class="ann-header">
        <h6><i class="fas fa-bullhorn me-2"></i>Announcements from QA</h6>
        <?php if ($isQA): ?>
        <button class="btn btn-sm btn-light" style="font-size:12px;padding:4px 12px;border-radius:8px;"
                onclick="document.getElementById('ann-compose-wrap').classList.toggle('d-none')">
            <i class="fas fa-plus me-1"></i> New
        </button>
        <?php endif; ?>
    </div>

    <div class="ann-body">
        <div id="ann-list">
            <div class="ann-empty"><i class="fas fa-circle-notch fa-spin me-2"></i>Loading…</div>
        </div>

        <?php if ($isQA): ?>
        <!-- Compose panel -->
        <div id="ann-compose-wrap" class="d-none">
            <p style="font-size:12px;font-weight:700;color:#0d6efd;margin-bottom:10px;">
                <i class="fas fa-edit me-1"></i> Compose Announcement
            </p>
            <input type="text" id="ann-new-title" placeholder="Title (e.g. Reminder: Submit Reports by Friday)">
            <textarea id="ann-new-body" placeholder="Message body…"></textarea>
            <div style="display:flex;gap:10px;">
                <select id="ann-new-priority" style="flex:1">
                    <option value="normal">🔵 Normal</option>
                    <option value="important">🟡 Important</option>
                    <option value="urgent">🔴 Urgent</option>
                </select>
                <div style="flex:1">
                    <label style="font-size:11px;color:#6c757d;display:block;margin-bottom:4px;">
                        Expires (optional)
                    </label>
                    <input type="date" id="ann-new-expires" style="margin-bottom:0">
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:4px;">
                <button class="btn btn-primary btn-sm" style="border-radius:8px;font-size:12px;padding:6px 18px;"
                        onclick="annCreate()">
                    <i class="fas fa-paper-plane me-1"></i> Post Announcement
                </button>
                <button class="btn btn-outline-secondary btn-sm" style="border-radius:8px;font-size:12px;"
                        onclick="document.getElementById('ann-compose-wrap').classList.add('d-none')">
                    Cancel
                </button>
            </div>
            <div id="ann-post-msg" style="font-size:11px;margin-top:8px;"></div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function() {
    const IS_QA = <?= $isQA ? 'true' : 'false' ?>;

    function priorityClass(p) {
        return p === 'urgent' ? 'urgent' : p === 'important' ? 'important' : 'normal';
    }

    function formatDate(dt) {
        if (!dt) return '';
        const d = new Date(dt.replace(' ', 'T'));
        return d.toLocaleDateString('en-MY', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
    }

    function renderList(items) {
        const el = document.getElementById('ann-list');
        if (!items.length) {
            el.innerHTML = '<div class="ann-empty"><i class="fas fa-inbox fa-lg mb-2 d-block opacity-25"></i>No announcements at the moment.</div>';
            return;
        }
        el.innerHTML = items.map(a => {
            const pc = priorityClass(a.priority);
            const delBtn = IS_QA
                ? `<button class="ann-del-btn" onclick="annDelete(${a.id})" title="Delete"><i class="fas fa-times"></i></button>`
                : '';
            return `
            <div class="ann-item" id="ann-item-${a.id}">
                <span class="ann-dot ${pc}"></span>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px;">
                        <span class="ann-title">${escHtml(a.title)}</span>
                        <span class="ann-badge ${pc}">${pc}</span>
                    </div>
                    <div class="ann-body-txt">${escHtml(a.body).replace(/\n/g,'<br>')}</div>
                    <div class="ann-meta">
                        <i class="fas fa-user-tie me-1"></i>${escHtml(a.created_by)}
                        &nbsp;·&nbsp;
                        <i class="fas fa-clock me-1"></i>${formatDate(a.created_at)}
                        ${a.expires_at ? `&nbsp;·&nbsp;<i class="fas fa-hourglass-end me-1"></i>Expires ${a.expires_at}` : ''}
                    </div>
                </div>
                ${delBtn}
            </div>`;
        }).join('');
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function loadAnnouncements() {
        fetch('announcements.php?action=list')
            .then(r => r.json())
            .then(d => { if (d.ok) renderList(d.data); })
            .catch(() => {
                document.getElementById('ann-list').innerHTML =
                    '<div class="ann-empty text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Could not load announcements.</div>';
            });
    }

    // Expose to global scope for onclick handlers
    window.annDelete = function(id) {
        if (!confirm('Delete this announcement?')) return;
        const body = new URLSearchParams({ action: 'delete', id });
        fetch('announcements.php', { method: 'POST', body })
            .then(r => r.json())
            .then(d => { if (d.ok) document.getElementById('ann-item-' + id).remove(); });
    };

    window.annCreate = function() {
        const title    = document.getElementById('ann-new-title').value.trim();
        const body_txt = document.getElementById('ann-new-body').value.trim();
        const priority = document.getElementById('ann-new-priority').value;
        const expires  = document.getElementById('ann-new-expires').value;
        const msgEl    = document.getElementById('ann-post-msg');

        if (!title || !body_txt) {
            msgEl.style.color = '#ef4444';
            msgEl.textContent = 'Please fill in the title and message.';
            return;
        }

        const params = new URLSearchParams({ action:'create', title, body: body_txt, priority, expires });
        fetch('announcements.php', { method:'POST', body: params })
            .then(r => r.json())
            .then(d => {
                if (d.ok) {
                    msgEl.style.color = '#16a34a';
                    msgEl.textContent = '✓ Posted!';
                    document.getElementById('ann-new-title').value   = '';
                    document.getElementById('ann-new-body').value    = '';
                    document.getElementById('ann-new-expires').value = '';
                    document.getElementById('ann-new-priority').value = 'normal';
                    loadAnnouncements();
                    setTimeout(() => msgEl.textContent = '', 3000);
                } else {
                    msgEl.style.color = '#ef4444';
                    msgEl.textContent = d.msg || 'Error posting.';
                }
            });
    };

    // Load on page ready
    loadAnnouncements();

    // Auto-refresh every 60 s (for PC view to get new ones live)
    setInterval(loadAnnouncements, 60000);
})();
</script>