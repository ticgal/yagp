# YAGP GLPI Plugin CHANGELOG

## 3.0.3 - 2026-10-15
### Bugfixes
- Observers now lift the waiting status when the pending reason has no automatic closure; the status change now happens after the followup is stored
- Fix configuration page failing to render with strict Twig variables (missing `params` for the form buttons)
- Automatic transfer: fix misspelled cartridge options and add the missing supplier and location options, which core treated as "delete permanently"
- Uninstall now drops the plugin tables and removes the old "See group tickets only" profile right
- Satisfaction cron task is registered at install instead of on every request
- Satisfaction modal stylesheet now works when GLPI is served under a sub-path
- Remove dead code (allocator SQL, unused tab hooks)
### Security
- Quick transfer: the confirmed transfer reads the ticket from the POST body only
- Transfer: the list sent to the core transfer is rebuilt from the checked integer ids
- Solved date cron: query built with the query builder instead of raw SQL
- Transfer: check update rights on every ticket, only allow tickets and stop forwarding request data as transfer options
- Escape ticket/entity/category names in the transfer modal, transfer result message, ticket form and search results (stored XSS)
- Satisfaction modal: require view rights on the ticket and update rights on the survey
- Quick transfer: require the transfer right and confirm the automatic transfer through a POST
- Remove unused satisfaction form script
- Category search selector respects entity restrictions
- Security warning next to the "Replace ticket requester (mailcollector)" option
- Replace ticket requester: only process real mail collector messages, so other ticket creation paths can no longer re-run the mail rules and change the ticket entity
- Autoclose rejected tickets: only a followup written by a requester of the ticket closes it
- Quick transfer: the automatic transfer is only done when the user can access the destination entity
- Block opening date: the option is now also enforced on the server

## 3.0.2 - 2026-09-08
### Bugfixes
- Fix syntax error in `showSatisfactionModal()` that prevented the plugin from being installed
- Fix "Transfer to" modal: entity dropdown was clipped/unusable when the iframe height was calculated too small on load

## 3.0.1 - 2025-03-20
### Bugfixes
- GLPI 11 deprecate see group tickets only

## 3.0.0 - 2025-01-16
### Features
- GLPI 11 support

## 2.6.0 - 2025-08-20
### Features
- New config option: Observers have an effect on pending tickets
- Self Service users doesn't have gototicket function

### Bugfixes
- Check items before transferring them

## 2.5.0 - 2025-06-09
### Features
- Display modal window after the ticket is closed to complete the satisfaction survey

## 2.4.1 - 2024-11-18
### Bugfixes
- Session control and better conditions

## 2.4.0 - 2024-11-11
### Features
- New Config option: Change default satisfaction
- New automatic action expired satisfaction removal

## 2.3.2 - 2024-06-10
### Bugfixes
- Fix strong typing definition #22663

## 2.3.1 - 2024-03-11
### Bugfixes
- Blockdate option fixed

## 2.3.0 - 2024-03-08
### Features
- Multiple solutions to autoclose tickets #19630
- Allow anonymous requester #19798
- See my group-assigned tickets only profile #20128

## 2.2.3 - 2024-01-15
### Bugfixes
- Fix reopened autoclosed tickets, second solution always closes the ticket

## 2.2.2 - 2024-01-03
### Bugfixes
- Fix requester replacement for mailcollector #19272

## 2.2.1 - 2023-10-18
### Bugfixes
- Fix transfer option ajax form
- Fix locales

## 2.2.0 - 2023-10-11
### Features
- Quick ticket transfer #17808
- Autoclose tickets with refused solutions #17833
- Reopen autoclosed tickets
- Deprecated automatic action for contracts #17878

## 2.1.1 - 2022-12-01
### Bugfixes
- Remove logs #12492

## 2.1.0 - 2022-09-30
### Features
- New Config option: Enable re-categorization tracking #11262
- New Config option: Hide historical tab to post-only users #11328
- New Config option: Enhance Private task/followup view #11432

### Bugfixes
- Fix "Go to ticket" field display at horizontal layout #11396
- Change "Go to ticket" input size #11396
- Migrate new config fields on update #11479

## 2.0.0
### Features
- GLPI 10 Compatibility #10285
### Bugfixes
- Change default minimum validation required option fixed #10285
- Go to ticket option fixed (Different HTML structure) #10285
- User mail returns array instead of string fixed #10285 
- Fixed menu option deprecated #10285
- Only execute script when date is not null #10298

## 1.4.0
### Features
- New Config option: Change default minimum validation required (TicketValidation) #10254

## 1.3.1 - Unreleased
### Bugfixes
- Rerun mail rules

## 1.3.0 - Unreleased
### Features
- New Config option: Replace ticket requester (mailcollector)
- Localazy integration

## 1.2.0
### Features
- New option to allow blocking the opening date field in the tickets

## 1.1.1
### Bugfixes
- fixed Impersonate incompatibility
- fixed Formcreator incompatibility

## 1.1.0
### Features
- Compatible with GLPI 9.5
- Fixed toolbar
- Go to ticket (by ticket Id)
