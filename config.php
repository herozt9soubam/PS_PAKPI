<?php
// Offline SQLite configuration.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Kolkata');

define('DB_PATH', __DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'pspakpi.sqlite');

function getConnection() {
    if (!extension_loaded('pdo_sqlite')) {
        die('SQLite support is not enabled. Enable the PHP pdo_sqlite extension before running this offline build.');
    }

    $dataDir = dirname(DB_PATH);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }

    try {
        $conn = new PDO('sqlite:' . DB_PATH);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $conn->exec('PRAGMA foreign_keys = ON');
        initializeOfflineDatabase($conn);
        return $conn;
    } catch (PDOException $e) {
        die('Database Connection Failed: ' . $e->getMessage());
    }
}

function initializeOfflineDatabase(PDO $conn) {
    static $initialized = false;
    if ($initialized) {
        return;
    }
    $initialized = true;

    $schema = [
        "CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            full_name TEXT,
            status TEXT NOT NULL DEFAULT 'active',
            last_login TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS clients (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id TEXT UNIQUE,
            username TEXT UNIQUE,
            password TEXT,
            full_name TEXT,
            email TEXT UNIQUE,
            phone TEXT,
            address TEXT,
            company_name TEXT,
            registration_date TEXT,
            status TEXT DEFAULT 'pending',
            created_by INTEGER,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            description TEXT,
            detailed_description TEXT,
            price_range TEXT,
            duration TEXT,
            is_featured INTEGER NOT NULL DEFAULT 0,
            features TEXT,
            image_url TEXT,
            is_visible_to_clients INTEGER NOT NULL DEFAULT 1,
            show_price_to_clients INTEGER NOT NULL DEFAULT 1,
            show_description_to_clients INTEGER NOT NULL DEFAULT 1,
            allow_client_quotes INTEGER NOT NULL DEFAULT 1,
            requires_admin_approval INTEGER NOT NULL DEFAULT 0,
            admin_notes TEXT,
            meta_title TEXT,
            meta_description TEXT,
            meta_keywords TEXT,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS service_quotes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            service_id INTEGER NOT NULL,
            quote_title TEXT NOT NULL,
            quote_description TEXT,
            price_type TEXT DEFAULT 'fixed',
            min_price REAL,
            max_price REAL,
            fixed_price REAL,
            duration_type TEXT DEFAULT 'fixed_range',
            min_duration INTEGER,
            max_duration INTEGER,
            inclusions TEXT,
            exclusions TEXT,
            prerequisites TEXT,
            documents_required TEXT,
            has_discount INTEGER NOT NULL DEFAULT 0,
            discount_percentage REAL,
            discount_description TEXT,
            is_featured_quote INTEGER NOT NULL DEFAULT 0,
            is_active INTEGER NOT NULL DEFAULT 1,
            is_visible_to_clients INTEGER NOT NULL DEFAULT 1,
            created_by INTEGER,
            quote_category TEXT,
            quote_code TEXT,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS service_inquiries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            phone TEXT,
            message TEXT,
            service_id INTEGER,
            service_name TEXT,
            quote_id INTEGER,
            organization TEXT,
            country TEXT,
            region TEXT,
            industry TEXT,
            status TEXT NOT NULL DEFAULT 'new',
            admin_notes TEXT,
            follow_up_date TEXT,
            created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )",
        "CREATE TABLE IF NOT EXISTS custom_fields (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            field_name TEXT NOT NULL,
            field_type TEXT NOT NULL DEFAULT 'text',
            field_options TEXT,
            required INTEGER NOT NULL DEFAULT 0,
            sort_order INTEGER NOT NULL DEFAULT 0
        )",
        "CREATE TABLE IF NOT EXISTS quote_custom_values (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            quote_id INTEGER NOT NULL,
            field_id INTEGER NOT NULL,
            field_value TEXT,
            FOREIGN KEY (quote_id) REFERENCES service_quotes(id) ON DELETE CASCADE,
            FOREIGN KEY (field_id) REFERENCES custom_fields(id) ON DELETE CASCADE
        )",
        "CREATE TABLE IF NOT EXISTS field_visibility (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            field_key TEXT NOT NULL UNIQUE,
            field_label TEXT NOT NULL,
            section TEXT NOT NULL,
            is_visible INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0
        )",
        "CREATE TABLE IF NOT EXISTS countries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            code TEXT,
            region TEXT
        )",
        "CREATE TABLE IF NOT EXISTS industries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        )"
    ];

    foreach ($schema as $sql) {
        $conn->exec($sql);
    }

    migrateOfflineDatabase($conn);
    seedOfflineDatabase($conn);
}

