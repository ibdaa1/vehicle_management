// session_check.js
// Robust session check used by pages: calls session_check.php with credentials so PHPSESSID is sent.
// Replace your existing session_check.js with this file.

const SESSION_CHECK_URL = '/vehicle_management/api/users/session_check.php';

async function sessionCheck() {
  try {
    const res = await fetch(SESSION_CHECK_URL, {
      method: 'GET',
      credentials: 'same-origin', // CRITICAL: ensure browser sends PHP session cookie
      cache: 'no-store',
      headers: {
        'Accept': 'application/json'
      }
    });

    // If server redirected to login page (HTML), treat as unauthenticated
    const ct = res.headers.get('content-type') || '';
    if (!res.ok) {
      // try to parse JSON error body if present
      if (ct.includes('application/json')) {
        const body = await res.json().catch(()=>({}));
        return body;
      }
      return { success: false, message: 'Network or server error', status: res.status };
    }

    // Response ok
    if (ct.includes('application/json')) {
      const data = await res.json();
      return data;
    }

    // Unexpected content type
    const text = await res.text();
    return { success: false, message: 'Unexpected response', body: text };

  } catch (err) {
    console.error('sessionCheck error', err);
    return { success: false, message: 'Client error', debug: String(err) };
  }
}

// Optional helper that pages can call on load
async function requireSession(redirectUrl = '/vehicle_management/public/login.html') {
  const s = await sessionCheck();
  if (!s || s.success !== true) {
    // not authenticated -> redirect to login
    window.location.href = redirectUrl;
    return null;
  }
  return s.user;
}

// Expose to global for existing code to call
window.sessionCheck = sessionCheck;
window.requireSession = requireSession;