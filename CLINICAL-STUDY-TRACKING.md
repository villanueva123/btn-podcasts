# BTN Podcasts – NHS Clinical Trial 2026 tracking

**Release:** 1.0.3.12 (assets 2.2.34). **Cohort source:** WordPress user meta `memb_Groups`, exact tag ID `3716`.

## What changed in this release

The previous builds checked the live Memberium/Keap tag API for each WordPress user during report generation. On large sites this can cause long-running AJAX requests and HTTP 504 timeouts. The report **no longer calls `memb_hasAnyTags()` or `memb_getContactIdByUserId()`**.

Instead, both **tracking eligibility** and **report membership** are now determined from the local WordPress user meta key `memb_Groups`. This accommodates Memberium's comma-delimited tag IDs, for example:

```
183,295,327,1403,1457,1547,1765,2085,2373,2529,2754,3050,3098,3306,3308,3310,3695,3716
```

A user qualifies **only if an entire comma-separated entry is 3716**, allowing surrounding whitespace. Examples:

- `183,295,3716` – included
- `3716` – included
- `183, 3716 , 295` – included
- `13716,37160` – **not included** (substring matches are forbidden)
- missing or empty `memb_Groups` – **not included**

The report now filters **inside MySQL** using `wp_usermeta.meta_key = 'memb_Groups'` (using the site's actual table prefix) and an exact-ID `REGEXP` on `meta_value`. It joins `wp_users` only for matching participants and returns no nonparticipant users or nonmatching metadata to PHP. The query uses `umeta_id` cursor pagination (at most **25 matching rows** per request; server cap 50), with 504 retries reducing the batch to 5 and then 1. Selected-email mode uses the same SQL eligibility condition.

**Performance qualification:** Standard WordPress usermeta does not index individual IDs stored inside a comma-separated `meta_value`. MySQL still has to examine relevant `memb_Groups` rows to evaluate the regular expression, but it does this on the database server rather than loading each user's data into PHP or checking all WordPress users. For very large cohorts/databases, a dedicated indexed cohort table or an extra membership index may offer further speed. Changing the selected date range re-queries current local membership rather than relying on a cached list of emails.

**Important: `memb_Groups` is a locally stored value.** If a Keap tag has changed but Memberium has not yet updated this user metadata, the report and tracking will follow the stored value until it is synced. This report shows **current cached membership**, not historical cohort membership as of an earlier date. Records already logged remain stored if a user later loses tag 3716; those users will simply stop appearing in the current cohort report until they regain it.

## Report

Location: **WordPress Admin → Tools → Clinical Trial Pod Report**. The table starts loading automatically, without a Generate button.

| Column | Meaning |
| --- | --- |
| Email | WordPress user email |
| Last login | Latest `wp_login` detected since tracking installation (site timezone) |
| First qualified listen | First recorded qualified listen (all-time) |
| Consecutive days | Qualified-listening streak calculated as of selected date range end |
| Listens in date range | Qualified listens within the selected dates |
| Total listens (all time) | All recorded qualified listens per individual user |

Date filter: **Last 7 days**, **Last 30 days**, **All time**, or **Custom dates** with From/To fields. Changing dates automatically refreshes counts. All-time totals, first listen and latest login are not limited by the selected dates. You can optionally supply participant emails, but tag 3716 is still required. **Download CSV** exports all visible rows, marked `PARTIAL` if the scan is incomplete or paused.

Changing the date range reruns the filtered MySQL query so the report always reflects current locally cached `memb_Groups` membership, even if tags changed while the report was open.

## Tracking rules

- A qualifying listen is recorded after at least **50% of the pod's duration has actually played**, excluding seek jumps and pauses. The client sends a single AJAX request per qualifying play session.
- The same user can listen to a pod again in a new session and increment their own **total listens** by one. One session cannot count twice because `(user_id, session_key)` is a unique key.
- **Only users whose `memb_Groups` includes 3716 when the event arrives are inserted into the clinical event table.** Nonparticipants retain the existing player and legacy streak behavior.
- Clinical listen events are stored in `{$wpdb->prefix}btn_podcasts_clinical_listens` and do not contain names or email addresses. The report joins those events to current user accounts.
- Manual streak adjustments do not create a clinical listen event. Historical pod plays cannot be recovered retroactively from this release.
- This is browser-observed playback telemetry, not proof of the patient's attention or comprehension.

## Installation

1. Back up the **current** BTN Podcasts plugin directory and the WordPress database. **Deploy on staging first.**
2. Replace/update the existing plugin with `btn-podcasts` from this ZIP, without activating two copies simultaneously.
3. Visit an admin page once to apply any required clinical table setup. Existing data is preserved; this release does not rebuild or delete the listening table.
4. Confirm a test participant has WordPress user meta `memb_Groups` containing an exact `3716` entry.
5. Open **Tools → Clinical Trial Pod Report** and confirm the matching users load automatically, including those with zero listens. Verify date filters and CSV.
6. Test a tagged participant and an untagged participant playing pods. The clinical total should increase only for the participant after 50% real playback; the existing player and legacy streak behavior must still work for both.

## Staging checklist

- [ ] `memb_Groups = "183,295,...,3716"` appears in the report.
- [ ] `memb_Groups = "13716,37160"` does **not** appear.
- [ ] A 3716 participant with zero qualifying listens appears with a 0 total.
- [ ] Only a 3716 participant creates a new clinical listen record at 50% actual playback; no duplicate from retries.
- [ ] Pauses and seeks cannot advance qualified playback; replaying from the start in a new session can count again.
- [ ] Existing pod lists, repeat, shuffle, mobile playback, and legacy streak features continue working.
- [ ] Date presets and custom dates adjust in-range counts, not lifetime totals.
- [ ] Admin-selected email filtering still requires `memb_Groups` tag 3716.
- [ ] Simulated HTTP 504 reduces the batch size, retries from the same cursor, and offers Pause/Resume/partial CSV.
- [ ] Confirm SQL returns only `memb_Groups` rows matching exact `3716`, not every `memb_Groups` value.
- [ ] Compare a real known participant with their `memb_Groups` usermeta value. Confirm Memberium cache freshness.
- [ ] Check server and database logs for errors on the actual staging environment.

## Performance and caveats

The frontend no longer polls the server every second. A qualified listen creates at most one indexed clinical insert; a server/network retry reuses the same unique session key. The report is administrator-only, protected with a nonce and `manage_options`, and reads paginated local metadata rather than contacting Keap for each user.

This substantially reduces the **likely** source of the earlier HTTP 504; however, a 504 can still occur for unrelated hosting, proxy or database problems. PHP and JavaScript mock tests do not replace live-site staging verification.

The new table is retained on plugin deactivation/rollback to preserve study data. Coordinate retention, access and deletion procedures with the clinical study's privacy requirements.

## Code touchpoints

- `classes/clinical.php`: local exact-match tag parsing, frontend tracking eligibility, login recording, SQL-filtered exact-3716 metadata pagination, selected-email SQL join, batch report assembly.
- `assets/js/clinical-report.js`: automatic report, date filters, 25-row SQL-filtered pages, 504 retries, duplicate-row suppression, CSV.
- `btn-podcasts.php`: version and asset cache bump.

Unchanged from 1.0.3.10: `assets/js/qualified-listens.js`, audio playback integration, legacy streak code, and clinical table structure.
