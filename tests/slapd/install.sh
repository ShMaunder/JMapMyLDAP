#!/bin/bash

SRCDIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

echo "- Installing LDAP and Configuration -"
DEBIAN_FRONTEND=noninteractive apt-get install -y slapd ldap-utils shelldap

ldapmodify -Y EXTERNAL -H ldapi:/// -f $SRCDIR/ldifs/db-config.ldif
ldapmodify -Y EXTERNAL -H ldapi:/// -f $SRCDIR/ldifs/activate-memberof.ldif

ldapmodify -x -D cn=admin,dc=shmanic,dc=net -w shmanic.com -f $SRCDIR/ldifs/add-ou.ldif
ldapmodify -x -D cn=admin,dc=shmanic,dc=net -w shmanic.com -f $SRCDIR/ldifs/default-group.ldif
ldapmodify -x -D cn=admin,dc=shmanic,dc=net -w shmanic.com -f $SRCDIR/ldifs/add-users.ldif
ldapmodify -x -D cn=admin,dc=shmanic,dc=net -w shmanic.com -f $SRCDIR/ldifs/add-groups.ldif

ldapmodify -Y EXTERNAL -H ldapi:/// -f $SRCDIR/ldifs/add-index.ldif

/etc/init.d/slapd stop

slapindex -F /etc/ldap/slapd.d/
chown -R openldap:openldap /var/lib/ldap

/etc/init.d/slapd start

echo "- Setup Completed - "
echo
echo "Admin DN is 'cn=admin,dc=shmanic,dc=net'"
echo "Admin password is 'shmanic.com'"
echo "Use to manage: shelldap --server localhost --binddn cn=admin,dc=shmanic,dc=net"
