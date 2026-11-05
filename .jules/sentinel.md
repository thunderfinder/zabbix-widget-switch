## 2026-11-04 - Safe DOM Construction and Event Handling in Widget Views

**Vulnerability:** Injecting dynamic HTML strings via `innerHTML` and attaching inline event handler attributes (`onmouseenter`, `onclick`, `onfocus`) on port cards creates DOM-based XSS risks and violates modern Content Security Policy (CSP) guidelines enforced in Zabbix 7.x/8.x frontends.

**Learning:** Concatenating data strings into `innerHTML` for SVG sparks and telemetry state grids bypasses standard DOM node safety. In addition, emitting large inline handler strings on repetitive UI components (e.g., 96 ports) duplicates code and conflicts with strict CSP policies that disallow `unsafe-inline`. Client scripts should bind event handlers once via `addEventListener` and build UI elements using `document.createElement()` and `Element.prototype.replaceChildren()`.

**Prevention:** Never use `innerHTML` string interpolation or inline event handler attributes in widget views. Construct DOM structures using native methods (`document.createElement()`, `setAttribute()`, `classList`) and atomically swap container contents using `replaceChildren()`.

## 2026-11-03 - Preserving Permissions During Atomic Configuration File Replacement

**Vulnerability:** When replacing shared module configuration files like `profiles.json` atomically via temporary files and `rename()`, the newly created file inherits the umask permissions of the temporary file (typically `0600` or `0644`), stripping the required group-write permission (`0664`) specified in repository guidelines. This can lock out shared web server processes (e.g., Apache/PHP-FPM) from subsequent configuration saves or corrupt shared operational state.

**Learning:** In Unix environments, `rename()` replaces the directory inode entry without adopting the target file's previous file mode or group ownership. If `file_put_contents()` creates the temporary file under a restrictive umask, the renamed target retains those restrictive permissions.

**Prevention:** When performing atomic write-and-rename operations on shared configuration files, explicitly apply the target file permission mode (`@chmod($tmp_file, 0664)`) to the temporary file before invoking `rename()`.

## 2026-10-30 - CSRF Protection in Module Action Controllers

**Vulnerability:** The profile saving controller (`actions/WidgetProfilesSave.php`) explicitly disabled CSRF protection via `$this->disableCsrfValidation();` on a state-changing POST endpoint that writes directly to disk (`profiles.json`). This exposed authenticated users to Cross-Site Request Forgery (CSRF), allowing malicious external sites to modify or corrupt shared switch profile configurations.

**Learning:** Zabbix's `CController` base class enables CSRF token validation by default, verifying `_csrf_token` against the module action name via `CCsrfTokenHelper::check($csrf_token_form, $this->action)`. Developers often disable CSRF checks on AJAX actions due to missing token parameters in custom fetch/POST calls. In Zabbix 7.x, the correct approach is generating the action token in the edit view with `CCsrfTokenHelper::get($action)` and supplying `_csrf_token` in the AJAX payload.

**Prevention:** Never disable CSRF validation (`disableCsrfValidation()`) on endpoints that modify state or filesystem assets. Pass action-specific CSRF tokens from the view template to the frontend script and validate them natively through Zabbix's controller pipeline.
