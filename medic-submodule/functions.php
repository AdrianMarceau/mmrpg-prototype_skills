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

        // If this robot is not on the bench, the skill doesn't activate
        if ($this_robot->robot_position !== 'bench'){ return false; }

        // Create an array to hold positions of special interest (stats to reset)
        $positions_of_interest = array();

        // Collect a list of active robots from this player so we can loop through 'em
        $this_player_robot = $this_player->values['robots_active'];
        if (!isset($this_robot)){ $this_robot = $this_player->get_active_robot(); }

        // Collect a list of robots from this player and then loop through 'em
        if (!empty($this_player_robot)){
            foreach ($this_player_robot AS $robot_key => $robot_info){
                if ($robot_info['robot_id'] === $this_robot->robot_id){ $robot = $this_robot; }
                else { $robot = rpg_game::get_robot($this_battle, $this_player, array('robot_id' => $robot_info['robot_id'])); }
                if (empty($robot)){ unset($robot); continue; }
                // Check to see if this robot's energy has been lowered
                if ($robot->robot_energy < $robot->robot_base_energy){
                    $positions_of_interest[] = array(
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
        $certain_text = count($positions_of_interest) === 1 ? 'a wounded robot' : 'wounded robots';
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
        $energy_recovery_percent = ceil(30 / count($positions_of_interest));
        foreach ($positions_of_interest AS $key => $recipient){

            // Show the camera looking at this robot first
            $this_robot->set_frame('summon');
            $recipient['robot']->set_frame('base');
            $this_battle->events_create($this_robot, false, '', '',
                array(
                    'this_skill' => $this_skill,
                    'canvas_show_this_skill_overlay' => false,
                    'canvas_show_this_skill_underlay' => true,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $recipient['robot']->player->player_side,
                    'event_flag_camera_focus' => $recipient['robot']->robot_position,
                    'event_flag_camera_depth' => $recipient['robot']->robot_key
                    )
                );
            $recipient['robot']->reset_frame();
            $this_robot->reset_frame();

            // Increase this robot's energy stat
            $this_skill->recovery_options_update(array(
                'kind' => 'energy',
                'percent' => true,
                'modifiers' => true,
                'frame' => 'taunt',
                'success' => array(0, -2, 0, -10, $recipient['robot']->print_name().'\'s energy was restored!'),
                'failure' => array(9, -2, 0, -10, $recipient['robot']->print_name().'\'s energy was not affected...')
                ));
            //$energy_recovery_percent = 10;
            $energy_recovery_amount = ceil($recipient['robot']->robot_base_energy * ($energy_recovery_percent / 100));
            $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
            $recipient['robot']->trigger_recovery($this_robot, $this_skill, $energy_recovery_amount, true, $trigger_options);
            $this_robot->reset_frame();

        }

        // Return true on success
        return true;

    }
);
?>
