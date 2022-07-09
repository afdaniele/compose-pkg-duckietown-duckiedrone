<?php

use \system\classes\Core;
use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;


class Duckiedrone_Arming extends BlockRenderer {
    
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
        "service" => [
            "name" => "ROS Service (Set mode)",
            "type" => "text",
            "mandatory" => True
        ],
        "topic" => [
            "name" => "ROS Topic (Read mode)",
            "type" => "text",
            "mandatory" => True
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
                           data-on="CLEAR"
                           data-onstyle="primary"
                           data-off="PRE-CHECK"
                           data-offstyle="warning"
                           data-class="fast"
                           data-size="small"
                           name="drone_mode_toggle_precheck"
                           id="drone_mode_toggle_precheck">
                </td>
                <td class="col-md-4 text-left">
                    <input type="checkbox"
                           data-toggle="toggle"
                           data-on="ARMED"
                           data-onstyle="danger"
                           data-off="&nbsp;DISARMED&nbsp;"
                           data-offstyle="warning"
                           data-class="fast"
                           data-size="small"
                           name="drone_mode_toggle_arm"
                           id="drone_mode_toggle_arm"
                           disabled>
                </td>
                <td class="col-md-4 text-left" style="padding-left: 0">
                    <input type="checkbox"
                           data-toggle="toggle"
                           data-on="&nbsp;&nbsp;ESTOP&nbsp;&nbsp;"
                           data-onstyle="danger"
                           data-off="FLY"
                           data-offstyle="danger"
                           data-class="fast"
                           data-size="small"
                           name="drone_mode_toggle_fly"
                           id="drone_mode_toggle_fly"
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
            let _DRONE_MODE_DISARMED = 0;
            let _DRONE_MODE_ARMED = 1;
            let _DRONE_MODE_FLYING = 2;
            
            $(document).on("<?php echo $connected_evt ?>", function (evt) {
                let set_mode_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['service'] ?>',
                    messageType : 'duckietown_msgs/SetDroneMode'
                });
                
                function set_mode(mode) {
                    let request = new ROSLIB.ServiceRequest({mode: {mode: mode}});
                    // send request
                    set_mode_srv.callService(request, function(_) {});
                    console.log("Set mode: {0}".format(mode));
                }
            
                $('#<?php echo $id ?> #drone_mode_toggle_precheck').change(function() {
                    let checked = $(this).prop('checked');
                    if (!checked) {
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').bootstrapToggle('disable');
                    } else {
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').bootstrapToggle('enable');
                    }
                });
                
                $('#<?php echo $id ?> #drone_mode_toggle_arm').change(function() {
                    let checked = $(this).prop('checked');
                    let mode = _DRONE_MODE_DISARMED;
                    if (!checked) {
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('off');
                    } else {
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('disable');
                        mode = _DRONE_MODE_ARMED;
                    }
                    // arm/disarm drone
                    set_mode(mode);
                });
                
                $('#<?php echo $id ?> #drone_mode_toggle_fly').change(function() {
                    let checked = $(this).prop('checked');
                    if (!checked) {
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('off');
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').bootstrapToggle('off');
                        $('#<?php echo $id ?> #drone_mode_toggle_fly').bootstrapToggle('disable');
                    } else {
                        // set fly mode
                        set_mode(_DRONE_MODE_FLYING);
                    }
                });
                
                (new ROSLIB.Topic({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args["topic"] ?>',
                    messageType: 'duckietown_msgs/DroneMode',
                    queue_size: 1,
                    throttle_rate: <?php echo 1000 / $args['frequency'] ?>
                })).subscribe(function (message) {
                    if (message.mode < _DRONE_MODE_ARMED) {
                        // disarmed
                        $('#<?php echo $id ?> #drone_mode_toggle_fly').data("bs.toggle").off(true);
                        $('#<?php echo $id ?> #drone_mode_toggle_fly').bootstrapToggle('disable');
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').data("bs.toggle").off(true);
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').bootstrapToggle('disable');
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').data("bs.toggle").off(true);
                    } else if (message.mode == _DRONE_MODE_ARMED) {
                        // armed
                        $('#<?php echo $id ?> #drone_mode_toggle_fly').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_fly').data("bs.toggle").off(true);
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').data("bs.toggle").on(true);
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('disable');
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').data("bs.toggle").on(true);
                    } else if (message.mode == _DRONE_MODE_FLYING) {
                        // fly
                        $('#<?php echo $id ?> #drone_mode_toggle_fly').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_fly').data("bs.toggle").on(true);
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_arm').data("bs.toggle").on(true);
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('disable');
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').data("bs.toggle").on(true);
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
    }//render
    
}//Duckiedrone_Arming
?>
