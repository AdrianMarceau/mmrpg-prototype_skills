<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-robot_check-skills_end-of-turn' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If this robot is not active, the skill doesn't activate
        if ($this_robot->robot_position !== 'active'){ return false; }

        // Collect a reference to the target robot as we don't have one in this context
        if (empty($this_player->other_player)){ return false; }
        $target_player = $this_player->other_player;
        $target_robot = $target_player->get_active_robot();

        // Define a flag to hold whether or not we have something to copy
        $stat_boosts_to_copy = array();

        // Compare the stats of this robot to the target in case there are boosts to copy
        $stats_to_check = array('attack', 'defense', 'speed');
        foreach ($stats_to_check AS $key => $stat){
            $this_value = $this_robot->get_counter($stat.'_mods');
            $target_value = $target_robot->get_counter($stat.'_mods');
            if (!empty($target_value)
                && $target_value > $this_value){
                $stat_boosts_to_copy[$stat] = $target_value;
            }
        }

        // Check to see if any stats are waiting to be copies
        if (!empty($stat_boosts_to_copy)){

            // Print a message showing that this effect is taking place
            $this_robot->set_frame('taunt');
            $this_battle->queue_sound_effect('scan-start');
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                'Positive stat changes on the target are being emulated!',
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

        // Loop through the stats to boost and then actually do so one by one
        foreach ($stat_boosts_to_copy AS $boost_stat => $boost_amount){

            // Call the global stat boost function with customized options
            $trigger_text = 'The target\'s '.$boost_stat.' buffs were copied!';
            rpg_ability::ability_function_stat_boost($this_robot, $boost_stat, $boost_amount, $this_skill, array(
                'success_frame' => 9,
                'failure_frame' => 9,
                'extra_text' => $trigger_text
                ));

        }

        // Return true on success
        return true;

    }
);
?>
