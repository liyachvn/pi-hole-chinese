/* Pi-hole: A black hole for Internet advertisements
 *  (c) 2017 Pi-hole, LLC (https://pi-hole.net)
 *  Network-wide ad blocking via your own hardware.
 *
 *  This file is copyright under the latest version of the EUPL.
 *  Please see LICENSE file for your rights under this license. */

/* global utils:false */

var table;
var groups = [];
var token = $("#token").html();

function reload_client_suggestions() {
  $.post(
    "scripts/pi-hole/php/groups.php",
    { action: "get_unconfigured_clients", token: token },
    function(data) {
      var sel = $("#select");
      var customWasSelected = sel.val() === "custom";
      sel.empty();
      for (var key in data) {
        if (!Object.prototype.hasOwnProperty.call(data, key)) {
          continue;
        }

        var text = key;
        if (data[key].length > 0) {
          text += " (" + data[key] + ")";
        }

        sel.append(
          $("<option />")
            .val(key)
            .text(text)
        );
      }

      sel.append(
        $("<option />")
          .val("custom")
          .text("自定义，在下方输入……")
      );
      if (customWasSelected) {
        sel.val("custom");
      }
    },
    "json"
  );
}

function get_groups() {
  $.post(
    "scripts/pi-hole/php/groups.php",
    { action: "get_groups", token: token },
    function(data) {
      groups = data.data;
      initTable();
    },
    "json"
  );
}

$(document).ready(function() {
  $("#btnAdd").on("click", addClient);

  reload_client_suggestions();
  utils.bsSelect_defaults();
  get_groups();

  $("#select").on("change", function() {
    $("#ip-custom").val("");
    $("#ip-custom").prop("disabled", $("#select option:selected").val() !== "custom");
  });
});

function initTable() {
  table = $("#clientsTable").DataTable({
    ajax: {
      url: "scripts/pi-hole/php/groups.php",
      data: { action: "get_clients", token: token },
      type: "POST"
    },
    order: [[0, "asc"]],
    columns: [
      { data: "id", visible: false },
      { data: "ip" },
      { data: "comment" },
      { data: "groups", searchable: false },
      { data: "name", width: "80px", orderable: false }
    ],
    drawCallback: function() {
      $('button[id^="deleteClient_"]').on("click", deleteClient);
      // Remove visible dropdown to prevent orphaning
      $("body > .bootstrap-select.dropdown").remove();
    },
    rowCallback: function(row, data) {
      $(row).attr("data-id", data.id);
      var tooltip =
        "已添加：" +
        utils.datetime(data.date_added) +
        "\n上次修改：" +
        utils.datetime(data.date_modified) +
        "\n数据库 ID: " +
        data.id;
      var ip_name =
        '<code id="ip_' +
        data.id +
        '" title="' +
        tooltip +
        '" class="breakall">' +
        data.ip +
        "</code>";
      if (data.name !== null && data.name.length > 0)
        ip_name +=
          '<br><code id="name_' +
          data.id +
          '" title="' +
          tooltip +
          '" class="breakall">' +
          data.name +
          "</code>";
      $("td:eq(0)", row).html(ip_name);

      $("td:eq(1)", row).html('<input id="comment_' + data.id + '" class="form-control">');
      var commentEl = $("#comment_" + data.id, row);
      commentEl.val(data.comment);
      commentEl.on("change", editClient);

      $("td:eq(2)", row).empty();
      $("td:eq(2)", row).append(
        '<select class="selectpicker" id="multiselect_' + data.id + '" multiple></select>'
      );
      var selectEl = $("#multiselect_" + data.id, row);
      // Add all known groups
      for (var i = 0; i < groups.length; i++) {
        var data_sub = "";
        if (!groups[i].enabled) {
          data_sub = 'data-subtext="(disabled)"';
        }

        selectEl.append(
          $("<option " + data_sub + "/>")
            .val(groups[i].id)
            .text(groups[i].name)
        );
      }

      // Select assigned groups
      selectEl.val(data.groups);
      // Initialize bootstrap-select
      selectEl
        // fix dropdown if it would stick out right of the viewport
        .on("show.bs.select", function() {
          var winWidth = $(window).width();
          var dropdownEl = $("body > .bootstrap-select.dropdown");
          if (dropdownEl.length > 0) {
            dropdownEl.removeClass("align-right");
            var width = dropdownEl.width();
            var left = dropdownEl.offset().left;
            if (left + width > winWidth) {
              dropdownEl.addClass("align-right");
            }
          }
        })
        .on("changed.bs.select", function() {
          // enable Apply button
          if ($(ApplyBtn).prop("disabled")) {
            $(ApplyBtn)
              .addClass("btn-success")
              .prop("disabled", false)
              .on("click", function() {
                editClient.call(selectEl);
              });
          }
        })
        .on("hide.bs.select", function() {
          // Restore values if drop-down menu is closed without clicking the Apply button
          if (!$(ApplyBtn).prop("disabled")) {
            $(this)
              .val(data.groups)
              .selectpicker("refresh");
            $(ApplyBtn)
              .removeClass("btn-success")
              .prop("disabled", true)
              .off("click");
          }
        })
        .selectpicker()
        .siblings(".dropdown-menu")
        .find(".bs-actionsbox")
        .prepend(
          '<button type="button" id=btn_apply_' +
            data.id +
            ' class="btn btn-block btn-sm" disabled>应用</button>'
        );

      var ApplyBtn = "#btn_apply_" + data.id;

      var button =
        '<button class="btn btn-danger btn-xs" type="button" id="deleteClient_' +
        data.id +
        '">' +
        '<span class="glyphicon glyphicon-trash"></span>' +
        "</button>";
      $("td:eq(3)", row).html(button);
    },
    dom:
      "<'row'<'col-sm-4'l><'col-sm-8'f>>" +
      "<'row'<'col-sm-12'<'table-responsive'tr>>>" +
      "<'row'<'col-sm-5'i><'col-sm-7'p>>",
    lengthMenu: [
      [10, 25, 50, 100, -1],
      [10, 25, 50, 100, "All"]
    ],
    stateSave: true,
    stateSaveCallback: function(settings, data) {
      // Store current state in client's local storage area
      localStorage.setItem("groups-clients-table", JSON.stringify(data));
    },
    stateLoadCallback: function() {
      // Receive previous state from client's local storage area
      var data = localStorage.getItem("groups-clients-table");
      // Return if not available
      if (data === null) {
        return null;
      }

      data = JSON.parse(data);
      // Always start on the first page to show most recent queries
      data.start = 0;
      // Always start with empty search field
      data.search.search = "";
      // Reset visibility of ID column
      data.columns[0].visible = false;
      // Apply loaded state to table
      return data;
    }
  });

  table.on("order.dt", function() {
    var order = table.order();
    if (order[0][0] !== 0 || order[0][1] !== "asc") {
      $("#resetButton").show();
    } else {
      $("#resetButton").hide();
    }
  });
  $("#resetButton").on("click", function() {
    table.order([[0, "asc"]]).draw();
    $("#resetButton").hide();
  });
}

