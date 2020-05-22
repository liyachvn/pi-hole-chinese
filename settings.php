<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
require "scripts/pi-hole/php/header.php";
require "scripts/pi-hole/php/savesettings.php";
// Reread ini file as things might have been changed
$setupVars = parse_ini_file("/etc/pihole/setupVars.conf");
if(is_readable($piholeFTLConfFile))
{
	$piholeFTLConf = parse_ini_file($piholeFTLConfFile);
}
else
{
	$piholeFTLConf = array();
}

// Handling of PHP internal errors
$last_error = error_get_last();
if($last_error["type"] === E_WARNING || $last_error["type"] === E_ERROR)
{
	$error .= "There was a problem applying your settings.<br>Debugging information:<br>PHP error (".htmlspecialchars($last_error["type"])."): ".htmlspecialchars($last_error["message"])." in ".htmlspecialchars($last_error["file"]).":".htmlspecialchars($last_error["line"]);
}

?>
<style>
	.tooltip-inner {
		max-width: none;
		white-space: nowrap;
	}
</style>

<?php // Check if ad lists should be updated after saving ...
if (isset($_POST["submit"])) {
    if ($_POST["submit"] == "saveupdate") {
        // If that is the case -> refresh to the gravity page and start updating immediately
        ?>
        <meta http-equiv="refresh" content="1;url=gravity.php?go">
    <?php }
} ?>

<?php if (isset($debug)) { ?>
    <div id="alDebug" class="alert alert-warning alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-hide="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
        </button>
        <h4><i class="icon fa fa-exclamation-triangle"></i> Debug</h4>
        <pre><?php print_r($_POST); ?></pre>
    </div>
<?php } ?>

<?php if (strlen($success) > 0) { ?>
    <div id="alInfo" class="alert alert-info alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-hide="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
        </button>
        <h4><i class="icon fa fa-info"></i> Info</h4>
        <?php echo $success; ?>
    </div>
<?php } ?>

<?php if (strlen($error) > 0) { ?>
    <div id="alError" class="alert alert-danger alert-dismissible fade in" role="alert">
        <button type="button" class="close" data-hide="alert" aria-label="Close"><span aria-hidden="true">&times;</span>
        </button>
        <h4><i class="icon fa fa-ban"></i> Error</h4>
        <?php echo $error; ?>
    </div>
<?php } ?>


<?php
// Networking
if (isset($setupVars["PIHOLE_INTERFACE"])) {
    $piHoleInterface = $setupVars["PIHOLE_INTERFACE"];
} else {
    $piHoleInterface = "unknown";
}
if (isset($setupVars["IPV4_ADDRESS"])) {
    $piHoleIPv4 = $setupVars["IPV4_ADDRESS"];
} else {
    $piHoleIPv4 = "unknown";
}
$IPv6connectivity = false;
if (isset($setupVars["IPV6_ADDRESS"])) {
    $piHoleIPv6 = $setupVars["IPV6_ADDRESS"];
    sscanf($piHoleIPv6, "%2[0-9a-f]", $hexstr);
    if (strlen($hexstr) == 2) {
        // Convert HEX string to number
        $hex = hexdec($hexstr);
        // Global Unicast Address (2000::/3, RFC 4291)
        $GUA = (($hex & 0x70) === 0x20);
        // Unique Local Address   (fc00::/7, RFC 4193)
        $ULA = (($hex & 0xfe) === 0xfc);
        if ($GUA || $ULA) {
            // Scope global address detected
            $IPv6connectivity = true;
        }
    }
} else {
    $piHoleIPv6 = "unknown";
}
$hostname = trim(file_get_contents("/etc/hostname"), "\x00..\x1F");
?>

<?php
// DNS settings
$DNSservers = [];
$DNSactive = [];

$i = 1;
while (isset($setupVars["PIHOLE_DNS_" . $i])) {
    if (isinserverlist($setupVars["PIHOLE_DNS_" . $i])) {
        array_push($DNSactive, $setupVars["PIHOLE_DNS_" . $i]);
    } elseif (strpos($setupVars["PIHOLE_DNS_" . $i], ".") !== false) {
        if (!isset($custom1)) {
            $custom1 = $setupVars["PIHOLE_DNS_" . $i];
        } else {
            $custom2 = $setupVars["PIHOLE_DNS_" . $i];
        }
    } elseif (strpos($setupVars["PIHOLE_DNS_" . $i], ":") !== false) {
        if (!isset($custom3)) {
            $custom3 = $setupVars["PIHOLE_DNS_" . $i];
        } else {
            $custom4 = $setupVars["PIHOLE_DNS_" . $i];
        }
    }
    $i++;
}

if (isset($setupVars["DNS_FQDN_REQUIRED"])) {
    if ($setupVars["DNS_FQDN_REQUIRED"]) {
        $DNSrequiresFQDN = true;
    } else {
        $DNSrequiresFQDN = false;
    }
} else {
    $DNSrequiresFQDN = true;
}

if (isset($setupVars["DNS_BOGUS_PRIV"])) {
    if ($setupVars["DNS_BOGUS_PRIV"]) {
        $DNSbogusPriv = true;
    } else {
        $DNSbogusPriv = false;
    }
} else {
    $DNSbogusPriv = true;
}

if (isset($setupVars["DNSSEC"])) {
    if ($setupVars["DNSSEC"]) {
        $DNSSEC = true;
    } else {
        $DNSSEC = false;
    }
} else {
    $DNSSEC = false;
}

