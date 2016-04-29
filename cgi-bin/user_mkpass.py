#!/usr/bin/env python

# vim: set noai tabstop=4 shiftwidth=4 expandtab:

import os, sys, subprocess, random

if len( sys.argv ) <> 3:

    print( "Usage: %s <username> <password>" %sys.argv[0] )
    sys.exit(1)

USERNAME = sys.argv[1]
PASSWORD = sys.argv[2]

import pwd

try:
    subprocess.check_call("iadmin moduser %s password %s" %(USERNAME, PASSWORD), shell=True)

except subprocess.CalledProcessError as detail:

    print( "Setting user password in iRODS failed with exit code: %s" %str(detail.returncode) )
    #sys.exit( detail.returncode )
else:
    print( "Succesfully modified password for user %s to %s" %(USERNAME, PASSWORD) )

sys.exit(0)
