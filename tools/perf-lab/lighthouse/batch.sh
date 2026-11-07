#!/usr/bin/env bash
# Lighthouse over several saved pages through the running replay, mobile and desktop.
#
#   SITE=https://www.example.ch bash lighthouse/batch.sh <out-dir> <runs> <path>=<file.html> ...
#
# Writes <out-dir>/<slug>-<mobile|desktop>[-<n>].json (read them with psi/summary.py) and
# prints one line per run. Run it alone: other work on the machine raises Total Blocking Time.
set -uo pipefail
LAB="$(cd "$(dirname "$0")/.." && pwd)"
OUT="${1:?out dir}"; RUNS="${2:?runs}"; shift 2
mkdir -p "$OUT"
for pair in "$@"; do
	path="${pair%%=*}"; file="${pair#*=}"
	slug="$(echo "$path" | sed -E 's#^/##; s#/$##; s#/#_#g')"; [ -z "$slug" ] && slug=home
	for ff in mobile desktop; do
		for n in $(seq 1 "$RUNS"); do
			suffix=""; [ "$RUNS" -gt 1 ] && suffix="-$n"
			# summary.py groups by "-<form factor>.json": the run number goes before it.
			target="$OUT/${slug}${suffix}-${ff}.json"
			res="$(PAGE="$file" timeout 300 node "$LAB/lighthouse/run.mjs" "$path" "$target" "$ff" "${CATS:-performance}" 2>&1 | tail -1)"
			printf '%-40s %-8s %s\n' "$path" "$ff" "$res"
		done
	done
done
