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
                <td class="col-md-6 text-right">
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
                <td class="col-md-6 text-left">
                    <input type="checkbox"
                           data-toggle="toggle"
                           data-on="ARMED"
                           data-onstyle="danger"
                           data-off="&nbsp;DISARMED&nbsp;"
                           data-offstyle="warning"
                           data-class="fast"
                           data-size="small"
                           name="drone_mode_toggle_trigger"
                           id="drone_mode_toggle_trigger"
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
            
            $(document).on("<?php echo $connected_evt ?>", function (evt) {
                let set_mode_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['service'] ?>',
                    messageType : 'duckietown_msgs/SetDroneMode'
                });
            
                $('#<?php echo $id ?> #drone_mode_toggle_precheck').change(function() {
                    let checked = $(this).prop('checked');
                    if (!checked) {
                        $('#<?php echo $id ?> #drone_mode_toggle_trigger').bootstrapToggle('disable');
                    } else {
                        $('#<?php echo $id ?> #drone_mode_toggle_trigger').bootstrapToggle('enable');
                    }
                });
                
                $('#<?php echo $id ?> #drone_mode_toggle_trigger').change(function() {
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
                    let request = new ROSLIB.ServiceRequest({mode: {mode: mode}});
                    // send request
                    set_mode_srv.callService(request, function(_) {});
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
                        $('#<?php echo $id ?> #drone_mode_toggle_trigger').data("bs.toggle").off(true);
                        $('#<?php echo $id ?> #drone_mode_toggle_trigger').bootstrapToggle('disable');
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_precheck').data("bs.toggle").off(true);
                    } else {
                        // armed
                        $('#<?php echo $id ?> #drone_mode_toggle_trigger').bootstrapToggle('enable');
                        $('#<?php echo $id ?> #drone_mode_toggle_trigger').data("bs.toggle").on(true);
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
