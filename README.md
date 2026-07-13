# Talent Show — Ζωντανή Βαθμολόγηση

Σύστημα ζωντανής βαθμολόγησης για Talent Show events, με Laravel 13, Livewire, MySQL, Redis και Docker (Sail).

## Απαιτήσεις

- Docker & Docker Compose
- Git

## Packages

| Package | Σκοπός |
|---------|--------|
| `livewire/livewire` | Real-time UI (polling) |
| `simplesoftwareio/simple-qrcode` | QR codes για κριτές |

## Εκκίνηση

```bash
cp .env.example .env
# Ορίστε ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD

./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail npm install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan storage:link
./vendor/bin/sail artisan db:seed
./vendor/bin/sail npm run build
```

## Admin credentials

Ορίστε στο `.env`:

```env
ADMIN_NAME="Admin"
ADMIN_EMAIL="admin@talentshow.local"
ADMIN_PASSWORD="your-secure-password"
```

Μετά: `./vendor/bin/sail artisan db:seed --class=AdminUserSeeder`

## Διευθύνσεις

| Ρόλος | URL |
|-------|-----|
| Admin login | `http://localhost/admin/login` |
| Admin dashboard | `http://localhost/admin` |
| Judge access | `http://localhost/judge/access/{token}` |
| Judge voting | `http://localhost/judge/vote` |
| Presentation | `http://localhost/presentation/{slug}` |

## Ροή χρήσης

### 1. Δημιουργία Talent Show
Admin → `/admin/talent-shows/create`

### 2. Ομάδες & Κριτές
- `/admin/talent-shows/{id}/teams` — προσθήκη ομάδων (φωτογραφία, σειρά)
- `/admin/talent-shows/{id}/judges` — ακριβώς 5 ενεργοί κριτές

### 3. QR Code κριτή
Στη σελίδα κριτών → **Δημιουργία QR**
- Εμφανίζεται αμέσως (SVG)
- **Λήψη PNG** / **Εκτύπωση**
- Το plain token **δεν αποθηκεύεται** — αποθηκεύστε/εκτυπώστε αμέσως

### 4. Login κριτή
Ο κριτής σκανάρει το QR → αυτόματο login → `/judge/vote`

### 5. Έναρξη βαθμολόγησης
`/admin/talent-shows/{id}/live-control`:
1. **Έναρξη Talent Show**
2. **Έναρξη βαθμολόγησης** — ενεργοποιείται η 1η ομάδα

### 6. Ψηφοφορία
Κάθε κριτής βάζει βαθμό 1–10 (μία φορά, χωρίς αλλαγή).

### 7. Εμφάνιση σκορ & Επόμενος
Όταν ψηφίσουν και οι 5:
- **Εμφάνιση σκορ** στην παρουσίαση
- **Επόμενος διαγωνιζόμενος** (επιβεβαίωση)

### 8. Διόρθωση βαθμού
Live Control → **Διόρθωση** → νέος βαθμός + αιτιολογία (υποχρεωτική)

### 9. Τελικά αποτελέσματα
- **Κλείσιμο βαθμολόγησης**
- **Εμφάνιση κατάταξης**
- **Αποκάλυψη νικητή** (χειροκίνητα σε ισοβαθμία)
- **Ολοκλήρωση Talent Show**

## Παρουσίαση

- Ενεργή ομάδα: `/presentation/{slug}`
- Κατάταξη: `/presentation/{slug}/ranking`
- Νικητής: `/presentation/{slug}/winner`

## Tests

Απαιτείται ενεργό MySQL (Docker/Sail). Η βάση `testing` δημιουργείται αυτόματα από το Sail.

```bash
docker compose up -d
composer test
# ή
docker compose exec laravel.test php artisan test
```

## Ασφάλεια

- Admin: email/password + `role=admin`
- Κριτές: SHA-256 hashed QR tokens, session-based auth (όχι User guard)
- Rate limiting στο `/judge/access/{token}`
- Audit logs για κρίσιμες ενέργειες
- IP/User-Agent αποθηκεύονται ως SHA-256 hash

## Demo data

```bash
./vendor/bin/sail artisan db:seed
```

Δημιουργεί demo show με 8 ομάδες και 5 κριτές (`slug: demo-talent-show-2026`).
# Talent-Show
# Talent-Show
