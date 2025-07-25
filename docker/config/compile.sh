#!/bin/bash

LINGUAGEM=$1
ARQUIVO=$2
OUTPUT=$3

if [ ! -f "$ARQUIVO" ]; then
    echo "Erro: Arquivo '$ARQUIVO' não encontrado."
    exit 1
fi

ulimit -v 1073741824

case "$LINGUAGEM" in
    python)
        ;;
    c)
        echo "Compilando código C..."
        #OUTPUT="/var/config/exec"  # Remove a extensão do arquivo
        gcc -std=c17 -mtune=native -lm -static -march=native -w -O2 "$ARQUIVO" -o "$OUTPUT" 2>&1
        if [ $? -eq 0 ]; then
            echo "Compilado com sucesso"
        else
            echo "Erro na compilação do código C."
            exit 1
        fi
        ;;
    c++)
        echo "Compilando código C++..."
        #OUTPUT="/var/config/exec"  # Remove a extensão do arquivo

        if grep -qE '\b(printf|scanf)\b' "$FILE"; then
            echo "➡️  printf ou scanf detectado. Compilando SEM fast_io.h..."
            g++ -flto -std=c++20 -mtune=native -Wreturn-type -static -march=native -w -O2 "$ARQUIVO" -o "$OUTPUT" 2>&1
        else
            echo "✅ Nenhum uso de printf/scanf detectado. Compilando COM fast_io.h..."
            g++ -include /var/config/fast_io.h -flto -std=c++20 -mtune=native -Wreturn-type -static -march=native -w -O2 "$ARQUIVO" -o "$OUTPUT" 2>&1
        fi
        if [ $? -eq 0 ]; then
            echo "Compilado com sucesso"
        else
            echo "Erro na compilação do código C++."
            exit 1
        fi
        ;;
    *)
        echo "Erro: Linguagem não suportada. Use 'python', 'c' ou 'c++'."
        exit 1
        ;;
esac
