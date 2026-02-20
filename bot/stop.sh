#!/bin/sh
if command -v killall >/dev/null 2>&1; then
    killall -9 python 2>/dev/null
    exit 0
fi
if command -v pkill >/dev/null 2>&1; then
    pkill -9 -f "bot.main" 2>/dev/null
    exit 0
fi
for pid in /proc/[0-9]*; do
    [ -f "$pid/cmdline" ] || continue
    if grep -q "bot.main" "$pid/cmdline" 2>/dev/null; then
        kill -9 "${pid##*/}" 2>/dev/null
    fi
done
exit 0