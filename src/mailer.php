<?php
// src/mailer.php

// VIKTIG: Bruk miljøvariabler for sikkerhet.
// Disse bør settes i din serverkonfigurasjon (f.eks. Laravel Forge Environment eller .htaccess).
$mailgun_api_key = getenv('MAILGUN_API_KEY');
$mailgun_domain = getenv('MAILGUN_DOMAIN');
$seller_email = getenv('SELLER_EMAIL') ?: 'salg@dittfirma.no'; // E-posten til intern selger
$from_email = 'lykkehjulh@akari.no'; // E-posten avsenderen ser.
$from_name = 'Akari Lykkehjul';

/**
 * Funksjon for å sende e-post via Mailgun API med cURL.
 */
function send_mailgun_email($to, $subject, $html_body, $from, $from_name, $api_key, $domain) {
    if (empty($api_key) || empty($domain)) {
        // Forhindrer feil hvis miljøvariabler ikke er satt.
        // I en produksjonssetting kan du logge denne feilen.
        error_log('Mailgun API-nøkkel eller domene er ikke konfigurert.');
        return null;
    }

    $api_url = "https://api.mailgun.net/v3/{$domain}/messages";
    $post_data = [
        'from'    => "{$from_name} <{$from}>",
        'to'      => $to,
        'subject' => $subject,
        'html'    => $html_body
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_USERPWD, "api:{$api_key}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

// Denne blokken kjører kun når filen inkluderes av en POST-request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Hent data fra POST-requesten (sendt som JSON)
    $input = json_decode(file_get_contents('php://input'), true);

    $company = filter_var($input['company'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($input['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $phone = filter_var($input['phone'] ?? '', FILTER_SANITIZE_STRING);
    $prize = filter_var($input['prize'] ?? 'Ingen premie vunnet', FILTER_SANITIZE_STRING);

    $errors = [];
    if (empty($company)) $errors[] = 'Bedriftsnavn mangler.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Ugyldig e-postadresse.';
    if (empty($phone)) $errors[] = 'Telefonnummer mangler.';
    if (empty($prize)) $errors[] = 'Premie mangler.';

    // Hvis ingen feil, send e-poster
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
        send_mailgun_email($email, $customer_subject, $customer_body, $from_email, $from_name, $mailgun_api_key, $mailgun_domain);

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
        send_mailgun_email($seller_email, $seller_subject, $seller_body, $from_email, $from_name, $mailgun_api_key, $mailgun_domain);

        // Send suksessrespons tilbake til JavaScript
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'E-poster er sendt.']);
    } else {
        // Send feilrespons
        header('Content-Type: application/json', true, 400);
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
    exit; // Stopp videre lasting av HTML-siden
}
