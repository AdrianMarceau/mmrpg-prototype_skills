<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills_battle-start' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // Collect parameters that have been provided and are valid
        $boost_type = $this_skill->skill_parameters['type'];

        // Display a message showing this robot's skill is in effect
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('ambush-sound');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            'Damage from '.$this_robot->get_pronoun('possessive2').' super effective abilities will do even more damage!',
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

        // Return true on success
        return true;

    },
    'rpg-ability_trigger-damage_before' => function($objects){

        // Extract objects into the global scope
        extract($objects);

        // If this robot is not the aggressor, the skill doesn't activate
        if ($options->damage_initiator !== $this_robot){ return false; }
        if (empty($options->damage_target)){ return false; }
        $target_robot = $options->damage_target;

        // Make sure the target has a weakness to this robot's move, else return early
        if (!$target_robot->has_weakness($this_ability->ability_type)
            && !$target_robot->has_weakness($this_ability->ability_type2)){
            return false;
        }

        // Display a message showing this robot's skill is in effect
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('ambush-sound');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            'Damage from '.$this_robot->get_pronoun('possessive2').' super effective ability will do even more damage!',
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

        // Otherwise, we can straight-up double the damage amount because that's the effect
        $options->damage_amount *= 2;

        // Return true on success
        return true;

    }
);
?>
