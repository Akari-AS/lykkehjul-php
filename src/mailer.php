<?php
// src/mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inkluder Composers autoloader for å laste inn PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';

// --- Konfigurasjon fra Miljøvariabler ---
// Disse MÅ settes i din serverkonfigurasjon (Forge FPM Pool eller .env)
$smtp_host = getenv('MAILGUN_SMTP_HOST') ?: 'smtp.mailgun.org';
$smtp_port = getenv('MAILGUN_SMTP_PORT') ?: 587;
$smtp_user = getenv('MAILGUN_SMTP_USERNAME'); // F.eks. postmaster@mg.akari.no
$smtp_pass = getenv('MAILGUN_SMTP_PASSWORD');
$seller_email = getenv('SELLER_EMAIL');
$from_email = 'lykkehjulh@akari.no';
$from_name = 'Akari Lykkehjul';

/**
 * Funksjon for å sende e-post via SMTP med PHPMailer.
 * Returnerer true ved suksess, false ved feil.
 */
function send_smtp_email($to, $subject, $html_body, $from, $from_name, $host, $port, $user, $pass) {
    $mail = new PHPMailer(true);

    try {
        // Server-innstillinger
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $port;
        $mail->CharSet    = 'UTF-8';

        // Avsender og mottaker
        $mail->setFrom($from, $from_name);
        $mail->addAddress($to);

        // Innhold
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html_body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Logg den detaljerte feilmeldingen fra PHPMailer
        error_log("PHPMailer send error: {$mail->ErrorInfo}");
        return false;
    }
}

// --- Hovedlogikk for POST-request ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sjekk om kritiske miljøvariabler for SMTP er satt
    if (empty($smtp_user) || empty($smtp_pass) || empty($seller_email)) {
        header('Content-Type: application/json', true, 500);
        echo json_encode([
            'success' => false, 
            'message' => 'Serverkonfigurasjonsfeil: SMTP-detaljer mangler.'
        ]);
        exit;
    }

    // Hent og valider input
    $input = json_decode(file_get_contents('php://input'), true);
    $company = filter_var($input['company'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = filter_var($input['phone'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $prize = filter_var($input['prize'] ?? 'Ingen premie vunnet', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    $errors = [];
    if (empty($company)) $errors[] = 'Bedriftsnavn mangler.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ugyldig e-postadresse.';
    if (empty($phone)) $errors[] = 'Telefonnummer mangler.';

    if (empty($errors)) {
        // --- E-post til kunden ---
        $customer_subject = 'Takk for at du spilte på Akari Lykkehjul!';
        $customer_body = "
            <html><body>
            <h2>Gratulerer, {$company}!</h2>
            <p>Du vant: <strong>{$prize}</strong></p>
            <p>Takk for din deltakelse. En av våre rådgivere vil ta kontakt med deg snart for en uforpliktende prat.</p>
            <p>Med vennlig hilsen,<br>Team Akari</p>
            </body></html>
        ";
        $success1 = send_smtp_email($email, $customer_subject, $customer_body, $from_email, $from_name, $smtp_host, $smtp_port, $smtp_user, $smtp_pass);

        // --- E-post til selger ---
        $seller_subject = "Nytt lead fra Lykkehjulet: {$company}";
        $seller_body = "
            <html><body>
            <h2>Nytt lead fra Lykkehjulet!</h2>
            <p><strong>Bedrift:</strong> {$company}</p>
            <p><strong>Kontaktperson (e-post):</strong> {$email}</p>
            <p><strong>Telefon:</strong> {$phone}</p>
            <p><strong>Vunnet premie:</strong> {$prize}</p>
            <p>Vennligst følg opp denne kontakten.</p>
            </body></html>
        ";
        $success2 = send_smtp_email($seller_email, $seller_subject, $seller_body, $from_email, $from_name, $smtp_host, $smtp_port, $smtp_user, $smtp_pass);

        if ($success1 && $success2) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'E-poster er sendt.']);
        } else {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['success' => false, 'message' => 'En feil oppstod under sending av e-post. Sjekk serverloggen.']);
        }
    } else {
        header('Content-Type: application/json', true, 400);
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
    exit;
}
