FROM bitnami/moodle:4.3

# Need root at build-time to install deps
USER root

# Build deps for pecl; install Xdebug; then clean up to keep image slim
RUN install_packages autoconf make gcc g++ pkg-config \
  && /opt/bitnami/php/bin/pecl install xdebug \
  && { \
  echo "zend_extension=xdebug"; \
  echo "xdebug.mode=debug,develop"; \
  echo "xdebug.start_with_request=yes"; \
  echo "xdebug.client_host=ifthenpay-dev"; \
  echo "xdebug.client_port=9003"; \
  echo "xdebug.discover_client_host=0"; \
  echo "xdebug.log=/tmp/xdebug.log"; \
  } > /opt/bitnami/php/etc/conf.d/99-xdebug.ini \
  && apt-get purge -y autoconf make gcc g++ pkg-config \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/*

# Back to Bitnami's non-root runtime user
USER 1001
