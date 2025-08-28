-- HUMAN RESOURCE 4 Compensation & HR Intelligence Database
-- Hospital System Database Structure

CREATE DATABASE IF NOT EXISTS hr4_hospital;
USE hr4_hospital;

-- Core Human Capital Management (HCM) Tables
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE positions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    department_id INT,
    salary_grade VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE employees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    date_of_birth DATE,
    hire_date DATE,
    position_id INT,
    department_id INT,
    status ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES positions(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT,
    date DATE,
    time_in TIME,
    time_out TIME,
    status ENUM('present', 'absent', 'late', 'half-day') DEFAULT 'present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Payroll Management Tables
CREATE TABLE salary_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    position_id INT,
    basic_salary DECIMAL(10,2),
    allowances DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES positions(id)
);

CREATE TABLE payroll (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT,
    month INT,
    year INT,
    basic_salary DECIMAL(10,2),
    allowances DECIMAL(10,2),
    deductions DECIMAL(10,2),
    net_salary DECIMAL(10,2),
    status ENUM('pending', 'processed', 'paid') DEFAULT 'pending',
    payment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE deductions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    amount DECIMAL(10,2),
    type ENUM('fixed', 'percentage') DEFAULT 'fixed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Compensation Planning Tables
CREATE TABLE compensation_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    description TEXT,
    effective_date DATE,
    status ENUM('draft', 'active', 'inactive') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE compensation_components (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_id INT,
    name VARCHAR(100),
    type ENUM('bonus', 'incentive', 'allowance', 'other'),
    amount DECIMAL(10,2),
    criteria TEXT,
    FOREIGN KEY (plan_id) REFERENCES compensation_plans(id)
);

-- HMO & Benefits Administration Tables
CREATE TABLE hmo_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    contact_person VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE hmo_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    provider_id INT,
    plan_name VARCHAR(100),
    coverage_details TEXT,
    premium_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES hmo_providers(id)
);

CREATE TABLE employee_benefits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT,
    hmo_plan_id INT,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (hmo_plan_id) REFERENCES hmo_plans(id)
);

-- HR Analytics Dashboard Tables
CREATE TABLE performance_metrics (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT,
    metric_name VARCHAR(100),
    value DECIMAL(5,2),
    target DECIMAL(5,2),
    period VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

CREATE TABLE training_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT,
    training_name VARCHAR(100),
    provider VARCHAR(100),
    start_date DATE,
    end_date DATE,
    cost DECIMAL(10,2),
    status ENUM('scheduled', 'in-progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Users and Authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'hr_manager', 'hr_staff', 'manager', 'employee') DEFAULT 'employee',
    employee_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Insert sample data
INSERT INTO departments (name, description) VALUES
('Human Resources', 'HR Department for managing human capital'),
('Information Technology', 'IT Department for technical support'),
('Finance', 'Finance and Accounting Department'),
('Operations', 'Hospital Operations Department'),
('Marketing', 'Marketing and Communications Department');

INSERT INTO positions (title, department_id, salary_grade) VALUES
('HR Manager', 1, 'SG-18'),
('HR Staff', 1, 'SG-11'),
('IT Manager', 2, 'SG-18'),
('IT Staff', 2, 'SG-11'),
('Finance Manager', 3, 'SG-18'),
('Accountant', 3, 'SG-14'),
('Operations Manager', 4, 'SG-18'),
('Marketing Manager', 5, 'SG-18');

INSERT INTO employees (employee_id, first_name, last_name, email, phone, hire_date, position_id, department_id) VALUES
('EMP001', 'John', 'Doe', 'john.doe@hospital.com', '09123456789', '2023-01-15', 1, 1),
('EMP002', 'Jane', 'Smith', 'jane.smith@hospital.com', '09123456790', '2023-02-01', 2, 1),
('EMP003', 'Mike', 'Johnson', 'mike.johnson@hospital.com', '09123456791', '2023-01-20', 3, 2),
('EMP004', 'Sarah', 'Williams', 'sarah.williams@hospital.com', '09123456792', '2023-02-15', 4, 2);

INSERT INTO users (username, password, email, role, employee_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hospital.com', 'admin', NULL),
('hr_manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'john.doe@hospital.com', 'hr_manager', 1),
('hr_staff', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'jane.smith@hospital.com', 'hr_staff', 2);

INSERT INTO hmo_providers (name, contact_person, phone, email) VALUES
('Maxicare', 'Juan Dela Cruz', '02-8123456', 'info@maxicare.com'),
('Medicard', 'Maria Santos', '02-8123457', 'info@medicard.com'),
('PhilCare', 'Pedro Reyes', '02-8123458', 'info@philcare.com');
