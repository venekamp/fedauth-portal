#!/usr/bin/env python

import sys, base64

if len( sys.argv ) <> 2:

    print( "Usage: %s <username>" %sys.argv[0] )
    sys.exit(1)

USERNAME = sys.argv[1]

oath_users = open( '/etc/users.oath', 'r' )
oath_users_lines = oath_users.readlines()
oath_users.close()

add_oath_user = True

for line in oath_users_lines:

    line = line.strip().split('\t')

    if len(line) > 4:
        ( oath_TYPE, oath_USER, oath_PIN, oath_SECRET, oath_OFFSET, oath_LASTOTP, oath_LASTTIME ) = line
    else:
        ( oath_TYPE, oath_USER, oath_PIN, oath_SECRET ) = line

    if oath_USER == USERNAME:
        s = ""
        for i in range(0, len(oath_SECRET) / 2):
            offset = i * 2
            chunck = oath_SECRET[offset:offset + 2]
            value = chunck.decode('hex');
            s += str(value)

        print("%s" %base64.b32encode(s))
