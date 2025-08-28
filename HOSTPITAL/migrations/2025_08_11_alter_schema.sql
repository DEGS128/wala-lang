-- Run this in phpMyAdmin on database hr4_hospital

-- PAYROLL: add period and timestamps
ALTER TABLE payroll 
    ADD COLUMN IF NOT EXISTS period VARCHAR(7) NULL AFTER employee_id,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL AFTER created_at;

-- EMPLOYEES: add salary and emergency contacts; extend status
ALTER TABLE employees 
    ADD COLUMN IF NOT EXISTS salary DECIMAL(12,2) NULL AFTER hire_date,
    ADD COLUMN IF NOT EXISTS emergency_contact VARCHAR(255) NULL AFTER salary,
    ADD COLUMN IF NOT EXISTS emergency_phone VARCHAR(50) NULL AFTER emergency_contact,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL AFTER created_at;

-- Update status enum to include 'on_leave'
ALTER TABLE employees MODIFY COLUMN status ENUM('active','inactive','terminated','on_leave') DEFAULT 'active';

-- POSITIONS: add optional salary range and details
ALTER TABLE positions 
    ADD COLUMN IF NOT EXISTS description TEXT NULL AFTER salary_grade,
    ADD COLUMN IF NOT EXISTS min_salary DECIMAL(12,2) NULL AFTER description,
    ADD COLUMN IF NOT EXISTS max_salary DECIMAL(12,2) NULL AFTER min_salary,
    ADD COLUMN IF NOT EXISTS requirements TEXT NULL AFTER max_salary,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL AFTER created_at;

-- SALARY STRUCTURES: add range/percentages and updated_at
ALTER TABLE salary_structures 
    ADD COLUMN IF NOT EXISTS min_salary DECIMAL(12,2) NULL AFTER position_id,
    ADD COLUMN IF NOT EXISTS max_salary DECIMAL(12,2) NULL AFTER min_salary,
    ADD COLUMN IF NOT EXISTS allowances_percentage DECIMAL(5,2) NULL AFTER allowances,
    ADD COLUMN IF NOT EXISTS deductions_percentage DECIMAL(5,2) NULL AFTER allowances_percentage,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL AFTER created_at;

-- COMPENSATION COMPONENTS: ensure created_at
ALTER TABLE compensation_components 
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- HMO PLANS: already has plan_name; ensure created_at exists (from schema)
-- No change needed

-- ATTENDANCE: ensure columns are (date, time_in, time_out). No change

-- USERS: no change needed

-- Helpful indexes
CREATE INDEX IF NOT EXISTS idx_payroll_period ON payroll(period);
CREATE INDEX IF NOT EXISTS idx_payroll_month_year ON payroll(year, month);
CREATE INDEX IF NOT EXISTS idx_employees_department ON employees(department_id);
