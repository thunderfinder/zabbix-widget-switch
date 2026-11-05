## 2026-11-03 - Preserving Permissions During Atomic Configuration File Replacement

**Vulnerability:** When replacing shared module configuration files like `profiles.json` atomically via temporary files and `rename()`, the newly created file inherits the umask permissions of the temporary file (typically `0600` or `0644`), stripping the required group-write permission (`0664`) specified in repository guidelines. This can lock out shared web server processes (e.g., Apache/PHP-FPM) from subsequent configuration saves or corrupt shared operational state.

**Learning:** In Unix environments, `rename()` replaces the directory inode entry without adopting the target file's previous file mode or group ownership. If `file_put_contents()` creates the temporary file under a restrictive umask, the renamed target retains those restrictive permissions.

**Prevention:** When performing atomic write-and-rename operations on shared configuration files, explicitly apply the target file permission mode (`@chmod($tmp_file, 0664)`) to the temporary file before invoking `rename()`.

## 2026-10-30 - CSRF Protection in Module Action Controllers

**Vulnerability:** The profile saving controller (`actions/WidgetProfilesSave.php`) explicitly disabled CSRF protection via `$this->disableCsrfValidation();` on a state-changing POST endpoint that writes directly to disk (`profiles.json`). This exposed authenticated users to Cross-Site Request Forgery (CSRF), allowing malicious external sites to modify or corrupt shared switch profile configurations.

**Learning:** Zabbix's `CController` base class enables CSRF token validation by default, verifying `_csrf_token` against the module action name via `CCsrfTokenHelper::check($csrf_token_form, $this->action)`. Developers often disable CSRF checks on AJAX actions due to missing token parameters in custom fetch/POST calls. In Zabbix 7.x, the correct approach is generating the action token in the edit view with `CCsrfTokenHelper::get($action)` and supplying `_csrf_token` in the AJAX payload.

**Prevention:** Never disable CSRF validation (`disableCsrfValidation()`) on endpoints that modify state or filesystem assets. Pass action-specific CSRF tokens from the view template to the frontend script and validate them natively through Zabbix's controller pipeline.
