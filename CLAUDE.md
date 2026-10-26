# Ski Manager (`ski-manager.net`) — VPS & Codebase Guide for Claude

## 1. SSH & VPS Access

- **Host / IP**: `72.62.170.146`
- **SSH User**: `root`
- **SSH Key**: Uses the local default SSH key (`~/.ssh/id_ed25519` or `~/.ssh/id_rsa` — passwordless key auth is already configured on this Mac).
- **Quick SSH Command**:
  ```bash
  ssh root@72.62.170.146
  ```
- **Run a one-off remote command**:
  ```bash
  ssh root@72.62.170.146 'command_here'
  ```

---

## 2. Environments & Where Files Live

### Local Workspace (Mac)
- **Local Git Repo**: `/Users/324080/.gemini/antigravity/scratch/ski-manager-repo`
- **GitHub Remote**: `https://github.com/MontaraGroup/Ski-Manager.git` (`main` branch)

### Production & Staging on the VPS (Managed by 1Panel)
The VPS runs **1Panel** with Dockerized OpenResty (Nginx), PHP-FPM 8.5, and MySQL 9.7.

| Environment | Domain | Host Path on VPS | Path Inside `PHP` Container | Database Name |
| :--- | :--- | :--- | :--- | :--- |
| **Production** | `https://ski-manager.net` | `/opt/1panel/www/sites/skiv2/ci4` | `/www/sites/skiv2/ci4` | `skiv2` |
| **Staging** | `https://staging.ski-manager.net` | `/opt/1panel/www/sites/skiv2-staging/ci4` | `/www/sites/skiv2-staging/ci4` | `skiv2_staging` |

> **Important Note on Docker Path Mapping**:
> On the VPS host, files live under `/opt/1panel/www/sites/...`, which is bind-mounted into the `PHP` and `1Panel-openresty-Xqgf` containers at `/www/sites/...` (without `/opt/1panel`).

---

## 3. Docker Containers on the VPS

 PHP and MySQL are **not** installed directly on the Ubuntu host OS — they run inside Docker containers:

- **`PHP`** (`1panel-php-fpm:8.5.5`): Runs PHP 8.5 + CodeIgniter 4 CLI (`spark`).
  - Lint a PHP file:
    ```bash
    ssh root@72.62.170.146 'docker exec PHP php -l /www/sites/skiv2/ci4/app/Controllers/Dashboard.php'
    ```
  - Clear CodeIgniter cache:
    ```bash
    ssh root@72.62.170.146 'docker exec PHP php /www/sites/skiv2/ci4/spark cache:clear'
    ```
  - Run migrations:
    ```bash
    ssh root@72.62.170.146 'docker exec PHP php /www/sites/skiv2/ci4/spark migrate'
    ```

- **`1Panel-mysql-Uibp`** (`mysql:9.7.0`): Runs the MySQL database.
  - Read credentials from `/opt/1panel/www/sites/skiv2/ci4/.env` (`database.default.*`).
  - Run a SQL query on Production (`skiv2`):
    ```bash
    ssh root@72.62.170.146 'docker exec 1Panel-mysql-Uibp mysql -uskiv2 -p"$(grep "database.default.password" /opt/1panel/www/sites/skiv2/ci4/.env | cut -d= -f2 | tr -d " ")" skiv2 -e "SHOW TABLES;"'
    ```

- **`1Panel-openresty-Xqgf`** (`1panel/openresty`): Web server / reverse proxy serving `public/` for each site.

---

## 4. Site Basics & Tech Stack

- **Framework**: CodeIgniter 4 (PHP 8.5)
- **Authentication**: CodeIgniter Shield (`users` table + `auth_identities` table for `email_password` hashes + `auth_groups_users` for roles like `admin` / `superadmin`) + custom `AdminSession` second-layer gate for `/admin`.
  - Primary admin user: `contact` (ID `2`, email `contact@ski-manager.net`).
- **Frontend Stack**:
  - Server-rendered PHP views in `app/Views/` extending `app/Views/layouts/main.php` (player layout) or `app/Views/layouts/admin.php` (admin panel layout).
  - **CSS**: Tailwind CSS v4 + DaisyUI v5 (`public/css/input.css` compiled via `npm run build` to `public/css/style.css`).
  - **Icons**: FontAwesome 6 (`fa-solid`, `fa-regular`, `fa-brands`).
  - **Interactive Trail Map (`/map`)**: Custom high-FPS HTML5 `<canvas>` pan/zoom map engine with progressive image loading (`_low.jpg` → `_med.jpg` → `.jpg`), SVG/Canvas lift & slope spline rendering, player Build Menu (`#buildDrawer`), and Admin Map Studio (`#adminEditorToolbar` + `#adminInspectorDrawer`) for drawing/editing presets on the mountain.
- **Game Loop & Cron**:
  - Game ticks run via `app/Controllers/Cron.php` / `spark` commands (advancing `game_day`, weather, snowfall, slope conditions, daily visitor traffic, ticket & building revenue, salaries, loans, and equipment maintenance).

---

## 5. Key File & Directory Structure

Inside `/opt/1panel/www/sites/skiv2/ci4` (and local `/Users/324080/.gemini/antigravity/scratch/ski-manager-repo`):

