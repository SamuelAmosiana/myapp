-- ClassReserve CHAU Database Schema (Updated based on ERD)
-- Database: classreserve_chau

-- Create database
CREATE DATABASE IF NOT EXISTS classreserve_chau;
USE classreserve_chau;

-- 1. ROLES Table
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- 2. PROGRAMS Table
CREATE TABLE programs (
    program_id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(100) NOT NULL,
    program_type VARCHAR(50) NOT NULL
);

-- 3. COURSES Table
CREATE TABLE courses (
    course_id INT AUTO_INCREMENT PRIMARY KEY,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    program_id INT NOT NULL,
    FOREIGN KEY (program_id) REFERENCES programs(program_id)
);

-- 4. USERS Table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    student_id VARCHAR(20),
    course_id INT NULL, -- Added to link users to their courses
    intake VARCHAR(20) NULL, -- Added for student intake information
    year_of_study ENUM('1', '2', '3', '4') NULL, -- Added for student year
    department VARCHAR(100) NULL, -- Added for lecturers and admins
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (course_id) REFERENCES courses(course_id) ON DELETE SET NULL
);

-- 5. ROOMS Table
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    capacity INT NOT NULL,
    room_type ENUM('lecture_hall', 'classroom', 'lab', 'seminar_room') DEFAULT 'classroom',
    facilities TEXT, -- JSON string for amenities
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. BOOKINGS Table
CREATE TABLE bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    room_id INT NOT NULL,
    booked_by INT NOT NULL,
    approved_by INT,
    lecturer_id INT NULL, -- Added to specify which lecturer will teach
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    booking_date DATE NOT NULL, -- Added for easier querying
    duration_minutes INT NOT NULL, -- Added for easy duration calculation
    subject VARCHAR(100) NULL, -- Added for class subject
    status ENUM('pending', 'approved', 'cancelled') DEFAULT 'pending',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (booked_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    FOREIGN KEY (lecturer_id) REFERENCES users(user_id),
    -- Ensure no overlapping bookings for the same room
    UNIQUE KEY unique_room_time (room_id, start_time, end_time)
);

-- 7. NOTIFICATIONS Table (Additional for better user experience)
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    booking_id INT NULL,
    notification_type ENUM('booking_created', 'booking_approved', 'booking_rejected', 'booking_cancelled', 'class_reminder', 'general') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL
);

-- Create indexes for better performance
CREATE INDEX idx_bookings_date ON bookings(booking_date);
CREATE INDEX idx_bookings_room_time ON bookings(room_id, start_time, end_time);
CREATE INDEX idx_bookings_course ON bookings(course_id);
CREATE INDEX idx_bookings_status ON bookings(status);
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_users_student_id ON users(student_id);
CREATE INDEX idx_notifications_user_unread ON notifications(user_id, is_read);

-- ============================================
-- INSERT SAMPLE DATA
-- ============================================

-- 1. Insert Roles
INSERT INTO roles (role_name) VALUES
('admin'),
('lecturer'),
('class_rep'),
('student');

-- 2. Insert Programs
INSERT INTO programs (program_name, program_type) VALUES
('Bachelor of Information and Communication Technology', 'Degree'),
('Bachelor of Business Administration', 'Degree'),
('Bachelor of Education', 'Degree'),
('Diploma in Computer Studies', 'Diploma'),
('Diploma in Business Studies', 'Diploma');

-- 3. Insert Courses
INSERT INTO courses (course_name, course_code, program_id) VALUES
-- ICT Program courses
('Information and Communication Technology', 'ICT', 1),
('Software Engineering', 'SE', 1),
('Computer Networks', 'CN', 1),
('Database Systems', 'DBS', 1),

-- Business Program courses
('Business Administration', 'BBA', 2),
('Marketing Management', 'MM', 2),
('Human Resource Management', 'HRM', 2),

-- Education Program courses
('Primary Education', 'PEDU', 3),
('Secondary Education', 'SEDU', 3),

-- Diploma courses
('Computer Studies', 'DCS', 4),
('Business Studies', 'DBS', 5);

-- 4. Insert Users (Using student_id as password for simplicity)
INSERT INTO users (name, email, password_hash, role_id, student_id, course_id, intake, year_of_study, department) VALUES
-- Admin users
('System Administrator', 'admin@chau.edu.zm', 'ADMIN001', 1, 'ADMIN001', NULL, NULL, NULL, 'Administration'),
('Timetable Officer', 'timetable@chau.edu.zm', 'ADMIN002', 1, 'ADMIN002', NULL, NULL, NULL, 'Academic Affairs'),

