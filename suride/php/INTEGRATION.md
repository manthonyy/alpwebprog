# SuRide — Backend Integration Guide

> **Audience:** developer connecting the existing single-file frontend to this PHP backend.  
> The frontend (`suride_updated.html`) is **not** to be restructured — only the JS auth/data calls need swapping.

---

## 1. File Structure

```
suride/
├── index.html            ← existing single-file frontend (unchanged)
└── php/
    ├── db.php            ← PDO connection + shared helpers
    ├── login.php         ← POST  /php/login.php
    ├── register.php      ← POST  /php/register.php
    ├── logout.php        ← POST  /php/logout.php
    ├── get_cars.php      ← GET   /php/get_cars.php
    ├── add_car.php       ← POST  /php/add_car.php  (admin)
    ├── update_car.php    ← POST  /php/update_car.php  (admin)
    ├── delete_car.php    ← POST  /php/delete_car.php  (admin)
    └── schema.sql        ← MySQL DDL + seed data
```

---

## 2. Server Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 8.1+ |
| MySQL | 5.7+ / MariaDB 10.4+ |
| Extensions | `pdo_mysql`, `json` |
| Web server | Apache / Nginx / Laragon (local) |

---

## 3. Setup Steps

### A. Create the database

```bash
mysql -u root -p < php/schema.sql
```

### B. Configure credentials

Open `php/db.php` and update:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'suride_db');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

### C. Place files on server

The `index.html` and `/php` folder must be served from the same origin to avoid CORS issues.  
For local dev use **Laragon** or **XAMPP** — place everything in `htdocs/suride/`.

---

## 4. Replacing Frontend Dummy Auth

### 4A — Replace `handleLogin()`

Find the current `handleLogin()` function in `index.html` and replace the `setTimeout` mock block with:

```js
async function handleLogin() {
  const email    = document.getElementById('lEmail').value.trim().toLowerCase();
  const password = document.getElementById('lPassword').value.trim();
  // ... existing validation ...

  const btn = document.getElementById('loginSubmitBtn');
  btn.textContent = 'Signing in…'; btn.disabled = true;

  try {
    const res  = await fetch('php/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password }),
    });
    const data = await res.json();

    if (data.success) {
      // Store in sessionStorage to keep nav working exactly as before
      sessionStorage.setItem('suride_user', JSON.stringify(data.user));
      showLoginAlert(`Welcome back, ${data.user.name}!`, 'success');
      updateNavAuth();
      setTimeout(() => {
        if (data.user.role === 'admin') showView('dashboard');
        else showView('landing');
      }, 800);
    } else {
      showLoginAlert(data.message || 'Invalid email or password.', 'error');
    }
  } catch (err) {
    showLoginAlert('Server error. Please try again.', 'error');
  } finally {
    btn.textContent = 'Sign In'; btn.disabled = false;
  }
}
```

### 4B — Replace `handleRegister()`

Replace the `setTimeout` block inside `handleRegister()`:

```js
async function handleRegister() {
  if (!document.getElementById('rAgreeTerms').checked) {
    document.getElementById('rTermsErr').classList.add('show'); return;
  }

  const btn = document.getElementById('regSubmitBtn');
  btn.textContent = 'Creating…'; btn.disabled = true;

  const payload = {
    first_name: document.getElementById('rFirstName').value.trim(),
    last_name:  document.getElementById('rLastName').value.trim(),
    email:      document.getElementById('rEmail').value.trim(),
    phone:      document.getElementById('rPhone').value.trim(),
    password:   document.getElementById('rPassword').value,
  };

  try {
    const res  = await fetch('php/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      sessionStorage.setItem('suride_user', JSON.stringify(data.user));
      document.getElementById('rFormStep3').style.display = 'none';
      document.getElementById('rFooterLink').style.display = 'none';
      document.getElementById('rSuccessScreen').style.display = 'block';
      document.getElementById('rSuccessMsg').textContent =
        `Welcome, ${data.user.name}! Your account is ready.`;
      updateNavAuth();
    } else {
      // Show first server-side error in the alert banner
      const msg = data.errors
        ? Object.values(data.errors)[0]
        : (data.message || 'Registration failed.');
      const alert = document.getElementById('regAlert');
      alert.textContent = msg;
      alert.style.display = 'block';
    }
  } catch (err) {
    const alert = document.getElementById('regAlert');
    alert.textContent = 'Server error. Please try again.';
    alert.style.display = 'block';
  } finally {
    btn.textContent = 'Create Account'; btn.disabled = false;
  }
}
```

### 4C — Replace `handleLogout()`

```js
async function handleLogout() {
  await fetch('php/logout.php', { method: 'POST' });
  sessionStorage.removeItem('suride_user');
  updateNavAuth();
  showView('landing');
}
```

---

## 5. Replacing Dummy Fleet Data

Find `renderFleetView(FLEET_DATA)` calls in `showView()` and the bottom of the file.  
Add this helper and call it instead:

```js
async function loadFleetFromAPI(filters = {}) {
  const params = new URLSearchParams(filters).toString();
  try {
    const res  = await fetch(`php/get_cars.php?${params}`);
    const data = await res.json();
    if (data.success) {
      renderFleetView(data.cars);   // data.cars shape matches FLEET_DATA
    }
  } catch (err) {
    console.error('Fleet load error:', err);
    renderFleetView(FLEET_DATA);   // fall back to local data if API fails
  }
}
```

In `showView()`:
```js
if (v === 'fleet') {
  loadFleetFromAPI();              // ← replaces renderFleetView(FLEET_DATA)
  fleetActiveCat = '';
  document.querySelectorAll('.fleet-filter-btn').forEach((b,i) =>
    b.classList.toggle('active', i === 0));
}
```

For category filter buttons, pass `?category=SUV` etc.:
```js
function filterFleet(cat) {
  fleetActiveCat = cat;
  loadFleetFromAPI(cat ? { category: cat } : {});
}
```

---

## 6. Admin CRUD (Dashboard Fleet Management)

When you build the admin "Add / Edit / Delete" UI in the dashboard, use these calls:

```js
// Add car
await fetch('php/add_car.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ brand, model, year, category, ... }),
});

// Update car
await fetch('php/update_car.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ id: 4, status: 'available', price_per_day: 3600000 }),
});

// Delete car
await fetch('php/delete_car.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ id: 4 }),
});
```

All three endpoints require an active admin session (PHP `$_SESSION`).

---

## 7. Session vs sessionStorage

| | Current (dummy) | After integration |
|---|---|---|
| Auth store | `sessionStorage` (browser) | PHP `$_SESSION` (server) |
| Login check | JS only | Server validates on every API call |
| Role routing | JS `user.role` | PHP `requireAdmin()` guards APIs |
| Logout | `sessionStorage.removeItem` | `session_destroy()` + clear storage |

`sessionStorage` is still used as a **UI cache** after login — keeping `updateNavAuth()` and `showView()` working unchanged.

---

## 8. Default Login Credentials (seed data)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@suride.id | Admin@SuRide1 |
| Customer | register a new account | — |

> **Change the admin password immediately** after first login in production.
