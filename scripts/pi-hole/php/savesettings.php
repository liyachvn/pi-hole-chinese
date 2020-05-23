<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */

if(!in_array(basename($_SERVER['SCRIPT_FILENAME']), ["settings.php", "teleporter.php"], true))
{
	die("Direct access to this script is forbidden!");
}

function validIP($address){
	if (preg_match('/[.:0]/', $address) && !preg_match('/[1-9a-f]/', $address)) {
		// Test if address contains either `:` or `0` but not 1-9 or a-f
		return false;
	}
	return !filter_var($address, FILTER_VALIDATE_IP) === false;
}

// Check for existance of variable
// and test it only if it exists
function istrue(&$argument) {
	if(isset($argument))
	{
		if($argument)
		{
			return true;
		}
	}
	return false;
}

// Credit: http://stackoverflow.com/a/4694816/2087442
function validDomain($domain_name)
{
	$validChars = preg_match("/^([_a-z\d](-*[_a-z\d])*)(\.([_a-z\d](-*[a-z\d])*))*(\.([_a-z\d])*)*$/i", $domain_name);
	$lengthCheck = preg_match("/^.{1,253}$/", $domain_name);
	$labelLengthCheck = preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name);
	return ( $validChars && $lengthCheck && $labelLengthCheck ); //length of each label
}

function validDomainWildcard($domain_name)
{
	// There has to be either no or at most one "*" at the beginning of a line
	$validChars = preg_match("/^((\*.)?[_a-z\d](-*[_a-z\d])*)(\.([_a-z\d](-*[a-z\d])*))*(\.([_a-z\d])*)*$/i", $domain_name);
	$lengthCheck = preg_match("/^.{1,253}$/", $domain_name);
	$labelLengthCheck = preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $domain_name);
	return ( $validChars && $lengthCheck && $labelLengthCheck ); //length of each label
}

function validMAC($mac_addr)
{
  // Accepted input format: 00:01:02:1A:5F:FF (characters may be lower case)
  return !filter_var($mac_addr, FILTER_VALIDATE_MAC) === false;
}

function formatMAC($mac_addr)
{
	preg_match("/([0-9a-fA-F]{2}[:]){5}([0-9a-fA-F]{2})/", $mac_addr, $matches);
	if(count($matches) > 0)
		return $matches[0];
	return null;
}

function validEmail($email)
{
	return filter_var($email, FILTER_VALIDATE_EMAIL)
		// Make sure that the email does not contain special characters which
		// may be used to execute shell commands, even though they may be valid
		// in an email address. If the escaped email does not equal the original
		// email, it is not safe to store in setupVars.
		&& escapeshellcmd($email) === $email;
}

$dhcp_static_leases = array();
function readStaticLeasesFile($origin_file="/etc/dnsmasq.d/04-pihole-static-dhcp.conf")
{
	global $dhcp_static_leases;
	$dhcp_static_leases = array();
	if(!file_exists($origin_file) || !is_readable($origin_file))
		return false;

	$dhcpstatic = @fopen($origin_file, 'r');
	if(!is_resource($dhcpstatic))
		return false;

	while(!feof($dhcpstatic))
	{
		// Remove any possibly existing variable with this name
		$mac = ""; $one = ""; $two = "";
		sscanf(trim(fgets($dhcpstatic)),"dhcp-host=%[^,],%[^,],%[^,]",$mac,$one,$two);
		if(strlen($mac) > 0 && validMAC($mac))
		{
			if(validIP($one) && strlen($two) == 0)
				// dhcp-host=mac,IP - no HOST
				array_push($dhcp_static_leases,["hwaddr"=>$mac, "IP"=>$one, "host"=>""]);
			elseif(strlen($two) == 0)
				// dhcp-host=mac,hostname - no IP
				array_push($dhcp_static_leases,["hwaddr"=>$mac, "IP"=>"", "host"=>$one]);
			else
				// dhcp-host=mac,IP,hostname
				array_push($dhcp_static_leases,["hwaddr"=>$mac, "IP"=>$one, "host"=>$two]);
		}
		else if(validIP($one) && validDomain($mac))
		{
			// dhcp-host=hostname,IP - no MAC
			array_push($dhcp_static_leases,["hwaddr"=>"", "IP"=>$one, "host"=>$mac]);
		}
	}
	return true;
}