-- Lecturers
('Mr. Zulu', 'zulu@chau.edu.zm', 'LEC001', 2, 'LEC001', NULL, NULL, NULL, 'ICT Department'),
('Dr. Chitungu', 'chitungu@chau.edu.zm', 'LEC002', 2, 'LEC002', NULL, NULL, NULL, 'Business Department'),
('Mrs. Banda', 'banda@chau.edu.zm', 'LEC003', 2, 'LEC003', NULL, NULL, NULL, 'Education Department'),
('Mr. Phiri', 'phiri@chau.edu.zm', 'LEC004', 2, 'LEC004', NULL, NULL, NULL, 'ICT Department'),

-- Class Representatives
('Samuel Sianamate', 'samuel@chau.edu.zm', '2104035934', 3, '2104035934', 1, 'SEPTEMBER 2024', '4', NULL),
('Mary Mulenga', 'mary@chau.edu.zm', '2104025123', 3, '2104025123', 5, 'SEPTEMBER 2024', '3', NULL),
('Peter Tembo', 'peter@chau.edu.zm', '2104045678', 3, '2104045678', 2, 'JANUARY 2024', '2', NULL),
('Grace Phiri', 'grace@chau.edu.zm', '2104056789', 3, '2104056789', 8, 'SEPTEMBER 2024', '1', NULL),

-- Students
('John Banda', 'john@chau.edu.zm', '2104067890', 4, '2104067890', 1, 'SEPTEMBER 2024', '4', NULL),
('Alice Mwanza', 'alice@chau.edu.zm', '2104078901', 4, '2104078901', 1, 'SEPTEMBER 2024', '4', NULL),
('David Lungu', 'david@chau.edu.zm', '2104089012', 4, '2104089012', 5, 'SEPTEMBER 2024', '3', NULL),
('Sarah Kunda', 'sarah@chau.edu.zm', '2104090123', 4, '2104090123', 2, 'JANUARY 2024', '2', NULL),
('Michael Zulu', 'michael@chau.edu.zm', '2104101234', 4, '2104101234', 8, 'SEPTEMBER 2024', '1', NULL),
('Rose Mubanga', 'rose@chau.edu.zm', '2104112345', 4, '2104112345', 10, 'JANUARY 2024', '1', NULL);

-- 5. Insert Rooms
INSERT INTO rooms (room_name, location, capacity, room_type, facilities) VALUES
('Main Lecture Hall', 'Main Building - Ground Floor', 150, 'lecture_hall', '{"projector": true, "ac": true, "microphone": true, "whiteboard": true}'),
('ICT Lab 1', 'ICT Block - First Floor', 40, 'lab', '{"computers": 40, "projector": true, "ac": false, "internet": true}'),
('ICT Lab 2', 'ICT Block - First Floor', 35, 'lab', '{"computers": 35, "projector": true, "ac": true, "internet": true}'),
('Classroom A', 'Main Building - Second Floor', 60, 'classroom', '{"projector": true, "whiteboard": true, "ac": false}'),
('Classroom B', 'Main Building - Second Floor', 50, 'classroom', '{"projector": false, "whiteboard": true, "ac": false}'),
('Business Seminar Room', 'Business Block - Ground Floor', 25, 'seminar_room', '{"projector": true, "ac": true, "whiteboard": true, "conference_table": true}'),
('Education Hall', 'Education Block - Ground Floor', 80, 'classroom', '{"projector": true, "whiteboard": true, "ac": true}'),
('Library Study Room', 'Library Building - First Floor', 20, 'seminar_room', '{"projector": false, "whiteboard": true, "ac": true, "quiet_zone": true}');

-- 6. Insert Sample Bookings
INSERT INTO bookings (course_id, room_id, booked_by, approved_by, lecturer_id, start_time, end_time, booking_date, duration_minutes, subject, status, remarks) VALUES
-- Today's bookings
(1, 1, 7, 9, 9, CONCAT(CURDATE(), ' 08:00:00'), CONCAT(CURDATE(), ' 10:00:00'), CURDATE(), 120, 'Database Systems', 'approved', 'Regular class session'),
(5, 6, 8, 10, 10, CONCAT(CURDATE(), ' 10:30:00'), CONCAT(CURDATE(), ' 12:30:00'), CURDATE(), 120, 'Marketing Principles', 'approved', 'Weekly seminar'),
(1, 2, 7, NULL, 9, CONCAT(CURDATE(), ' 14:00:00'), CONCAT(CURDATE(), ' 16:00:00'), CURDATE(), 120, 'Network Programming Lab', 'pending', 'Practical session'),

