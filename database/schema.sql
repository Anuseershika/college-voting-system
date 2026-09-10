-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `college_voting` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `college_voting`;

-- --------------------------------------------------------
-- Table structure for table `admin`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `students`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `has_voted` TINYINT(1) DEFAULT 0 NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `candidate_applications`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `candidate_applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL UNIQUE,
  `candidate_name` VARCHAR(100) NOT NULL,
  `department` VARCHAR(100) NOT NULL,
  `position` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `photo` VARCHAR(255) NOT NULL,
  `manifesto` TEXT NOT NULL,
  `status` VARCHAR(20) DEFAULT 'Pending' NOT NULL -- 'Pending', 'Approved', 'Rejected'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `votes`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `votes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) NOT NULL UNIQUE, -- Ensures a student can only vote once
  `candidate_id` INT NOT NULL,
  `vote_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`candidate_id`) REFERENCES `candidate_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `system_settings`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(50) NOT NULL UNIQUE,
  `setting_value` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Seed Data
-- --------------------------------------------------------

-- Default Admin: username = admin, password = admin123
INSERT INTO `admin` (`id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$KGm6rSeX6EkpvnyzfwHScOIstSvJzd/1E4sb9Xm3Q9fz2tgcbXLA.')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Default System Settings: election_status = 'voting' (can be 'setup', 'voting', 'ended')
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('election_status', 'voting')
ON DUPLICATE KEY UPDATE `setting_key`=`setting_key`;

-- Seed Students: password is 'student123'
INSERT INTO `students` (`student_id`, `name`, `email`, `password`, `has_voted`) VALUES
('S101', 'John Doe', 'john@college.edu', '$2y$10$t.kb531Q/jDgS0/Ymp9R5O2vvbPdis1A.Ni5bUry/WlPqgskdooPq', 0),
('S102', 'Jane Smith', 'jane@college.edu', '$2y$10$t.kb531Q/jDgS0/Ymp9R5O2vvbPdis1A.Ni5bUry/WlPqgskdooPq', 0),
('S103', 'Robert Johnson', 'robert@college.edu', '$2y$10$t.kb531Q/jDgS0/Ymp9R5O2vvbPdis1A.Ni5bUry/WlPqgskdooPq', 0),
('S104', 'Alice Williams', 'alice@college.edu', '$2y$10$t.kb531Q/jDgS0/Ymp9R5O2vvbPdis1A.Ni5bUry/WlPqgskdooPq', 0),
('S105', 'Charlie Brown', 'charlie@college.edu', '$2y$10$t.kb531Q/jDgS0/Ymp9R5O2vvbPdis1A.Ni5bUry/WlPqgskdooPq', 0)
ON DUPLICATE KEY UPDATE `student_id`=`student_id`;

-- Seed Candidates: password is 'candidate123'
-- Two approved candidates, one pending, one rejected
INSERT INTO `candidate_applications` (`student_id`, `candidate_name`, `department`, `position`, `email`, `password`, `photo`, `manifesto`, `status`) VALUES
('C201', 'Michael Green', 'Computer Science', 'President', 'michael@college.edu', '$2y$10$XhfTCyaxesFt0lldw8aLD.yaejdz.IKeiJRgd3U7x7LSNB2Okzdji', 'default_candidate.svg', 'Empowering students through technology. I will work to secure 24/7 lab access, organize campus hackathons, and streamline student funding applications.', 'Approved'),
('C202', 'Emily White', 'Mechanical Engineering', 'President', 'emily@college.edu', '$2y$10$XhfTCyaxesFt0lldw8aLD.yaejdz.IKeiJRgd3U7x7LSNB2Okzdji', 'default_candidate.svg', 'Sustainability first! I plan to implement campus-wide recycling drives, solar-powered study hubs, and subsidized public transit vouchers for all students.', 'Approved'),
('C203', 'David Black', 'Business Administration', 'Vice President', 'david@college.edu', '$2y$10$XhfTCyaxesFt0lldw8aLD.yaejdz.IKeiJRgd3U7x7LSNB2Okzdji', 'default_candidate.svg', 'Bridging the gap between students and career opportunities. I will launch a peer-to-peer business incubator and invite industry experts for monthly workshops.', 'Pending'),
('C204', 'Sarah Connor', 'Physics', 'Vice President', 'sarah@college.edu', '$2y$10$XhfTCyaxesFt0lldw8aLD.yaejdz.IKeiJRgd3U7x7LSNB2Okzdji', 'default_candidate.svg', 'Advocating for better research labs and fairer academic policies. Let\'s make sure student voices are heard in curriculum committees.', 'Approved'),
('C205', 'James Smith', 'Chemistry', 'Treasurer', 'james@college.edu', '$2y$10$XhfTCyaxesFt0lldw8aLD.yaejdz.IKeiJRgd3U7x7LSNB2Okzdji', 'default_candidate.svg', 'Budget transparency is key. I will publish monthly financial audits and ensure club funding is distributed equitably based on active membership.', 'Rejected')
ON DUPLICATE KEY UPDATE `student_id`=`student_id`;
