#!/bin/bash

# Detect virtual memory limit from ulimit (in KB)
limit_kb=$(ulimit -v)

# If no limit was set, fallback to a default (e.g., 1 GB)
if [ "$limit_kb" = "unlimited" ] || [ -z "$limit_kb" ]; then
  limit_kb=$((1024 * 1024)) # 4 GB
fi

# Convert to MB
limit_mb=$((limit_kb / 1024))

# Reserve 10% for JVM overhead (stack, metaspace, code cache, etc)
heap_mb=$(((limit_mb-900)/2))


/langs/javaOpenJDK24/bin/java \
    -Xms${heap_mb}m \
    -Xmx${heap_mb}m \
    -XX:+UseSerialGC \
    -XX:+TieredCompilation \
    -XX:+ExitOnOutOfMemoryError \
    -Xlog:cds=off \
    -XX:+UseCompressedOops \
    -XX:-UseCompressedClassPointers \
    -Dfile.encoding=UTF-8 \
    -Djava.awt.headless=true \
    -jar /var/config/exec "$@"