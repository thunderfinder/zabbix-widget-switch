## 2026-10-30 - CSRF Protection in Module Action Controllers

**Vulnerability:** The profile saving controller (`actions/WidgetProfilesSave.php`) explicitly disabled CSRF protection via `$this->disableCsrfValidation();` on a state-changing POST endpoint that writes directly to disk (`profiles.json`). This exposed authenticated users to Cross-Site Request Forgery (CSRF), allowing malicious external sites to modify or corrupt shared switch profile configurations.

**Learning:** Zabbix's `CController` base class enables CSRF token validation by default, verifying `_csrf_token` against the module action name via `CCsrfTokenHelper::check($csrf_token_form, $this->action)`. Developers often disable CSRF checks on AJAX actions due to missing token parameters in custom fetch/POST calls. In Zabbix 7.x, the correct approach is generating the action token in the edit view with `CCsrfTokenHelper::get($action)` and supplying `_csrf_token` in the AJAX payload.

**Prevention:** Never disable CSRF validation (`disableCsrfValidation()`) on endpoints that modify state or filesystem assets. Pass action-specific CSRF tokens from the view template to the frontend script and validate them natively through Zabbix's controller pipeline.
