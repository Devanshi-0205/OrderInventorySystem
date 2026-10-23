---
name: vue-frontend
description: Use when creating, modifying, reviewing, debugging, or testing the Vue 3 frontend of the Order Inventory Management System, including components, pages, routing, Pinia state, Axios API integration, forms, validation, and responsive UI.
paths:
  - "frontend/**"
---

# Vue Frontend Skill — Order Inventory System

## Stack & Tooling
- **Vue 3.5** · Composition API · `<script setup>` · JavaScript (no TypeScript)
- **Vite 8** · Node ≥ 22.18.0 (`nvm use 22.23.4`) · port locked to **5173** (`strictPort: true`)
- **Vue Router 4** · **Pinia** · **Axios 1.20** · **PrimeVue 4** (Aura theme, `@primeuix/themes`)
- **Vitest** + `@vue/test-utils` · **ESLint** flat config + `eslint-plugin-vue`

## Source Layout
```
src/
├── api/          axios.js · auth.js · products.js · orders.js · dashboard.js
├── components/   AppLayout.vue (sidebar + topbar — wraps every auth page)
├── pages/        LoginPage · DashboardPage · Product{List,Form,Detail}Page · Order{List,Form,Detail}Page
├── stores/       auth.js · products.js · orders.js
├── router/       index.js  (8 routes, auth + role guards)
└── main.js       registers Pinia · Vue Router · PrimeVue · ToastService
```

## Hard Rules

### API calls
- All HTTP requests go through `src/api/` only — never call `axios` directly in a component.
- `baseURL` always read from `import.meta.env.VITE_API_BASE_URL`. Never hardcode a URL.
- `axios.js` attaches `Authorization: Bearer <token>` (from `localStorage`) and redirects to `/login` on `401`.

### State management
- Use Pinia stores (`useAuthStore`, `useProductStore`, `useOrderStore`) for server state.
- Every store action must set `loading = true` before the call and `loading = false` in `finally`.
- Local UI state (modal open, active tab) lives in the component, not the store.

### Forms & error handling
- On `422`: map `err.response.data.errors` to per-field messages.
- On `409`: show `err.response.data.message` via PrimeVue `Toast` (insufficient stock).
- On `401`/`403`: redirect or show permission error — never silently swallow.
- Disable submit buttons while `store.loading` is `true`.

### Authorization
- Route guards (`meta.requiresAuth`, `meta.role`) are **UX only** — the backend enforces all real auth.
- Never send prices, totals, or stock quantities from the frontend to the backend; those are backend-owned.
- `useAuthStore.isAdmin` / `isStaff` may show/hide controls but must never gate actual access.

### UI
- Use **PrimeVue 4** as the sole component library. Import components explicitly (tree-shakable).
- Do not install a second UI library without explicit approval.
- Every data-fetching view must handle: loading skeleton · empty state · error message · success feedback.
- Use `<style scoped>` for component CSS.
- Sidebar hides at ≤ 768px; all pages are responsive.

### Security
- Do not store tokens in `localStorage` long-term in production — document migration to `httpOnly` cookies.
- Never log tokens, passwords, or response payloads to `console.log`.
- Only `VITE_`-prefixed variables may be used in frontend code.

## Verification — run before claiming anything works
```bash
npm run build        # must exit 0
npm run lint:check   # must exit 0, no errors
npm run test:unit    # report pass/fail count
```
If `npm` is blocked: `Set-ExecutionPolicy RemoteSigned -Scope Process` then retry.

## Agent Rules
1. Read the target file before editing it.
2. Make the smallest change that satisfies the requirement.
3. Do not refactor code unrelated to the task.
4. If a required API endpoint does not yet exist, say so — do not write dead frontend code.
5. Report: files changed · commands run with output · limitations.
