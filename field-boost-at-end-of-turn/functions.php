<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - type (string: cutter/impact/freeze/etc.)
    - amount (float: 0.1 - 9.9)

Optional:
    - repeat (boolean: true/false) [default: true]
    - condition (format: "stat operator value" like "energy <= 50%") [default: none]
        - stat can be energy/weapons/attack/defense/speed
        - operator can be <, >, <=, >=, =, <>, !=
        - value can be -5 to 5 for attack/defense/speed, 1% - 100% for energy/weapons
        - note that stat numbers (-5 to 5) are referring to stat-mods and not the calculated values themselves

Examples:
    {"type":"nature","amount":0.1}
        would boost the nature multiplier by 10% at start of battle
    {"type":"flame","amount":0.5,"condition":"attack < 0"}
        would boost the flame multiplier by 50% at start of battle when the user's attack is lower than zero (neutral)
    {"type":"water","amount":1.0,"condition":"energy <= 50%","repeat":false}
        would boost the water multiplier by 100% at the start of battle when the user's life energy is below 50% but only once

==================================================
*/
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills_end-of-turn' => function($objects){

        // Extract objects into the global scope
        extract($objects);

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // Collect parameters that have been provided and are valid
        $boost_type = $this_skill->skill_parameters['type'];
        $boost_amount = $this_skill->skill_parameters['amount'];
        $boost_repeat = $this_skill->skill_parameters['repeat'];

        // If this is a one-time skill and has already been triggered, return immediately
        if ($boost_repeat === false
            && $this_skill->get_flag('triggered') === true){
            return false;
        }

        // If a condition was provided, make sure we quality for this boost
        if (!empty($this_skill->skill_parameters['condition'])
            && !empty($this_skill->values['skill_condition_parameters'])){
            $condition_parameters = $this_skill->values['skill_condition_parameters'];
            if (!$this_robot->check_battle_condition_is_true($condition_parameters)){
                return false;
            }
        }

        // Ensure the requested field multiplier isn't already at max value
        if (!isset($this_field->field_multipliers[$boost_type]) || $this_field->field_multipliers[$boost_type] < MMRPG_SETTINGS_MULTIPLIER_MAX){

            // Define this skill's attachment token
            $this_arrow_index = rpg_prototype::type_arrow_image('boost', !empty($boost_type) ? $boost_type : 'none');
            $this_attachment_token = 'skill_effects_field-booster';
            $this_attachment_info = array(
                'class' => 'skill',
                'attachment_token' => $this_attachment_token,
                'skill_token' => $this_skill->skill_token,
                'skill_image' => $this_arrow_index['image'],
                'skill_frame' => $this_arrow_index['frame'],
                'skill_frame_animate' => array($this_arrow_index['frame']),
                'skill_frame_offset' => array('x' => 0, 'y' => 0, 'z' => -10)
                );

            // Attach this skill attachment to this robot temporarily
            $this_robot->set_frame('taunt');
            $this_robot->set_attachment($this_attachment_token, $this_attachment_info);

            // Create or increase the elemental booster for this field
            $temp_change_percent = $boost_amount;
            $new_multiplier_value = (isset($this_field->field_multipliers[$boost_type]) ? $this_field->field_multipliers[$boost_type] : 1) + $temp_change_percent;
            if ($new_multiplier_value >= MMRPG_SETTINGS_MULTIPLIER_MAX){
                $temp_change_percent = $new_multiplier_value - MMRPG_SETTINGS_MULTIPLIER_MAX;
                $new_multiplier_value = MMRPG_SETTINGS_MULTIPLIER_MAX;
            }
            $this_field->set_multiplier($boost_type, $new_multiplier_value);

            // Create the event to show this element boost
            if ($temp_change_percent > 0){
                $print_multiplier_value = number_format($new_multiplier_value, 1);
                $this_battle->events_create($this_robot, false, $this_field->field_name.' Multipliers',
                    $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                    'The <span class="skill_stat type '.$boost_type.'">'.ucfirst($boost_type).'</span> field multiplier rose to <span class="skill_stat type none">'.$print_multiplier_value.'</span>!',
                    array('canvas_show_this_skill_overlay' => false)
                    );
            }

            // Remove this item attachment from this robot
            $this_robot->set_frame('base');
            $this_robot->unset_attachment($this_attachment_token);

            // If the "repeat" condition was set to FALSE, make sure we don't do this again
            if ($boost_repeat === false){
                $this_skill->set_flag('triggered', true);
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

        // Validate the "amount" parameter has been set to a valid value
        if (!isset($this_skill->skill_parameters['amount'])
            || !is_numeric($this_skill->skill_parameters['amount'])
            || !($this_skill->skill_parameters['amount'] > 0)){
            error_log('skill parameter "amount" was not set or was invalid ('.$this_skill->skill_token.':'.__LINE__.')');
            if (isset($this_skill->skill_parameters['amount'])){
                error_log('amount = '.print_r($this_skill->skill_parameters['amount'], true));
            }
            $this_skill->set_flag('validated', false);
            return false;
        } else {
            // Otherwise make sure this is in a proper numberic, float format
            $this_skill->skill_parameters['amount'] = (float)(number_format($this_skill->skill_parameters['amount'], 1));
        }

        // Validate the "repeat" parameter has been set to a valid value, else use default
        if (isset($this_skill->skill_parameters['repeat'])){
            if (!is_bool($this_skill->skill_parameters['repeat'])){
                error_log('skill parameter "repeat" was not a boolean value ('.$this_skill->skill_token.':'.__LINE__.')');
                error_log('repeat = '.print_r($this_skill->skill_parameters['repeat'], true));
                $this_skill->set_flag('validated', false);
                return false;
            }
        } else {
            // Otherwise make sure this is in a proper boolean format
            $this_skill->skill_parameters['repeat'] = true;
        }

        // Validate the optional "condition" parameter has been set to a valid value if it's there
        if (isset($this_skill->skill_parameters['condition'])){
            $condition = $this_skill->skill_parameters['condition'];
            $condition_params = rpg_game::check_battle_condition_is_valid($condition, $this_skill);
            if (!empty($condition_params)){
                $this_skill->set_value('skill_condition_parameters', $condition_params);
            } else {
                $this_skill->set_flag('validated', false);
                return false;
            }
        }

        // Everything is fine so let's return true
        return true;

    }
);
?>
