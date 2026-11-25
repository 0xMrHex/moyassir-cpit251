# UML Deployment Diagram — Physical System Architecture

Illustrates the physical hardware nodes, client devices, network protocol, PHP web server runtime, PDO database driver, and persistent relational SQLite 3 storage tier.

```mermaid
flowchart TB
    subgraph ClientTier["Client Tier (End-User Hardware Devices)"]
        AdminPC["Device: Campaign Manager Workstation<br/>(Windows / macOS Desktop)<br/>Web Browser (Chrome / Edge)<br/>HTML5, CSS3, Print-to-PDF & CSV"]
        DriverPhone["Device: Bus Driver Smartphone<br/>(iOS / Android Mobile)<br/>Mobile Web Browser<br/>Driver Schedule & Boarding Portal"]
        PilgrimPhone["Device: Attendee Smartphone<br/>(iOS / Android Mobile)<br/>Mobile Web Browser<br/>Pilgrim Pass & Customer Support"]
    end

    subgraph NetworkLayer["Network Layer"]
        HTTP(("HTTP / HTTPS<br/>TCP Port 80 / 443<br/>(Local Dev: 8088)<br/>Session Cookie + CSRF"))
    end

    subgraph WebServerTier["Application & Database Host Node (XAMPP / PHP 8.2 Server)"]
        PHPServer["Execution Env: Apache / PHP 8.2.12 Runtime<br/>Artifacts: index.php, login.php, logout.php, auth_check.php<br/>Artifacts: admin/*.php (Dashboard, Attendees, Allocate, Drivers, Logistics, Reports, Support)<br/>Artifacts: portal.php, support.php & driver_portal.php<br/>Artifacts: classes/*.php (9 OOP Domain Classes)<br/>Driver: PHP PDO + pdo_sqlite Extension"]

        SQLiteDB[("SQLite 3 Relational Database<br/>(data/hajj_umrah.sqlite)<br/>PRAGMA foreign_keys = ON<br/>Tables: USERS, DRIVERS, BUSES, TENTS,<br/>ATTENDEES, ITINERARIES, SUPPORT_TICKETS<br/>Constraints: PK, FK, UNIQUE, CHECK, ACID Tx")]
    end

    AdminPC <--> HTTP
    DriverPhone <--> HTTP
    PilgrimPhone <--> HTTP
    HTTP <--> PHPServer
    PHPServer <-->|"PDO Bound SQL & ACID Tx"| SQLiteDB
```

## Physical Node Specifications (Verified on Project Workstation)
1. **Client Nodes:** Desktop browsers (Admin operations dashboard, driver/bus controls, manifest printing) and mobile browsers (Bus Driver schedule portal and Pilgrim read-only pass portal in Mina/Arafat). Zero client installation required.
2. **Application Server Node:** Host running **XAMPP (Apache / PHP 8.2.12 x64)** with server-side PHP sessions, role-based access guards (`admin`, `driver`, `attendee`), and `pdo_sqlite`.
3. **Relational Storage Tier:** Embedded **SQLite 3** relational database (`data/hajj_umrah.sqlite`) initialized from `sql/schema.sql` and `sql/seed.sql` with `PRAGMA foreign_keys = ON`, enforcing all 7 normalized 3NF tables, `UNIQUE` and `CHECK` constraints, and ACID transactions.
4. **Security Controls:** Prepared statements with named bound parameters (`PDOStatement::bindValue`) on 100% of SQL queries, `bcrypt` (`password_hash`) credential storage, session ID regeneration on login, and CSRF token verification on all POST actions.