-- Tomorrow's bookings
(2, 4, 9, 10, 10, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 09:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 11:00:00'), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 120, 'Strategic Management', 'pending', 'Group presentation'),
(8, 7, 10, 11, 11, CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 13:00:00'), CONCAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), ' 15:00:00'), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 120, 'Teaching Methods', 'approved', 'Student teaching practice');

-- 7. Insert Sample Notifications
INSERT INTO notifications (user_id, booking_id, notification_type, title, message) VALUES
-- Notifications for approved bookings
(7, 1, 'booking_approved', 'Booking Approved', 'Your booking for Main Lecture Hall on ' + CURDATE() + ' from 08:00-10:00 has been approved.'),
(8, 2, 'booking_approved', 'Booking Approved', 'Your booking for Business Seminar Room has been approved by the lecturer.'),

-- Reminder notifications
(13, 1, 'class_reminder', 'Class Reminder', 'Database Systems class starts in 1 hour at Main Lecture Hall.'),
(14, 1, 'class_reminder', 'Class Reminder', 'Database Systems class starts in 1 hour at Main Lecture Hall.'),

-- Pending approval notifications
(9, 3, 'booking_created', 'New Booking Request', 'A new booking request for ICT Lab 1 requires your approval.'),
(10, 4, 'booking_created', 'New Booking Request', 'A new booking request for Classroom A requires your approval.');

-- ============================================
-- HELPFUL VIEWS FOR QUICK QUERIES
-- ============================================

-- View for current bookings with user and room details
CREATE VIEW current_bookings_view AS
SELECT 
    b.booking_id,
    b.booking_date,
    b.start_time,
    b.end_time,
    b.duration_minutes,
    b.subject,
    b.status,
    r.room_name,
    r.location,
    r.capacity,
    c.course_name,
    c.course_code,
    u_booked.name as booked_by_name,
    u_booked.student_id as booked_by_student_id,
    u_lecturer.name as lecturer_name,
    u_approved.name as approved_by_name,
    p.program_name
FROM bookings b
JOIN rooms r ON b.room_id = r.room_id
JOIN courses c ON b.course_id = c.course_id
JOIN programs p ON c.program_id = p.program_id
JOIN users u_booked ON b.booked_by = u_booked.user_id
LEFT JOIN users u_lecturer ON b.lecturer_id = u_lecturer.user_id
LEFT JOIN users u_approved ON b.approved_by = u_approved.user_id;

-- View for available rooms at current time
CREATE VIEW available_rooms_now AS
SELECT 
    r.*,
    CASE 
        WHEN EXISTS (
            SELECT 1 FROM bookings b 
            WHERE b.room_id = r.room_id 
            AND b.booking_date = CURDATE()
            AND b.status = 'approved'
            AND NOW() BETWEEN b.start_time AND b.end_time
        ) THEN 'occupied'
        ELSE 'available'
    END as current_status
FROM rooms r
WHERE r.is_available = TRUE;

-- View for user details with roles
CREATE VIEW users_with_roles AS
SELECT 
    u.user_id,
    u.name,
    u.email,
    u.student_id,
    u.intake,
    u.year_of_study,
    u.department,
    u.is_active,
    r.role_name,
    c.course_name,
    c.course_code,
    p.program_name
FROM users u
JOIN roles r ON u.role_id = r.role_id
LEFT JOIN courses c ON u.course_id = c.course_id
LEFT JOIN programs p ON c.program_id = p.program_id;

-- ============================================
-- SUMMARY OF INSERTED DATA
-- ============================================

-- Check inserted data
SELECT 'ROLES' as table_name, COUNT(*) as count FROM roles
UNION ALL
SELECT 'PROGRAMS' as table_name, COUNT(*) as count FROM programs
UNION ALL
SELECT 'COURSES' as table_name, COUNT(*) as count FROM courses
UNION ALL
SELECT 'USERS' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'ROOMS' as table_name, COUNT(*) as count FROM rooms
UNION ALL
SELECT 'BOOKINGS' as table_name, COUNT(*) as count FROM bookings
UNION ALL
SELECT 'NOTIFICATIONS' as table_name, COUNT(*) as count FROM notifications;