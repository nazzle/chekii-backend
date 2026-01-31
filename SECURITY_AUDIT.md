# Security Audit Report — Production Readiness

**Date:** January 31, 2026  
**Scope:** Chekii Backend (Laravel 10 API)

---

## Executive Summary

The application uses Laravel Sanctum for API auth, permission checks on sensitive actions, and validation in most controllers. Several issues should be addressed before production, including one **critical** public auth endpoint, deploy endpoint protection when `DEPLOYMENT_KEY` is unset, dependency CVEs, and input validation gaps.

---

## Critical

### 1. Public `/api/reauth` endpoint (credential exposure)

- **Location:** `routes/api.php` (lines 221–249)
- **Issue:** `POST /api/reauth` is **not** behind `auth:sanctum`. It accepts `username` and `password` and returns a new access token. That makes it a second, public login endpoint.
- **Risk:** Brute-force and credential stuffing; same impact as a leaked login URL.
- **Recommendation:**
  - **Option A (preferred):** Remove this route. Use `/api/login` for login and refresh tokens via a dedicated refresh flow (e.g. existing token or short-lived refresh token).
  - **Option B:** If reauth must stay, require `auth:sanctum` and only re-issue a token for the already-authenticated user (no password in the body). Do not accept username/password on a public route.
- **Note:** The route uses `Auth::user()` and `Hash::check()` but does not import `Illuminate\Support\Facades\Auth` and `Illuminate\Support\Facades\Hash`. Add those imports if the route is kept.

### 2. Deploy endpoints when `DEPLOYMENT_KEY` is unset

- **Location:** `MigrationController.php`, `SeederController.php`
- **Issue:** Access is gated by `$request->header('deployment_key') !== config('app.deployment_key')`. If `DEPLOYMENT_KEY` is not set in `.env`, `config('app.deployment_key')` is `null`. Sending no header also yields `null`, so the check does **not** abort and the request is allowed.
- **Risk:** On environments where deploy routes are reachable (e.g. misconfigured `APP_ENV` or staging), anyone could run migrations/seeders.
- **Recommendation:** Require a non-empty deployment key and reject when missing or wrong, e.g.  
  `if (empty(config('app.deployment_key')) || $request->header('deployment_key') !== config('app.deployment_key')) { abort(401, 'Unauthorized'); }`  
  Also document `DEPLOYMENT_KEY` in `.env.example` and set a strong value in production.

---

## High

### 3. Dependency vulnerabilities (`composer audit`)

- **phpunit/phpunit** (dev): CVE-2026-24765 — unsafe deserialization in PHPT code coverage. Update to a patched version when available; dev-only reduces production impact.
- **psy/psysh** (dev): CVE-2026-25129 — local privilege escalation. Update when fixed.
- **symfony/http-foundation**: CVE-2025-64500 — PATH_INFO parsing can lead to limited authorization bypass. **Production impact.** Update Laravel / Symfony to a version that includes the fix.
- **symfony/process**: CVE-2026-24739 — argument escaping on Windows (MSYS2/Git Bash). Update when fixed.

**Recommendation:** Run `composer update` and follow Laravel/Symfony security advisories; ensure production uses patched versions of `symfony/http-foundation` and `symfony/process`.

### 4. Unvalidated request fields in `SaleController::createSale`

- **Location:** `app/Http/Controllers/Api/SaleController.php` (e.g. lines 58–66)
- **Issue:** Validator covers many fields, but `createSale` also uses `$request->subtotal`, `amount_received`, `change`, `notes`, `original_sale_id` (and possibly others) without validation. Data is passed into `Sale::create()` and related models.
- **Risk:** Incorrect or malicious data (e.g. XSS via `notes` if rendered without escaping), wrong amounts, or unexpected behaviour from unvalidated fields.
- **Recommendation:** Validate every input used in the sale creation (add rules for `subtotal`, `amount_received`, `change`, `notes`, `original_sale_id`, etc.) and use only validated (or safely derived) data when creating/updating records.

### 5. Unsanitized `order_by` / `order_direction` (potential SQL/query abuse)

- **Location:** `SaleController::getPaginatedSales` — `$request->input('order_by', 'sale_date')` and `$request->input('order_direction', 'desc')` passed to `$query->orderBy($orderBy, $orderDirection)`.
- **Issue:** Column and direction are user-controlled and not whitelisted.
- **Risk:** Query builder may escape in a way that avoids classic SQL injection but can still lead to errors or abuse (e.g. invalid column names, or in other stacks to injection). Best practice is to whitelist.
- **Recommendation:** Allow only a fixed set of columns and directions, e.g.  
  `$allowedOrderBy = ['sale_date', 'total_amount', 'id', ...];`  
  `$orderBy = in_array($request->input('order_by'), $allowedOrderBy) ? $request->input('order_by') : 'sale_date';`  
  and restrict `order_direction` to `'asc'` or `'desc'`.

