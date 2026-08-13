#!/bin/bash
set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DIST_DIR="$SCRIPT_DIR/dist"

rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR"

echo "Building OpenCart 2.0 plugin..."
cd "$SCRIPT_DIR"
# OC 2.0 Extension Installer expects upload/ root
mkdir -p /tmp/paynkolay-oc20-build/upload
cp -r opencart-2.0/admin /tmp/paynkolay-oc20-build/upload/
cp -r opencart-2.0/catalog /tmp/paynkolay-oc20-build/upload/
cp -r opencart-2.0/system /tmp/paynkolay-oc20-build/upload/
cd /tmp/paynkolay-oc20-build
zip -r "$DIST_DIR/paynkolay-opencart-2.0.ocmod.zip" upload/ -x '*.DS_Store' '*__MACOSX*'
rm -rf /tmp/paynkolay-oc20-build

echo "Building OpenCart 2.3 plugin..."
cd "$SCRIPT_DIR"
# OC 2.3 uses flat structure (admin/, catalog/)
cd opencart-2.3
zip -r "$DIST_DIR/paynkolay-opencart-2.3.ocmod.zip" admin/ catalog/ system/ -x '*.DS_Store' '*__MACOSX*'

echo "Building OpenCart 3.x plugin..."
cd "$SCRIPT_DIR"
# OC 3.x Extension Installer expects upload/ root
mkdir -p /tmp/paynkolay-oc3x-build/upload
cp -r opencart-3.x/admin /tmp/paynkolay-oc3x-build/upload/
cp -r opencart-3.x/catalog /tmp/paynkolay-oc3x-build/upload/
cp -r opencart-3.x/system /tmp/paynkolay-oc3x-build/upload/
cd /tmp/paynkolay-oc3x-build
zip -r "$DIST_DIR/paynkolay-opencart-3.x.ocmod.zip" upload/ -x '*.DS_Store' '*__MACOSX*'
rm -rf /tmp/paynkolay-oc3x-build

echo "Building OpenCart 4.x plugin..."
cd "$SCRIPT_DIR"
# OC 4.x uses flat structure with install.json at root
cd opencart-4.x
zip -r "$DIST_DIR/paynkolay-opencart-4.x.ocmod.zip" install.json admin/ catalog/ system/ -x '*.DS_Store' '*__MACOSX*'

echo ""
echo "Build complete. Output:"
ls -lh "$DIST_DIR"/*.zip
