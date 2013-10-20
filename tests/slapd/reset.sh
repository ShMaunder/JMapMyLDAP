#!/bin/bash

SRCDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

$SRCDIR/purge.sh
$SRCDIR/install.sh
