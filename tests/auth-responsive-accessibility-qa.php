<?php
/**
 * QA-04 responsive and accessibility evidence checks.
 *
 * Usage:
 * php tests/auth-responsive-accessibility-qa.php
 */

$root          = dirname(__DIR__);
$report_path   = $root . '/docs/qa-04-responsive-accessibility-report.md';
$template_path = $root . '/wp-content/themes/astra/template-auth.php';
$css_path      = $root . '/wp-content/themes/astra/assets/css/abitai-frontend.css';
$js_path       = $root . '/wp-content/themes/astra/assets/js/abitai-auth.js';

function qa04_read_file($path, $label) {
    $contents = file_get_contents($path);

    if (false === $contents) {
        throw new RuntimeException("Could not read {$label}.");
    }

    return $contents;
}

function qa04_assert_contains($haystack, $needle, $message) {
    if (false === strpos($haystack, $needle)) {
        throw new RuntimeException($message);
    }
}

function qa04_assert_matches($haystack, $pattern, $message) {
    if (1 !== preg_match($pattern, $haystack)) {
        throw new RuntimeException($message);
    }
}

$report   = qa04_read_file($report_path, 'QA-04 report');
$template = qa04_read_file($template_path, 'auth template');
$css      = qa04_read_file($css_path, 'auth CSS');
$js       = qa04_read_file($js_path, 'auth JavaScript');

foreach (array('Desktop', 'Tablet', 'Mobile', 'Keyboard', 'Screen-Reader', 'Contrast', 'Focus-State') as $section) {
    qa04_assert_contains($report, "## {$section}", "QA report must include the {$section} section.");
}

qa04_assert_contains($report, 'No critical accessibility or mobile-blocking defects remain.', 'QA report must state the ERP acceptance result.');
qa04_assert_contains($report, '3.33:1 before fix', 'QA report must document the corrected primary button contrast defect.');
qa04_assert_contains($report, '4.70:1 after fix', 'QA report must document the corrected primary button contrast result.');

qa04_assert_contains($css, '--auth-blue: #0f75cf;', 'Primary auth blue must meet AA contrast with white text.');
qa04_assert_contains($css, '--auth-blue-strong: #0b5fa8;', 'Primary auth hover blue must remain darker than the base state.');
qa04_assert_contains($css, '@media (max-width: 921px)', 'Tablet responsive breakpoint must be implemented.');
qa04_assert_contains($css, '@media (max-width: 600px)', 'Mobile responsive breakpoint must be implemented.');
qa04_assert_contains($css, '.abit-auth-app-shell', 'Auth shell layout must be scoped.');
qa04_assert_contains($css, 'overflow-x: hidden;', 'Auth route must prevent horizontal overflow.');
qa04_assert_contains($css, 'grid-template-columns: minmax(0, 1fr);', 'Responsive grids must collapse to one column.');
qa04_assert_contains($css, '.abit-auth-button', 'Auth buttons must have stable touch target styling.');
qa04_assert_contains($css, 'min-height: 46px;', 'Inputs and buttons must meet minimum touch target height.');
qa04_assert_contains($css, '.abit-auth-button:focus', 'Auth buttons must have visible focus styling.');
qa04_assert_contains($css, '.abit-auth-alert:focus', 'Focusable alerts must have visible focus styling.');
qa04_assert_contains($css, '.abit-auth-route-footer a:focus-visible', 'Inline auth links must have visible focus styling.');
qa04_assert_contains($css, '.abit-auth-input[aria-invalid="true"]', 'Invalid controls must expose a visual error state.');
qa04_assert_contains($css, '.abit-auth-sr-only', 'Screen-reader-only live text utility must exist.');
qa04_assert_contains($css, '[data-auth-signup-step][hidden]', 'Hidden signup steps must not remain visually exposed.');

qa04_assert_contains($template, 'aria-labelledby="abit-auth-route-title"', 'Auth shell must expose a labelled main section.');
qa04_assert_contains($template, 'aria-label="', 'Auth template must include labelled landmark/region text.');
qa04_assert_contains($template, 'role="alert"', 'Errors must be announced as alerts.');
qa04_assert_contains($template, 'role="status"', 'Non-error status changes must be announced as status messages.');
qa04_assert_contains($template, 'tabindex="-1"', 'Autofocus status regions must be programmatically focusable.');
qa04_assert_contains($template, 'data-auth-autofocus', 'Status regions must support JavaScript autofocus.');
qa04_assert_contains($template, 'aria-live="polite"', 'Signup step changes must expose polite live updates.');
qa04_assert_contains($template, 'aria-current="step"', 'Signup stepper must expose current step semantics.');
qa04_assert_contains($template, '<fieldset class="abit-auth-module-selector"', 'Module checkbox group must use a fieldset.');
qa04_assert_contains($template, '<legend>', 'Module checkbox group must use a legend.');
qa04_assert_matches($template, '/aria-describedby="[^"]*error/', 'Inputs must connect inline errors with aria-describedby.');

qa04_assert_contains($js, "input.setAttribute( 'aria-invalid'", 'Client validation must set aria-invalid.');
qa04_assert_contains($js, '.focus();', 'Client validation must move focus to the first invalid field or status region.');
qa04_assert_contains($js, "submit.setAttribute( 'aria-busy', 'true' );", 'Submitting forms must expose busy state.');
qa04_assert_contains($js, "link.setAttribute( 'aria-disabled', 'true' );", 'Locked links must expose aria-disabled.');
qa04_assert_contains($js, "stepStatus.textContent", 'Signup step changes must update the live region.');

echo "QA-04 responsive and accessibility checks passed.\n";
