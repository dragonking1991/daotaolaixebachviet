#!/bin/zsh
set -euo pipefail
LOG_FILE="$HOME/Library/Logs/bachviet-quicktunnel.log"
if [[ ! -f "$LOG_FILE" ]]; then
  echo "Log not found: $LOG_FILE"
  exit 1
fi
url=$(grep -Eo 'https://[-a-z0-9]+\.trycloudflare\.com' "$LOG_FILE" | tail -1)
if [[ -z "$url" ]]; then
  echo "No tunnel URL found yet."
  exit 1
fi
echo "$url"
