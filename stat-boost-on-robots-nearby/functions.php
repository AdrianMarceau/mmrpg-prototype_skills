<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - robots (array: token1, token2, ...)
    - stat (string: attack/defense/speed)
    - amount (integer: 1 - 5)

Examples:
    {"robots":"mega-man,proto-man","stat":"attack","amount":1} // note the STRING format list of robot tokens
        would boost attack by one stage when either mega man or proto man are on the field

==================================================
*/
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'robot_function_on-robots-nearby' => function($objects){

        // Extract objects into the global scope
        extract($objects);

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // Collect parameters that have been provided and are valid
        $boost_robots = $this_skill->skill_parameters['robots'];
        $boost_robots = !empty($boost_robots) ? explode(',', $boost_robots) : array();
        $boost_stat = $this_skill->skill_parameters['stat'];
        $boost_stats = $boost_stat === 'all' ? array('attack', 'defense', 'speed') : array($boost_stat);
        $boost_amount = $this_skill->skill_parameters['amount'];

        // Check to see if there are any robots able to trigger this skill
        $robots_of_interest = array();
        $noticed_robot_strings = $this_robot->get_value('noticed_robot_strings');
        if (empty($noticed_robot_strings)){ $noticed_robot_strings = array(); }
        $find_robots_of_interest = function($player)
            use(&$robots_of_interest, &$noticed_robot_strings, $this_battle, $this_robot, $boost_robots){
            $robots = $player->get_robots_active();
            foreach ($robots AS $key => $robot){
                if ($robot === $this_robot){ continue; }
                if (in_array($robot->robot_string, $noticed_robot_strings)){ continue; }
                if (in_array($robot->robot_token, $boost_robots)){
                    $robots_of_interest[] = array('player' => $player, 'robot' => $robot);
                    $noticed_robot_strings[] = $robot->robot_string;
                }
            }
        };
        $find_robots_of_interest($target_player);
        $find_robots_of_interest($this_player);
        $this_robot->set_value('noticed_robot_strings', $noticed_robot_strings);

        // If there aren't any robots of interest, that means we have to return
        if (empty($robots_of_interest)){ return false; }
        $boost_amount *= count($robots_of_interest);

        // Otherwise, print a message showing that this effect is taking place
        $certain_text = count($robots_of_interest) === 1 ? 'a certain robot' : 'certain robots';
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().' took notice of '.$certain_text.' on the field...',
            array(
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key
                )
            );
        $this_robot->reset_frame();

        // Loop through and show the camera looking at them one-by-one
        foreach ($robots_of_interest AS $key => $player_and_robot){
            $player_and_robot['robot']->set_frame('defend');
            $this_battle->queue_sound_effect('scan-start');
            $this_battle->events_create($this_robot, false, '', '',
                array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $player_and_robot['robot']->player->player_side,
                    'event_flag_camera_focus' => $player_and_robot['robot']->robot_position,
                    'event_flag_camera_depth' => $player_and_robot['robot']->robot_key
                    )
                );
            $player_and_robot['robot']->reset_frame();
        }

        // Ensure this robot's stat isn't already at max value
        foreach ($boost_stats AS $boost_stat){
            if ($this_robot->counters[$boost_stat.'_mods'] < MMRPG_SETTINGS_STATS_MOD_MAX){
                // If this robot has a stat-based skill, display the trigger text separately
                $trigger_text = $this_robot->print_name().' is getting pumped up!';
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

        // Validate the "robots" parameter has been set to a valid value
        if (!isset($this_skill->skill_parameters['robots'])
            || !is_string($this_skill->skill_parameters['robots'])
            || empty($this_skill->skill_parameters['robots'])){
            error_log('skill parameter "robots" was not set or was invalid ('.$this_skill->skill_token.':'.__LINE__.')');
            $this_skill->set_flag('validated', false);
            return false;
        }

        // Validate the "stat" parameter has been set to a valid value
        $allowed_stats = array('attack', 'defense', 'speed', 'all');
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

        // Everything is fine so let's return true
        return true;

    }
);
$functions['rpg-robot_check-skills_battle-start'] = function($objects) use ($functions){
    return $functions['robot_function_on-robots-nearby']($objects, true);
};
$functions['rpg-robot_check-skills_turn-start'] = function($objects) use ($functions){
    return $functions['robot_function_on-robots-nearby']($objects, true);
};
?>
