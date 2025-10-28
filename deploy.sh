#!/bin/bash

PLUGIN_SLUG="modulux-shipping-helper"
VERSION="1.0.0"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

# Remove existing zip
rm -f $ZIP_NAME

# Create zip, excluding dev files
zip -r $ZIP_NAME $PLUGIN_SLUG \
    -x "*.git*" \
    -x "*node_modules*" \
    -x "*.DS_Store" \
    -x "*README.md" \
    -x "deploy.sh" \
    -x "assets/*.png" \
    -x "assets/*.jpg" \
    -x "assets/*.gif" \

echo "✅ Plugin zipped as $ZIP_NAME"
