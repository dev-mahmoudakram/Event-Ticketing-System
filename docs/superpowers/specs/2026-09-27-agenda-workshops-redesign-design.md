# Agenda and workshops redesign — design

**Date:** 2026-09-27
**Status:** Approved in conversation, awaiting written-spec review

## Goal

Make the agenda and workshops describe real event sessions: several speakers per session,
session types and locations the admin manages, start–end times, rich descriptions, and
career-180-style cards (in the CCS identity) that open a details pop-up — for agenda sessions
and workshops alike, on one shared schedule.

## What the user asked for

- More than one person on a panel or talk.
- Session types beyond Keynote / Break / Panel, added by the admin.
- Start **and** end time shown ("10:00 – 11:15").
- Speaker photos shown (they are uploaded but the agenda never displays them).
- A location per session ("On Stage", "Room A", …).
- A description that isn't restrictive — room for detailed content — shown when a session is
  clicked.
- Workshop and agenda cards like the career-180 reference, in our identity, each opening a
  details pop-up, with the backend to support it.

## Decisions (from the conversation)

- **Session types and locations are managed lists per event**, each bilingual (Arabic + English).
- **One schedule:** workshops get their own day, start/end time, location and speakers, and the
  public agenda lists sessions and workshops together in time order. Agenda items no longer link
  to workshops.
- **Approach A:** sessions (`agenda_items`) and workshops stay separate records sharing the same
  speakers, types and locations. Workshop booking (keys, capacity, bookings) is untouched.
- Admin screens for all of this sit under the existing **Agenda and workshops** permission
  (`Permission::AgendaWorkshops`).
- No new dependencies. Palette: CCS colours only (coral, gold, maroon, red, black; no teal).

## Current state (for reference)

- `agenda_items`: `event_id`, `speaker_id` (one, nullable), `workshop_id` (nullable), `day_date`,
  `start_time`, `end_time`, `title_ar/en`, `type` (string, `App\Enums\AgendaItemType`: keynote,
  session, workshop, break, panel), `sort_order`. No description, no location.
- `workshops`: `event_id`, `speaker_id` (one, nullable), `slug`, `name_ar/en`,
  `description_ar/en` (text), `capacity`, `sort_order`. No time, no location.
- Public agenda (`resources/views/agenda/show.blade.php`) shows start time only and the speaker's
  name only.

## Data model

### `session_types` (new)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `event_id` | FK → events, cascade on delete | |
| `name_ar`, `name_en` | string(100) | |
| `sort_order` | unsigned int, default 0 | |
| timestamps | | |

Every existing event gets **Keynote, Session, Workshop, Break, Panel** (Arabic: كلمة رئيسية،
جلسة، ورشة عمل، استراحة، حلقة نقاشية) by the migration; new events get the same five when
created (in `EventController@store`, via one `SessionType::seedDefaultsFor(Event)` helper).
"Break" is special only in display: a session whose type is Break renders as a slim card — see
**Cards**. The type is recognised by a `is_break` boolean column (default false), set true on the
seeded "Break" row, and editable as a checkbox on the type form ("Show as a break").

### `locations` (new)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint | |
| `event_id` | FK → events, cascade on delete | |
| `name_ar`, `name_en` | string(100) | |
| `sort_order` | unsigned int, default 0 | |
| timestamps | | |

No defaults are seeded.

### `agenda_items` (changed)

- Add `session_type_id` (FK → session_types, `restrictOnDelete`, required after migration).
- Add `location_id` (FK → locations, `nullOnDelete`, nullable).
- Add `description_ar`, `description_en` (`longText`, nullable), cast with the existing
  `SanitizedRichText` cast.
- Drop `type`, `speaker_id`, `workshop_id` after moving the data.

### `workshops` (changed)

- Add `day_date` (date, nullable), `start_time`, `end_time` (time, nullable) — nullable because
  existing workshops may have no schedule yet; the admin form requires them for new/edited ones.
- Add `location_id` (FK → locations, `nullOnDelete`, nullable).
- `description_ar/en` become `longText` and use the `SanitizedRichText` cast.
- Drop `speaker_id` after moving the data.

### Speakers (new pivots)

- `agenda_item_speaker`: `agenda_item_id` (cascade), `speaker_id` (cascade), `sort_order`
  (unsigned int); primary key (`agenda_item_id`, `speaker_id`).
- `speaker_workshop`: `workshop_id` (cascade), `speaker_id` (cascade), `sort_order`; primary key
  (`workshop_id`, `speaker_id`).
- Relations: `AgendaItem::speakers()` and `Workshop::speakers()` — `belongsToMany`, ordered by
  pivot `sort_order`.

### Moving existing data (one migration, safe for `migrate --force`)

1. Create `session_types`, `locations`, both pivots; seed the five types for every event.
2. For each agenda item: `session_type_id` = the event's type whose English name matches the old
   `type` value (keynote → Keynote, session → Session, workshop → Workshop, break → Break,
   panel → Panel; anything else → Session).
3. Copy `agenda_items.speaker_id` and `workshops.speaker_id` into the pivots with `sort_order` 0.
4. For each agenda item with a `workshop_id`: copy its `day_date`, `start_time`, `end_time` onto
   that workshop (the first such item wins if several point at one workshop), then delete the
   agenda item — the workshop now appears on the schedule itself.
5. Drop the old columns.

`down()` restores the columns (type from the session type's English name lower-cased; the first
pivot speaker back into `speaker_id`), and drops the new tables. Workshop-linked agenda items are
not recreated on rollback (their times stay on the workshop).

`App\Enums\AgendaItemType` is deleted once nothing uses it; its label test in
`HubTranslationCoverageTest` goes with it.

## Admin

