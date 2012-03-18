#!/bin/bash
# -- JMapMyLDAP extensions build script --
# 1) Run with "bash build.sh"
# 2) Specify a version - for example 2.0.0.25
# 3) Wait till completed
# 4) Joomla packages can be found inside ./_build/2.0.0.25/public/

# TODO:
# - Allow optional read-only pull from GIT - have to find a way to override build.sh working file

# Updates an XML. Assumed current directory contains file.
# @param $1 file
# @param $2 version
function xmlupdate {
	xmlstarlet ed -P -L -u "/extension/version" --value "$2" $1
	xmlstarlet ed -P -L -u "/extension/author" --value "Shaun Maunder" $1
	xmlstarlet ed -P -L -u "/extension/authorEmail" --value "shaun@shmanic.com" $1
	xmlstarlet ed -P -L -u "/extension/authorUrl" --value "www.shmanic.com" $1
	xmlstarlet ed -P -L -u "/extension/buildDate" --value "$(date)" $1
}

# Compresses specified directory.
# @param $1 working version directory (e.g. /project/1.0.3)
# @param $2 name of plugin (e.g. lib_lalala)
function compress {
	OLDDIR="$(pwd)"
	cd $1
	zip -r "$1/public/$2.zip" $2 >&1
	tar -zcvf "$1/public/$2.tar.gz" $2 >&1
	cd $OLDDIR
}

# Creates a new plugin directory.
# @param $1 working version directory (e.g. /project/1.0.3)
# @param $2 name of plugin (e.g. lib_lalala)
# @param $3 template directory
function newplugin {
	mkdir "$1/$2"
	cd "$1/$2"

	cp "$3/LICENSE.txt" "."
	cp "$3/index.html" "."
}

# Copies over a language for a plugin.
# @param $1 working version directory (e.g. /project/1.0.3)
# @param $2 name of plugin (e.g. lib_lalala)
# @param $3 language trunk directory
# @param $4 language prefix (e.g. en-GB)
function copylang {

	cd "$1/$2"

	mkdir "language"
	mkdir "language/$4"	

	LANGFILE="$3/$4/$4.$2.ini"
	echo $LANGFILE
	if [ -a $LANGFILE ]; then
		cp "$LANGFILE" "language/$4"
	fi

	LANGFILE="$3/$4/$4.$2.sys.ini"
	if [ -a $LANGFILE ]; then
		cp "$LANGFILE" "language/$4"
	fi

}

# Error trapping - remove if causing problems!
trap 'echo "There was an error - exiting..." && exit' ERR

echo "============================================"
echo "===  JMapMyLDAP extensions build script  ==="
echo "============================================"
echo

DIR="$( cd "$( dirname "$0" )" && pwd )"

# Set to the trunk directory (this can be external)
# TRUNK="$DIR/trunk"
TRUNK="$DIR"

echo "Your trunk directory is $TRUNK"

echo "Please specify a version for this package (e.g. 2.0.0.25):"
read VER

WORKDIR="$DIR/_build/$VER/"

# Set to the templates directory (should contain LICENSE.txt and index.html)
TEMPLATE="$DIR/_build"

# If the directory already exists then this version has been built in the past .:. therefore exit
if [ ! -d $WORKDIR ]; then
	mkdir "$WORKDIR"

# Create a public directory for this new version
	mkdir "$WORKDIR/public"


# JLDAP2 Library
	NAME="lib_jldap2"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/libraries/shmanic/client/jldap2.php" "."
	cp "$TRUNK/libraries/shmanic/client/jldap2.xml" "."

	xmlupdate jldap2.xml "$VER"

	copylang "$WORKDIR" "$NAME" "$TRUNK/language" "en-GB"

	compress "$WORKDIR" "$NAME"


# LDAP Core Library
	NAME="lib_ldapcore"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/libraries/shmanic/ldap/event.php" "."
	cp "$TRUNK/libraries/shmanic/ldap/helper.php" "."
	cp "$TRUNK/libraries/shmanic/ldap/core.xml" "."

	xmlupdate core.xml "$VER"

#	copylang "$WORKDIR" "$NAME" "$TRUNK/language" "en-GB"

	compress "$WORKDIR" "$NAME"

# LDAP Logging Library
	NAME="lib_ldaplog"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/libraries/shmanic/log/ldapentry.php" "."
	cp "$TRUNK/libraries/shmanic/log/ldaphelper.php" "."
	cp "$TRUNK/libraries/shmanic/log/ldaplog.xml" "."

	xmlupdate ldaplog.xml "$VER"

