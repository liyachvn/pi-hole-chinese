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
    <h1>Pi-hole 查询数据库计算热门列表</h1>
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

<div id="timeoutWarning" class="alert alert-warning alert-dismissible fade in" role="alert" hidden="true">
    当您指定范围为多大时，Pi-hole 尝试检索所有数据时，请求可能会超时。<br/><span id="err"></span>
</div>

<?php
if($boxedlayout)
{
	$tablelayout = "col-md-6";
}
else
{
	$tablelayout = "col-md-6 col-lg-4";
}
?>
<div class="row">
    <div class="<?php echo $tablelayout; ?>">
      <div class="box" id="domain-frequency">
        <div class="box-header with-border">
          <h3 class="box-title">热门域名</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                    <th>域名</th>
                    <th>命中</th>
                    <th>频率</th>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        <div class="overlay" hidden>
          <i class="fa fa-sync fa-spin"></i>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.col -->
    <div class="<?php echo $tablelayout; ?>">
      <div class="box" id="ad-frequency">
        <div class="box-header with-border">
          <h3 class="box-title">阻止的域名</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                    <th>域名</th>
                    <th>命中</th>
                    <th>频率</th>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        <div class="overlay" hidden>
          <i class="fa fa-sync fa-spin"></i>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.col -->
    <div class="<?php echo $tablelayout; ?>">
      <div class="box" id="client-frequency">
        <div class="box-header with-border">
          <h3 class="box-title">活跃客户端</h3>
        </div>
        <!-- /.box-header -->
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                  <tbody>
                    <tr>
                    <th>客户端</th>
                    <th>请求</th>
                    <th>频率</th>
                    </tr>
                  </tbody>
                </table>
            </div>
        </div>
        <div class="overlay" hidden>
          <i class="fa fa-sync fa-spin"></i>
        </div>
        <!-- /.box-body -->
      </div>
      <!-- /.box -->
    </div>
    <!-- /.col -->
</div>

<script src="scripts/vendor/moment.min.js"></script>
<script src="scripts/vendor/daterangepicker.js"></script>
<script src="scripts/pi-hole/js/db_lists.js"></script>

<?php
    require "scripts/pi-hole/php/footer.php";
?>
