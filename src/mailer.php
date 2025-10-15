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
 * Returnerer true ved suksess, false ved feil.
 */
function send_mailgun_email($to, $subject, $html_body, $from, $from_name, $api_key, $domain) {
    if (empty($api_key) || empty($domain)) {
        error_log('Mailgun API-nøkkel eller domene er ikke konfigurert.');
        return false;
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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        // Logg cURL-spesifikke feil (f.eks. nettverksproblemer)
        error_log('Mailgun cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        // Suksess (typisk 200 OK)
        return true;
    } else {
        // Logg feil fra Mailgun API
        error_log("Mailgun API Error - HTTP Status: {$http_code}");
        error_log("Mailgun API Response: {$response}");
        return false;
    }
}

// Denne blokken kjører kun når filen inkluderes av en POST-request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Først, sjekk om kritiske miljøvariabler er satt
    if (empty($mailgun_api_key) || empty($mailgun_domain)) {
        header('Content-Type: application/json', true, 500);
        echo json_encode([
            'success' => false, 
            'message' => 'Serverkonfigurasjonsfeil: Mailgun API-detaljer mangler.'
        ]);
        exit;
    }

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

    if (empty($errors)) {
        // --- E-post til kunden ---
        $customer_subject = 'Takk for at du spilte på Akari Lykkehjul!';
        $customer_body = "<html>...</html>"; // (Body forkortet for lesbarhet)
        $success1 = send_mailgun_email($email, $customer_subject, $customer_body, $from_email, $from_name, $mailgun_api_key, $mailgun_domain);

        // --- E-post til selger ---
        $seller_subject = "Nytt lead fra Lykkehjulet: {$company}";
        $seller_body = "<html>...</html>"; // (Body forkortet for lesbarhet)
        $success2 = send_mailgun_email($seller_email, $seller_subject, $seller_body, $from_email, $from_name, $mailgun_api_key, $mailgun_domain);

        if ($success1 && $success2) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'E-poster er sendt.']);
        } else {
            header('Content-Type: application/json', true, 500);
            echo json_encode(['success' => false, 'message' => 'En feil oppstod under sending av e-post. Sjekk serverloggen.']);
        }
    } else {
        // Send feilrespons
        header('Content-Type: application/json', true, 400);
        echo json_encode(['success' => false, 'errors' => $errors]);
    }
    exit;
}