---

## Medium

### 6. Pagination `per_page` unbounded (DoS / resource use)

- **Location:** Multiple controllers (e.g. `UserController`, `SaleController`, `InventoryController`, etc.) use `$request->input('per_page', 15)` without a maximum.
- **Issue:** Callers can send very large `per_page` (e.g. 999999) and increase memory/CPU and database load.
- **Recommendation:** Validate and cap, e.g. `'per_page' => 'sometimes|integer|min:1|max:100'` and use `min($request->input('per_page', 15), 100)` (or similar) when calling `paginate()`.

### 7. Error message leakage in API responses

- **Location:** e.g. `MigrationController`, `SeederController`, `SaleController` — on failure they return `'error' => $e->getMessage()` (or similar) in JSON.
- **Issue:** Exception messages can reveal paths, DB details, or environment info.
- **Recommendation:** In production (or for all API 500 responses), return a generic message and log the full exception; do not expose `$e->getMessage()` to the client.

### 8. CORS and production frontend

- **Location:** `config/cors.php` — `allowed_origins` includes `http://localhost:5010`.
- **Recommendation:** Add the production frontend origin(s) (e.g. `https://yourdomain.com`) and avoid `'*'` for credentials if you use cookies. Ensure CORS is not broader than needed.

### 9. `.env.example` and production defaults

- **Location:** `.env.example`
- **Issue:** No `DEPLOYMENT_KEY`; no reminder to set `APP_DEBUG=false` and `APP_ENV=production` for production.
- **Recommendation:** Add `DEPLOYMENT_KEY=` and a short comment that production must set `APP_DEBUG=false`, `APP_ENV=production`, and a strong `APP_KEY` and `DEPLOYMENT_KEY`.

---

## Low / Informational

- **Login throttling:** API throttle is 60/min per IP/user. Consider a stricter limit for `POST /api/login` (e.g. 5 attempts per minute per IP) to reduce brute-force risk.
- **Token expiry:** Sanctum token expiry is set to 59 minutes in code; `config/sanctum.php` has `'expiration' => null`. Align config and code so expiry is clear and consistent.
- **Password policy:** User creation/update uses `min:6`. Consider requiring stronger passwords (length, complexity) for production.

---

## Positive Findings

- Sensitive API routes are protected with `auth:sanctum`.
- Permission checks (e.g. `hasPermission('CREATE_SALE')`) are used on sensitive actions.
- Passwords are hashed with Laravel’s default (bcrypt); no plaintext storage.
- User model uses `$fillable` and `$hidden` appropriately; password and remember_token are hidden.
- Input validation is used in most controllers via `Validator::make($request->all(), ...)` and often only validated data is used for create/update.
- Migrations/seeders are blocked when `APP_ENV` is production (environment check before deploy key).
- `.env` is in `.gitignore`; no secrets committed in the repo.

---

## Checklist Before Production

- [ ] Remove or secure `/api/reauth` (no public username/password → token).
- [ ] Enforce non-empty `DEPLOYMENT_KEY` for deploy endpoints; set in production `.env` and document in `.env.example`.
- [ ] Update Laravel/Symfony (and other packages) to versions that fix CVE-2025-64500 and other relevant CVEs; run `composer audit` again.
- [ ] Validate and whitelist all inputs used in `SaleController::createSale`; whitelist `order_by` / `order_direction` in list endpoints.
- [ ] Cap `per_page` (e.g. max 100) wherever used.
- [x] Stop exposing `$e->getMessage()` in API error responses in production (all catch blocks now guard with `config('app.debug')`).
- [ ] Set production CORS origins; set `APP_DEBUG=false`, `APP_ENV=production`, strong `APP_KEY` and `DEPLOYMENT_KEY`.
- [ ] (Optional) Stricter login throttling and stronger password rules.

---

## Exception messages in production (`$e->getMessage()`)

**You can be assured exception messages do not leak in production if:**

1. **`APP_DEBUG=false`** in production `.env`. Laravel then hides stack traces and detailed errors for uncaught exceptions.
2. **All controller catch blocks** that return error content only include `$e->getMessage()` when `config('app.debug')` is true.

**Current state:** All known catch blocks that return error content have been updated to use `config('app.debug') ? $e->getMessage() : …` (or a generic message). Controllers covered: `SaleController`, `MigrationController`, `SeederController`, `MovementController`, `SaleBackupController`.

**Going forward:** Any new `catch` block that returns JSON with an `error` or `message` from an exception should use the same pattern so production never exposes internal details (paths, DB errors, env info).

---

*This report was generated from a static and config review. A full production deployment should also include HTTPS, secure headers, and environment-specific hardening.*
