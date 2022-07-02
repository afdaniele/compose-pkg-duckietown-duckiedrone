<?php

use \system\classes\Core;
use \system\classes\BlockRenderer;
use \system\packages\ros\ROS;


class Duckiedrone_Heartbeats_Monitor extends BlockRenderer {
    
    static protected $ICON = [
        "class" => "glyphicon",
        "name" => "tasks"
    ];
    
    static protected $ARGUMENTS = [
        "ros_hostname" => [
            "name" => "ROSbridge hostname",
            "type" => "text",
            "mandatory" => False,
            "default" => ""
        ],
        "topic1" => [
            "name" => "ROS Topic (1)",
            "type" => "text",
            "mandatory" => False,
            "default" => null
        ],
        "label1" => [
            "name" => "Heartbeat label (1)",
            "type" => "text",
            "mandatory" => False,
            "default" => null
        ],
        "topic2" => [
            "name" => "ROS Topic (2)",
            "type" => "text",
            "mandatory" => False,
            "default" => null
        ],
        "label2" => [
            "name" => "Heartbeat label (2)",
            "type" => "text",
            "mandatory" => False,
            "default" => null
        ],
        "topic3" => [
            "name" => "ROS Topic (3)",
            "type" => "text",
            "mandatory" => False,
            "default" => null
        ],
        "label3" => [
            "name" => "Heartbeat label (3)",
            "type" => "text",
            "mandatory" => False,
            "default" => null
        ],
        "topic4" => [
            "name" => "ROS Topic (4)",
            "type" => "text",
            "mandatory" => False,
            "default" => null
        ],
        "label4" => [
            "name" => "Heartbeat label (4)",
            "type" => "text",
            "mandatory" => False,
            "default" => null
        ],
        "threshold" => [
            "name" => "Threshold in seconds before the heartbeat disappears",
            "type" => "number",
            "mandatory" => True,
            "default" => 2
        ],
        "frequency" => [
            "name" => "Frequency (Hz)",
            "type" => "number",
            "default" => 5,
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
        <div id="block_content">
            <table class="resizable" style="height: 100%">
                <tr style="font-size: 18pt;">
                    <?php
                    for ($i = 1; $i <= 4; $i++) {
                        if (is_null($args["topic{$i}"]) || strlen($args["topic{$i}"]) <= 0)
                            continue
                        ?>
                        <td class="col-md-3 heartbeats-monitor-topic<?php echo $i ?>">
                            <span class="glyphicon glyphicon-heart" aria-hidden="true"></span>
                        </td>
                    <?php
                    }
                    ?>
                </tr>
                <tr style="font-family: monospace; font-size: 8pt"><?php
                    for ($i = 1; $i <= 4; $i++) {
                        if (is_null($args["topic{$i}"]) || strlen($args["topic{$i}"]) <= 0)
                            continue
                        ?>
                        <td class="col-md-3 heartbeats-monitor-topic<?php echo $i ?>">
                            <?php echo $args["label{$i}"] ?>
                        </td>
                    <?php
                    }
                    ?>
                </tr>
            </table>
        </div>
        
        <?php
        $ros_hostname = $args['ros_hostname'] ?? null;
        $ros_hostname = ROS::sanitize_hostname($ros_hostname);
        $connected_evt = ROS::get_event(ROS::$ROSBRIDGE_CONNECTED, $ros_hostname);
        ?>

        <!-- Include ROS -->
        <script src="<?php echo Core::getJSscriptURL('rosdb.js', 'ros') ?>"></script>

        <script type="text/javascript">
            $(document).on("<?php echo $connected_evt ?>", function (evt) {
                let _heartbeats = {};
                
                <?php
                for ($i = 1; $i <= 4; $i++) {
                    if (is_null($args["topic{$i}"]) || strlen($args["topic{$i}"]) <= 0)
                        continue
                    ?>
                    _heartbeats['<?php echo "topic{$i}" ?>'] = 0.0;
                    (new ROSLIB.Topic({
                        ros: window.ros['<?php echo $ros_hostname ?>'],
                        name: '<?php echo $args["topic{$i}"] ?>',
                        messageType: 'std_msgs/Empty',
                        queue_size: 1,
                        throttle_rate: <?php echo 1000 / $args['frequency'] ?>
                    })).subscribe(function (message) {
                        _heartbeats['<?php echo "topic{$i}" ?>'] = seconds_since_epoch();
                        _heartbeat_turn_to_color("<?php echo "topic{$i}" ?>", "green");
                    });
                    <?php
                }
                ?>
                
                function _heartbeat_turn_to_color(label, color){
                    $("#<?php echo $id ?> .heartbeats-monitor-{0}".format(label)).css("color", color);
                }
                
                function _update_heartbeats_monitor(){
                    for (let label in _heartbeats) {
                        let t = _heartbeats[label];
                        if (seconds_since_epoch() - t > <?php echo $args["threshold"] ?>) {
                            _heartbeat_turn_to_color(label, "darkred");
                        }
                    }
                }

                setInterval(_update_heartbeats_monitor, 1000 * (1.0 / <?php echo $args['frequency'] ?>))
            });
        </script>
        
        <?php
        ROS::connect($ros_hostname);
        ?>

        <style type="text/css">
            #<?php echo $id ?>{
                background-color: <?php echo $args['background_color'] ?>;
            }
            
            #<?php echo $id ?> .glyphicon{
                margin-bottom: 8px;
            }
        </style>
        <?php
    }//render
    
}//Duckiedrone_Heartbeats_Monitor
?>
