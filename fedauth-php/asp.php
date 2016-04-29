<?php
    require_once('/var/simplesamlphp/lib/_autoload.php');

    $as = new SimpleSAML_Auth_Simple('SURFconext');
    $as->requireAuth();
    $attributes = $as->getAttributes();

    /*
     * Return a unique user name from the attributes.
     *
     */
    function get_username($samlAttributes)
    {
        // For now we use the 'uid' attributes to get a username. However, this
        // this not correct as the 'uid' the same. See,
        // https://wiki.surfnet.nl/display/surfconextdev/Attributes+in+SURFconext#AttributesinSURFconext-uid
        $username = $samlAttributes['urn:mace:dir:attribute-def:uid'][0];

        return $username;
    }

    /*
     * Return the last name from the current attributes.
     *
     */
    function get_lastname($samlAttributes)
    {
        // Unfortunately, the attrubutes release to the portal do not include
        // a last name. Since we cannot possibly start to get it from the
        // attributes we do recieve. we'll simply return 'last_name' for now.
        return "last_name";
    }

    /*
     * For the portal a special group 'irodsuser' exists and its gid is returned.
     *
     */
    function get_gidNumber($ldapConnection, $base_dn)
    {
        $rdn = "ou=group," . $base_dn;
        $filter = "cn=irodsuser";

        $result = ldap_search($ldapConnection, $rdn, $filter)
            or die("Error in ldap search query: " . ldap_error($ldapConnection));

        $data = ldap_get_entries($ldapConnection, $result);

        $gidNumber = -1;
        if ($data["count"] == 1)
        {
            $gidNumber = $data[0]["gidnumber"][0];
        }
        else
        {
            die("Group not found.");
        }

        return $gidNumber;
    }

    /*
     * From the set of existing uids pick the first uid that is not in use yet.
     * 
     */
    function get_next_free_uid_number($ldapConnection, $base_dn)
    {
        $rdn = "ou=users," . $base_dn;

        $filter = "(uidNumber=*)";
        $result = ldap_search($ldapConnection, $rdn, $filter, array("uidNumber"))
            or die("Search failed.");

        $data = ldap_get_entries($ldapConnection, $result);

        //  If there are not uids present, we'll start at 1000.
        if ($data["count"] == 0)
            return 1000;

        //  Collect all available uids.
        for ($i=0; $i<$data["count"]; $i++)
        {
            $uids[] = intval($data[$i]["uidnumber"][0]);
        }

        //  Sort the uids fist, it make searching easier.
        sort($uids);

        //  Try to find a gap in the list of uids. If there is one,
        //  the 'missing' uid is unused. If there is nothing, we'll
        //  end at the last issued uid.
        $free_uid = $uids[0];
        for ($i=1; $i<count($uids); $i++)
        {
            if (($uids[$i] - $free_uid) > 1)
                break;

            $free_uid = $uids[$i];
        }

        //  Don't forget to advance the counter.
        return ++$free_uid;
    }

    /*
     *  Generate a random password
     *
     */
    function generate_password($length = 8) {
        #$chars = "+-0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        # NOTE: avoid chars like #, ?, and / etc if you want to be able to embed credentials in a URL
        $chars = "23456789-bcd+fgh.jkmnpqrst:vwxyz";        # 32 characters => 5 bits entropy. Need 20 chars for 80 bit entropy
        #$length = 20;
        #$length = 12;       # settle for 60 bit
        $count = mb_strlen($chars);
        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }
        return $result;
    }

    /*
     * Check if the RDN exists.
     *
     */
    function rdn_exists($ldapConnection, $base_dn, $cn)
    {
        $rdn = "ou=users," . $base_dn;
        $filter = "cn=" . $cn;

        $result = ldap_search($ldapConnection, $rdn, $filter)
            or die("Error in ldap search query: " . ldap_error($ldapConnection));
        $data = ldap_get_entries($ldapConnection, $result);

        // If the number of returned items equals 0, then nothing has ben found.
        // Thus, the rdn does not exists.
        if ($data["count"] == 0)
        {
            return false;
        }

        return true;
    }

    /*
     * Update the the password.
     *
     */
    function update_user_password($ldapConnection, $base_dn, $name, $password)
    {
        $rdn = "cn=" . $name . ",ou=users," . $base_dn;

        $data["userPassword"] = $password;
        ldap_mod_replace($ldapConnection, $rdn, $data);
    }

    /*
     * Create a new RDN.
     *
     */
    function create_rdn($ldapConnection, $base_dn, $name, $password)
    {
        $rdn = "cn=" . $name . ",ou=users," . $base_dn;

        $lastName = get_lastname($ldapConnection);
        $uidNumber = get_next_free_uid_number($ldapConnection, $base_dn);
        $gidNumber = get_gidNumber($ldapConnection, $base_dn);

        $data["sn"] = $lastName;
        $data["uid"] = $name;
        $data["uidNumber"] = $uidNumber;
        $data["gidNumber"] = $gidNumber;
        $data["userPassword"] = $password;
        $data["homeDirectory"] = "/Users/irods/". $name;
        $data["objectClass"][2] = "top";
        $data["objectClass"][1] = "posixAccount";
        $data["objectClass"][0] = "inetOrgPerson";

        $r = ldap_add($ldapConnection, $rdn, $data);
    }

    $password = generate_password(4);

    $ldapConnection = ldap_connect("ldaps://irods-01.sara.vm.surfsara.nl/")
        or die("Connecting to LDAP server failed.");

    $base_dn = "dc=sara,dc=vm,dc=surfsara,dc=nl";
    $admin_dn = "cn=admin" . "," . $base_dn;
    $admin_pwd = "12345678";

    ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);

    $ldapBind = ldap_bind($ldapConnection, $admin_dn, $admin_pwd)
        or die("Binding to the LDAP server failed.");

    $name = get_username($attributes);

    if (rdn_exists($ldapConnection, $base_dn, $name))
    {
        update_user_password($ldapConnection, $base_dn, $name, $password);
    }
    else
    {
        create_rdn($ldapConnection, $base_dn, $name, $password);
    }

    ldap_close($ldapConnection);