#	copylang "$WORKDIR" "$NAME" "$TRUNK/language" "en-GB"

	compress "$WORKDIR" "$NAME"


# LDAP Admin Component
	NAME="com_ldapadmin"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	mkdir controllers
	mkdir help
	mkdir helpers
	mkdir views

	cp "$TRUNK/administrator/components/$NAME/config.xml" "."
	cp "$TRUNK/administrator/components/$NAME/controller.php" "."
	cp "$TRUNK/administrator/components/$NAME/ldapadmin.php" "."
	cp "$TRUNK/administrator/components/$NAME/ldapadmin.xml" "."
	cp -r "$TRUNK/administrator/components/$NAME/controllers/" "./controllers"
	cp -r "$TRUNK/administrator/components/$NAME/help/" "./help"
	cp -r "$TRUNK/administrator/components/$NAME/helpers/" "./helpers"
	cp -r "$TRUNK/administrator/components/$NAME/views/" "./views"

	xmlupdate ldapadmin.xml "$VER"

	copylang "$WORKDIR" "$NAME" "$TRUNK/administrator/language" "en-GB"

	compress "$WORKDIR" "$NAME"


# LDAP Mapping Library
	NAME="lib_ldapmapping"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/libraries/shmanic/ldap/mapping.php" "."
	cp "$TRUNK/libraries/shmanic/ldap/mapping.xml" "."

	xmlupdate mapping.xml "$VER"

	copylang "$WORKDIR" "$NAME" "$TRUNK/language" "en-GB"

	compress "$WORKDIR" "$NAME"


# LDAP Profile Library
	NAME="lib_ldapprofile"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/libraries/shmanic/ldap/profile.php" "."
	cp "$TRUNK/libraries/shmanic/ldap/profile.xml" "."

	xmlupdate profile.xml "$VER"

	copylang "$WORKDIR" "$NAME" "$TRUNK/language" "en-GB"

	compress "$WORKDIR" "$NAME"


# JMapMyLDAP Authentication Plugin
	NAME="plg_authentication_jmapmyldap"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/plugins/authentication/jmapmyldap/jmapmyldap.php" "."
	cp "$TRUNK/plugins/authentication/jmapmyldap/jmapmyldap.xml" "."

	xmlupdate jmapmyldap.xml "$VER"

	copylang "$WORKDIR" "$NAME" "$TRUNK/administrator/language" "en-GB"

	compress "$WORKDIR" "$NAME"


# LDAP Dispatcher System Plugin
	NAME="plg_system_ldapdispatcher"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/plugins/system/ldapdispatcher/ldapdispatcher.php" "."
	cp "$TRUNK/plugins/system/ldapdispatcher/ldapdispatcher.xml" "."

	xmlupdate ldapdispatcher.xml "$VER"

	copylang "$WORKDIR" "$NAME" "$TRUNK/administrator/language" "en-GB"

	compress "$WORKDIR" "$NAME"


# LDAP Mapping Plugin
	NAME="plg_ldap_mapping"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/plugins/ldap/mapping/mapping.php" "."
	cp "$TRUNK/plugins/ldap/mapping/mapping.xml" "."

	xmlupdate mapping.xml "$VER"

	copylang "$WORKDIR" "$NAME" "$TRUNK/administrator/language" "en-GB"

	compress "$WORKDIR" "$NAME"


# LDAP Profile Plugin
	NAME="plg_ldap_profile"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 

	cp "$TRUNK/plugins/ldap/profile/profile.php" "."
	cp "$TRUNK/plugins/ldap/profile/profile.xml" "."

	xmlupdate profile.xml "$VER"

	copylang "$WORKDIR" "$NAME" "$TRUNK/administrator/language" "en-GB"

	compress "$WORKDIR" "$NAME"



#Build Packages
	echo "Building packages..."


# Authentication Only Package
	NAME="pkg_ldap_authentication"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 
	cd "$WORKDIR/$NAME"
	mkdir "packages"

	cp "$TEMPLATE/$NAME.xml" "."
	cp "$WORKDIR/public/lib_jldap2.zip" "packages"
	cp "$WORKDIR/public/plg_authentication_jmapmyldap.zip" "packages"

	xmlupdate "$NAME.xml" "$VER"

	compress "$WORKDIR" "$NAME"


