#!/bin/bash

echo "- Installing LDAP and Configuration -"
DEBIAN_FRONTEND=noninteractive apt-get install -y slapd ldap-utils shelldap

ldapmodify -Y EXTERNAL -H ldapi:/// -f ./ldifs/db-config.ldif
ldapmodify -Y EXTERNAL -H ldapi:/// -f ./ldifs/activate-memberof.ldif

ldapmodify -x -D cn=admin,dc=shmanic,dc=net -w shmanic.com -f ./ldifs/add-ou.ldif
ldapmodify -x -D cn=admin,dc=shmanic,dc=net -w shmanic.com -f ./ldifs/default-group.ldif
ldapmodify -x -D cn=admin,dc=shmanic,dc=net -w shmanic.com -f ./ldifs/add-users.ldif
ldapmodify -x -D cn=admin,dc=shmanic,dc=net -w shmanic.com -f ./ldifs/add-groups.ldif

/etc/init.d/slapd restart
echo "- Setup Completed - "
echo
echo "Admin DN is 'cn=admin,dc=shmanic,dc=net'"
echo "Admin password is 'shmanic.com'"
echo "Use to manage: shelldap --server localhost --binddn cn=admin,dc=shmanic,dc=net"