function addClient() {
  var ip = $("#select").val();
  var comment = $("#new_comment").val();
  if (ip === "custom") {
    ip = $("#ip-custom").val();
  }

  utils.disableAll();
  utils.showAlert("信息", "", "正在添加客户端……", ip);

  if (ip.length === 0) {
    utils.enableAll();
    utils.showAlert("警告", "", "警告", "请指定一个客户端 IP 地址");
    return;
  }

  // Validate IP address (may contain CIDR details)
  var ipv6format = ip.includes(":");

  if (!ipv6format && !utils.validateIPv4CIDR(ip)) {
    utils.enableAll();
    utils.showAlert("警告", "", "警告", "无效的 IPv4 地址！");
    return;
  }

  if (ipv6format && !utils.validateIPv6CIDR(ip)) {
    utils.enableAll();
    utils.showAlert("警告", "", "警告", "无效的 IPv6 地址！");
    return;
  }

  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: { action: "add_client", ip: ip, comment: comment, token: token },
    success: function(response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert("成功", "glyphicon glyphicon-plus", "添加客户端成功", ip);
        reload_client_suggestions();
        table.ajax.reload(null, false);
      } else {
        utils.showAlert("错误", "", "添加新的客户端时发生错误", response.message);
      }
    },
    error: function(jqXHR, exception) {
      utils.enableAll();
      utils.showAlert("错误", "", "添加新的客户端时发生错误", jqXHR.responseText);
      console.log(exception);
    }
  });
}

function editClient() {
  var elem = $(this).attr("id");
  var tr = $(this).closest("tr");
  var id = tr.attr("data-id");
  var groups = tr.find("#multiselect_" + id).val();
  var ip = tr.find("#ip_" + id).text();
  var name = tr.find("#name_" + id).text();
  var comment = tr.find("#comment_" + id).val();

  var done = "edited";
  var not_done = "editing";
  switch (elem) {
    case "multiselect_" + id:
      done = "edited groups of";
      not_done = "editing groups of";
      break;
    case "comment_" + id:
      done = "edited comment of";
      not_done = "editing comment of";
      break;
    default:
      alert("元素出错或无效数据 ID ！");
      return;
  }

  var ip_name = ip;
  if (name.length > 0) {
    ip_name += " (" + name + ")";
  }

  utils.disableAll();
  utils.showAlert("信息", "", "编辑客户端……", ip_name);
  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: {
      action: "edit_client",
      id: id,
      groups: groups,
      token: token,
      comment: comment
    },
    success: function(response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert(
          "成功",
          "glyphicon glyphicon-pencil",
          "Successfully " + done + " client",
          ip_name
        );
        table.ajax.reload(null, false);
      } else {
        utils.showAlert(
          "错误",
          "Error while " + not_done + " client with ID " + id,
          response.message
        );
      }
    },
    error: function(jqXHR, exception) {
      utils.enableAll();
      utils.showAlert(
        "错误",
        "",
        "Error while " + not_done + " client with ID " + id,
        jqXHR.responseText
      );
      console.log(exception);
    }
  });
}

function deleteClient() {
  var tr = $(this).closest("tr");
  var id = tr.attr("data-id");
  var ip = tr.find("#ip_" + id).text();
  var name = tr.find("#name_" + id).text();

  var ip_name = ip;
  if (name.length > 0) {
    ip_name += " (" + name + ")";
  }

  utils.disableAll();
  utils.showAlert("信息", "", "正在删除客户端……", ip_name);
  $.ajax({
    url: "scripts/pi-hole/php/groups.php",
    method: "post",
    dataType: "json",
    data: { action: "delete_client", id: id, token: token },
    success: function(response) {
      utils.enableAll();
      if (response.success) {
        utils.showAlert(
          "成功",
          "glyphicon glyphicon-trash",
          "删除客户端成功",
          ip_name
        );
        table
          .row(tr)
          .remove()
          .draw(false)
          .ajax.reload(null, false);
        reload_client_suggestions();
      } else {
        utils.showAlert("错误", "", "删除客户端时发生错误。ID：" + id, response.message);
      }
    },
    error: function(jqXHR, exception) {
      utils.enableAll();
      utils.showAlert("错误", "", "删除客户端时发生错误。ID：" + id, jqXHR.responseText);
      console.log(exception);
    }
  });
}
