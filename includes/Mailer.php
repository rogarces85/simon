<?php
require_once __DIR__ . '/../config/config.php';

class Mailer
{
    /**
     * Send an email using PHP's mail() function or SMTP if configured
     */
    public static function send($to, $subject, $htmlBody, $textBody = null)
    {
        // Set headers
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_EMAIL . '>',
            'Reply-To: ' . MAIL_FROM_EMAIL,
            'X-Mailer: PHP/' . phpversion()
        ];

        // If no text body provided, strip HTML
        if (!$textBody) {
            $textBody = strip_tags($htmlBody);
        }

        // Use PHP mail() function
        try {
            $result = mail($to, $subject, $htmlBody, implode("\r\n", $headers));

            if ($result) {
                self::log("Email sent to: $to - Subject: $subject");
                return true;
            } else {
                self::log("Failed to send email to: $to - Subject: $subject", 'error');
                return false;
            }
        } catch (Exception $e) {
            self::log("Email error: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Send training plan notification to athlete
     */
    public static function sendNewPlanNotification($athleteEmail, $athleteName, $coachName, $weekStart, $workouts)
    {
        $subject = "🏃 Nuevo Plan de Entrenamiento - " . SITE_NAME;

        // Format date
        $weekDate = (new DateTime($weekStart))->format('d/m/Y');

        // Build workout summary
        $workoutList = '';
        foreach ($workouts as $workout) {
            $date = (new DateTime($workout['date']))->format('l d/m');
            $workoutList .= "<tr>
                <td style='padding: 12px; border-bottom: 1px solid #e2e8f0;'>{$date}</td>
                <td style='padding: 12px; border-bottom: 1px solid #e2e8f0;'><strong>{$workout['type']}</strong></td>
                <td style='padding: 12px; border-bottom: 1px solid #e2e8f0;'>{$workout['description']}</td>
            </tr>";
        }

        $htmlBody = self::getEmailTemplate([
            'athlete_name' => $athleteName,
            'coach_name' => $coachName,
            'week_start' => $weekDate,
            'workout_list' => $workoutList,
            'workout_count' => count($workouts),
            'login_url' => BASE_URL . 'login.php'
        ]);

        return self::send($athleteEmail, $subject, $htmlBody);
    }

    /**
     * Get HTML email template
     */
    private static function getEmailTemplate($data)
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background-color: #f1f5f9;">
    <div style="max-width: 600px; margin: 0 auto; padding: 40px 20px;">
        
        <!-- Header -->
        <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); border-radius: 16px 16px 0 0; padding: 32px; text-align: center;">
            <h1 style="margin: 0; color: white; font-size: 28px; font-weight: bold;">🏃 ' . SITE_NAME . '</h1>
            <p style="margin: 8px 0 0; color: rgba(255,255,255,0.9); font-size: 16px;">Nuevo Plan de Entrenamiento</p>
        </div>
        
        <!-- Content -->
        <div style="background: white; padding: 32px; border-radius: 0 0 16px 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
            
            <p style="font-size: 16px; color: #334155; margin: 0 0 16px;">
                ¡Hola <strong>' . htmlspecialchars($data['athlete_name']) . '</strong>! 👋
            </p>
            
            <p style="font-size: 16px; color: #334155; margin: 0 0 24px;">
                Tu entrenador <strong>' . htmlspecialchars($data['coach_name']) . '</strong> ha cargado un nuevo plan de entrenamiento para la semana del <strong>' . $data['week_start'] . '</strong>.
            </p>
            
            <!-- Stats Box -->
            <div style="background: #f0f9ff; border-radius: 12px; padding: 20px; margin-bottom: 24px; text-align: center;">
                <p style="margin: 0; font-size: 32px; font-weight: bold; color: #0369a1;">' . $data['workout_count'] . '</p>
                <p style="margin: 4px 0 0; font-size: 14px; color: #0369a1;">Sesiones programadas</p>
            </div>
            
            <!-- Workout Table -->
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px;">
                <thead>
                    <tr style="background: #f8fafc;">
                        <th style="padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0;">Día</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0;">Tipo</th>
                        <th style="padding: 12px; text-align: left; font-size: 12px; text-transform: uppercase; color: #64748b; border-bottom: 2px solid #e2e8f0;">Descripción</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $data['workout_list'] . '
                </tbody>
            </table>
            
            <!-- CTA Button -->
            <div style="text-align: center; margin-top: 32px;">
                <a href="' . $data['login_url'] . '" style="display: inline-block; background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: bold; font-size: 16px;">
                    Ver Mi Plan Completo
                </a>
            </div>
            
            <p style="margin-top: 32px; font-size: 14px; color: #64748b; text-align: center;">
                ¡Buena suerte con tus entrenamientos! 💪
            </p>
        </div>
        
        <!-- Footer -->
        <div style="text-align: center; padding: 24px; color: #94a3b8; font-size: 12px;">
            <p style="margin: 0;">© ' . date('Y') . ' ' . SITE_NAME . '. Todos los derechos reservados.</p>
            <p style="margin: 8px 0 0;">Este correo fue enviado automáticamente.</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Log email activity
     */
    private static function log($message, $type = 'info')
    {
        $logFile = __DIR__ . '/../logs/email.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$type] $message\n";

        @file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
}
