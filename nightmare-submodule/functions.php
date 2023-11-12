<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-robot_check-skills_turn-start' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If this robot is not active, the skill doesn't activate
        if ($this_robot->robot_position !== 'active'){ return false; }

        // Collect a reference to the target robot as we don't have one in this context
        if (empty($this_player->other_player)){ return false; }
        $target_player = $this_player->other_player;
        $target_robot = $target_player->get_active_robot();

        // Collect a reference to this robot's core type
        $this_core_type = $this_robot->get_core();

        // If the core type is not set, check to see if the robot has an item and if so, use that as the core type
        if (empty($this_core_type)
            && $this_robot->has_item()
            && strstr($this_robot->robot_item, '-core')){
            $this_core_type = str_replace('-core', '', $this_robot->robot_item);
            }

        // If the core type is still not set, the skill doesn't activate, sorry
        if (empty($this_core_type)){
            return false;
            }

        // If the target already has a weakness to this robot's core type, we don't have to do anything
        if ($target_robot->has_weakness($this_core_type)){ return false; }

        // Otherwise we need to collect the target robot's weaknessed, append this new one, then save
        $target_weaknesses = $target_robot->get_weaknesses();
        $target_weaknesses[] = $this_core_type;
        $target_robot->set_weaknesses($target_weaknesses);

        // Do the same thing to their base weaknesesses, grabbing base_weaknesses and then appending the new one and then saving
        $target_base_weaknesses = $target_robot->get_base_weaknesses();
        $target_base_weaknesses[] = $this_core_type;
        $target_robot->set_base_weaknesses($target_base_weaknesses);

        // Print a message showing that this effect is taking place
        $subject_pretext = $this_robot->get_pronoun('subject');
        $subject_pretext .= ($subject_pretext === 'they' ? '\'re' : '\'s');
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in! <br />'.
            ucfirst($subject_pretext).' sending out negative vibes!',
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

        // Print a message showing that this effect is taking place
        $target_robot->set_frame('damage');
        $this_battle->queue_sound_effect('debuff-received');
        $this_battle->events_create($target_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $target_robot->print_name().' feels funny! <br />'.
            ucfirst($target_robot->get_pronoun('subject')).' suddenly found '.$target_robot->get_pronoun('reflexive').' weak to the '.rpg_type::print_span($this_core_type).' type!',
            array(
                'this_skill' => $this_skill,
                'canvas_show_this_skill_overlay' => false,
                'canvas_show_this_skill_underlay' => false,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $target_robot->player->player_side,
                'event_flag_camera_focus' => $target_robot->robot_position,
                'event_flag_camera_depth' => $target_robot->robot_key
                )
            );
        $target_robot->reset_frame();

        // Return true on success
        return true;

    }
);
$functions['rpg-battle_switch-in_after'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_turn-start']($objects, false);
};
?>
