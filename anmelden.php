<?php
/**
 * Clean Klar Fit: Warteliste-Formular
 * Nimmt die Anmeldung entgegen und schickt sie per E-Mail an den Empfaenger.
 * Es werden keine Daten auf dem Server gespeichert.
 */
declare(strict_types=1);

// ---- Einstellungen -------------------------------------------------------
const EMPFAENGER = 'hello@markmade.de';        // Hierhin gehen die Anmeldungen
const ABSENDER   = 'noreply@cleanklarfit.de';  // Absenderadresse (Postfach/Domain bei ALL-INKL anlegen)
// --------------------------------------------------------------------------

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function antwort(int $status, array $daten): void {
    http_response_code($status);
    echo json_encode($daten, JSON_UNESCAPED_UNICODE);
    exit;
}

function sauber(string $wert, int $max): string {
    $wert = trim($wert);
    $wert = preg_replace('/[\r\n\t]+/', ' ', $wert) ?? '';
    return mb_substr($wert, 0, $max);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    antwort(405, ['ok' => false, 'error' => 'method']);
}

// Spam-Schutz: verstecktes Feld und zu schnelles Absenden. Bots bekommen "ok", ohne dass etwas passiert.
if (!empty($_POST['website'])) {
    antwort(200, ['ok' => true]);
}
$t = (int)($_POST['t'] ?? 0);
if ($t > 0 && ((int)(microtime(true) * 1000) - $t) < 2500) {
    antwort(200, ['ok' => true]);
}

$name = sauber((string)($_POST['name'] ?? ''), 80);
$mail = sauber((string)($_POST['email'] ?? ''), 120);
$tel  = sauber((string)($_POST['tel'] ?? ''), 40);
$ziel = sauber((string)($_POST['goal'] ?? ''), 40);
$ok   = ($_POST['consent'] ?? '') === 'ja';

$erlaubteZiele = ['', 'Abnehmen', 'Aufbauen', 'Gesünder leben'];
if (!in_array($ziel, $erlaubteZiele, true)) {
    $ziel = '';
}

if ($name === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)
    || !preg_match('/^[0-9+()\/\s\-]{6,}$/', $tel) || !$ok) {
    antwort(422, ['ok' => false, 'error' => 'validation']);
}

$betreff = 'Neue Anmeldung Warteliste: ' . $name;
$text  = "Neue Anmeldung für die Warteliste von Clean Klar Fit\n\n";
$text .= "Vorname:  $name\n";
$text .= "E-Mail:   $mail\n";
$text .= "Telefon:  $tel\n";
$text .= "Ziel:     " . ($ziel !== '' ? $ziel : 'nicht angegeben') . "\n";
$text .= "Zeit:     " . date('d.m.Y H:i') . " Uhr\n\n";
$text .= "Einwilligung zu E-Mail- und Telefonkontakt wurde per Häkchen erteilt.\n";
$text .= "Nächster Schritt: persönlich anrufen (Ziele und Erwartungen besprechen).\n";

$kopf  = "From: Clean Klar Fit <" . ABSENDER . ">\r\n";
$kopf .= "Reply-To: $mail\r\n";
$kopf .= "MIME-Version: 1.0\r\n";
$kopf .= "Content-Type: text/plain; charset=UTF-8\r\n";
$kopf .= "Content-Transfer-Encoding: 8bit\r\n";

$gesendet = mail(
    EMPFAENGER,
    mb_encode_mimeheader($betreff, 'UTF-8', 'B', "\r\n"),
    $text,
    $kopf,
    '-f' . ABSENDER
);

if (!$gesendet) {
    antwort(500, ['ok' => false, 'error' => 'mail']);
}
antwort(200, ['ok' => true]);
