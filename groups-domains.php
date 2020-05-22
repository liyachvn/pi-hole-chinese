<?php /*
*    Pi-hole: A black hole for Internet advertisements
*    (c) 2019 Pi-hole, LLC (https://pi-hole.net)
*    Network-wide ad blocking via your own hardware.
*
*    This file is copyright under the latest version of the EUPL.
*    Please see LICENSE file for your rights under this license. */
    require "scripts/pi-hole/php/header.php";
    $type = "all";
    $pagetitle = "域";
    $adjective = "";
    if (isset($_GET['type']) && ($_GET['type'] === "white" || $_GET['type'] === "black")) {
        $type = $_GET['type'];
        $pagetitle = ucfirst($type)."list";
        $adjective = $type."listed";
    }
?>

<!-- Title -->
<div class="page-header">
    <h1><?php echo $pagetitle; ?>管理</h1>
</div>

<!-- Domain Input -->
<div class="row">
    <div class="col-md-12">
        <div class="box" id="add-group">
            <div class="box-header with-border">
                <h3 class="box-title">
		    添加一个新的列入<?php echo $adjective; ?>的域或正则表达式过滤器
		</h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a aria-expanded="true" href="#tab_domain" data-toggle="tab">域</a></li>
                        <li class=""><a aria-expanded="false" href="#tab_regex" data-toggle="tab">正则表达式过滤器</a></li>
                    </ul>        
                    <div class="tab-content">
                        <!-- Domain tab -->
                        <div id="tab_domain" class="tab-pane active in">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_domain">域：</label>
                                        <div class="input-group">
                                            <input id="new_domain" type="text" class="form-control active" placeholder="要添加的域">
                                            <span class="input-group-addon">
                                                <input type="checkbox" id="wildcard_checkbox">
                                                通配符</span>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <strong><i class="fa fa-question-circle"></i> 通配符复选框：</span></strong>如果要涉及所有子域，请选中此框。输入的域将在添加时转换为正则表达式过滤器。
                                    </div>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label for="new_domain_comment">注解：</label>
                                    <input id="new_domain_comment" type="text" class="form-control" placeholder="描述（可选项）">
                                </div>
                            </div>
                        </div>
                        <!-- RegEx tab -->
                        <div id="tab_regex" class="tab-pane">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="new_regex">正则表达式：</label>
                                        <input id="new_regex" type="text" class="form-control active" placeholder="要添加的正则表达式">
                                    </div>
                                    <div class="form-group">
                                        <strong>示意：</strong>需要帮助以编写正确的正则表达式规则吗？请参阅我们的在线
                                        <a href="https://docs.pi-hole.net/ftldns/regex/tutorial" rel="noopener" target="_blank">
                                            正则表达式教程</a>。
                                    </div>
                                </div>
                                <div class="form-group col-md-6">
                                        <label for="new_regex_comment">注解：</label>
                                        <input id="new_regex_comment" type="text" class="form-control" placeholder="描述（可选项）">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="btn-toolbar pull-right" role="toolbar" aria-label="Toolbar with buttons">
                    <?php if ( $type !== "white" ) { ?>
                    <div class="btn-group" role="group" aria-label="Third group">
                        <button type="button" class="btn btn-primary" id="add2black">添加到黑名单</button>
                    </div>
                    <?php } if ( $type !== "black" ) { ?>
                    <div class="btn-group" role="group" aria-label="Third group">
                        <button type="button" class="btn btn-primary" id="add2white">添加到白名单</button>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <!-- /.box-body -->
        </div>
        <!-- /.box -->
    </div>
</div>

<!-- Domain List -->
<div class="row">
    <div class="col-md-12">
        <div class="box" id="domains-list">
            <div class="box-header with-border">
                <h3 class="box-title">
                    <?php echo $adjective; ?>列表
                </h3>
            </div>
            <!-- /.box-header -->
            <div class="box-body">
                <table id="domainsTable" class="display table table-striped table-bordered" cellspacing="0" width="100%">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>域/正则表达式</th>
                        <th>类型</th>
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
<script src="scripts/pi-hole/js/groups-domains.js"></script>

<?php
require "scripts/pi-hole/php/footer.php";
?>
