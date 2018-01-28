#!/usr/bin/env bash

# Hack to enable PHP assertions in Varying Vagrant Vagrants.

if [ ! -d "/vagrant" ]; then
  # Try to only run this in vagrant boxes.
  exit
fi

find /etc/php -type f -name *.ini -print0 | xargs -0 sed -i 's/zend.assertions = -1/zend.assertions = 1/g'
