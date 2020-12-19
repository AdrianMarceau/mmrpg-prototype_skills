<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - stat (string: attack/defense/speed)
    - amount (integer: 1 - 5)

Optional:
    - repeat (boolean: true/false) [default: true]
    - condition (format: "stat operator value" like "energy <= 50%") [default: none]
        - stat can be energy/weapons/attack/defense/speed
        - operator can be <, >, <=, >=, =, <>, !=
        - value can be -5 to 5 for attack/defense/speed, 1% - 100% for energy/weapons
        - note that stat numbers (-5 to 5) are referring to stat-mods and not the calculated values themselves

Examples:
    {"stat":"attack","amount":1}
        would boost attack by one stage every single turn
    {"stat":"defense","amount":1,"condition":"attack < 0"}
        would boost defense by one stage each turn the user's attack is lower than zero (neutral)
    {"stat":"speed","amount":5,"condition":"energy <= 50%","repeat":false}
        would boost speed by five stages when the user's life energy dips below 50% but only once

==================================================
*/
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills' => function($objects){

        // Extract objects into the global scope
        extract($objects);

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // Collect parameters that have been provided and are valid
        $boost_stat = $this_skill->skill_parameters['stat'];
        $boost_amount = $this_skill->skill_parameters['amount'];
        $boost_repeat = $this_skill->skill_parameters['repeat'];

        // If this is a one-time skill and has already been triggered, return immediately
        if ($boost_repeat === false
            && $this_skill->get_flag('triggered') === true){
            return false;
        }

        // If a condition was provided, make sure we quality for this boost
        if (!empty($this_skill->skill_parameters['condition'])){
            $boost_condition = $this_skill->values['skill_parameter_condition'];
            list($c_stat, $c_operator, $c_value) = array_values($boost_condition);
            $is_percent_based = in_array($c_stat, array('energy', 'weapons')) ? true : false;
            $boost_stat_value_required = intval($c_value);
            $boost_stat_value_current = $this_robot->get_info('robot_'.$c_stat);
            if ($is_percent_based){
                $base_stat_value = $this_robot->get_info('robot_base_'.$c_stat);
                $boost_stat_value_current = ($boost_stat_value_current / $base_stat_value) * 100;
            }
            if (!version_compare($boost_stat_value_current, $boost_stat_value_required, $c_operator)){
                return false;
            }
        }

        // Ensure this robot's stat isn't already at max value
        if ($this_robot->counters[$boost_stat.'_mods'] < MMRPG_SETTINGS_STATS_MOD_MAX){
            // If this robot has a stat-based skill, display the trigger text separately
            $trigger_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!';
            if (!empty($this_robot->robot_item) && preg_match('/^(guard|reverse|xtreme)-module$/', $this_robot->robot_item)){
                $this_skill->target_options_update(array('frame' => 'summon', 'success' => array(9, 0, 0, -10, $trigger_text)));
                $this_robot->trigger_target($this_robot, $this_skill, array('prevent_default_text' => true));
                $trigger_text = '';
            }
            // Call the global stat boost function with customized options
            rpg_ability::ability_function_stat_boost($this_robot, $boost_stat, $boost_amount, $this_skill, array(
                'success_frame' => 9,
                'failure_frame' => 9,
                'extra_text' => $trigger_text
                ));
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

        // Validate the "stat" parameter has been set to a valid value
        $allowed_stats = array('attack', 'defense', 'speed');
        if (!isset($this_skill->skill_parameters['stat'])
            || !in_array($this_skill->skill_parameters['stat'], $allowed_stats)){
            error_log('skill parameter "stat" was not set or was invalid ('.$this_skill->skill_token.':'.__LINE__.')');
            if (isset($this_skill->skill_parameters['stat'])){
                error_log('stat = '.print_r($this_skill->skill_parameters['stat'], true));
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
            // Otherwise make sure this is in a proper numberic, integer format
            $this_skill->skill_parameters['amount'] = intval($this_skill->skill_parameters['amount']);
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
            // First check to ensure it matches the established condition format
            // examples: "attack < 3" or "defense >= 2" or "energy < 50%"
            $condition = $this_skill->skill_parameters['condition'];
            if (!preg_match('/^([a-z]+)\s?([\<\>\=\!]+)\s?(\-?[0-9]+\%?)$/i', $condition, $matches)){
                error_log('skill parameter "condition" was set but was invalid ('.$this_skill->skill_token.':'.__LINE__.')');
                error_log('$condition = '.print_r($condition, true));
                $this_skill->set_flag('validated', false);
                return false;
            }
            // Now check to make sure the individual parts of the condition are allowed
            $allowed_condition_stats = array('energy', 'weapons', 'attack', 'defense', 'speed');
            $allowed_condition_operators = array('=', '<=', '>=', '<', '>', '<>');
            list($x, $c_stat, $c_operator, $c_value) = $matches;
            if (!in_array($c_stat, $allowed_condition_stats)){
                error_log('skill parameter "condition" stat was set but was invalid ('.$this_skill->skill_token.':'.__LINE__.')');
                error_log('$c_stat = '.print_r($c_stat, true));
                $this_skill->set_flag('validated', false);
                return false;
            } elseif (!in_array($c_operator, $allowed_condition_operators)){
                error_log('skill parameter "condition" operator was set but was invalid ('.$this_skill->skill_token.':'.__LINE__.')');
                error_log('$c_operator = '.print_r($c_operator, true));
                $this_skill->set_flag('validated', false);
                return false;
            } else {
                // Validate the value parameter differently for energy/weapons vs attack/defense/speed stats
                if ($c_stat === 'energy' || $c_stat === 'weapons'){
                    if (!strstr($c_value, '%')){
                        error_log('skill parameter "condition" value must be percent for energy/weapons stat ('.$this_skill->skill_token.':'.__LINE__.')');
                        error_log('$c_value = '.print_r($c_value, true));
                        $this_skill->set_flag('validated', false);
                        return false;
                    } elseif (intval($c_value) <= 0 || intval($c_value) > 100){
                        error_log('skill parameter "condition" value must be > 0% and <= 100% for energy/weapons stat ('.$this_skill->skill_token.':'.__LINE__.')');
                        error_log('$c_value = '.print_r($c_value, true));
                        $this_skill->set_flag('validated', false);
                        return false;
                    }
                } else {
                    if (intval($c_value) < MMRPG_SETTINGS_STATS_MOD_MIN || intval($c_value) > MMRPG_SETTINGS_STATS_MOD_MAX){
                        error_log('skill parameter "condition" value must be > '.MMRPG_SETTINGS_STATS_MOD_MIN.' and < '.MMRPG_SETTINGS_STATS_MOD_MAX.' for attack/defense/speed stat ('.$this_skill->skill_token.':'.__LINE__.')');
                        error_log('$c_value = '.print_r($c_value, true));
                        $this_skill->set_flag('validated', false);
                        return false;
                    }
                }
            }
            // If we made it this far it must be valid, so let's save our broken-up parameter better
            $this_skill->set_value('skill_parameter_condition', array(
                'stat' => $c_stat,
                'operator' => $c_operator,
                'value' => $c_value,
                ));
        }

        // Everything is fine so let's return true
        return true;

    }
);
?>
