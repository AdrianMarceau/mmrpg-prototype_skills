<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - type (string: cutter/impact/freeze/etc.)

Examples:
    {"type":"nature"}
        would allow Nature-type abilities to be used at reduced cost
    {"type":"flame"}
        would allow Flame-type abilities to be used at reduced cost

==================================================
*/
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills_update-wellsprings' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // Collect parameters that have been provided and are valid
        $wellspring_type = $this_skill->skill_parameters['type'];

        // Turn ON the weapon-wellspring feature of this skill
        // by adding this robot's ID to the player's weapon_wellspring list
        $weapon_wellspring_token = $wellspring_type.'_wellspring_robots';
        $weapon_wellspring_robots = $this_player->get_value($weapon_wellspring_token);
        if (empty($weapon_wellspring_robots)){ $weapon_wellspring_robots = array(); }
        if (!in_array($this_robot->robot_id, $weapon_wellspring_robots)){ $weapon_wellspring_robots[] = $this_robot->robot_id; }
        $this_player->set_value($weapon_wellspring_token, $weapon_wellspring_robots);

        // Print a message showing that this effect is taking place
        $pronoun_subject = $this_robot->get_pronoun('subject');
        $pronoun_possessive2 = $this_robot->get_pronoun('possessive2');
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            ucfirst($pronoun_possessive2).' team\'s '.
            rpg_type::print_span($this_robot->robot_core).'-type abilities '.
            'cost only '.rpg_type::print_span('weapons', '1 WE').' while '.
            ($pronoun_subject === 'they' ? $pronoun_subject.'\'re ' : $pronoun_subject.'\'s ').
            'active!',
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
    'rpg-robot_trigger-disabled_after' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // If this robot has no core type, the skill does nothing
        if (empty($this_robot->robot_core)){ return false; }

        // If this robot is not the target, then we can return early
        if ($this_robot !== $options->disabled_target){ return false; }

        // Turn OFF the priority-blocking feature of this skill
        // by removing this robot's ID to the player's weapon_wellspring list
        $weapon_wellspring_token = $this_robot->robot_core.'_wellspring_robots';
        $weapon_wellspring_robots = $this_player->get_value($weapon_wellspring_token);
        if (empty($weapon_wellspring_robots)){ $weapon_wellspring_robots = array(); }
        if (in_array($this_robot->robot_id, $weapon_wellspring_robots)){ $weapon_wellspring_robots = array_diff($weapon_wellspring_robots, array($this_robot->robot_id)); }
        $this_player->set_value($weapon_wellspring_token, $weapon_wellspring_robots);

        // Return true on success
        return true;

    },
    'skill_function_onload' => function($objects){

        // Extract objects into the global scope
        extract($objects);

        // Default to this skill being validated and go from there
        $this_skill->set_flag('validated', true);

        // Validate the "type" parameter has been set to a valid value
        $allowed_types = array_keys(rpg_type::get_index(false, false, false, false));
        if (!isset($this_skill->skill_parameters['type'])
            || !in_array($this_skill->skill_parameters['type'], $allowed_types)){
            error_log('skill parameter "type" was not set or was invalid ('.$this_skill->skill_token.':'.__LINE__.')');
            if (isset($this_skill->skill_parameters['type'])){
                error_log('type = '.print_r($this_skill->skill_parameters['type'], true));
            }
            $this_skill->set_flag('validated', false);
            return false;
        }

        // Everything is fine so let's return true
        return true;

    }
);
$functions['rpg-robot_check-skills_battle-start'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_update-wellsprings']($objects, true);
};
$functions['rpg-robot_check-skills_turn-start'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_update-wellsprings']($objects, true);
};
$functions['rpg-battle_switch-in_after'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_update-wellsprings']($objects, false);
};
?>
