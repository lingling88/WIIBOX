(function($) {
    if ($('.page-monitor').length) {

        /*
         * 获取Hashrate的数据
         */
        $.ajax({
            type: "get",
            url: $('.hashrate').data('url'),
            dataType: 'json',
            timeout: 5000,
            success: function(data) {
                hashrate(data);
            },
            error: function() {
                //“加载失败，请重试”
                $('.hashrate').text(basei18n.loadFaild);
            }
        });


        /*
         * Hashrate统计图
         */

        function hashrate(data) {
            $('.hashrate').highcharts({
                chart: {
                    type: 'spline'
                },
                title: {
                    text: ''
                },
                credits: {
                    enabled: false
                },
                xAxis: {
                    type: 'datetime',

                },
                yAxis: {
                    title: {
                        text: ''
                    },
                    min: 0,
                    labels: {
                        formatter: function() {
                            return this.value;
                        }
                    }
                },
                tooltip: {
                    formatter: function() {
                        return Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', this.x) + '<br/><b>' + this.series.name + '</b><br><b>' + this.y + '</b>';
                    }
                },
                plotOptions: {
                    spline: {
                        lineWidth: 2,
                        states: {
                            hover: {
                                lineWidth: 3
                            }
                        },
                        marker: {
                            enabled: false
                        }
                    }
                },
                series: [{
                    name: 'SHA (khash/s)',
                    color: '#FF7F0E',
                    data: data.DATA.B
                }, {
                    name: 'SCRYPT (khash/s)',
                    data: data.DATA.L
                }],
                navigation: {
                    menuItemStyle: {
                        fontSize: '10px'
                    }
                }
            });
        }



        /*
         * 查询设备运行状态
         *
         */

        function deviceInfo() {
            $.ajax({
                type: 'get',
                url: $('#urlConfig').data('check'),
                dataType: 'json',
                timeout: 10000,
                success: function(data) {
                    if (data.alived.length || data.died.length) {
                        var tpl = '{{#each alived.BTC}}<tr><td>B:{{this}}</td><td>' + basei18n.running + '</td><td>SHA</td></tr>{{/each}}';
                        tpl += '{{#each alived.LTC}}<tr><td>L:{{this}}</td><td>' + basei18n.running + '</td><td>SCRYPT</td></tr>{{/each}}';
                        tpl += '{{#each died.BTC}}<tr><td>B:{{this}}</td><td>' + basei18n.stopped + '</td><td>SHA</td></tr>{{/each}}';
                        tpl += '{{#each died.LTC}}<tr><td>L:{{this}}</td><td>' + basei18n.stopped + '</td><td>SCRYPT</td></tr>{{/each}}';
                        tpl += 'test';
                        var temp = Handlebars.compile(tpl);
                        $('#status-table>tbody').html(temp(data));
                    }else{
                        $('#statusTable>tbody').html('<tr><td colspan="3">-</td></tr>');
                    }
                },
                error: function() {
                    $('#statusTable>tbody').html('<tr><td colspan="3">' + basei18n.loadFaild + '</td></tr>');
                },
                complete: function() {
                    doAgain();
                }
            });

            /*
             * 定时执行查询
             * 10s
             */

            function doAgain() {
                setTimeout(function() {
                    deviceInfo();
                }, 10000);
            }
        }

        $(document).ready(function() {
            deviceInfo();
        });

    }
})(window.jQuery);