<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - all (boolean: false)
    - screws (boolean: true/false)
    - pellets (boolean: true/false)
    - capsules (boolean: true/false)
    - tanks (boolean: true/false)
    - shards (boolean: true/false)
    - cores (boolean: true/false)

Examples:
    {"screws":true,"shards":true}
        would randomly find shards and/or screws at end-of-turn

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

        // If this robot is not owned by a humnan player it doesn't work
        if ($this_player->player_side !== 'left'){ return false; }

        // If we don't have access to the inventory this skill doesn't work
        $allow_inventory_access = $this_battle->allow_inventory_access();
        if (!$allow_inventory_access){ return false; }

        //error_log('end-of-turn check for this skill');

        // Use the robot's current level to determine which tier items they'll find
        $max_item_tier = 1;
        if ($this_robot->robot_level >= 33){ $max_item_tier += 1; }
        if ($this_robot->robot_level >= 66){ $max_item_tier += 1; }
        if ($this_robot->robot_level >= 99){ $max_item_tier += 1; }
        //error_log('$max_item_tier = '.print_r($max_item_tier, true));

        // Define a boolean to check if skill will trigger
        $trigger_skill = false;

        // If the robot isn't lucky this turn, the skill doesn't work
        $battle_turn = $this_battle->counters['battle_turn'];
        $lucky_number = $this_skill->values['skill_lucky_number'];
        $lucky_int = intval(substr(($battle_turn + $lucky_number), -1, 1));
        //error_log('$battle_turn = '.print_r($battle_turn, true));
        //error_log('$lucky_number = '.print_r($lucky_number, true));
        //error_log('$lucky_int ('.print_r(($battle_turn + $lucky_number), true).') = '.print_r($lucky_int, true));
        $check_stats = array();
        if ($this_robot->robot_level >= 0){ $check_stats[] = 'energy'; }
        if ($this_robot->robot_level >= 20){ $check_stats[] = 'weapons'; }
        if ($this_robot->robot_level >= 40){ $check_stats[] = 'attack'; }
        if ($this_robot->robot_level >= 60){ $check_stats[] = 'defense'; }
        if ($this_robot->robot_level >= 80){ $check_stats[] = 'speed'; }
        if ($this_robot->robot_level >= 100){ $check_stats[] = 'level'; }
        //$check_stats = array('energy', 'attack', 'defense', 'speed');
        //error_log('$check_stats = '.print_r($check_stats, true));
        foreach ($check_stats AS $stat){
            $prop_name = 'robot_'.$stat;
            $stat_int = intval(substr($this_robot->$prop_name, -1, 1));
            //error_log('$'.$stat.'_int('.$stat_int.') vs $lucky_int('.$lucky_int.')');
            if ($stat_int === $lucky_int){ $trigger_skill = true; break; }
        }

        // If we're not allowed to trigger the skill now, return false
        //error_log('$trigger_skill = '.($trigger_skill ? 'true' : 'false'));
        if (!$trigger_skill){ return false; }

        // Define a variable to hold the item drop table with options/weights
        $item_drop_table = array();

        // Collect the various drop flags we're allow to use
        $allowed_item_kinds = $this_skill->values['skill_allowed_item_kinds'];
        //error_log('$allowed_item_kinds = '.print_r($allowed_item_kinds, true));

        // Generate an array of elemental weights to use with shard/core drops
        $allowed_types = rpg_type::get_index(false, false, false, true);
        $allowed_type_weights = array();
        if (!empty($this_field->field_multipliers)){
            foreach ($this_field->field_multipliers AS $temp_type => $temp_multiplier){
                if ($temp_multiplier <= 1){ continue; }
                elseif (!isset($allowed_types[$temp_type])){ continue; }
                $allowed_type_weights[$temp_type] = $temp_multiplier;
            }
        }
        //error_log('$allowed_type_weights = '.print_r($allowed_type_weights, true));

        // If SCREWS are allowed to be found, add them to the drop table
        if (in_array('all', $allowed_item_kinds)
            || in_array('screws', $allowed_item_kinds)){
            $item_drop_table['small-screw'] = 20;
            if ($max_item_tier >= 2){
                $item_drop_table['large-screw'] = 5;
            }
        }

        // If PELLETS are allowed to be found, add them to the drop table
        if (in_array('all', $allowed_item_kinds)
            || in_array('pellets', $allowed_item_kinds)){
            $item_drop_table['energy-pellet'] = 4;
            $item_drop_table['weapon-pellet'] = 4;
            if ($max_item_tier >= 2){
                $item_drop_table['attack-pellet'] = 3;
                $item_drop_table['defense-pellet'] = 3;
                $item_drop_table['speed-pellet'] = 3;
                if ($max_item_tier >= 3){
                    $item_drop_table['super-pellet'] = 1;
                }
            }
        }

        // If CAPSULES are allowed to be found, add them to the drop table
        if (in_array('all', $allowed_item_kinds)
            || in_array('capsules', $allowed_item_kinds)){
            $item_drop_table['energy-capsule'] = 3;
            $item_drop_table['weapon-capsule'] = 3;
            if ($max_item_tier >= 3){
                $item_drop_table['attack-capsule'] = 2;
                $item_drop_table['defense-capsule'] = 2;
                $item_drop_table['speed-capsule'] = 2;
                if ($max_item_tier >= 4){
                    $item_drop_table['super-capsule'] = 1;
                }
            }
        }

        // If TANKS are allowed to be found, add them to the drop table
        if (in_array('all', $allowed_item_kinds)
            || in_array('tanks', $allowed_item_kinds)){
            if ($max_item_tier >= 2){
                $item_drop_table['energy-tank'] = 3;
                $item_drop_table['weapon-tank'] = 3;
            }
        }

        // If SHARDS are allowed to be found, add them to the drop table
        if (in_array('all', $allowed_item_kinds)
            || in_array('shards', $allowed_item_kinds)){
            if (!empty($allowed_type_weights) && $max_item_tier >= 2){
                foreach ($allowed_type_weights AS $temp_type => $temp_weight){
                    $item_drop_table[$temp_type.'-shard'] = round($temp_weight * 10) * ($max_item_tier - 1);
                }
            }
        }

        // If CORES are allowed to be found, add them to the drop table
        if (in_array('all', $allowed_item_kinds)
            || in_array('cores', $allowed_item_kinds)){
            if (!empty($allowed_type_weights) && $max_item_tier >= 3){
                foreach ($allowed_type_weights AS $temp_type => $temp_weight){
                    $item_drop_table[$temp_type.'-core'] = round($temp_weight * 2) * ($max_item_tier - 1);
                }
            }
        }

        //error_log('$item_drop_table(A) = '.print_r($item_drop_table, true));

        // Loop through the drop table and remove any items we already have the max limit of
        if (!empty($item_drop_table)){
            foreach ($item_drop_table AS $item_token => $item_weight){
                $item_count = mmrpg_prototype_get_battle_item_count($item_token);
                if ($item_count >= MMRPG_SETTINGS_ITEMS_MAXQUANTITY){
                    unset($item_drop_table[$item_token]);
                    continue;
                }
            }
        }

        //error_log('$item_drop_table(B) = '.print_r($item_drop_table, true));

        // If a drop table was generated, check to see which item might randomly be picked up
        if (!empty($item_drop_table)){

            $options = array_keys($item_drop_table);
            $weights = array_values($item_drop_table);
            $item_token = $this_battle->weighted_chance($options, $weights);

            //error_log('$item_token = '.print_r($item_token, true));

            // If an item was actually found, we can display the skill message now and equip
            if (!empty($item_token)){

                // If this robot has a stat-based skill, display the trigger text separately
                $trigger_text = $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br /> ';
                $trigger_text .= ucfirst($this_robot->get_pronoun('subject')).' started digging the ground below...';
                $this_skill->target_options_update(array('frame' => 'defend', 'success' => array(9, 0, 0, -10, $trigger_text)));
                $this_robot->trigger_target($this_robot, $this_skill, array('prevent_default_text' => true));

                // If this robot has a stat-based skill, display the trigger text separately
                $item_info = rpg_item::get_index_info($item_token);
                $this_item = rpg_game::get_item($this_battle, $this_player, $this_robot, $item_info);
                $old_item_count = mmrpg_prototype_get_battle_item_count($item_token);
                $new_item_count = mmrpg_prototype_inc_battle_item_count($item_token, 1);
                $a_or_an = preg_match('/^(a|e|i|o|u|y)/i', $item_token) ? 'an' : 'a';
                $trigger_text = rpg_battle::random_positive_word().' '.$this_robot->print_name().' found '.$a_or_an.' '.$this_item->print_name().' underground!<br /> ';
                $trigger_text .= 'The item was added to '.$this_player->print_name().'\'s inventory! ';
                $trigger_text .= '<span class="item_stat item_type item_type_none">'.$old_item_count.' <sup style="bottom: 2px;">&raquo;</sup> '.$new_item_count.'</span>';
                $item_x_frame = $this_robot->robot_position === 'active' ? 90 : 45;
                $this_player->set_frame('victory');
                $this_item->target_options_update(array('frame' => 'summon', 'success' => array(0, $item_x_frame, 10, 20, $trigger_text)));
                $this_robot->trigger_target($this_robot, $this_item, array('prevent_default_text' => true));
                $this_player->reset_frame();

                // If the found item was an elemental shard, we need special code to add to inventory
                if (strstr($item_token, '-shard')){ rpg_item::add_new_shard_to_inventory($item_token, $objects, array('robot_frame' => 'taunt')); }

            }

        }

        // Return true on success
        return true;

    },
    'skill_function_onload' => function($objects){

        // Extract objects into the global scope
        extract($objects);

        // If this skill has already been validated, don't do the work again
        if (isset($this_skill->flags['validated'])){ return; }

        // Default to this skill being validated and go from there
        $this_skill->set_flag('validated', true);

        // First we should also define a "lucky number" for this battle we'll later
        $this_lucky_number = mt_rand(0, 9);
        $this_skill->set_value('skill_lucky_number', $this_lucky_number);
        //error_log('$this_lucky_number = '.print_r($this_lucky_number, true));

        // Now define an array of allowed items types (will be filtered later)
        $allowed_item_kinds = array('screws', 'pellets', 'capsules', 'tanks', 'shards', 'cores');

        // We need to make sure at least one flag has been set, otherwise not validated
        $true_flags = array();
        $allowed_flags = array_merge(array('all'), $allowed_item_kinds);
        foreach ($allowed_flags AS $flag_name){
            if (isset($this_skill->skill_parameters[$flag_name])
                && !empty($this_skill->skill_parameters[$flag_name])){
                $true_flags[] = $flag_name;
            }
        }

        // If none of the allowed flags were set, we have a problem
        if (empty($true_flags)){
            error_log('skill parameters were not set or were invalid ('.$this_skill->skill_token.':'.__LINE__.')');
            error_log('allowed = '.print_r($allowed_flags, true));
            $this_skill->set_flag('validated', false);
            return false;
        }

        // If the "all" parameter was set to true, allow all items, else filter given parameters
        $allow_all_kinds = isset($this_skill->skill_parameters['all']) && !empty($this_skill->skill_parameters['all']) ? true : false;
        if (!$allow_all_kinds){
            foreach ($allowed_item_kinds AS $key => $kind){
                if (!isset($this_skill->skill_parameters[$kind])
                    || empty($this_skill->skill_parameters[$kind])){
                    unset($allowed_item_kinds[$key]);
                    continue;
                }
            }
            $allowed_item_kinds = array_values($allowed_item_kinds);
        }

        // Otherwise, define a variable with all the allowed drop types
        $this_skill->set_value('skill_allowed_item_kinds', $allowed_item_kinds);
        //error_log('$allowed_item_kinds = '.print_r($allowed_item_kinds, true));

        // Everything is fine so let's return true
        return true;

    }
);
?>
