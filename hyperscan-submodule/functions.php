<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills_battle-start' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Turn ON the ability to see skills/abilities during the scan
        // by adding this robot's ID to the player's hyperscan list
        $hyperscan_robots = $this_player->get_value('hyperscan_robots');
        if (empty($hyperscan_robots)){ $hyperscan_robots = array(); }
        if (!in_array($this_robot->robot_id, $hyperscan_robots)){ $hyperscan_robots[] = $this_robot->robot_id; }
        $this_player->set_value('hyperscan_robots', $hyperscan_robots);

        // Only bother printing this message if the player is a human
        if ($this_player->player_autopilot === false){
            // Print a message showing that this effect is taking place
            $this_robot->set_frame('taunt');
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                'Target robot abilities and skills can be scanned now!',
                array(
                    'this_skill' => $this_skill,
                    'canvas_show_this_skill_overlay' => false,
                    'canvas_show_this_skill_underlay' => true,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $this_robot->player->player_side,
                    'event_flag_camera_focus' => $this_robot->robot_position,
                    'event_flag_camera_depth' => $this_robot->robot_key
                    )
                );
            $this_robot->reset_frame();
        }

        // Return true on success
        return true;

    }
);
?>
