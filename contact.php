<?php
header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Sanitise inputs
function clean(string $s): string {
    return htmlspecialchars(strip_tags(trim($s)), ENT_QUOTES, 'UTF-8');
}

$name         = clean($_POST['name']         ?? '');
$organisation = clean($_POST['organisation'] ?? '');
$email        = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$subject      = clean($_POST['subject']      ?? '');
$message      = clean($_POST['message']      ?? '');

// Validate required fields
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

// Build email
$to      = 'pawel.bilinski.1@city.ac.uk';
$subject_line = "ARAWAI Contact: {$subject}";

$body  = "New message from the ARAWAI website contact form.\n";
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

$headers  = "From: ARAWAI Website <noreply@arawai.uk>\r\n";
$headers .= "Reply-To: {$name} <{$email}>\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($to, $subject_line, $body, $headers);

if ($sent) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail delivery failed']);
}
