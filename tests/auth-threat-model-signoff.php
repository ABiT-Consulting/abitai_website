<?php
/**
 * SEC-08 auth threat-model signoff checks.
 *
 * Run from the repository root:
 * php tests/auth-threat-model-signoff.php
 */

$doc_path = dirname(__DIR__) . '/docs/auth-threat-model-security-review.md';
$doc = file_get_contents($doc_path);
if ($doc === false) {
    throw new RuntimeException('Could not read auth threat-model security review.');
}

function assert_contains_text(string $haystack, string $needle, string $message): void
{
    if (strpos($haystack, $needle) === false) {
        throw new RuntimeException($message);
    }
}

function assert_matches_text(string $haystack, string $pattern, string $message): void
{
    if (preg_match($pattern, $haystack) !== 1) {
        throw new RuntimeException($message);
    }
}

assert_contains_text($doc, 'Task: TASK-2026-02025 / SEC-08', 'Threat-model doc must identify the ERP task.');
assert_contains_text($doc, '## Assets and Trust Boundaries', 'Threat-model doc must include assets and trust boundaries.');
assert_contains_text($doc, '## Threat Model Summary', 'Threat-model doc must include a threat model summary.');
assert_contains_text($doc, '## Security Review Findings', 'Threat-model doc must include findings.');
assert_contains_text($doc, '## Remediation List', 'Threat-model doc must include a remediation list.');
assert_contains_text($doc, '## Launch Signoff', 'Threat-model doc must include launch signoff.');
assert_contains_text($doc, 'SEC-08 signoff: approved for launch', 'Threat-model doc must explicitly sign off launch readiness.');
assert_contains_text($doc, 'Open critical findings: 0.', 'Threat-model doc must state no open critical findings.');
assert_contains_text($doc, 'Open high findings: 0.', 'Threat-model doc must state no open high findings.');

assert_matches_text($doc, '/### Critical\s+No open critical findings\./s', 'Critical section must have no open findings.');
assert_matches_text($doc, '/### High\s+No open high findings\./s', 'High section must have no open findings.');
assert_matches_text($doc, '/Launch gate result: Pass\./', 'Launch gate must pass.');

echo "Auth threat-model signoff tests passed.\n";
