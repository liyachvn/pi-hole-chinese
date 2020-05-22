<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";

// Generate CSRF token
if(empty($_SESSION['token'])) {
    $_SESSION['token'] = base64_encode(openssl_random_pseudo_bytes(32));
}
$token = $_SESSION['token'];

?>
<!-- Send PHP info to JS -->
<div id="token" hidden><?php echo $token ?></div>

<!-- Title -->
<div class="page-header">
    <h1>指定要从 Pi-hole 查询数据库中查询的日期范围</h1>
</div>


<div class="row">
    <div class="col-md-12">
<!-- Date Input -->
      <div class="form-group">
        <label>日期和时间范围：</label>

        <div class="input-group">
          <div class="input-group-addon">
            <i class="far fa-clock"></i>
          </div>
          <input type="button" class="form-control pull-right" id="querytime" value="点击以选择日期和时间范围">
        </div>
        <!-- /.input group -->
      </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <label>查询状态：</label>
    </div>
    <div class="form-group">
        <div class="col-md-3">
            <div class="checkbox">
                <label><input type="checkbox" id="type_forwarded" checked><strong>已允许：</strong>已转发</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" id="type_cached" checked><strong>已允许：</strong>已缓存</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="checkbox">
                <label><input type="checkbox" id="type_gravity" checked><strong>已阻止：</strong>规则</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" id="type_external" checked><strong>已阻止：</strong>外部</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="checkbox">
                <label><input type="checkbox" id="type_blacklist" checked><strong>已阻止：</strong>确切黑名单</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" id="type_regex" checked><strong>已阻止：</strong>正则表达式黑名单</label>
            </div>
        </div>
        <div class="col-md-3">
            <div class="checkbox">
                <label><input type="checkbox" id="type_gravity_CNAME" checked><strong>已阻止：</strong>规则 (CNAME)</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" id="type_blacklist_CNAME" checked><strong>已阻止：</strong>确切黑名单 (CNAME)</label>
            </div>
            <div class="checkbox">
                <label><input type="checkbox" id="type_regex_CNAME" checked><strong>已阻止：</strong>正则表达式黑名单 (CNAME)</label>
            </div>
        </div>
    </div>
</div>

<div id="timeoutWarning" class="alert alert-warning alert-dismissible fade in" role="alert" hidden="true">
    当您指定范围为多大时，Pi-hole 尝试检索所有数据时，请求可能会超时。<br/><span id="err"></span>
</div>

<!-- Small boxes (Stat box) -->
<div class="row">
    <div class="col-lg-3 col-xs-12">
        <!-- small box -->
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3 class="statistic" id="ads_blocked_exact">---</h3>
                <p>查询被阻止</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-paper"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-12">
        <!-- small box -->
        <div class="small-box bg-aqua">
            <div class="inner">
                <h3 class="statistic" id="ads_wildcard_blocked">---</h3>
                <p>查询被阻止 (通配符)</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-paper"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-12">
        <!-- small box -->
        <div class="small-box bg-green">
            <div class="inner">
                <h3 class="statistic" id="dns_queries">---</h3>
                <p>总查询</p>
            </div>
            <div class="icon">
                <i class="fas fa-globe-americas"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
    <div class="col-lg-3 col-xs-12">
        <!-- small box -->
        <div class="small-box bg-yellow">
            <div class="inner">
                <h3 class="statistic" id="ads_percentage_today">---</h3>
                <p>查询被阻止</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-pie"></i>
            </div>
        </div>
    </div>
    <!-- ./col -->
</div>

<div class="row">
    <div class="col-md-12">
      <div class="box" id="recent-queries">
        <div class="box-header with-border">
          <h3 class="box-title">最近查询</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <table id="all-queries" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                <thead>
                    <tr>
                        <th>时间</th>
                        <th>类型</th>
                        <th>域名</th>
                        <th>客户端</th>
                        <th>状态</th>
                        <th>动作</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>时间</th>
                        <th>类型</th>
                        <th>域名</th>
                        <th>客户端</th>
                        <th>状态</th>
                        <th>动作</th>
                    </tr>
                </tfoot>
            </table>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
</div>
<!-- /.row -->

<script src="scripts/vendor/moment.min.js"></script>
<script src="scripts/vendor/daterangepicker.js"></script>
<script src="scripts/pi-hole/js/db_queries.js"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
