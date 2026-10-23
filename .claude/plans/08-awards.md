# Awards

Scope: Awarding system with public voting. Design: `docs/superpowers/specs/2026-10-23-award-voting-design.md`.

Status: ✅ Done.

- [x] Admin-editable Awards teaser blurb (Landing Page Content CMS)
- [x] Public awards teaser (landing page) + `/events/{event}/awards` page shell
- [x] Voting mechanics decided: anyone can vote, one vote per email per award, emailed 6-digit code, Cloudflare Turnstile, live public counts, scheduled window + manual override
- [x] Nominee entry (admin)
- [x] `Award` / `AwardNominee` / `AwardVote` / `AwardVoteCode` models
- [x] Vote submission flow + live results display
- [x] Admin vote review, removal (email stays blocked) and CSV export

Deployment notes: set `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` in the live `.env`; keep `TRUST_CLOUDFLARE=false` while the domain is on GoDaddy DNS; after launch check the admin votes list shows real visitor IPs (if every vote shows the server's own IP, set `TRUSTED_PROXIES`).
