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

        // Define the stat token we will be checking mods for
        $anti_stat_token = 'defense';

        // Create an array to hold positions of special interest (stats to reset)
        $positions_of_interest = array();

        // Collect a list of active robots from the target player so we can loop through 'em
        $target_player_robots = $target_player->values['robots_active'];
        if (!isset($target_robot)){ $target_robot = $target_player->get_active_robot(); }

        // Collect a list of robots from this player and then loop through 'em
        if (!empty($target_player_robots)){
            foreach ($target_player_robots AS $robot_key => $robot_info){
                if ($robot_info['robot_id'] === $target_robot->robot_id){ $robot = $target_robot; }
                else { $robot = rpg_game::get_robot($this_battle, $target_player, array('robot_id' => $robot_info['robot_id'])); }
                if (empty($robot)){ unset($robot); continue; }
                // Check to see if this robot's stat has been modified
                $anti_stat_value = isset($robot->counters[$anti_stat_token.'_mods']) ? $robot->counters[$anti_stat_token.'_mods'] : 0;
                if ($anti_stat_value > 0 || $anti_stat_value < 0){
                    $positions_of_interest[] = array(
                        'stat' => $anti_stat_token,
                        'value' => $anti_stat_value,
                        'player' => $target_player,
                        'robot' => $robot,
                        );
                }
                unset($robot);
            }
        }

        // Collect a list of active robots from this player so we can loop through 'em
        $this_player_robot = $this_player->values['robots_active'];
        if (!isset($this_robot)){ $this_robot = $this_player->get_active_robot(); }

        // Collect a list of robots from this player and then loop through 'em
        if (!empty($this_player_robot)){
            foreach ($this_player_robot AS $robot_key => $robot_info){
                if ($robot_info['robot_id'] === $this_robot->robot_id){ $robot = $this_robot; }
                else { $robot = rpg_game::get_robot($this_battle, $this_player, array('robot_id' => $robot_info['robot_id'])); }
                if (empty($robot)){ unset($robot); continue; }
                // Check to see if this robot's stat has been modified
                $anti_stat_value = isset($robot->counters[$anti_stat_token.'_mods']) ? $robot->counters[$anti_stat_token.'_mods'] : 0;
                if ($anti_stat_value > 0 || $anti_stat_value < 0){
                    $positions_of_interest[] = array(
                        'stat' => $anti_stat_token,
                        'value' => $anti_stat_value,
                        'player' => $this_player,
                        'robot' => $robot,
                        );
                }
                unset($robot);
            }
        }

        // If there aren't any points of interest, we should return now
        if (empty($positions_of_interest)){ return false; }

        // Otherwise, print a message showing that this effect is taking place
        $certain_text = count($positions_of_interest) === 1 ? 'a temporal disturbance' : 'temporal disturbances';
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().' took notice of '.$certain_text.' on the field...',
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

        // Loop through and show the camera looking at them one-by-one
        foreach ($positions_of_interest AS $key => $stat_to_remove){

            // Show the camera looking at this robot first
            $this_robot->set_frame('summon');
            $stat_to_remove['robot']->set_frame('base');
            $this_battle->queue_sound_effect('scan-start');
            $this_battle->events_create($this_robot, false, '', '',
                array(
                    'this_skill' => $this_skill,
                    'canvas_show_this_skill_overlay' => false,
                    'canvas_show_this_skill_underlay' => true,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $stat_to_remove['robot']->player->player_side,
                    'event_flag_camera_focus' => $stat_to_remove['robot']->robot_position,
                    'event_flag_camera_depth' => $stat_to_remove['robot']->robot_key
                    )
                );
            $stat_to_remove['robot']->reset_frame();

            // If this robot has a stat-based skill, display the trigger text separately
            $trigger_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!';
            if (!empty($this_robot->robot_item) && preg_match('/^(guard|reverse|xtreme)-module$/', $this_robot->robot_item)){
                $this_skill->target_options_update(array('frame' => 'summon', 'success' => array(9, 0, 0, -10, $trigger_text)));
                $this_robot->trigger_target($this_robot, $this_skill, array('prevent_default_text' => true));
                $trigger_text = '';
            }
            // And now we can actually remove the stat change while showing the skill popup for each
            $this_robot->set_frame('taunt');
            //$this_battle->queue_sound_effect('debuff-received');
            rpg_ability::ability_function_stat_reset($stat_to_remove['robot'], $stat_to_remove['stat'], $this_skill, array(
                'initiator_robot' => $this_robot,
                'success_frame' => 9,
                'failure_frame' => 9,
                'extra_text' => $trigger_text
                ));
            $this_robot->reset_frame();

        }

        // Return true on success
        return true;

    }
);
?>
