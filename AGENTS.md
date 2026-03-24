### Best Practices & Coding Standards

This project follows the **WooCommerce-Core** coding standards with some modifications.

#### 1. Coding Style
- **Indentation**: Use **Tabs**, not spaces.
- **PHP Version**: Target PHP 8.1.
- **Naming Conventions**:
    - Classes: `PascalCase`.
    - Methods and Variables: `camelCase` (Note: standard WordPress uses `snake_case`, but this project uses `camelCase` for methods/variables in `src/`).
    - Namespaces: `Packetery\...`.
- **Strict Types**: Use `declare(strict_types=1);` in every new PHP file.
- **Yoda Conditions**: **Do NOT use** Yoda conditions (e.g., use `if ($a === 1)` instead of `if (1 === $a)`).
- **Arrays**: Short array syntax `[]` is preferred.
- **SRP**: Follow Single Responsibility Principle.
- Code comments: **English** only.

#### 2. Security
- **SQL Injection**: Always use `$wpdb->prepare()` for SQL queries.
- **XSS Prevention**:
    - Escape all outputs using `esc_html()`, `esc_attr()`, etc., or use **Latte** templates (they escape automatically).
- **Data Sanitization**: Always sanitize user inputs using `sanitize_text_field()`, `absint()`, etc.
- **Nonces**: Use WordPress nonces for form submissions and AJAX requests.
- **Permissions**: Always check user capabilities using `current_user_can()` before sensitive actions.
- **Never:** Commit secrets or API keys

#### 3. Defensive Programming & Robustness
- **Type Hinting**: Always use type hints for method parameters and return values (e.g., `public function getById(int $id): ?Order`).
- **Early Returns**: Use early returns to reduce code nesting.
- **Null Safety**: Always check for `null` before using an object.
- **Exception Handling**: Use `try-catch` blocks for operations that can fail (like API calls). Throw exceptions instead of returning `false` for errors.
- **Logging**: Use `WcLogger` for logging errors.

#### 4. Readability & Naming
- **Descriptive Names**: Use descriptive names for methods and variables (e.g., `isShippingRateRestrictedByWeight` instead of `checkWeight`).
- **Self-Documenting Code**: Write code that is easy to read. Avoid unnecessary comments if the method name explains what it does.
- **Small Methods**: Keep methods small and focused on one thing.

#### 5. Validation & Quality Assurance
Run these commands after every change:
- **Check everything**: `composer run check:all`
- **PHPStan**: `composer run phpstan:all`
- **Coding Style**: `composer run check:phpcs`
- **Tests**: `composer run tests:unit`

#### 6. Fixing Coding Style
If `check:phpcs` fails:
1. **Primary fix**: Always run `composer run fix:phpcbf` first. It is automated and cheaper than manual AI fixes.
2. **Manual fix**: Only if `phpcbf` cannot fix it, fix it manually.

#### 7. WordPress & WooCommerce Best Practices
- **Use Adapters**: Prefer using `WcAdapter` and `WpAdapter` classes instead of calling WordPress/WooCommerce functions directly.
- **HPOS Support**: This plugin supports High-Performance Order Storage (HPOS). Always use `$order->get_id()` instead of `$order->ID` and avoid direct database queries to `wp_posts` for order data.
- **Action/Filter Hooks**: Register hooks in `src/Packetery/Module/Hooks/HookRegistrar.php`. Use descriptive callback names.
- **Translations**: Always use `__()` or `_e()` with the `packeta` text domain. For consistency, use `$this->wpAdapter->__(...)` in classes.
- **Templates**: Use **Latte** templates located in `template/` for any HTML output. Do not echo HTML directly in PHP classes.

#### 8. Forbidden Techniques
- **No Direct Superglobals**: Avoid using `$_POST`, `$_GET`, `$_REQUEST` directly. Use `Packetery\Nette\Http\Request` (via `httpRequest` property) or WordPress sanitization functions.
- **No direct HTML in logic**: Keep business logic and presentation (Latte) separate.

#### 9. Project Structure
- `src/Packetery`: Main plugin logic (PSR-4).
- `tests`: Unit and integration tests.
- `languages`: Translation files.
- `temp`: Cache and logs.
