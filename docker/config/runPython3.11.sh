#!/bin/bash

# Caminho alternativo do Python
PYTHON_ALT="/langs/python3.11/bin/python"

# Verifica se o comando python3.11 existe no PATH
if command -v python3.11 >/dev/null 2>&1; then
    python3.11 -u /var/config/exec "$@"
elif [ -x "$PYTHON_ALT" ]; then
    "$PYTHON_ALT" -u /var/config/exec "$@"
else
    exit 1
fi

