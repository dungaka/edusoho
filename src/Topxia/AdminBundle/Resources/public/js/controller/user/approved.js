define(function(require, exports, module) {

    var Notify = require('common/bootstrap-notify');
    var validator = require('bootstrap.validator');
    var SelectTree = require('edusoho.selecttree');
    require("jquery.bootstrap-datetimepicker");

    exports.run = function() {
        if ($("#orgSelectTree").val()) {
            var selectTree = new SelectTree({
                element: "#orgSelectTree",
                name: 'orgCode'
            });
        }
        var $table = $('#user-table');

        $table.on('click', '.cancel-approval', function() {
            if (!confirm('确定要撤销这条认证成功的实名认证吗？')) {
                return;
            }

            $.post($(this).data('url'), function(response) {
                window.location.reload();
            }).error(function() {
                window.location.reload();
            });

        });

        $("#startDate").datetimepicker({
            autoclose: true
        }).on('changeDate', function() {
            $("#endDate").datetimepicker('setStartDate', $("#startDate").val().substring(0, 16));
        });

        $("#startDate").datetimepicker('setEndDate', $("#endDate").val().substring(0, 16));

        $("#endDate").datetimepicker({
            autoclose: true
        }).on('changeDate', function() {

            $("#startDate").datetimepicker('setEndDate', $("#endDate").val().substring(0, 16));
        });

        $("#endDate").datetimepicker('setStartDate', $("#startDate").val().substring(0, 16));

    };

});