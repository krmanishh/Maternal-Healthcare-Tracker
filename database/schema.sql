-- Maternal Healthcare Tracker Database Schema
-- Created: 2025-08-20

DROP DATABASE IF EXISTS maternal_healthcare;
CREATE DATABASE maternal_healthcare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE maternal_healthcare;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_role ENUM('pregnant_woman', 'doctor_asha', 'admin') NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_user_role (user_role),
    INDEX idx_email (email)
);

-- Pregnancies table for pregnant women details
CREATE TABLE pregnancies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    age INT NOT NULL,
    lmp_date DATE NOT NULL,
    edd_date DATE GENERATED ALWAYS AS (DATE_ADD(lmp_date, INTERVAL 280 DAY)) STORED,
    current_week INT GENERATED ALWAYS AS (
        CASE 
            WHEN DATEDIFF(CURDATE(), lmp_date) < 0 THEN 0
            WHEN DATEDIFF(CURDATE(), lmp_date) > 280 THEN 40
            ELSE FLOOR(DATEDIFF(CURDATE(), lmp_date) / 7)
        END
    ) STORED,
    current_trimester INT GENERATED ALWAYS AS (
        CASE 
            WHEN FLOOR(DATEDIFF(CURDATE(), lmp_date) / 7) <= 12 THEN 1
            WHEN FLOOR(DATEDIFF(CURDATE(), lmp_date) / 7) <= 27 THEN 2
            ELSE 3
        END
    ) STORED,
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(15),
    assigned_doctor_id INT,
    risk_level ENUM('low', 'moderate', 'high') DEFAULT 'low',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_doctor_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_assigned_doctor (assigned_doctor_id),
    INDEX idx_risk_level (risk_level)
);

-- ANC visits table
CREATE TABLE visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregnancy_id INT NOT NULL,
    doctor_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_type ENUM('routine', 'emergency', 'follow_up') DEFAULT 'routine',
    gestational_week INT,
    weight DECIMAL(5,2),
    blood_pressure_systolic INT,
    blood_pressure_diastolic INT,
    hemoglobin DECIMAL(3,1),
    sugar_level DECIMAL(5,2),
    protein_urine ENUM('nil', 'trace', '+', '++', '+++') DEFAULT 'nil',
    fundal_height DECIMAL(4,1),
    fetal_heart_rate INT,
    complaints TEXT,
    examination_notes TEXT,
    advice TEXT,
    next_visit_date DATE,
    risk_factors TEXT,
    medications TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pregnancy_id (pregnancy_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_visit_date (visit_date)
);

-- Reminders table for ANC appointments
CREATE TABLE reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregnancy_id INT NOT NULL,
    reminder_type ENUM('anc_visit', 'vaccination', 'test', 'medication') NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    reminder_date DATE NOT NULL,
    reminder_time TIME DEFAULT '09:00:00',
    is_sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    send_via ENUM('email', 'sms', 'both') DEFAULT 'both',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(id) ON DELETE CASCADE,
    INDEX idx_pregnancy_id (pregnancy_id),
    INDEX idx_reminder_date (reminder_date),
    INDEX idx_is_sent (is_sent)
);

-- Nutrition tips table
CREATE TABLE nutrition_tips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trimester INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('food', 'supplement', 'avoid', 'general') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_trimester (trimester),
    INDEX idx_category (category)
);

-- Emergency contacts table
CREATE TABLE emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_name VARCHAR(100) NOT NULL,
    contact_number VARCHAR(15) NOT NULL,
    service_type ENUM('ambulance', 'hospital', 'police', 'helpline') NOT NULL,
    location VARCHAR(200),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_service_type (service_type)
);

-- High-risk alerts table
CREATE TABLE risk_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pregnancy_id INT NOT NULL,
    alert_type ENUM('bp_high', 'bp_low', 'hb_low', 'sugar_high', 'weight_gain', 'other') NOT NULL,
    alert_message TEXT NOT NULL,
    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
    detected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    resolved_by INT NULL,
    visit_id INT,
    FOREIGN KEY (pregnancy_id) REFERENCES pregnancies(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (visit_id) REFERENCES visits(id) ON DELETE SET NULL,
    INDEX idx_pregnancy_id (pregnancy_id),
    INDEX idx_alert_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_is_resolved (is_resolved)
);

-- Insert default data
-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, user_role, full_name, phone) VALUES
('admin', 'admin@maternalhealth.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', '1234567890');

