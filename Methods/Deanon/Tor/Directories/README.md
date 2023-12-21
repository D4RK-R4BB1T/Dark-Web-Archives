# Tor Hidden Service directories

Pretty Simple there's a few methods to de-anon a Hidden Service and best way is to know your hidden service's webserver Apache2, NGINX, Lightspeed, Python HTTP Server or whatever.

To find this out you could use Brave's Tor Window (Since Tor Browser has removed the ability to anaylize Traffic via the network console), You could add use wappalyzer.com and add it to tor or you can fuck around by apperhending a domain with /404


Once you've identified what webserver you're working with sometimes these services maybe misconfigured we've seen sites like Ransomed.vc leak their IP Even though they claimeed to be using NGINX but Apache2 was still present so adding https:// would completely pivot from "NGINX's "We can't find that page" to the site in itself" HTTP/HTTPS and Ports can widely change how a direct IP connection may work.


# Directories

/404 - Can be used to deterime a webserver

/server-status - Part of Apache2, Can be used to identify VHOSTS, Search Queries and etc also may leak IPv4 Addresses if misconfigured

/server-info - Can leak server configuration files and provide information about Apache2's installation, Common on Windows Servers using XMPP 

/cpanel - A common directory which can be used to modified databases and files remotely and with ease, can leak IPs if not properly configured

/zpanel - Same as /cpanel

/kpanel - same as the last 2

/config.php - Rarely left open, but can give out information if left open to the public

/test - Maybe used to test if a domain may leak information or redirect

# Ports

2082 - Cpanel's HTTP Default Port can be added directly as in .onion:2082

2083 - Cpanel's HTTPS Default Port

443 -  HTTPS Default

80 - HTTP Default Port

8080 - Common HTTP Port


# Subdomains

When all else fails simply adding test. maybe open the door to directories having permissions changed, If you get lead to a CPanel Default page you can purge the /cgi-bin/* and replace with /cpanel it may deanon the site's backend.

# HTTP vs HTTPS

As mentioned previously sometimes certian IPs may refuse the default HTTP Route and may requrie you to add http:// or https:// and may require you to a port.


