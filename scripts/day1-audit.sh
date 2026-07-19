#!/usr/bin/env bash
set -uo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

failures=0
warnings=0

run_check() {
  local label="$1"
  shift

  echo
  echo "============================================================"
  echo "$label"
  echo "============================================================"

  if "$@"; then
    echo "[OK] $label"
  else
    echo "[ECHEC] $label"
    failures=$((failures + 1))
  fi
}

command_exists() {
  command -v "$1" >/dev/null 2>&1
}

echo "Audit AutoFacture - $(date -u +'%Y-%m-%dT%H:%M:%SZ')"
echo "Dossier : $ROOT_DIR"

if command_exists php; then
  run_check "Version PHP" php -v
else
  echo "[AVERTISSEMENT] PHP absent de la machine hôte. Utilisez Docker Compose."
  warnings=$((warnings + 1))
fi

if command_exists composer; then
  run_check "Validation composer.json" composer validate --strict --no-check-publish
  run_check "Audit de sécurité Composer" composer audit
else
  echo "[AVERTISSEMENT] Composer absent de la machine hôte."
  warnings=$((warnings + 1))
fi

if command_exists node; then
  run_check "Version Node.js" node --version
else
  echo "[AVERTISSEMENT] Node.js absent de la machine hôte."
  warnings=$((warnings + 1))
fi

if command_exists npm; then
  run_check "Version npm" npm --version
  if [[ -f package-lock.json ]]; then
    run_check "Audit de sécurité NPM" npm audit --omit=dev
  else
    echo "[AVERTISSEMENT] package-lock.json absent : npm audit non exécuté."
    warnings=$((warnings + 1))
  fi
else
  echo "[AVERTISSEMENT] npm absent de la machine hôte."
  warnings=$((warnings + 1))
fi

if command_exists docker; then
  run_check "Validation Docker Compose" docker compose config --quiet
else
  echo "[AVERTISSEMENT] Docker absent."
  warnings=$((warnings + 1))
fi

echo
echo "============================================================"
echo "Résumé"
echo "============================================================"
echo "Échecs : $failures"
echo "Avertissements : $warnings"

if (( failures > 0 )); then
  exit 1
fi

exit 0
