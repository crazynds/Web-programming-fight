#!/bin/bash

ulimit -s 65532
/var/config/exec "$@"