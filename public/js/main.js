/* =========================
   THEME (CLARO / OSCURO / SISTEMA)
   ========================= */

(function initTheme() {
    const storedTheme = localStorage.getItem('theme');

    if (storedTheme === 'light' || storedTheme === 'dark') {
        document.documentElement.setAttribute('data-bs-theme', storedTheme);
    } else {
        applySystemTheme();
    }
})();

function applySystemTheme() {
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.documentElement.setAttribute(
        'data-bs-theme',
        prefersDark ? 'dark' : 'light'
    );
}

function setTheme(theme) {
    if (theme === 'system') {
        localStorage.removeItem('theme');
        applySystemTheme();
    } else {
        localStorage.setItem('theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
    }
}

/* Escuchar cambios del sistema */
window
    .matchMedia('(prefers-color-scheme: dark)')
    .addEventListener('change', () => {
        if (!localStorage.getItem('theme')) {
            applySystemTheme();
        }
    });

/* =========================
   SELECTOR VISUAL – CHECK ACTIVO
   ========================= */

document.addEventListener("DOMContentLoaded", function () {

    const themeButtons = document.querySelectorAll(".theme-option");

    function updateThemeUI(currentTheme) {
        themeButtons.forEach(btn => {
            const check = btn.querySelector(".theme-check");

            if (btn.dataset.theme === currentTheme) {
                btn.classList.add("active-theme");
                if (check) check.classList.remove("d-none");
            } else {
                btn.classList.remove("active-theme");
                if (check) check.classList.add("d-none");
            }
        });
    }

    function getCurrentTheme() {
        const stored = localStorage.getItem("theme");
        if (stored === "light" || stored === "dark") {
            return stored;
        }
        return "system";
    }

    // Estado inicial
    updateThemeUI(getCurrentTheme());

    // Click en opciones
    themeButtons.forEach(btn => {
        btn.addEventListener("click", function () {
            const selectedTheme = this.dataset.theme;
            setTheme(selectedTheme);
            updateThemeUI(selectedTheme);
        });
    });

});

/* =========================
   TOGGLE PASSWORD (GLOBAL)
   ========================= */
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');

    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-lock-fill');
        icon.classList.add('bi-eye-fill');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-fill');
        icon.classList.add('bi-lock-fill');
    }
}

/* =========================
   NOTIFICACIONES DEL DASHBOARD
   ========================= */
document.addEventListener("DOMContentLoaded", function () {
    let notifications = window.financeDashboardNotifications || [];
    const userId = window.financeDashboardUserId || "guest";
    const enableButton = document.getElementById("enableBrowserNotifications");
    const markReadButton = document.getElementById("markNotificationsRead");
    const badge = document.getElementById("financeNotificationBadge");
    const list = document.getElementById("financeNotificationList");
    const actions = document.getElementById("financeNotificationActions");
    const endpoint = window.financeNotificationsEndpoint;

    if (!("Notification" in window)) {
        if (enableButton) {
            enableButton.classList.add("d-none");
        }
    }

    function notificationSentStorageKey(notification) {
        return `finance_notification_sent_${userId}_${notification.id}`;
    }

    function notificationReadStorageKey(notification) {
        return `finance_notification_read_${userId}_${notification.id}`;
    }

    function unreadNotifications() {
        return notifications.filter(notification => !localStorage.getItem(notificationReadStorageKey(notification)));
    }

    function updatePermissionButton() {
        if (!enableButton) {
            return;
        }

        if (!("Notification" in window) || Notification.permission === "granted") {
            enableButton.classList.add("d-none");
            return;
        }

        enableButton.classList.remove("d-none");
    }

    function sendDashboardNotification(notification) {
        const key = notificationSentStorageKey(notification);

        if (localStorage.getItem(key)) {
            return;
        }

        if (!("Notification" in window) || Notification.permission !== "granted") {
            return;
        }

        new Notification(notification.title, {
            body: notification.message,
            icon: window.financeNotificationIcon || "/public/img/favicon.png"
        });

        localStorage.setItem(key, new Date().toISOString());
    }

    function sendPendingNotifications() {
        if (!("Notification" in window) || Notification.permission !== "granted") {
            return;
        }

        unreadNotifications().forEach(sendDashboardNotification);
    }

    function createNotificationItem(notification) {
        const item = document.createElement("div");
        item.className = "notification-item px-3 py-3";

        const wrapper = document.createElement("div");
        wrapper.className = "d-flex align-items-start gap-2";

        const dot = document.createElement("span");
        dot.className = `notification-dot notification-dot-${notification.type || "info"}`;

        const body = document.createElement("div");

        const title = document.createElement("div");
        title.className = "fw-semibold mb-1";
        title.textContent = notification.title || "Notificación";

        const message = document.createElement("div");
        message.className = "small text-muted";
        message.textContent = notification.message || "";

        body.appendChild(title);
        body.appendChild(message);
        wrapper.appendChild(dot);
        wrapper.appendChild(body);
        item.appendChild(wrapper);

        return item;
    }

    function renderNotifications() {
        const visibleNotifications = unreadNotifications();

        if (badge) {
            badge.textContent = visibleNotifications.length;
            badge.classList.toggle("d-none", visibleNotifications.length === 0);
        }

        if (actions) {
            actions.classList.toggle("d-none", visibleNotifications.length === 0);
        }

        updatePermissionButton();

        if (!list) {
            return;
        }

        list.innerHTML = "";

        if (!visibleNotifications.length) {
            const empty = document.createElement("div");
            empty.className = "px-3 py-4 text-center text-muted small";
            empty.textContent = "No tienes notificaciones activas por ahora.";
            list.appendChild(empty);
            return;
        }

        visibleNotifications.forEach(notification => {
            list.appendChild(createNotificationItem(notification));
        });
    }

    function syncNotifications() {
        if (!endpoint) {
            return;
        }

        fetch(endpoint, {
            headers: {
                "Accept": "application/json"
            },
            cache: "no-store",
            credentials: "same-origin"
        })
            .then(response => response.ok ? response.json() : null)
            .then(data => {
                if (!data || !data.ok || !Array.isArray(data.notifications)) {
                    return;
                }

                notifications = data.notifications;
                renderNotifications();
                sendPendingNotifications();
            })
            .catch(() => {});
    }

    if (enableButton) {
        enableButton.addEventListener("click", function () {
            if (!("Notification" in window)) {
                return;
            }

            Notification.requestPermission().then(function (permission) {
                if (permission === "granted") {
                    sendPendingNotifications();
                }

                renderNotifications();
            });
        });
    }

    if (markReadButton) {
        markReadButton.addEventListener("click", function () {
            unreadNotifications().forEach(notification => {
                localStorage.setItem(notificationReadStorageKey(notification), new Date().toISOString());
            });

            renderNotifications();
        });
    }

    renderNotifications();
    sendPendingNotifications();
    syncNotifications();

    if (endpoint) {
        window.setInterval(syncNotifications, 60000);
    }
});