/*
    $output = array();
    $rt = 0;
    exec("sudo ./user_mkpass.py ".$username , $password, $rt );
    $secret = $output[0];
*/

/*
    $ldapConnection = ldap_connect("ldaps://localhost/")
        or die("Connecting to LDAP server failed.");

    if ($ldapConnection)
    {
        $admin_dn = "cn=admin,dc=sara,dc=vm,dc=surfsara,dc=nl";
        $admin_pwd = "12345678";

        ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);

        $ldapBind = ldap_bind($ldapConnection, $admin_dn, $admin_pwd);

        if( $ldapBind ) {
            #echo "Bind was succesful.";

            $sr = ldap_search($ldapConnection, "dc=sara,dc=vm,dc=surfsara,dc=nl", "objectClass=posixAccount");

            #echo "Search result: " . $sr . "<br />";
            #echo "Number of entries returned is " . ldap_count_entries($ldapConnection, $sr) . "<br />";

            #echo "Getting entries ...<p>";
            #$info = ldap_get_entries($ldapConnection, $sr);
            #echo "Data for " . $info["count"] . " items returned:<p>";

            #for ($i=0; $i<$info["count"]; $i++) {
            #    echo "dn is: " . $info[$i]["dn"] . "<br />";
            #    echo "first cn entry is: " . $info[$i]["cn"][0] . "<br />";
            #}

            $dn = "cn=Sint Klaas,ou=users,dc=sara,dc=vm,dc=surfsara,dc=nl";

            #$data["cn"] = "Mika Venekamp";
            $data["sn"] = "Klaas";
            $data["uid"] = "sint";
            $data["uidNumber"] = 2003;
            $data["gidNumber"] = 1000;
            $data["userPassword"] = "12345678";
            $data["homeDirectory"] = "/Users/persons/klaas";
            $data["objectClass"][2] = "top";
            $data["objectClass"][1] = "posixAccount";
            $data["objectClass"][0] = "inetOrgPerson";

            $r = ldap_add($ldapConnection, $dn, $data);

            #echo "Error number: " . ldap_errno($ldapConnection) . "<br />";
            #echo "Error message: " . ldap_error($ldapConnection);

            #var_dump($data);
            #print_r($data);

            #return;
        }
        else
        {
            echo "LDAP bind failed.";
            return;
        }

        ldap_close($ldapConnection);
    }
    else
    {
        echo "Unable to set the password";
    }
 */

    echo $password;
?>
