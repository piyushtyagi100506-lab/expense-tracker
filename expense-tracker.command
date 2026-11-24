#!/bin/bash

set -eu

export PATH="/opt/homebrew/bin:/opt/homebrew/sbin:/usr/local/bin:/usr/local/sbin:/usr/bin:/bin:/usr/sbin:/sbin"

PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"
DB_USER="${EXPENSE_TRACKER_DB_USER:-$(id -un)}"
export EXPENSE_TRACKER_DB_USER="$DB_USER"
export EXPENSE_TRACKER_DB_PASS="${EXPENSE_TRACKER_DB_PASS:-}"

fail() {
  printf '%s\n' "$1" >&2
  /usr/bin/osascript \
    -e 'on run argv' \
    -e 'display alert "Expense Tracker could not start" message (item 1 of argv) as critical' \
    -e 'end run' "$1" >/dev/null 2>&1 || true
  exit 1
}

for tool in brew mariadb php curl lsof open; do
  command -v "$tool" >/dev/null 2>&1 || fail "Required command '$tool' is missing. Install Homebrew, MariaDB, and PHP first."
done

# Start MariaDB and wait for it to accept local connections.
if ! mariadb -u "$DB_USER" -e "SELECT 1;" >/dev/null 2>&1; then
  brew services start mariadb >/dev/null 2>&1 || true
  for _ in $(seq 1 30); do
    mariadb -u "$DB_USER" -e "SELECT 1;" >/dev/null 2>&1 && break
    sleep 1
  done
fi

mariadb -u "$DB_USER" -e "SELECT 1;" >/dev/null 2>&1 ||
  fail "MariaDB did not start or the local database user could not connect."

# Import the demo schema and data only when the database has not been created.
if ! mariadb -u "$DB_USER" -e "USE expense_tracker;" >/dev/null 2>&1; then
  mariadb -u "$DB_USER" < "$PROJECT_DIR/database.sql" ||
    fail "Could not initialize the expense_tracker database from database.sql."
fi

# Reuse the app if already running; otherwise choose an available local port.
PORT=""
for candidate in $(seq 8000 8010); do
  if lsof -n -iTCP:"$candidate" -sTCP:LISTEN >/dev/null 2>&1; then
    page="$(curl -fsSL --max-time 3 "http://127.0.0.1:${candidate}/auth/login.php" 2>/dev/null || true)"
    if printf '%s' "$page" | grep -q "ExpenseTracker"; then
      PORT="$candidate"
      break
    fi
  else
    PORT="$candidate"
    nohup php -S "127.0.0.1:${PORT}" -t "$PROJECT_DIR" \
      > "$PROJECT_DIR/.expense_tracker_server.log" 2>&1 </dev/null &
    break
  fi
done

[ -n "$PORT" ] || fail "Ports 8000 through 8010 are busy. Close another local server and try again."

URL="http://127.0.0.1:${PORT}/auth/login.php"
for _ in $(seq 1 30); do
  page="$(curl -fsSL --max-time 3 "$URL" 2>/dev/null || true)"
  printf '%s' "$page" | grep -q "ExpenseTracker" && break
  sleep 1
done

printf '%s' "$page" | grep -q "ExpenseTracker" ||
  fail "The PHP server did not become ready. See .expense_tracker_server.log in the project folder."

open "$URL" || fail "The app is running at $URL, but the browser could not be opened."
