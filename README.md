
# DMBuilder

**DMBuilder** is a web-based deck-building and playtest simulation platform designed for the Duel Masters TCG. It eliminates the need for physical card acquisition during initial theory-crafting by providing players with centralized data management, a streamlined user interface, and an interactive virtual sandbox to validate deck consistency and strategic performance in real time.

**Live Demo:** [https://gict.xsrv.jp/rk_web/deckbuilder](https://gict.xsrv.jp/rk_web/deckbuilder)

---

## Core Features

* **Centralized Card Database:** Fast, asynchronous client-side card rendering, mapping, and deep filtering across a comprehensive card dataset.
* **Interactive Deck Builder:** Features an intuitive interface with dynamic filtering to streamline deck compilation, validation, and metadata management.
* **Virtual Playtest Simulator:** An interactive sandbox environment allowing users to draw opening hands, simulate card placement, and map out early-game turns to test deck velocity and resource curves.
* **Persistent User Ecosystem:** Secure user authentication pipelines paired with a centralized dashboard to save, edit, categorize, and delete multiple deck profiles.

---

## Tech Stack

| Layer | Technology | Implementation Details |
| :--- | :--- | :--- |
| **Frontend** | HTML5, CSS3, JavaScript (ES6+) | Vanilla DOM manipulation, dynamic card rendering, UI event handling, and interactive sandbox states. |
| **Backend** | PHP 8.x (Vanilla / PDO) | Secure session state tracking, stateless RESTful API routing, and backend validation layer. |
| **Database** | MySQL | Persistent storage engine utilizing optimized relational schemas for user account indexing and deck data serialization. |
| **Data Layer** | JSON | Optimized client-side card catalog payload used for instantaneous memory-cached lookups, filtering, and queries. |
| **Dev Environment** | XAMPP | Local environment setup utilizing Apache web server modules and MariaDB compilation. |

---

## Architectural Deep Dive & CRUD Logic

The platform architecture relies on a traditional client-server model communicating via structured endpoints. The data tier utilizes a hybrid approach—offloading structural static definitions to lightweight JSON data blocks while persisting dynamic application data within a relational MySQL database.


```

+-------------------------------------------------------+
|                     Client Browser                    |
|  [UI Layout] <--> [JS Memory Layer (cards.json Cache)]|
+-------------------------------------------------------+
^                                         ^
| HTTP Requests                           | AJAX (JSON)
v                                         v
+-------------------------+             +-----------------------+
|  PHP Authentication &   |             |   save_deck.php API   |
|   Session Layer (PDO)   |             |  (De/Serialization)   |
+-------------------------+             +-----------------------+
^                                         ^
| SQL Queries                             | SQL Queries
v                                         v
+---------------------------------------------------------------+
|                        MySQL Database                         |
|      [users table: id, pass_hash] <--> [decks table]           |
+---------------------------------------------------------------+

```

### Create Operations
* **User Provisioning:** Registration initializes a clean database transaction inserting new entity rows into the `users` table. Raw string passwords undergo asymmetric cryptographic protection via native `password_hash()` execution using `PASSWORD_DEFAULT`.
* **Deck Serialization:** When a user initializes and saves a new deck config, the engine inserts a record into the `decks` table. To allow fluid, arbitrary card combinations without heavy table indexing overhead, individual card IDs and quantities are bound, serialized into a structured JSON string, and saved to a long-form text column.

### Read Operations
* **Client-Side In-Memory Cache:** The complete card dictionary (`cards.json`) is fetched once asynchronously via the client browser on entry. This prevents continuous network round-trips by running fast search regex operations directly in memory.
* **Relational Fetching:** Upon entry to `index.php`, the backend reads individual tracking variables within `$_SESSION['user_id']` and fires an optimized `SELECT` query utilizing a parametric `WHERE` clause targeting the `decks` table to assemble the dashboard list.

### Update Operations
* **Payload Overwriting:** Modifying an existing stack pulls structural data back into the `builder.php` module. Committing edits fires an updated payload toward `save_deck.php`, executing an explicit `UPDATE` query targeting the persistent entity matching the primary `deck_id`.
* **Credential Updates:** Handled strictly through an isolated account routing script (`account.php`), updating user attributes such as user signatures or fresh password hashes securely.

### Delete Operations
* **Entity Pruning:** Selecting deck teardown triggers an absolute `DELETE FROM` execution mapping strictly to the target record's `deck_id`, maintaining lightweight indexes and automated storage cleanup.

---

## Local Setup & Installation

Follow these instructions to configure and run the project locally using a standard XAMPP stack.

### Prerequisites
* **PHP:** Version 8.0 or higher.
* **Database:** MySQL / MariaDB engine.
* **Web Server:** Apache or Nginx configuration.

### Deployment Instructions

1. **Clone the Repository**
   Move into your local server root path (e.g., `XAMPP/htdocs/`) and clone the codebase:
   ```bash
   git clone [https://github.com/yourusername/DMBuilder.git](https://github.com/yourusername/DMBuilder.git)
   cd DMBuilder

```

2. **Database Initialization**
* Access your database administration panel (e.g., `phpMyAdmin`).
* Generate an empty schema named `dmbuilder`.
* Open the database management workspace and run the initial setup script:


```sql
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS decks (
    deck_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    deck_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    cover_image VARCHAR(255),
    card_list TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

```


3. **Configure Environment Connection**
Modify the database initialization parameters inside your database configuration file (e.g., `db_connect.php` or header blocks) to match your local runtime environment credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'dmbuilder');
define('DB_USER', 'root');
define('DB_PASS', ''); // Modify to match your root user config

```


---

## Technical Refactoring Roadmap

* **Migration to MVC Framework (Laravel):** Plans are underway to rewrite the vanilla PHP procedural script architecture into an object-oriented MVC pattern with the Laravel framework. This update will decouple business logic from the view layout layers, integrate Eloquent ORM to handle relational data transactions safely, and secure routing via native middleware and CSRF token validations.
* **Component-Driven Frontend Overhaul:** Decoupling structural components from standard DOM query models by moving toward a modern single-page-app view model (such as Vue.js or React). This shift will isolate the simulation sandbox and collection matrices into independent state containers, improving state synchronization and rendering updates.
* **Sophisticated Game Engine Simulation:** Expanding simulation tracking modules to introduce object-oriented game state tracking. This includes tracking turn boundaries, executing condition triggers, and monitoring zone state transformations (e.g., card movements between Mana Zone, Shield Zone, and Graveyard).

---

## Contact

* **Maintainer:** Ryo Koga
* **Email:** ryokoga2004@gmail.com
* **Project Repository:** [https://github.com/helloreese1217/Duel-Masters-Deck-Builder](https://www.google.com/search?q=https://github.com/helloreese1217/Duel-Masters-Deck-Builder)

```

```
