-- Seed data for local development

-- Insert sample interviewers (companies)
INSERT INTO interviewers (name, table_number, is_paused, image_url) VALUES 
    ('TechCorp Industries', 'A1', 0, NULL),
    ('DataFlow Solutions', 'A2', 0, NULL),
    ('CloudNine Systems', 'B1', 0, NULL),
    ('InnovateTech', 'B2', 0, NULL),
    ('FutureSoft Inc', 'C1', 0, NULL);

-- Insert operators (password is 'password123' - SHA-256 hashed)
-- In production, use proper password hashing!
INSERT INTO operators (username, password_hash, role, interviewer_id) VALUES 
    ('secretary1', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'SECRETARY', NULL),
    ('secretary2', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'SECRETARY', NULL),
    ('gatekeeper', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'GATEKEEPER', NULL),
    ('techcorp', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'COMPANY', 1),
    ('dataflow', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'COMPANY', 2),
    ('cloudnine', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'COMPANY', 3),
    ('innovate', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'COMPANY', 4),
    ('futuresoft', 'ef92b778bafe771e89245b89ecbc08a44a4e166c06659911881f383d4473e94f', 'COMPANY', 5);


-- Insert sample students
INSERT INTO interviewees (first_name, last_name, email, major, graduation_year, is_paused) VALUES 
    ('Alice', 'Johnson', 'alice.j@university.edu', 'Computer Science', '2025', 0),
    ('Bob', 'Smith', 'bob.s@university.edu', 'Electrical Engineering', '2024', 0),
    ('Carol', 'Williams', 'carol.w@university.edu', 'Computer Science', '2025', 0),
    ('David', 'Brown', 'david.b@university.edu', 'Information Systems', '2026', 0),
    ('Eva', 'Davis', 'eva.d@university.edu', 'Computer Engineering', '2025', 0);

