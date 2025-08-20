<?php
// ANC Reminder Cron Job
// Run this script daily via cron job: 0 9 * * * /usr/bin/php /path/to/send_reminders.php

require_once '../../config/database.php';

// Set execution time limit to prevent timeout
set_time_limit(300); // 5 minutes

$db = getDBConnection();

try {
    // Get reminders that need to be sent today
    $stmt = $db->prepare("
        SELECT r.*, p.user_id, u.full_name, u.email, u.phone, p.current_week
        FROM reminders r
        JOIN pregnancies p ON r.pregnancy_id = p.id
        JOIN users u ON p.user_id = u.id
        WHERE DATE(r.reminder_date) = CURDATE()
        AND r.is_sent = 0
        AND p.is_active = 1
        AND u.is_active = 1
        ORDER BY r.reminder_time
    ");
    $stmt->execute();
    $reminders = $stmt->fetchAll();

    $sentCount = 0;
    $failedCount = 0;

    foreach ($reminders as $reminder) {
        $success = false;
        
        try {
            // Send email reminder
            if ($reminder['send_via'] === 'email' || $reminder['send_via'] === 'both') {
                $emailSent = sendEmailReminder($reminder);
                $success = $emailSent;
            }
            
            // Send SMS reminder (mock implementation - integrate with SMS service)
            if ($reminder['send_via'] === 'sms' || $reminder['send_via'] === 'both') {
                $smsSent = sendSMSReminder($reminder);
                $success = $success || $smsSent;
            }
            
            // Update reminder status
            if ($success) {
                $updateStmt = $db->prepare("UPDATE reminders SET is_sent = 1, sent_at = NOW() WHERE id = ?");
                $updateStmt->execute([$reminder['id']]);
                $sentCount++;
                
                // Log successful send
                logActivity("Reminder sent successfully", $reminder['id'], 'reminder_sent');
            } else {
                $failedCount++;
                logActivity("Failed to send reminder", $reminder['id'], 'reminder_failed');
            }
            
        } catch (Exception $e) {
            $failedCount++;
            logActivity("Error sending reminder: " . $e->getMessage(), $reminder['id'], 'reminder_error');
        }
    }
    
    // Generate upcoming reminders for next week
    generateUpcomingReminders($db);
    
    // Output results
    echo "Reminder Job Completed:\n";
    echo "Sent: $sentCount\n";
    echo "Failed: $failedCount\n";
    echo "Total processed: " . count($reminders) . "\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";

} catch (Exception $e) {
    echo "Error in reminder job: " . $e->getMessage() . "\n";
}

function sendEmailReminder($reminder) {
    // Simple email implementation - in production, use a proper email service like PHPMailer
    $to = $reminder['email'];
    $subject = $reminder['title'];
    
    $message = "
    <html>
    <head>
        <title>ANC Reminder - Maternal Healthcare</title>
    </head>
    <body>
        <h2>Maternal Healthcare Reminder</h2>
        <p>Dear {$reminder['full_name']},</p>
        
        <p>{$reminder['message']}</p>
        
        <p><strong>Reminder Details:</strong></p>
        <ul>
            <li>Type: {$reminder['reminder_type']}</li>
            <li>Date: " . date('M j, Y', strtotime($reminder['reminder_date'])) . "</li>
            <li>Current Week: {$reminder['current_week']}</li>
        </ul>
        
        <p>Please don't miss your appointment. Your health and your baby's health are our priority.</p>
        
        <p>For any questions, please contact your healthcare provider.</p>
        
        <br>
        <p>Best regards,<br>Maternal Healthcare Team</p>
        
        <hr>
        <small>This is an automated message. Please do not reply to this email.</small>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: noreply@maternalhealthcare.com' . "\r\n";
    
    // In production, replace with proper email service
    // return mail($to, $subject, $message, $headers);
    
    // Mock successful send for demo
    return true;
}

function sendSMSReminder($reminder) {
    // Mock SMS implementation - integrate with SMS service like Twilio, AWS SNS, etc.
    $phone = $reminder['phone'];
    $message = "Hello {$reminder['full_name']}, this is a reminder: {$reminder['message']} - Maternal Healthcare";
    
    // In production, integrate with actual SMS service
    /*
    Example with Twilio:
    
    require_once '/path/to/vendor/autoload.php';
    use Twilio\Rest\Client;
    
    $sid = 'your_twilio_sid';
    $token = 'your_twilio_token';
    $twilio = new Client($sid, $token);
    
    try {
        $message = $twilio->messages->create(
            $phone,
            [
                'from' => '+1234567890', // Your Twilio number
                'body' => $message
            ]
        );
        return true;
    } catch (Exception $e) {
        return false;
    }
    */
    
    // Mock successful send for demo
    return true;
}

function generateUpcomingReminders($db) {
    // Generate reminders for pregnancies without upcoming ANC reminders
    
    $stmt = $db->prepare("
        SELECT p.*, u.full_name, u.email, u.phone
        FROM pregnancies p
        JOIN users u ON p.user_id = u.id
        WHERE p.is_active = 1 
        AND u.is_active = 1
        AND p.id NOT IN (
            SELECT DISTINCT pregnancy_id 
            FROM reminders 
            WHERE reminder_date > CURDATE() 
            AND reminder_type = 'anc_visit'
        )
        AND p.current_week < 40
    ");
    $stmt->execute();
    $pregnancies = $stmt->fetchAll();
    
    foreach ($pregnancies as $pregnancy) {
        $nextVisitDate = calculateNextANCDate($pregnancy['current_week'], $pregnancy['lmp_date']);
        
        if ($nextVisitDate) {
            // Create reminder 2 days before the recommended visit
            $reminderDate = date('Y-m-d', strtotime($nextVisitDate . ' -2 days'));
            
            $title = "ANC Visit Reminder - Week " . $pregnancy['current_week'];
            $message = "Your next ANC visit is recommended for " . date('M j, Y', strtotime($nextVisitDate)) . ". Please schedule your appointment with your healthcare provider.";
            
            $insertStmt = $db->prepare("
                INSERT INTO reminders (pregnancy_id, reminder_type, title, message, reminder_date)
                VALUES (?, 'anc_visit', ?, ?, ?)
            ");
            $insertStmt->execute([
                $pregnancy['id'],
                $title,
                $message,
                $reminderDate
            ]);
        }
    }
}

function calculateNextANCDate($currentWeek, $lmpDate) {
    // ANC visit schedule based on WHO recommendations
    $ancSchedule = [
        8, 12, 16, 20, 24, 28, 30, 32, 34, 36, 37, 38, 39, 40
    ];
    
    foreach ($ancSchedule as $week) {
        if ($week > $currentWeek) {
            $lmp = new DateTime($lmpDate);
            $visitDate = clone $lmp;
            $visitDate->add(new DateInterval('P' . ($week * 7) . 'D'));
            return $visitDate->format('Y-m-d');
        }
    }
    
    return null; // No more visits needed
}

function logActivity($message, $reminderId, $type) {
    // Simple logging - in production, use proper logging library
    $logFile = __DIR__ . '/../../logs/reminder_log.txt';
    $logEntry = date('Y-m-d H:i:s') . " - $type - Reminder ID: $reminderId - $message" . PHP_EOL;
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>
