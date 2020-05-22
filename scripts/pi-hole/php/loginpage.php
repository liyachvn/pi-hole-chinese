<?php
/* Pi-hole: A black hole for Internet advertisements
*  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
*  Network-wide ad blocking via your own hardware.
*
*  This file is copyright under the latest version of the EUPL.
*  Please see LICENSE file for your rights under this license. */ ?>

<div class="mainbox col-md-6 col-md-offset-3 col-sm-8 col-sm-offset-2" style="float:none">
  <div class="panel panel-default">
    <div class="panel-heading">
      <div class="text-center">
        <img src="img/logo.svg" alt="" style="width: <?php if ($boxedlayout) { ?>50%<?php } else { ?>30%<?php } ?>;">
      </div>
      <br>

      <div class="panel-title text-center"><span class="logo-lg" style="font-size: 25px;">Pi-<b>hole</b></span></div>
      <p class="login-box-msg">请登录以开始会话</p>
      <div id="cookieInfo" class="panel-title text-center text-red" style="font-size: 150%" hidden>Verify that cookies are allowed for <code><?php echo $_SERVER['HTTP_HOST']; ?></code></div>
      <?php if ($wrongpassword) { ?>
        <div class="form-group has-error login-box-msg">
          <label class="control-label"><i class="fa fa-times-circle"></i> 密码错误！</label>
        </div>
      <?php } ?>
    </div>

    <div class="panel-body">
      <form action="" id="loginform" method="post">
        <div class="form-group has-feedback <?php if ($wrongpassword) { ?>has-error<?php } ?> ">
          <input type="password" id="loginpw" name="pw" class="form-control" placeholder="密码" autofocus>
          <span class="fa fa-key form-control-feedback"></span>
        </div>
        <div class="row">
          <div class="col-xs-8 hidden-xs hidden-sm">
          <ul>
            <li><kbd>回车键</kbd> &rarr; 登录并转到请求页面 (<?php echo $scriptname; ?>)</li>
            <li><kbd>Ctrl</kbd>+<kbd>回车键</kbd> &rarr; 登录并转到设置页面</li>
          </ul>
          </div>
          <div class="col-xs-12 col-md-4">
            <div class="form-group pull-left">
              <div class="checkbox pull-right"><label><input type="checkbox" id="logincookie" name="persistentlogin">七日之内记住我</label></div>
            </div>
            <button type="submit" class="btn btn-primary pull-right"><i class="glyphicon glyphicon-log-in"></i>&nbsp;&nbsp;&nbsp;登录</button>
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-xs-12">
            <div class="box box-<?php if (!$wrongpassword) { ?>info<?php } else { ?>danger<?php }
            if (!$wrongpassword) { ?> collapsed-box<?php } ?> box-solid">
              <div class="box-header with-border">
                <h3 class="box-title">忘记密码</h3>

                <div class="box-tools pull-right">
                  <button type="button" class="btn btn-box-tool" data-widget="collapse"><i
                      class="fa <?php if ($wrongpassword) { ?>fa-minus<?php } else { ?>fa-plus<?php } ?>"></i>
                  </button>
                </div>
              </div>
              <div class="box-body">
                首次安装 Pi-hole 之后，将生成一个密码并将其显示在屏幕上。以后无法检索密码，但是可以使用以下命令来设置新密码（或通过设置空密码来显示禁用密码）：
                <pre>sudo pihole -a -p</pre>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>
