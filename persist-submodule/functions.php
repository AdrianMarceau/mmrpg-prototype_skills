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
    'rpg-ability_trigger-damage_pre-damage' => function($objects){
        //error_log('rpg-ability_trigger-damage_pre-damage() for '.$objects['this_robot']->robot_string.'//'.$objects['this_skill']->skill_token);

        // Extract objects into the global scope
        extract($objects);

        // If this robot is not the recipient, the skill doesn't activate
        if ($options->damage_target !== $this_robot){ return false; }
        //error_log('$this_robot->robot_string = '.print_r($this_robot->robot_string, true));
        //error_log('$this_robot->robot_status: '.print_r($this_robot->robot_status, true));
        //error_log('$this_robot->robot_energy = '.print_r($this_robot->robot_energy, true));
        //error_log('$this_robot->robot_base_energy = '.print_r($this_robot->robot_base_energy, true));
        //error_log('$this_ability->ability_results[\'this_amount\'] = '.print_r($this_ability->ability_results['this_amount'], true));

        // Check to see if this robot had full health before the damage
        $full_health_before_damage = $this_robot->get_flag('full_health_before_damage');
        //error_log('full_health_before_damage: '.($full_health_before_damage ? 'true' : 'false'));
        $this_robot->set_flag('full_health_before_damage', false);

        // Check to see if this robot is now at zero energy and thus would be disabled
        $calculated_damage_amount = $this_ability->ability_results['this_amount'];
        $health_after_damage = $this_robot->robot_energy - $calculated_damage_amount;
        $zero_health_after_damage = $health_after_damage <= 0 ? true : false;
        //error_log('$calculated_damage_amount: '.print_r($calculated_damage_amount, true));
        //error_log('$health_after_damage: '.print_r($health_after_damage, true));
        //error_log('$zero_health_after_damage: '.($zero_health_after_damage ? 'true' : 'false'));

        // Check to see if this was a legit OHKO from full health otherwise the skill doesn't activate
        if (!$full_health_before_damage || !$zero_health_after_damage){ return false; }

        // Otherwise, reduce the damage to until only 1 energy would remain
        $this_ability->ability_results['this_amount'] = $this_robot->robot_energy - 1;
        //error_log('NEW $this_ability->ability_results[\'this_amount\'] = '.print_r($this_ability->ability_results['this_amount'], true));

        // Display a message showing this robot's skill is in effect
        $this_robot->set_flag('persist_submodule_triggered', true);

        // Return true on success
        return true;

    },
    'rpg-ability_trigger-damage_after' => function($objects){
        //error_log('rpg-ability_trigger-damage_pre-damage() for '.$objects['this_robot']->robot_string.'//'.$objects['this_skill']->skill_token);

        // Extract objects into the global scope
        extract($objects);

        // If this robot is not the recipient, the skill doesn't activate
        if ($options->damage_target !== $this_robot){ return false; }

        // If there is no message to display, we can return early
        if (empty($this_robot->get_flag('persist_submodule_triggered'))){ return false; }
        $this_robot->unset_flag('persist_submodule_triggered');

        // Display a message showing this robot's skill is in effect
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('use-recovery-item');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            'The skill protects '.$this_robot->get_pronoun('object').' from being disabled by a OHKO!',
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
