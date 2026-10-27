// auth.js — Session management & route protection for Zoloverse

const API_BASE = window.location.origin + window.location.pathname.replace(/[^/]*$/, '');

document.addEventListener("DOMContentLoaded", () => {
    applyAuthState();
    protectRoutes();
});

function getToken() {
    return localStorage.getItem("zoloverse_token");
}

function getUser() {
    const raw = localStorage.getItem("zoloverse_user");
    try {
        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function getAuthHeaders() {
    const token = getToken();
    const headers = { "Content-Type": "application/json" };
    if (token) {
        headers["Authorization"] = `Bearer ${token}`;
        headers["X-Authorization"] = `Bearer ${token}`;
    }
    return headers;
}

function isAdmin() {
    const user = getUser();
    return user && user.role === "admin";
}

function applyAuthState() {
    const token = getToken();
    const user = getUser();
    const authContainer = document.getElementById("navAuthContainer");
    if (!authContainer) return;

    if (token && user) {
        let html = '';
        if (user.role === 'admin') {
            html += `<a href="admin.html" class="nav-cta-btn admin-btn"><i class="fas fa-shield-alt"></i> Admin Panel</a>`;
        }
        html += `<button class="nav-logout-btn" onclick="executeLogout()" title="Sign Out (${user.name})"><i class="fas fa-sign-out-alt"></i></button>`;
        authContainer.innerHTML = html;
    } else {
        authContainer.innerHTML = `
            <a href="login.html" class="nav-signin-link" title="Admin Sign In"><i class="fas fa-user-lock"></i></a>
        `;
    }
}

function protectRoutes() {
    const page = window.location.pathname.split("/").pop();
    const token = getToken();
    const user = getUser();

    if (page === "admin.html") {
        if (!token || !user) {
            window.location.href = "login.html?redirect=admin.html";
            return;
        }
        if (user.role !== "admin") {
            alert("Administrator privileges are required to access the admin panel.");
            window.location.href = "login.html?redirect=admin.html";
            return;
        }
        injectAdminLogout();
    }
}

function injectAdminLogout() {
    const header = document.querySelector(".header-bar");
    if (!header || document.getElementById("adminLogoutBtn")) return;

    const user = getUser();
    const wrap = document.createElement("div");
    wrap.style.cssText = "display:flex; align-items:center; gap:16px;";
    wrap.innerHTML = `
        <span style="color:var(--text-muted); font-size:0.9rem;"><i class="fas fa-user-shield" style="color:var(--accent); margin-right:6px;"></i>${user?.name || "Admin"}</span>
        <button id="adminLogoutBtn" class="btn btn-secondary" style="padding:8px 16px; font-size:0.85rem;" onclick="executeLogout()">
            <i class="fas fa-sign-out-alt"></i> Sign Out
        </button>`;
    header.appendChild(wrap);
}

function executeLogout() {
    localStorage.removeItem("zoloverse_token");
    localStorage.removeItem("zoloverse_user");
    window.location.href = "login.html";
}

async function verifySession() {
    const token = getToken();
    if (!token) return false;
    try {
        const res = await fetch(`${API_BASE}auth.php?action=verify`, {
            headers: { 
                Authorization: `Bearer ${token}`,
                "X-Authorization": `Bearer ${token}`
            }
        });
        const data = await res.json();
        if (data.valid) {
            localStorage.setItem("zoloverse_user", JSON.stringify(data.user));
            return true;
        }
    } catch { /* session invalid */ }
    executeLogout();
    return false;
}
