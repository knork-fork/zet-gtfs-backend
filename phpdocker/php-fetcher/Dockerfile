FROM phpdockerio/php:8.4-fpm
WORKDIR "/application"

# Install Python and venv
RUN sed -i 's|http://archive.ubuntu.com/ubuntu/|http://mirror.eu.oneandone.net/ubuntu/|g' /etc/apt/sources.list \
    && apt-get update \
    && apt-get install -y python3 python3-pip python3-venv git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

# Create virtualenv and install GTFS bindings
RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --upgrade pip \
    && /opt/venv/bin/pip install \
        gtfs-realtime-bindings \
        requests


ENV PATH="/opt/venv/bin:$PATH"
CMD ["php-fpm8.4"]