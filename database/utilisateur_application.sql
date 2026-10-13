
CREATE USER IF NOT EXISTS 'paie_app'@'localhost' IDENTIFIED BY 'ChangeMoi_2026';
GRANT SELECT, INSERT, UPDATE, DELETE ON personnel_paie.* TO 'paie_app'@'localhost';
FLUSH PRIVILEGES;
