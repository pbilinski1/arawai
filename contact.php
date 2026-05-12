<?php
/**
 * ARAWAI Contact Form Handler
 * Works on GoDaddy shared hosting (cPanel / PHP mail relay).
 * On GitHub Pages this file is never called — Formspree handles it instead.
 *
 * To switch FROM Formspree TO this file on GoDaddy:
 *   In index.html change the form action to:  action="contact.php"
 */

header('Content-Type: application/json; charset=UTF-8');

// ── CORS: allow same-origin only ──────────────────────────────────────────
$allowed = $_SERVER['HTTP_HOST'] ?? '';
header("Access-Control-Allow-Origin: https://{$allowed}");
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// ── Rate-limit: 1 submission per IP per 60 s (session-based) ─────────────
session_start();
$now = time();
if (isset($_SESSION['last_submit']) && ($now - $_SESSION['last_submit']) < 60) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Please wait before submitting again']);
    exit;
}

// ── Sanitise inputs ───────────────────────────────────────────────────────
function clean(string $s): string {
    return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8');
}

$name         = clean($_POST['name']         ?? '');
$organisation = clean($_POST['organisation'] ?? '');
$email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject      = clean($_POST['subject']      ?? '');
$message      = clean($_POST['message']      ?? '');

// ── Honeypot spam check (add a hidden field named "website" to the form) ──
if (!empty($_POST['website'])) {
    // Silently succeed to fool bots
    echo json_encode(['ok' => true]);
    exit;
}

// ── Validate ──────────────────────────────────────────────────────────────
if (!$name || !$email || !$subject || !$message) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Required fields are missing']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email address']);
    exit;
}

$allowed_subjects = [
    'General Enquiry', 'Research Collaboration', 'Enterprise Access',
    'Technical Support', 'Press & Media', 'Other',
];
if (!in_array($subject, $allowed_subjects, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid subject']);
    exit;
}

// ── Build email ───────────────────────────────────────────────────────────
$to           = 'pawel.bilinski.1@city.ac.uk';
$subject_line = "=?UTF-8?B?" . base64_encode("ARAWAI Contact: {$subject}") . "?=";

$body  = "New message via the ARAWAI website contact form.\n";
$body .= str_repeat('-', 56) . "\n\n";
$body .= "Name:         {$name}\n";
if ($organisation) {
    $body .= "Organisation: {$organisation}\n";
}
$body .= "Email:        {$email}\n";
$body .= "Subject:      {$subject}\n\n";
$body .= "Message:\n{$message}\n\n";
$body .= str_repeat('-', 56) . "\n";
$body .= "Sent via www.arawai.uk\n";

// GoDaddy requires the From address to match your hosting domain
// Change noreply@YOUR-GODADDY-DOMAIN.com to match your GoDaddy domain
$from_domain  = $_SERVER['HTTP_HOST'] ?? 'arawai.uk';
$headers      = "From: ARAWAI Website <noreply@{$from_domain}>\r\n";
$headers     .= "Reply-To: {$name} <{$email}>\r\n";
$headers     .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers     .= "MIME-Version: 1.0\r\n";
$headers     .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers     .= "Content-Transfer-Encoding: 8bit\r\n";

// ── Send ──────────────────────────────────────────────────────────────────
$sent = mail($to, $subject_line, $body, $headers);

if ($sent) {
    $_SESSION['last_submit'] = $now;
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail delivery failed. Please email pawel.bilinski.1@city.ac.uk directly.']);
}
