<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - stat (string: attack/defense/speed)

Examples:
    {"stat":"speed"}
        would lower attack by one stage to recovery lost health when needed and able

==================================================
*/
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

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // If this robot's health is already full, the skill doesn't activate
        if ($this_robot->robot_energy === $this_robot->robot_base_energy){ return false; }

        // Collect parameters that have been provided and are valid
        $convert_stat = $this_skill->skill_parameters['stat'];

        // Collect a reference to the target robot as we don't have one in this context
        if (empty($this_player->other_player)){ return false; }
        $target_player = $this_player->other_player;
        $target_robot = $target_player->get_active_robot();

        // If this robot's stat is already at minimum (or maximum w/ reverse), the skill doesn't activate
        $invert_logic = false;
        if ($this_robot->has_item() && $this_robot->robot_item === 'reverse-module'){ $invert_logic = true; }
        if (!$invert_logic && $this_robot->counters[$convert_stat.'_mods'] <= MMRPG_SETTINGS_STATS_MOD_MIN){ return false; }
        else if ($invert_logic && $this_robot->counters[$convert_stat.'_mods'] >= MMRPG_SETTINGS_STATS_MOD_MAX){ return false; }

        // Print a message showing that this effect is taking place
        $this_robot->set_frame('defend');
        $this_battle->queue_sound_effect('downward-impact');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in! <br />'.
            ucfirst($this_robot->get_pronoun('subject')).' decided to recover some health by resting!',
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
            'frame' => 'base2',
            'success' => array(0, -2, 0, -10, $this_robot->print_name().'\'s energy was restored!'),
            'failure' => array(9, -2, 0, -10, '...but '.$this_robot->print_name().'\'s energy was not affected!')
            ));
        $energy_recovery_percent = $this_robot->robot_position === 'bench' ? 20 : 10;
        $energy_recovery_amount = ceil($this_robot->robot_base_energy * ($energy_recovery_percent / 100));
        $trigger_options = array('apply_modifiers' => true, 'apply_position_modifiers' => false, 'apply_stat_modifiers' => false, 'canvas_show_this_skill' => false);
        $this_robot->trigger_recovery($this_robot, $this_skill, $energy_recovery_amount, true, $trigger_options);

        // Call the global stat boost function with customized options
        if ($convert_stat === 'attack'){ $trigger_text = 'The resting '.(!$invert_logic ? 'powered-down' : 'inexplicably powered-up').' '.$this_robot->print_name().'\'s weapons!'; }
        elseif ($convert_stat === 'defense'){ $trigger_text = 'The resting '.(!$invert_logic ? 'weakened' : 'inexplicably bolstered').' '.$this_robot->print_name().'\'s shields!'; }
        elseif ($convert_stat === 'speed'){ $trigger_text = 'The resting '.(!$invert_logic ? 'slowed' : 'inexplicably hastened').' '.$this_robot->print_name().'\'s movement!'; }
        rpg_ability::ability_function_stat_break($this_robot, $convert_stat, 1, $this_skill, array(
            'success_frame' => 9,
            'failure_frame' => 9,
            'extra_text' => $trigger_text,
            'skip_canvas_header' => true
            ));


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

        // Everything is fine so let's return true
        return true;

    }
);
?>
