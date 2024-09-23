<?php

use \system\classes\Core;
use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;


class Duckiedrone_Control extends BlockRenderer {
    
    static protected $ICON = [
        "class" => "fa",
        "name" => "gamepad"
    ];
    
    static protected $ARGUMENTS = [
        "ros_hostname" => [
            "name" => "ROSbridge hostname",
            "type" => "text",
            "mandatory" => False,
            "default" => ""
        ],
        "service_set_mode" => [
            "name" => "ROS Service (Set mode)",
            "type" => "text",
            "mandatory" => True
        ],
        "service_override_commands" => [
            "name" => "ROS Service (Set commands override)",
            "type" => "text",
            "mandatory" => True
        ],
        "param_override_prefix" => [
            "name" => "ROS Param Prefix (Command override)",
            "type" => "text",
            "mandatory" => True
        ],
        "topic_mode_current" => [
            "name" => "ROS Topic (Read mode)",
            "type" => "text",
            "mandatory" => True
        ],
        "topic_control" => [
            "name" => "ROS Topic (Joystick control)",
            "type" => "text",
            "mandatory" => True
        ],
        "topic_commands" => [
            "name" => "ROS Topic (Read commands)",
            "type" => "text",
            "mandatory" => True
        ],
        "frequency" => [
            "name" => "Frequency (Hz)",
            "type" => "number",
            "default" => 10,
            "mandatory" => True
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
            <tr style="height: 20px; font-weight: bold">
                <td class="col-md-1">
                    Channel
                </td>
                <td class="col-md-1 text-center">
                    Override
                </td>
                <td class="col-md-6 text-left">
                    Intensity
                </td>
                <td rowspan="5" class="col-md-2 text-center" style="padding: 0">
                    <canvas id="drone_control_commands_joy_keys" width="150px" height="150px"></canvas>
                </td>
                <td rowspan="5" class="col-md-2 text-center" style="padding: 0">
                    <div id="drone_control_commands_joy_stick" style="width:160px;height:160px;margin:0;"></div>
                </td>
            </tr>
            <?php
            $bars = [
                [
                    "id" => "roll",
                    "label" => "Roll"
                ],
                [
                    "id" => "pitch",
                    "label" => "Pitch"
                ],
                [
                    "id" => "yaw",
                    "label" => "Yaw"
                ],
                [
                    "id" => "throttle",
                    "label" => "Throttle"
                ],
            ];
            
            foreach ($bars as &$bar) {
                ?>
                <tr style="height: 20px">
                    <td class="col-md-1" style="text-align: right">
                        <p class=text-right" style="margin: 0"><?php echo $bar["label"] ?></p>
                    </td>
                    <td class="col-md-1">
                        <input type="checkbox"
                               data-toggle="toggle"
                               data-onstyle="primary"
                               data-offstyle="warning"
                               data-class="fast"
                               data-size="mini"
                               name="drone_control_commands_override_<?php echo $bar["id"] ?>"
                               id="drone_control_commands_override_<?php echo $bar["id"] ?>">
                    </td>
                    <td class="col-md-6 text-left">
                        <div class="progress" style="margin: 0; height: 16px">
                            <div class="progress-bar progress-bar-primary" role="progressbar"
                                 id="drone_control_commands_bar_<?php echo $bar["id"] ?>"
                                 aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"
                                 style="width: 0">
                                <span class="sr-only"></span>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php
            }
            ?>
        </table>
        
        <?php
        $ros_hostname = $args['ros_hostname'] ?? null;
        $ros_hostname = ROS::sanitize_hostname($ros_hostname);
        $connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
        ?>

        <!-- Include ROS -->
        <script src="<?php echo Core::getJSscriptURL('rosdb.js', 'ros') ?>"></script>
        <!-- Include Joy library -->
        <script src="<?php echo Core::getJSscriptURL('joy.js', 'duckietown_duckiedrone') ?>"></script>

        <script type="text/javascript">
            const CONST_YAW_DELTA = 90;
            const CONST_ROLL_DELTA = 45;
            const CONST_PITCH_DELTA = 45;
            const CONST_MID_VAL = 1500;
            
            const CONST_JOY_YAW_DEADBAND = 20;
            
            function drawArrow(ctx, fromx, fromy, tox, toy, arrowWidth, color) {
                //variables to be used when creating the arrow
                var headlen = 10;
                var angle = Math.atan2(toy - fromy, tox - fromx);
                
                ctx.save();
                ctx.strokeStyle = color;
                
                //starting path of the arrow from the start square to the end square and drawing the stroke
                ctx.beginPath();
                ctx.moveTo(fromx, fromy);
                ctx.lineTo(tox, toy);
                ctx.lineWidth = arrowWidth;
                ctx.stroke();
                
                //starting a new path from the head of the arrow to one of the sides of the point
                ctx.beginPath();
                ctx.moveTo(tox, toy);
                ctx.lineTo(
                    tox - headlen * Math.cos(angle - Math.PI / 7),
                    toy - headlen * Math.sin(angle - Math.PI / 7)
                );
                
                //path from the side point of the arrow, to the other side point
                ctx.lineTo(
                    tox - headlen * Math.cos(angle + Math.PI / 7),
                    toy - headlen * Math.sin(angle + Math.PI / 7)
                );
                
                //path from the side point back to the tip of the arrow, and then again to the opposite side point
                ctx.lineTo(tox, toy);
                ctx.lineTo(
                    tox - headlen * Math.cos(angle - Math.PI / 7),
                    toy - headlen * Math.sin(angle - Math.PI / 7)
                );
                
                //draws the paths created above
                ctx.stroke();
                ctx.restore();
            }
      
            // data types
            class JoyAxes {
                constructor(left_right, front_back, cw_ccw, up_down) {
                    this.throttle = up_down;
                    this.roll = left_right;
                    this.pitch = front_back;
                    this.yaw = cw_ccw;
                }
            
                get droneControlMsg() {
                    return {
                        roll: this.roll,
                        pitch: this.pitch,
                        yaw: this.yaw,
                        throttle: this.throttle
                    };
                }
            }
            
            class JoyButtons {
                constructor(arm, disarm, takeoff, land) {
                    this.arm = arm;
                    this.disarm = disarm;
                    this.takeoff = takeoff;
                    this.land = land;
                }
            
                get btnArr() {
                    return [this.arm, this.disarm, this.takeoff, this.land]
                }
            }
            
            class JoyXY {
                constructor(x, y) {
                    this.x = x;
                    this.y = y;
                }
            }
      
            $(document).on("<?php echo $connected_evt ?>", function (evt) {
                // TODO: this is the right way to do it
                let set_override_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['service_override_commands'] ?>',
                    messageType : 'duckietown_msgs/SetDroneCommandsOverride'
                });
                
                let roll_override = new ROSLIB.Param({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args['param_override_prefix'] ?>roll_override',
                });
                roll_override.get((v) => {
                    let status = (v)? 'on' : 'off';
                    $('#<?php echo $id ?> #drone_control_commands_override_roll').bootstrapToggle(status);
                });
                
                let pitch_override = new ROSLIB.Param({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args['param_override_prefix'] ?>pitch_override',
                });
                pitch_override.get((v) => {
                    let status = (v)? 'on' : 'off';
                    $('#<?php echo $id ?> #drone_control_commands_override_pitch').bootstrapToggle(status);
                });
                
                let yaw_override = new ROSLIB.Param({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args['param_override_prefix'] ?>yaw_override',
                });
                yaw_override.get((v) => {
                    let status = (v)? 'on' : 'off';
                    $('#<?php echo $id ?> #drone_control_commands_override_yaw').bootstrapToggle(status);
                });
                
                let throttle_override = new ROSLIB.Param({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args['param_override_prefix'] ?>throttle_override',
                });
                throttle_override.get((v) => {
                    let status = (v)? 'on' : 'off';
                    $('#<?php echo $id ?> #drone_control_commands_override_throttle').bootstrapToggle(status);
                });
                
                let set_mode_srv = new ROSLIB.Service({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name : '<?php echo $args['service_set_mode'] ?>',
                    messageType : 'duckietown_msgs/SetDroneMode'
                });
            
                let ctx = document.getElementById("drone_control_commands_joy_keys").getContext('2d');
                
                let roll_bar = $('#<?php echo $id ?> #drone_control_commands_bar_roll');
                let pitch_bar = $('#<?php echo $id ?> #drone_control_commands_bar_pitch');
                let yaw_bar = $('#<?php echo $id ?> #drone_control_commands_bar_yaw');
                let throttle_bar = $('#<?php echo $id ?> #drone_control_commands_bar_throttle');
                
                let range = (<?php echo $args['max_value'] ?> - <?php echo $args['min_value'] ?>).toFixed(1);
                
                let joy_stick_data = new JoyXY(0, 0);
                let joy_stick = new JoyStick('drone_control_commands_joy_stick', {}, function (data) {
                    joy_stick_data.x = data.x;
                    joy_stick_data.y = data.y;
                });
                let joy_keys = new Set([]);
                
                let armed = false;
            
                $('#<?php echo $id ?> #drone_control_commands_override_roll').change(function() {
                    let checked = $(this).prop('checked');
                    roll_override.set(checked);
                });
            
                $('#<?php echo $id ?> #drone_control_commands_override_pitch').change(function() {
                    let checked = $(this).prop('checked');
                    pitch_override.set(checked);
                });
            
                $('#<?php echo $id ?> #drone_control_commands_override_yaw').change(function() {
                    let checked = $(this).prop('checked');
                    yaw_override.set(checked);
                });
            
                $('#<?php echo $id ?> #drone_control_commands_override_throttle').change(function() {
                    let checked = $(this).prop('checked');
                    throttle_override.set(checked);
                });
                
                // subscribe to control signals
                (new ROSLIB.Topic({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args["topic_commands"] ?>',
                    messageType: 'duckietown_msgs/DroneControl',
                    queue_size: 1,
                    throttle_rate: <?php echo 1000 / $args['frequency'] ?>
                })).subscribe(function (message) {
                    let r = Math.floor(((message.roll - <?php echo $args['min_value'] ?>) / range) * 100);
                    roll_bar.width("{0}%".format(r));
                    let p = Math.floor(((message.pitch - <?php echo $args['min_value'] ?>) / range) * 100);
                    pitch_bar.width("{0}%".format(p));
                    let y = Math.floor(((message.yaw - <?php echo $args['min_value'] ?>) / range) * 100);
                    yaw_bar.width("{0}%".format(y));
                    let t = Math.floor(((message.throttle - <?php echo $args['min_value'] ?>) / range) * 100);
                    throttle_bar.width("{0}%".format(t));
                });
                
                //subscribe to mode
                (new ROSLIB.Topic({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args["topic_mode_current"] ?>',
                    messageType: 'mavros_msgs/State',
                    queue_size: 1,
                    throttle_rate: <?php echo 1000 / $args['frequency'] ?>
                })).subscribe(function (message) {
                    armed = message.armed;
                });
                
                // joystick commands publisher
                const joystick_topic = new ROSLIB.Topic({
                    ros: window.ros['<?php echo $ros_hostname ?>'],
                    name: '<?php echo $args["topic_control"] ?>',
                    messageType: 'duckietown_msgs/DroneControl',
                    queue_size: 1
                });
                
                function publish_joy_cmd(joy_axes, joy_buttons) {
                    let msg = new ROSLIB.Message(joy_axes.droneControlMsg)
                    joystick_topic.publish(msg);
                }
                
                function disarm_drone() {
                    // disarm drone
                    let request = new ROSLIB.ServiceRequest({mode: {mode: 0}});
                    // send request
                    set_mode_srv.callService(request, (_) => {});
                }
                
                function map_to_real(k_front, k_back, k_left, k_right) {
                    let x = parseInt(joy_stick_data.x);
                    let y = parseInt(joy_stick_data.y);
                    if (y < 0) y = 0;
                    let throttle = Math.round((y / 100.0) * 500 + 1100);
                    // deadzone for yaw
                    if (Math.abs(x) < CONST_JOY_YAW_DEADBAND) x = 0;
                    let yaw = Math.round(x / 100.0 * CONST_YAW_DELTA + CONST_MID_VAL);
                
                    let k_pitch = 0;
                    if (k_front) {
                        k_pitch = 1;
                    } else if (k_back) {
                        k_pitch = -1;
                    }
                    let pitch = CONST_PITCH_DELTA * k_pitch + CONST_MID_VAL;
                    
                    let k_roll = 0;
                    if (k_left) {
                        k_roll = -1;
                    } else if (k_right) {
                        k_roll = 1;
                    }
                    let roll = CONST_ROLL_DELTA * k_roll + CONST_MID_VAL;
                    
                    return new JoyAxes(roll, pitch, yaw, throttle);
                }
                
                $(document).on("keyup", (e) => {
                    let key = e.key.toLowerCase();
                    if (['w', 'a', 's', 'd', ' '].indexOf(key) >= 0 && armed) {
                        e.preventDefault();
                    }
                    joy_keys.delete(key);
                });
                
                $(document).on("keydown", (e) => {
                    let key = e.key.toLowerCase();
                    if (['w', 'a', 's', 'd', ' '].indexOf(key) >= 0 && armed) {
                        e.preventDefault();
                    }
                    if (key === " ") {
                        // space -> disarms
                        console.log("Disarming drone...");
                        disarm_drone();
                    } else {
                        joy_keys.add(key);
                    }
                });
                
                function main_loop() {
                    let front = joy_keys.has("w");
                    let back = joy_keys.has("s");
                    let left = joy_keys.has("a");
                    let right = joy_keys.has("d");
                    
                    let line_width = 20;
                    let pos = {
                        up: [50, 45, 50, 10],
                        down: [50, 55, 50, 90],
                        left: [45, 50, 10, 50],
                        right: [55, 50, 90, 50],
                    };
                    let scale = 1.2;
                    let offsetX = 20;
                    let offsetY = 20;
                    
                    for (let k in pos) {
                        pos[k] = pos[k].map(x => x * scale);
                        pos[k][0] += offsetX;
                        pos[k][2] += offsetX;
                        pos[k][1] += offsetY;
                        pos[k][3] += offsetY;
                    }
                    
                    drawArrow(ctx, ...pos.up, line_width, front ? 'green' : 'gray');
                    drawArrow(ctx, ...pos.down, line_width, back ? 'green' : 'gray');
                    drawArrow(ctx, ...pos.left, line_width, left ? 'green' : 'gray');
                    drawArrow(ctx, ...pos.right, line_width, right ? 'green' : 'gray');
                    
                    let joy_axes = map_to_real(front, back, left, right);
                    publish_joy_cmd(joy_axes, {});
                }
                
                setInterval(main_loop, 50);
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
    
}//Duckiedrone_Control
?>