function isequal(&$argument, &$compareto) {
	if(isset($argument))
	{
		if($argument === $compareto)
		{
			return true;
		}
	}
	return false;
}

function isinserverlist($addr) {
	global $DNSserverslist;
	foreach ($DNSserverslist as $key => $value) {
		if (isequal($value['v4_1'],$addr) || isequal($value['v4_2'],$addr))
			return true;
		if (isequal($value['v6_1'],$addr) || isequal($value['v6_2'],$addr))
			return true;
	}
	return false;
}

$DNSserverslist = [];
function readDNSserversList()
{
	// Reset list
	$list = [];
	$handle = @fopen("/etc/pihole/dns-servers.conf", "r");
	if ($handle)
	{
		while (($line = fgets($handle)) !== false)
		{
			$line = rtrim($line);
			$line = explode(';', $line);
			$name = $line[0];
			$values = [];
			if (!empty($line[1]) && validIP($line[1])) {
				$values["v4_1"] = $line[1];
			}
			if (!empty($line[2]) && validIP($line[2])) {
				$values["v4_2"] = $line[2];
			}
			if (!empty($line[3]) && validIP($line[3])) {
				$values["v6_1"] = $line[3];
			}
			if (!empty($line[4]) && validIP($line[4])) {
				$values["v6_2"] = $line[4];
			}
            $list[$name] = $values;
		}
		fclose($handle);
	}
	return $list;
}

require_once("database.php");

