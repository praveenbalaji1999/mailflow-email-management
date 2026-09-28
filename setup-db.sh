#!/usr/bin/env bash
# Run with: sudo bash setup-db.sh
set -euo pipefail

DB_NAME="${DB_NAME:-email_management}"
DB_USER="${DB_USER:-mailflow}"
DB_PASS="${DB_PASS:-mailflow123}"
ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"

echo "==> Creating database and user..."
mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'%';
FLUSH PRIVILEGES;
SQL

echo "==> Importing schema..."
mysql -u root "${DB_NAME}" < "${ROOT_DIR}/database/schema.sql"

echo "==> Setting admin password (admin@mailflow.local / Admin@123)..."
HASH="$(php -r 'echo password_hash("Admin@123", PASSWORD_DEFAULT);')"
mysql -u root "${DB_NAME}" -e "INSERT INTO admins (name, email, password) VALUES ('Admin User', 'admin@mailflow.local', '${HASH}') ON DUPLICATE KEY UPDATE password=VALUES(password), name=VALUES(name);"

echo "==> Seeding sample recipients if empty..."
COUNT="$(mysql -N -u root "${DB_NAME}" -e 'SELECT COUNT(*) FROM recipients')"
if [[ "${COUNT}" == "0" ]]; then
  mysql -u root "${DB_NAME}" <<'SQL'
INSERT INTO recipients (name, email, status) VALUES
('John Mitchell','john.mitchell@gmail.com','active'),
('Kumar Sharma','kumar.sharma@company.com','active'),
('Priya Patel','priya.patel@gmail.com','active'),
('Sarah Chen','sarah.chen@hr.company.com','active'),
('Marcus Webb','marcus.webb@sales.company.com','inactive'),
('Aisha Rahman','aisha.rahman@gmail.com','active'),
('David Okonkwo','david.okonkwo@outlook.com','active'),
('Elena Rossi','elena.rossi@premium.io','active');
INSERT IGNORE INTO group_members (group_id, recipient_id)
SELECT g.id, r.id FROM recipients r
JOIN `groups` g ON (
  (r.email='john.mitchell@gmail.com' AND g.name='All Customers') OR
  (r.email='kumar.sharma@company.com' AND g.name='Employees') OR
  (r.email='priya.patel@gmail.com' AND g.name='Premium Customers') OR
  (r.email='sarah.chen@hr.company.com' AND g.name='HR Team') OR
  (r.email='marcus.webb@sales.company.com' AND g.name='Sales Team') OR
  (r.email='aisha.rahman@gmail.com' AND g.name='Marketing') OR
  (r.email='david.okonkwo@outlook.com' AND g.name='All Customers') OR
  (r.email='elena.rossi@premium.io' AND g.name='Premium Customers')
);
SQL
fi

cat > "${ROOT_DIR}/.env" <<EOF
APP_NAME=MailFlow
APP_URL=http://127.0.0.1:8080
APP_DEBUG=true

DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}

SESSION_NAME=mailflow_session

QUEUE_BATCH_SIZE=20
QUEUE_MAX_ATTEMPTS=3
EOF

echo "==> Done."
echo "Start the app:  php -S 127.0.0.1:8080 -t ${ROOT_DIR}"
echo "Login: admin@mailflow.local / Admin@123"
