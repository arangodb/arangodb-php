
ARCH=$(arch)
if [ "$ARCH" == "x86_64" ]; then
  ARANGOD="arangod_x86_64"
else
  ARANGOD="arangod"
fi
echo "killing arangod binary ${ARANGOD}"
killall -9 "${ARANGOD}" || true