```text
├── app/
│   ├── Config/
│   │   ├── Routes.php              # All URL routes (player routes, /map APIs, /admin routes)
│   │   ├── Filters.php             # Auth, CSRF, AdminAuthFilter, MaintenanceFilter
│   │   └── AuthGroups.php          # Shield groups/permissions
│   ├── Controllers/
│   │   ├── Dashboard.php           # /dashboard executive overview, KPI & cashflow calculations, widget layout AJAX
│   │   ├── ResortMap.php           # /map interactive trail map, build facility/slope/lift endpoints, admin preset drawing APIs
│   │   ├── Resort.php              # /resort operations (open/close resort, ticket pricing, lifts & slopes management)
│   │   ├── Finances.php            # /finances ledger & transactions
│   │   ├── Staff.php               # /staff hiring, training, morale
│   │   ├── Grooming.php            # /grooming trail maintenance
│   │   ├── Snowmaking.php          # /snowmaking snow guns & coverage
│   │   ├── Buildings.php           # /buildings hotels, restaurants, lodges
│   │   ├── TerrainParks.php        # /terrain-parks freestyle parks
│   │   ├── Parking.php             # /parking lots & capacity
│   │   ├── Equipment.php           # /equipment snowcats & fleet
│   │   ├── Weather.php             # /weather forecast & snowpack
│   │   ├── Cron.php                # Daily game tick processor
│   │   └── Admin/                  # /admin controllers (AdminAuth, Users, Resorts, GameSettings, Logs, etc.)
│   ├── Filters/
│   │   └── AdminAuthFilter.php     # Protects /admin routes (checks Shield group + AdminSession)
│   ├── Helpers/
│   │   └── game_helper.php         # Global helpers: currency(), temp(), snow(), speed(), getGameDay(), getSeasonLength(), featureEnabled()
│   ├── Libraries/
│   │   └── AdminSession.php        # Secondary password/session verification for /admin
│   ├── Models/                     # CI4 models (FinanceModel,WeatherModel, DashboardWidgetModel, PresetLiftModel, PresetSlopeModel, etc.)
│   └── Views/
│       ├── layouts/
│       │   ├── main.php            # Main player navbar, sidebar, theme & global scripts
│       │   └── admin.php           # Admin panel layout
│       ├── dashboard/
│       │   └── index.php           # /dashboard view (KPI cards, modular container-query widgets, drag-and-drop customize mode)
│       ├── resort_map/
│       │   └── index.php           # /map view & canvas engine, Build Menu drawer, Admin Map Studio toolbar & inspector
│       └── admin/                  # Admin panel views
├── public/
│   ├── index.php                   # CodeIgniter front controller (DocumentRoot points to public/)
│   ├── css/
│   │   ├── input.css               # Source CSS (Tailwind v4 + custom theme rules)
│   │   └── style.css               # Compiled production CSS bundle
│   └── img/                        # Resort trail maps (e.g., ParkCity.jpg, ParkCity-Big.jpg, *_low.jpg, *_med.jpg) & assets
├── writable/
│   ├── cache/                      # CI4 cache files
│   ├── logs/                       # CI4 error logs (log-YYYY-MM-DD.log)
│   └── session/                    # User session files (keep owned by ubuntu:ubuntu or 0777)
├── ios/                            # Native SwiftUI iOS companion app
└── android/                        # Native Jetpack Compose Android companion app
```

---

## 6. Standard Deployment Workflow

1. **Edit files locally** in `/Users/324080/.gemini/antigravity/scratch/ski-manager-repo`.
2. **If editing CSS (`public/css/input.css`)**, rebuild the stylesheet before deploying:
   ```bash
   npm run build
   ```
3. **Deploy to Production & Staging via `scp` or `git`**:
   ```bash
   # Option A: Direct SCP of modified file(s)
   scp app/Views/dashboard/index.php root@72.62.170.146:/opt/1panel/www/sites/skiv2/ci4/app/Views/dashboard/index.php
   scp app/Views/dashboard/index.php root@72.62.170.146:/opt/1panel/www/sites/skiv2-staging/ci4/app/Views/dashboard/index.php

   # Option B: Commit & push to GitHub, then pull on VPS
   git add -A && git commit -m "Describe change" && git push origin main
   ssh root@72.62.170.146 'cd /opt/1panel/www/sites/skiv2/ci4 && git pull origin main && cd /opt/1panel/www/sites/skiv2-staging/ci4 && git pull origin main'
   ```
4. **Lint & Clear Cache on the VPS**:
   ```bash
   ssh root@72.62.170.146 'docker exec PHP php /www/sites/skiv2/ci4/spark cache:clear && docker exec PHP php /www/sites/skiv2-staging/ci4/spark cache:clear'
   ```
5. **Check Error Logs if Needed**:
   ```bash
   ssh root@72.62.170.146 'tail -n 80 /opt/1panel/www/sites/skiv2/ci4/writable/logs/log-$(date +%F).log'
   ```

<!-- entire-agent:begin -->
Read .entire/agent-guide.md for this repository's workflow, source inspection, and verification guidance.
@.entire/agent-guide.md
<!-- entire-agent:end -->
