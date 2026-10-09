<?php
/**
 * Hotel Luxury Mailer Service
 * ----------------------------
 * Pure PHP SMTP Client with SSL/TLS encryption for Gmail SMTP.
 * No external composer dependencies required - works directly in XAMPP!
 */

require_once __DIR__ . '/../config/database.php';

function send_hotel_smtp_mail($to_email, $to_name, $subject, $html_body) {
    global $mail_host, $mail_port, $mail_user, $mail_pass, $mail_from_name;

    $host = !empty($mail_host) ? $mail_host : 'smtp.gmail.com';
    $port = !empty($mail_port) ? $mail_port : 465; // SSL port for Gmail
    $username = $mail_user;
    $password = str_replace(' ', '', $mail_pass); // Strip spaces from app password
    $from_name = !empty($mail_from_name) ? $mail_from_name : 'HOTEL Luxury Stay';

    // Socket context with SSL verification settings
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        error_log("SMTP Connection failed: {$errno} - {$errstr}");
        return ['success' => false, 'error' => "Could not connect to SMTP server: {$errstr}"];
    }

    $response = fgets($socket, 515);

    $write = function($command) use ($socket) {
        fputs($socket, $command . "\r\n");
        $res = '';
        while ($line = fgets($socket, 515)) {
            $res .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        return $res;
    };

    // Handshake
    $write("EHLO " . gethostname());

    // Authentication
    $res = $write("AUTH LOGIN");
    $res = $write(base64_encode($username));
    $res = $write(base64_encode($password));

    if (strpos($res, '235') === false) {
        fclose($socket);
        error_log("SMTP Authentication failed: " . $res);
        return ['success' => false, 'error' => "SMTP Authentication failed. Check your App Password."];
    }

    // Sender & Recipient
    $write("MAIL FROM: <{$username}>");
    $write("RCPT TO: <{$to_email}>");
    $write("DATA");

    // Headers & Payload
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=UTF-8";
    $headers[] = "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <{$username}>";
    $headers[] = "To: =?UTF-8?B?" . base64_encode($to_name) . "?= <{$to_email}>";
    $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
    $headers[] = "Date: " . date("r");
    $headers[] = "X-Mailer: HOTEL Luxury PHP Mailer";

    $message = implode("\r\n", $headers) . "\r\n\r\n" . $html_body . "\r\n.";
    $res = $write($message);

    $write("QUIT");
    fclose($socket);

    if (strpos($res, '250') !== false) {
        return ['success' => true];
    }

    return ['success' => false, 'error' => $res];
}

/**
 * Generate Luxury Gold & Navy Styled HTML Email Template
 */
function get_luxury_email_template($title, $preheader, $content_html, $call_to_action_url = '', $call_to_action_text = '') {
    $btn_html = '';
    if (!empty($call_to_action_url) && !empty($call_to_action_text)) {
        $btn_html = '
        <div style="text-align: center; margin: 30px 0 20px;">
          <a href="' . htmlspecialchars($call_to_action_url) . '" style="background: linear-gradient(135deg, #C59B27 0%, #E5C365 50%, #B38918 100%); color: #0A1329; font-weight: 700; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; text-decoration: none; padding: 14px 28px; border-radius: 4px; display: inline-block;">
            ' . htmlspecialchars($call_to_action_text) . '
          </a>
        </div>';
    }

    return '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>' . htmlspecialchars($title) . '</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: \'Plus Jakarta Sans\', -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Arial, sans-serif;">
      <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; padding: 35px 15px;">
        <tr>
          <td align="center">
            <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #FFFFFF; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(10, 19, 41, 0.08); border: 1px solid #E2E8F0;">
              
              <!-- Header -->
              <tr>
                <td style="background: linear-gradient(135deg, #0A1329 0%, #15244C 100%); padding: 32px 30px; text-align: center; border-bottom: 2px solid #C59B27;">
                  <div style="font-family: \'Playfair Display\', Georgia, serif; font-size: 26px; font-weight: 700; letter-spacing: 3px; color: #FFFFFF; margin-bottom: 4px;">
                    HOTEL
                  </div>
                  <div style="font-size: 10px; letter-spacing: 3px; color: #E5C365; text-transform: uppercase;">
                    LUXURY STAY & ROOMS
                  </div>
                </td>
              </tr>

              <!-- Body -->
              <tr>
                <td style="padding: 36px 36px 28px; color: #1E293B; line-height: 1.65;">
                  <div style="font-size: 12px; font-weight: 700; color: #C59B27; letter-spacing: 1.5px; text-transform: uppercase; margin-bottom: 8px;">
                    ' . htmlspecialchars($preheader) . '
                  </div>
                  <h1 style="font-family: \'Playfair Display\', Georgia, serif; font-size: 24px; color: #0A1329; margin-top: 0; margin-bottom: 18px; font-weight: 700;">
                    ' . htmlspecialchars($title) . '
                  </h1>
                  
                  <div style="font-size: 15px; color: #334155; line-height: 1.7;">
                    ' . $content_html . '
                  </div>

                  ' . $btn_html . '
                </td>
              </tr>

              <!-- Footer -->
              <tr>
                <td style="background-color: #F8FAFC; padding: 22px 30px; text-align: center; border-top: 1px solid #E2E8F0; font-size: 12px; color: #64748B;">
                  <p style="margin: 0 0 6px;">Beach Boulevard, Marine Bay, Goa 403001 | 24/7 Front Desk: +91 90000 00000</p>
                  <p style="margin: 0;">&copy; ' . date('Y') . ' HOTEL Luxury Stay & Rooms. All rights reserved.</p>
                </td>
              </tr>

            </table>
          </td>
        </tr>
      </table>
    </body>
    </html>';
}
