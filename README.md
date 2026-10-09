# MFS Print

**Version 1.0.1** · FreeScout module by [Managed FreeScout](https://managedfreescout.com) (StackPros)

Print a FreeScout conversation without the parts a customer, colleague or auditor should not see. The
conversation's **Print** menu item opens a small window where the agent ticks what to leave out:

- internal notes
- history (status changes, assignments and other activity lines)
- translations, when FreeScout's Ticket Translator module is active

FreeScout's own print view then opens with those threads left out (filtered on the server, not hidden
with CSS). The printout does not mention what was left out, so it can go to a customer as is.

## Requirements

- FreeScout 1.8.236 or newer
- An active MFS Print licence (yearly subscription, one FreeScout installation, all agents)
- Outgoing HTTPS from the FreeScout server to our licence server

## Installation

1. Download `mfsprint.zip` from the [latest release](https://github.com/ManagedFreeScout/mfsprint/releases/latest).
2. Unzip it into FreeScout's `Modules/` folder, so you get `Modules/MFSPrint/`.
3. In FreeScout: **Manage → Modules**, activate **MFS Print**.
4. **Manage → Settings → MFS Print**: enter your licence key and click **Activate licence**.
5. Optional, same page: choose which boxes are ticked by default in the print window.

Updates arrive through **Manage → Modules → Update now** (FreeScout reads `version.txt` on `main` and
the `mfsprint.zip` asset of the latest GitHub release).

## Licence

- The module asks the Managed FreeScout hub (`hub_url`, default `https://app.managedfreescout.com`)
  with only the licence key and this installation's domain:
  `POST {hub_url}/modules/mfsprint/license/{activate|validate|deactivate}`.
  The module holds no StackPros credentials; the hub calls invAIse with the MFS Print product-line credentials.
- The result is stored in the shared `modules_licenses` table (row `module_alias = mfsprint`); the module
  creates that table itself when it is missing.
- Re-checked every 6 hours by FreeScout's scheduler. No licence, an expired subscription or no successful
  check for 14 days = MFS Print is off, and **Print** works as in standard FreeScout.
- `.env` overrides (testing only): `MFSPRINT_HUB_URL`, `MFSPRINT_BUY_URL`.

## How it works

| Hook | What MFS Print does |
| --- | --- |
| `conversation.get_action_buttons` | Points the Print menu item at the options modal (`GET /mfsprint/modal/{id}`) |
| `conversation.view.threads` | On `?print=1`: drops notes / line items, empties `translations` in memory |
| `settings.*`, `modules.*`, `schedule` | Settings page, licence on Manage → Modules, 6-hourly re-check |

The modal route needs a logged-in user who may view the conversation (FreeScout's own `view` policy).
The print view itself is FreeScout's conversation page, with FreeScout's own access checks and print dialog.

## Changelog

| Version | Date | Changes |
| --- | --- | --- |
| 1.0.1 | 2026-10-09 | The print options window closes after Print (it stayed open behind the new tab, also in Teams). |
| 1.0.0 | 2026-10-09 | First MFS Print release, rebuilt from the former Advanced Print module (alias `advanced-print`): licence via the Managed FreeScout hub (no credentials in the module, no dependency on other modules); modal behind login + conversation access check; saved defaults now pre-tick the modal; no second print dialog. |
