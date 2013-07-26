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

if [ -f ${DIR}/_build/version.txt ]
then
	echo "Automatic increment of version based on version.txt"
	VER=$(<${DIR}/_build/version.txt)

	POINT=$(echo $VER | awk -F'.' '{print $4}')

	POINT=$((POINT + 1))

	NEW=$(echo $VER | awk -F'.' '{print $1"."$2"."$3".""'"$POINT"'"}')
	echo $NEW > ${DIR}/_build/version.txt
else
	echo "Please specify a version for this package (e.g. 2.0.0.25):"
	read VER
fi

cd $DIR
phing -Dpackage.version=$VER

echo "Finished. Packages can be found in ${DIR}/_build/${VER}/packages."
