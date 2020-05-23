<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";

    if(strlen($pwhash) > 0)
    {
        $authenticationsystem = true;
    }
    else
    {
        $authenticationsystem = false;
    }
?>

<div class="row">
    <div class="col-md-12">
    <h1>帮助中心</h1>
    <h2>顶部</h2>
    <h4>顶部左侧：状态显示</h4>
    <p>显示不同状态信息：</p>
    <ul>
        <li>状态：Pi-hole 当前状态 - 活动 (<i class="fa fa-circle text-green-light"></i>)，离线 (<i class="fa fa-circle text-red"></i>)，或者启动中 (<i class="fa fa-circle text-orange"></i>)。</li>
        <li>温度：当前 CPU 温度。</li>
        <li>负载：分别为最后 1 分钟、5 分钟和 15 分钟的平均负载。如果平均负载为 1，则说明系统上处理器的单个核心已经满载。如果当前负载超过此计算机上的可用处理器核心数量（本机处理器具有 <?php echo $nproc; ?> 个核心），我们将显示一个红色图标。</li>
        <li>内存使用：显示实际被应用程序占用的内存百分比。如果内存使用率超过 75%，我们将显示一个红色图标。</li>
    </ul>
    <h4>顶部右侧：关于</h4>
    <ul>
        <li>GitHub：链接到 Pi-hole Github 仓库。</li>
        <li>描述：链接到 Jacob Salmela 的博客以获取更多详细信息，还描述了 Pi-hole 的概念。</li>
        <li>升级：链接到发布列表。</li>
        <li>升级通知：如果有可用的升级，此处将会显示一个链接。</li>
        <?php if($authenticationsystem){ ?>
        <li>会话计时器：显示当前登录会话到期之前剩余的时间。</li>
        <?php } ?>
    </ul>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>仪表盘</h2>
    <p>在仪表盘上，您可以查看各种 Pi-hole 统计信息：</p>
    <ul>
        <li>摘要：统计信息摘要显示了今天被阻止的 DNS 查询总数，被阻止的 DNS 查询百分比以及已编译的广告列表中有多少个域。此摘要每 10 秒更新一次。</li>
        <li>一段时间内的查询：该图显示了 10 分钟时间间隔内的 DNS 查询（总计和已阻止）。悬停在行上可以获取更多信息。该图每 10 分钟更新一次。</li>
        <li>查询记录类型：标识已处理查询的类型。</li>
        <li>查询应答来源：显示允许的请求已转发到哪个上游 DNS。</li>
        <li>活跃放行域名：按 DNS 查找数对请求的网站进行排名。</li>
        <li>活跃阻止域名：按 DNS 查找数对请求的广告进行排名。</li>
        <li>活跃客户端：每个客户端在本地网络上发出的 DNS 请求数据的排名。</li>
    </ul>
    <p>根据设置页面上的隐私设置，“活跃放行域名”和“活跃阻止域名”列表可能会隐藏。</p>
    <?php if($authenticationsystem){ ?>
    <p>请注意，登录会话<em>不会</em>在仪表板上终止，因为摘要每 10 秒更新一次，从而刷新会话。</p>
    <?php } ?>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>查询日志</h2>
    <p>通过分析 Pi-hole 的日志显示最近的查询。通过在“搜索”输入框键入关键字可以搜索整个列表。如果状态报告为“OK”，则表明 DNS 请求已被允许。否则 ("Pi-holed") 它已被阻止。通过单击“操作”下的按钮，可以将相应的域快速添加到白名单/黑名单中。操作状态将在此页面上报告。默认情况下，仅显示最近的 10 分钟以提高查询日志页面的加载速度。通过单击页面标题中的相应链接，可以请求所有域。请注意，结果在很大程度上取决于您的隐私设置（请参阅“设置”页面）。</p>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>白名单/黑名单</h2>
    <p>从白名单/黑名单中添加或删除域（或子域）。如果将域添加到例如白名单中，同一域的所有可能条目都会自动从黑名单中删除，反之亦然。</p>
    <p>支持正则表达式黑名单 (输入 <code>^example</code> 将阻止以 <code>example</code>开始的任何域，另请参见我们的<a href="https://docs.pi-hole.net/ftldns/regex/" rel="noopener" target="_blank">正则表达式文档</a>)。即使特定域属于正则表达式模式，您仍然可以将其列入白名单。</p>
    <p>如果您用空格分隔域，则可以一次将多个条目列入白名单/黑名单。</p>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>禁用/启用</h2>
    完全禁用/启用 Pi-hole。您可能需要等待几分钟，更改才能在所有设备上生效。更改将通过状态更改反映出来（顶部左侧）。
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>工具 &rarr; 更新规则</h2>
    <p>将从我们提供的第三方阻止列表中下载所有更新。默认情况下，该命令每周通过 cron 命令（星期日）运行一次。</p>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>工具 &rarr; 查询列表</h2>
    此功能对于查找域出现在什么列表上很有用。由于我们无法控制第三方放置在阻止列表中的内容，因此您可能会发现通常访问的域停止工作。如果是这种情况，您可以运行此命令来扫描阻止的域列表中的字符串，它将返回找到该域的列表。当 Mahakala 列表将 <code>apple.com</code> 和 <code>microsoft.com</code> 添加到其阻止列表时，这被证明是有用的。</p>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>工具 &rarr; 跟踪 pihole.log</h2>
    原始 Pi-hole 日志实时跟踪。</p>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>设置</h2>
    修改 Pi-hole 的设置。
    <h4>系统</h4>
    显示 Pi-hole 的网络和其它系统信息。底部有一个“危险区域”，其中包含诸如禁用查询日志记录和重新启动之类的操作。
    <h4>阻止列表</h4>
    查看和编辑基于阻止域名的阻止列表。
    <h4>DNS</h4>
    自定义使用的上游 DNS 服务器和 DNS 服务器的高级设置。请注意，一次可以启用任意数量的 DNS 服务器。
    <h4>DHCP</h4>
    使用此设置，您可以启用/禁用 Pi-hole 的 DHCP 服务器。请注意，您应该禁用网络上的任何其它 DHCP 服务器，以避免 IP 地址重复。您必须提供 DHCP 服务器的 IP 地址范围以及本地路由器（网关）的 IP 地址。如果 DHCP 服务器处于活动状态，则当前的租约将显示在设置页面上。IPv4 DHCP 将始终被激活，Ipv6 (无状态和有状态）则可以被启用。
    <h4>API / Web 界面</h4>
    修改应用于 API 和 Web 界面的设置。
    <h4>隐私</h4>
    设置查询的隐私级别。请注意，降低隐私级别不会透露以前隐藏的查询数据。
    <h4>传送器</h4>
    导入和导出 Pi-hole 设置信息。
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>认证系统 (当前 <?php if($authenticationsystem) { ?>启用<?php } else { ?>禁用<?php } ?>)</h2>
    <p>使用以下命令<pre>sudo pihole -a -p</pre> 并输入要设置的密码，即可启用该 Web 界面的身份验证系统。此后，大多数页面都需要登录（仪表板将显示有限数量的统计信息）。请注意，通过使用上面显示的命令设置空密码，可以再次禁用身份验证系统。帮助中心仅在启用身份验证系统后，才会显示有关身份验证系统的更多详细信息。</p>
    </div>
</div>
<?php if($authenticationsystem) { ?>
<div class="row">
    <div class="col-md-12">
    <h2>登录/注销</h2>
    <p>使用登录/注销功能，用户可以启动/终止登录会话。如果用户尝试在没有有效会话的情况下直接访问受保护的页面，则也会始终显示登录页面。</p>
    </div>
</div>
<?php } ?>
<div class="row">
    <div class="col-md-12">
    <h2>捐赠</h2>
    请记住，Pi-hole 是免费的。如果您喜欢 Pi-hole，请考虑捐赠以支持其发展。
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>帮助（本页面）</h2>
    显示有关幕后发生的事情以及此 Web 界面可以完成的操作的信息。帮助中心仅在启用身份验证系统后，才会显示有关身份验证系统的详细信息。
    </div>
</div>
<div class="row">
    <div class="col-md-12">
    <h2>底部</h2>
    显示当前安装的 Pi-hole 和 Web 界面版本。如果有更新，将在此处显示。
    </div>
</div>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
