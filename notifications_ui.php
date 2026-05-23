<?php // notifications_ui.php — include this inside any page that needs the bell ?>
<style>
.notif-bell {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 9998;
    cursor: pointer;
    width: 52px; height: 52px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0d6efd, #002b6b);
    box-shadow: 0 4px 20px rgba(13,110,253,0.45);
    display: flex; align-items: center; justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s;
}
.notif-bell:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 8px 28px rgba(13,110,253,0.55);
}
.notif-bell:active {
    transform: scale(0.96);
}

.notif-dot {
    position: absolute; top: 0px; right: 0px;
    min-width: 18px; height: 18px;
    background: #ef4444; color: white;
    border-radius: 999px; font-size: 10px; font-weight: 700;
    display: none; align-items: center; justify-content: center;
    padding: 0 4px; line-height: 1;
}
.notif-dot.active { display: flex; }

.notif-dropdown {
    position: fixed;
    bottom: 90px;
    right: 28px;
    width: 360px; background: white;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.15);
    z-index: 9999; display: none;
    overflow: hidden; border: 1px solid #e5e7eb;
    font-family: 'Poppins', sans-serif;
}
.notif-dropdown.open { display: block; }

.notif-header {
    padding: 14px 16px 10px;
    display: flex; justify-content: space-between; align-items: center;
    border-bottom: 1px solid #f3f4f6;
}
.notif-header .notif-title { font-size: 14px; font-weight: 700; color: #111827; }
.notif-mark-all {
    font-size: 11px; color: #6b7280; cursor: pointer;
    padding: 3px 8px; border-radius: 6px;
    border: 1px solid #e5e7eb; transition: all 0.15s;
    text-decoration: none;
}
.notif-mark-all:hover { background: #f3f4f6; color: #111827; }

.notif-section-label {
    padding: 8px 16px 4px;
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.8px;
    color: #9ca3af; background: #fafafa;
    border-bottom: 1px solid #f3f4f6;
}

.notif-list { max-height: 380px; overflow-y: auto; }
.notif-list::-webkit-scrollbar { width: 4px; }
.notif-list::-webkit-scrollbar-track { background: #f9fafb; }
.notif-list::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 4px; }

.notif-item {
    padding: 12px 16px; border-bottom: 1px solid #f9fafb;
    cursor: pointer; transition: background 0.15s;
    display: flex; align-items: flex-start; gap: 10px;
}
.notif-item:hover { background: #f8faff; }
.notif-item:last-child { border-bottom: none; }
.notif-item.unread { background: #eff6ff; border-left: 3px solid #3b82f6; }
.notif-item.unread:hover { background: #dbeafe; }
.notif-item.read { opacity: 0.7; }

.notif-icon {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; flex-shrink: 0; margin-top: 1px;
}
.notif-icon.overdue    { background: #fee2e2; color: #dc2626; }
.notif-icon.incomplete { background: #fef3c7; color: #d97706; }

.notif-msg  { font-size: 12px; color: #374151; line-height: 1.5; margin-bottom: 3px; }
.notif-time { font-size: 10px; color: #9ca3af; }

.notif-empty {
    padding: 32px 16px; text-align: center;
    color: #9ca3af; font-size: 12px;
}
.notif-empty i { font-size: 28px; margin-bottom: 8px; display: block; opacity: 0.4; }

/* Banner strip */
.notif-banner-strip { margin-bottom: 16px; }
.notif-banner {
    background: #fffbeb; border: 1px solid #fde68a;
    border-left: 4px solid #f59e0b;
    border-radius: 10px; padding: 11px 14px; margin-bottom: 8px;
    font-size: 12px; color: #92400e;
    display: flex; align-items: flex-start; gap: 8px;
    animation: slideDown 0.3s ease;
}
.notif-banner.danger {
    background: #fef2f2; border-color: #fecaca;
    border-left-color: #ef4444; color: #7f1d1d;
}
@keyframes slideDown {
    from { opacity:0; transform:translateY(-6px); }
    to   { opacity:1; transform:translateY(0); }
}
.notif-banner i { margin-top: 1px; flex-shrink: 0; }
.close-banner {
    margin-left: auto; cursor: pointer;
    opacity: 0.5; font-size: 11px; flex-shrink: 0;
}
.close-banner:hover { opacity: 1; }
</style>

<!-- Bell icon -->
<div class="notif-bell" id="notifBell" onclick="toggleNotifDropdown(event)">
    <i class="fas fa-bell" style="font-size:1.15rem; color:white; opacity:0.9"></i>
    <span class="notif-dot" id="notifDot"></span>

    <div class="notif-dropdown" id="notifDropdown" onclick="event.stopPropagation()">
        <div class="notif-header">
            <span class="notif-title">Notifications</span>
            <a class="notif-mark-all" onclick="markAllRead()">Mark all as read</a>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-empty">
                <i class="fas fa-bell-slash"></i>
                No notifications yet
            </div>
        </div>
    </div>
</div>

<script>
let _dropdownOpen = false;

function _timeAgo(dateStr) {
    const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
    if (diff < 60)      return 'Just now';
    if (diff < 3600)    return Math.floor(diff/60) + ' min ago';
    if (diff < 86400)   return Math.floor(diff/3600) + ' hr ago';
    if (diff < 604800)  return Math.floor(diff/86400) + ' day' + (Math.floor(diff/86400)>1?'s':'') + ' ago';
    if (diff < 2592000) return Math.floor(diff/604800) + ' week' + (Math.floor(diff/604800)>1?'s':'') + ' ago';
    return Math.floor(diff/2592000) + ' month' + (Math.floor(diff/2592000)>1?'s':'') + ' ago';
}

function _isToday(dateStr) {
    const d = new Date(dateStr), t = new Date();
    return d.getDate()===t.getDate() && d.getMonth()===t.getMonth() && d.getFullYear()===t.getFullYear();
}

function _icon(type) {
    const cls = type === 'overdue_meeting' ? 'overdue' : 'incomplete';
    const ico = type === 'overdue_meeting' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
    return `<div class="notif-icon ${cls}"><i class="fas ${ico}"></i></div>`;
}

function _renderList(data) {
    const list   = document.getElementById('notifList');
    const today  = data.filter(n => _isToday(n.created_at));
    const older  = data.filter(n => !_isToday(n.created_at));

    if (data.length === 0) {
        list.innerHTML = `<div class="notif-empty"><i class="fas fa-bell-slash"></i>You're all caught up!</div>`;
        return;
    }

    let html = '';

    if (today.length > 0) {
        html += `<div class="notif-section-label">Today</div>`;
        today.forEach(n => {
            html += `<div class="notif-item ${n.is_read==0?'unread':'read'}" onclick="markRead(${n.id})">
                ${_icon(n.type)}
                <div><div class="notif-msg">${n.message}</div>
                <div class="notif-time">${_timeAgo(n.created_at)}</div></div>
            </div>`;
        });
    }

    if (older.length > 0) {
        html += `<div class="notif-section-label">Older</div>`;
        older.forEach(n => {
            html += `<div class="notif-item ${n.is_read==0?'unread':'read'}" onclick="markRead(${n.id})">
                ${_icon(n.type)}
                <div><div class="notif-msg">${n.message}</div>
                <div class="notif-time">${_timeAgo(n.created_at)}</div></div>
            </div>`;
        });
    }

    list.innerHTML = html;
}

function loadNotifications() {
    fetch('get_notifications.php?action=fetch')
    .then(r => r.json())
    .then(data => {
        const unread = data.filter(n => n.is_read == 0);

        // Dot badge
        const dot = document.getElementById('notifDot');
        if (unread.length > 0) {
            dot.classList.add('active');
            dot.textContent = unread.length > 9 ? '9+' : unread.length;
        } else {
            dot.classList.remove('active');
            dot.textContent = '';
        }

        // Dropdown
        _renderList(data);

        // Banner strip
        const strip = document.getElementById('notifBannerStrip');
        if (!strip) return;
        if (unread.length > 0) {
           strip.innerHTML = unread.slice(0, 3).map(n => `
    <div class="notif-banner ${n.type==='overdue_meeting'?'danger':''}" id="banner-${n.id}" style="pointer-events:auto">
                    <i class="fas ${n.type==='overdue_meeting'?'fa-exclamation-circle':'fa-exclamation-triangle'}"></i>
                    <span>${n.message}</span>
                    <span class="close-banner" onclick="dismissBanner(${n.id})">✕</span>
                </div>`).join('');
        } else {
            strip.innerHTML = '';
        }
    })
    .catch(() => {});
}

function dismissBanner(id) {
    const el = document.getElementById('banner-' + id);
    if (el) el.style.display = 'none';
}

function toggleNotifDropdown(e) {
    e.stopPropagation();
    _dropdownOpen = !_dropdownOpen;
    document.getElementById('notifDropdown').classList.toggle('open', _dropdownOpen);
}

function markRead(id) {
    fetch('get_notifications.php?action=mark_read&id=' + id)
    .then(() => loadNotifications());
}

function markAllRead() {
    fetch('get_notifications.php?action=mark_all_read')
    .then(() => loadNotifications());
}

document.addEventListener('click', function(e) {
    if (!document.getElementById('notifBell')?.contains(e.target)) {
        document.getElementById('notifDropdown')?.classList.remove('open');
        _dropdownOpen = false;
    }
});

loadNotifications();
setInterval(loadNotifications, 60000);
</script>