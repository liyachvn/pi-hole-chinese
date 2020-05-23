<?php

    require_once "func.php";

    $customDNSFile = "/etc/pihole/custom.list";

    switch ($_REQUEST['action'])
    {
        case 'get':     echo json_encode(echoCustomDNSEntries());    break;
        case 'add':     echo json_encode(addCustomDNSEntry());      break;
        case 'delete':  echo json_encode(deleteCustomDNSEntry());   break;
        default:
            die("操作错误");
    }

    function echoCustomDNSEntries()
    {
        $entries = getCustomDNSEntries();

        $data = [];
        foreach ($entries as $entry)
            $data[] = [ $entry->domain, $entry->ip ];

        return [ "data" => $data ];
    }

    function getCustomDNSEntries()
    {
        global $customDNSFile;

        $entries = [];

        $handle = fopen($customDNSFile, "r");
        if ($handle)
        {
            while (($line = fgets($handle)) !== false) {
                $line = str_replace("\r","", $line);
                $line = str_replace("\n","", $line);
                $explodedLine = explode (" ", $line);

                if (count($explodedLine) != 2)
                    continue;

                $data = new \stdClass();
                $data->ip = $explodedLine[0];
                $data->domain = $explodedLine[1];
                $entries[] = $data;
            }

            fclose($handle);
        }

        return $entries;
    }

    function addCustomDNSEntry()
    {
        try
        {
            $ip = !empty($_REQUEST['ip']) ? $_REQUEST['ip']: "";
            $domain = !empty($_REQUEST['domain']) ? $_REQUEST['domain']: "";

            if (empty($ip))
                return errorJsonResponse("IP 地址必须设置");

            $ipType = get_ip_type($ip);

            if (!$ipType)
                return errorJsonResponse("请输入有效的 IP 地址");

            if (empty($domain))
                return errorJsonResponse("域名必须设置");

            if (!is_valid_domain_name($domain))
                return errorJsonResponse("请输入有效的域名");

            $existingEntries = getCustomDNSEntries();

            foreach ($existingEntries as $entry)
                if ($entry->domain == $domain)
                    if (get_ip_type($entry->ip) == $ipType)
                        return errorJsonResponse("此域名已经存在一个自定义 DNS 值解析到 IPv" . $ipType);

            exec("sudo pihole -a addcustomdns ".$ip." ".$domain);

            return successJsonResponse();
        }
        catch (\Exception $ex)
        {
            return errorJsonResponse($ex->getMessage());
        }
    }

    function deleteCustomDNSEntry()
    {
        try
        {
            $ip = !empty($_REQUEST['ip']) ? $_REQUEST['ip']: "";
            $domain = !empty($_REQUEST['domain']) ? $_REQUEST['domain']: "";

            if (empty($ip))
                return errorJsonResponse("IP 地址必须设置");

            if (empty($domain))
                return errorJsonResponse("域名必须设置");

            $existingEntries = getCustomDNSEntries();

            $found = false;
            foreach ($existingEntries as $entry)
                if ($entry->domain == $domain)
                    if ($entry->ip == $ip) {
                        $found = true;
                        break;
                    }

            if (!$found)
                return errorJsonResponse("此域名/IP 地址组合不存在");

            exec("sudo pihole -a removecustomdns ".$ip." ".$domain);

            return successJsonResponse();
        }
        catch (\Exception $ex)
        {
            return errorJsonResponse($ex->getMessage());
        }
    }

    function successJsonResponse($message = "")
    {
        return [ "success" => true, "message" => $message ];
    }

    function errorJsonResponse($message = "")
    {
        return [ "success" => false, "message" => $message ];
    }
?>
