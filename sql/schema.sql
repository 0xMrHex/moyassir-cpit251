-- ============================================================================
-- CPIT251: Hajj and Umrah Registration System
-- Relational Database Schema DDL — SQLite 3 (via PHP PDO)
-- 6 Normalized (3NF) Tables: USERS, DRIVERS, BUSES, TENTS, ATTENDEES, ITINERARIES
-- Team: Mohammed Aljedani, Omar Alofi, Saud Faisal Almesfer (Section IT2)
-- ============================================================================

PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS ITINERARIES;
DROP TABLE IF EXISTS ATTENDEES;
DROP TABLE IF EXISTS BUSES;
DROP TABLE IF EXISTS DRIVERS;
DROP TABLE IF EXISTS TENTS;
DROP TABLE IF EXISTS USERS;

-- ----------------------------------------------------------------------------
-- 1. USERS: Authentication credentials and role-based access control
-- ----------------------------------------------------------------------------
CREATE TABLE USERS (
    user_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    username      TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role          TEXT NOT NULL CHECK (role IN ('admin', 'attendee', 'driver')),
    created_at    TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ----------------------------------------------------------------------------
-- 2. DRIVERS: Bus drivers linked to driver portal login accounts & service status
-- ----------------------------------------------------------------------------
CREATE TABLE DRIVERS (
    driver_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id        INTEGER NOT NULL UNIQUE,
    full_name      TEXT NOT NULL,
    license_number TEXT NOT NULL UNIQUE,
    phone          TEXT NOT NULL,
    status         TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'out_of_service')),
    created_at     TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE
);

-- ----------------------------------------------------------------------------
-- 3. BUSES: Campaign transport vehicles, assigned driver (1:0..1), capacity & status
-- ----------------------------------------------------------------------------
CREATE TABLE BUSES (
    bus_id         INTEGER PRIMARY KEY AUTOINCREMENT,
    bus_number     TEXT NOT NULL UNIQUE,
    driver_id      INTEGER UNIQUE,
    max_capacity   INTEGER NOT NULL CHECK (max_capacity > 0),
    departure_time TEXT NOT NULL,
    status         TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'out_of_service')),
    FOREIGN KEY (driver_id) REFERENCES DRIVERS(driver_id) ON DELETE SET NULL
);

-- ----------------------------------------------------------------------------
-- 4. TENTS: Accommodation units (Mina/Arafat tents or Umrah housing blocks)
-- ----------------------------------------------------------------------------
CREATE TABLE TENTS (
    tent_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    tent_number   TEXT NOT NULL UNIQUE,
    location_zone TEXT NOT NULL,
    max_capacity  INTEGER NOT NULL CHECK (max_capacity > 0)
);

-- ----------------------------------------------------------------------------
-- 5. ATTENDEES: Pilgrim profiles linked to user accounts, buses, and tents
-- ----------------------------------------------------------------------------
CREATE TABLE ATTENDEES (
    attendee_id   INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER NOT NULL UNIQUE,
    full_name     TEXT NOT NULL,
    national_id   TEXT NOT NULL UNIQUE,
    phone         TEXT NOT NULL,
    campaign_type TEXT NOT NULL DEFAULT 'Hajj' CHECK (campaign_type IN ('Hajj', 'Umrah')),
    bus_id        INTEGER,
    tent_id       INTEGER,
    registered_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES USERS(user_id) ON DELETE CASCADE,
    FOREIGN KEY (bus_id)  REFERENCES BUSES(bus_id)  ON DELETE SET NULL,
    FOREIGN KEY (tent_id) REFERENCES TENTS(tent_id) ON DELETE SET NULL
);

-- ----------------------------------------------------------------------------
-- 6. ITINERARIES: Scheduled movement & ritual events per assigned bus
-- ----------------------------------------------------------------------------
CREATE TABLE ITINERARIES (
    itinerary_id INTEGER PRIMARY KEY AUTOINCREMENT,
    title        TEXT NOT NULL,
    event_date   TEXT NOT NULL,
    description  TEXT NOT NULL,
    bus_id       INTEGER NOT NULL,
    FOREIGN KEY (bus_id) REFERENCES BUSES(bus_id) ON DELETE CASCADE
);

-- Performance indexes for frequent search and foreign-key lookups
CREATE INDEX idx_buses_driver_id    ON BUSES(driver_id);
CREATE INDEX idx_attendees_bus_id   ON ATTENDEES(bus_id);
CREATE INDEX idx_attendees_tent_id  ON ATTENDEES(tent_id);
CREATE INDEX idx_attendees_fullname ON ATTENDEES(full_name);
CREATE INDEX idx_itineraries_bus_id ON ITINERARIES(bus_id);