# LDAP Core Framework Package
	NAME="pkg_ldap_core"
	newplugin "$WORKDIR" "$NAME" "$TEMPLATE" 
	cd "$WORKDIR/$NAME"
	mkdir "packages"

	cp "$TEMPLATE/$NAME.xml" "."
	cp "$WORKDIR/public/com_ldapadmin.zip" "packages"
	cp "$WORKDIR/public/lib_ldaplog.zip" "packages"
	cp "$WORKDIR/public/lib_ldapcore.zip" "packages"
	cp "$WORKDIR/public/plg_system_ldapdispatcher.zip" "packages"
	cp "$WORKDIR/public/lib_jldap2.zip" "packages"

	xmlupdate "$NAME.xml" "$VER"

	compress "$WORKDIR" "$NAME"

	exit

# =======================
# ===  OLD SSO Stuff  ===
# =======================

#JSSOMySite Library
	NAME="lib_jssomysite"
	mkdir "$WORKDIR/$NAME"
	cd "$WORKDIR/$NAME"
	mkdir "language"
	mkdir "language/en-GB"

	cp "$TEMPLATE/LICENSE.txt" "."
	cp "$TEMPLATE/index.html" "."

	cp "$TRUNK/libraries/shmanic/jssomysite.php" "."
	cp "$TRUNK/libraries/shmanic/jssomysite.xml" "."

	cp "$TRUNK/language/en-GB/$LANG.$NAME.ini" "language/en-GB"

	cd ..
	zip -r "$WORKDIR/public/$NAME.zip" $NAME
	tar -zcvf "$WORKDIR/public/$NAME.tar.gz" $NAME

#HTTP SSO Plugin
	NAME="plg_sso_http"
	mkdir "$WORKDIR/$NAME"
	cd "$WORKDIR/$NAME"
	mkdir "language"
	mkdir "language/en-GB"

	cp "$TEMPLATE/LICENSE.txt" "."
	cp "$TEMPLATE/index.html" "."

	cp "$TRUNK/plugins/sso/http/http.php" "."
	cp "$TRUNK/plugins/sso/http/http.xml" "."

	cp "$TRUNK/administrator/language/en-GB/$LANG.$NAME.ini" "language/en-GB"
	cp "$TRUNK/administrator/language/en-GB/$LANG.$NAME.sys.ini" "language/en-GB"

	cd ..
	zip -r "$WORKDIR/public/$NAME.zip" $NAME
	tar -zcvf "$WORKDIR/public/$NAME.tar.gz" $NAME

#JSSOMySite System Plugin
	NAME="plg_system_jssomysite"
	mkdir "$WORKDIR/$NAME"
	cd "$WORKDIR/$NAME"
	mkdir "language"
	mkdir "language/en-GB"

	cp "$TEMPLATE/LICENSE.txt" "."
	cp "$TEMPLATE/index.html" "."

	cp "$TRUNK/plugins/system/jssomysite/jssomysite.php" "."
	cp "$TRUNK/plugins/system/jssomysite/jssomysite.xml" "."

	cp "$TRUNK/administrator/language/en-GB/$LANG.$NAME.ini" "language/en-GB"
	cp "$TRUNK/administrator/language/en-GB/$LANG.$NAME.sys.ini" "language/en-GB"

	cd ..
	zip -r "$WORKDIR/public/$NAME.zip" $NAME
	tar -zcvf "$WORKDIR/public/$NAME.tar.gz" $NAME

#Build Packages
	echo "Building packages..."

#JSSOMySite Core Package
	NAME="pkg_jssomysite_core"
	mkdir "$WORKDIR/$NAME"
	cd "$WORKDIR/$NAME"
	mkdir "packages"

	cp "$TEMPLATE/LICENSE.txt" "."
	cp "$TEMPLATE/$NAME.xml" "."

	cp "$WORKDIR/public/plg_system_jssomysite.zip" "packages"
	cp "$WORKDIR/public/lib_jssomysite.zip" "packages"

	cd ..
	zip -r "$WORKDIR/public/$NAME.zip" $NAME
	tar -zcvf "$WORKDIR/public/$NAME.tar.gz" $NAME

#JSSOMySite Plugins Package
	NAME="pkg_jssomysite_plugins"
	mkdir "$WORKDIR/$NAME"
	cd "$WORKDIR/$NAME"
	mkdir "packages"

	cp "$TEMPLATE/LICENSE.txt" "."
	cp "$TEMPLATE/$NAME.xml" "."

	cp "$WORKDIR/public/plg_sso_http.zip" "packages"
	cp "$WORKDIR/public/plg_system_jssomysite.zip" "packages"
	cp "$WORKDIR/public/lib_jssomysite.zip" "packages"

	cd ..
	zip -r "$WORKDIR/public/$NAME.zip" $NAME
	tar -zcvf "$WORKDIR/public/$NAME.tar.gz" $NAME

else
	echo "Version already exists - exiting..."
fi