if (isset($setupVars["DNSMASQ_LISTENING"])) {
    if ($setupVars["DNSMASQ_LISTENING"] === "single") {
        $DNSinterface = "single";
    } elseif ($setupVars["DNSMASQ_LISTENING"] === "all") {
        $DNSinterface = "all";
    } else {
        $DNSinterface = "local";
    }
} else {
    $DNSinterface = "single";
}
if (isset($setupVars["CONDITIONAL_FORWARDING"]) && ($setupVars["CONDITIONAL_FORWARDING"] == 1)) {
    $conditionalForwarding = true;
    $conditionalForwardingDomain = $setupVars["CONDITIONAL_FORWARDING_DOMAIN"];
    $conditionalForwardingIP = $setupVars["CONDITIONAL_FORWARDING_IP"];
} else {
    $conditionalForwarding = false;
}
?>

<?php
// Query logging
if (isset($setupVars["QUERY_LOGGING"])) {
    if ($setupVars["QUERY_LOGGING"] == 1) {
        $piHoleLogging = true;
    } else {
        $piHoleLogging = false;
    }
} else {
    $piHoleLogging = true;
}
?>

<?php
// Excluded domains in API Query Log call
if (isset($setupVars["API_EXCLUDE_DOMAINS"])) {
    $excludedDomains = explode(",", $setupVars["API_EXCLUDE_DOMAINS"]);
} else {
    $excludedDomains = [];
}

// Exluded clients in API Query Log call
if (isset($setupVars["API_EXCLUDE_CLIENTS"])) {
    $excludedClients = explode(",", $setupVars["API_EXCLUDE_CLIENTS"]);
} else {
    $excludedClients = [];
}

// Exluded clients
if (isset($setupVars["API_QUERY_LOG_SHOW"])) {
    $queryLog = $setupVars["API_QUERY_LOG_SHOW"];
} else {
    $queryLog = "all";
}

// Privacy Mode
if (isset($setupVars["API_PRIVACY_MODE"])) {
    $privacyMode = $setupVars["API_PRIVACY_MODE"];
} else {
    $privacyMode = false;
}

?>

