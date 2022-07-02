<?php

use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;


class DuckietownMsgs_DroneMotorCommand extends BlockRenderer {
    
    static protected $ICON = [
        "class" => "fa",
        "name" => "exchange"
    ];
    
    static protected $ARGUMENTS = [
        "ros_hostname" => [
            "name" => "ROSbridge hostname",
            "type" => "text",
            "mandatory" => False,
            "default" => ""
        ],
        "topic" => [
            "name" => "ROS Topic",
            "type" => "text",
            "mandatory" => True
        ],
        "fps" => [
            "name" => "Update frequency (Hz)",
            "type" => "numeric",
            "mandatory" => True,
            "default" => 5
        ],
        "min_value" => [
            "name" => "Minimum value",
            "type" => "numeric",
            "mandatory" => True,
            "default" => 1000
        ],
        "max_value" => [
            "name" => "Maximum value",
            "type" => "numeric",
            "mandatory" => True,
            "default" => 2000
        ]
    ];
    
    protected static function render($id, &$args) {
        ?>
        <canvas class="resizable" style="width:100%; height:95%; padding:6px 16px"></canvas>
        <?php
        $ros_hostname = $args['ros_hostname'] ?? null;
        $ros_hostname = ROS::sanitize_hostname($ros_hostname);
        $connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
        ?>

        <script type="text/javascript">
            $(document).on("<?php echo $connected_evt ?>", function (evt) {
                // Subscribe to the given topic
                let subscriber = new ROSLIB.Topic({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args['topic'] ?>',
                    messageType: 'duckietown_msgs/DroneMotorCommand',
                    queue_size: 1,
                    throttle_rate: <?php echo 1000 / $args['fps'] ?>
                });

                let time_horizon_secs = 20;
                let color = Chart.helpers.color;
                let chart_config = {
                    type: 'line',
                    data: {
                        labels: range(time_horizon_secs - 1, 0, 1),
                        datasets: [{
                            label: 'Motor 1',
                            backgroundColor: color(window.chartColors.red).alpha(0.5).rgbString(),
                            borderColor: window.chartColors.red,
                            fill: true,
                            data: new Array(time_horizon_secs).fill(<?php echo $args["min_value"] ?>)
                        }, {
                            label: 'Motor 2',
                            backgroundColor: color(window.chartColors.blue).alpha(0.5).rgbString(),
                            borderColor: window.chartColors.blue,
                            fill: true,
                            data: new Array(time_horizon_secs).fill(<?php echo $args["min_value"] ?>)
                        }, {
                            label: 'Motor 3',
                            backgroundColor: color(window.chartColors.green).alpha(0.5).rgbString(),
                            borderColor: window.chartColors.green,
                            fill: true,
                            data: new Array(time_horizon_secs).fill(<?php echo $args["min_value"] ?>)
                        }, {
                            label: 'Motor 4',
                            backgroundColor: color(window.chartColors.purple).alpha(0.5).rgbString(),
                            borderColor: window.chartColors.purple,
                            fill: true,
                            data: new Array(time_horizon_secs).fill(<?php echo $args["min_value"] ?>)
                        }]
                    },
                    options: {
                        scales: {
                            xAxes: [{
                                scaleLabel: {
                                    display: false
                                }
                            }],
                            yAxes: [{
                                scaleLabel: {
                                    display: true,
                                    labelString: 'PWM'
                                },
                                ticks: {
                                    suggestedMin: <?php echo $args['min_value'] ?>,
                                    suggestedMax: <?php echo $args['max_value'] ?>
                                }
                            }]
                        },
                        tooltips: {
                            enabled: false
                        },
                        maintainAspectRatio: false
                    }
                };
                // create chart obj
                let ctx = $("#<?php echo $id ?> .block_renderer_container canvas")[0].getContext('2d');
                let chart = new Chart(ctx, chart_config);
                window.mission_control_page_blocks_data['<?php echo $id ?>'] = {
                    chart: chart,
                    config: chart_config
                };

                subscriber.subscribe(function (message) {
                    // get chart
                    let chart_desc = window.mission_control_page_blocks_data['<?php echo $id ?>'];
                    let chart = chart_desc.chart;
                    let config = chart_desc.config;
                    // cut the time horizon to `time_horizon_secs` points
                    config.data.datasets[0].data.shift();
                    config.data.datasets[1].data.shift();
                    config.data.datasets[2].data.shift();
                    config.data.datasets[3].data.shift();
                    // add new Y
                    config.data.datasets[0].data.push(
                        message.m1
                    );
                    config.data.datasets[1].data.push(
                        message.m2
                    );
                    config.data.datasets[2].data.push(
                        message.m3
                    );
                    config.data.datasets[3].data.push(
                        message.m4
                    );
                    // refresh chart
                    chart.update();
                });
            });
        </script>
        <?php
    }//render
    
}//DuckietownMsgs_DroneMotorCommand
?>
