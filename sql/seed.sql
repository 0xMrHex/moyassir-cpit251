-- ============================================================================
-- CPIT251: Hajj and Umrah Registration System
-- Seed Data — SQLite 3 (via PHP PDO)
-- Default Credentials:
--   Admin:    username = admin        | password = Admin@2026
--   Driver:   username = driver1      | password = Driver@2026
--   Attendee: username = 1098765431   | password = Pilgrim@2026
-- ============================================================================

PRAGMA foreign_keys = ON;

INSERT INTO USERS (username, password_hash, role) VALUES
('admin', '$2y$10$h4PoiAiHBBn2eK44aphicOES03.goHnWQKGAsJXJ5Etx391w4zx1.', 'admin'),
('driver1', '$2y$10$kMwU1PC0U7MU..K6ZMiNFe.XNhqy9HjCGtVatUYih7G90keNxGjKO', 'driver'),
('driver2', '$2y$10$kMwU1PC0U7MU..K6ZMiNFe.XNhqy9HjCGtVatUYih7G90keNxGjKO', 'driver'),
('driver3', '$2y$10$kMwU1PC0U7MU..K6ZMiNFe.XNhqy9HjCGtVatUYih7G90keNxGjKO', 'driver'),
('1098765431', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765432', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765433', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765434', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765435', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765436', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765437', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765438', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765439', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765440', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765441', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee'),
('1098765442', '$2y$10$xrz6ntsR0K8Tdk8gV/Ge4OdkZHwHazQFWp/0eyY1tA.Utcx65nJ7m', 'attendee');

INSERT INTO DRIVERS (user_id, full_name, license_number, phone, status) VALUES
((SELECT user_id FROM USERS WHERE username='driver1'), 'Khalid Al-Harbi', 'DL-2026-101', '0501122334', 'active'),
((SELECT user_id FROM USERS WHERE username='driver2'), 'Fahad Al-Ghamdi', 'DL-2026-102', '0554433221', 'active'),
((SELECT user_id FROM USERS WHERE username='driver3'), 'Tariq Al-Zahrani', 'DL-2026-201', '0567788990', 'active');

-- BUS-101 intentionally seeded at max_capacity=5 (FULL) to demonstrate capacity enforcement
INSERT INTO BUSES (bus_number, driver_id, max_capacity, departure_time, status) VALUES
('BUS-101', 1, 5, '8 Dhul-Hijjah — 06:30 AM (Jeddah Assembly Point)', 'active'),
('BUS-102', 2, 10, '8 Dhul-Hijjah — 07:15 AM (Jeddah Assembly Point)', 'active'),
('BUS-201', 3, 8, '15 Ramadan — 04:00 PM (Jeddah Umrah Terminal)', 'active');

-- TENT-A01 intentionally seeded at max_capacity=6 (FULL) to demonstrate tent capacity enforcement
INSERT INTO TENTS (tent_number, location_zone, max_capacity) VALUES
('TENT-A01', 'Mina Zone A — Gate 4 (Camp 112)', 6),
('TENT-B02', 'Mina Zone B — Gate 9 (Camp 118)', 10),
('TENT-U01', 'Makkah Aziziyah Residence — Floor 2', 8);

INSERT INTO ATTENDEES (user_id, full_name, national_id, phone, campaign_type, bus_id, tent_id) VALUES
((SELECT user_id FROM USERS WHERE username='1098765431'), 'Abdullah Nasser Al-Qahtani', '1098765431', '0502345601', 'Hajj', 1, 1),
((SELECT user_id FROM USERS WHERE username='1098765432'), 'Faisal Mohammed Al-Otaibi', '1098765432', '0502345602', 'Hajj', 1, 1),
((SELECT user_id FROM USERS WHERE username='1098765433'), 'Noura Saeed Al-Dossary', '1098765433', '0502345603', 'Hajj', 1, 1),
((SELECT user_id FROM USERS WHERE username='1098765434'), 'Sultan Abdulrahman Al-Shehri', '1098765434', '0502345604', 'Hajj', 1, 1),
((SELECT user_id FROM USERS WHERE username='1098765435'), 'Maha Ibrahim Al-Malki', '1098765435', '0502345605', 'Hajj', 1, 1),
((SELECT user_id FROM USERS WHERE username='1098765436'), 'Yousef Saleh Al-Sulaiman', '1098765436', '0502345606', 'Hajj', 2, 1),
((SELECT user_id FROM USERS WHERE username='1098765437'), 'Reem Khalid Al-Juhani', '1098765437', '0502345607', 'Hajj', 2, 2),
((SELECT user_id FROM USERS WHERE username='1098765438'), 'Turki Majed Al-Subaie', '1098765438', '0502345608', 'Hajj', 2, 2),
((SELECT user_id FROM USERS WHERE username='1098765439'), 'Huda Hamad Al-Anazi', '1098765439', '0502345609', 'Hajj', 2, 2),
((SELECT user_id FROM USERS WHERE username='1098765440'), 'Badr Adel Al-Shammari', '1098765440', '0502345610', 'Hajj', 2, 2),
((SELECT user_id FROM USERS WHERE username='1098765441'), 'Layla Omar Al-Bishi', '1098765441', '0502345611', 'Umrah', 3, 3),
((SELECT user_id FROM USERS WHERE username='1098765442'), 'Mansour Tariq Al-Rashed', '1098765442', '0502345612', 'Umrah', 3, 3);

INSERT INTO ITINERARIES (title, event_date, description, bus_id) VALUES
('Tarwiyah Day Departure to Mina', '8 Dhul-Hijjah — 06:30 AM', 'Board BUS-101 at Jeddah Assembly Point Gate 2. Carry Nusuk card and Ihram essentials.', 1),
('Arafat Day Transfer', '9 Dhul-Hijjah — 05:00 AM', 'Group assembly outside Mina Zone A Gate 4 for bus dispatch to Arafat Camp.', 1),
('Muzdalifah Overnight Movement', '9 Dhul-Hijjah — 07:15 PM', 'Post-Maghrib boarding to Muzdalifah open ground; follow bus captain green banner.', 1),
('Tarwiyah Day Departure to Mina (Wave 2)', '8 Dhul-Hijjah — 07:15 AM', 'Board BUS-102 at Jeddah Assembly Point Gate 3 directly to Mina Zone B.', 2),
('Arafat Day Transfer (Wave 2)', '9 Dhul-Hijjah — 05:30 AM', 'Assemble at Mina Zone B Gate 9 for guided transfer to Arafat.', 2),
('Umrah Shuttle to Masjid Al-Haram', '15 Ramadan — 04:00 PM', 'Departure from Jeddah Umrah Terminal via Miqat checkpoint to Aziziyah Residence and Al-Haram.', 3);

INSERT INTO SUPPORT_TICKETS (attendee_id, category, priority, subject, message, status, admin_reply) VALUES
(1, 'Schedule / Itinerary', 'normal', 'Luggage Check-In Time for BUS-101', 'Assalamu Alaikum, could you confirm whether Gate 2 opens at 05:45 AM for luggage loading before departure?', 'resolved', 'Wa Alaikum Assalam. Yes, Gate 2 opens at 05:45 AM and Driver Khalid Al-Harbi will assist with luggage tagging.'),
(7, 'Accommodation / Tent', 'urgent', 'Ground-Row Placement in TENT-B02', 'Requesting ground-row mattress space near Mina Zone B Gate 9 entrance due to knee mobility.', 'open', NULL);
