<?php

use \system\classes\Core;
use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;

class Mavros_Arming extends BlockRenderer {

    static protected $ICON = [
        "class" => "fa",
        "name" => "key"
    ];

    static protected $ARGUMENTS = [
        "ros_hostname" => [
            "name" => "ROSbridge hostname",
            "type" => "text",
            "mandatory" => False,
            "default" => ""
        ],
        "arming_service" => [
            "name" => "Arming Service",
            "type" => "text",
            "mandatory" => True,
            "default" => "/mavros/cmd/arming"
        ],
        "set_mode_service" => [
            "name" => "Set Mode Service",
            "type" => "text",
            "mandatory" => True,
            "default" => "/mavros/set_mode"
        ],
        "state_topic" => [
            "name" => "State Topic",
            "type" => "text",
            "mandatory" => True,
            "default" => "/mavros/state"
        ],
        "frequency" => [
            "name" => "Frequency (Hz)",
            "type" => "number",
            "default" => 10,
            "mandatory" => True
        ],
        "background_color" => [
            "name" => "Background color",
            "type" => "color",
            "mandatory" => True,
            "default" => "#fff"
        ]
    ];

    protected static function render($id, &$args) {
        ?>
        <table class="resizable" style="height: 100%">
            <tr style="font-size: 18pt;">
                <td class="col-md-4 text-right" style="padding-right: 0">
                    <input type="checkbox"
                           data-toggle="toggle"
                           data-on="ARM"
                           data-onstyle="primary"
                           data-off="DISARM"
                           data-offstyle="warning"
                           data-class="fast"
                           data-size="small"
                           name="drone_arming_toggle"
                           id="drone_arming_toggle">
                </td>
                <td class="col-md-4 text-left">
                    <input type="checkbox"
                           data-toggle="toggle"
                           data-on="OFFBOARD"
                           data-onstyle="danger"
                           data-off="&nbsp;STABILIZE&nbsp;"
                           data-offstyle="warning"
                           data-class="fast"
                           data-size="small"
                           name="drone_mode_toggle"
                           id="drone_mode_toggle"
                           disabled>
                </td>
            </tr>
        </table>
        
        <?php
        $ros_hostname = $args['ros_hostname'] ?? null;
        $ros_hostname = ROS::sanitize_hostname($ros_hostname);
        $connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
        ?>

        <!-- Include ROS -->
        <script src="<?php echo Core::getJSscriptURL('rosdb.js', 'ros') ?>"></script>

        <script type="text/javascript">
            let _MODE_STABILIZE = 'STABILIZE';
            let _MODE_OFFBOARD = 'OFFBOARD';
            
            $(document).on("<?php echo $connected_evt ?>", function (evt) {
                let arming_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['arming_service'] ?>',
                    serviceType : 'mavros_msgs/CommandBool'
                });

                let set_mode_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['set_mode_service'] ?>',
                    serviceType : 'mavros_msgs/SetMode'
                });

                function set_arming(arm) {
                    let request = new ROSLIB.ServiceRequest({value: arm});
                    arming_srv.callService(request, function(response) {
                        console.log("Arming result: ", response.success);
                    });
                }

                function set_mode(mode) {
                    let request = new ROSLIB.ServiceRequest({custom_mode: mode});
                    set_mode_srv.callService(request, function(response) {
                        console.log("Mode change result: ", response.mode_sent);
                    });
                }

                $('#<?php echo $id ?> #drone_arming_toggle').change(function() {
                    let checked = $(this).prop('checked');
                    set_arming(checked);
                    if (!checked) {
                        $('#<?php echo $id ?> #drone_mode_toggle').bootstrapToggle('disable');
                    } else {
                        $('#<?php echo $id ?> #drone_mode_toggle').bootstrapToggle('enable');
                    }
                });

                $('#<?php echo $id ?> #drone_mode_toggle').change(function() {
                    let checked = $(this).prop('checked');
                    let mode = checked ? _MODE_OFFBOARD : _MODE_STABILIZE;
                    set_mode(mode);
                });

                (new ROSLIB.Topic({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args["state_topic"] ?>',
                    messageType: 'mavros_msgs/State',
                    queue_size: 1,
                    throttle_rate: <?php echo 1000 / $args['frequency'] ?>
                })).subscribe(function (message) {
                    if (!message.armed) {
                        $('#<?php echo $id ?> #drone_mode_toggle').bootstrapToggle('disable');
                        $('#<?php echo $id ?> #drone_arming_toggle').bootstrapToggle('off');
                    } else {
                        $('#<?php echo $id ?> #drone_mode_toggle').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_arming_toggle').bootstrapToggle('on');
                    }

                    if (message.mode === _MODE_OFFBOARD) {
                        $('#<?php echo $id ?> #drone_mode_toggle').bootstrapToggle('on');
                    } else {
                        $('#<?php echo $id ?> #drone_mode_toggle').bootstrapToggle('off');
                    }
                });
            });
        </script>
        
        <?php
        ROS::connect($ros_hostname);
        ?>

        <style type="text/css">
            #<?php echo $id ?>{
                background-color: <?php echo $args['background_color'] ?>;
            }
        </style>
        <?php
    }
}
?>
