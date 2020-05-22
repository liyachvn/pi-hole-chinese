<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2019 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";
?>

<!-- Title -->
<div class="page-header">
    <h1>广告列表群组管理</h1>
</div>

<!-- Domain Input -->
<div class="row">
    <div class="col-md-12">
        <div class="box" id="add-group">
            <!-- /.box-header -->
            <div class="box-header with-border">
                <h3 class="box-title">
                    添加一个新的广告列表
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="row">
                    <div class="form-group col-md-6">
                        <label for="new_address">地址：</label>
                        <input id="new_address" type="text" class="form-control" placeholder="http://..., https://..., file://...">
                    </div>
                    <div class="form-group col-md-6">
                        <label for="new_comment">注解：</label>
                        <input id="new_comment" type="text" class="form-control" placeholder="广告列表描述（可选项）">
                    </div>
                </div>
            </div>
            <div class="box-footer clearfix">
                <strong>提示：</strong>请在编辑广告列表后，运行 <code>pihole -g</code> 或者更新<a href="gravity.php">在线</a>规则列表。
                <button id="btnAdd" class="btn btn-primary pull-right">添加</button>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="box" id="adlists-list">
            <div class="box-header with-border">
                <h3 class="box-title">
                    已经配置的广告列表
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="adlistsTable" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>地址</th>
                        <th>状态</th>
                        <th>注解</th>
                        <th>群组分配</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                </table>
                <button type="button" id="resetButton" hidden="true">重置排序</button>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
</div>

<script src="scripts/pi-hole/js/groups-common.js"></script>
<script src="scripts/pi-hole/js/groups-adlists.js"></script>

<?php
require "scripts/pi-hole/php/footer.php";
?>