All under `Permission::AgendaWorkshops`; the permission's route patterns gain
`admin.events.session-types.*` and `admin.events.locations.*`.

### Session Types and Locations pages (new)

- Routes: `admin.events.session-types.*` and `admin.events.locations.*` (resource, except show,
  plus a `reorder` POST each), linked in the event sidebar next to Agenda and Workshops.
- Index: list with drag handles (the existing `sortable.js` pattern used by audience tabs), the
  number of sessions/workshops using each row, Edit and Delete.
- Form: Arabic name, English name (both required, max 100); session types also get "Show as a
  break".
- Delete is refused while anything uses the row: "In use by :count sessions or workshops — move
  them first." (types are `restrictOnDelete`; locations are checked in the controller).

### Session form (agenda item)

Fields: day (date, within the event's dates), start time, end time (after start), type (required,
one of this event's types), location (optional, one of this event's locations), title Arabic/English
(required), description Arabic/English (rich text, optional), speakers.

**Speaker picker** (new Blade component `x-admin.speaker-picker`, used by both forms):
- An "Add speaker" nice-select listing the event's speakers not yet chosen.
- The chosen list below it: photo/initials, name, a drag handle to reorder, and ✕ to remove.
- Submits `speaker_ids[]` in display order. Validation: each must be a speaker of the same event,
  no duplicates. Zero speakers is allowed (breaks, networking).

### Workshop form

Adds day, start time, end time (required when saving), location, and the speaker picker to the
existing fields; description becomes the rich-text editor.

### Agenda list page

Columns: day, time range, title, type, location, speakers (count). Workshops are **not** listed
here (they stay on the Workshops page), but a note links to it.

## Public pages

### Card (new Blade component `x-schedule-card`, used for sessions and workshops)

Dark card (`bg-white/[0.03]`, `border-white/10`, rounded-2xl), CCS fonts, RTL-aware:
- ⏱ time range, e.g. "11:15 – 12:00" (tabular numerals, `dir="ltr"` on the range).
- Type pill (gold text on `gold/10`).
- Title (display font, bold, up to 3 lines).
- 📍 location, when set.
- Speakers: round photo (`Speaker::photoUrl()`) or initials, and name — up to 4, then
  "+:count more".
- Workshops only: a coral **"Book your seat"** button → `workshops.book` for that workshop, and
  "Full" (disabled) when `isFull()`.
- The whole card is a button that opens the details pop-up; the booking button stops propagation.
- **Breaks** (type `is_break`): a slim single-line card (time · title), not clickable unless it
  has a description.
- Cards in a row share rows (CSS subgrid, as the ticket cards) so they match in height.

### Agenda page (`agenda.show`, rewritten)

- Day tabs when the event spans more than one day (Alpine; the first day with sessions opens).
- Type filter chips: "All" + each type that has at least one session or workshop that day.
- Grid: 3 columns ≥ lg, 2 ≥ md, 1 on phones. Sessions and workshops for the day, merged and
  sorted by start time (workshops without a time are left out of the agenda and listed only on
  the Workshops page).
- Merging happens in one read-only service, `App\Services\EventSchedule`, which returns, per day,
  a sorted list of entries (`kind` = session|workshop, the model, start, end) with speakers, type
  and location eager-loaded.

### Details pop-up

One Alpine modal per page, filled from the clicked card's data (rendered server-side into a
`<template>` per entry, so no extra request):
- Time range, type pill, title, the full rich description, location, and each speaker with photo,
  name and job title.
- Workshops: seats left ("12 seats left" / "Full") and "Book your seat".
- Deep links: every card has an id (`session-<id>` / `workshop-<id>`); opening the page with that
  hash opens its pop-up, and opening a pop-up updates the hash (closing clears it).
- Closes with ✕, Esc, or a click on the backdrop; focus moves into the pop-up and back to the
  card on close; body scroll locked while open; `prefers-reduced-motion` respected.

### Workshops

The landing page's workshop teaser and the workshops index page use `x-schedule-card` and the
same pop-up. The workshop show page stays as the booking entry point.

## Validation summary

- Session: `day_date` within the event's start/end dates; `end_time` after `start_time`;
  `session_type_id` exists for this event; `location_id` nullable, exists for this event;
  `speaker_ids.*` exist and belong to this event, distinct.
- Workshop: same schedule, location and speaker rules; `day_date`, `start_time`, `end_time`
  required on save.
- Types/locations: names required, max 100.

## Translations

Every new string in both `lang/en.json` and `lang/ar.json` (the coverage test enforces it).
Seeded type names are stored bilingual in the database, not in JSON.

## Testing

- Migration: old `type`/`speaker_id`/`workshop_id` data lands in the new structure (rollback +
  re-run of the migration, as the roles migration test does).
- Types/locations CRUD, reorder, delete refused while in use, other event's rows can't be used.
- Session and workshop save with several speakers in order; validation failures (end before
  start, day outside the event, another event's speaker/type/location, duplicate speakers).
- `EventSchedule`: merges and sorts sessions and workshops per day; skips unscheduled workshops.
- Public agenda: time ranges, type pills, location, speaker photos and initials, "+N more",
  workshop "Book your seat" / "Full", break cards, day tabs and filter chips present, deep-link
  ids, pop-up content (description, speaker titles, seats left).
- Landing teaser and workshops index use the new card.
- Permission map: the new routes resolve to `AgendaWorkshops` (the route-coverage test).
- Translation coverage.
- Browser check (Arabic and English, desktop and phone): cards, filter, day tabs, pop-up open/
  close/Esc/deep link, booking button.

## Out of scope

- Per-person roles on a session (moderator vs panelist).
- Tracks/parallel-room views (a room-by-time grid).
- Calendar export (.ics).
- Speaker detail pages linked from the pop-up (the speaker's photo, name and title are shown in
  place).
