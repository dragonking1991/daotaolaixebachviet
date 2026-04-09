#!/bin/zsh
set -euo pipefail
mkdir -p "$HOME/Library/Logs"
exec /Users/trungthanh.nguyen/homebrew/bin/cloudflared tunnel --url http://localhost:8080 --no-autoupdate >> "$HOME/Library/Logs/bachviet-quicktunnel.log" 2>&1