function addStaticDHCPLease($mac, $ip, $hostname) {
	global $error, $success, $dhcp_static_leases;

	try {
		if(!validMAC($mac))
		{
			throw new Exception("MAC 地址 (".htmlspecialchars($mac).") 无效！<br>", 0);
		}
		$mac = strtoupper($mac);

		if(!validIP($ip) && strlen($ip) > 0)
		{
			throw new Exception("IP 地址 (".htmlspecialchars($ip).") 无效！<br>", 1);
		}

		if(!validDomain($hostname) && strlen($hostname) > 0)
		{
			throw new Exception("主机名称 (".htmlspecialchars($hostname).") 无效！<br>", 2);
		}

		if(strlen($hostname) == 0 && strlen($ip) == 0)
		{
			throw new Exception("您不能既不输入 IP 地址又不输入主机名称！<br>", 3);
		}

		if(strlen($hostname) == 0)
			$hostname = "nohost";

		if(strlen($ip) == 0)
			$ip = "noip";

		// Test if this lease is already included
		readStaticLeasesFile();

		foreach($dhcp_static_leases as $lease) {
			if($lease["hwaddr"] === $mac)
			{
				throw new Exception("MAC 地址为 (".htmlspecialchars($mac).") 的静态租约已经定义！<br>", 4);
			}
			if($ip !== "noip" && $lease["IP"] === $ip)
			{
				throw new Exception("IP 地址为 (".htmlspecialchars($ip).") 的静态租约已定义！<br>", 5);
			}
			if($lease["host"] === $hostname)
			{
				throw new Exception("主机名称为 (".htmlspecialchars($hostname).") 的静态租约已定义<br>", 6);
			}
		}

		exec("sudo pihole -a addstaticdhcp ".$mac." ".$ip." ".$hostname);
		$success .= "一个新的静态地址已经添加";
		return true;
	} catch(Exception $exception) {
		$error .= $exception->getMessage();
		return false;
	}
}

	// Read available DNS server list
	$DNSserverslist = readDNSserversList();

	$error = "";
	$success = "";

	if(isset($_POST["field"]))
	{
		// Handle CSRF
		check_csrf(isset($_POST["token"]) ? $_POST["token"] : "");

		// Process request
		switch ($_POST["field"]) {
			// Set DNS server
			case "DNS":

				$DNSservers = [];
				// Add selected predefined servers to list
				foreach ($DNSserverslist as $key => $value)
				{
					foreach(["v4_1", "v4_2", "v6_1", "v6_2"] as $type)
					{
						if(@array_key_exists("DNSserver".str_replace(".","_",$value[$type]),$_POST))
						{
							array_push($DNSservers,$value[$type]);
						}
					}
				}

				// Test custom server fields
				for($i=1;$i<=4;$i++)
				{
					if(array_key_exists("custom".$i,$_POST))
					{
						$exploded = explode("#", $_POST["custom".$i."val"], 2);
						$IP = $exploded[0];
						if(count($exploded) > 1)
						{
							$port = $exploded[1];
						}
						else
						{
							$port = "53";
						}
						if(!validIP($IP))
						{
							$error .= "IP 地址 (".htmlspecialchars($IP).") 无效！<br>";
						}
						elseif(!is_numeric($port))
						{
							$error .= "端口 (".htmlspecialchars($port).") 无效！<br>";
						}
						else
						{
							array_push($DNSservers,$IP."#".$port);
						}
					}
				}
				$DNSservercount = count($DNSservers);

				// Check if at least one DNS server has been added
				if($DNSservercount < 1)
				{
					$error .= "未选择 DNS 服务器。<br>";
				}

				// Check if domain-needed is requested
				if(isset($_POST["DNSrequiresFQDN"]))
				{
					$extra = "domain-needed ";
				}
				else
				{
					$extra = "domain-not-needed ";
				}

				// Check if domain-needed is requested
				if(isset($_POST["DNSbogusPriv"]))
				{
					$extra .= "bogus-priv ";
				}
				else
				{
					$extra .= "no-bogus-priv ";
				}

				// Check if DNSSEC is requested
				if(isset($_POST["DNSSEC"]))
				{
					$extra .= "dnssec";
				}
				else
				{
					$extra .= "no-dnssec";
				}

				// Check if Conditional Forwarding is requested
				if(isset($_POST["conditionalForwarding"]))
				{
					// Validate conditional forwarding IP
					if (!validIP($_POST["conditionalForwardingIP"]))
					{
						$error .= "条件转发 IP 地址 (".htmlspecialchars($_POST["conditionalForwardingIP"]).") 无效！<br>";
					}

					// Validate conditional forwarding domain name
					if(!validDomain($_POST["conditionalForwardingDomain"]))
					{
						$error .= "条件转发域名 (".htmlspecialchars($_POST["conditionalForwardingDomain"]).") 无效！<br>";
					}
					if(!$error)
					{
						$addressArray = explode(".", $_POST["conditionalForwardingIP"]);
						$reverseAddress = $addressArray[2].".".$addressArray[1].".".$addressArray[0].".in-addr.arpa";
						$extra .= " conditional_forwarding ".$_POST["conditionalForwardingIP"]." ".$_POST["conditionalForwardingDomain"]." $reverseAddress";
					}
				}

				// Check if DNSinterface is set
				if(isset($_POST["DNSinterface"]))
				{
					if($_POST["DNSinterface"] === "single")
					{
						$DNSinterface = "single";
					}
					elseif($_POST["DNSinterface"] === "all")
					{
						$DNSinterface = "all";
					}
					else
					{
						$DNSinterface = "local";
					}
				}
				else
				{
					// Fallback
					$DNSinterface = "local";
				}
				exec("sudo pihole -a -i ".$DNSinterface." -web");

				// If there has been no error we can save the new DNS server IPs
				if(!strlen($error))
				{
					$IPs = implode (",", $DNSservers);
					$return = exec("sudo pihole -a setdns \"".$IPs."\" ".$extra);
					$success .= htmlspecialchars($return)."<br>";
					$success .= "DNS 设置已更新（使用 ".$DNSservercount." DNS 服务器）";
				}
				else
				{
					$error .= "设置已被重置为原始值";
				}

				break;

			// Set query logging
			case "Logging":

				if($_POST["action"] === "Disable")
				{
					exec("sudo pihole -l off");
					$success .= "已禁用日志记录，并且已刷新日志。";
				}
				elseif($_POST["action"] === "Disable-noflush")
				{
					exec("sudo pihole -l off noflush");
					$success .= "已禁用日志记录，您的日志<strong>没有</strong>被刷新。";
				}
				else
				{
					exec("sudo pihole -l on");
					$success .= "已启用日志记录。";
				}

				break;

			// Set domains to be excluded from being shown in Top Domains (or Ads) and Top Clients
			case "API":

				// Explode the contents of the textareas into PHP arrays
				// \n (Unix) and \r\n (Win) will be considered as newline
				// array_filter( ... ) will remove any empty lines
				$domains = array_filter(preg_split('/\r\n|[\r\n]/', $_POST["domains"]));
				$clients = array_filter(preg_split('/\r\n|[\r\n]/', $_POST["clients"]));

				$domainlist = "";
				$first = true;
				foreach($domains as $domain)
				{
					if(!validDomainWildcard($domain) || validIP($domain))
					{
						$error .= "活跃域名/广告条目 ".htmlspecialchars($domain)." 无效（只使用域名）！<br>";
					}
					if(!$first)
					{
						$domainlist .= ",";
					}
					else
					{
						$first = false;
					}
					$domainlist .= $domain;
				}

				$clientlist = "";
				$first = true;
				foreach($clients as $client)
				{
					if(!validDomainWildcard($client) && !validIP($client))
					{
						$error .= "活跃客户端条目 ".htmlspecialchars($client)." 无效（只使用主机名称和 IP 地址）！<br>";
					}
					if(!$first)
					{
						$clientlist .= ",";
					}
					else
					{
						$first = false;
					}
					$clientlist .= $client;
				}

				// Set Top Lists options
				if(!strlen($error))
				{
					// All entries are okay
					exec("sudo pihole -a setexcludedomains ".$domainlist);
					exec("sudo pihole -a setexcludeclients ".$clientlist);
					$success .= "API 设置已更新<br>";
				}
				else
				{
					$error .= "设置已被重置为原始值";
				}

				// Set query log options
				if(isset($_POST["querylog-permitted"]) && isset($_POST["querylog-blocked"]))
				{
					exec("sudo pihole -a setquerylog all");
					if(!isset($_POST["privacyMode"]))
					{
						$success .= "所有条目将在查询日志中显示。";
					}
					else
					{
						$success .= "只有阻止的条目在查询日志中显示。";
					}
				}
				elseif(isset($_POST["querylog-permitted"]))
				{
					exec("sudo pihole -a setquerylog permittedonly");
					if(!isset($_POST["privacyMode"]))
					{
						$success .= "只有允许的条目在查询日志中显示。";
					}
					else
					{
						$success .= "查询日志中不显示任何条目。";
					}
				}
				elseif(isset($_POST["querylog-blocked"]))
				{
					exec("sudo pihole -a setquerylog blockedonly");
					$success .= "只有阻止的条目在查询日志中显示。";
				}
				else
				{
					exec("sudo pihole -a setquerylog nothing");
					$success .= "查询日志中不显示任何条目。";
				}


				if(isset($_POST["privacyMode"]))
				{
					exec("sudo pihole -a privacymode true");
					$success .= " （隐私模式开启）";
				}
				else
				{
					exec("sudo pihole -a privacymode false");
				}

				break;

			case "webUI":
				if($_POST["tempunit"] == "F")
				{
					exec('sudo pihole -a -f');
				}
				elseif($_POST["tempunit"] == "K")
				{
					exec('sudo pihole -a -k');
				}
				else
				{
					exec('sudo pihole -a -c');
				}
				$adminemail = trim($_POST["adminemail"]);
				if(strlen($adminemail) == 0 || !isset($adminemail))
				{
					$adminemail = '';
				}
				if(strlen($adminemail) > 0 && !validEmail($adminemail))
				{
					$error .= "管理员邮件地址 (".htmlspecialchars($adminemail).") 无效！<br>";
				}
				else
				{
					exec('sudo pihole -a -e \''.$adminemail.'\'');
				}
				if(isset($_POST["boxedlayout"]))
				{
					exec('sudo pihole -a layout boxed');
				}
				else
				{
					exec('sudo pihole -a layout traditional');
				}
				$success .= "webUI 设置已更新";
				break;

			case "poweroff":
				exec("sudo pihole -a poweroff");
				$success = "系统将在 5 秒后关机……";
				break;

			case "reboot":
				exec("sudo pihole -a reboot");
				$success = "系统将在 5 秒后重新启动……";
				break;

			case "restartdns":
				exec("sudo pihole -a restartdns");
				$success = "DNS 服务器已重新启动";
				break;

			case "flushlogs":
				exec("sudo pihole -f");
				$success = "Pi-hole 日志文件已刷新";
				break;

			case "DHCP":

				if(isset($_POST["addstatic"]))
				{
					$mac = $_POST["AddMAC"];
					$ip = $_POST["AddIP"];
					$hostname = $_POST["AddHostname"];

					addStaticDHCPLease($mac, $ip, $hostname);
					break;
				}

				if(isset($_POST["removestatic"]))
				{
					$mac = $_POST["removestatic"];
					if(!validMAC($mac))
					{
						$error .= "MAC 地址 (".htmlspecialchars($mac).") 无效！<br>";
					}
					$mac = strtoupper($mac);

					if(!strlen($error))
					{
						exec("sudo pihole -a removestaticdhcp ".$mac);
						$success .= "MAC 地址为 ".htmlspecialchars($mac)." 的静态地址已被删除";
					}
					break;
				}

				if(isset($_POST["active"]))
				{
					// Validate from IP
					$from = $_POST["from"];
					if (!validIP($from))
					{
						$error .= "起始 IP (".htmlspecialchars($from).") 无效！<br>";
					}

					// Validate to IP
					$to = $_POST["to"];
					if (!validIP($to))
					{
						$error .= "终止 IP (".htmlspecialchars($to).") 无效！<br>";
					}

					// Validate router IP
					$router = $_POST["router"];
					if (!validIP($router))
					{
						$error .= "路由器 IP (".htmlspecialchars($router).") 无效！<br>";
					}

					$domain = $_POST["domain"];

					// Validate Domain name
					if(!validDomain($domain))
					{
						$error .= "域名 ".htmlspecialchars($domain)." 无效！<br>";
					}

					$leasetime = $_POST["leasetime"];

					// Validate Lease time length
					if(!is_numeric($leasetime) || intval($leasetime) < 0)
					{
						$error .= "租约时间 ".htmlspecialchars($leasetime)." 无效！<br>";
					}

					if(isset($_POST["useIPv6"]))
					{
						$ipv6 = "true";
						$type = "(IPv4 + IPv6)";
					}
					else
					{
						$ipv6 = "false";
						$type = "(IPv4)";
					}

					if(isset($_POST["DHCP_rapid_commit"]))
					{
						$rapidcommit = "true";
					}
					else
					{
						$rapidcommit = "false";
					}

					if(!strlen($error))
					{
						exec("sudo pihole -a enabledhcp ".$from." ".$to." ".$router." ".$leasetime." ".$domain." ".$ipv6." ".$rapidcommit);
						$success .= "DHCP 服务器已被激活 ".htmlspecialchars($type);
					}
				}
				else
				{
					exec("sudo pihole -a disabledhcp");
					$success = "DHCP 服务器已被停用";
				}

				break;

			case "privacyLevel":
				$level = intval($_POST["privacylevel"]);
				if($level >= 0 && $level <= 4)
				{
					// Check if privacylevel is already set
					if (isset($piholeFTLConf["PRIVACYLEVEL"])) {
						$privacylevel = intval($piholeFTLConf["PRIVACYLEVEL"]);
					} else {
						$privacylevel = 0;
					}

					// Store privacy level
					exec("sudo pihole -a privacylevel ".$level);

					if($privacylevel > $level)
					{
						exec("sudo pihole -a restartdns");
						$success .= "隐私级别已降低，DNS 解析器已经重新启动。";
					}
					elseif($privacylevel < $level)
					{
						$success .= "隐私级别已提升。";
					}
					else
					{
						$success .= "隐私级别未改变。";
					}
				}
				else
				{
					$error .= "无效的隐私级别 （".$level."）！";
				}
				break;
			// Flush network table
			case "flusharp":
				exec("sudo pihole arpflush quiet", $output);
				$error = implode("<br>", $output);
				if(strlen($error) == 0)
				{
					$success .= "网络表已刷新。";
				}
				break;

			default:
				// Option not found
				$debug = true;
				break;
		}
	}

	// Credit: http://stackoverflow.com/a/5501447/2087442
	function formatSizeUnits($bytes)
	{
		if ($bytes >= 1073741824)
		{
			$bytes = number_format($bytes / 1073741824, 2) . ' GB';
		}
		elseif ($bytes >= 1048576)
		{
			$bytes = number_format($bytes / 1048576, 2) . ' MB';
		}
		elseif ($bytes >= 1024)
		{
			$bytes = number_format($bytes / 1024, 2) . ' kB';
		}
		elseif ($bytes > 1)
		{
			$bytes = $bytes . ' bytes';
		}
		elseif ($bytes == 1)
		{
			$bytes = $bytes . ' byte';
		}
		else
		{
			$bytes = '0 bytes';
		}

		return $bytes;
	}
?>
