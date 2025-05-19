#!/bin/bash

# Caminho alternativo do Python
PYTHON_ALT="/langs/python3.13/bin/python3.13"

# Verifica se o comando python3.13 existe no PATH
if command -v python3.13 >/dev/null 2>&1; then
    python3.13 -u /var/config/exec "$@"
elif [ -x "$PYTHON_ALT" ]; then
    "$PYTHON_ALT" -u /var/config/exec "$@"
else
    echo "Não foi possível encontrar o executavel Python 3.13."
    exit 1
fi