-- Insert sample doctor/ASHA worker (password: doctor123)
INSERT INTO users (username, email, password_hash, user_role, full_name, phone) VALUES
('dr_sharma', 'dr.sharma@hospital.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor_asha', 'Dr. Priya Sharma', '9876543210');

-- Insert nutrition tips
INSERT INTO nutrition_tips (trimester, title, description, category) VALUES
(1, 'Folic Acid Intake', 'Take 400-600 mcg of folic acid daily to prevent neural tube defects', 'supplement'),
(1, 'Avoid Raw Fish', 'Avoid sushi, raw fish and undercooked seafood to prevent infections', 'avoid'),
(1, 'Iron Rich Foods', 'Include spinach, lentils, and lean meat for iron requirements', 'food'),
(2, 'Calcium Sources', 'Include dairy products, leafy greens, and fortified foods for bone development', 'food'),
(2, 'Protein Requirements', 'Increase protein intake to 75-100g daily for fetal growth', 'food'),
(2, 'Stay Hydrated', 'Drink 8-10 glasses of water daily to prevent dehydration', 'general'),
(3, 'Small Frequent Meals', 'Eat smaller, frequent meals to avoid heartburn and aid digestion', 'general'),
(3, 'Omega-3 Fatty Acids', 'Include fish oil supplements or walnuts for brain development', 'supplement'),
(3, 'Limit Caffeine', 'Limit caffeine intake to less than 200mg per day', 'avoid');

-- Insert emergency contacts
INSERT INTO emergency_contacts (service_name, contact_number, service_type, location) VALUES
('National Ambulance Service', '102', 'ambulance', 'Nationwide'),
('Emergency Police', '100', 'police', 'Nationwide'),
('Women Helpline', '1091', 'helpline', 'Nationwide'),
('Maternal Health Helpline', '104', 'helpline', 'Nationwide'),
('Local Hospital Emergency', '108', 'hospital', 'Local Area');

-- Create triggers for risk detection
DELIMITER //

CREATE TRIGGER check_risk_factors 
AFTER INSERT ON visits 
FOR EACH ROW
BEGIN
    DECLARE risk_count INT DEFAULT 0;
    DECLARE current_risk VARCHAR(10) DEFAULT 'low';
    
    -- Check for high blood pressure
    IF NEW.blood_pressure_systolic > 140 OR NEW.blood_pressure_diastolic > 90 THEN
        INSERT INTO risk_alerts (pregnancy_id, alert_type, alert_message, severity, visit_id) 
        VALUES (NEW.pregnancy_id, 'bp_high', 'High blood pressure detected - requires immediate attention', 'high', NEW.id);
        SET risk_count = risk_count + 2;
    END IF;
    
    -- Check for low hemoglobin
    IF NEW.hemoglobin < 7.0 THEN
        INSERT INTO risk_alerts (pregnancy_id, alert_type, alert_message, severity, visit_id) 
        VALUES (NEW.pregnancy_id, 'hb_low', 'Severe anemia detected - immediate treatment required', 'critical', NEW.id);
        SET risk_count = risk_count + 3;
    ELSEIF NEW.hemoglobin < 11.0 THEN
        INSERT INTO risk_alerts (pregnancy_id, alert_type, alert_message, severity, visit_id) 
        VALUES (NEW.pregnancy_id, 'hb_low', 'Low hemoglobin levels - iron supplementation needed', 'medium', NEW.id);
        SET risk_count = risk_count + 1;
    END IF;
    
    -- Check for high sugar levels
    IF NEW.sugar_level > 140 THEN
        INSERT INTO risk_alerts (pregnancy_id, alert_type, alert_message, severity, visit_id) 
        VALUES (NEW.pregnancy_id, 'sugar_high', 'High blood sugar levels - diabetes screening required', 'high', NEW.id);
        SET risk_count = risk_count + 2;
    END IF;
    
    -- Update pregnancy risk level
    IF risk_count >= 3 THEN
        SET current_risk = 'high';
    ELSEIF risk_count >= 1 THEN
        SET current_risk = 'moderate';
    END IF;
    
    UPDATE pregnancies SET risk_level = current_risk WHERE id = NEW.pregnancy_id;
END//

DELIMITER ;

-- Create indexes for better performance
CREATE INDEX idx_pregnancies_lmp ON pregnancies(lmp_date);
CREATE INDEX idx_visits_composite ON visits(pregnancy_id, visit_date);
CREATE INDEX idx_reminders_composite ON reminders(reminder_date, is_sent);