function migrateOfflineDatabase(PDO $conn) {
    ensureTableColumns($conn, 'services', [
        'is_visible_to_clients' => 'INTEGER NOT NULL DEFAULT 1',
        'show_price_to_clients' => 'INTEGER NOT NULL DEFAULT 1',
        'show_description_to_clients' => 'INTEGER NOT NULL DEFAULT 1',
        'allow_client_quotes' => 'INTEGER NOT NULL DEFAULT 1',
        'requires_admin_approval' => 'INTEGER NOT NULL DEFAULT 0',
        'admin_notes' => 'TEXT',
        'meta_title' => 'TEXT',
        'meta_description' => 'TEXT',
        'meta_keywords' => 'TEXT',
        'sort_order' => 'INTEGER NOT NULL DEFAULT 0',
        'updated_at' => 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ]);

    ensureTableColumns($conn, 'service_quotes', [
        'is_active' => 'INTEGER NOT NULL DEFAULT 1',
        'is_visible_to_clients' => 'INTEGER NOT NULL DEFAULT 1',
        'quote_category' => 'TEXT',
        'quote_code' => 'TEXT',
        'sort_order' => 'INTEGER NOT NULL DEFAULT 0',
        'updated_at' => 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP',
    ]);

    $conn->exec("
        UPDATE services
        SET
            is_visible_to_clients = COALESCE(is_visible_to_clients, 1),
            show_price_to_clients = COALESCE(show_price_to_clients, 1),
            show_description_to_clients = COALESCE(show_description_to_clients, 1),
            allow_client_quotes = COALESCE(allow_client_quotes, 1),
            requires_admin_approval = COALESCE(requires_admin_approval, 0),
            sort_order = COALESCE(sort_order, 0)
    ");

    $conn->exec("
        UPDATE service_quotes
        SET
            is_active = COALESCE(is_active, 1),
            is_visible_to_clients = COALESCE(is_visible_to_clients, 1),
            sort_order = COALESCE(sort_order, 0)
    ");
}

function ensureTableColumns(PDO $conn, string $table, array $columns) {
    $existing = [];
    $stmt = $conn->query("PRAGMA table_info({$table})");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $column) {
        $existing[$column['name']] = true;
    }

    foreach ($columns as $name => $definition) {
        if (!isset($existing[$name])) {
            $conn->exec("ALTER TABLE {$table} ADD COLUMN {$name} {$definition}");
        }
    }
}

function seedOfflineDatabase(PDO $conn) {
    if ((int)$conn->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
        $stmt = $conn->prepare("INSERT INTO admins (username, password, full_name, status) VALUES (?, ?, ?, 'active')");
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Offline Admin']);
    }

    ensureCoreServices($conn);

    if ((int)$conn->query('SELECT COUNT(*) FROM field_visibility')->fetchColumn() === 0) {
        $stmt = $conn->prepare("INSERT INTO field_visibility (field_key, field_label, section, is_visible, sort_order) VALUES (?, ?, ?, 1, ?)");
        $fields = [
            ['service_title', 'Service Title', 'service', 10],
            ['service_description', 'Service Description', 'service', 20],
            ['service_price', 'Service Price', 'service', 30],
            ['service_duration', 'Service Duration', 'service', 40],
            ['quote_price', 'Quote Price', 'quote', 10],
            ['quote_duration', 'Quote Duration', 'quote', 20],
            ['quote_inclusions', 'Quote Inclusions', 'quote', 30],
            ['inquiry_receive_info', 'Receive Info Opt-in', 'inquiry', 10],
        ];
        foreach ($fields as $field) {
            $stmt->execute($field);
        }
    }

    if ((int)$conn->query('SELECT COUNT(*) FROM countries')->fetchColumn() === 0) {
        $stmt = $conn->prepare('INSERT INTO countries (name, code, region) VALUES (?, ?, ?)');
        foreach ([
            ['India', 'IN', 'Asia Pacific'],
            ['United States', 'US', 'North America'],
            ['United Kingdom', 'GB', 'Europe'],
            ['United Arab Emirates', 'AE', 'Middle East'],
            ['Australia', 'AU', 'Australia/New Zealand'],
        ] as $country) {
            $stmt->execute($country);
        }
    }

    if ((int)$conn->query('SELECT COUNT(*) FROM industries')->fetchColumn() === 0) {
        $stmt = $conn->prepare('INSERT INTO industries (name) VALUES (?)');
        foreach (['Construction', 'Retail', 'Manufacturing', 'Education', 'Healthcare', 'Public Services', 'Other'] as $industry) {
            $stmt->execute([$industry]);
        }
    }
}

function ensureCoreServices(PDO $conn) {
    if ((int)$conn->query('SELECT COUNT(*) FROM services')->fetchColumn() > 0) {
        return;
    }

    $stmt = $conn->prepare("INSERT INTO services
        (title, description, detailed_description, price_range, duration, is_featured, features, image_url, sort_order)
        SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?
        WHERE NOT EXISTS (
            SELECT 1 FROM services
            WHERE lower(title) = lower(?)
               OR (lower(?) = 'supply works' AND lower(title) IN ('supply', 'supply works'))
               OR (lower(?) = 'construction works' AND lower(title) IN ('construction', 'construction works'))
        )");

    $services = [
        ['Bakery', 'Bakery products and related services.', 'Manage bakery inquiries, custom orders and quotations offline.', 'Contact for pricing', '1-4 weeks', 1, '["Fresh products","Custom orders","Event supply"]', 'Bakery.jpg', 10],
        ['Construction', 'Construction works, support and project services.', 'Capture construction work inquiries, site requirements and quote requests.', 'Contact for pricing', '1-6 months', 1, '["Planning","Execution support","Site coordination"]', 'construction.jpg', 20],
        ['Consultancy', 'Business and professional consultancy.', 'Collect consultancy requirements, documentation needs and follow up locally.', 'Contact for pricing', '1-12 weeks', 1, '["Advisory","Documentation","Follow-up"]', 'Consultancy.jpg', 30],
        ['Supply Works', 'General supply, procurement and delivery services.', 'Track supply work requests, procurement details and quotations offline.', 'Contact for pricing', '1-8 weeks', 1, '["Procurement","Delivery coordination","Bulk supply"]', 'supply.jpg', 40],
        ['Many More', 'Custom work across education, healthcare, public services and other sectors.', 'Capture any other service requirement that does not fit the standard categories.', 'Contact for pricing', 'Flexible', 0, '["Custom work","Multi-industry support","Admin follow-up"]', 'supply.jpg', 50],
    ];

    foreach ($services as $service) {
        $stmt->execute([
            $service[0], $service[1], $service[2], $service[3], $service[4],
            $service[5], $service[6], $service[7], $service[8],
            $service[0], $service[0], $service[0]
        ]);
    }
}

function generateToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
