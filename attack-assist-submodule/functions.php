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
        $stat_boost_kind = 'attack';

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
                if (empty($robot)
                    || $robot->robot_status === 'disabled'
                    || $robot->robot_position === 'bench'){
                    unset($robot);
                    continue;
                }
                // Check to see if this robot's weapons have been lowered
                if ((!$robot->has_item('reverse-module') && $robot->counters[$stat_boost_kind.'_mods'] < MMRPG_SETTINGS_STATS_MOD_MAX)
                    || ($robot->has_item('reverse-module') && $robot->counters[$stat_boost_kind.'_mods'] > MMRPG_SETTINGS_STATS_MOD_MIN)){
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
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $pronoun_subtext = $this_robot->get_pronoun('subject');
        $support_subtext = $pronoun_subtext === 'they' ? 'support' : 'supports';
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name_s().' '.$this_skill->print_name().' skill kicked in! <br />'.
            ucfirst($pronoun_subtext).' '.$support_subtext.' '.$this_robot->get_pronoun('possessive2').' teammate from the bench!',
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
        foreach ($positions_of_interest AS $key => $recipient){

            // Call the global stat boost function with customized options
            $this_robot->set_frame('summon');
            $trigger_text = '';
            rpg_ability::ability_function_stat_boost($recipient['robot'], $stat_boost_kind, 1, $this_skill, array(
                'success_frame' => 9,
                'failure_frame' => 9,
                'extra_text' => $trigger_text,
                'skip_canvas_header' => false
                ));
            $this_robot->reset_frame();

        }

        // Return true on success
        return true;

    }
);
?>
