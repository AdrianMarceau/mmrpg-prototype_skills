<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills_pre-check-health' => function($objects){
        //error_log('rpg-robot_check-skills_pre-check-health() for '.$objects['this_robot']->robot_string.'//'.$objects['this_skill']->skill_token);

        // Extract all objects into the current scope
        extract($objects);

        // Take note of whether or not this robot is starting with full health
        $full_health_before_damage = $this_robot->robot_energy >= $this_robot->robot_base_energy ? true : false;
        $this_robot->set_flag('full_health_before_damage', $full_health_before_damage);
        //error_log('$this_robot->robot_energy: '.print_r($this_robot->robot_energy, true));
        //error_log('$this_robot->robot_base_energy: '.print_r($this_robot->robot_base_energy, true));
        //error_log('full_health_before_damage: '.($full_health_before_damage ? 'true' : 'false'));

        // Return true on success
        return true;

    },
    'rpg-robot_check-skills_end-of-turn' => function($objects){
        //error_log('rpg-robot_check-skills_end-of-turn() for '.$objects['this_robot']->robot_string.'//'.$objects['this_skill']->skill_token);

        // Extract all objects into the current scope
        extract($objects);

        // Unset the full health check flag now that we're done with it
        $this_robot->unset_flag('full_health_before_damage');

        // Return true on success
        return true;

    },
    'rpg-ability_trigger-damage_after' => function($objects){
        //error_log('rpg-ability_trigger-damage_after() for '.$objects['this_robot']->robot_string.'//'.$objects['this_skill']->skill_token);

        // Extract objects into the global scope
        extract($objects);

        // If this robot is not the recipient, the skill doesn't activate
        if ($options->damage_target !== $this_robot){ return false; }
        //if (empty($options->damage_target)){ return false; }
        //$target_robot = $options->damage_target;
        //error_log('$this_robot->robot_energy: '.print_r($this_robot->robot_energy, true));
        //error_log('$this_robot->robot_status: '.print_r($this_robot->robot_status, true));

        // Check to see if this robot had full health before the damage
        $full_health_before_damage = $this_robot->get_flag('full_health_before_damage');
        //error_log('full_health_before_damage: '.($full_health_before_damage ? 'true' : 'false'));

        // Check to see if this robot is now at zero energy and thus would be disabled
        $zero_health_after_damage = $this_robot->robot_energy <= 0 ? true : false;
        //error_log('$zero_health_after_damage: '.($zero_health_after_damage ? 'true' : 'false'));

        // Check to see if this was a legit OHKO from full health otherwise the skill doesn't activate
        $this_robot->set_flag('full_health_before_damage', false);
        if (!$full_health_before_damage || !$zero_health_after_damage){ return false; }

        // Revive this robot with the appropriate energy and remove any flags
        $this_robot->set_energy(1);
        $this_robot->set_status('active');
        $this_robot->unset_flag('apply_disabled_state');
        $this_robot->unset_flag('hidden');
        $this_robot->unset_attachment('object_defeat-explosion');

        // Display a message showing this robot's skill is in effect
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('use-recovery-item');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            ucfirst($this_robot->get_pronoun('subject')).' survived the attack and restored '.$this_robot->get_pronoun('possessive2').' data!',
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

    }
);
$functions['rpg-robot_check-skills_battle-start'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_pre-check-health']($objects, true);
};
$functions['rpg-robot_check-skills_turn-start'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_pre-check-health']($objects, true);
};
$functions['rpg-battle_switch-in_after'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_pre-check-health']($objects, false);
};
?>
