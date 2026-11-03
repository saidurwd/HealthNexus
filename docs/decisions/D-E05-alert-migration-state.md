# D-E05 — Alert Service: migration state (H-04)

Done: `clinical_alerts`, `clinical_alert_recipients`, `clinical_alert_escalations`; `AlertService` (raise, acknowledge,
acknowledgeBySource, resolve, override, escalateDue); job `EscalateUnacknowledgedAlerts` (every minute); `AlertReport`
(latency per category, unacknowledged list); ladders in `config/alerts.php`, overridable by the hospital setting
`alerts.escalation_policies`.

Raised through the service today (module tables still written as before, acknowledgement mirrored back):
- Laboratory critical result → category `critical_lab` (read-back required when acknowledged through the service)
- ICU critical alert → `icu_deterioration`, policy `icu` (no ladder: ICU's own escalation command still runs)
- Nursing alert → `nursing_ews`, policy `nursing` (no ladder: Nursing's own escalation still runs)

Not yet: Radiology critical findings (`critical_radiology`), pharmacy safety alerts / overrides, inbox bell UI and
alert screens, SMS/call delivery channels (only the in-app `database` channel is wired). Acknowledging a lab alert
from its own screen records no read-back; the service-side acknowledgement does.
