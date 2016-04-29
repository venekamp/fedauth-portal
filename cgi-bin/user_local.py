#!/usr/bin/env python

# vim: set noai tabstop=4 shiftwidth=4 expandtab:

import os, sys, subprocess, random

if len( sys.argv ) <> 2:

    print( "Usage: %s <username>" %sys.argv[0] )
    sys.exit(1)

USERNAME = sys.argv[1]
USERADD = "/usr/sbin/useradd"

import pwd

try:
    pwd.getpwnam( USERNAME )

except KeyError:

    print('User %s does not exist.' %USERNAME )

    try:
        subprocess.check_call("sudo %s %s" %(USERADD, USERNAME), shell=True)

    except subprocess.CalledProcessError as detail:

        print( "Adding user failed with exit code: %s" %str(detail.returncode) )
        print( detail.output )
        sys.exit( detail.returncode )
    else:
        print( "Succesfully added user %s" %USERNAME )

# TODO: check irods user exists: iadmin lu
# iadmin mkuser USERNAME rodsuser
try:
    subprocess.check_call("sudo -u irods /usr/bin/iadmin mkuser %s rodsuser" %(USERNAME), shell=True)

except subprocess.CalledProcessError as detail:

    print( "Adding user to iRODS failed with exit code: %s" %str(detail.returncode) )
    #sys.exit( detail.returncode )
else:
    print( "Succesfully added user %s to iRODS" %USERNAME )

# is user in OATH users file?
oath_users = open( '/etc/users.oath', 'r' )
oath_users_lines = oath_users.readlines()
oath_users.close()

add_oath_user = True

for line in oath_users_lines:
    line = line.strip().split('\t')

    if len(line) > 4:
        ( oath_TYPE, oath_USER, oath_PIN, oath_SECRET, oath_OFFSET, oath_LASTOTP, oath_LASTTIME ) = line
    else:
        print line
        ( oath_TYPE, oath_USER, oath_PIN, oath_SECRET ) = line

    if oath_USER == USERNAME:
        add_oath_user = False
        print( "User %s already in OATH" %USERNAME )

# echo "HOTP root - secret" >/etc/users.oath 
if add_oath_user:
    new_secret = '%020x' % random.randrange(16**20)

    hexed_secret = "".join("{0:02x}".format(ord(c)) for c in new_secret)
        
    oath_user_string = "HOTP/T30\t%s\t-\t%s\n" %(USERNAME, hexed_secret)

    oath_users = open("/etc/users.oath", "a")

    oath_users.write( oath_user_string )
    oath_users.close()

    print( "Succesfully added user %s to OATH" %USERNAME )

# gauth webapp secret = base32 encoded = base64.b32encode( SECRET )

sys.exit(0)
