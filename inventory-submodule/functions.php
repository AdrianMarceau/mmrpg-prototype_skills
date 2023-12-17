<?
/*
==================================================

THIS SKILL HAS PARAMETERS!

Required:
    - all (boolean: true/false)
    - attack (boolean: false/true)
    - defense (boolean: false/true)
    - speed (boolean: false/true)
    - energy (boolean: false/true)
    - weapons (boolean: false/true)
    - pellets (boolean: false/true)
    - capsules (boolean: false/true)
    - tanks (boolean: false/true)

Examples:
    {"attack":true,"defense":true,"speed":true,"pellets":true,"capsules":true}
        would find attack/defense/speed pellets and/or capsules at end of turn when needed
    {"energy":true,"weapons":true,"pellets":true,"capsules":true}
        would find energy/weapon pellets and/or capsules at end of turn when needed
    {"energy":true,"weapons":true,"tanks":true}
        would find energy/weapon tanks at end of turn when needed

==================================================
*/
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_check-skills_end-of-turn' => function($objects){
        //error_log('rpg-robot_check-skills_end-of-turn() for '.$objects['this_robot']->robot_token);

        // Extract objects into the global scope
        extract($objects);

        // If this skill was not validated we cannot proceed
        if (empty($this_skill->flags['validated'])){ return false; }

        // Define a boolean to check if skill will trigger
        $trigger_skill = true;

        // If we're not allowed to trigger the skill now, return false
        //error_log('$trigger_skill = '.($trigger_skill ? 'true' : 'false'));
        if (!$trigger_skill){ return false; }
        //error_log('we can trigger the skill now');

        // Define the base arrays of allowed items types (unfiltered)
        $base_allowed_item_stats = array('energy', 'weapons', 'attack', 'defense', 'speed');
        $base_allowed_item_kinds = array('pellets', 'capsules', 'tanks');

        // Collect the arrays of allowed items types (filtered by the validator)
        $allowed_item_stats = $this_skill->get_value('skill_allowed_item_stats');
        $allowed_item_kinds = $this_skill->get_value('skill_allowed_item_kinds');

        // Define an array to hold the inventory gifts we may or may not be giving out
        $inventory_gifts_array = array();

        // Loop through all of this player's robots to see which types of items we could theoretically give them
        $this_robots_active = $this_player->get_robots_active();
        foreach ($this_robots_active AS $key => $robot){
            //error_log('checking robot '.$robot->robot_token);

            // We're not allowed to use this skill on ourselves
            if ($robot->robot_id == $this_robot->robot_id){ continue; }

            // Define a mini-array to hold the items that would be appropriate given context
            $items = array();
            $priority = 0;

            // Review the robot's energy and weapons and determine which health or ammo items we could give them
            if ($robot->robot_energy <= ceil($robot->robot_base_energy / 3)){ $items[] = 'energy-tank'; $priority += 3; }
            if ($robot->robot_weapons <= ceil($robot->robot_base_weapons / 3)){ $items[] = 'weapon-tank'; $priority += 3; }
            if ($robot->robot_energy <= ceil($robot->robot_base_energy * 0.6)){ $items[] = 'energy-capsule'; $priority += 2; }
            if ($robot->robot_weapons <= ceil($robot->robot_base_weapons * 0.6)){ $items[] = 'weapon-capsule'; $priority += 2; }
            if ($robot->robot_energy <= ceil($robot->robot_base_energy * 0.85)){ $items[] = 'energy-pellet'; $priority += 1; }
            if ($robot->robot_weapons <= ceil($robot->robot_base_weapons * 0.85)){ $items[] = 'weapon-pellet'; $priority += 1; }

            // Review the robot's attack, defense, and speed to see which items could we give them
            if (!empty($robot->counters['attack_mods']) && $robot->counters['attack_mods'] <= -2){ $items[] = 'attack-capsule'; $priority += 2; }
            if (!empty($robot->counters['attack_mods']) && $robot->counters['attack_mods'] <= -1){ $items[] = 'attack-pellet'; $priority += 1; }
            if (!empty($robot->counters['defense_mods']) && $robot->counters['defense_mods'] <= -2){ $items[] = 'defense-capsule'; $priority += 2; }
            if (!empty($robot->counters['defense_mods']) && $robot->counters['defense_mods'] <= -1){ $items[] = 'defense-pellet'; $priority += 1; }
            if (!empty($robot->counters['speed_mods']) && $robot->counters['speed_mods'] <= -2){ $items[] = 'speed-capsule'; $priority += 2; }
            if (!empty($robot->counters['speed_mods']) && $robot->counters['speed_mods'] <= -1){ $items[] = 'speed-pellet'; $priority += 1; }

            // If items were found, add them to the inventory gifts array
            //error_log('$items '.print_r($items, true));
            //error_log('$priority = '.$priority);
            if (!empty($items)){
                //error_log('$items '.print_r($items, true));
                $inventory_gifts_array[] = array(
                    'robot' => $robot,
                    'items' => $items,
                    'priority' => $priority
                    );
            }

        }
        //error_log('$allowed_item_stats = '.print_r($allowed_item_stats, true));
        //error_log('$allowed_item_kinds = '.print_r($allowed_item_kinds, true));
        //error_log('$inventory_gifts_array = '.print_r(array_map(function($a){ $a['robot'] = $a['robot']->robot_string; return $a; }, $inventory_gifts_array), true));

        // Re-sort the gifts array by priority with highest at the top
        usort($inventory_gifts_array, function($a, $b){
            if ($a['priority'] == $b['priority']){ return 0; }
            return ($a['priority'] > $b['priority']) ? -1 : 1;
            });

        // Loop through the list of inventory gifts and try to find one we can give out
        $inventory_gift = false;
        foreach ($inventory_gifts_array AS $key => $gift){
            //error_log('checking gift key:'.$key.' | robot:'.$gift['robot']->robot_token.' | priority:'.$gift['priority']);
            if (empty($gift['items'])){ continue; }
            //error_log('- gift items = '.print_r($gift['items'], true));
            foreach ($gift['items'] AS $item){
                list($item_stat, $item_kind) = explode('-', $item);
                if ($item_stat === 'weapon'){ $item_stat .= 's'; }
                $item_kind .= 's';
                //error_log('-- checking item '.$item.' (stat:'.$item_stat.' / kind:'.$item_kind.')');
                if (in_array($item_stat, $allowed_item_stats)
                    && in_array($item_kind, $allowed_item_kinds)){
                    //error_log('--- found allowed item '.$item.' (!)');
                    $gift['item'] = $item;
                    $inventory_gift = $gift;
                    break;
                }
            }
            if ($inventory_gift){ break; }
        }

        // If an allowed inventory gift was found, we can give it to the target
        if (!empty($inventory_gift) && !empty($inventory_gift['item'])){

            //error_log('allowed inventory gift found!');
            //error_log('$inventory_gift//robot = '.$inventory_gift['robot']->robot_token);
            //error_log('$inventory_gift//priority = '.$inventory_gift['priority']);
            //error_log('$inventory_gift//item = '.print_r($inventory_gift['item'], true));

            // Define this ability's attachment token
            $temp_rotate_amount = -15;
            $item_attachment_token = 'item_'.$inventory_gift['item'];
            $item_attachment_info = array(
                'class' => 'item',
                'sticky' => true,
                'attachment_token' => $item_attachment_token,
                'item_token' => $inventory_gift['item'],
                'item_frame' => 0,
                'item_frame_animate' => array(0),
                'item_frame_offset' => array('x' => 30, 'y' => 20, 'z' => 20),
                'item_frame_styles' => 'opacity: 0.75; transform: rotate('.$temp_rotate_amount.'deg); -webkit-transform: rotate('.$temp_rotate_amount.'deg); -moz-transform: rotate('.$temp_rotate_amount.'deg); '
                );

            // Generate an object representing this temporary item we can display it
            $this_gift_token = $inventory_gift['item'];
            $this_gift_item = rpg_game::get_item($this_battle, $this_player, $inventory_gift['robot'], array('item_token' => $this_gift_token));

            // Display the message about pulling out an item (be vague with the wording)
            $find_frame = !empty($this_skill->skill_parameters['frame']) ? $this_skill->skill_parameters['frame'] : 'summon';
            $this_robot->set_frame($find_frame);
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                ucfirst($this_robot->get_pronoun('subject')).' pulled out '.(preg_match('/^(a|e|i|o|u)/i', $this_gift_token) ? 'an' : 'a').' '.$this_gift_item->print_name().'!',
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


            // Attach the item to the recipient robot
            $inventory_gift['robot']->set_attachment($item_attachment_token, $item_attachment_info);

            // Collect details about the stat and kind we're dealing with
            list($item_stat, $item_kind) = explode('-', $item);
            if ($item_stat === 'weapon'){ $item_stat .= 's'; }
            $item_kind .= 's';
            //error_log('$item_stat = '.print_r($item_stat, true));
            //error_log('$item_kind = '.print_r($item_kind, true));

            // Since we're dealing with energy/weapon/stat-based consumables, we just apply each one a bit different
            if ($item_stat == 'energy' || $item_stat == 'weapons'){

                // Define which stat(s) we're boosting and by how much
                $stat_boost_amount = 0;
                $stat_boost_tokens = array($item_stat);
                if ($item_kind == 'pellets'){ $stat_boost_amount = 25; }
                elseif ($item_kind == 'capsules'){ $stat_boost_amount = 50; }
                elseif ($item_kind == 'tanks'){ $stat_boost_amount = 100; }
                //error_log('$stat_boost_amount = '.print_r($stat_boost_amount, true));
                //error_log('$stat_boost_tokens = '.print_r($stat_boost_tokens, true));

                // If we're dealing with an energy-based consumable
                if (in_array('energy', $stat_boost_tokens)){

                    $this_gift_item->recovery_options_update(array(
                        'kind' => 'energy',
                        'percent' => true,
                        'modifiers' => false,
                        'frame' => 'taunt',
                        'success' => array(9, 0, 0, -9999, $inventory_gift['robot']->print_name().'\'s life energy was restored!'),
                        'failure' => array(9, 0, 0, -9999, $inventory_gift['robot']->print_name().'\'s life energy was not affected...')
                        ));
                    $energy_recovery_amount = ceil($inventory_gift['robot']->robot_base_energy * ($stat_boost_amount / 100));
                    $inventory_gift['robot']->trigger_recovery($inventory_gift['robot'], $this_gift_item, $energy_recovery_amount);
                    //error_log('$energy_recovery_amount = '.print_r($energy_recovery_amount, true));

                }

                // If we're dealing with a weapons-based consumable
                if (in_array('weapons', $stat_boost_tokens)){

                    // Increase this robot's life energy stat
                    $this_gift_item->recovery_options_update(array(
                        'kind' => 'weapons',
                        'percent' => true,
                        'modifiers' => false,
                        'frame' => 'taunt',
                        'success' => array(9, 0, 0, -9999, $inventory_gift['robot']->print_name().'\'s weapon energy was restored!'),
                        'failure' => array(9, 0, 0, -9999, $inventory_gift['robot']->print_name().'\'s weapon energy was not affected...')
                        ));
                    $weapons_recovery_amount = ceil($inventory_gift['robot']->robot_base_weapons * ($stat_boost_amount / 100));
                    $inventory_gift['robot']->trigger_recovery($inventory_gift['robot'], $this_gift_item, $weapons_recovery_amount);
                    //error_log('$weapons_recovery_amount = '.print_r($weapons_recovery_amount, true));

                }

            }
            // Otherwise for all other stat-based consumables
            else {

                // Define the stat(s) this item will boost and how much
                $stat_boost_amount = 0;
                $stat_boost_tokens = array();
                if ($item_stat == 'super'){ $stat_boost_amount = $item_kind == 'capsules' ? 2 : 1; }
                else { $stat_boost_amount = $item_kind == 'capsules' ? 3 : 2; }
                if ($item_stat == 'attack' || $item_stat == 'super'){ $stat_boost_tokens[] = 'attack'; }
                if ($item_stat == 'defense' || $item_stat == 'super'){ $stat_boost_tokens[] = 'defense'; }
                if ($item_stat == 'speed' || $item_stat == 'super'){ $stat_boost_tokens[] = 'speed'; }
                //$this_battle->events_create(false, false, 'DEBUG', 'it was a basic stat item! <br /> $stat_boost_amount = '.$stat_boost_amount.' | $stat_boost_tokens = '.implode(',', $stat_boost_tokens));
                //error_log('$stat_boost_amount = '.print_r($stat_boost_amount, true));
                //error_log('$stat_boost_tokens = '.print_r($stat_boost_tokens, true));

                // Loop through and boost relevant stats as if this item was consumed
                if (!empty($stat_boost_tokens)){
                    foreach ($stat_boost_tokens AS $stat_token){
                        // Call the global stat boost function with customized options
                        rpg_ability::ability_function_stat_boost($inventory_gift['robot'], $stat_token, $stat_boost_amount, $this_skill);
                    }
                }

            }

            // Remove the visual item attachment as we're done with it
            $inventory_gift['robot']->unset_attachment($item_attachment_token);

        }

        // Return true on success
        return true;

    },
    'skill_function_onload' => function($objects){
        //error_log('skill_function_onload() for '.$objects['this_robot']->robot_token);

        // Extract objects into the global scope
        extract($objects);

        // If this skill has already been validated, don't do the work again
        if (isset($this_skill->flags['validated'])){ return; }

        // Default to this skill being validated and go from there
        $this_skill->set_flag('validated', true);

        // Now define an array of allowed items types (will be filtered later)
        $allowed_item_stats = array('energy', 'weapons', 'attack', 'defense', 'speed');
        $allowed_item_kinds = array('pellets', 'capsules', 'tanks');

        // We need to make sure at least one flag has been set, otherwise not validated
        $true_flags = array();
        $allowed_flags = array_merge(array('all'), $allowed_item_stats, $allowed_item_kinds);
        foreach ($allowed_flags AS $flag_name){
            if (isset($this_skill->skill_parameters[$flag_name])
                && !empty($this_skill->skill_parameters[$flag_name])){
                $true_flags[] = $flag_name;
            }
        }

        // Validate the "frame" parameter has been set to a valid value, else use default
        if (isset($this_skill->skill_parameters['frame'])){
            if (!is_string($this_skill->skill_parameters['frame'])){
                //error_log('skill parameter "frame" was not a string value ('.$this_skill->skill_token.':'.__LINE__.')');
                //error_log('frame = '.print_r($this_skill->skill_parameters['frame'], true));
                $this_skill->set_flag('validated', false);
                return false;
            }
        } else {
            // Otherwise make sure this at least exists
            $this_skill->skill_parameters['frame'] = '';
        }

        // If none of the allowed flags were set, we have a problem
        if (empty($true_flags)){
            //error_log('skill parameters were not set or were invalid ('.$this_skill->skill_token.':'.__LINE__.')');
            //error_log('allowed = '.print_r($allowed_flags, true));
            $this_skill->set_flag('validated', false);
            return false;
        }

        // If the "all" parameter was set to true, allow all items, else filter given parameters
        $allow_all_kinds = isset($this_skill->skill_parameters['all']) && !empty($this_skill->skill_parameters['all']) ? true : false;
        //error_log('$allow_all_kinds = '.print_r($allow_all_kinds, true));
        if (!$allow_all_kinds){
            foreach ($allowed_item_stats AS $key => $stat){
                if (!isset($this_skill->skill_parameters[$stat])
                    || empty($this_skill->skill_parameters[$stat])){
                    unset($allowed_item_stats[$key]);
                    continue;
                }
            }
            $allowed_item_stats = array_values($allowed_item_stats);
            //error_log('$allowed_item_stats = '.print_r($allowed_item_stats, true));
            foreach ($allowed_item_kinds AS $key => $kind){
                if (!isset($this_skill->skill_parameters[$kind])
                    || empty($this_skill->skill_parameters[$kind])){
                    unset($allowed_item_kinds[$key]);
                    continue;
                }
            }
            $allowed_item_kinds = array_values($allowed_item_kinds);
            //error_log('$allowed_item_kinds = '.print_r($allowed_item_kinds, true));
        }

        // Otherwise, define a variable with all the allowed drop types
        $this_skill->set_value('skill_allowed_item_stats', $allowed_item_stats);
        $this_skill->set_value('skill_allowed_item_kinds', $allowed_item_kinds);
        //error_log('final $allowed_item_stats = '.print_r($allowed_item_stats, true));
        //error_log('final $allowed_item_kinds = '.print_r($allowed_item_kinds, true));

        // Everything is fine so let's return true
        return true;

    }
);
?>
