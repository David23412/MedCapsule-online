#!/usr/bin/env bash

set -euo pipefail

STATE="${1:-status}"
ENV_FILE="${2:-.env}"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing env file: $ENV_FILE" >&2
  exit 1
fi

read_current_state() {
  local current
  current="$(grep -E '^DESIGN_PROTECTION_ENABLED=' "$ENV_FILE" | tail -n 1 | cut -d '=' -f 2- || true)"

  if [[ -z "$current" ]]; then
    echo "true"
  else
    echo "$current"
  fi
}

write_state() {
  local desired="$1"
  local tmp_file
  tmp_file="$(mktemp)"

  awk -v line="DESIGN_PROTECTION_ENABLED=${desired}" '
    BEGIN { updated = 0 }
    /^DESIGN_PROTECTION_ENABLED=/ {
      if (updated == 0) {
        print line
        updated = 1
      }
      next
    }
    { print }
    END {
      if (updated == 0) {
        print line
      }
    }
  ' "$ENV_FILE" > "$tmp_file"

  mv "$tmp_file" "$ENV_FILE"
  echo "Design protection ${desired} in ${ENV_FILE}"
}

case "$STATE" in
  on|enable|enabled|true|1)
    write_state "true"
    ;;
  off|disable|disabled|false|0)
    write_state "false"
    ;;
  status)
    echo "Design protection is $(read_current_state)"
    ;;
  *)
    echo "Usage: ./scripts/design-protection.sh [on|off|status] [env-file]" >&2
    exit 2
    ;;
esac
