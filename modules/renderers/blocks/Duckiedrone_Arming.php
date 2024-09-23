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
            "default" => "~/flight_controller_node/arm"
        ],
        "kill_switch" => [
            "name" => "Kill Switch Service",
            "type" => "text",
            "mandatory" => True,
            "default" => "~/mavros/cmd/command"
        ],
        "set_mode_service" => [
            "name" => "Set Mode Service",
            "type" => "text",
            "mandatory" => True,
            "default" => "~/flight_controller_node/set_mode"
        ],
        "state_topic" => [
            "name" => "State Topic",
            "type" => "text",
            "mandatory" => True,
            "default" => "~/flight_controller_node/mode/current"
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
            
            // Track the success of the arming service call
            let armingSuccess = false;

            $(document).on("<?php echo $connected_evt ?>", function (evt) {
                let arming_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['arming_service'] ?>',
                    serviceType : 'mavros_msgs/CommandBool'
                });

                let kill_switch_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['kill_switch'] ?>',
                    serviceType : 'mavros/CommandLong'
                });

                let set_mode_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['set_mode_service'] ?>',
                    serviceType : 'mavros_msgs/SetMode'
                });

                function set_arming(arm) {
                    console.log("ros service hostname:", arming_srv.ros.hostname);
                    console.log("Calling arming service: ", arming_srv.name);
                    console.log("Setting arming to: ", arm);
                                        
                    if (arm) {
                        // Call the arming service
                        let request = new ROSLIB.ServiceRequest({value: arm}); // `arm` is the desired boolean value
                        arming_srv.callService(request, function(response) {
                            console.log("Arming result: ", response.success);
                            armingSuccess = response.success;
                        });
                    } else {
                        // Call the kill switch service
                        let request = new ROSLIB.ServiceRequest({
                            broadcast: false,
                            command: 400, // MAV_CMD_COMPONENT_ARM_DISARM
                            confirmation: 0,
                            param1: 0.0, // Disarm
                            param2: 21196.0,
                            param3: 0.0,
                            param4: 0.0,
                            param5: 0.0,
                            param6: 0.0,
                            param7: 0.0
                        });
                        kill_switch_srv.callService(request, function(response) {
                            console.log("Kill switch result: ", response.success);
                            armingSuccess = response.success;
                        });
                    }

                }

                function set_mode(mode) {
                    let request = new ROSLIB.ServiceRequest({custom_mode: mode});
                    set_mode_srv.callService(request, function(response) {
                        console.log("Mode change result: ", response.mode_sent);
                    });
                }

                $('#<?php echo $id ?> #drone_arming_toggle').off().change(function() {
                    let checked = $(this).prop('checked');
                    console.log("Arming toggle changed. Checked: ", checked);
                    set_arming(checked);  // Trigger the arming service call
                });

                // Subscribe to the State topic
                (new ROSLIB.Topic({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args["state_topic"] ?>',
                    messageType: 'mavros_msgs/State',
                    queue_size: 1,
                    throttle_rate: <?php echo 1000 / $args['frequency'] ?>
                })).subscribe(function (message) {
                    console.log("State topic message received. Armed:", message.armed);
                    
                    // Only update the toggle if both the service call was successful and the armed state is true
                    if (armingSuccess && message.armed) {
                        $('#<?php echo $id ?> #drone_arming_toggle').bootstrapToggle('on');
                    } else if (!armingSuccess || !message.armed) {
                        $('#<?php echo $id ?> #drone_arming_toggle').bootstrapToggle('off');
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
