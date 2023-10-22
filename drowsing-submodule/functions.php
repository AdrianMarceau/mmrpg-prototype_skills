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

        // If this robot's health is already full, the skill doesn't activate
        if ($this_robot->robot_energy === $this_robot->robot_base_energy){ return false; }

        // Collect a reference to the target robot as we don't have one in this context
        if (empty($this_player->other_player)){ return false; }
        $target_player = $this_player->other_player;
        $target_robot = $target_player->get_active_robot();

        // Print a message showing that this effect is taking place
        $this_robot->set_frame('defend');
        $this_battle->queue_sound_effect('downward-impact');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            $this_robot->get_pronoun('subject').' decided to recover some health by resting!',
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

        // Increase this robot's energy stat
        $this_skill->recovery_options_update(array(
            'kind' => 'energy',
            'percent' => true,
            'modifiers' => true,
            'frame' => 'taunt',
            'success' => array(0, -2, 0, -10, $this_robot->print_name().'\'s energy was restored!'),
            'failure' => array(9, -2, 0, -10, $this_robot->print_name().'\'s energy was not affected...')
            ));
        $energy_recovery_percent = 20;
        $energy_recovery_amount = ceil($this_robot->robot_base_energy * ($energy_recovery_percent / 100));
        $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false);
        $this_robot->trigger_recovery($this_robot, $this_skill, $energy_recovery_amount, true, $trigger_options);

        // Call the global stat boost function with customized options
        $trigger_text = '...but the resting slowed '.$this_robot->print_name().'\'s movement!';
        rpg_ability::ability_function_stat_break($this_robot, 'speed', 1, $this_skill, array(
            'success_frame' => 9,
            'failure_frame' => 9,
            'extra_text' => $trigger_text
            ));

        // Return true on success
        return true;

    }
);
?>
