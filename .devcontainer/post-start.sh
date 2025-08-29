#!/usr/bin/env bash
set -euo pipefail

# 1) (Optional) convenience: mirror Bitnami path for CLI parity inside dev container
if [ ! -e /bitnami ]; then
  sudo ln -snf /workspace/bitnami /bitnami || true
fi

# 2) Link plugin into Moodle *on the shared volume*.
#    Target points to the neutral mount INSIDE the app container.
#    (The link is valid for the app; it may appear "dangling" inside the dev container.)
PLUGIN_LINK=/workspace/bitnami/moodle/payment/gateway/ifthenpay
sudo mkdir -p /workspace/bitnami/moodle/payment/gateway
sudo ln -snf /opt/dev/ifthenpay "$PLUGIN_LINK"
ls -l "$PLUGIN_LINK" || true

echo "✅ Plugin symlink ready: $PLUGIN_LINK -> /opt/dev/ifthenpay"
