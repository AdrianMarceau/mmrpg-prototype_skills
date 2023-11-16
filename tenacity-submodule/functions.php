<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - type (string: cutter/impact/freeze/etc.)

Examples:
    {"type":"nature"}
        would allow nature type abilities to be used without resistance

==================================================
*/
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
        $this_battle->queue_sound_effect('flame-sound');
        $this_battle->queue_sound_effect(array('name' => 'flame-sound', 'delay' => 200));
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            'Damage from '.$this_robot->get_pronoun('possessive2').' '.rpg_type::print_span($boost_type).'-type abilities can\'t be resisted anymore!',
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

        // If this robot is the aggressor, the skill doesn't activate
        if ($options->damage_initiator !== $this_robot){ return false; }
        if (empty($options->damage_target)){ return false; }
        $target_robot = $options->damage_target;

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // Collect parameters that have been provided and are valid
        $boost_type = $this_skill->skill_parameters['type'];

        // If this ability matches the requested type, make sure it ignores negative multipliers
        if ((empty($this_ability->ability_type) && $boost_type === 'none')
            ||(!empty($this_ability->ability_type) && $this_ability->ability_type === $boost_type)
            || (!empty($this_ability->ability_type2) && $this_ability->ability_type2 === $boost_type)){

            // If the target has a resistance, affinity, or immunity to this type, ignore them
            if ($target_robot->has_resistance($boost_type)){
                $options->trigger_options['apply_type_modifiers'] = false;
            } elseif ($target_robot->has_affinity($boost_type)){
                $options->trigger_options['apply_type_modifiers'] = false;
            } elseif ($target_robot->has_immunity($boost_type)){
                $options->trigger_options['apply_type_modifiers'] = false;
            }

            // If the matching field multiplier is less than one, we should ignore that too
            if (!empty($this_field->field_multipliers[$boost_type])
                && $this_field->field_multipliers[$boost_type] < 1){
                $options->trigger_options['apply_field_modifiers'] = false;
            }

            // If this robot has any attachments, temporarily neutralize any would-be negative their effects
            if (!empty($this_robot->robot_attachments)){
                foreach ($this_robot->robot_attachments AS $attachment_token => $attachment_info){
                    if (strstr($attachment_token, '_core-shield_')){ continue; }
                    if (!empty($attachment_info['attachment_damage_output_breaker'])
                        || !empty($attachment_info['attachment_damage_output_breaker_'.$boost_type])){
                        $attachment_info['attachment_supressed'] = true;
                        $attachment_info['attachment_supressed_by_'.$this_skill->skill_token] = true;
                        $this_robot->set_attachment($attachment_token, $attachment_info);
                    }
                }
            }

            // If the target has any attachments, temporarily neutralize any would-be negative their effects
            if (!empty($target_robot->robot_attachments)){
                foreach ($target_robot->robot_attachments AS $attachment_token => $attachment_info){
                    if (strstr($attachment_token, '_core-shield_')){ continue; }
                    if (!empty($attachment_info['attachment_damage_breaker'])
                        || !empty($attachment_info['attachment_damage_input_breaker'])
                        || !empty($attachment_info['attachment_damage_breaker_'.$boost_type])
                        || !empty($attachment_info['attachment_damage_input_breaker_'.$boost_type])){
                        $attachment_info['attachment_supressed'] = true;
                        $attachment_info['attachment_supressed_by_'.$this_skill->skill_token] = true;
                        $target_robot->set_attachment($attachment_token, $attachment_info);
                    }

                }
            }

        }

        // Return true on success
        return true;

    },
    'rpg-ability_trigger-damage_after' => function($objects){

        // Extract objects into the global scope
        extract($objects);

        // If this robot is the aggressor, the skill doesn't activate
        if ($options->damage_initiator !== $this_robot){ return false; }
        if (empty($options->damage_target)){ return false; }
        $target_robot = $options->damage_target;

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // Collect parameters that have been provided and are valid
        $boost_type = $this_skill->skill_parameters['type'];

        // Loop through target attachments and remove any surpressions by this skill
        if (!empty($target_robot->robot_attachments)){
            foreach ($target_robot->robot_attachments AS $attachment_token => $attachment_info){
                if (!empty($attachment_info['attachment_supressed'])
                    || !empty($attachment_info['attachment_supressed_by_'.$this_skill->skill_token])){
                    unset($attachment_info['attachment_supressed']);
                    unset($attachment_info['attachment_supressed_by_'.$this_skill->skill_token]);
                    $target_robot->set_attachment($attachment_token, $attachment_info);
                }

            }
        }

        // Loop through this robot's attachments and remove any surpressions by this skill
        if (!empty($this_robot->robot_attachments)){
            foreach ($this_robot->robot_attachments AS $attachment_token => $attachment_info){
                if (!empty($attachment_info['attachment_supressed'])
                    || !empty($attachment_info['attachment_supressed_by_'.$this_skill->skill_token])){
                    unset($attachment_info['attachment_supressed']);
                    unset($attachment_info['attachment_supressed_by_'.$this_skill->skill_token]);
                    $this_robot->set_attachment($attachment_token, $attachment_info);
                }
            }
        }

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
        $allowed_types = array_merge($allowed_types, array('damage', 'recovery', 'experience'));
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
?>
