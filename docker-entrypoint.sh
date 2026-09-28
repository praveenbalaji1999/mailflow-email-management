#!/bin/bash
set -e

echo "⏳ Waiting for MySQL to be ready..."
max_tries=30
count=0
until php -r "
  try {
    \$pdo = new PDO(
      'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';charset=utf8mb4',
      getenv('DB_USER'),
      getenv('DB_PASS')
    );
    exit(0);
  } catch (Exception \$e) {
    exit(1);
  }
" 2>/dev/null; do
  count=$((count + 1))
  if [ "$count" -ge "$max_tries" ]; then
    echo "❌ MySQL did not become ready in time. Exiting."
    exit 1
  fi
  echo "  ... still waiting ($count/$max_tries)"
  sleep 2
done

echo "✅ MySQL is ready."

# Import schema only if the admins table doesn't exist yet
echo "🔍 Checking if schema needs to be imported..."
TABLE_EXISTS=$(php -r "
  try {
    \$pdo = new PDO(
      'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4',
      getenv('DB_USER'),
      getenv('DB_PASS')
    );
    \$res = \$pdo->query(\"SHOW TABLES LIKE 'admins'\");
    echo \$res->rowCount() > 0 ? '1' : '0';
  } catch (Exception \$e) {
    echo '0';
  }
" 2>/dev/null || echo "0")

if [ "$TABLE_EXISTS" = "0" ]; then
  echo "📦 Importing database schema..."
  php -r "
    \$pdo = new PDO(
      'mysql:host=' . getenv('DB_HOST') . ';port=' . getenv('DB_PORT') . ';charset=utf8mb4',
      getenv('DB_USER'),
      getenv('DB_PASS'),
      [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    \$sql = file_get_contents('/var/www/html/database/schema.sql');
    // Split on statement delimiter and execute one by one
    \$statements = array_filter(array_map('trim', explode(';', \$sql)));
    foreach (\$statements as \$stmt) {
      if (\$stmt !== '') {
        \$pdo->exec(\$stmt);
      }
    }
    echo 'Schema imported successfully.' . PHP_EOL;
  "
  echo "✅ Schema imported."
else
  echo "✅ Schema already exists, skipping import."
fi

echo "🚀 Starting Apache..."
exec "$@"