<?php
if (isset($_GET['tab']) && in_array($_GET['tab'], array("sysadmin", "blocklists", "dns", "piholedhcp", "api", "privacy", "teleporter"))) {
    $tab = $_GET['tab'];
} else {
    $tab = "sysadmin";
}
?>
<div class="row justify-content-md-center">
    <div class="col-md-12">
        <div class="nav-tabs-custom">
            <ul class="nav nav-tabs">
                <li<?php if($tab === "sysadmin"){ ?> class="active"<?php } ?>><a data-toggle="tab" href="#sysadmin">系统</a></li>
                <li<?php if($tab === "blocklists"){ ?> class="active"<?php } ?>><a data-toggle="tab" href="#blocklists">阻止列表</a></li>
                <li<?php if($tab === "dns"){ ?> class="active"<?php } ?>><a data-toggle="tab" href="#dns">DNS</a></li>
                <li<?php if($tab === "piholedhcp"){ ?> class="active"<?php } ?>><a data-toggle="tab" href="#piholedhcp">DHCP</a></li>
                <li<?php if($tab === "api"){ ?> class="active"<?php } ?>><a data-toggle="tab" href="#api">API / Web 界面</a></li>
                <li<?php if($tab === "privacy"){ ?> class="active"<?php } ?>><a data-toggle="tab" href="#privacy">隐私</a></li>
                <li<?php if($tab === "teleporter"){ ?> class="active"<?php } ?>><a data-toggle="tab" href="#teleporter">传送器</a></li>
            </ul>
            <div class="tab-content">
                <!-- ######################################################### Blocklists ######################################################### -->
                <div id="blocklists" class="tab-pane fade<?php if($tab === "blocklists"){ ?> in active<?php } ?>">
                    <form role="form" method="post">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="box">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">阻止列表用来生成 Pi-hole 的规则</h3>
                                    </div>
                                    <div class="box-body">
                                        <p>请转到<a href="groups-adlists.php">群组管理页面</a>编辑 Pi-hole 使用的阻止列表。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- ######################################################### DHCP ######################################################### -->
                <div id="piholedhcp" class="tab-pane fade<?php if($tab === "piholedhcp"){ ?> in active<?php } ?>">
                    <?php
                    // Pi-hole DHCP server
                    if (isset($setupVars["DHCP_ACTIVE"])) {
                        if ($setupVars["DHCP_ACTIVE"] == 1) {
                            $DHCP = true;
                        } else {
                            $DHCP = false;
                        }
                        // Read setings from config file
                        if (isset($setupVars["DHCP_START"])) {
                            $DHCPstart = $setupVars["DHCP_START"];
                        } else {
                            $DHCPstart = "";
                        }
                        if (isset($setupVars["DHCP_END"])) {
                            $DHCPend = $setupVars["DHCP_END"];
                        } else {
                            $DHCPend = "";
                        }
                        if (isset($setupVars["DHCP_ROUTER"])) {
                            $DHCProuter = $setupVars["DHCP_ROUTER"];
                        } else {
                            $DHCProuter = "";
                        }

                        // This setting has been added later, we have to check if it exists
                        if (isset($setupVars["DHCP_LEASETIME"])) {
                            $DHCPleasetime = $setupVars["DHCP_LEASETIME"];
                            if (strlen($DHCPleasetime) < 1) {
                                // Fallback if empty string
                                $DHCPleasetime = 24;
                            }
                        } else {
                            $DHCPleasetime = 24;
                        }
                        if (isset($setupVars["DHCP_IPv6"])) {
                            $DHCPIPv6 = $setupVars["DHCP_IPv6"];
                        } else {
                            $DHCPIPv6 = false;
                        }
                        if (isset($setupVars["DHCP_rapid_commit"])) {
                            $DHCP_rapid_commit = $setupVars["DHCP_rapid_commit"];
                        } else {
                            $DHCP_rapid_commit = false;
                        }

                    } else {
                        $DHCP = false;
                        // Try to guess initial settings
                        if ($piHoleIPv4 !== "unknown") {
                            $DHCPdomain = explode(".", $piHoleIPv4);
                            $DHCPstart = $DHCPdomain[0] . "." . $DHCPdomain[1] . "." . $DHCPdomain[2] . ".201";
                            $DHCPend = $DHCPdomain[0] . "." . $DHCPdomain[1] . "." . $DHCPdomain[2] . ".251";
                            $DHCProuter = $DHCPdomain[0] . "." . $DHCPdomain[1] . "." . $DHCPdomain[2] . ".1";
                        } else {
                            $DHCPstart = "";
                            $DHCPend = "";
                            $DHCProuter = "";
                        }
                        $DHCPleasetime = 24;
                        $DHCPIPv6 = false;
                        $DHCP_rapid_commit = false;
                    }
                    if (isset($setupVars["PIHOLE_DOMAIN"])) {
                        $piHoleDomain = $setupVars["PIHOLE_DOMAIN"];
                    } else {
                        $piHoleDomain = "lan";
                    }
                    ?>
                    <form role="form" method="post">
                        <div class="row">
                            <!-- DHCP Settings Box -->
                            <div class="col-md-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">DHCP 设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="active" id="DHCPchk"
                                                                      <?php if ($DHCP){ ?>checked<?php }
                                                                      ?>>启用 DHCP 服务器</label>
                                                    </div>
                                                </div>
                                                <p id="dhcpnotice" <?php if (!$DHCP){ ?>hidden<?php }
                                                                   ?>>当您开启 Pi-hole 的 DHCP 服务器时，请确认关闭了您路由器中的 DHCP 功能！</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12">
                                                <label>分配的 IP 地址范围</label>
                                            </div>
                                            <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6">
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">从</div>
                                                        <input type="text" class="form-control DHCPgroup" name="from"
                                                               value="<?php echo $DHCPstart; ?>"
                                                               <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6">
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">到</div>
                                                        <input type="text" class="form-control DHCPgroup" name="to"
                                                               value="<?php echo $DHCPend; ?>"
                                                               <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>路由器（默认网关）IP 地址</label>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">路由器</div>
                                                        <input type="text" class="form-control DHCPgroup" name="router"
                                                               value="<?php echo $DHCProuter; ?>"
                                                               <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Advanced DHCP Settings Box -->
                            <div class="col-md-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">DHCP 高级设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>Pi-hole 域名称</label>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">域名</div>
                                                        <input type="text" class="form-control DHCPgroup" name="domain"
                                                               value="<?php echo $piHoleDomain; ?>"
                                                               <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>DHCP 租约时间</label>
                                                <div class="form-group">
                                                    <div class="input-group">
                                                        <div class="input-group-addon">租约时间（以小时计）</div>
                                                        <input type="text" class="form-control DHCPgroup"
                                                               name="leasetime"
                                                               id="leasetime" value="<?php echo $DHCPleasetime; ?>"
                                                               data-mask <?php if (!$DHCP){ ?>disabled<?php } ?>>
                                                    </div>
                                                </div>
                                                <p>提示: 0 = 无限，24 = 一天，168 = 一周，744 = 一个月，8760 = 一年</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="useIPv6" class="DHCPgroup"
                                                                      <?php if ($DHCPIPv6){ ?>checked<?php };
                                                                            if (!$DHCP){ ?> disabled<?php }
                                                                      ?>>启用 IPv6 支持（SLAAC + RA）</label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="DHCP_rapid_commit" class="DHCPgroup"
                                                                      <?php if ($DHCP_rapid_commit){ ?>checked<?php };
                                                                            if (!$DHCP){ ?> disabled<?php }
                                                                      ?>>启用 DHCP 快速提交（快速地址分配）</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DHCP Leases Box -->
                        <div class="row">
                            <?php
                            $dhcp_leases = array();
                            if ($DHCP) {
                                // Read leases file
                                $leasesfile = true;
                                $dhcpleases = @fopen('/etc/pihole/dhcp.leases', 'r');
                                if (!is_resource($dhcpleases))
                                    $leasesfile = false;

                                function convertseconds($argument)
                                {
                                    $seconds = round($argument);
                                    if ($seconds < 60) {
                                        return sprintf('%ds', $seconds);
                                    } elseif ($seconds < 3600) {
                                        return sprintf('%dm %ds', ($seconds / 60), ($seconds % 60));
                                    } elseif ($seconds < 86400) {
                                        return sprintf('%dh %dm %ds', ($seconds / 3600 % 24), ($seconds / 60 % 60), ($seconds % 60));
                                    } else {
                                        return sprintf('%dd %dh %dm %ds', ($seconds / 86400), ($seconds / 3600 % 24), ($seconds / 60 % 60), ($seconds % 60));
                                    }
                                }

                                while (!feof($dhcpleases) && $leasesfile) {
                                    $line = explode(" ", trim(fgets($dhcpleases)));
                                    if (count($line) == 5) {
                                        $counter = intval($line[0]);
                                        if ($counter == 0) {
                                            $time = "Infinite";
                                        } elseif ($counter <= 315360000) // 10 years in seconds
                                        {
                                            $time = convertseconds($counter);
                                        } else // Assume time stamp
                                        {
                                            $time = convertseconds($counter - time());
                                        }

                                        if (strpos($line[2], ':') !== false) {
                                            // IPv6 address
                                            $type = 6;
                                        } else {
                                            // IPv4 lease
                                            $type = 4;
                                        }

                                        $host = $line[3];
                                        if ($host == "*") {
                                            $host = "<i>unknown</i>";
                                        }

                                        $clid = $line[4];
                                        if ($clid == "*") {
                                            $clid = "<i>unknown</i>";
                                        }

                                        array_push($dhcp_leases, ["TIME" => $time, "hwaddr" => strtoupper($line[1]), "IP" => $line[2], "host" => $host, "clid" => $clid, "type" => $type]);
                                    }
                                }
                            }

                            readStaticLeasesFile();
                            ?>
                            <div class="col-md-12">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">DHCP 租约</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label>当前活动的 DHCP 租约</label>
                                                <table id="DHCPLeasesTable" class="table table-striped table-bordered dt-responsive nowrap"
                                                       cellspacing="0" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>MAC 地址</th>
                                                            <th>IP 地址</th>
                                                            <th>主机名称</th>
                                                            <td></td>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($dhcp_leases as $lease) { ?>
                                                        <tr data-placement="auto" data-container="body" data-toggle="tooltip"
                                                            title="Lease type: IPv<?php echo $lease["type"]; ?><br/>Remaining lease time: <?php echo $lease["TIME"]; ?><br/>DHCP UID: <?php echo $lease["clid"]; ?>">
                                                            <td id="MAC"><?php echo $lease["hwaddr"]; ?></td>
                                                            <td id="IP" data-order="<?php echo bin2hex(inet_pton($lease["IP"])); ?>"><?php echo $lease["IP"]; ?></td>
                                                            <td id="HOST"><?php echo $lease["host"]; ?></td>
                                                            <td>
                                                                <button class="btn btn-warning btn-xs" type="button" id="button" data-static="alert">
                                                                    <span class="glyphicon glyphicon-copy"></span>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                                <br>
                                            </div>
                                            <div class="col-md-12">
                                                <label>静态 DHCP 租约设置</label>
                                                <table id="DHCPStaticLeasesTable" class="table table-striped table-bordered dt-responsive nowrap"
                                                       cellspacing="0" width="100%">
                                                    <thead>
                                                    <tr>
                                                        <th>MAC 地址</th>
                                                        <th>IP 地址</th>
                                                        <th>主机名称</th>
                                                        <td></td>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($dhcp_static_leases as $lease) { ?>
                                                        <tr>
                                                            <td><?php echo $lease["hwaddr"]; ?></td>
                                                            <td data-order="<?php echo bin2hex(inet_pton($lease["IP"])); ?>"><?php echo $lease["IP"]; ?></td>
                                                            <td><?php echo $lease["host"]; ?></td>
                                                            <td><?php if (strlen($lease["hwaddr"]) > 0) { ?>
                                                                <button class="btn btn-danger btn-xs" type="submit" name="removestatic"
                                                                        value="<?php echo $lease["hwaddr"]; ?>">
                                                                    <span class="glyphicon glyphicon-trash"></span>
                                                                </button>
                                                                <?php } ?>
                                                            </td>
                                                        </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                    <tfoot style="display: table-row-group">
                                                        <tr>
                                                            <td><input type="text" name="AddMAC"></td>
                                                            <td><input type="text" name="AddIP"></td>
                                                            <td><input type="text" name="AddHostname" value=""></td>
                                                            <td>
                                                                <button class="btn btn-success btn-xs" type="submit" name="addstatic">
                                                                    <span class="glyphicon glyphicon-plus"></span>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                                <p>必须指定 MAC 地址，并且每个 MAC 地址仅允许一个条目。如果省略 IP 地址并给出主机名，则该 IP 地址仍将动态生成，并使用指定的主机名。如果省略主机名，将仅添加静态租约。</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="field" value="DHCP">
                                <input type="hidden" name="token" value="<?php echo $token ?>">
                                <button type="submit" class="btn btn-primary pull-right">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- ######################################################### DNS ######################################################### -->
                <div id="dns" class="tab-pane fade<?php if($tab === "dns"){ ?> in active<?php } ?>">
                    <form role="form" method="post">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h1 class="box-title">上游 DNS 服务器</h1>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th colspan="2">IPv4</th>
                                                        <th colspan="2">IPv6</th>
                                                        <th>名称</th>
                                                    </tr>
                                                    <?php foreach ($DNSserverslist as $key => $value) { ?>
                                                    <tr>
                                                    <?php if (isset($value["v4_1"])) { ?>
                                                        <td title="<?php echo $value["v4_1"]; ?>">
                                                            <input type="checkbox" name="DNSserver<?php echo $value["v4_1"]; ?>" value="true"
                                                                   <?php if (in_array($value["v4_1"], $DNSactive)){ ?>checked<?php } ?>>
                                                        </td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                    <?php if (isset($value["v4_2"])) { ?>
                                                        <td title="<?php echo $value["v4_2"]; ?>">
                                                            <input type="checkbox" name="DNSserver<?php echo $value["v4_2"]; ?>" value="true"
                                                                   <?php if (in_array($value["v4_2"], $DNSactive)){ ?>checked<?php } ?>>
                                                        </td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                    <?php if (isset($value["v6_1"])) { ?>
                                                        <td title="<?php echo $value["v6_1"]; ?>">
                                                            <input type="checkbox" name="DNSserver<?php echo $value["v6_1"]; ?>" value="true"
                                                                   <?php if (in_array($value["v6_1"], $DNSactive) && $IPv6connectivity){ ?>checked<?php }
                                                                         if (!$IPv6connectivity) { ?> disabled <?php } ?>>
                                                        </td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                    <?php if (isset($value["v6_2"])) { ?>
                                                        <td title="<?php echo $value["v6_2"]; ?>">
                                                            <input type="checkbox" name="DNSserver<?php echo $value["v6_2"]; ?>" value="true"
                                                                   <?php if (in_array($value["v6_2"], $DNSactive) && $IPv6connectivity){ ?>checked<?php }
                                                                if (!$IPv6connectivity) { ?> disabled <?php } ?>>
                                                        </td>
                                                    <?php } else { ?>
                                                        <td></td>
                                                    <?php } ?>
                                                        <td><?php echo $key; ?></td>
                                                    </tr>
                                                    <?php } ?>
                                                </table>
                                                <p>ECS（扩展客户端子网）定义了一种机制，用于递归解析器将部分客户端 IP 地址信息发送到权威 DNS 名称服务器。当响应通过公共 DNS 解析器进行名称查找时，内容传递网络 （CDNs）和对延迟敏感的服务可使用它来提供地理位置响应。<em>请注意，ECS 可能会导致隐私性降低。</em></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h1 class="box-title">上游 DNS 服务器</h1>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>自定义服务器一（IPv4）</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon">
                                                            <input type="checkbox" name="custom1" value="Customv4"
                                                                   <?php if (isset($custom1)){ ?>checked<?php } ?>>
                                                        </div>
                                                        <input type="text" name="custom1val" class="form-control"
                                                               <?php if (isset($custom1)){ ?>value="<?php echo $custom1; ?>"<?php } ?>>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>自定义服务器二（IPv4）</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon">
                                                            <input type="checkbox" name="custom2" value="Customv4"
                                                                   <?php if (isset($custom2)){ ?>checked<?php } ?>>
                                                        </div>
                                                        <input type="text" name="custom2val" class="form-control"
                                                               <?php if (isset($custom2)){ ?>value="<?php echo $custom2; ?>"<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>自定义服务器三（IPv6）</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon">
                                                            <input type="checkbox" name="custom3" value="Customv6"
                                                                   <?php if (isset($custom3)){ ?>checked<?php } ?>>
                                                        </div>
                                                        <input type="text" name="custom3val" class="form-control"
                                                               <?php if (isset($custom3)){ ?>value="<?php echo $custom3; ?>"<?php } ?>>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>自定义服务器四（IPv6）</label>
                                                    <div class="input-group">
                                                        <div class="input-group-addon">
                                                            <input type="checkbox" name="custom4" value="Customv6"
                                                                   <?php if (isset($custom4)){ ?>checked<?php } ?>>
                                                        </div>
                                                        <input type="text" name="custom4val" class="form-control"
                                                               <?php if (isset($custom4)){ ?>value="<?php echo $custom4; ?>"<?php } ?>>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h1 class="box-title">接口监听动作</h1>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-group">
                                                    <div class="radio">
                                                        <label><input type="radio" name="DNSinterface" value="local"
                                                                      <?php if ($DNSinterface == "local"){ ?>checked<?php } ?>>
                                                               <strong>在所有接口监听</strong>
                                                               <br>仅允许查询距离最多为一跳的设备（本地设备）</label>
                                                    </div>
                                                    <div class="radio">
                                                        <label><input type="radio" name="DNSinterface" value="single"
                                                                      <?php if ($DNSinterface == "single"){ ?>checked<?php } ?>>
                                                               <strong>只监听 <?php echo htmlentities($piHoleInterface); ?> 接口</strong>
                                                        </label>
                                                    </div>
                                                    <div class="radio">
                                                        <label><input type="radio" name="DNSinterface" value="all"
                                                                      <?php if ($DNSinterface == "all"){ ?>checked<?php } ?>>
                                                               <strong>在所有接口监听，允许所有来源</strong>
                                                        </label>
                                                    </div>
                                                </div>
                                                <p>请注意，最后一个选项不应在直接连接到 Internet 的设备上使用。如果您的 Pi-hole 位于本地网络中，即在路由器后面受到保护，并且尚未将端口 53 转发到此设备，则此选项是安全的。在几乎所有其它情况下，您都必须确保对 Pi-hole 进行了适当的防火墙保护。</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">DNS 高级设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="DNSrequiresFQDN" title="domain-needed"
                                                                      <?php if ($DNSrequiresFQDN){ ?>checked<?php }
                                                                      ?>>从不转发 non-FQDNs</label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="DNSbogusPriv" title="bogus-priv"
                                                                      <?php if ($DNSbogusPriv){ ?>checked<?php }
                                                                      ?>>从不转发私有 IP 范围的反向查询</label>
                                                    </div>
                                                </div>
                                                <p>请注意，启用这两个选项可能会提高您的隐私度，但是如果未将 Pi-hole 用作 DHCP 服务器，则可能会阻止您访问本地主机名。</p>
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="DNSSEC"
                                                                      <?php if ($DNSSEC){ ?>checked<?php }
                                                                      ?>>使用 DNSSEC</label>
                                                    </div>
                                                </div>
                                                <p>验证 DNS 回应并缓存 DNSSEC 数据。在转发 DNS 查询时，Pi-hole 会请求验证答复所需的 DNSSEC 记录。如果域验证失败或上游不支持 DNSSEC，则此设置可能导致解析域的问题。激活 DNSSEC 时，请使用 Google、Cloudflare、DNS.WATCH、Quad9，或者其它支持 DNSSEC 的 DNS 服务器。请注意，启用DNSSEC 时，日志的大小可能会大大增加。您可以在<a href="https://dnssec.vs.uni-due.de/" rel="noopener" target="_blank">这里</a>找到 DNSSEC 解析器的测试。</p>
                                                <label>条件转发</label>
                                                <p>如果未配置为 DHCP 服务器，Pi-hole 将无法确定本地网络上的设备名称。这样的结果是，诸如“活跃客户端”之类的表将仅显示 IP 地址。</p>
                                                <p>一种解决方案是，将 Pi-hole 配置为将这些请求转发到 DHCP server （很可能是路由器），但仅将其转发给家庭网络中的设备。要进行配置，我们需要知道您的 DHCP 服务器的 IP 地址和您的本地网络的名称</p>
                                                <p>注意：本地域名必须与 DHCP 服务器中指定的域名匹配，该域名一般可以在 DHCP 设置中找到。</p>
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="conditionalForwarding" value="conditionalForwarding"
                                                        <?php if(isset($conditionalForwarding) && ($conditionalForwarding == true)){ ?>checked<?php }
                                                        ?>>使用条件转发</label>
                                                    </div>
                                                    <div class="input-group">
                                                      <table class="table table-bordered">
                                                        <tr>
                                                          <th>路由器 IP 地址</th>
                                                          <th>本地域名称</th>
                                                        </tr>
                                                        <tr>
                                                          <div class="input-group">
                                                            <td>
                                                              <input type="text" name="conditionalForwardingIP" class="form-control"
                                                              <?php if(isset($conditionalForwardingIP)){ ?>value="<?php echo $conditionalForwardingIP; ?>"<?php } ?>>
                                                            </td>
                                                            <td><input type="text" name="conditionalForwardingDomain" class="form-control" data-mask
                                                              <?php if(isset($conditionalForwardingDomain)){ ?>value="<?php echo $conditionalForwardingDomain; ?>"<?php } ?>>
                                                            </td>
                                                          </div>
                                                        </tr>
                                                      </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="field" value="DNS">
                                <input type="hidden" name="token" value="<?php echo $token ?>">
                                <button type="submit" class="btn btn-primary pull-right">保存</button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- ######################################################### API and Web ######################################################### -->
                <?php
                // CPU temperature unit
                if (isset($setupVars["TEMPERATUREUNIT"])) {
                    $temperatureunit = $setupVars["TEMPERATUREUNIT"];
                } else {
                    $temperatureunit = "C";
                }

                // Administrator email address
                if (isset($setupVars["ADMIN_EMAIL"])) {
                    $adminemail = $setupVars["ADMIN_EMAIL"];
                } else {
                    $adminemail = "";
                }
                ?>
                <div id="api" class="tab-pane fade<?php if($tab === "api"){ ?> in active<?php } ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <form role="form" method="post">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">API 设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4>活跃列表</h4>
                                                <p>排除以下域</p>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6">
                                                <div class="form-group">
                                                    <label>活跃域/活跃广告商</label>
                                                    <textarea name="domains" class="form-control" placeholder="Enter one domain per line"
                                                              rows="4"><?php foreach ($excludedDomains as $domain) {
                                                                             echo $domain . "\n"; }
                                                                       ?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-xs-12 col-sm-6 col-md-12 col-lg-6">
                                                <div class="form-group">
                                                    <label>活跃客户端</label>
                                                    <textarea name="clients" class="form-control" placeholder="Enter one IP address or host name per line"
                                                              rows="4"><?php foreach ($excludedClients as $client) {
                                                                             echo $client . "\n"; }
                                                                       ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                            <h4>查询日志</h4>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <div class="checkbox"><label><input type="checkbox" name="querylog-permitted" <?php if($queryLog === "permittedonly" || $queryLog === "all"){ ?>checked<?php } ?>> 显示允许的域条目</label></div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <div class="checkbox"><label><input type="checkbox" name="querylog-blocked" <?php if($queryLog === "blockedonly" || $queryLog === "all"){ ?>checked<?php } ?>> 显示阻止的域条目</label></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer clearfix">
                                        <input type="hidden" name="field" value="API">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                        <button type="button" class="btn btn-primary api-token">显示 API 令牌</button>
                                        <button type="submit" class="btn btn-primary pull-right">保存</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form role="form" method="post">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">Web 界面设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4>界面外观</h4>
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="boxedlayout" value="yes"
                                                                      <?php if ($boxedlayout){ ?>checked<?php }
                                                                      ?>>使用磁贴式式布局（在大尺寸屏幕上效果更好）</label>
                                                    </div>
                                                </div>
                                                <h4>CPU 温度单位</h4>
                                                <div class="form-group">
                                                    <div class="radio">
                                                        <label><input type="radio" name="tempunit" value="C"
                                                                      <?php if ($temperatureunit === "C"){ ?>checked<?php }
                                                                      ?>>摄氏度</label>
                                                    </div>
                                                    <div class="radio">
                                                        <label><input type="radio" name="tempunit" value="K"
                                                                      <?php if ($temperatureunit === "K"){ ?>checked<?php }
									?>>开氏度</label>
						    </div>
                                                    <div class="radio">
                                                        <label><input type="radio" name="tempunit" value="F"
                                                                      <?php if ($temperatureunit === "F"){ ?>checked<?php }
                                                                      ?>>华氏度</label>
                                                    </div>
                                                </div>
                                                <h4>管理员邮件地址</h4>
                                                <div class="form-group">
                                                    <input type="text" class="form-control" name="adminemail"
                                                           value="<?php echo htmlspecialchars($adminemail); ?>">
                                                </div>
                                                <input type="hidden" name="field" value="webUI">
                                                <input type="hidden" name="token" value="<?php echo $token ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer clearfix">
                                        <button type="submit" class="btn btn-primary pull-right">保存</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- ######################################################### Privacy (may be expanded further later on) ######################################################### -->
                <?php
                // Get privacy level from piholeFTL config array
                if (isset($piholeFTLConf["PRIVACYLEVEL"])) {
                    $privacylevel = intval($piholeFTLConf["PRIVACYLEVEL"]);
                } else {
                    $privacylevel = 0;
                }
                ?>
                <div id="privacy" class="tab-pane fade<?php if($tab === "privacy"){ ?> in active<?php } ?>">
                    <div class="row">
                        <div class="col-md-12">
                            <form role="form" method="post">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">隐私设置</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h4>DNS 解析器隐私级别</h4>
                                                <p>指定是否应匿名使用 DNS 查询，可用选项有：
                                                <div class="form-group">
                                                    <div class="radio">
                                                        <label><input type="radio" name="privacylevel" value="0"
                                                                      <?php if ($privacylevel === 0){ ?>checked<?php }
                                                                      ?>>显示并记录全部。<br>提供最大的统计量。</label>
                                                    </div>
                                                    <div class="radio">
                                                        <label><input type="radio" name="privacylevel" value="1"
                                                                      <?php if ($privacylevel === 1){ ?>checked<?php }
                                                                      ?>>隐藏域：显示所有域并将其存储为“隐藏”。<br>此选项会禁用仪表板上的“活跃域名”和“活跃广告”表。</label>
                                                    </div>
                                                    <div class="radio">
                                                        <label><input type="radio" name="privacylevel" value="2"
                                                                      <?php if ($privacylevel === 2){ ?>checked<?php }
                                                                      ?>>隐藏域和客户端：显示所有域并将其存储为“隐藏”，将所有客户端存储为“0.0.0.0”。<br>此选项会禁用仪表板上的所有表。</label>
                                                    </div>
                                                    <div class="radio">
                                                        <label><input type="radio" name="privacylevel" value="3"
                                                                      <?php if ($privacylevel === 3){ ?>checked<?php }
                                                                      ?>>匿名模式：基本上禁用了实时匿名统计信息以外的所有功能。<br>历史记录根本没有保存到数据库，查询日志中什么也不显示。此外，也没有任何活跃列表。</label>
                                                    </div>
                                                    <div class="radio">
                                                        <label><input type="radio" name="privacylevel" value="4"
                                                                      <?php if ($privacylevel === 4){ ?>checked<?php }
                                                            ?>>无统计信息模式：这将禁用所有统计信息处理功能。甚至查询计数器也将不可用。<br><strong>请注意，禁用查询分析时，正则表达式阻止功能不可用。</strong><br>另外，您可以使用 <code> sudo pihole logging off</code> 命令禁用写入日志文件 <code>/var/log/pihole.log</code>。</label>
                                                    </div>
                                                </div>
                                                <p>您可以随时提高隐私级别，而不必重新启动 DNS 服务器。但是要注意的是，降低隐私级别时，需要重新启动 DNS 服务器。保存时会自动完成重启动作。</p>
                                                <?php if($privacylevel > 0 && $piHoleLogging){ ?>
                                                <p class="lookatme">警告：Pi-hole 的查询记录功能已启用。尽管仪表板将隐藏请求的详细信息，但所有查询仍会完全记录到 pihole.log 文件中。</p>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="box-footer clearfix">
                                        <input type="hidden" name="field" value="privacyLevel">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                        <button type="submit" class="btn btn-primary pull-right">应用</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- ######################################################### Teleporter ######################################################### -->
                <div id="teleporter" class="tab-pane fade<?php if($tab === "teleporter"){ ?> in active<?php } ?>">
                    <div class="row">
                        <?php if (extension_loaded('Phar')) { ?>
                        <form role="form" method="post" id="takeoutform"
                              action="scripts/pi-hole/php/teleporter.php"
                              target="_blank" enctype="multipart/form-data">
                            <input type="hidden" name="token" value="<?php echo $token ?>">
                            <div class="col-lg-6 col-md-12">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">传送器导出</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <p>将您的 Pi-hole 列表导出为可下载的存档文件。</p>
                                                <button type="submit" class="btn btn-default">导出</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-12">
                                <div class="box box-warning">
                                    <div class="box-header with-border">
                                        <h3 class="box-title">传送器导入</h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="row">
                                            <div class="col-lg-6 col-md-12">
                                                <label>导入……</label>
                                                <div class="form-group">
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="whitelist" value="true"
                                                                      checked>
                                                            白名单（精确）</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="regex_whitelist" value="true"
                                                                      checked>
                                                            白名单（正则表达式/通配符）</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="blacklist" value="true"
                                                                      checked>
                                                            黑名单（精确）</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="regexlist" value="true"
                                                                      checked>
                                                            黑名单（正则表达式/通配符）</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="adlist" value="true"
                                                                      checked>
                                                            阻止列表</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="auditlog" value="true"
                                                                      checked>
                                                            审核日志</label>
                                                    </div>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="staticdhcpleases" value="true"
                                                                      checked>
                                                            静态 DHCP 租约</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <div class="form-group">
                                                    <label for="zip_file">导入文件</label>
                                                    <input type="file" name="zip_file" id="zip_file">
                                                    <p class="help-block">只上传 Pi-hole 备份文件。</p>
                                                    <button type="submit" class="btn btn-default" name="action"
                                                            value="in">导入
                                                    </button>
                                                    <div class="checkbox">
                                                        <label><input type="checkbox" name="flushtables" value="true"
                                                                      checked>
                                                            清除已存在的数据</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php } else { ?>
                        <div class="col-lg-12">
                            <div class="box box-warning">
                                <div class="box-header with-border">
                                    <h3 class="box-title">传送器</h3>
                                </div>
                                <div class="box-body">
                                    <p>PHP 扩展模块“ <code>Phar</code> ”没有加载。请确认您已经安装并加载了该模块。</p>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <!-- ######################################################### System admin ######################################################### -->
                <div id="sysadmin" class="tab-pane fade<?php if($tab === "sysadmin"){ ?> in active<?php } ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="box">
                                <div class="box-header with-border">
                                    <h3 class="box-title">网络信息</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <table class="table table-striped table-bordered dt-responsive nowrap">
                                                <tbody>
                                                <tr>
                                                    <th scope="row">Pi-hole 以太网接口：</th>
                                                    <td><?php echo htmlentities($piHoleInterface); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Pi-hole IPv4 地址：</th>
                                                    <td><?php echo htmlentities($piHoleIPv4); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Pi-hole IPv6 地址：</th>
                                                    <td><?php echo htmlentities($piHoleIPv6); ?></td>
                                                </tr>
                                                <tr>
                                                    <th scope="row">Pi-hole 主机名称：</th>
                                                    <td><?php echo htmlentities($hostname); ?></td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="box">
                                <div class="box-header with-border">
                                    <h3 class="box-title">FTL 信息</h3>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <?php
                                            if ($FTL) {
                                                function get_FTL_data($arg)
                                                {
                                                    global $FTLpid;
                                                    return trim(exec("ps -p " . $FTLpid . " -o " . $arg));
                                                }

                                                $FTLversion = exec("/usr/bin/pihole-FTL version");
                                            ?>
                                            <table class="table table-striped table-bordered dt-responsive nowrap">
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">FTL 版本：</th>
                                                        <td><?php echo $FTLversion; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">进程标识符（PID）：</th>
                                                        <td><?php echo $FTLpid; ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">FTL 运行时间：</th>
                                                        <td><?php print_r(get_FTL_data("start")); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">用户/群组：</th>
                                                        <td><?php print_r(get_FTL_data("euser")); ?> / <?php print_r(get_FTL_data("egroup")); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">CPU 总利用率：</th>
                                                        <td><?php print_r(get_FTL_data("%cpu")); ?>%</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">内存利用率：</th>
                                                        <td><?php print_r(get_FTL_data("%mem")); ?>%</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <span title="常驻内存是主内存中保留的进程所占用的内存部分。其余占用的内存位于交换分区或文件系统中。">已用内存：</span>
                                                        </th>
                                                        <td><?php echo formatSizeUnits(1e3 * floatval(get_FTL_data("rss"))); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <span title="DNS 域缓存大小">DNS 缓存大小：</span>
                                                        </th>
                                                        <td id="cache-size">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <span title="缓存插入数">DNS 缓存插入：</span>
                                                        </th>
                                                        <td id="cache-inserted">&nbsp;</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">
                                                            <span title="尽管尚未过期但必须清除的高速缓存条目数（增加高速缓存大小可减少此数量）">DNS 缓存收回</span>
                                                        </th>
                                                        <td id="cache-live-freed">&nbsp;</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            您可参阅我们的<a href="https://docs.pi-hole.net/ftldns/dns-cache/" rel="noopener" target="_blank">DNS 缓存文档</a>来获取更多信息。
                                            <?php } else { ?>
                                            <div>FTL 服务已经离线！</div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="box box-warning">
                                <div class="box-header with-border">
                                    <h3 class="box-title">危险区域！</h3><br/>
                                </div>
                                <div class="box-body">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <?php if ($piHoleLogging) { ?>
                                                <button type="button" class="btn btn-warning confirm-disablelogging-noflush form-control">禁用查询记录功能</button>
                                            <?php } else { ?>
                                                <form role="form" method="post">
                                                    <input type="hidden" name="action" value="Enable">
                                                    <input type="hidden" name="field" value="Logging">
                                                    <input type="hidden" name="token" value="<?php echo $token ?>">
                                                    <button type="submit" class="btn btn-success form-control">开启查询记录功能</button>
                                                </form>
                                            <?php } ?>
                                        </div>
                                        <p class="hidden-md hidden-lg"></p>
                                        <div class="col-md-4">
                                                <button type="button" class="btn btn-warning confirm-flusharp form-control">刷新网络表</button>
                                        </div>
                                        <p class="hidden-md hidden-lg"></p>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-warning confirm-restartdns form-control">重启 DNS 解析器</button>
                                        </div>
                                    </div>
                                    <br/>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger confirm-flushlogs form-control">刷新日志</button>
                                        </div>
                                        <p class="hidden-md hidden-lg"></p>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger confirm-poweroff form-control">关闭系统</button>
                                        </div>
                                        <p class="hidden-md hidden-lg"></p>
                                        <div class="col-md-4">
                                            <button type="button" class="btn btn-danger confirm-reboot form-control">重启系统</button>
                                        </div>
                                    </div>

                                    <form role="form" method="post" id="flushlogsform">
                                        <input type="hidden" name="field" value="flushlogs">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="flusharpform">
                                        <input type="hidden" name="field" value="flusharp">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="disablelogsform-noflush">
                                        <input type="hidden" name="field" value="Logging">
                                        <input type="hidden" name="action" value="Disable-noflush">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="poweroffform">
                                        <input type="hidden" name="field" value="poweroff">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="rebootform">
                                        <input type="hidden" name="field" value="reboot">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                    <form role="form" method="post" id="restartdnsform">
                                        <input type="hidden" name="field" value="restartdns">
                                        <input type="hidden" name="token" value="<?php echo $token ?>">
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="scripts/vendor/jquery.confirm.min.js"></script>
<script src="scripts/pi-hole/js/settings.js"></script>

<?php
require "scripts/pi-hole/php/footer.php";
?>
