## 2026-10-30 - CSRF Protection in Module Action Controllers

**Vulnerability:** The profile saving controller (`actions/WidgetProfilesSave.php`) explicitly disabled CSRF protection via `$this->disableCsrfValidation();` on a state-changing POST endpoint that writes directly to disk (`profiles.json`). This exposed authenticated users to Cross-Site Request Forgery (CSRF), allowing malicious external sites to modify or corrupt shared switch profile configurations.

**Learning:** Zabbix's `CController` base class enables CSRF token validation by default, verifying `_csrf_token` against the module action name via `CCsrfTokenHelper::check($csrf_token_form, $this->action)`. Developers often disable CSRF checks on AJAX actions due to missing token parameters in custom fetch/POST calls. In Zabbix 7.x, the correct approach is generating the action token in the edit view with `CCsrfTokenHelper::get($action)` and supplying `_csrf_token` in the AJAX payload.

**Prevention:** Never disable CSRF validation (`disableCsrfValidation()`) on endpoints that modify state or filesystem assets. Pass action-specific CSRF tokens from the view template to the frontend script and validate them natively through Zabbix's controller pipeline.

## 2026-11-03 - Preserving File Permissions in Atomic Configuration Replacement

**Vulnerability:** In `actions/WidgetProfilesSave.php`, atomic file replacement using temporary files (`.tmp.*`) and `rename()` overwrote `profiles.json` with default umask permissions (typically 0644 or 0640). This stripped configured group-write permissions (`0664`), violating the least-privilege deployment model and causing permission denial on subsequent administrative profile updates.

**Learning:** In POSIX filesystems, `rename()` replaces the target inode entirely with the temporary file inode, inheriting the temporary file's permissions rather than preserving the destination's permissions. In web environments where group permissions (e.g. `root:apache 0664`) are used for shared access, creating temporary files with default umask strips necessary write bits.

**Prevention:** Before replacing configuration files via `rename()`, inspect the existing file's permissions (`fileperms($file) & 0777`) and explicitly apply them to the temporary file via `chmod()`, with a secure fallback (e.g. `0664`), before executing the atomic rename.
