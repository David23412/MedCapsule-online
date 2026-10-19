# Database recovery

The droplet deployment command writes a MySQL SQL dump before each deployment to
`/root/medcapsule-deployment-backups`. These deployment backups are distinct from
the legacy JSON files accepted by `php artisan db:restore`.

## Rehearse the current deployment backup

Run the repository's `docker/verify-backup.sh` on the droplet as an operator. It
uses the existing `medcapsule-db` container and its credentials without printing
them. It imports the latest dump into a new `medcapsule_restore_check_*` database,
compares the number of restored tables with the dump, runs `CHECK TABLE`, and
removes the temporary database. It never imports into the application database.
Run only trusted deployment dumps. Allow enough disk space for a second database.

A rehearsal passed on 19 October 2026: 37 tables imported and checked. This checks
SQL import and table integrity; it does not verify uploaded files or constitute
an application-level disaster recovery test. Deployment dumps live on the same
server; configure separate off-server copies for protection against server loss.
The backup settings in `config/medcapsule.php` alone do not schedule those copies.

## Legacy JSON backups

Place a trusted JSON file directly in `storage/app/backups`, with table names as
keys and arrays of row objects as values. Validate without changing data:

```sh
php artisan db:restore filename.json --dry-run
```

Dry-run checks structure, columns, dependency order and transactional table
engines; actual row constraints are checked during restoration. Before restoring,
stop application writes and take a database-native backup. To restore:

```sh
php artisan db:restore filename.json
```

The command keeps foreign keys enabled, deletes dependent rows before parent
rows, inserts parents before dependents, and rolls the entire transaction back
if any operation fails. Empty row lists intentionally empty the named table.
Unknown columns/tables, inconsistent rows, unsafe paths and cyclic dependencies
are rejected. It does not disable constraints to force an incompatible partial
backup into the database. For a production SQL dump, use database-native tools
and rehearse in a separate database before an operator performs a live restore.
