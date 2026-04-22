<?php
/**
 * Mail class for sending emails via multiple protocols (SMTP & Mailgun) using PHPMailer.
 *
 * Usage Examples:
 *
 * // Basic usage
 * Mail::send('recipient@example.com', 'Test Subject', '<h1>Test Body</h1>');
 *
 * // With additional headers
 * $headers = [
 *     'CC' => 'cc@example.com',
 *     'BCC' => 'bcc@example.com'
 * ];
 * Mail::send('to@example.com', 'Subject', '<p>Body</p>', $headers);
 *
 * // The class automatically loads mail configuration from the database
 * // Configuration should be set via the Site Management page
 */
class Mail
{
    /**
     * Send an email using the configured protocol via PHPMailer.
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $headers Optional headers (e.g., ['CC' => 'cc@example.com'])
     * @return bool True on success, false on failure
     */
    public static function send($to, $subject, $body, $headers = [])
    {
        $config = self::getConfig();
        
        if (!$config) {
            error_log('Mail configuration not found');
            return false;
        }
        
        try {
            $mail = new PHPMailer(true);
            
            $protocol = $config['protocol'] ?? 'smtp';
            if ($protocol === 'mailgun') {
                $mail->isMailgun();
                $mail->Mailgun = array(
                    'api_key' => $config['mailgun_api_key'] ?? '',
                    'domain'  => $config['mailgun_domain'] ?? ''
                );
            } else {
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'] ?? 'localhost';
                $mail->Port       = $config['smtp_port'] ?? 25;
                $mail->SMTPAuth   = !empty($config['smtp_username']) || !empty($config['smtp_password']);
                $mail->Username   = $config['smtp_username'] ?? '';
                $mail->Password   = $config['smtp_password'] ?? '';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = false;
            }
            
            $mail->setFrom($config['from_email'] ?? 'no-reply@example.com', 
                          $config['from_name'] ?? 'BandBinder');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->msgHTML($body);
            
            foreach ($headers as $key => $value) {
                $mail->addCustomHeader($key, $value);
            }
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Mail sending failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get mail configuration from the database.
     * 
     * @return array|null Configuration array or null if not found
     */
    private static function getConfig()
    {
        $db = new \DatabaseManager();
        $result = $db->query("SELECT config_value FROM site_config WHERE config_key = 'mail_settings'");
        if ($result && count($result) > 0) {
            $row = $result[0];
            return json_decode($row['config_value'], true);
        }
        return null;
    }
}