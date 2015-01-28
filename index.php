<?php
function get_members($group=FALSE,$inclusive=FALSE) {
    // Active Directory server
    $ldap_host = "x.x.x.x";
    // Active Directory DN
    $ldap_dn = "OU=People,OU=,DC=domain,DC=local";
    // Active Directory user
    $user = "domain\\user";
    $password = "password";
    // User attributes we want to keep
    // List of User Object properties:
    // http://www.dotnetactivedirectory.com/Understanding_LDAP_Active_Directory_User_Object_Properties.html
    $keep = array(
	"name",
    "samaccountname",
	"department",
	"mail",
	"homephone",
	"mobile",
	"telephonenumber",
	"manager",
	"physicaldeliveryofficename",
	"title"
    );
    // Connect to AD
    $ldap = ldap_connect($ldap_host) or die("Could not connect to LDAP");
    ldap_bind($ldap,$user,$password) or die("Could not bind to LDAP");
    // Begin building query
    if($group) $query = "(&"; else $query = "";
 
    $query .= "(&(objectClass=user)(objectCategory=person))";
 
    // Filter by memberOf, if group is set
    if(is_array($group)) {
        // Looking for a members amongst multiple groups
            if($inclusive) {
                // Inclusive - get users that are in any of the groups
                // Add OR operator
                $query .= "(|";
            } else {
                // Exclusive - only get users that are in all of the groups
                // Add AND operator
                $query .= "(&";
            }
 
            // Append each group
            foreach($group as $g) $query .= "(memberOf=CN=$g,$ldap_dn)";
 
            $query .= ")";
    } elseif($group) {
        // Just looking for membership of one group
        $query .= "(memberOf=CN=$group,$ldap_dn)";
    }
 
    // Close query
    if($group) $query .= ")"; else $query .= "";
 
    // Uncomment to output queries onto page for debugging
    // print_r($query);
 
    // Search AD
    $results = ldap_search($ldap,$ldap_dn,$query);
    $entries = ldap_get_entries($ldap, $results);

    // Remove first entry (it's always blank)
    array_shift($entries);
    $output = array(); // Declare the output array
    $i = 0; // Counter
    // Build output array
    foreach($entries as $u) {
        foreach($keep as $x) {
            // Check for attribute
            if(isset($u[$x][0])) $attrval = $u[$x][0]; else $attrval = NULL;
 
            // Append attribute to output array
            $output[$i][$x] = $attrval;
        }
        $i++;
    }
    return $output;
}

$contactdata = get_members();
$contactcount = count($contactdata);
$c = 0;

print "<html><head><title>Company Directory</title><script type='text/javascript' src='./jquery-latest.js'></script><script type='text/javascript' src='./jquery.tablesorter.js'></script><script type='text/javascript'>\$(document).ready(function() {\$('#myTable').tablesorter({ sortList: [[0,0]] });});</script></head><body><center><h1>Company Directory</h1></center>";
print "<center><table border='1' cellpadding='4' cellspacing='0' bordercolor='#bcbcbc' id='myTable' class='tablesorter'>";
print "<thead><tr><th>Name</th><th>Department</th><th>Position</th><th>Location</th><th>Office Phone</th><th>Mobile Phone</th><th>Home Phone</th><th>Email</th><th>Manager</th></tr></thead>";
print "<tbody>";
// start contact rows
while ($c < $contactcount) {
	print "<tr>";
	print "<td>" . $contactdata[$c]["name"] . "</td>";
	print "<td>" . $contactdata[$c]["department"] . "</td>";
        print "<td>" . $contactdata[$c]["title"] . "</td>";
        print "<td>" . $contactdata[$c]["physicaldeliveryofficename"] . "</td>";
	print "<td>" . $contactdata[$c]["telephonenumber"] . "</td>";
	print "<td>" . $contactdata[$c]["mobile"] . "</td>";
	print "<td>" . $contactdata[$c]["homephone"] . "</td>";
	print "<td><a href='mailto:" . $contactdata[$c]["mail"] . "'>" . $contactdata[$c]["mail"] . "</a></td>";
	print "<td>" . substr(strstr($contactdata[$c]["manager"], ",OU=", true), 3) . "</td>";
	print "</tr>";
	++$c;
}
// end contact rows
print "</tbody></table></center></body></html>";
?>
