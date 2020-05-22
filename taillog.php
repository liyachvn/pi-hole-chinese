<?php /* 
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";
?>
<!-- Title -->
<div class="page-header">
    <h1>输出 pihole.log 文件的最后几行（实时）</h1>
</div>

<div class="checkbox"><label><input type="checkbox" name="active" checked id="chk1"> 更新时自动滚动</label></div>
<pre id="output" style="width: 100%; height: 100%; max-height:650px; overflow-y:scroll;"></pre>
<div class="checkbox"><label><input type="checkbox" name="active" checked id="chk2"> 更新时自动滚动</label></div>

<script src="scripts/pi-hole/js/taillog.js"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
