# Operations runbook (pilot)

Targets are the guideline's suggestions (21.4); the hospital's policy decides the real numbers. Commands run from the application folder.

## Every day (automatic — check they ran)
| What | How | Where to look |
|---|---|---|
| Scheduler heartbeat | `ops:heartbeat` every minute | `php artisan ops:check` |
| Database backup 01:30 | `backup:run` (gzip, AES-256 when `BACKUP_PASSPHRASE` is set, SHA-256 recorded) | table `backup_runs`; files in `storage/app/private/backups` (move/sync them off the server) |
| Backups older than 14 days removed | part of `backup:run` | `config/ops.php → backup.keep_days` |
| Inpatient census every 6 h | `downtime:census` | Administration → Downtime kit |
| Export files removed after 24 h | `exports:prune` hourly | — |

Cron entry on the server (one line, every minute): `* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1`.
Queue workers must run (`php artisan queue:work` under Supervisor, or Horizon once Redis is in place).

## Monthly (S/M hospitals) or weekly (L): restore drill
1. `php artisan backup:restore-drill` — restores the newest backup into a throw-away database `<db>_restore_drill`, compares table count and the row counts of the key tables with what was recorded when the backup was taken, then drops the scratch database.
2. If it says **passed**, a person signs it off: `php artisan backup:restore-drill --sign="Full name"` (this runs a new drill and signs that one) and records it in the log below.
3. If it says **FAILED**, treat it as an incident: no backup can be trusted until a drill passes. The reason is printed and stored in `backup_runs.error`.

`php artisan ops:check` and `pilot:readiness` fail when the last passed drill is older than 35 days or has not been signed off.

### Drill log (sign here as well as in the system)
| Date | Backup file | Tables restored | Result | Signed by | Notes |
|---|---|---|---|---|---|
| | | | | | |

### Real recovery (not a drill)
1. Stop the application (maintenance mode: `php artisan down`). 2. Restore the chosen backup into an EMPTY database with the same commands the drill uses (`openssl enc -d … | gunzip | mysql`). 3. `php artisan migrate --force` (applies anything newer than the backup). 4. `php artisan up`. 5. Reconcile: anything entered on paper during the outage is back-entered with the original time and marked as entered late.

## Monitoring baseline (guideline 21.5)
`php artisan ops:check [--json]` exits 1 when anything has failed — point the monitoring tool (cron + e-mail/SMS, Uptime Kuma, Zabbix …) at it every 5 minutes.

| Check | Fails when | Threshold (config/ops.php) |
|---|---|---|
| Database | cannot connect | — |
| Failed jobs | any failed job | `failed_jobs_max` 0 |
| Queue depth | too many waiting jobs | 500 |
| Disk space | storage volume low | < 15 % free |
| Scheduler heartbeat | no beat for N minutes | 3 |
| Critical alerts waiting | a critical clinical alert unacknowledged | 15 min |
| Last backup | none, or older than | 26 h |
| Last restore drill | none, not signed off, or older than | 35 days |

Not covered yet (add with the hospital's tooling): slow-query log, Redis memory, SSL certificate expiry, SMS/payment gateway failures, security-event spikes, and sending the alerts to an on-call phone.

## Downtime (guideline 21.6)
1. Print every form once, now: Administration → Downtime kit (English + Bangla). Keep a pack at each nurses' station, reception, pharmacy, lab and cashier.
2. Keep the latest inpatient census printed at each ward (the system stores a new one every 6 hours).
3. During an outage use the paper forms; sequence receipts from the pre-printed book.
4. After recovery, back-enter with the original time and mark entries as entered late; reconcile receipt numbers with the cashier.
5. Drill: once before go-live, switch off a test server, run one admission, one prescription, one receipt on paper, and back-enter them.
