#!/bin/bash
# -- JMapMyLDAP extensions build script --
# 1) Run with "bash build.sh"
# 2) Specify a version - for example 2.0.0.25
# 3) Wait till completed
# 4) Joomla packages can be found inside ./_build/2.0.0.25/packages/

# Error trapping - remove if causing problems!
trap 'echo "There was an error - exiting..." && exit' ERR

echo "============================================"
echo "===  JMapMyLDAP extensions build script  ==="
echo "============================================"
echo

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "Please specify a version for this package (e.g. 2.0.0.25):"
read VER

cd $DIR
phing -Dpackage.version=$VER

echo "Finished. Packages can be found in ${DIR}/_build/${VER}/packages."
